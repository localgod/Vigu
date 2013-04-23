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
 * This is the Vigu error handler.
 * This class gathers errors during runtime execution, and sends them to a Redis server.
 *
 * @category ErrorAggregation
 * @package  Vigu
 * @author   Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 */
class ViguErrorHandler {
    /**
     * Contains all logged errors.
     *
     * @var array[]
     */
    private static $_log = array();

    /**
     * The host, port and timeout to use to connect to Redis.
     *
     * @var array
     */
    private static $_redisConnectionData;

    /**
     * These super globals get stripped from contexts before storing them.
     *
     * @var array
     */
    private static $_superGlobals = array('GLOBALS', '_SERVER', '_GET',
            '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST', '_ENV',);

    /**
     * These errors will be caught at shutdown.
     *
     * @var integer[]
     */
    private static $_shutdownErrors = array(E_ERROR, E_PARSE, E_CORE_ERROR,
            E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING);

    /**
     * Caches the host name.
     *
     * @var string
     */
    private static $_host;

    /**
     * The last logged key.
     *
     * @var string
     */
    private static $_lastLoggedKey;

    /**
     * True if this handler is enabled.
     *
     * @var boolean
     */
    private static $_enabled = false;

    /**
     * Read and parse vigu.ini.
     *
     * @return boolean True on success, false on failure.
     */
    public static function readConfig() {
        if (file_exists($iniFile = dirname(__FILE__) . '/vigu.ini')) {
            $config = parse_ini_file($iniFile, true);

            if (isset($config['redis'])) {
                self::$_redisConnectionData = $config['redis'];
            } else {
                trigger_error(
                        'Vigu shutdown handler could not determine the Redis connection data, from vigu.ini.',
                        E_USER_NOTICE);
                return false;
            }

            if (isset($config['enable_shutdown_handler'])
                    && $config['enable_shutdown_handler'] == 1) {
                self::$_enabled = true;
            }
            return true;
        } else {
            trigger_error('Vigu shutdown handler could not locate vigu.ini.',
                    E_USER_NOTICE);
            return false;
        }
    }

    /**
     * Hooks the error, exception and shutdown handlers up, if the shutdown handler is enabled in vigu.ini.
     *
     * @return null
     */
    public static function hookupIfEnabled() {
        if (self::$_enabled) {
            register_shutdown_function('ViguErrorHandler::shutdown');
            set_error_handler('ViguErrorHandler::error');
            set_exception_handler('ViguErrorHandler::exception');
        }
    }

    /**
     * Handle any fatal errors.
     *
     * @return void
     */
    public static function shutdown() {
        $lastError = error_get_last();
        $lastLoggedError = self::_getLastLoggedError();

        if ($lastError && in_array($lastError['type'], self::$_shutdownErrors)
                && !preg_match('/^Uncaught exception /', $lastError['message'])) {
            // Make sure that the last error has not already been logged
            if ($lastLoggedError
                    && $lastError['file'] == $lastLoggedError['file']
                    && $lastError['line'] == $lastLoggedError['line']
                    && $lastError['message'] == $lastLoggedError['message']
                    && self::_errnoToString($lastError['type'])
                            == $lastLoggedError['level']) {
                self::_send();
                return;
            }

            self::_logError($lastError['type'], $lastError['message'],
                    $lastError['file'], $lastError['line']);
        }

        self::_send();
    }

    /**
     * Handle any soft errors, ignoring @-suppressed errors.
     *
     * @param integer $errno      Error number.
     * @param string  $errstr     Message.
     * @param string  $errfile    File.
     * @param integer $errline    Line number.
     * @param array   $errcontext The context when the error occured.
     *
     * @return boolean Always returns false to continue error handling by other error handlers, and PHP itself.
     */
    public static function error($errno = 0, $errstr = '', $errfile = '',
            $errline = 0, $errcontext = null) {
        if (error_reporting() !== 0) {
            self::_logError($errno, $errstr, $errfile, $errline, $errcontext,
                    debug_backtrace(false));
        }

        return false;
    }

    /**
     * Handle any uncaught exceptions.
     *
     * @param Exception $exception Exception
     *
     * @return void
     */
    public static function exception(Exception $exception) {
        self::_logError(
                preg_replace('/(?:([a-z])([A-Z][a-z]))/', '$1 $2',
                        get_class($exception)), $exception->getMessage(),
                $exception->getFile(), $exception->getLine(), array(),
                $exception->getTrace());

        // Exceptions thrown in exception handlers are useless prior to 5.3.6, and <5.3 does not support inner exceptions.
        if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 5
                && PHP_MINOR_VERSION >= 3 && PHP_RELEASE_VERSION >= 6) {
            $ex = new Exception(
                    'Uncaught exception \'' . get_class($exception) . '\'', 42,
                    $exception);
            throw $ex;
        } else {
            trigger_error(
                    'Uncaught exception \'' . get_class($exception)
                            . "' with message '{$exception->getMessage()}' in {$exception
                                    ->getFile()} on line {$exception->getLine()}",
                    E_USER_ERROR);
        }
    }

    /**
     * Convert an error number to a string.
     *
     * @param integer $errno Error number
     *
     * @return string
     */
    private static function _errnoToString($errno) {
        switch ($errno) {
        // Default
        default:
            return sprintf('UNKNOWN[%d/%b]', $errno, $errno);

        // PHP 5.2+ error types
        case E_ERROR:
            return 'ERROR';
        case E_WARNING:
            return 'WARNING';
        case E_PARSE:
            return 'PARSE';
        case E_NOTICE:
            return 'NOTICE';
        case E_CORE_ERROR:
            return 'CORE ERROR';
        case E_CORE_WARNING:
            return 'CORE WARNING';
        case E_COMPILE_ERROR:
            return 'COMPILE ERROR';
        case E_COMPILE_WARNING:
            return 'COMPILE WARNING';
        case E_USER_ERROR:
            return 'USER ERROR';
        case E_USER_WARNING:
            return 'USER WARNING';
        case E_USER_NOTICE:
            return 'USER NOTICE';
        case E_STRICT:
            return 'STRICT';
        case E_RECOVERABLE_ERROR:
            return 'RECOVERABLE ERROR';

        // PHP 5.3+ only
        case defined('E_DEPRECATED') ? E_DEPRECATED : 10000000:
            return 'DEPRECATED';
        case defined('E_USER_DEPRECATED') ? E_USER_DEPRECATED : 10000000:
            return 'USER DEPRECATED';
        }
    }

    /**
     * Log an error.
     *
     * @param integer|string $errno      The error number.
     * @param string         $message    The error message.
     * @param string         $file       The file.
     * @param integer        $line       The line number.
     * @param array          $context    The error context (variables available).
     * @param array[]        $stacktrace The stacktrace, as produced by debug_backtrace().
     *
     * @return void
     */
    private static function _logError($errno, $message, $file, $line,
            $context = array(), $stacktrace = array()) {
        $level = is_string($errno) ? $errno : self::_errnoToString($errno);
        $host = self::_getHost();

        $key = md5($level . self::_getHost() . $file . $line . $message);

        if (!isset(self::$_log[$key])) {
            array_shift($stacktrace);
            self::$_log[$key] = array('host' => $host, 'level' => $level,
                    'message' => $message, 'file' => $file, 'line' => $line,
                    'context' => self::_cleanContext($context),
                    'stacktrace' => self::_cleanStacktrace($stacktrace),
                    'count' => 1,);

            if (count(self::$_log) >= 100) {
                self::_send();
            }
        } else {
            self::$_log[$key]['count']++;
        }
        self::$_lastLoggedKey = $key;
    }

    /**
     * Get the last logged error.
     *
     * @return array|null
     */
    private static function _getLastLoggedError() {
        if (!empty(self::$_log)) {
            return self::$_log[self::$_lastLoggedKey];
        } else {
            return null;
        }
    }

    /**
     * Clean a stacktrace, stripping class instances and array contents.
     *
     * @param array &$stacktrace Stacktrace
     *
     * @return array The cleaned stacktrace.
     */
    private static function _cleanStacktrace(&$stacktrace) {
        $newStacktrace = array();

        foreach ($stacktrace as &$line) {
            $newLine = array('args' => array(), 'function' => '', 'line' => 0,
                    'file' => '', 'class' => '', 'type' => '',);
            if (isset($line['args']))
                foreach ($line['args'] as $name => &$arg) {
                    switch (true) {
                    case is_object($arg):
                        $newLine['args'][$name] = 'instance of '
                                . get_class($arg);
                        break;
                    case is_array($arg):
                        $newLine['args'][$name] = 'array[' . count($arg) . ']';
                        break;
                    default:
                        $newLine['args'][$name] = $arg;
                        break;
                    }
                }
            if (isset($line['function']))
                $newLine['function'] = $line['function'];
            if (isset($line['line']))
                $newLine['line'] = $line['line'];
            if (isset($line['file']))
                $newLine['file'] = $line['file'];
            if (isset($line['class']))
                $newLine['class'] = $line['class'];
            if (isset($line['type']))
                $newLine['type'] = $line['type'];
            $newStacktrace[] = $newLine;
        }

        return $newStacktrace;
    }

    /**
     * Clean a context array of superglobals, class instances and arrays.
     *
     * @param array $context Execution context
     *
     * @return array The cleaned context.
     */
    private static function _cleanContext($context) {
        $newContext = array();

        foreach ($context as $key => $var) {
            if (array_search($key, self::$_superGlobals) === false) {
                switch (true) {
                case is_object($var):
                    $newContext[$key] = 'instance of ' . get_class($var);
                    break;
                case is_array($var):
                    $newContext[$key] = 'array[' . count($var) . ']';
                    break;
                default:
                    $newContext[$key] = $var;
                    break;
                }
            }
        }

        return $newContext;
    }

    /**
     * Get the host name.
     *
     * @return string
     */
    private static function _getHost() {
        if (!isset(self::$_host)) {
            if (isset($_SERVER['HTTP_HOST'])) {
                self::$_host = $_SERVER['HTTP_HOST'];
            } else if (function_exists('gethostname')
                    && ($host = gethostname())) {
                self::$_host = $host;
            } else {
                self::$_host = 'Unknown';
            }
        }

        return self::$_host;
    }

    /**
     * Send the errors to Redis, to be consumed by the daemon.
     * Errors will be discarded if Redis is not reachable or the phpredis extension is not loaded.
     *
     * @return void
     */
    private static function _send() {
        if (!empty(self::$_log)) {
            if (class_exists('Redis')) {
                $redis = new Redis();
                try {
                    if ($redis
                            ->pconnect(self::$_redisConnectionData['host'],
                                    self::$_redisConnectionData['port'],
                                    self::$_redisConnectionData['timeout'])) {
                        $redis
                                ->setOption(Redis::OPT_SERIALIZER,
                                        Redis::SERIALIZER_PHP);
                        $redis->select(3);
                        $time = time();
                        $redis->multi(Redis::PIPELINE);
                        foreach (self::$_log as $md5 => $line) {
                            $redis
                                    ->rPush('incoming',
                                            array($md5, $time, $line['count']));
                            unset($line['count']);
                            $redis->set($md5, $line);
                        }
                        $redis->exec();
                        $redis->close();
                    }
                    unset($redis);
                } catch (RedisException $ex) {
                    // Ignore
                }
            }
            self::$_log = array();
        }
    }
}

if (ViguErrorHandler::readConfig()) {
    ViguErrorHandler::hookupIfEnabled();
} else {
    trigger_error('Vigu could not be configured. Data will not be gathered.',
            E_USER_WARNING);
}
