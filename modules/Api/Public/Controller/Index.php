<?php
/**
 * TODO_DOCUMENT_ME
 *
 * PHP version 5
 *
 * @category TODO_DOCUMENT_ME
 * @package  TODO_DOCUMENT_ME
 * @author   Jens Riisom Schultz <jers@fynskemedier.dk>
 * @since    2012-TODO-
 */

/**
 * TODO_DOCUMENT_ME
 *
 * @category   TODO_DOCUMENT_ME
 * @package    TODO_DOCUMENT_ME
 * @subpackage Class
 * @author     Jens Riisom Schultz <jers@fynskemedier.dk>
 */
class ApiPublicControllerIndex extends ApiPublicController {
	/**
	 * Register log lines.
	 *
	 * @param array $lines <>
	 *
	 * @return void
	 */
	public function indexAction($lines) {
		$this->doOutputDisabled();

		foreach ($lines as $line) {
			ApiPublicModelLine::create($line);
		}
	}
}
