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
 * Actions to provide data for the frontend.
 * 
 * @category ErrorAggregation
 * @package  Vigu
 * @author   Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 * @author   Johannes Skov Frandsen <localgod@heaven.dk>
 */
class ApiPublicControllerLog extends ApiPublicController {
	/**
	 * Data for the frontend table.
	 *
	 * @param integer $rows    <10>        Number of rows to return
	 * @param integer $page    <1>         Result offset
	 * @param string  $sidx    <timestamp> Field to sort by, 'timestamp' or 'count'.
	 * @param string  $path    <null>      Limit search to match a specific path (default is null = any file path)
	 * @param boolean $handled <false>     Set to true, to not filter handled errors out.
	 * @param string  $level   <null>      Set this to an error level, to only get errors of that level.
	 *
	 * @return void
	 */
	public function gridAction($rows, $page, $sidx, $path, $handled, $level) {
		$timeStart = microtime(true);
		$offset = $rows * ($page - 1);
		$limit  = $rows;

		try {
			switch (true) {
				case $sidx == 'timestamp':
					$lines = ApiPublicModelLine::getMostRecent($offset, $limit, $path, $level, $handled);
					break;
				case $sidx == 'count':
					$lines = ApiPublicModelLine::getMostTriggered($offset, $limit, $path, $level, $handled);
					break;
				default:
					throw new RuntimeException("You cannot order by $sidx.");
			}

			$total = ApiPublicModelLine::getTotal($path, $level, $handled);
		} catch (RuntimeException $ex) {
			$this->assign('error', $ex->getMessage());
			return;
		}

		$rows = array();
		foreach ($lines as $line) {
			$rows[] = array(
				'key'  => $line->getKey(),
				'cell' => array(
					$line->getLevel(),
					$line->getHost(),
					$line->getMessage(),
					date('Y-m-d H:i:s', $line->getLast()),
					$line->getCount(),
					$line->isHandled(),
				),
			);

			$count = count($rows);

			$this->assign('page', $page);
			$this->assign('total', $count > 0 ? ceil($total / $limit) : 0);
			$this->assign('records', $total);
			$this->assign('rows', $rows);
		}
		$this->assign('time', microtime(true) - $timeStart);
	}

	/**
	 * Get the details for a given log line, by key.
	 *
	 * @param string $key <null>
	 *
	 * @return void
	 */
	public function detailsAction($key) {
		if ($key === null) {
			$this->assign('error', 'No key given - cannot show details.');
			return;
		}

		try {
			$line = new ApiPublicModelLine($key);
		} catch (RuntimeException $e) {
			$this->assign('error', get_class($e) . ': ' . $e->getMessage());
		}

		$this->assign(
			'details',
			array(
				'host'       => $line->getHost(),
				'last'       => date('Y-m-d H:i:s', $timestampMax = $line->getLast()),
				'first'      => date('Y-m-d H:i:s', $timestampMin = $line->getFirst()),
				'level'      => $line->getLevel(),
				'message'    => $line->getMessage(),
				'file'       => $line->getFile(),
				'line'       => $line->getLine(),
				'context'    => $line->getContext(),
				'stacktrace' => $line->getStacktrace(),
				'count'      => $count = $line->getCount(),
				'frequency'  => ($count / (max(1, time() - $timestampMin))) * 3600,
				'handled'    => $line->isHandled(),
				'key'		 => $key,
			)
		);
	}

	/**
	 * Get all registered error levels.
	 *
	 * @return void
	 */
	public function errorLevelsAction() {
		$levels = ApiPublicModelLine::getAllLevels();
		sort($levels);
		$this->assign('levels', $levels);
	}

	/**
	 * Mark an error as handled. It will stay handled for the configured ttl.
	 *
	 * @param string $key The key of the error to mark.
	 * 
	 * @return void
	 */
	public function handleAction($key) {
		try {
			$line = new ApiPublicModelLine($key);
		} catch (RuntimeException $e) {
			$this->assign('error', get_class($e) . ': ' . $e->getMessage());
		}

		$line->handle();
	}

	/**
	 * Unmark a previously handled error.
	 *
	 * @param string $key The key of the error to unmark.
	 * 
	 * @return void
	 */
	public function unHandleAction($key) {
		try {
			$line = new ApiPublicModelLine($key);
		} catch (RuntimeException $e) {
			$this->assign('error', get_class($e) . ': ' . $e->getMessage());
		}

		$line->unhandle();
	}
}
