<?php
/**
 * Core Frame Class 
 * 
 * url.php
 * 
 * @author  & HouRui
 * @since 2011-11
 */
class MfController
{
  
  /**
   * __construct final function
   * could not be overload
   */
  final public function __construct()
  {
    $r = new ReflectionClass('Mf');
    foreach ($r->getStaticProperties() AS $module_name => $module) {
      $this->$module_name = $module;
    }
    $this->_preAction();
  }
  
  /**
   * 执行action函数之前的预执行函数
   */
  protected function _preAction() {}
}