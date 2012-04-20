<?php
/**
 * TODO_DOCUMENT_ME
 *
 * PHP version 5
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
class ViguConfiguration extends FroodConfiguration {
	/**
	 * This function provides the base routes, i.e. relates uri prefixes to modules.
	 *
	 * @return array[] The keys are uri prefixes and the values are arrays of module names.
	 */
	public function getBaseRoutes() {
		return array_merge(array('' => array('site')), parent::getBaseRoutes());
	}
}
