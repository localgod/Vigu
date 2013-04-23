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
/**
 * Handles all Redis communication. Used by the daemon and the gearman workers and client.
 * 
 * @category ErrorAggregation
 * @package  Vigu
 * @author   Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 */
class RedisFunctions {
    /** @var string The prefix to use for counts indexes. */
    const COUNTS_PREFIX = '|counts|';

    /** @var string The prefix to use for timestamps indexes. */
    const TIMESTAMPS_PREFIX = '|timestamps|';

    /** @var string The prefix to use for the error level indexes. */
    const LEVEL_PREFIX = '|level|';

    /** @var string The prefix to use for temporary indexes. */
    const SEARCH_PREFIX = '|search|';

    /** @var string The prefix to use for word indexes. */
    const WORD_PREFIX = '|word|';

    /** @var string The Redis host. */
    private $_host;

    /** @var integer The Redis port. */
    private $_port;

    /** @var float The Redis connection timeout. */
    private $_timeout;

    /** @var Redis The Redis connection. */
    private $_redis;

    /** @var integer The time to live of stored errors. */
    private $_ttl;

    /**
     * Construct a new instance with the given connection parameters.
     *
     * @param integer $ttl     The time to live, in seconds, of stored errors.
     * @param string  $host    The Redis host.
     * @param integer $port    The Redis port.
     * @param float   $timeout The Redis connection timeout.
     *
     * @return null
     *
     * @throws RedisException On Redis connection error.
     */
    public function __construct($ttl, $host = 'localhost', $port = 6379,
            $timeout = 0) {
        $this->_ttl = $ttl;
        $this->_host = $host;
        $this->_port = $port;
        $this->_timeout = $timeout;
        $this->_connect();
    }

    /**
     * Connect to the Redis server.
     *
     * @return null
     */
    private function _connect() {
        try {
            unset($this->_redis);
        } catch (RedisException $ex) {
            // Do nothing
        }
        $this->_redis = new Redis();
        if (!$this->_redis
                ->connect($this->_host, $this->_port, $this->_timeout)) {
            unset($this->_redis);
        } else {
            $this->_redis
                    ->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        }
    }

    /**
     * Ensure that the Redis connection is unset when the instance is unset.
     *
     * @throws RedisException On Redis connection error.
     *
     * @return null
     */
    public function __destruct() {
        unset($this->_redis);
    }

    /**
     * Ready the Redis connection and select the given db.
     *
     * @param integer $db The db to select.
     *
     * @return null
     *
     * @throws RedisException On Redis connection error.
     */
    private function _readys($db) {
        try {
            $this->_redis->select($db);
        } catch (RedisException $ex) {
            $this->_connect();
            $this->_redis->select($db);
        }
    }

    /**
     * Process a hash/timestamp combo.
     *
     * @param string  $hash      Hash value
     * @param integer $timestamp Timestamp
     * @param integer $count     The number of times the error was triggered.
     *
     * @return null
     */
    public function process($hash, $timestamp, $count) {
        $this->_readys(3);

        if (($line = $this->_redis->get($hash)) === false) {
            $line = null;
        }
        $this->_redis->expire($hash, 60);

        $line = $this->store($hash, $timestamp, $line);
        $this->index($hash, $timestamp, $line, $count);
    }

    /**
     * Process an array of hash/timestamp combos.
     *
     * @param array $hashAndTimestamps An array containing arrays of the form [hash, timestamp].
     * 
     * @return void
     */
    public function processMultiple(array $hashAndTimestamps) {
        foreach ($hashAndTimestamps as $hashAndTimestamp) {
            list($hash, $timestamp, $count) = $hashAndTimestamp;
            $this->process($hash, $timestamp, $count);
        }
    }

    /**
     * Store an incoming error.
     *
     * @param string     $hash      Hash value
     * @param integer    $timestamp Timestamp
     * @param array|null $line      Line
     *
     * @return array The stored error.
     */
    public function store($hash, $timestamp, array $line = null) {
        $this->_readys(1);

        if ($line === null) {
            $line = $this->_redis->get($hash);
        }

        if ($oldLine = $this->_redis->get($hash)) {
            if ($oldLine['last'] < $timestamp) {
                $line['last'] = $timestamp;
            }
            if ($oldLine['first'] > $timestamp) {
                $line['first'] = $timestamp;
            } else {
                $line['first'] = $oldLine['first'];
            }
            if ($oldLine['last'] < $timestamp) {
                $line['last'] = $timestamp;
            } else {
                $line['last'] = $oldLine['last'];
            }

            $this->_redis->setex($hash, $this->_ttl + 360, $line);
        } else {
            $line['first'] = $timestamp;
            $line['last'] = $timestamp;
            $this->_redis->setex($hash, $this->_ttl + 360, $line);
        }

        return $line;
    }

    /**
     * Index an incoming error.
     *
     * @param string  $hash      Hash value
     * @param integer $timestamp Timestamp
     * @param array   $line      Line
     * @param integer $count     The number of times the error was triggered.
     *
     * @return null
     */
    public function index($hash, $timestamp, array $line, $count) {
        $this->_readys(2);

        $oldLastTimestamp = $this->_redis
                ->zScore(self::TIMESTAMPS_PREFIX, $hash);

        $this->_redis->multi(Redis::PIPELINE);

        $this->_redis->zIncrBy(self::COUNTS_PREFIX, $count, $hash);

        if ($timestamp > $oldLastTimestamp) {
            $this->_redis->zAdd(self::TIMESTAMPS_PREFIX, $timestamp, $hash);
        } else {
            $timestamp = $oldLastTimestamp;
        }
        foreach ($this->splitPath($line['file']) as $word) {
            $this->_redis
                    ->zAdd(self::WORD_PREFIX . strtolower($word), 1.0, $hash);
        }
        $this->_redis->zAdd(self::LEVEL_PREFIX . $line['level'], 1.0, $hash);

        $this->_redis->exec();
    }

    /**
     * Clean the indexes of timed out errors.
     *
     * @return null
     */
    public function cleanIndexes() {
        $this->_readys(2);

        $hashes = $this->_redis
                ->zRangeByScore(self::TIMESTAMPS_PREFIX, 0,
                        time() - $this->_ttl);

        $indexes = $this->_redis->keys('*');

        $this->_redis->multi(Redis::PIPELINE);
        foreach ($hashes as $hash) {
            foreach ($indexes as $index) {
                $this->_redis->zRem($index, $hash);
            }
        }
        $this->_redis->exec();
    }

    /**
     * Get an array of incoming hashes/timestamps
     *
     * @param integer $limit The maximum number of incoming elements to retrieve.
     *
     * @return array An array of [string key, integer timestamp, integer count].
     */
    public function getIncoming($limit = 1000) {
        $this->_readys(3);

        $amount = min($limit, $this->getIncomingSize());
        $inc = $this->_redis->lGetRange('incoming', 0, $amount - 1);
        $this->_redis->lTrim('incoming', $amount, -1);

        return $inc;
    }

    /**
     * Get the number of elements in the incoming queue.
     *
     * @return integer
     */
    public function getIncomingSize() {
        $this->_readys(3);

        return $this->_redis->lSize('incoming');
    }

    /**
     * Flush all the DB's. Only used for testing.
     *
     * @return boolean Always true.
     */
    public function flushAll() {
        $this->_readys(0);

        return $this->_redis->flushAll();
    }

    /**
     * Split a path to an array of words.
     *
     * @param string $path Input path
     *
     * @return array
     */
    public function splitPath($path) {
        return array_filter(preg_split('#[/\\\\\\\.: -]#', $path));
    }
}
