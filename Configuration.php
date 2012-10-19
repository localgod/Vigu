<?php
/**
 * This file is part of the Vigu PHP error aggregation system.
 * @link https://github.com/localgod/Vigu
 *
 * @copyright 2012 Copyright Jens Riisom Schultz, Johannes Skov Frandsen
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
/**
 * Custom configuration to allow routing everything to the Site module.
 *
 * @author Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
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
