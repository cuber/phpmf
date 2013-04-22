<?php
if (!defined('ROOT')) return header("HTTP/1.1 404 Not Found");
/**
 * magic autoload function
 * @param string $classname
 */
function __autoload($classname) 
{
  require_once $classname . '.php';
}