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
 * ApiConfiguration
 *
 * @category ErrorAggregation
 * @package  Vigu
 * @author   Bo Thinggaard
 */
class ApiConfiguration extends FroodModuleConfiguration
{
    /**
     * Does this module use namespaces?
     *
     * @return boolean
     * @see FroodModuleConfiguration::useNamespaces()
     */
    public function useNamespaces()
    {
        return false;
    }
}
