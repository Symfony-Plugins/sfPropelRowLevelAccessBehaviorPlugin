<?php

/*
 * This file is part of the Naenius Row Level Access plugin.
 * (c) 2009-2009 Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Alters the criteria needed to restrict a query to only show results 
 * matching the output of a method in the User object.
 * 
 * @package sfPropelRowLevelAccessBehaviorPlugin
 * @subpackage Rules
 * @author Mike van Riel <mike.vanriel@naenius.com>
 */
class sfRlaRuleFieldEqualsUserMethod extends sfRlaRule
{
	/**
	 * Contains the constant declaration which table field to check against.
	 *
	 * @example CustomerPeer::ID
	 * 
	 * @var string
	 */
	private $field_constant = "";
	
	/**
	 * Contains an sfCallable to the correct method in the User object
	 *
	 * @var sfCallable
	 */
	private $user_method = null;
	
	/**
	 * Contains a list of joins to execute. 
	 * 
	 * Every join is represented by an array containing 2 elements:
	 * - The constant declaration of the field to join from (e.g. ContactPeer::CUSTOMER_ID)
	 * - The constant declaration of the field to join to (e.g. CustomerPeer::ID)
	 * The default propel join type is used.
	 * 
	 * @example array(array('ContactPeer::CUSTOMER_ID', 'CustomerPeer::ID'))
	 * 
	 * @var array
	 */
	private $joins = array();
	
	/**
	 * Executes the method from the User object and compares the
	 * result to the given field.
	 * 
	 * If an array is returned by the User object this method will combine the
	 * value by ORring them.
	 * 
	 * @param Criteria $c              Criteria object to adapt
	 * 
	 * @see sfRlaRule#generate
	 * 
	 * @return Criteria
	 */
	public function generate(Criteria $c)
	{
		// get the result of the user method
		$result = $this->getUserMethod()->call();

		// on NULL we do not restrict
		if (is_null($result)) return $c;
		
		// attempt to convert the result to string if it is an object
		if (is_object($result)) $result = (string)$result;
		
		// if result is anything else than a scalar or array we cancel
		if (!is_scalar($result) && !is_array($result))
		{
			throw new InvalidArgumentException('Expected the result from the User Method to be an object (with toString), scalar or array');
		}
		
		// add any defined joins
		foreach ($this->getJoins() as $key => $join)
		{
			if (!isset($join[0]) || is_null(constant($join[0])))
			{
				throw new InvalidArgumentException('First argument of join "'.$key.'" is invalid, expected an existing model constant');
			}
			
			if (!isset($join[1]) || is_null(constant($join[1])))
			{
				throw new InvalidArgumentException('Second argument of join "'.$key.'" is invalid, expected an existing model constant');
			}
			
			$c->addJoin(constant($join[0]), constant($join[1]));
		}
		
		// if user method result is not an array, transform it. Makes processing more uniform
		if (!is_array($result)) $result = array($result);
		
		// support returned arrays by chaining them as an OR statement
		$criterion = new Criterion($c, constant($this->getField()), array_shift($result));
		foreach ($result as $value)
		{
			$criterion->addOr(new Criterion($c, constant($this->getField()), $value));
		}
		
		// enhance criteria
		$c->addOr($criterion);
		
		return $c;
	}
	
	/**
	 * Returns the field constant declaration
	 * 
	 * @return string
	 */
	public function getField()
	{
		return $this->field_constant;
	}
	
	/**
	 * Sets the field constant declaration
	 * 
	 * @param string $field_constant
	 * 
	 * @return void
	 */
	public function setField($field_constant)
	{
		$this->field_constant = $field_constant;
	}
	
	/**
	 * Returns the method which is to be called
	 * 
	 * @return sfCallable
	 */
	public function getUserMethod()
	{
		if (!($this->user_method instanceof sfCallable))
		{
			throw new Exception("User method must be set before it can be used");
		}
		
		return $this->user_method;
	}

	/**
	 * Creates a pointer (sfCallable) to the given method
	 * 
	 * @param string $user_method
	 * 
	 * @throws InvalidArgumentException if the method does not exist
	 * 
	 * @return void
	 */
	public function setUserMethod($user_method)
	{
		$user = sfContext::getInstance()->getUser();
		if (!method_exists($user, $user_method))
		{
			throw new InvalidArgumentException('The method "'.$user_method.'" in the User object does not exist');
		}
		
		$this->user_method = new sfCallable(array($user, $user_method));
	}

	/**
	 * Returns the joins needed for this rule to function properly
	 * 
	 * @return array
	 */
	public function getJoins()
	{
		return $this->joins;
	}
	
	/**
	 * Sets the joins needed to match Field Constant declaration
	 * 
	 * @param array $joins
	 * 
	 * @return void
	 */
	public function setJoins($joins)
	{
		if (!is_array($joins))
		{
			throw new InvalidArgumentException('Expected the Joins to be an array');
		}
		
		$this->joins = $joins;
	}
	
}