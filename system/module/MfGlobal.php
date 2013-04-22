<?php
if (!defined('ROOT')) return header("HTTP/1.1 404 Not Found");
/**
 * 
 * @package 全局变量模块
 * @author Cube
 * @since 2012-11-04
 *
 */
class MfGlobal extends MfVar implements ArrayAccess 
{
  /**
   * @see ArrayAccess::offsetSet()
   */
  public function offsetSet($offset, $value) 
  {
    if (is_null($offset)) $this->__storage[] = $value;
    else $this->set($offset, $value);
  }
  
  /**
   * @see ArrayAccess::offsetExists()
   */
  public function offsetExists($offset)
  {
    return $this->exists($offset);
  }
  
  /**
   * @see ArrayAccess::offsetGet()
   */
  public function offsetGet($offset) 
  {
    return $this->get($offset);
  }
  
  /**
   * @see ArrayAccess::offsetUnset()
   */
  public function offsetUnset($offset)
  {
    $this->delete($offset);
  }
}