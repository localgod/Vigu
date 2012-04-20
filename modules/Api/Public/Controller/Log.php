<?php
/**
 * Awesome actions to provide data for the frontend.
 *
 * PHP version 5
 *
 * @category TODO_DOCUMENT_ME
 * @package  TODO_DOCUMENT_ME
 * @author   Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 * @author   Johannes Skov Frandsen <localgod@heaven.dk>
 */

/**
 * Awesome actions to provide data for the frontend.
 *
 * @category TODO_DOCUMENT_ME
 * @package  TODO_DOCUMENT_ME
 * @author   Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 * @author   Johannes Skov Frandsen <localgod@heaven.dk>
 */
class ApiPublicControllerLog extends ApiPublicController {
	/**
	 * Data for the frontend table.
	 *
	 * @param integer $rows <10>        Number of rows to return
	 * @param integer $page <1>         Result offset
	 * @param string  $sidx <timestamp> Field to sort by, 'timestamp' or 'count'.
	 * @param string  $path <null>      Limit search to match a specific path (default is null = any file path)
	 *
	 * @return void
	 */
	public function gridAction($rows, $page, $sidx, $path) {
		$timeStart = microtime(true);
		$offset = $rows * ($page - 1);
		$limit  = $rows;

		switch (true) {
			case $sidx == 'timestamp':
				$lines = ApiPublicModelLine::getMostRecent($offset, $limit, $path);
				break;
			case $sidx == 'count':
				$lines = ApiPublicModelLine::getMostTriggered($offset, $limit, $path);
				break;
			default:
				throw new RuntimeException("You cannot order by $sidx.");
		}

		$total = ApiPublicModelLine::getTotal($path);

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
				),
			);
		}
		$count = count($rows);

		$this->assign('page', $page);
		$this->assign('total', $count > 0 ? ceil($total / $count) : 0);
		$this->assign('records', $total);
		$this->assign('rows', $rows);
		$this->assign('time', microtime(true) - $timeStart);
	}

	/**
	 * Get the details for a given log line, by key.
	 *
	 * @param string $key <>
	 *
	 * @return void
	 */
	public function detailsAction($key) {
		try {
			$line = new ApiPublicModelLine($key);

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
				)
			);
		} catch (Exception $e) {
			$this->assign('error', get_class($e) . ': ' . $e->getMessage());
		}
	}
}
