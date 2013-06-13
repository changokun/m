<?php
// will need short_open_tag = On in your php.ini
// also, copy rename sample.ini to m.ini, and set the values inside it.

class m {

	const SEPARATOR = ' =&gt; '; // maintain html entities.

	public static $mode; // do not set a default - this is how we know if init has run or not.
	public static $developer_email;
	public static $m_email_domain; // so that we can create our own email addresses
	public static $email_headers; // filled in on init so that we gots the html, from, etc.
	protected static $dump_these_global_vars_for_still_in_use = array('_REQUEST', '_SERVER');
	protected static $dump_these_global_vars_for_aMail = array('_REQUEST', '_SERVER');
	protected static $classes_to_skip = array();
	static $sensitive_folders; // will attempt to scrub these from output
	static public $jQuery_src_url = 'http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js';
	public static $side_dish; // collects dechoes
	public static $is_bot; // see is_bot method.


	public static $default_dump_label = 'No label provided'; // this can be set in config
	public static $default_death_label = 'Death rattle'; // this can be set in config

	static private $javascript_has_been_output = false;
	static private $debug_info;

	private static function init() {
		// set the mode. it is like these words: live, dev, report and they indicate if a civilian could be observing output, or if a dev is.

		// default to live
		self::$mode = 'live';

		// todo make this more flexible as to how it is defined.
		if(isset($_SERVER['site_is_dev']) and $_SERVER['site_is_dev']) self::$mode = 'dev';

		// load config
		if(file_exists(dirname(__FILE__) . '/m.ini')) {
			foreach(parse_ini_file('m.ini', true) as $key => $value) {
				self::$$key = $value;
			}
		}

		// did we get an email domain name?
		if(empty(static::$m_email_domain)) {
			// todo petty warning?
			echo '<p>config lacks m_email_domain</p>';
			static::$m_email_domain = 'misconfiguredm.com';
		}
		static::$email_headers = implode("\r\n", array(
			'From: m_' . $_SERVER['SERVER_NAME'] . '@' . static::$m_email_domain,
			'Content-type: text/html; charset=utf-8'
		));

		// make sure sensitive folders includes doc root
		if(empty(self::$sensitive_folders)) {
			self::$sensitive_folders = array();
		} elseif(is_scalar(self::$sensitive_folders)) {
			self::$sensitive_folders = array(self::$sensitive_folders);
		} elseif( ! is_array(self::$sensitive_folders)) {
			throw new Exception('bad sensitive folder config. i want an array!');
		}
		self::$sensitive_folders[] = $_SERVER['DOCUMENT_ROOT'];
		// and let's go ahead and make all the slashes more flexible: /
		foreach(self::$sensitive_folders as $key => $folder) self::$sensitive_folders[$key] = str_replace('\\', '/', $folder);


		// todo check request vars for instructions to turn on/ help - and to persist it in session.

	}

	public static function __callStatic($name, $args) {
		// the method named $name was not found.
		// make sure we have been initialized
		if( ! isset(static::$mode)) static::init();

		// try to look for the function with the mode appended.
		$potential_method_name = $name . '_' . static::$mode;
		if(method_exists(get_called_class(), $potential_method_name)) {
			return forward_static_call_array(array('static', $potential_method_name), $args);
		}

		// if mode isn't live, we're basically done.
		if(static::$mode == 'live') return false; // this will deny helps, dechoes, etc.

		// todo - i'm cascading from dev to live, but that doesn' tmake sense when you need report or whatever.

		// report the missing function, by email or log file.

		// for now, output to screen
		echo '<p>unknown m method: ' . $name . ', please stand by.</p>';
	}

	public function __construct() {
		if( ! isset(static::$mode)) static::init();
		if(static::$mode != 'live') static::death('oh no, don&rsquo;t try to instantiate me, that is silly.');
	}

	public static function dump_live($dumpee, $label = 'no label provided', $options = array()) {
		// check for some kind of monitoring/logging mode. if found, generate output and mail it or what evs. otherwise:
		// echo '<p>' . get_called_class() . '::' . __FUNCTION__ . '() returns no output.</p>';
		echo '<!-- ' . get_called_class() . '::' . __FUNCTION__ . '() returns no output. -->';
	}

	/**
	* this one creates the html wrapper and label
	*
	* @param mixed $dumpee
	* @param string $label
	* @param array $options
	*/
	public static function dump_dev($dumpee, $label = NULL, $options = array()) {
		if( ! isset(static::$mode)) static::init();

		// we want a stack frame (or array of them) - did we get one? if not, we'll get oour own, and you can give hints as to how deep to go
		if(isset($options['stack_frame'])) {
			$stack_frame = $options['stack_frame'];
		} else {
			// get a fresh backtrace
			if(empty($options['backtrace'])) {
				$options['backtrace'] = debug_backtrace(); // get a fresh backtrace
				// remove myself - look for my name with a file.... the __calStatic doesn't report a file name on my frame.
				while(count($options['backtrace']) and ( ! isset($options['backtrace'][0]['function']) or substr($options['backtrace'][0]['function'], 0, 4) != 'dump' or ! isset($options['backtrace'][0]['file']))) {
					array_shift($options['backtrace']); // lose one
				}
				// if your dump call is nested inside a more important function, add backtrace_additional_depth // todo test
				if(isset($options['backtrace_additional_depth']) and is_numeric($options['backtrace_additional_depth'])) {
					$temp = (int) $options['backtrace_additional_depth'];
					while($temp) {
						array_shift($options['backtrace']); // lose one
						$temp --;
					}
				}
			}
			$stack_frame = $options['backtrace'][0];
		}

		if(empty($label)) {
			$label = array(static::$default_dump_label);
		} elseif(is_scalar($label)) {
			$label = array($label);
		} else {
			$label = (array) $label; /// hmmmmm
		}
		//echo '<xmp>'; var_dump($label); echo '</xmp>';

		// todo - union with default options
		// collapse? expand?
		$options['collapse'] = isset($options['collapse']) ? $options['collapse'] : true;

		// set the founder string. it is what shows up at the bottom, telling you where in the code to go
		if( ! isset($options['founder'])) {
			if( ! isset($options['founder_verb'])) $options['founder_verb'] = 'm::dump&rsquo;d on '; // keep the html entity. many times dumps occur on headless pages
			$options['founder'] = $options['founder_verb'] . self::get_caller_fragment($stack_frame);
		}

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

<div class="mDump" style="border: 2px solid olive; font-family: Arial; font-size: 13px; margin: 10px 0;">
	<? if($label) : ?><div class = "mDump_label" style="font-size:16px; font-weight: bold; color:white; background-color:#333; padding:5px 5px 8px 5px;"><?=implode(' | ', $label)?></div><? endif; ?>
	<? if( ! $done) : ?><div class="collapseybull<?=$options['collapse'] ? ' collapseybull_on_init' : ''?>"><?=static::_dump($dumpee, -1)?></div><? endif; ?>
	<div class="mDump_meta_info_main" style="font-size:11px; text-transform:uppercase; color: white; background-color: #333; padding:5px 5px 5px 5px; letter-spacing:2"><?=$options['founder']?></div>
</div>

<?
		echo static::get_asset_html();

	}

	/**
	* this is the one that handles the interior, and is recursed.
	*
	* @param mixed $dumpee
	* @param int $depth
	* @param mixed $parent_key - sort of the label? so we know what we are dealing with?
	*/
	private static function _dump($dumpee, $depth, $parent_key = '') {
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
				?><span class="float_value"><?=$dumpee?> <span class="mDump_meta_info">(<?=numlen($dumpee)?>-digit float/double)</span></span><?
			break;

			case 'integer':
				?><span class="integer_value"><?=$dumpee // no number format, plz?> <span class="mDump_meta_info">(<?if(4 < $numlen = numlen($dumpee)) echo $numlen . '-digit ';?>integer)</span></span><?
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
					?>array with <?=count($dumpee)?> item<?=count($dumpee) != 1 ? 's' : ''?><span class="mDump_depth_twistee_control"></span>
					<? foreach($dumpee as $key => $value): ?>
						<div class='depth_<?=$depth?>' <?=static::get_inline_style_tag_for_depth($depth)?>>
							<span class="key"><?=$key?></span><?=self::SEPARATOR?><?= self::_dump($value, $depth, $key) ?>
						</div>
					<? endforeach;
				}
			break;

			case 'object':

				if($dumpee instanceof xyz) { // special class treatment

				} elseif(method_exists($dumpee, '_dump')) { // dumpee has special method for showing off.
					?><div class='depth_<?=$depth?>' <?=static::get_inline_style_tag_for_depth($depth)?>><span class="key">custom <?=get_class($dumpee)?>->_dump()</span><?=self::SEPARATOR?><?=self::_dump($dumpee->_dump(), $depth) ?>
						</div><?

				} else { // normal object stuff, please
					// make a list of keys
					$keys = array();
					foreach($dumpee as $key => $value) {
						$keys[] = $key;
					}
					asort($keys); // todo make configurable

					echo get_class($dumpee) . ' object';

					if($depth) { // no twistee if no depth
						echo '<span class="mDump_depth_twistee_control"></span>';
					}

					foreach($keys as $key) : ?>
						<div class='depth_<?=$depth?>' <?=static::get_inline_style_tag_for_depth($depth)?>><span class="key"><?=$key?></span><?=self::SEPARATOR?><?=self::_dump($dumpee->$key, $depth, $key) ?>
						</div>
					<? endforeach; ?>

					<? /* todo make optional if(get_class($dumpee) != 'stdClass') :
						$methods = get_class_methods(get_class($dumpee)); ?>
						<div style="background-color:wheat; color:#333; font-weight:bold; font-size:16px; padding:5px;"><?=count($methods) ? number_format(count($methods)) . ' method' . (count($methods) != 1 ? 's' : '') : 'no methods'?><span class='mDump_twistee_control'></span>
							<? if(count($methods)) echo '<ul style="margin:0; padding:0; display:none;" class="mDump_twistee_zone">';
								foreach($methods as $method_name): ?>
								<li style="list-style-type:none; padding-left:10px; font-weight:normal; font-size:13px;" title="<?=get_class($dumpee)?>::<?=$method_name?>"><?=$method_name?></li>
							<? endforeach; echo '</ul>'; ?>
						</div>
					<? endif;*/
				}

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

	public static function get_asset_html() {
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
/*	div.mDump span div { padding:3px; margin:3px;}*/
	div.mDump div div { margin-left:7px; }
	div.mDump div div.depth_0 { margin-left:3px; }
<?
/*	for ($x = 1; $x < 12; $x++) { // this is a big old wtf. dechex maybe doesn't like negative numbers?
		echo "	div.depth_$x { background-color: #" . str_repeat(strtoupper(dechex(15 - $x)), 3) . '; ';
		if($x > 7) echo 'color:white;';
		echo "}\n";
	}*/
?>
	.key { color: #444; }
	.mDump_meta_info { color:#999 }
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
		<?
		return ob_get_clean();
	}

	public static function death_dev($dumpee = NULL, $label = NULL, $options = array()) {
		// get any decho output
		if($temp = m::get_HTML_output()) echo '<div style="border:2px solid tan; padding:6px;">' . $temp . '</div>';

		// get a fresh backtrace
		if( ! isset($options['backtrace'])) {
			$options['backtrace'] = debug_backtrace();
			// remove myself - look for my name with a file.... the __calStatic doesn't report a file name on my frame.
			while(count($options['backtrace']) and (substr($options['backtrace'][0]['function'], 0, 5) != 'death' or ! isset($options['backtrace'][0]['file']))) {
				array_shift($options['backtrace']); // lose one
			}
		}

		if(is_scalar($label)) $label = array($label);
		if(empty($label)) $label = array(static::$default_death_label);

		// make the label red!
		$label[0] = '<span style="color:crimson">' . $label[0] . '</span>';

		// todo - union with default options
		// collapse? expand?
		$options['collapse'] = isset($options['collapse']) ? $options['collapse'] : false;

		// set the founder string. it is what shows up at the bottom, telling you where in the code to go
		if( ! isset($options['founder'])) {
			$options['founder'] = 'Cause of death on ' . self::get_caller_fragment($options['backtrace'][0]);
		}

		static::dump_dev($dumpee, $label, $options);

		// and then die.
		die;

	}

	public static function death_live($dumpee = NULL, $label = NULL, $options = array()) {
		// so, you left a death in your code. does that mean processing should stop? on live?
		// let's make that configgable todo
		// if it is a bad thng: 		throw new Exception('Sorry, we have an issue handling your request.');

		// check for some kind of monitoring/logging mode. if found, generate output and mail it or what evs. otherwise:
		// echo '<p>' . get_called_class() . '::' . __FUNCTION__ . '() returns no output.</p>';
		echo '<!-- ' . get_called_class() . '::' . __FUNCTION__ . '() returns no output. -->';
	}

	public static function get_HTML_output_dev() {
		if(empty(static::$side_dish)) return NULL;
		return static::$side_dish;
	}

	/*public static function get_HTML_output_live() {
		return NULL;
	}*/

	public static function is_this_still_in_use($msg = '') { //m::is_this_still_in_use('old account password change code block'); // 2012 08 02 ab
		if( ! isset(static::$mode)) static::init();

		// get fresh debug
		static::$debug_info = debug_backtrace();

		// send an email on live, or just die on dev.
		if(self::$mode == 'live') {
			if(count(self::$dump_these_global_vars_for_still_in_use)) {
				$body = self::get_global_dumps(self::$dump_these_global_vars_for_still_in_use);
			} else {
				$body = '<p>No data to report (if you would like to see some data, consider setting dump_these_global_vars_for_still_in_use in your m.ini).</p>';
			}

			// get caller info and append to msg.
			$msg .= ' | complained ' . self::get_caller_fragment(static::$debug_info[1]);
			$subject = "STILL IN USE: $msg";
			if(self::is_bot()) $subject .= ' [bot]';
			@mail(static::$developer_email, $subject, $body, static::$email_headers);
		} else {
			self::death('it is true.', 'STILL IN USE: ' . $msg, array('relevant_backtrace_depth' => 2));
		}
	}

	public static function aMail() {
		if( ! isset(static::$mode)) static::init();

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
				static::dump_dev($arg);
				$body .= ob_get_clean();
			}
		}

		// dump the configured global vars + debug info
		$body .= self::get_global_dumps(self::$dump_these_global_vars_for_aMail, array('debug info' => $debugInfo));

		if(self::is_bot()) $subject .= " [bot]";

		@mail(static::$developer_email, $subject, $body, static::$email_headers);

	}

	public static function help_dev($area = 'general', $depth = 0) { // todo add tiny drumkit as an option
		return (bool) ((isset($_REQUEST[$area . 'Help']) and $_REQUEST[$area . 'Help'] > $depth) or (isset($_SESSION[$area . 'HelpXXXXXXXXXXXXXXXXX']) and $_SESSION[$area . 'HelpXXXXXXXXXXX'] > $depth));
	}

	/*public static function help_live() { // todo - not needed, overload will return false, right?
		// no help on live. todo - log it?
		return false;
	}*/

	public static function turn_on_help($area = 'general', $depth = 1) {
		$_REQUEST[$area . 'Help'] = $depth;
	}

	public static function turn_off_help($area = 'all') {
		$_REQUEST[$area . 'Help'] = false;
	}


	public static function decho_dev() {
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

		$debug_info = debug_backtrace();
		// we want the frame where the function is 'decho'
		foreach($debug_info as $potential_stack_frame) {
			if($potential_stack_frame['function'] == 'decho') {
				$stack_frame =$potential_stack_frame;
				break;
			}
		}

		ob_start();

		if(count($args) == 1 and is_scalar($args[0])) {
			// output simple string
			echo static::get_scalar_decho_HTML($args[0], $stack_frame);
		} elseif(count($args) == 2 and is_scalar($args[1])) {
			// same as a dump - dumpee and label have been provided.
			static::dump_dev($args[0], $args[1], array('relevant_backtrace_depth' => 3));
		} else {
			$all_scalar = true; // we'll see about that!
			foreach($args as $arg) if( ! is_scalar($arg)) $all_scalar = false;
			if($all_scalar) {
				foreach($args as $arg) echo static::get_scalar_decho_HTML($arg, $stack_frame);
			} else {
				foreach($args as $arg) static::dump_dev($arg, 'decho', array('relevant_backtrace_depth' => 3));
			}
		}

		$output = ob_get_clean();

		// now, the big question... inline? or on the side?
		// if it was not set by args. default to 'on the side' unless emergencyHelp is on.
		// rather than call the help method and risk a loop, i'll do a manual check for emergency Help
		if( ! isset($inline)) $inline = static::help('emergency');
		if($inline) {
			echo $output;
		} else {
			static::$side_dish .= $output;
		}

		//m::dump($args, 'decho!!!', array('relevant_backtrace_depth' => 2));
		// if there is one arg, and it is scalar, let's do a simple output. if it is more complex, auto-dump.
		// this is the dev version of the function. do what ever you want.
	}

	protected static function get_scalar_decho_HTML($str, $stack_frame) {
		return '<div class="m_decho" style="background-color:wheat; color:#333; font-size:13px; padding:2px 3px; margin:2px; font-family:Arial" title="dechoed ' . strip_tags(static::get_caller_fragment($stack_frame)) . '">' . $str . '</div>';
	}






	/**
	* produces ' on line 123 of file /xyz' without revealing doc root.
	*
	* @param int $relevant_backtrace_depth
	*/
	static function get_caller_fragment($stack_frame) {
		$return = '';
		// is there a class? temp disable, maybe screws up depth
		// $class = isset($debug_info[$relevant_backtrace_depth + 1]['class']) ? $debug_info[++$relevant_backtrace_depth]['class'] . '::' : '';

		if(isset($stack_frame['line']) and isset($stack_frame['file'])) {
			$return .= 'on line ' . $stack_frame['line'] . ' of ' . $stack_frame['file'];
		} elseif(isset($stack_frame['line'])) {
			$return .= 'on line ' . $stack_frame['line'] . ' of an unknown file, oddly enough. does this happen?';
		} elseif(isset($stack_frame['file'])) {
			$return .= 'on an unknown line of ' . $stack_frame['file'];
		}

		// clean up slashes, remove core dir info
		$return = str_replace('\\', '/', $return); // so that we always use forward slashes
		foreach(self::$sensitive_folders as $folder) $return = str_replace($folder, '', $return);

		return $return;
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

	static private function get_inline_style_tag_for_depth($depth) {
		$output = 'style="padding:3px; margin:3px; background-color: #' . str_repeat(strtoupper(dechex(14 - $depth)), 3) . '; ';
		if($depth > 7) $output .= ' color:white; ';
		$output .= '"';
		return $output;
	}

	/** use this to get it to put the javascript on page again, if nec. i'm using this after closing the connection in other output. */
	public static function reset_javascript_output_check() {
		self::$javascript_has_been_output = false;
	}

	protected static function get_global_dumps($var_names, $additional_data = array()) {
		// additional data is an assoc array (label, data) that is more things to be dumped in the same styles
		ob_start();

		foreach($additional_data as $label => $data) {
			static::dump_dev($data, $label);
		}

		foreach($var_names as $var_name) {
			switch($var_name) { // we do this this way because these globals are picky about refs or something.
				case '_SERVER':
					static::dump_dev($_SERVER, '$_SERVER');
				break;
				case '_REQUEST':
					static::dump_dev($_REQUEST, '$_REQUEST');
				break;
				default:
					// assumed to be a global
					if(isset($GLOBALS[$var_name])) {
						static::dump_dev($GLOBALS[$var_name], $GLOBALS[$var_name]);
					} else {
						echo "<p>\$GLOBALS['" . $var_name . "'] not found.</p>";
					}
				break;
			}
		}


		$temp = ob_get_clean();
		return $temp;
	}

		protected static function XXXget_global_dumps($var_names, $additional_data = array()) {
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

	public static function is_bot() {
		if(isset(self::$is_bot)) return self::$is_bot;
		// this is a very simple detection. if you already know whether your requestor is a bot, tell m so that m doesn't check: m::$is_bot = true/false;
		self::$is_bot = false;
		if(empty($_SERVER['HTTP_USER_AGENT'])) {
			self::$is_bot = true;
		} else {
			foreach(array('bot', 'msn', 'google', 'slurp', 'jeeves', 'spider', 'yandex') as $bot_name) {
				if(stripos($_SERVER['HTTP_USER_AGENT'], $bot_name) !== false) {
					self::$is_bot = true;
					break;
				}
			}
		}
		return self::$is_bot;
	}

	public static function status() {
		if( ! isset(self::$mode)) self::init();

		$data = array();
		$data['all emails go to'] = static::$developer_email;
		$data['all emails use these headers'] = '<pre>' . static::$email_headers . '</pre>';

		m::dump($data, 'm status', array('collapse' => false));

		return true;
	}

}




function numlen($number) {
	if(is_float($number)) {
		// not sure what to do really.
		return strlen(abs($number));
	} elseif(is_int($number)) {
		if($number === 0) return 1;
		return ceil(log10(abs($number)));
	}
	return false;
}