<?php
/**
 * This file is part of the Vigu PHP error aggregation system.
 * 
 * PHP version 5
 * 
 * @category  Application
 * @package   Vigu 
 * @author    Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 * @author    Johannes Skov Frandsen <localgod@heaven.dk>
 * @copyright 2012 Jens Riisom Schultz, Johannes Skov Frandsen
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link      https://github.com/Ibmurai/vigu
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
