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
/**
 * The Vigu Daemon runs on the server, to process incoming errors.
 *
 * @author Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 */
class ViguDaemon extends Core_Daemon {
    /** @var RedisFunctions The redis functions used for Redis communication. */
    private $_redis;

    /**
     * The time the daemon was started, in microtime.
     *
     * @var float
     */
    private $_startTime;

    /**
     * Construct a new Daemon instance.
     *
     * @return null
     */
    protected function __construct() {
        // We want to our daemon to tick once every 1 second.
        $this->loop_interval = 1.00;

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
     * Connects three Redis clients, and configures log, ttl and email notifications.
     *
     * @return null
     */
    protected function setup() {
        if (!isset($this->ini['log'])) {
            $this->fatal_error('The configuration does not define the \'log\' setting.');
        }

        if (!isset($this->ini['ttl'])) {
            $this->fatal_error('The configuration does not define the \'ttl\' setting.');
        }

        if (isset($this->ini['redis'])) {
            $this->_redis = new RedisFunctions($this->ini['ttl'], $this->ini['redis']['host'], $this->ini['redis']['port'], $this->ini['redis']['timeout']);
        } else {
            $this->fatal_error('The configuration does not define a redis section.');
        }

        if ($this->is_parent()) {
            $emails = array();
            if (isset($this->ini['emails'])) foreach ($this->ini['emails'] as $email) {
                $emails[] = $email;
                $this->log("Adding $email to notification list.");
            }
            $this->email_distribution_list = $emails;
        }

        $this->_startTime = microtime(true);
    }

    /**
     * Checks for incoming errors and processes them.
     *
     * This is where you implement the tasks you want your daemon to perform.
     * This method is called at the frequency defined by loop_interval.
     * If this method takes longer than 90% of the loop_interval, a Warning will be raised.
     *
     * @return null
     */
    protected function execute() {
        /** @var float */
        static $lastCleanUpTime = -999999999;

        $incCount = $this->_redis->getIncomingSize();
        if ($incCount > 0) {
            $this->log("$incCount elements in queue.");
            $this->_redis->processMultiple($this->_redis->getIncoming(2000));
        }

        if ($this->_upTime() - $lastCleanUpTime > $this->ini['ttl']) {
            $this->_redis->cleanIndexes();
            $lastCleanUpTime = $this->_upTime();
        }
    }

    /**
     * Get the Daemon uptime.
     *
     * @return float
     */
    private function _upTime() {
        return microtime(true) - $this->_startTime;
    }

    /**
     * Gets the log file name from configuration.
     *
     * @return string
     */
    protected function log_file() {
        return $this->ini['log'];
    }
}

// The daemon needs to know from which file it was executed.
ViguDaemon::setFilename(__file__);

// The run() method will start the daemon loop.
ViguDaemon::getInstance()->run();
