<?php
if (!defined('ROOT')) return header("HTTP/1.1 404 Not Found");
/**
 * 
 * @package 全局配置模块
 * @author Cube
 * @since 2012-11-04
 *
 */
class MfConfig extends MfVar 
{
  public function __construct()
  {
    $app_conf_dir = A_ROOT . DIRECTORY_SEPARATOR . 'config';
    if (!is_dir($app_conf_dir)) return;
    $filename_arr = scandir($app_conf_dir);
    foreach ($filename_arr as $filename) {
      if (!preg_match_all("#^config_(.+)\.php$#", basename($filename), $matches)) continue;
      $this->set($matches[1][0], include $app_conf_dir . DIRECTORY_SEPARATOR . $filename);
    }
  }  
}