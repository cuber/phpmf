<?php
if(!defined('ROOT')) { header("HTTP/1.1 404 Not Found"); exit(E_ERROR); }
/**
 * 
 * Error Handler
 * @author Cube
 * @since 2012-07
 *
 */
class MfError
{
  
//------------------------------------------------------------------------------
//                          Error static functions
//------------------------------------------------------------------------------
  public static function error_handler($error_code, $error_msg, 
                                       $error_file, $error_line)
  {
    // error off
    if (!(ini_get('error_reporting') & $error_code)) return true;
    // handle error
    switch ($error_code) {
      case E_WARNING:
        $type = 'PHP warning';
        break;
      case E_NOTICE:
        $type = 'PHP notice';
        break;
      case E_USER_ERROR:
        $type = 'User error';
        break;
      case E_USER_WARNING:
        $type = 'User warning';
        break;
      case E_USER_NOTICE:
        $type = 'User notice';
        break;
      case E_RECOVERABLE_ERROR:
        $type = 'Recoverable error';
        break;
      default:
        $type = 'PHP error';
    }
    /*
    $backtrace = array();
    foreach (debug_backtrace(true) as $trace) {
      if (in_array($trace['class'], array(__CLASS__))) continue;
      //array_pop($trace['args']);
      //Mf::p($trace); continue;
      is_array($trace['args']) && $trace['args'] = implode(", ", array_map(function($v) {
                                       return is_string($v) ? '"' . @strval($v) .'"' : $v;}, 
                                       $trace['args']));
      $backtrace[] = sprintf(PHP_EOL . "FunctionTrace[#%d]: %s%s%s(%s) in %s on line %s",
                         count($backtrace),  $trace['class'], $trace['type'], 
                         $trace['function'], $trace['args'],  $trace['file'], 
                         $trace['line']);
    }
    */
    include S_ROOT . DIRECTORY_SEPARATOR . 'error' 
                   . DIRECTORY_SEPARATOR . 500 . '.php';
    if (!Mf::$config->safeGet('system.debug', false)) exit;
    Mf::p($error_code, $error_msg, $error_file, $error_line);
    exit;
    /*
    // 记录日志
    $message = sprintf('%s:  %s in %s on line %d.', $type, $errstr, 
    $errfile, $errline);
    error_log($message);
    $debug = DD::$config->safeGet('debug', false);
    if (!$debug && !in_array($errno, array(E_CORE_ERROR, E_ERROR, E_USER_ERROR))) {
      return true; // 不再处理
    }
    ob_clean();
    DDRequest::setHttpStatus(500);
    if ($debug) {
      $title = $type;
      $backtrace = debug_backtrace();
      array_shift($backtrace);
      require SYSROOT . '/errors/error.php';
    } else {
      require SYSROOT . '/errors/500.php';
    }
    exit();
    */
  }
  
  public static function exception_handler($exception)
  {
    if ($exception instanceof MfHttpException) {
      $http_status = $exception->getHttpStatus();
      $title = 'Http ' . $http_status . ' Exception';
    } else {
      $http_status = 500;
      $title = get_class($exception);
    }
    
    include S_ROOT . DIRECTORY_SEPARATOR . 'error' 
                   . DIRECTORY_SEPARATOR . $http_status . '.php';
    if (!Mf::$config->safeGet('system.debug', false)) exit;
    Mf::p($title, $exception->getFile(), $exception->getMessage(),
          $exception->getTraceAsString(), $http_status); exit;
    /*
    ob_clean();
    DDRequest::setHttpStatus($status_code);
    $message = sprintf(
    "Fatal error:  Uncaught exception '%s' with message '%s' in %s on line %s.", 
    get_class($exception), $exception->getMessage(), $exception->getFile(), 
    $exception->getLine());
    // 500错误写PHP日志
    if ($status_code == 500) {
      error_log($message . '\nStack trace:\n' . $exception->getTraceAsString());
    }
    // 显示错误页面
    if (!DD::$config->safeGet('debug', false) || $status_code != 500) {
      require SYSROOT . "/errors/$status_code.php";
    } else {
      $backtrace = $exception->getTrace();
      require SYSROOT . '/errors/error.php';
    }
    return true;
    */
  }
  
  private static function getFilename($filename) 
  {
    static $path_from = null;
    is_null($path_from) && $path_from = strlen(realpath(ROOT));
    return substr($filename, $path_from);
  }

//------------------------------------------------------------------------------
//                          Error class
//------------------------------------------------------------------------------
  
  public $http_code;
  public $message;
  public $error_info;
  public $backtrace;
  
  /**
   * __construct
   * @param int    $http_code
   * @param string $error_info
   * @param string $message
   * @param array  $backtrace
   */
  private function __construct($http_code, $message = null, $error_info = null, $backtrace = null)
  {
    $this->http_code  = $http_code;
    $this->message    = $message;
    $this->error_info = $error_info;
    $this->backtrace  = $backtrace;
    $this->show();
  }
  
  /**
   * show error page
   */
  private function show()
  {
    include S_ROOT . DIRECTORY_SEPARATOR . 'error' 
                   . DIRECTORY_SEPARATOR . $this->http_code . '.php';
  }
}

/**
 * 
 * All Exception
 * @author Cube
 * @since 2012-07
 *
 */
class MfHttpException extends Exception
{
  /**
   * http status
   * @var int
   */
  private $httpStatus;
  
  /**
   * __construct
   * @param int    $httpStatus
   * @param string $message
   */
  public function __construct($status, $message = '')
  {
    parent::__construct($message);
    $this->httpStatus = intval($status);
  }
  
  /**
   * @return int $httpStatus
   */
  public function getHttpStatus()
  {
    return $this->httpStatus;
  }
}