<?php

//------------------------------------------------------------------------------
//   确保 Mf::$url 在此模块之前初始化
//------------------------------------------------------------------------------

class MfInput extends MfVar
{
  /**
   * request method
   * @var string
   */
  private $__request_method = null;
  
  /**
   * __construct
   * @global $_SERVER['REQUEST_METHOD']
   */
  public function __construct()
  {
    $this->__request_method = strtoupper($_SERVER['REQUEST_METHOD']);
    if ($this->__request_method == 'GET') {
      $this->__storage = $_GET;
    } else {
      if (!is_array($_GET)) $_GET = array();
      $this->__storage = array_merge($_GET, $_POST);
    }
  }
  
  /**
   * get request method
   * @return enum('GET', 'POST')
   */
  public function getMethod()
  {
    return $this->__request_method;
  }
}