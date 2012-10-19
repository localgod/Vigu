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
 * Logged lines as a model.
 * 
 * @category ErrorAggregation
 * @package  Vigu
 * @author   Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 */
class ApiPublicModelLine extends ApiPublicModel {
	/**
	 * @var string
	 */
	const COUNTS_PREFIX = '|counts|';

	/**
	 * @var string
	 */
	const TIMESTAMPS_PREFIX = '|timestamps|';

	/**
	 * @var string
	 */
	const SEARCH_PREFIX = '|search|';

	/**
	 * @var string
	 */
	const LEVEL_PREFIX = '|level|';

	/**
	 * @var string
	 */
	const WORD_PREFIX = '|word|';

	/**
	 * The name of the list of handled keys.
	 *
	 * @var string
	 */
	const HANDLED_INDEX = '|handled|';

	/**
	 * @var Redis
	 */
	private static $_storageRedis;

	/**
	 * @var Redis
	 */
	private static $_indexingRedis;

	/**
	 * @var string
	 */
	private $_message;

	/**
	 * @var string
	 */
	private $_level;

	/**
	 * @var integer
	 */
	private $_first;

	/**
	 * @var integer
	 */
	private $_last;

	/**
	 * @var string
	 */
	private $_file;

	/**
	 * @var integer
	 */
	private $_line;

	/**
	 * @var string
	 */
	private $_host;

	/**
	 * @var array
	 */
	private $_context;

	/**
	 * @var array
	 */
	private $_stacktrace;

	/**
	 * @var integer
	 */
	private $_count;

	/**
	 * @var boolean
	 */
	private $_isHandled;

	/**
	 * Get an existing Line, by key.
	 *
	 * @param string $key Key name
	 *
	 * @return null
	 *
	 * @throws RuntimeException If the key does not match a line.
	 */
	public function __construct($key) {
		if (!($values = self::_getStorageRedis()->get($key)) || empty($values)) {
			throw new RuntimeException("No line with key, $key, found.");
		}

		foreach ($values as $key => $value) {
			$this->{"_$key"} = $value;
		}
	}

	/**
	 * Get the Redis key of this log line.
	 *
	 * @return integer
	 */
	public function getKey() {
		return md5($this->_level . $this->_host . $this->_file . $this->_line . $this->_message);
	}

	/**
	 * Get the number of times this line has been logged.
	 *
	 * @return integer
	 */
	public function getCount() {
		if (!isset($this->_count)) {
			$redis = self::_getIndexingRedis();

			$this->_count = (integer)$redis->zScore(self::COUNTS_PREFIX, $this->getKey());
		}

		return $this->_count;
	}

	/**
	 * Get the message.
	 *
	 * @return string
	 */
	public function getMessage() {
		return $this->_message;
	}

	/**
	 * Get the error level.
	 *
	 * @return string
	 */
	public function getLevel() {
		return $this->_level;
	}

	/**
	 * Get the timestamp when this line was first logged.
	 *
	 * @return integer
	 */
	public function getFirst() {
		return $this->_first;
	}

	/**
	 * Get the timestamp when this line was most recently logged.
	 *
	 * @return integer
	 */
	public function getLast() {
		return $this->_last;
	}

	/**
	 * Get the file.
	 *
	 * @return string
	 */
	public function getFile() {
		return $this->_file;
	}

	/**
	 * Get the line.
	 *
	 * @return integer
	 */
	public function getLine() {
		return $this->_line;
	}

	/**
	 * Get the host.
	 *
	 * @return string
	 */
	public function getHost() {
		return $this->_host;
	}

	/**
	 * Get the context.
	 *
	 * @return array
	 */
	public function getContext() {
		return $this->_context;
	}

	/**
	 * Get the stacktrace.
	 *
	 * @return array
	 */
	public function getStacktrace() {
		return $this->_stacktrace;
	}

	/**
	 * Get whether or not this line is handled.
	 *
	 * @return boolean True if the line is handled.
	 */
	public function isHandled() {
		if (!isset($this->_isHandled)) {
			$redis = self::_getIndexingRedis();
			$this->_isHandled = $redis->zScore(self::HANDLED_INDEX, $this->getKey()) == 1.0;
		}
		return $this->_isHandled;
	}

	/**
	 * Get an error of all registered error levels.
	 *
	 * @return array
	 */
	public static function getAllLevels() {
		$redis = self::_getIndexingRedis();

		$levelIndexes = $redis->keys(self::LEVEL_PREFIX . '*');
		foreach ($levelIndexes as &$level) {
			$level = substr($level, strlen(self::LEVEL_PREFIX));
		}

		return $levelIndexes;
	}

	/**
	 * Get lines ordered by their last timestamp, descending.
	 *
	 * @param integer $offset  Result offset
	 * @param integer $limit   Result limit
	 * @param string  $path    An optional path search string.
	 * @param string  $level   An optional error level to filter by.
	 * @param boolean $handled Get handled errors?
	 *
	 * @return ApiPublicModelLine[]
	 * @throws RuntimeException if failing to create a new ApiPublicModelLine
	 */
	public static function getMostRecent($offset, $limit, $path = null, $level = null, $handled = false) {
		return self::_getByPrefix(self::TIMESTAMPS_PREFIX, $offset, $limit, $path, $level, $handled);
	}

	/**
	 * Get lines ordered by count, descending.
	 *
	 * @param integer $offset  Result offset
	 * @param integer $limit   Result limit
	 * @param string  $path    An optional path search string.
	 * @param string  $level   An optional error level to filter by.
	 * @param boolean $handled Get handled errors?
	 *
	 * @return ApiPublicModelLine[]
	 * @throws RuntimeException if failing to create a new ApiPublicModelLine
	 */
	public static function getMostTriggered($offset, $limit, $path = null, $level = null, $handled = false) {
		return self::_getByPrefix(self::COUNTS_PREFIX, $offset, $limit, $path, $level, $handled);
	}

	/**
	 * Get the total number of lines.
	 *
	 * @param string  $path    An optional path search string.
	 * @param string  $level   An optional error level to filter by.
	 * @param boolean $handled Get handled errors?
	 *
	 * @return integer
	 */
	public static function getTotal($path = null, $level = null, $handled = false) {
		$redis = self::_getIndexingRedis();

		$id = uniqid(self::SEARCH_PREFIX, true);

		$zinters = array(self::COUNTS_PREFIX);

		if ($path !== null) {
			foreach (self::_splitPath($path) as $val) {
				$zinters[] = self::WORD_PREFIX . strtolower($val);
			}
		}

		if ($level !== null) {
			$zinters[] = self::LEVEL_PREFIX . $level;
		}

		$redis->zInter($id, $zinters);

		if ($handled == false) {
			foreach ($redis->zRange(self::HANDLED_INDEX, 0, -1) as $hash) {
				$redis->zRem($id, $hash);
			}
		}

		$total = $redis->zCard($id);
		
		$redis->del($id);

		return $total;
	}

	/**
	 * Get lines ordered by timestamp or count, descending.
	 *
	 * @param string  $prefix  The index prefix.
	 * @param integer $offset  Result offset
	 * @param integer $limit   Result limit
	 * @param string  $path    An optional path search string.
	 * @param string  $level   An optional error level to filter by.
	 * @param boolean $handled Get handled errors?
	 *
	 * @return ApiPublicModelLine[]
	 */
	private static function _getByPrefix($prefix, $offset, $limit, $path = null, $level = null, $handled = false) {
		$redis = self::_getIndexingRedis();
		$start = $offset;
		$end   = $start + ($limit - 1);

		$id = uniqid(self::SEARCH_PREFIX, true);

		$zinters = array($prefix);

		if ($path !== null) {
			foreach (self::_splitPath($path) as $val) {
				$zinters[] = self::WORD_PREFIX . strtolower($val);
			}
		}

		if ($level !== null) {
			$zinters[] = self::LEVEL_PREFIX . $level;
		}

		$redis->zInter($id, $zinters);

		if ($handled == false) {
			foreach ($redis->zRange(self::HANDLED_INDEX, 0, -1) as $hash) {
				$redis->zRem($id, $hash);
			}
		}

		$result = array();
		foreach ($redis->zRevRange($id, $start, $end) as $key) {
			$result[] = new ApiPublicModelLine($key);
		}

		$redis->del($id);

		return $result;
	}


	/**
	 * Mark the line as handled.
	 *
	 * @return null
	 */
	public function handle() {
		$redis = self::_getIndexingRedis();

		$redis->zAdd(self::HANDLED_INDEX, 1.0, $this->getKey());

		$this->_isHandled = true;
	}

	/**
	 * Unmark the line as handled.
	 *
	 * @return null
	 */
	public function unhandle() {
		$redis = self::_getIndexingRedis();

		$redis->zRem(self::HANDLED_INDEX, $this->getKey());

		$this->_isHandled = false;
	}

	/**
	 * Get the Redis client used for storage.
	 *
	 * @return Redis
	 *
	 * @throws RedisException
	 * @throws RuntimeException
	 */
	private static function _getStorageRedis() {
		if (self::$_storageRedis == null) {
			self::$_storageRedis = self::_getRedis();
			self::$_storageRedis->select(1);
		}

		return self::$_storageRedis;
	}
	/**
	 * Get the Redis client used for indexing.
	 *
	 * @return Redis
	 *
	 * @throws RedisException
	 * @throws RuntimeException
	 */
	private static function _getIndexingRedis() {
		if (self::$_indexingRedis == null) {
			self::$_indexingRedis = self::_getRedis();
			self::$_indexingRedis->select(2);
		}

		return self::$_indexingRedis;
	}

	/**
	 * Connect a new Redis client.
	 *
	 * @return Redis The newly connected client.
	 *
	 * @throws RedisException
	 * @throws RuntimeException
	 */
	private static function _getRedis() {
		$redis = new Redis();
		if (!$redis->connect(self::_config('host'), self::_config('port'), self::_config('timeout'))) {
			throw new RuntimeException("Could not connect to Redis server.");
		}
		$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

		return $redis;
	}

	/**
	 * Split a path to an array of words.
	 *
	 * @param string $path Input path
	 *
	 * @return array
	 */
	private static function _splitPath($path) {
		return array_filter(preg_split('#[/\\\\\\\.: -]#', $path));
	}
}