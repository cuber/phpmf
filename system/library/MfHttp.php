<?php
class MfHttp 
{
  public static function redirect($dest_url, $http_code = 301)
  {
    $http_code = intval($http_code);
    if (!in_array($http_code, array(301, 302))) {
      trigger_error("Redirect code:$http_code error");
    }
    header("Location: $dest_url", null, $http_code); exit;
  }  
  
  
  /**
   * curl handle
   * @staticvar object
   */
  private static $ch;
  
  /**
   * user agent
   * @staticvar string
   */
  private static $user_agent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)';
  
  /**
   * get request
   * @param string $url
   * @param string $timeout
   */
  public static function get($url, $timeout)
  {
    self::init($url, $timeout);
    return self::request();
  }
  
  /**
   * private init
   * @param string $url
   * @param int    $timeout
   */
  private static function init($url, $timeout)
  {
    self::$ch = curl_init();
    // set url
    curl_setopt(self::$ch, CURLOPT_URL, $url);
    // instead of outputting it out directly
    curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
    // automatically set the Referer
    curl_setopt(self::$ch, CURLOPT_AUTOREFERER, true);
    // TRUE to follow any "Location: " header that the server sends
    curl_setopt(self::$ch, CURLOPT_FOLLOWLOCATION, true);
    // maximum amount of HTTP redirections to follow
    curl_setopt(self::$ch, CURLOPT_MAXREDIRS, 5);
    // The number of seconds to wait whilst trying to connect. Use 0 to wait indefinitely
    curl_setopt(self::$ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    // set the maximum seconds to download image
    curl_setopt(self::$ch, CURLOPT_TIMEOUT, $timeout);
    // Set User-agent
    curl_setopt(self::$ch, CURLOPT_USERAGENT, self::$user_agent);
    // TRUE to include the header in the output
    curl_setopt(self::$ch, CURLOPT_HEADER, false);
    // if HTTPS
    if (stripos($url, "https://") === FALSE) return;
    curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, false);
  }
  
  /**
   * make request
   * @return object HttpResponse
   */
  private static function request()
  {
    $http_status = curl_getinfo(self::$ch, CURLINFO_HTTP_CODE);
    return new HttpResponse(curl_exec(self::$ch), 
                            curl_getinfo(self::$ch, CURLINFO_HTTP_CODE), 
                            curl_error(self::$ch));  
  }
}

/**
 * 
 * Http Response class
 * @author HouRui
 * @since 2012-08
 *
 */
class HttpResponse
{
  /**
   * response data
   * @var string
   */
  public $data;
  
  /**
   * response http code
   * @var int
   */
  public $code;
  
  /**
   * response error message
   * @var string
   */
  public $errorMsg;
  
  /**
   * __construct
   * @param string $data
   * @param int    $httpCode
   * @param string $errorMsg
   */
  public function __construct($data, $code, $errorMsg)
  {
    $this->data     = $data;
    $this->code     = $code;
    $this->errorMsg = $errorMsg;
  }
}