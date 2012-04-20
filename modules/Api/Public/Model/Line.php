<?php
/**
 * Logged lines as a model.
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
	 * Get an existing Line, by key.
	 *
	 * @param string $key
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
	 * Get lines ordered by their last timestamp, descending.
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @param string  $path   An optional path search string.
	 *
	 * @return ApiPublicModelLine[]
	 */
	public static function getMostRecent($offset, $limit, $path = null) {
		return self::_getByPrefix(self::TIMESTAMPS_PREFIX, $offset, $limit, $path);
	}

	/**
	 * Get lines ordered by count, descending.
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @param string  $path   An optional path search string.
	 *
	 * @return ApiPublicModelLine[]
	 */
	public static function getMostTriggered($offset, $limit, $path = null) {
		return self::_getByPrefix(self::COUNTS_PREFIX, $offset, $limit, $path);
	}

	/**
	 * Get the total number of lines.
	 *
	 * @param string $path An optional path search string.
	 *
	 * @return integer
	 */
	public static function getTotal($path = null) {
		$redis = self::_getIndexingRedis();

		if ($path === null) {
			return $redis->zCard(self::COUNTS_PREFIX);
		} else {
			$search = self::_splitPath($path);
			foreach ($search as &$val) {
				$val = self::COUNTS_PREFIX . strtolower($val);
			}

			$id = uniqid(self::SEARCH_PREFIX, true);
			$total = $redis->zInter($id, $search);
			$redis->del($id);

			return $total;
		}
	}

	/**
	 * Get lines ordered by timestamp or count, descending.
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @param string  $path   An optional path search string.
	 *
	 * @return ApiPublicModelLine[]
	 */
	private static function _getByPrefix($prefix, $offset, $limit, $path = null) {
		$redis = self::_getIndexingRedis();
		$start = $offset;
		$end   = $start + ($limit - 1);

		$result = array();
		if ($path === null) {
			foreach ($redis->zRevRange($prefix, $start, $end) as $key) {
				$result[] = new ApiPublicModelLine($key);
			}
		} else {
			$search = self::_splitPath($path);
			foreach ($search as &$val) {
				$val = $prefix . strtolower($val);
			}

			$id = uniqid(self::SEARCH_PREFIX, true);
			$redis->zInter($id, $search);
			foreach ($redis->zRevRange($id, $start, $end) as $key) {
				$result[] = new ApiPublicModelLine($key);
			}
			$redis->del($id);
		}

		return $result;
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
	 * @param string $path
	 *
	 * @return array
	 */
	private static function _splitPath($path) {
		return array_filter(preg_split('#[/\\\\\\\.: -]#', $path));
	}
}
