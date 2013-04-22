<?php
/**
 * Log
 * 
 * @author HouRui
 * @since 2012-08
 * 
 */
class MfLog
{
  const OFF       = 0x00;
  const INFO      = 0x01;
  const WARNING   = 0x02;
  const NOTICE    = 0x04;
  const ERROR     = 0x08;
  const EXCEPTION = 0x10;
  const ALL       = 0xFF;
  
  /**
   * @var log level
   * MfLog::LOG_OFF 
   * MfLog::LOG_INFO
   * MfLog::LOG_WARNING 
   * MfLog::LOG_NOTICE 
   * MfLog::LOG_ERROR 
   * MfLog::LOG_EXCEPTION 
   * MfLog::LOG_ALL
   * @example record error and notice
   * 	'log_level' => MfLog::LOG_ERROR | MfLog::LOG_NOTICE
   * @example record all level
   * 	'log_level' => MfLog::LOG_ALL
   * @example turn off log
   * 	'log_level' => MfLog::LOG_OFF
   */
  private $log_level = self::LOG_ALL;
  
  /**
   * log basename
   * @var string
   */
  private $basename = '';
  
  /**
   * log filename
   * @var string
   */
  private $filename = '';
  
  /**
   * log dirname
   * @var string
   */
  private $dirname = '';
  
  /**
   * date fotmat
   * @var string
   */
  private $date_format = 'Y-m-d H:i:s';
  
  /**
   * log suffix
   * @var string
   */
  private $suffix = 'log';
  
  /**
   * split day by file
   * @var bool
   */
  private $split_per_day = false;
  
  /**
   * split month by folder
   * @var bool
   */
  private $split_per_month = false;
  
  /**
   * tracecode
   * @var bool
   */
  private $backtrace = true;
  
  /**
   * echo log instead of write log file 
   * ini_get('display_errors') will overwrite it
   * @var bool
   */
  private $stdout = false;
  
  /**
   * __construct
   * @param array() $log_options
   */
  public function __construct($options)
  {
    foreach($this AS $key => $value) {
      isset($options[$key]) && $this->$key = $options[$key];
    }
    empty($this->dirname)        && trigger_error("Log dirname is empty", E_USER_ERROR);
    empty($this->basename)       && trigger_error("Log basename is empty", E_USER_ERROR);
    !is_dir($this->dirname)      && trigger_error("Dir not exists: {$this->dirname}", E_USER_ERROR);     
    !is_writable($this->dirname) && trigger_error("Permission Deny: {$this->dirname}", E_USER_ERROR);
    $this->dirname = rtrim($this->dirname, DIRECTORY_SEPARATOR);
    ini_set('error_log', $this->initFile());
  }
  
 /**
   * magic function
   * @param string $method
   * @param array $params
   */
  public function __call($method, $params)
  {
    $this->initFile();
    if (!preg_match_all("/^(info|warning|notice|error|exception|pid)$/i", $method, $matches)) {
      /* method not found */
      trigger_error("Function: " . __CLASS__ . "->$method() doesn't exists", E_USER_ERROR);
    }
    if ($this->log_level <= self::LOG_OFF) return;
    $reflector = new ReflectionClass(__CLASS__);
    $level     = strtoupper($matches[1][0]);
    if (!($reflector->getConstant($level) & $this->log_level)) return;
    $message   = trim($params[0]);
    $backtrace = isset($params[1]) ? $params[1] : debug_backtrace();
    $this->write($level, $message, $this->backtrace ? $backtrace : array());
  }
  
  /**
   * create the folder or file if not exists
   * @return $log_filename
   */
  private function initFile()
  {
    clearstatcache();
    $dirname = $this->dirname . DIRECTORY_SEPARATOR . 
               ($this->split_per_month ? date("Y-m") : '');
    !is_dir($dirname) && mkdir($dirname, 0755, true);
    $this->filename = $dirname . DIRECTORY_SEPARATOR . $this->basename . 
           ($this->split_per_day ? date("_Y-m-d") : '') . '.' . $this->suffix;
    ini_set('error_log', $this->filename);
  }
  
  /**
   * write log
   */
  private function write($level, $message, $backtrace)
  {
    static $func;
    if (!isset($func)) $func = create_function('$v', 'return is_string($v) ? "\"$v\"" : $v;');
    !empty($backtrace) && krsort($backtrace);
    /* backtrance */
    $error_reporting = ini_get('error_reporting');
    ini_set('error_reporting', 0);
    $trace_level = 0;
    foreach ($backtrace as $trace) {
      if (in_array($trace['class'], array(__CLASS__))) { continue; }
      $trace['args'] = implode(", ", array_map($func, $trace['args']));
      $message .= sprintf(PHP_EOL . "FunctionTrace[#%d]: %s%s%s(%s) in %s on line %s",
                         $trace_level++,     $trace['class'], $trace['type'], 
                         $trace['function'], $trace['args'],  $trace['file'], 
                         $trace['line']);
    }
    ini_set('error_reporting', $error_reporting);
    /* !backtrance */
    $message = sprintf("%-8s %s", "[$level]", $message);
    if ($this->stdout || ini_get('display_errors') == 'on') {
      echo sprintf("%s %s" . PHP_EOL, date($this->date_format), $message);
    } 
    if ($this->stdout) return;
    // @todo php version > 5.2.10 the timezone of error_log is always UTC  
    $callback = version_compare(PHP_VERSION, '5.2.10' ,'>') ? array($this, '_write')
                                                            : 'error_log';
    call_user_func($callback, $message);
  }
  
  /**
   * use fopen to write
   * @param string $message
   */
  private function _write($message)
  {
    $fp = fopen($this->filename, "a+");
    stream_set_write_buffer($fp, 0);
    fwrite($fp, sprintf(PHP_EOL . "%s %s", date($this->date_format), $message)); 
    fclose($fp); unset($fp);
  }
  
  /**
   * get handle
   * @param string $log_filename
   */
  public static function getHandle($basename)
  {
    $default_options = array(
      'log_level'        => self::LOG_ALL, 
      'basename'         => $basename,
      'dirname'          => dirname(__FILE__),
      'date_format'      => 'Y-m-d H:i:s',
      'suffix'           => 'log',
      'split_per_day'    => false,
      'split_per_month'  => false,
      'backtrace'        => false, 
      'stdout'           => false,
    );
    if (class_exists('Globals') && isset(Globals::$config) && 
        Globals::$config instanceof VarStorage) {
      $default_options = Globals::$config->getSection('log')->safeGetMulti($default_options);
    }
    return new self($default_options);
  }
}