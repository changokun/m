<?php
//dev version no changes please
// is this change okay?
class m {
  function __construct() {
    die('what are you doing?');
  }

  static function dump($dumpee, $label = 'no label provided', $relevant_backtrace_depth = 0) {
      static $vDump_display_count = 0;
      $vDump_display_count ++;
      if(strlen($label)) $label = array($label); else $label = array();

	  $meta_info = self::get_caller('m::Dump’d', $relevant_backtrace_depth);

      $data_type = gettype($dumpee);

      switch($data_type) {
          case 'string':
              $label[] = '<span title="' . number_format(strlen($dumpee)) . ' character' . (strlen($dumpee) != 1 ? 's' : '') . '" >' . $data_type . '</span>';
          break;
          case 'boolean':
              array_unshift($label, $dumpee ? '<span class="boolean_true_value">true</span>' : '<span class="boolean_false_value">false</span>');
          break;
          case 'object':
            if($label[0] == 'no label provided') {
              if(get_class($dumpee) == 'bass_account') $label[0] = $dumpee->name;
              elseif(get_class($dumpee) == 'bass_user') $label[0] = $dumpee->name . ' (current user)';
            }
              $label[] = $data_type;
              $label[] = get_class($dumpee);
          break;
          default:
              $label[] = $data_type;
          break;
      }

      // for the simple types, output the value in the label area.
      $done = false; // will cause the function to go into the subfunction
      switch($data_type) {
          case 'string':
              $trim_length = 100;
              if(strlen($dumpee) < $trim_length) $done = true;
              array_unshift($label, substr($dumpee, 0, $trim_length) . (strlen($dumpee) > $trim_length ? '&nbsp;&hellip;' : ''));
          break;
          case 'double':
              $done = true;
              array_unshift($label, $dumpee);
          break;
          case 'integer':
              $done = true;
              array_unshift($label, number_format($dumpee, 0, '.', ','));
          break;
      }


      ?>
          <div class="vDump">
              <? if($label) : ?>
                  <div style="font-size:16px; font-weight: bold; color:white; background-color:#333; padding:5px 5px 8px 5px; " class = "vDump_label">
                      <?=implode(' | ', $label)?>
                  </div>
              <? endif; ?>
              <? if( ! $done) : ?>
                  <div class="collapseybull"><?=self::_dump($dumpee, -1, $label)?></div>
              <? endif; ?>
              <div class="vDump_meta_info_main" style="font-size:9px; text-transform:uppercase; color: white; background-color: #333; padding:5px 5px 5px 5px;"><?=$meta_info?></div>
          </div>
      <? if($vDump_display_count == 1): // only for the first one.?>
          <script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js'></script>
          <script>
              if(typeof $ == 'function') {
                  $(function() {
                      $("div.vDump_label").add("div.vDump_meta_info_main").css('cursor', 'pointer').click(function() {
                          $(this).parent().find('div.collapseybull').first().toggle(500);
                      })
                      $("div.collapseybull").hide(500);
                      $(".vDump_depth_twistee_control").each(function(){
                          parent = $(this).closest("[class^='depth_']");
                          if(parent.length) {
                              parent_depth = parseInt(parent.attr('class').substr(6));
                              depth = parent_depth +1;
                              zone = parent.find(".depth_" + depth);
                              if(zone.length) {
                                  if(zone.css('display') != 'none') {
                                      // is shown. make the control a -
                                      $(this).html('-');
                                  } else {
                                      /// hidden, make the control a +
                                      $(this).html('+');
                                  }
                                  $(this).click(function(){
                                      parent = $(this).closest("[class^='depth_']");
                                      if(parent.length && typeof parent.attr('class') != 'undefined') {
                                          parent_depth = parseInt(parent.attr('class').substr(6));
                                          depth = parent_depth +1;
                                          zone = parent.find(".depth_" + depth);
                                          if(zone.css('display') != 'none') {
                                              // is shown. make the control a -
                                              $(this).html('+');
                                          } else {
                                              /// hidden, make the control a +
                                              $(this).html('-');
                                          }
                                          zone.toggle(500);
                                      }
                                  });
                              } else {
                                  $(this).remove();
                              }
                          }
                      });
                      $(".vDump_twistee_control").each(function(){
                          zone = $(this).parent().find(".vDump_twistee_zone");
                          if(zone.length) {
                              if(zone.css('display') != 'none') {
                                  // is shown. make the control a -
                                  $(this).html('-');
                              } else {
                                  /// hidden, make the control a +
                                  $(this).html('+');
                              }

                              $(this).click(function(){
                                  zone = $(this).parent().find(".vDump_twistee_zone");
                                  if(zone.css('display') != 'none') {
                                      // is shown. make the control a -
                                      $(this).html('+');
                                  } else {
                                      /// hidden, make the control a +
                                      $(this).html('-');
                                  }
                                  zone.toggle(500);
                              });
                          } else {
                              $(this).remove();
                          }
                      });
                  });
              }
          </script>
          <style>
              div.vDump { border: 2px solid olive; font-family: Arial; font-size: 13px; margin: 10px 0; }
              div.vDump span div { padding:3px; margin:3px;}
              div.vDump div div { margin-left:7px; }
              div.vDump div div.depth_0 { margin-left:3px; }
              div.depth_0 {background-color:#EEE;}
              div.depth_1 {background-color:#DDD;}
              div.depth_2 {background-color:#CCC;}
              div.depth_3 {background-color:#BBB;}
              .key { color: #444; }
              .vDump_meta_info { color:#333 }
              .vDump_meta_info_main { font-size:9px; text-transform:uppercase; color: white; background-color: #333; padding:5px 5px 5px 5px; }
              .string_value { color:olive }
              .integer_value { color:magenta }
              .float_value { color:purple }
              .boolean_true_value { color:green }
              .boolean_false_value { color:crimson }
              .null_value { color:blue; text-transform:uppercase}
              .vDump_depth_twistee_control, .vDump_twistee_control {margin:0 5px; cursor:pointer;}
              .note { color:#666; font-size:12px;}
          </style>
      <? endif;

  }

  static private function _dump($dumpee, $depth, $parentKey = '') {
      static $separator = ' =&gt; ';
      $depth ++;
      $data_type = gettype($dumpee);
      switch($data_type) {
          case 'NULL':
              ?><span class="null_value">null</span><?
          break;
          case 'boolean':
              echo $dumpee ? '<span class="boolean_true_value">true</span>' : '<span class="boolean_false_value">false</span>' ;
          break;
          case 'double':
              ?><span class="float_value"><?=$dumpee?> <span class="vDump_meta_info">(float/double)</span></span><?
          break;
          case 'integer':
              ?><span class="integer_value"><?=number_format($dumpee, 0, '.', ',')?> <span class="vDump_meta_info">(integer)</span></span><?
          break;
          case 'string':
              if(strlen($dumpee)):?>
                  <span class='string_value' title="string <?=number_format(strlen($dumpee))?> characters"><?=$dumpee?></span>
              <? else: ?>
                  <span class="vDump_meta_info">(zero-length string)</span>
              <? endif;
          break;
          case 'array':
              $sorted = false;
              if(array_values($dumpee) !== $dumpee) { $sorted = true; ksort($dumpee); }// it's associative, so sort it. -- may cause errors
              ?><span>array with <?=count($dumpee)?> item<?=count($dumpee) != 1 ? 's' : ''?><span class="vDump_depth_twistee_control"></span><?=$sorted ? ' <span class="note">(This associative array has been sorted.)</note>' : '';?><br>
                  <? foreach($dumpee as $key => $value): ?>
                      <div class='depth_<?=$depth?>'><span class="key"><?=$key?></span><?=$separator?><?= self::_dump($value, $depth, $key) ?></div>
                  <? endforeach; ?>
              </span><?
          break;
          case 'object':
              $keys = array();
              $missive = false;
              foreach($dumpee as $key => $value) {
                  if($key == 'missive') {
                      $missive = true;
                  } else {
                      $keys[] = $key;
                  }
              }
              asort($keys);
              if($missive) array_push($keys, 'missive');
              ?><span><?if($depth):?>object of class <?=get_class($dumpee)?><span class="vDump_depth_twistee_control"></span><br><?endif;?>
                  <? foreach($keys as $key): ?>
                      <div class='depth_<?=$depth?>'><span class="key"><?=$key?></span><?=$separator?><?=self::_dump($dumpee->$key, $depth, $key) ?></div>
                  <? endforeach; ?>
                  <? if(get_class($dumpee) != 'stdClass') :
                      $methods = get_class_methods(get_class($dumpee)); ?>
                      <div style="background-color:wheat; color:#333; font-weight:bold; font-size:16px; padding:5px;" class="depth_<?=$depth?>"><?=count($methods) ? number_format(count($methods)) . ' method' . (count($methods) != 1 ? 's' : '') : 'no methods'?><span class='vDump_twistee_control'></span>
                          <? if(count($methods)) echo '<ul style="margin:0; padding:0; display:none;" class="vDump_twistee_zone">'; foreach($methods as $method_name): ?>
                              <li style="list-style-type:none; padding-left:10px; font-weight:normal; font-size:13px;" title="<?=get_class($dumpee)?>::<?=$method_name?>"><?=$method_name?></li>
                          <? endforeach; echo '</ul>'; ?>
                      </div>
                  <? endif; ?>
              </span><?
          break;
          case 'xxx':
              ?>
              <xmp style="font-size:12px; font-family:Arial; padding:5px;"><?var_dump($dumpee);?></xmp>
          <? break;
          default: ?>
              <h1>What do i do with a <?=$data_type?></h1>
          <? break;
      }

      // a little post-data....
      switch($data_type) {
          case 'integer':
          case 'string':
              if(is_numeric($dumpee) and (isset($parentKey) and is_string($parentKey) and (substr($parentKey, -5, 5) == '_date' or substr($parentKey, -4, 4) == 'Date' or substr($parentKey, -9, 9) == 'TimeStamp' or substr($parentKey, -10, 10) == '_timestamp')) or (strlen($dumpee) == 10)) echo date(' (l, F jS, Y \a\t h:i:s A)', (int) $dumpee);
          break;
      }
  }

  static function death() {
	  foreach(func_get_args() as $arg) m::dump($arg, '<span style="color:crimson">death rattle</span>', 2);
	  die('<hr>' . self::get_caller('Died', 1));
  }

  static function get_caller($return = '', $relevant_backtrace_depth = 0) {
  	  // $return is the verb you want... as in 'dumped'
      $debug_info = debug_backtrace();

      // is there a class?
      $class = isset($debug_info[$relevant_backtrace_depth + 1]['class']) ? $debug_info[$relevant_backtrace_depth + 1]['class'] . '::' : '';

      $return .= ' on line ' . $debug_info[$relevant_backtrace_depth]['line'] . ' of ' . $debug_info[$relevant_backtrace_depth]['file'];
      if(isset($debug_info[$relevant_backtrace_depth+1]['function'])) $return .= ' in ' . $class . $debug_info[$relevant_backtrace_depth+1]['function'] . '()';
      // clean up slashes, remove core dir info
      $return = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('/', '\\', $return));
      // clean up more core info that doc root doesn't cover
      $return = str_replace('C:\www', '', str_replace('/', '\\', $return));
      $return = str_replace('/usr/www', '', str_replace('/', '\\', $return));

      return $return;
  }

  static function aMail() {
    global $user;
    $headers  = "From: aMailError_{$_SERVER['SERVER_NAME']}@placewise.com\r\n";
    $headers .= "Content-type: text/html\r\n";
    $debugInfo = debug_backtrace();
    $subject = (isset($user->uid) and $user->uid) ? '' : 'anon ';
    $subject .= "error aMail() from line {$debugInfo[0]['line']} of " . str_replace(str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']), '', $debugInfo[0]['file']);
    $body = "<h3>aMail from line {$debugInfo[0]['line']} of " . str_replace(str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']), '', $debugInfo[0]['file']) . "</h3>";
    $body .= "<div style='border:1px solid olive; padding:5px; margin:5px;'>page requested: http://{$_SERVER['SERVER_NAME']}";
    if(isset($_SERVER['REDIRECT_URL'])) $body .= $_SERVER['REDIRECT_URL']; // this should probably be some other ting.
    if(isset($_SERVER['HTTP_REFERER']) and strlen($_SERVER['HTTP_REFERER'])) {
      $body .= "<br>from {$_SERVER['HTTP_REFERER']}";
    } else {
      $body .= "<br> no referer [sic].";
    }
    $body .= "</div>";
    $args = func_get_args();
    foreach($args as $arg) { // first, put strings at top
      if(is_scalar($arg)) {
        $body .= "<div style='border:1px solid olive; padding:5px; margin:5px;'>$arg</div>";
      }
    }
    foreach($args as $arg) { // then the more complex things.
      if( ! is_scalar($arg)) {
        ob_start();
        var_dump($arg);
        $body .= "<pre style='font-size:12px; font-family:Arial'>" . ob_get_clean() . "</pre>";
      }
    }
    ob_start();
    echo "<h1>\$user</h1>";
    var_dump($user);
    echo "<h1>\$debugInfo</h1>";
    var_dump($debugInfo);
    /*echo "<h1>\$_SERVER</h1>";
    var_dump($_SERVER);*/
    echo "<h1>\$_REQUEST</h1>";
    var_dump($_REQUEST);
    $body .= "<pre style='font-size:12px; font-family:Arial'>" . ob_get_clean() . "</pre>";
    if(stripos($_SERVER['HTTP_USER_AGENT'], 'bot') !== false) $subject .= " [bot]";
    if(bass_config::get('mf_machineName') == 'tlaloc' or (isset($user->uid) and $user->uid == 9)) {
      echo '<div style="overflow:hidden; height:200px; border:6px solid wheat; padding:5px;">' . $body . '</div>';
    } else {
      @mail('alex.brown@placewise.com', $subject, $body, $headers); /// sorry, only to me. if you want to use this for yourself, it's an easy mod. go ahead
    }
  }

  static function is_this_still_in_use($msg) {
      //m::is_this_still_in_use(); // 2012 08 02 ab

    $headers = "Content-type: text/html; charset=utf-8\r\n";
    $subject = "STILL IN USE: $msg";
    if(is_bot()) $subject .= ' [bot]';
    ob_start(); global $user; var_dump($user, $_REQUEST, $_SERVER); $body = ob_get_clean();
    if(bass_config::get('mf_machineName') == 'tlaloc') {
      var_dump($body, $subject);
      var_dump(debug_backtrace());
      die("<hr><h1>still in use</h1>Died on line " . __LINE__ . " of " . __FILE__);
    } else {
      mail("alex.brown@placewise.com", $subject, $body, $headers);
    }
  }


}

