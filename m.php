<?php
// set the static vars below to your personal values.
// will need short_open_tag = On in your php.ini

class m {
	static public $developer_email;
	static public $object_email;
	static public $m_email_domain;
	static private $instance;
	static private $is_live = true; // play it safe.
	static private $javascript_has_been_output = false;
	static protected $functions_that_can_get_hot_output = array('aMail'); // be careful adding to this.
	static protected $dump_these_global_vars_for_still_in_use = array('_REQUEST', '_SERVER');
	static protected $dump_these_global_vars_for_aMail = array('_REQUEST', '_SERVER');
	static $sensitive_folders = array(); // will attempt to scrub these from output
	static public $jQuery_src_url = 'http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js';

	static public $classes_to_skip = array(); // outputing these seems troublesome.

	private function __construct() {
		if(file_exists(dirname(__FILE__) . '/m.ini')) {
			foreach(parse_ini_file('m.ini', true) as $key => $value) {
				self::$$key = $value;
			}
		}
	}

	/** use this to get it to put the javascript on page again, if nec. i'm using this after closing the connection in other output. */
	public static function reset_javascript_output_check() {
		self::$javascript_has_been_output = false;
	}

	// here's the pattern:
	public static function screw() { // function with nice name that you use in your code.
		if( ! isset(self::$instance)) self::init(); // get the instance
		self::$instance->do_screw($pass_your_args_here); // depending on what is instantiated, this will run either m->do_screw() or m_live->do_screw()
		// now go find both function definitions.
	}

	protected function do_screw() {
		// this is the dev version of the function. do what ever you want.
	}

	private static function init() {
		// if this is a dev server or is in dev mode, load a fully functioning tool otherwise a silent logger/monitor.
		// so... load some config first.
		if(isset($_SERVER['site_is_dev'])) self::$is_live = ! (bool) $_SERVER['site_is_dev'];

		if(self::$is_live) {
			// instantiate the hot stuff
			self::$instance = new m_live();
		} else {
			// instantiate the cold version of the stuff (which is this class, unchanged.)
			self::$instance = new m();
		}
	}

	/**
	* dump stuff. click the black bar to expand when the data is complex. you can set options that include founder verb, relevant backtrace depth, etc.
	*
	* this function prepares your label and the backtrace info, then calls sub functions.
	*
	* @param mixed $dumpee the thing you want dumpd
	* @param string $label the most visible part of the dump
	* @param array $options see code for all options
	*/
	public static function dump($dumpee, $label = 'no label provided', $options = array()) {
		if( ! isset(self::$instance)) self::init();
		if( ! isset($options['founder'])) {
			if( ! isset($options['founder_verb'])) $options['founder_verb'] = 'm::dump&rsquo;d on '; // keep the html entity. many times dumps occur on headless pages
			if( ! isset($options['relevant_backtrace_depth'])) $options['relevant_backtrace_depth'] = 0;
			$options['founder'] = $options['founder_verb'] . self::get_caller_fragment($options['relevant_backtrace_depth']);
		}
		// collapse? expand?
		$options['collapse'] = isset($options['collapse']) ? $options['collapse'] : true;

		self::$instance->do_dump($dumpee, $label, $options);
	}

	protected static function get_stack_frame($relevant_backtrace_depth = 0) {
		$debug_info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		array_shift($debug_info); // lose the function that called me.

		// if $relevant_backtrace_depth is a string, it is actually a function name. go through the output and look for it.
		if(is_string($relevant_backtrace_depth) and strlen($relevant_backtrace_depth)) {
			$new_depth = 0;
			foreach($debug_info as $depth => $frame) {
				if(isset($frame['function']) and $frame['function'] == $relevant_backtrace_depth) {
					$new_depth = $depth;
					break;
				}
			}
			$relevant_backtrace_depth = $new_depth;
		}

		return $debug_info[$relevant_backtrace_depth];

	}

	/**
	* this one creates the html wrapper and label
	*
	* @param mixed $dumpee
	* @param string $label
	* @param array $options
	*/
	protected function do_dump($dumpee, $label = 'no label provided', $options = array()) {

			if(is_scalar($label)) $label = array($label); else $label = array();

			$data_type = gettype($dumpee);

			// let us figure out the label
			switch($data_type) {
				case 'string':
					$label[] = '<span title="' . number_format(strlen($dumpee)) . ' character' . (strlen($dumpee) != 1 ? 's' : '') . '" >' . $data_type . '</span>';
				break;
				case 'boolean':
					array_unshift($label, $dumpee ? '<span class="boolean_true_value">true</span>' : '<span class="boolean_false_value">false</span>');
				break;
				case 'object':
					if($label[0] == 'no label provided') {
						// these are some special classes.
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
				case 'boolean':
					$done = true;
					array_unshift($label, $dumpee ? 'true' : 'false');
				break;
				case 'double':
					$done = true;
					array_unshift($label, $dumpee);
				break;
				case 'integer':
					$done = true;
					//array_unshift($label, number_format($dumpee, 0, '.', ','));
					array_unshift($label, $dumpee);
				break;
			}
			// there are some extra blank lines and indentation changes, please keep them, it makes the output more readable.
		?>


<div class="mDump">
	<? if($label) : ?><div class = "mDump_label"><?=implode(' | ', $label)?></div><? endif; ?>
	<? if( ! $done) : ?><div class="collapseybull<?=$options['collapse'] ? ' collapseybull_on_init' : ''?>"><?=self::_dump($dumpee, -1)?></div><? endif; ?>
	<div class="mDump_meta_info_main"><?=$options['founder']?></div>
</div>


<? echo self::get_asset_html();
	}

	/**
	* this is the one that handles the interior, and is recursed.
	*
	* @param mixed $dumpee
	* @param int $depth
	* @param mixed $parent_key - sort of the label? so we know what we are dealing with?
	*/
	static private function _dump($dumpee, $depth, $parent_key = '') {
		static $separator = ' =&gt; '; // again, maintain html entities.
		$depth ++;
		$data_type = gettype($dumpee);

		// do we even want to dump this thing?
		if($putative = self::omit_by_key($parent_key, is_scalar($dumpee)) or $putative = self::omit_by_value($dumpee)) {
			$putative = is_scalar($putative) ? $putative : $data_type . ' omitted from dump';
			echo '<span class="mDump_meta_info">(' . $putative . ')</span>';
			return;
		}

		switch($data_type) {
			case 'NULL':
				echo '<span class="null_value">null</span>';
			break;

			case 'boolean':
				echo $dumpee ? '<span class="boolean_true_value">true</span>' : '<span class="boolean_false_value">false</span>' ;
			break;

			case 'double':
				?><span class="float_value"><?=$dumpee?> <span class="mDump_meta_info">(float/double)</span></span><?
			break;

			case 'integer':
				?><span class="integer_value"><?=$dumpee // no number format, plz?> <span class="mDump_meta_info">(<?if(strlen($dumpee) > 4) echo strlen($dumpee) . '-digit ';?>integer)</span></span><?
			break;

			case 'string':
				if(strlen($dumpee)):?>
					<span class='string_value'><?=$dumpee?> <span class="mDump_meta_info">(<?if(strlen($dumpee) > 8) echo strlen($dumpee) . '-character ';?>string)</span></span>
				<? else: ?>
					<span class="mDump_meta_info"><span class="mDump_meta_info">(zero-length string)</span></span>
				<? endif;
			break;

			case 'array':
				$sorted = false;
				if( ! count($dumpee)) {
					echo '<span class="mDump_meta_info">(empty array)</span>';
				} else {
					//if(array_values($dumpee) !== $dumpee) $sorted = true; ksort($dumpee); // it's associative, so sort it. -- may cause errors
					?><span>array with <?=count($dumpee)?> item<?=count($dumpee) != 1 ? 's' : ''?><span class="mDump_depth_twistee_control"></span><?=$sorted ? ' <span class="note">(This associative array has been sorted.)</note>' : '';?><br>
					<? foreach($dumpee as $key => $value): ?>
					<div class='depth_<?=$depth?>'>
						<span class="key"><?=$key?></span>
						<?=$separator?>
						<?= self::_dump($value, $depth, $key) ?>
					</div>
					<? endforeach; ?>
					</span><?
				}
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
				?><span><?if($depth):?>object of class <?=get_class($dumpee)?><span class="mDump_depth_twistee_control"></span><br><?endif;?>
					<? foreach($keys as $key) : ?>
						<div class='depth_<?=$depth?>'><span class="key"><?=$key?></span><?=$separator?>
							<?=self::_dump($dumpee->$key, $depth, $key) ?>
						</div>
					<? endforeach; ?>
					<? if(get_class($dumpee) != 'stdClass') :
						$methods = get_class_methods(get_class($dumpee)); ?>
						<div style="background-color:wheat; color:#333; font-weight:bold; font-size:16px; padding:5px;" class="depth_<?=$depth?>"><?=count($methods) ? number_format(count($methods)) . ' method' . (count($methods) != 1 ? 's' : '') : 'no methods'?><span class='mDump_twistee_control'></span>
							<? if(count($methods)) echo '<ul style="margin:0; padding:0; display:none;" class="mDump_twistee_zone">';
								foreach($methods as $method_name): ?>
								<li style="list-style-type:none; padding-left:10px; font-weight:normal; font-size:13px;" title="<?=get_class($dumpee)?>::<?=$method_name?>"><?=$method_name?></li>
							<? endforeach; echo '</ul>'; ?>
						</div>
					<? endif; ?>
				</span><?
			break;

			case 'resource': ?>
				<span class="mDump_meta_info"><?=get_resource_type($dumpee)?> resources cannot be dumped. consider writing some special handling.</span>
			<? break;

			case 'xxx': ?>
				<xmp style="font-size:12px; font-family:Arial; padding:5px;"><?var_dump($dumpee);?></xmp>
			<? break;

			default: ?>
				<h3>What do i do with a <?=$data_type?></h3>
			<? break;
		}

		// a little post-data....
		switch($data_type) {
			case 'integer':
			case 'string':
				if(is_numeric($dumpee) and (isset($parent_key) and is_string($parent_key) and (substr($parent_key, -5, 5) == '_date' or substr($parent_key, -4, 4) == 'Date' or substr($parent_key, -9, 9) == 'TimeStamp' or substr($parent_key, -10, 10) == '_timestamp')) or (is_numeric($dumpee) and strlen($dumpee) == 10)) echo date(' (l, F jS, Y \a\t h:i:s A)', (int) $dumpee);
			break;
		}
	}

	static function omit_by_key($key = NULL, $data_is_scalar = true) {
		if(empty($key)) return false;
		if($key === 'GLOBALS') return $key . ' cannot be dumped; too much recursion.';
		if($data_is_scalar and ($key === 'PHP_AUTH_PW' or stripos($key, 'pass') !== false)) return 'passwords are omitted from dumps';
		return false;
	}

	static function omit_by_value($value = NULL) {
		if(empty($value)) return false;
		if(is_object($value)) {
			$class = get_class($value);
			if(in_array($class, self::$classes_to_skip)) return $class . ' objects can be troublesome and are skipped.';
		}
		return false;
	}

	static private function get_asset_html() {
		if(self::$javascript_has_been_output) return NULL; // only once, amigo
		self::$javascript_has_been_output = true;
		ob_start(); ?>
<script type="text/javascript" src="<?=self::$jQuery_src_url?>"></script>
<script type="text/javascript">
	if(typeof $ == 'function') {
		$(function() {
			$("div.mDump_label").add("div.mDump_meta_info_main").css('cursor', 'pointer').click(function() {
				$(this).parent().find('div.collapseybull').first().toggle(500);
			})
			$("div.collapseybull_on_init").hide(500);
			$(".mDump_depth_twistee_control").each(function(){
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
			$(".mDump_twistee_control").each(function(){
				zone = $(this).parent().find(".mDump_twistee_zone");
				if(zone.length) {
					if(zone.css('display') != 'none') {
						// is shown. make the control a -
						$(this).html('-');
					} else {
						/// hidden, make the control a +
						$(this).html('+');
					}

					$(this).click(function(){
						zone = $(this).parent().find(".mDump_twistee_zone");
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
	div.mDump { border: 2px solid olive; font-family: Arial; font-size: 13px; margin: 10px 0; }
	div.mDump span div { padding:3px; margin:3px;}
	div.mDump div div { margin-left:7px; }
	div.mDump div div.depth_0 { margin-left:3px; }
<?
	for ($x = 1; $x < 12; $x++) { // this is a big old wtf. dechex maybe doesn't like negative numbers?
		echo "	div.depth_$x { background-color: #" . str_repeat(strtoupper(dechex(15 - $x)), 3) . '; ';
		if($x > 7) echo 'color:white;';
		echo "}\n";
	}
?>
	.key { color: #444; }
	.mDump_label { font-size:16px; font-weight: bold; color:white; background-color:#333; padding:5px 5px 8px 5px; }
	.mDump_meta_info { color:#999 }
	.mDump_meta_info_main { font-size:11px; text-transform:uppercase; color: white; background-color: #333; padding:5px 5px 5px 5px; }
	.string_value { color:olive }
	.depth_3 .string_value { color:white }
	.integer_value { color:magenta }
	.float_value { color:purple }
	.boolean_true_value { color:green }
	.boolean_false_value { color:crimson }
	.null_value { color:blue; text-transform:uppercase}
	.mDump_depth_twistee_control, .mDump_twistee_control {margin:0 5px; cursor:pointer;}
	.note { color:#666; font-size:12px;}
</style>
		<? return ob_get_clean();
	}

	/**
	* produces ' on line 123 of file /xyz' without revealing doc root.
	*
	* @param int $relevant_backtrace_depth
	*/
	static function get_caller_fragment($relevant_backtrace_depth = 0) {
		$debug_info = debug_backtrace();

		// if $relevant_backtrace_depth is a string, it is actually a function name. go through the output and look for it.
		if(is_string($relevant_backtrace_depth) and strlen($relevant_backtrace_depth)) {
			$new_depth = 0;
			foreach($debug_info as $depth => $stack) {
				if(isset($stack['function']) and $stack['function'] == $relevant_backtrace_depth) {
					$new_depth = --$depth;
					break;
				}
			}

			$relevant_backtrace_depth = $new_depth;

		// is $relevant_backtrace_depth too deep?
		} elseif($relevant_backtrace_depth > (count($debug_info) -1)) $relevant_backtrace_depth = count($debug_info) -1;

		// is there a class?
		$class = isset($debug_info[$relevant_backtrace_depth + 1]['class']) ? $debug_info[++$relevant_backtrace_depth]['class'] . '::' : '';

		$return = 'on line ' . $debug_info[$relevant_backtrace_depth]['line'] . ' of ' . $debug_info[$relevant_backtrace_depth]['file'];
		// clean up slashes, remove core dir info
		$return = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('/', '\\', $return));
		// clean up more core info that doc root doesn't cover - because sometime your libs will use this and they aren't in doc root.
		foreach(self::$sensitive_folders as $folder) $return = str_replace(str_replace('/', '\\', $folder), '', str_replace('/', '\\', $return));

		if(isset($debug_info[$relevant_backtrace_depth+1]['function'])) $return .= '<span style="color:#888"> in ' . $class . $debug_info[$relevant_backtrace_depth+1]['function'] . '()</span>';

		return $return;
	}

	static function aMail() {
		$headers  = "From: aMail_{$_SERVER['SERVER_NAME']}@{self::$m_email_domain}\r\n";
		$headers .= "Content-type: text/html\r\n";
		$debugInfo = debug_backtrace();
		$subject = (isset($GLOBALS['user']) and $GLOBALS['user']->uid) ? '' : 'anon '; // todo - how to tell if anonymous
		$subject .= "aMail() from line {$debugInfo[0]['line']} of " . str_replace(str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']), '', $debugInfo[0]['file']); // todo formalize the path scrubbing
		$body = "<h3>aMail from line {$debugInfo[0]['line']} of " . str_replace(str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']), '', $debugInfo[0]['file']) . "</h3>";
		$body .= '<div style="border:1px solid olive; padding:5px; margin:5px;">page requested: http://' . $_SERVER['SERVER_NAME'];
		if(isset($_SERVER['REDIRECT_URL'])) $body .= $_SERVER['REDIRECT_URL']; // this should probably be some other ting.
		if(isset($_SERVER['HTTP_REFERER']) and strlen($_SERVER['HTTP_REFERER'])) {
			$body .= '<br>from ' . $_SERVER['HTTP_REFERER'];
		} else {
			$body .= '<br> no referer [sic].';
		}
		$body .= '</div>';
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

		// dump the configured global vars + debug info
		$body .= self::get_global_dumps(self::$dump_these_global_vars_for_aMail, array('debug info' => $debugInfo));

		if(stripos($_SERVER['HTTP_USER_AGENT'], 'bot') !== false) $subject .= " [bot]"; // todo update
		if(bass_config::get('mf_machineName') == 'tlaloc' or (isset($GLOBALS['user']->uid) and $GLOBALS['user']->uid == 9)) {
			echo '<div style="overflow:hidden; height:200px; border:6px solid wheat; padding:5px;">' . $body . '</div>';
		} else {
			@mail(self::$developer_email, $subject, $body, $headers);
		}
	}

	static function is_this_still_in_use($msg = '') {
		//m::is_this_still_in_use('old account password change code block'); // 2012 08 02 ab
		if( ! isset(self::$instance)) self::init();

		if(count(self::$dump_these_global_vars_for_still_in_use)) {
			$body = self::get_global_dumps(self::$dump_these_global_vars_for_still_in_use);
		} else {
			$body = '<p>No data to report (if you would like to see some data, consider setting dump_these_global_vars_for_still_in_use in your m.ini).</p>';
		}

		// get caller info and append to msg.
		$msg .= ' | complained ' . self::get_caller_fragment(1);

		// send an email on live, or just die on dev.
		if(self::$is_live) {
			$headers = "Content-type: text/html; charset=utf-8\r\n";
			$subject = "STILL IN USE: $msg";
			if(self::is_bot()) $subject .= ' [bot]';
			mail(self::$developer_email, $subject, $body, $headers);
		} else {
			die('<hr><h3>' . __FUNCTION__ . '() says: ' . $msg . '</h3>' . $body);
		}
	}

	protected static function get_global_dumps($var_names, $additional_data = array()) {
		// additional data is an assoc array (label, data) that is more things to be dumped in the same styles
		static $label_tag_name = 'h4';
		ob_start();
		foreach($var_names as $var_name) {
			echo "<$label_tag_name>\$$var_name</$label_tag_name>";
			switch($var_name) {
				case '_SERVER':
					echo '<xmp>'; var_dump($_SERVER); echo '</xmp>';
				break;
				case '_REQUEST':
					echo '<xmp>'; var_dump($_REQUEST); echo '</xmp>';
				break;
				default:
					// assumed to be a global
					if(isset($GLOBALS[$var_name])) {
						echo '<xmp>'; var_dump($GLOBALS[$var_name]); echo '</xmp>';
					} else {
						echo "<p>\$GLOBALS['" . $var_name . "'] not found.</p>";
					}
				break;
			}
		}

		foreach($additional_data as $label => $data) {
			echo "<$label_tag_name>\$$var_name!!!</$label_tag_name>";
			echo '<xmp>'; var_dump($data); echo '</xmp>';
		}

		$temp = ob_get_clean();
		return $temp;
	}

	protected function is_bot() {
		return false; // todo
	}



	// death section //////////////////////////////////////////////////////
	/**
	* death dumps all its arguments then stops execution.
	* if in live mode, it throws an exception, which should be properly handled by your code.
	*/
	public static function death() { // function with nice name that you use in your code.
		if( ! isset(self::$instance)) self::init(); // get the instance
		self::$instance->do_death(func_get_args()); // depending on what is instantiated, this will run either m->do_screw() or m_live->do_screw()
		// now go find both function definitions.
	}

	protected function do_death() {

		// get any decho output
		if($temp = m::get_HTML_output()) echo '<div style="border:2px solid tan">' . $temp . '</div>';

		// in the case of a death, the relevant backtrace depth is always 2
		$founder = 'Cause of death on ' . self::get_caller_fragment(1);
		$temp = func_get_args(); $args = array_pop($temp); // keep in mind how args are passed to this slave func
		if(count($args == 1)) $args[] = 'death rattle';
		if(isset($args[1]) and ! is_scalar($args[1])) $args[1] = 'second argument for death must be scalar.';
		if(count($args) > 1) {
			$this->dump($args[0], '<span style="color:crimson">' . $args[1] . '</span>', array('founder' => $founder, 'collapse' => false, 'relevant_backtrace_depth' => 3));
			die('<!-- ' . $founder . ' -->');
		} else {
			die('<hr />' . $founder);
		}
	}




	// help section ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* checks if help is turned on for the area, and is of at least $depth
	*
	* @param string, array $area defaults to general
	* @param int $depth def 1
	*/
	public static function help($area = 'general', $depth = 0) {
		if( ! isset(self::$instance)) self::init(); // get the instance
		return self::$instance->do_help($area, $depth);
	}

	/**
	* return boolean indicating whether or not help is enabled for the area, and at the $depth
	*
	*/
	protected function do_help($area, $depth = 0) { // defaults specified in pub func
		// this is the dev version of the function. do what ever you want.
		// has help been initted?
		// i//f( ! isset(self::$helps)) $this->init_help();
		/*if($value = persistence::get($area . 'Help', array('session', 'request'), false)) {
			return $value >= $depth;
		}*/
		return (bool) ((isset($_REQUEST[$area . 'Help']) and $_REQUEST[$area . 'Help'] > $depth) or (isset($_SESSION[$area . 'Help']) and $_SESSION[$area . 'Help'] > $depth));
	}

	public static function turn_on_help($area = 'general', $depth = 1) {

	}

	public static function turn_off_help($area = 'all') {

	}


	// decho section ///////////////////////////////////////////////////////////////

	// a decho is a dump that can have it's output collected and output on the side.

	public static function decho() { // function with nice name that you use in your code.
		if( ! isset(self::$instance)) self::init(); // get the instance
		// todo - make this an apply
		call_user_func_array(array(self::$instance, 'do_decho'), func_get_args()); // depending on what is instantiated, this will run either m->do_screw() or m_live->do_screw()
		// now go find both function definitions.
	}

	protected function do_decho() {
		$args = func_get_args();

		// pull out some behavior keywords
		foreach($args as $key => $arg) {
			if($arg == 'inline') {
				$inline = true;
				unset($args[$key]);
				break;
			}
			if($arg == 'on the side' or $arg == 'on_the_side') {
				$inline = false;
				unset($args[$key]);
				break;
			}
		}

		ob_start();

		if(count($args) == 1 and is_scalar($args[0])) {
			// output simple string
			echo $this->get_scalar_decho_HTML($args[0]);
		} elseif(count($args) == 2 and is_scalar($args[1])) {
			// same as a dump - dumpee and label have been provided.
			$this->dump($args[0], $args[1], array('relevant_backtrace_depth' => 3));
		} else {
			$all_scalar = true;
			foreach($args as $arg) if( ! is_scalar($arg)) $all_scalar = false;
			if($all_scalar) {
				foreach($args as $arg) echo $this->get_scalar_decho_HTML($arg);
			} else {
				foreach($args as $arg) $this->dump($arg, 'decho', array('relevant_backtrace_depth' => 3));
			}
		}

		$output = ob_get_clean();

		// now, the big question... inline? or on the side?
		// if it was not set by args. default to 'on the side' unless emergencyHelp is on.
		// rather than call the help method and risk a loop, i'll do a manual check for emergency Help
		if( ! isset($inline)) $inline = isset($_REQUEST['emergencyHelp']);
		if($inline) {
			echo $output;
		} else {
			$this->side_dish .= $output;
		}

		//m::dump($args, 'decho!!!', array('relevant_backtrace_depth' => 2));
		// if there is one arg, and it is scalar, let's do a simple output. if it is more complex, auto-dump.
		// this is the dev version of the function. do what ever you want.
	}

	protected function get_scalar_decho_HTML($str) {
		return '<div class="m_decho" style="background-color:wheat; color:#333; font-size:13px; padding:2px 3px; margin:2px; font-family:Arial" title="dechoed ' . strip_tags($this->get_caller_fragment(3)) . '">' . $str . '</div>';
	}



	// output section ////////////////////////////////////////////////
	public static function get_HTML_output() { // function with nice name that you use in your code.
		// this is a case where you don't want to instantiate if it doesn't already exist.
		if( ! isset(self::$instance)) return NULL;
		return self::$instance->do_get_HTML_output(); // depending on what is instantiated, this will run either m->do_get_HTML_output() or m_live->do_get_HTML_output()
	}

	protected function do_get_HTML_output() {
		if(isset(self::$instance->side_dish)) return self::$instance->side_dish;
		return NULL;
	}



}



class m_live extends m {

	protected function do_dump($dumpee, $label = 'no label provided', $relevant_backtrace_depth = 0) {
		// well, what if this is being used to create monitor output?
		// how do we tell it to do dev dump? could check the backtrace, look for autorized functions, like aMail or log_something().
		// that sounds safest.
		foreach(debug_backtrace() as $track) {
			if(in_array($track['function'], self::$functions_that_can_get_hot_output)) {
				return parent::do_dump($dumpee, $label . ' [hot function]', $relevant_backtrace_depth + 4);
			}
		}

		echo "<!-- live dump returns no output. -->";

	}

	protected function do_screw() {
		// this is the live version of the function. do non-obtrusive things, like sending alert emails or logging;
}

	// help section //////////////////////////////////////////////////////////////////////////////
	// does not matter, do nothing for helps on live. if you need some help output, get m to instantiate as dev.
	protected function do_help($ignore, $ignore) {
		// we do nothing live.
		return false;
	}

	// decho section //////////////////////////////////////////////////////////////////////////////
	// does not matter, do nothing for dechoes on live. if you need some decho output, get m to instantiate as dev.
	protected function do_decho() {
		// we do nothing live.
	}

	// death section //////////////////////////////////////////////////////
	/**
	* death dumps all its arguments then stops execution.
	* if in live mode, it throws an exception, which should be properly handled by your code.
	*/
	protected function do_death() {
		throw new Exception('Sorry, we have an issue handling your request.');
	}


}
