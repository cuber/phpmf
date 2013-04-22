<?php


class MfCore extends MfModule 
{
  /**
   * p var
   * @param mixed $var 
   * @param mixed $var
   * ...
   * @return object IsHalt
   */
  public static function p()
  {
    if (func_num_args() <= 0) return;
    static $is_print_css = false;
    if ($is_print_css === false) {
      echo <<<EOF
<style>
body { margin:0; padding:0; }
.debug_backtrace { 
  color:#fff; background-color:#3c3c3c; padding-bottom:15px;
  font-family: Consolas, verdana, arial, helvetica, sans-serif; 
}
.debug_backtrace a { color:#94aefb; cursor:pointer; }
.debug_backtrace pre { font-family: Monaco, Consolas, verdana, arial, helvetica, sans-serif; }
.debug_backtrace hr { background-color:#3c3c3c; border:0; }
.debug_backtrace .func { font-weight:bold; color:#1ad77c; }
.debug_backtrace .trace_header { background-color:#515252; padding:5px; font-size:12px; }
.debug_backtrace .var { 
  color:#f9dd1d; margin:3px 10px 0 20px; word-break:break-all;
  border-left:2px solid #88a3f2; background-color:#515252;
  padding:5px; font-weight:500; font-size:14px; 
  word-wrap: break-word;
}
.debug_backtrace .trace { border-left:3px solid #39c4dd; padding-left:2px; }
.debug_backtrace .line_num { color: white; padding-left:3px; }
</style>
EOF;
      $is_print_css = true;
    }
    $backtrace     = debug_backtrace(true);
    // trace返回字段会不全, 手动填充消除warning
    $trace_keys    = array('file', 'class', 'type', 'function', 'line');
    $trace_default = array_combine($trace_keys, 
                                   array_fill(0, count($trace_keys), ""));
    // 函数堆栈
    echo "<div class='debug_backtrace'><div class='trace_header'>";
    $count = count($backtrace);
    for ($i = $count - 1; $i >= 0; $i--) {
      $trace = array_merge($trace_default, $backtrace[$i]);
      $margin = ($count - $i - 1) * 30;
      $file_basename = substr($trace['file'], strlen(ROOT));
      $title = htmlspecialchars($trace['file']);
      echo <<<HTML
<div class="trace" style="margin-left:{$margin}px;">
  <span class='func'>{$trace['class']}{$trace['type']}{$trace['function']}()</span>
  <a title="$title"><b>$file_basename</b>:<b class="line_num">{$trace['line']}</b></a>
</div>
HTML;
    }
    echo "</div>";
    foreach (func_get_args() AS $var) {
      // 输出变量信息
      echo "<pre class='var'>";
      var_dump($var);
      echo '</pre>';
    }
    echo '</div>';
    return new IsHalt();
  }
  
  /**
   * htmlspecialchars recursive and trim
   * @param  mixed $vars  
   * @param  bool  $is_trim
   * @return bool  $vars
   */
  public static function h($vars, $is_trim = true)
  {
    if (is_null($vars))   return null;
    if (!is_array($vars)) return htmlspecialchars($is_trim ? trim($vars) : $vars);
    foreach ($vars AS $key => $value) {
      $vars[$key] = self::h($value);
    }
    return $vars;
  }  
}

// is halt class for Mf::p()
class IsHalt
{
  public function __construct() { }
  public function halt() { exit; }
}