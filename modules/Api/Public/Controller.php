<?php
/**
 * TODO_DOCUMENT_ME
 *
 * PHP version 5.3+
 *
 * @category TODO_DOCUMENT_ME
 * @package  TODO_DOCUMENT_ME
 * @author   Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 * @since    2012-TODO-
 */
/**
 * TODO_DOCUMENT_ME
 *
 * @category   TODO_DOCUMENT_ME
 * @package    TODO_DOCUMENT_ME
 * @subpackage Class
 * @author     Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 */
class ApiPublicController extends FroodController {
	/**
	 * Initialization.
	 *
	 * @return void
	 */
	protected function initialize() {
		parent::initialize();

		$this->doOutputJson();
	}
}
