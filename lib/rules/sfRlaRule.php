<?php

/*
 * This file is part of the Naenius Row Level Access plugin.
 * (c) 2009-2009 Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base class for altering the criteria needed to restrict a query to only show the intended results.
 * 
 * @package sfPropelRowLevelAccessBehaviorPlugin
 * @subpackage Rules
 * @author Mike van Riel <mike.vanriel@naenius.com>
 */
class sfRlaRule
{
	/**
	 * Alters the criteria object to activate this rule
	 * 
	 * @param Criteria $c Criteria object to adapt
	 * 
	 * @return Criteria
	 */
	public function generate(Criteria $criteria)
	{
		return $criteria;
	}

}