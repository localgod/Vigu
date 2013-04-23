<?php
/**
 * This file is part of the Vigu PHP error aggregation system.
 *
 * PHP version 5.3+
 * 
 * @category  ErrorAggregation
 * @package   Vigu
 * @author    Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 * @copyright 2012 Copyright Jens Riisom Schultz, Johannes Skov Frandsen
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link      https://github.com/localgod/Vigu
 */
require_once dirname(__FILE__) . '/../lib/PHP-Daemon/Core/Daemon.php';
require_once dirname(__FILE__) . '/../lib/PHP-Daemon/Core/IPlugin.php';
require_once dirname(__FILE__) . '/../lib/PHP-Daemon/Core/Lock/Lock.php';
require_once dirname(__FILE__) . '/../lib/PHP-Daemon/Core/Lock/File.php';
require_once dirname(__FILE__) . '/../lib/PHP-Daemon/Core/Plugin/Ini.php';
require_once dirname(__FILE__) . '/RedisFunctions.php';
require_once dirname(__FILE__) . '/../lib/php-gearman-admin/GearmanAdmin.php';
/**
 * The Vigu Gearman Daemon runs on the server, to process incoming errors, using workers called "peons".
 *
 * @author Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 */
class ViguGearmanDaemon extends Core_Daemon {
    /** @var RedisFunctions The redis functions used for Redis communication. */
    private $_redis;

    /** @var GearmanClient The gearman job server connection. */
    private $_gearman;

    /** @var GearmanAdmin The gearman job server administration protocol aggregation. */
    private $_gearmanAdmin;

    /** @var integer The number of times the daemon has ticked since startup. */
    private $_ticks = 0;

    /**
     * Construct a new Daemon instance.
     *
     * @return null
     */
    protected function __construct()
    {
        // We want to our daemon to tick once every 2 seconds.
        $this->loop_interval = 2.00;

        // Set our Lock Provider
        $this->plugin('lock', new Core_Lock_File($this));
        $this->lock->daemon_name = __CLASS__;
        $this->lock->ttl = $this->loop_interval;
        $this->lock->path = '/var/run/';

        $this->plugin('ini', new Core_Plugin_Ini());
        $this->ini->filename = 'vigu.ini';

        parent::__construct();
    }

    /**
     * Set the daemon up.
     *
     * @return null
     */
    protected function setup()
    {
        if (!isset($this->ini['log'])) {
            $this->fatal_error('The configuration does not define the \'log\' setting.');
        }

        if (!isset($this->ini['ttl'])) {
            $this->fatal_error('The configuration does not define the \'ttl\' setting.');
        }

        if (isset($this->ini['redis'])) {
            $this->_redis = new RedisFunctions(
                    $this->ini['ttl'], $this->ini['redis']['host'], 
                    $this->ini['redis']['port'], $this->ini['redis']['timeout']);
        } else {
            $this->fatal_error('The configuration does not define a redis section.');
        }

        if (isset($this->ini['gearman'])) {
            $this->_gearman = new GearmanClient();
            $this->_gearman->addServer($this->ini['gearman']['host'], $this->ini['gearman']['port']);

            $this->_gearmanAdmin = new GearmanAdmin(
                    $this->ini['gearman']['host'], $this->ini['gearman']['port'], 
                    $this->ini['gearman']['timeout']);
        } else {
            $this->fatal_error('The configuration does not define a gearman section.');
        }

        if (!isset($this->ini['gearman']['workers'])) {
            $this->fatal_error('The configuration does not define [gearman] workers. 
                    You must set this to the number of gearman workers you want.');
        }

        if ($this->is_parent()) {
            $emails = array();
            if (isset($this->ini['emails'])) {
                foreach ($this->ini['emails'] as $email) {
                    $emails[] = $email;
                    $this->log("Adding $email to notification list.");
                }
            }
            $this->email_distribution_list = $emails;
        }

        $this->log('Gearman server status:');
        $this->log($this->_gearmanAdmin->getStatus());
        $this->log('Gearman server worker information:');
        $this->log($this->_gearmanAdmin->getWorkers());
    }

    /**
     * Checks for incoming errors and assigns workers to process them.
     *
     * This is where you implement the tasks you want your daemon to perform.
     * This method is called at the frequency defined by loop_interval.
     * If this method takes longer than 90% of the loop_interval, a Warning will be raised.
     *
     * @return null
     */
    protected function execute() 
    {
        if ($this->_ticks++ % 150 == 0) {
            $this->_cleanupAndCheckWorkers();
        }

        if (($incSize = $this->_redis->getIncomingSize()) > 0) {
            $this->log("$incSize items queued in Redis...");
            $status = $this->_gearmanAdmin->refreshStatus();
            $maxJobsToSchedule = $status->getAvailable('incoming') * 3 - $status->getTotal('incoming');
            while ($maxJobsToSchedule-- > 0 && count($data = $this->_redis->getIncoming())) {
                $this->_order($data);
            }
        }

        if (!$this->_gearman->runTasks()) {
            $this->fatal_error('ERROR: ' . $this->_gearman->error());
        }
    }

    /**
     * Performs a cleanup of the Redis db's and checks if any workers are missing.
     * If workers are missing it starts new ones in the background.
     *
     * @return null
     */
    private function _cleanupAndCheckWorkers() 
    {
        $this->log('Cleaning up...');
        $this->_redis->cleanIndexes();
        if (($missing = $this->ini['gearman']['workers'] - 
                $this->_gearmanAdmin->refreshStatus()->getAvailable('incoming')) > 0) {
            $this->log("I'm missing $missing peons...");
            for ($i = 0; $i < $missing; $i++) {
                $this->_newPeon();
            }
        }
    }

    /**
     * Order a peon to perform some work.
     *
     * @param array $data The work to perform. [hash, timestamp] pairs.
     */
    private function _order(array $data) 
    {
        $this->log('Ordering peon to work. (Chop down ' . count($data) .' trees)');

        $this->_gearman->addTaskBackground('incoming', json_encode($data));
    }

    /**
     * Starts a new peon in the background.
     *
     * @return null
     */
    private function _newPeon() 
    {
        $this->log('I\'m starting a new peon...');
        system("nohup php peon.php >/dev/null 2>&1 &");
    }

    /**
     * Gets the log file name from configuration.
     *
     * @return string
     */
    protected function log_file() 
    {
        return $this->ini['log'];
    }
}

// The daemon needs to know from which file it was executed.
ViguGearmanDaemon::setFilename(__file__);

// The run() method will start the daemon loop.
ViguGearmanDaemon::getInstance()->run();