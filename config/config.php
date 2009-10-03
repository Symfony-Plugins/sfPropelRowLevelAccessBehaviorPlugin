<?php
require_once(dirname(__FILE__) . '/../lib/sfRowLevelAccessBehavior.php');

// register the behavioral hooks 
sfPropelBehavior::registerHooks('rla', array(
  'Peer:doSelectStmt:doSelectStmt' => array('sfRowLevelAccessBehavior', 'doSelectStmt')
));

// add this behavior to the models present in the configuration yaml
$rules = sfRowLevelAccessBehavior::loadYaml();
foreach ($rules as $class => $rule)
{
	sfPropelBehavior::add($class, array('rla'));
}