<?php


class MfTpl extends MfGlobal implements ArrayAccess 
{
  
  const S_HTTP   = 1;
  const S_LIB    = 2;
  const S_CUSTOM = 3;
  
  /**
   * lib dir
   * @var dirname array()
   */
  private $__dirname = array();
  
  /**
   * tpl file
   * @var array
   */
  private $__filenames = array();
  
  /**
   * sources
   * @var array
   */
  private $__sources = array(
    self::S_HTTP   => array(),
    self::S_LIB    => array(),
    self::S_CUSTOM => array(),
  );
  
  /**
   * init dir
   * @param string $template
   * @param string $type
   * @return string $dirname
   */
  private function initDir($type, &$template = null)
  {
    if (is_null($template)) {
      $template = Mf::$config->safeGet('system.template', 'default');
    }
    // parse
    $this->__dirname = array(
      'web'   => "/$template-theme/$type/",
      'local' => A_ROOT . DIRECTORY_SEPARATOR . 'template'
                        . DIRECTORY_SEPARATOR . $template
                        . DIRECTORY_SEPARATOR . $type
                        . DIRECTORY_SEPARATOR
    );
  }
  
  /**
   * load source in specific order
   * @param string $template
   */
  private function loadSource($source, $template)
  {
    if (is_null($template)) {
      $this->__sources[self::S_HTTP][] = $source;
    } else if ($template == 'lib') {
      $this->__sources[self::S_LIB][] = $source;
    } else {
      $this->__sources[self::S_CUSTOM][] = $source;
    }
    return $this;
  }
  
  /**
   * load boot strap
   * @param string libname
   * @return $this
   */
  public function loadLib($libname)
  {
    switch (strtolower(trim($libname))) {
      case 'bootstrap':
        $this->loadScript('bootstrap.min.js', 'lib')
             ->loadLink('bootstrap.min.css', array(), 'lib');
           //->loadLink('bootstrap-responsive.min.css', array(), 'lib');
      break;
      case 'jquery.fancybox':
        $this->loadScript('jquery-1.8.2.min.js', 'lib')
             ->loadScript('jquery.fancybox.min.js', 'lib')
             ->loadLink('jquery.fancybox.css', array(), 'lib');
        break;
      case 'jquery.form':
        $this->loadScript('jquery-1.8.2.min.js', 'lib')
             ->loadScript('jquery.form.js', 'lib');
        break;
      case 'jquery':
        $this->loadScript('jquery-1.8.2.min.js', 'lib');
      break;
      default: break;
    }
    return $this;
  }
  
  /**
   * load script
   * @param string $src
   * @param string $template
   */
  public function loadScript($src, $template = null)
  { 
    // is remote
    if (strtolower(substr($src, 0, 4)) != 'http') {
      $this->initDir('js', $template);
      if (!is_file($this->__dirname['local'] . $src)) return $this;
      $src = $this->__dirname['web'] . $src;
    }
    $source = <<<HTML
<script src="$src" type="text/javascript"></script>\n
HTML;
    return $this->loadSource($source, $template);
  }
  
  /**
   * load link
   * @param string $basename
   * @param string $type
   * @param string $template
   * @return $this
   */
  public function loadLink($href, array $attrs = array(), $template = null)
  { 
    // is reomote
    if (strtolower(substr($href, 0, 4)) != 'http') {
      $path_info = pathinfo($href);
      if (empty($path_info['extension'])) return $this;
      switch (strtolower($path_info['extension'])) {
        case 'css':
          $this->initDir('css', $template);
          $attrs = array(
            'rel'  => 'stylesheet',
            'type' => 'text/css',
          );
        break;
        case 'ico':
          $this->initDir('img', $template);
          $attrs = array(
            'rel'  => 'shortcut icon',
            'type' => 'image/x-icon',
          );
        break;
        default: return;
      }
      $basename = $path_info['basename'];
      if (!is_file($this->__dirname['local'] . $basename)) return $this;
      $href = $this->__dirname['web'] . $basename;
    }
    // parse attr
    $attr_str = ''; ksort($attrs);
    foreach ($attrs as $k => $v) {
      $attr_str .= sprintf('%s="%s" ', $k, $v);
    }
    $source = <<<HTML
<link href="$href" $attr_str/>\n
HTML;
    return $this->loadSource($source, $template);
  }
  
  /**
   * echo source
   */
  public function viewSource()
  {
    foreach (array(self::S_HTTP, self::S_LIB, self::S_CUSTOM) as $type)
    foreach (array_unique($this->__sources[$type]) as $idx => $source) {
      echo $source; unset($this->__sources[$type][$idx]);
    }
    
  }
  
  /**
   * 加载模板
   * @param string $basename 模板名
   * @return $this
   */
  public function loadTpl($basename)
  {
    if (substr($basename, -4) != '.php') $basename .= '.php';
    $this->__filenames[] = $basename;
    return $this;
  }
  
  /**
   * 显示模板
   */
  public function view()
  {
    define('__TPL__', sprintf('/%s-theme',
                              Mf::$config->safeGet('system.template', 'default')));
    $__dirname = A_ROOT . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR
                        . Mf::$config->safeGet('system.template', 'default');
    $_ = $this->dumpStorage(); // $_ 能够在模板中使用
    foreach ($this->__filenames as $filename) {
      include $__dirname . DIRECTORY_SEPARATOR . $filename;
    }
  }
  
//------------------------------------------------------------------------------
//                         魔术函数接口 类成员式访问
//------------------------------------------------------------------------------
  /**
   * magic set 
   */
  public function __set($name, $value)
  {
    return $this->set($name, $value);
  }
  
  /**
   * magic get
   */
  public function __get($name)
  {
    return $this->get($name);
  }
  
  /**
   * magic isset
   */
  public function __isset($name)
  {
    return $this->exists($name);
  }
  
  /**
   * magic unset
   */
  public function __unset($name)
  {
    $this->delete($name);
  }  
}