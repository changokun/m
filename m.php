<?php
// set the static vars below to your personal values.
// will need short_open_tag = On in your php.ini

class m {
	static public $developer_email = '';
	static public $object_email = '';
	static public $object_email_domain = '';
	static private $instance;
	static private $is_live = true; // play it safe.
	static protected $functions_that_can_get_hot_output = array('aMail'); // be careful adding to this.
	static protected $dump_these_vars_for_still_in_use = array('_REQUEST', '_SERVER', 'user');
	static $sensitive_folders = array('C:\www', '/usr/www'); // will attempt to scrub these from output

	private function __construct() {
		// anything to do here?
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
			// instantiate the cold version of the stuff (which is this class, unchanged.)
			self::$instance = new m_live();
		} else {
			// instantiate the hot stuff
			self::$instance = new m();
		}
	}

	/**
	* dump stuff. click the black bar to expand when the data is complex. you can set options that include founder verb, relevant backtrace depth, etc.
	*
	* @param mixed $dumpee the thing you want dumpd
	* @param string $label the most visible part of the dump
	* @param array $options see code for all options
	*/
	public static function dump($dumpee, $label = 'no label provided', $options = array()) {
		// temp for debug usage:
		if( ! is_array($options)) {
			if(is_numeric($options)) {
				$options = array('relevant_backtrace_depth' => $options);
			} else {
				m::death(debug_backtrace());
			}
		}
		if( ! isset(self::$instance)) self::init();
		if( ! isset($options['founder'])) {
			if( ! isset($options['founder_verb'])) $options['founder_verb'] = 'm::dump&rsquo;d on ';
			if( ! isset($options['relevant_backtrace_depth'])) $options['relevant_backtrace_depth'] = 0;
			$options['founder'] = $options['founder_verb'] . self::get_caller_fragment($options['relevant_backtrace_depth']);
		}
		// collapse? expand?
		if( ! isset($options['collapse'])) $options['collapse'] = true;

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

	protected function do_dump($dumpee, $label = 'no label provided', $options = array()) {

			static $vDump_display_count = 0;
			$vDump_display_count ++;

			if(strlen($label)) $label = array($label); else $label = array();

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
				<div class="collapseybull<?=$options['collapse'] ? ' collapseybull_on_init' : ''?>"><?=self::_dump($dumpee, -1, $label)?></div>
			<? endif; ?>
			<div class="vDump_meta_info_main" style="font-size:11px; text-transform:uppercase; color: white; background-color: #333; padding:5px 5px 5px 5px;"><?=$options['founder']?></div>
		</div>
		<? if($vDump_display_count == 1): // only for the first one.?>
			<!--<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js'></script>-->
			<script type='text/javascript' src='http://assets.sarumino.dev/lib/js/jQuery/jquery.min.js'></script>
			<script>
				if(typeof $ == 'function') {
					$(function() {
						$("div.vDump_label").add("div.vDump_meta_info_main").css('cursor', 'pointer').click(function() {
							$(this).parent().find('div.collapseybull').first().toggle(500);
						})
						$("div.collapseybull_on_init").hide(500);
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
				if(array_values($dumpee) !== $dumpee) $sorted = true; ksort($dumpee); // it's associative, so sort it. -- may cause errors
				?><span>array with <?=count($dumpee)?> item<?=count($dumpee) != 1 ? 's' : ''?><span class="vDump_depth_twistee_control"></span><?=$sorted ? ' <span class="note">(This associative array has been sorted.)</note>' : '';?><br>
				<? foreach($dumpee as $key => $value): ?>
					<? if(is_string($value) and (strtolower($key) == 'pass' or stripos($key, 'password') !== false)) $value = 'JA JA JA'; ?>
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
						<? if(is_string($dumpee->$key) and (strtolower($key) == 'pass' or stripos($key, 'password') !== false)) $dumpee->$key = 'JA JA JA'; ?>
						<div class='depth_<?=$depth?>'><span class="key"><?=$key?></span><?=$separator?><?=self::_dump($dumpee->$key, $depth, $key) ?></div>
					<? endforeach; ?>
					<? if(get_class($dumpee) != 'stdClass') :
						$methods = get_class_methods(get_class($dumpee)); ?>
						<div style="background-color:wheat; color:#333; font-weight:bold; font-size:16px; padding:5px;" class="depth_<?=$depth?>"><?=count($methods) ? number_format(count($methods)) . ' method' . (count($methods) != 1 ? 's' : '') : 'no methods'?><span class='vDump_twistee_control'></span>
							<? if(count($methods)) echo '<ul style="margin:0; padding:0; display:none;" class="vDump_twistee_zone">';
								foreach($methods as $method_name): ?>
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
			@mail(self::$developer_email, $subject, $body, $headers);
		}
	}

	static function is_this_still_in_use($msg = '') {
		//m::is_this_still_in_use('old account password change code block'); // 2012 08 02 ab
		if( ! isset(self::$instance)) self::init();

		if(isset(self::$dump_these_vars_for_still_in_use)) {
			ob_start();
			foreach(self::$dump_these_vars_for_still_in_use as $var_name) {
				echo '<h4>$' . $var_name . '</h4>';
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
							echo '<h4>$' . $var_name . '</h4>';
							echo '<xmp>'; var_dump($GLOBALS[$var_name]); echo '</xmp>';
						} else {
							echo "<p>\$GLOBALS['" . $var_name . "'] not found.</p>";
						}
					break;
				}
				echo '</xmp>';
			}
			$body = ob_get_clean();
		} else {
			$body = '<p>No data to report.</p>';
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

	protected function is_bot() {
		return false; // todo
	}



	// death section //////////////////////////////////////////////////////
	/**
	* death dumps all its arguments then stops execution.
	* if in live mode, it throws an exception, which should be properly handled by your code.
	*
	*/
	public static function death() { // function with nice name that you use in your code.
		if( ! isset(self::$instance)) self::init(); // get the instance
		self::$instance->do_death(func_get_args()); // depending on what is instantiated, this will run either m->do_screw() or m_live->do_screw()
		// now go find both function definitions.
	}

	protected function do_death() {

		// get any decho output
		echo '<div style="border:2px solid tan">';
		echo m::get_HTML_output();
		echo '</div>';

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
	public static function help($area = 'general', $depth = 1) {
		if( ! isset(self::$instance)) self::init(); // get the instance
		self::$instance->do_help($area, $depth);
	}

	/**
	* return boolean indicating whether or not help is enabled for the area, and at the $depth
	*
	*/
	protected function do_help($area, $depth) { // defaults specified in pub func
		// this is the dev version of the function. do what ever you want.
		// has help been initted?
		// i//f( ! isset(self::$helps)) $this->init_help();
		if($value = persistence::get($area . 'Help', array('session', 'request'), false)) {
			return $value >= $depth;
		}
	}

	public static function turn_on_help($area = 'general', $depth = 1) {

	}

	public static function turn_off_help($area = 'all') {

	}


	// decho section ///////////////////////////////////////////////////////////////

	// a decho is a dump that can have it's output collected and output on the side.

	public static function decho() { // function with nice name that you use in your code.
		if( ! isset(self::$instance)) self::init(); // get the instance
		self::$instance->do_decho(func_get_args()); // depending on what is instantiated, this will run either m->do_screw() or m_live->do_screw()
		// now go find both function definitions.
	}

	protected function do_decho() {
		$temp = func_get_args(); // mind you how args are passedin to this slave func
		$args = array_pop($temp);

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
		return '<div class="m_decho" style="background-color:wheat; color:#333; font-size:13px; padding:2px 3px; margin:2px; font-family:Arial" title="dechoed ' . strip_tags($this->get_caller_fragment(2)) . '">' . $str . '</div>';
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



}
