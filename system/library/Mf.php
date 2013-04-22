<?php
if (!defined('ROOT')) return header("HTTP/1.1 404 Not Found");
/**
 * 
 * Core library of My Framework
 * 'Mf' is alias of 'My Framework'
 * @author Cube
 * @since 2012-07
 *
 */
class Mf extends MfCore 
{ 
  /**
   * @package MfGlobal
   */
  public static $global;
  
  /**
   * @package MfConfig
   */
  public static $config;
  
  /**
   * @package MfUrl
   */
  public static $url;
  
  /**
   * @package MfInput
   */
  public static $input;
  
  /**
   * @package MfTpl
   */
  public static $tpl;
  
  
//------------------------------------------------------------------------------
//                         框架启动!
//------------------------------------------------------------------------------
  public static function go()
  {
    self::init();
    self::iniSet();
    self::action();
  }
  
//------------------------------------------------------------------------------
//                         !!变量初始化顺序请勿随意调整!!
//------------------------------------------------------------------------------
  private static function init()
  {
    self::$global = new MfGlobal();
    self::$config = new MfConfig();
    self::$url    = new MfUrl();
    self::$input  = new MfInput();
    self::$tpl    = new MfTpl();
  }

//------------------------------------------------------------------------------
//                         设定ini以及header编码
//------------------------------------------------------------------------------
  private static function iniSet()
  {
    foreach (self::$config->getSection('ini', true) AS $key => $value) {
      ini_set($key, $value);
    }
    header("Content-type: text/html; charset=" . 
           self::$config->safeGet('system.charset', 'UTF-8'));
  }
  
//------------------------------------------------------------------------------
//                          action
//------------------------------------------------------------------------------
  private static function action()
  {
    $classname = self::$url['controller'] . 'Controller';
    $filename  = A_ROOT . DIRECTORY_SEPARATOR . 'controller' . 
                 DIRECTORY_SEPARATOR . $classname . '.php';
    if (!is_file($filename)) { 
      $message = sprintf("Controller File: '%s' not exists", basename($filename));
      throw new MfHttpException(404, $message);
    }
    include $filename;
    $r = new ReflectionClass($classname);
    if (!method_exists($classname, self::$url['action']) || 
        !$r->getMethod(self::$url['action'])->isPublic()) {
      $message = sprintf("Controller: '%s' has no public method named '%s'",
                         $classname, self::$url['action']);
      throw new MfHttpException(404, $message);
    }
    self::preLoad();
    $controller = new $classname();
    call_user_func_array(array($controller, self::$url['action']), 
                               self::$url['param']);
  }

//------------------------------------------------------------------------------
//                         约定好预定包含以及加载的内容
//                         !!!   请勿调整顺序   !!!
//------------------------------------------------------------------------------
  private static function preLoad()
  {
    self::extensionCheck();
    self::defaultLoadModule();
    self::defaultInclude();
  }
  
//------------------------------------------------------------------------------
//                         默认的包含目录
//------------------------------------------------------------------------------
  private static function defaultInclude()
  {
    $filename_arr = array(
       A_ROOT . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR 
              . "function_global.php",
       A_ROOT . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR 
              . "function_" . self::$url['controller'] . ".php",
       A_ROOT . DIRECTORY_SEPARATOR . 'initialize.php',
    );
    foreach ($filename_arr as $filename) {
      if (is_file($filename)) include $filename;
    }
  }  

//------------------------------------------------------------------------------
//                         默认的加载的模块
//------------------------------------------------------------------------------
  private static function defaultLoadModule()
  {
    $app_module_dir = A_ROOT . DIRECTORY_SEPARATOR . 'module';
    foreach (scandir($app_module_dir) AS $filename) {
      //  || is_dir($filename)
      if ($filename == '.' || $filename == '.') continue;
      if (substr($filename, -4) != '.php') continue;
      if (($module_name = basename($filename, '.php')) == 'MfModule') continue;
      include $app_module_dir . DIRECTORY_SEPARATOR . $filename;
      $member_name = strtolower($module_name); // member always lower 
      $r = new ReflectionClass('Mf');          // reflection class
      if (!array_key_exists($member_name, $r->getStaticProperties())) continue;
      $class_name = $r->getStaticPropertyValue($member_name);
      if (empty($class_name)) $class_name = $module_name;
      $r->setStaticPropertyValue($member_name, new $class_name());
    }
  }  
  
//------------------------------------------------------------------------------
//                         扩展检测
//------------------------------------------------------------------------------
  public static function extensionCheck()
  {
    $extension_arr = self::$config->safeGet('system.extension', array());
    $unloaded_extension = array_diff($extension_arr, get_loaded_extensions());
    if(empty($unloaded_extension)) return;
    trigger_error(sprintf("Extension: '%s' unloaded",
                          implode(", ", $unloaded_extension)), E_USER_ERROR); 
  }
}
