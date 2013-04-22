<?php
/**
 * MfVarInterface
 * @author Cube
 * 
 * if key has . please addslashes
 * 
 */
interface MfVarInterface
{
  public function __construct();
  public function load(/*$file1, $file2, $file3 ...$fileN*/);
  public function import(array $array);
  public function set($key_str, $value);
  public function setMulti(array $value_arr);
  public function get($key_str);
  public function getMulti(array $key_arr);
  public function magicGet($key_str); /* "*" is wildcard */
  public function getSection($key_section, $is_renturn_array = false);
  public function safeGet($key_str, $default_value);
  public function safeGetMulti(array $default_value_arr);
  public function listPush($key_str, $value);
  public function listPop($key_str, $value);
  public function delete($key_str);
  public function deleteMulti(array $key_arr);
  public function exists($key_str);
  public function dumpStorage();
}

/**
 * 
 * Mf Var
 * @author Cube
 * @since 2012-07
 *
 */
class MfVar implements MfVarInterface
{
  /**
   * global vars
   * @var array
   */
  protected $__storage = array();
  
  /**
   * __construct
   * @param array $init_array
   */
  public function __construct()
  {
    foreach (func_get_args() AS $array) {
      if (!is_array($array)) { 
        trigger_error("Only array can pass in " . __CLASS__, E_USER_ERROR);
      }
      $this->import($array);
    }
  }
  
  /**
   * muilti load var files
   */
  public function load(/*$file1, $file2, $file3 ...$fileN*/)
  {
    foreach (func_get_args() AS $file) {
      $this->import(include $file);
    }
  }
  
  /**
   * import array vars
   * @param mix $value_arr
   */
  public function import(array $array)
  {
    $this->setMulti($this->_arrayToValueArr($array));
  }
  
  /**
   * array to value array
   * @param  array $array
   * @return array $value_arr
   */
  protected function _arrayToValueArr(array $array)
  {
    foreach ($array AS $key => $value) {
      if (strpos($key, ".") === false) { continue; }
      unset($array[$key]);
      $array[addcslashes($key, '.')] = $value;
    }
    while ($this->_importHasArray($array)) {
      foreach ($array AS $key => $value) {
        if (!is_array($value)) { continue; }
        unset($array[$key]);
        foreach ($value AS $sub_key => $sub_value) {
          $array[$key . "." . addcslashes($sub_key, '.')] = $sub_value;
        }
      }
    }
    return $array;
  }
  
  /**
   * sub function of import
   * @param array $array
   */
  protected function _importHasArray(array $array)
  {
    foreach ($array AS $value) {
      if (is_array($value) && !empty($value)) { return true; }
    }
    return false;
  }
  
  /**
   * set global var
   * @param string $key_str
   * @param mix    $value
   */
  public function set($key_str, $value)
  {
    eval('$reference_value = &'.$this->_getExpress($key_str).';');
    $reference_value = $value;
  }
  
  /**
   * set multi global var
   * @param array $value_arr
   */
  public function setMulti(array $value_arr)
  {
    foreach ($value_arr AS $key => $value) {
      $this->set($key, $value);
    }
  }
  
  /**
   * get global var
   * @param  string $key_str
   * @return mix    $value      
   */
  public function get($key_str)
  {
    if (!$this->exists($key_str)) { 
      trigger_error("Var key '$key_str' not exists in " . __CLASS__, E_USER_ERROR); 
    }
    return eval('return ' . $this->_getExpress($key_str) . ';');
  }
  
  /**
   * safe get
   * @param  string $key_str 
   * @param  mix    $default_value
   * @return mix    $value
   */
  public function safeGet($key_str, $default_value)
  {
    return $this->exists($key_str) ? $this->get($key_str) : $default_value;
  }
  
  /**
   * magic get
   * "*" is used as wildcard
   * @param $key_str
   */
  public function magicGet($key_str)
  {
    if (strpos($key_str, "*") === false) return $this->get($key_str);
    $value_arr     = $this->_arrayToValueArr($this->storage);
    $key_str_regex = str_replace("*", '[^\.]*', $key_str);
    $key_arr_flip  = array();
    foreach($value_arr AS $key_str => $value) {
      if (!preg_match_all("#^($key_str_regex)#", $key_str, $matches)) continue;
      $key_arr_flip[] = $matches[1][0];
    }
    return $this->getMulti(array_unique($key_arr_flip));
  }
  
  /**
   * get section 
   * @param  $key_section
   * @param  $is_renturn_array [true]
   * @return VarStorage 
   */
  public function getSection($key_section, $is_renturn_array = false)
  {
    if ($is_renturn_array) return $this->get($key_section);
    return new self($this->get($key_section));
  }
  
  /**
   * get multi global var
   * @param  string $key_str
   * @return array  $value      
   */
  public function getMulti(array $key_arr)
  {
    $value_arr = array();
    foreach ($key_arr AS $key) {
      $value_arr[$key] = $this->get($key);
    }
    return $value_arr;
  }
  
  /**
   * safe get multi
   * @param  array $default_value_arr
   * @return array $value
   */
  public function safeGetMulti(array $default_value_arr)
  {
    $value_arr = array();
    foreach ($default_value_arr AS $key => $value) {
      $value_arr[$key] = $this->safeGet($key, $value);
    }
    return $value_arr;
  }
  
  /**
   * list push 
   * if none array convert to empty array
   * @param string $key_str
   * @param mix    $value
   */
  public function listPush($key_str, $value)
  {
    $list = $this->safeGet($key_str, array());
    !is_array($list) && $list = array();
    $list[] = $value;
    $this->set($key_str, $list);
  }
  
  /**
   * list pop 
   * if none array convert to empty array
   * @param string $key_str
   * @param mix    $value
   */
  public function listPop($key_str, $value)
  {
    $list = $this->safeGet($key_str, array());
    if (!is_array($list)) $list = array();
    $index = array_search($value, $list);
    if ($index !== false) unset($list[$index]);
    sort($list);
    $this->set($key_str, $list);
  }
  
  /**
   * unset key
   * @param string $key_str
   */
  public function delete($key_str)
  {
    eval("unset({$this->_getExpress($key_str)});");
  }
  
  /**
   * unset multi keys
   * @param string $key_str
   */
  public function deleteMulti(array $key_arr)
  {
    foreach ($key_arr AS $key_str) {
      $this->delete($key_str);
    }
  }
  
  /**
   * check if the global var exists
   * @param  string $key_str
   * @return bool
   */
  public function exists($key_str)
  {
    return eval("return isset({$this->_getExpress($key_str)});");
  }
  
  /**
   * dump storage
   * @return $this->storage
   */
  public function dumpStorage()
  {
    return $this->__storage;
  }
  
  /**
   * get the value express
   * @param  string $key_str
   * @return string $value_express
   */
  protected function _getExpress($key_str)
  {
    static $slash_char = '__^__';
    return '$this->__storage' . implode("", 
      array_map(
        function ($k) use($slash_char) { 
          return  "['" . addcslashes($k, "'") . "']";
        }, 
        array_map(
          function ($k) use($slash_char) { 
            return str_replace($slash_char, '.', $k);
          }, 
          explode(".", str_replace('\.', $slash_char, $key_str))
        )
      )
    );
  }
}