<?php

//------------------------------------------------------------------------------
//   确保 Mf::$config & Mf::$global 在此模块之前初始化
//     MfUrl::R_NEXT 匹配下一条规则
//     MfUrl::R_LAST 匹配完该条规则, 结束匹配
//     MfUrl::R_REDIRECT  301跳转
//     MfUrl::R_PERMANENT 302跳转
//------------------------------------------------------------------------------
class MfUrl implements ArrayAccess
{
//------------------------------------------------------------------------------
//    Url 路由 Flag
//------------------------------------------------------------------------------  
  const R_NEXT      = 0x1;
  const R_LAST      = 0x2;
  const R_REDIRECT  = 0x4;
  const R_PERMANENT = 0x8;
  
//------------------------------------------------------------------------------
//    生成原始Url, query合并方式Flag
//------------------------------------------------------------------------------  
  const Q_MERGE = 1;
  const Q_ALLOW = 2;
  const Q_NEW   = 3;
  
//------------------------------------------------------------------------------
//    私有变量
//------------------------------------------------------------------------------  
  private $requset;
  private $route_rule;
  private $request_url;
  private $max_route_count;
  
//------------------------------------------------------------------------------
//    公共函数
//------------------------------------------------------------------------------   
  /**
   * __construct
   */
  public function __construct()
  {
    $this->init();
    $this->go();
  }
  
  /**
   * basic make url
   * @param string $url
   * @param array $query
   * @return null
   */
  public function makeBasic($url, array $query = array())
  {
    $url = rtrim($url, '/?') . '/';
    if (!empty($query)) $url .= '?';
    return $url . http_build_query($query);
  }
  
  /**
   * make url
   * @param array  $query
   * @param int    $flag
   */
  public function makeOrigin($query = array(), $flag = self::Q_MERGE)
  {
    $url = $this->requset['path'];
    $origin_query = Mf::$input->dumpStorage();
    switch ($flag) {
      case self::Q_NEW:
        // use the input query
      break;
      case self::Q_MERGE:
        $query = array_merge($origin_query, $query);
      break;
      case self::Q_ALLOW:
        foreach ($query AS $key) {
          if (!isset($origin_query[$key])) continue;
          $query[$key] = $origin_query[$key];
        }
      break;
      default: trigger_error('Wrong Query Flag', E_USER_ERROR); break;
    }
    return $this->makeBasic(
             $url, 
             array_filter($query, function($v) { return !is_null($v); })
           );
  }

//------------------------------------------------------------------------------
//    私有函数
//------------------------------------------------------------------------------  

  /**
   * init 初始化私有变量
   * @global $_SERVER['REQUEST_URI']
   */
  private function init()
  {
    $this->request_url     = $_SERVER['REQUEST_URI'];
    $this->route_rule      = Mf::$config->safeGet('route', array());
    $this->max_route_count = Mf::$config->safeGet('system.max_route_count', 10);
  }
  
  /**
   * 执行 route parse action
   */
  private function go()
  {
    $this->route();
    $this->parse();
  }
  
  /**
   * url 路由
   */
  private function route()
  {
    for ($route_count = 0; 1; $route_count++) {
      if ($route_count > $this->max_route_count) { 
        trigger_error("Route beyond max count", E_USER_ERROR);
      }
      if (empty($this->route_rule)) return; 
      foreach ($this->route_rule AS $rule) {
        $replace_count = 0;
        list($pattern, $replacement, $flag) = $rule;
        $this->request_url = preg_replace($pattern, $replacement, 
                                          $this->request_url, -1, $replace_count);
        if ($replace_count <= 0) {
          if ($rule == end($this->route_rule)) return;
          else continue; 
        }
        switch ($flag) {
          case self::R_LAST: break;
          case self::R_NEXT: 
            if ($rule == end($this->route_rule)) continue 2;
            else continue;
          case self::R_REDIRECT:
            MfHttp::redirect($this->request_url, 301);
          break;
          case self::R_PERMANENT:
            MfHttp::redirect($this->request_url, 302);
          break;
          default: trigger_error("Route flag:$flag error", E_USER_ERROR); break;
        }
      }
    }
  }
  
  /**
   * 解析 Requset Url
   * @global $_SERVER['REQUEST_METHOD']
   */
  private function parse()
  {
    $this->request_url = preg_replace("#^/index.php(.*)$#", '$1', $this->request_url);
    $requset_default_key = array('scheme', 'host', 'port', 'user', 'pass', 
                                 'path', 'query', 'fragment');
    $this->requset = array_merge(
      array_combine($requset_default_key, 
                    array_fill(0, count($requset_default_key), "")), 
      parse_url($this->request_url));
    $_GET = array(); // init $_GET
    parse_str($this->requset['query'], $_GET);
    $url_pieces = array_filter(explode('/', ltrim($this->requset['path'], '/')));
    if (empty($url_pieces)) {
      $this->requset['controller'] = Mf::$config->safeGet('system.default_controller', 'Index');
    } else {
      $this->requset['controller'] = ucfirst(strtolower(array_shift($url_pieces)));
    }
    if (empty($url_pieces)) {
      $this->requset['action'] = Mf::$config->safeGet('system.index_action', 'index');
    } else {
      $this->requset['action'] = strtolower(array_shift($url_pieces));
    }
    $this->requset['param'] = $url_pieces;
  }
  
//------------------------------------------------------------------------------
//         ArrayAccess 接口
//------------------------------------------------------------------------------
  /**
   * @see ArrayAccess::offsetSet()
   */
  public function offsetSet($offset, $value) 
  {
    trigger_error("MfUrl cannot be modified", E_USER_ERROR);
  }
  
  /**
   * @see ArrayAccess::offsetExists()
   */
  public function offsetExists($offset)
  {
    return isset($this->requset[$offset]);
  }
  
  /**
   * @see ArrayAccess::offsetGet()
   */
  public function offsetGet($offset) 
  {
    if (!isset($this->requset[$offset])) {
      trigger_error("$offset not exists in MfUrl", E_USER_ERROR);
    }
    return $this->requset[$offset];
  }
  
  /**
   * @see ArrayAccess::offsetUnset()
   */
  public function offsetUnset($offset)
  {
    trigger_error("MfUrl cannot be modified", E_USER_ERROR);
  }
}