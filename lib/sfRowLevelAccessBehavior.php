<?php

/*
 * This file is part of the Naenius Row Level Access plugin.
 * (c) 2009-2009 Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Behaviour for inserting custom criterias / restrictions into models.
 * 
 * @package sfPropelRowLevelAccessBehaviorPlugin
 * @subpackage Behavior
 * @author Mike van Riel <mike.vanriel@naenius.com>
 */
class sfRowLevelAccessBehavior
{
	/**
	 * List of all defined rules
	 * 
	 * @var array
	 */
	private $rules = null;
	
	/**
	 * Class name of the default Rule type, can be overridden in the app.yml
	 * 
	 * @var string
	 */
	const DEFAULT_TYPE = 'sfRlaRuleFieldEqualsUserMethod';

	/**
	 * Loads the Yaml file (/config/rla.yml) for this functionality
	 * 
	 * @return array;
	 */
	public static function loadYaml()
	{
		$file = sfConfig::get('sf_config_dir').'/rla.yml';
		if (!is_readable($file))
		{
			throw new Exception("Unable to load Row Level Access configuration, expected to be able to read rla.yml");
		}
	
		// load the rules array and replace every entry with an 
		// instantiated rule 
		return sfYaml::load($file);
	}
	
	/**
	 * Loads the YAML file and converts the rules to objects of type nsRlaRule
	 * 
	 * @return array Array with sfRlaRule objects
	 */
	public function getRules()
	{
		// rules are already loaded, skip loading
		if (is_null($this->rules))
		{
			$this->rules = self::loadYaml();
			
			// convert rule arrays to objects
			foreach($this->rules as $class_name => &$rule_definition)
			{
				$rule_definition = $this->loadRule($class_name, $rule_definition);
			}			
		}
		
		// return rules
		return $this->rules;
	}
	
	/**
	 * Loads the class specified in the 'type' options and feeds it the 
	 * other options in setters.
	 * 
	 * @param string $model_class_name
	 * @param array  $options
	 * 
	 * @throws Exception
	 * 
	 * @return sfRlaRule
	 */
	public function loadRule($model_class_name, $options)
	{
		// get class from options or, if not present, load from config 
		// (and if that is not present, use the DEFAULT_TYPE)
		$type = (isset($options['type'])) ? 
			$options['type'] : 
			sfConfig::get('app_row_level_access_default_type', self::DEFAULT_TYPE);
		
		if (!class_exists($type))
		{
			throw new Exception('Rule type "' . $type . '" does not exist');
		}

		// initialize rule
		$rule = new $type();
		foreach($options as $key => $value)
		{
			// convert key from lowerscores to CamelCase
			$name = str_replace(' ', '', ucwords(str_replace('_', ' ', trim($key))));
			$setter = 'set'.$name;
			
			// set value
			$rule->$setter($value);
		}
		
		return $rule;
	}

	/**
	 * Returns the rule identified by the model's class name, or null 
	 * if no rule could be found.
	 * 
	 * @param string $model_class_name
	 * @return sfRlaRule
	 */
	public function getRule($model_class_name)
	{
		// if the class name starts with Base, we ignore that by removing it
		if (substr($model_class_name, 0, 4) === "Base") 
		{
			$model_class_name = substr($model_class_name, 4);
		}
		
		// if the class name ends with Peer, we ignore that by removing it
		if (substr($model_class_name, -4) === "Peer") 
		{
			$model_class_name = substr($model_class_name, 0, -4);	
		}
		
		// get the rule object from the rules array
		$rules = $this->getRules();
		
		// returns the sfRlaRule object or null when not found
		return isset($rules[$model_class_name]) ? $rules[$model_class_name] : null;
	}
	
	/**
	 * Add extra criterias based on the rules in the configuration yaml
	 * 
	 * @param string $class
	 * @param Criteria $criteria
	 * @param PropelPDO $con
	 * 
	 * @return void
	 */
	public function doSelectStmt($class, Criteria $criteria, $con = null)
	{
		$rule = $this->getRule($class);
		if (is_null($rule)) return;

		// execute the rule
		$rule->generate($criteria);
	}
		
}