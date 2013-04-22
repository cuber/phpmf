<?php
/**
 * 
 * 入口文件请包涵此文件
 * system init.ini file
 * 
 * @filesource: S_ROOT/core/init.php
 * 
 * @author Cube
 * @since 2012-11
 * 
 */
//------------------------------------------------------------------------------
//                         预定义常量
//------------------------------------------------------------------------------
define(  'ROOT', dirname(dirname(__DIR__)));
define('S_ROOT', ROOT . DIRECTORY_SEPARATOR . 'system');
define('A_ROOT', ROOT . DIRECTORY_SEPARATOR . 'application');

//------------------------------------------------------------------------------
//                         设定include path
//------------------------------------------------------------------------------
ini_set('include_path', 
  '.' . PATH_SEPARATOR . 
  S_ROOT . DIRECTORY_SEPARATOR . 'core'    . PATH_SEPARATOR .
  S_ROOT . DIRECTORY_SEPARATOR . 'module'  . PATH_SEPARATOR .
  S_ROOT . DIRECTORY_SEPARATOR . 'library' . PATH_SEPARATOR .
  A_ROOT . DIRECTORY_SEPARATOR . 'module'  . PATH_SEPARATOR .
  A_ROOT . DIRECTORY_SEPARATOR . 'library'
);

//------------------------------------------------------------------------------
//                         spl魔术加载函数
//------------------------------------------------------------------------------
include 'autoload.php';

//------------------------------------------------------------------------------
//                         初始化开启所有错误
//------------------------------------------------------------------------------
ini_set('log_errors',       true);
ini_set('display_errors',   'on');
ini_set('error_reporting', E_ALL);
ini_set('error_log',       A_ROOT . 'log' . DIRECTORY_SEPARATOR . 'init.log');

//------------------------------------------------------------------------------
//                         注册错误处理函数
//------------------------------------------------------------------------------
set_error_handler    (array('MfError', 'error_handler'));
set_exception_handler(array('MfError', 'exception_handler'));

