<?php
class FH_Theme {
	const NAME		= 'FoxHunt';
	const ASSETS		= 'assets';
	const BUFFER		= 128;
	const CAPABILITY	= 'administrator';
	const MENU		= 'menu';
	const ROLES_DIR		= 'roles';
	const TEXTDOMAIN	= __CLASS__;
	const HOME		= 'https://www.foxhunt.ro';
	const GET		= 'page';
	const ACTION		= 'action';
	const DEFAULT_ACTION	= 'read';

	const COMMON_DIR	= 'common';
	const INFO_DIR		= 'info';
	const HELP_DIR		= 'help';

	public static $A	= [
		'create',
		'copy',
		'read',
		'update',
		'delete',
		'search',
		'export',
		'import',
		'load'
		];

	private $assets;
	private $storage;
	private $user;
	private $scenario;

	private $pages;
	private $breadcrumbs;

	private $page;
	private $action;

	public function __construct ($menus = [], $sidebars = []) {
#		global $locale;

		$this->assets = [];
		$this->storage = new FH_Storage ();
		$this->user = new FH_User ($this->storage->get ('player'));
		$this->scenario = null;

#		if ($this->storage->get ('locale'))
#			$this->storage->set ('locale', $locale);
#		else
#			$locale = $this->storage->get ('locale');
		
		$this->page = isset ($_GET[self::GET]) ? $_GET[self::GET] : 'dashboard';

		$this->action = isset ($_GET[self::ACTION]) ? (in_array ($_GET[self::ACTION], static::$A) ? $_GET[self::ACTION] : self::DEFAULT_ACTION) : self::DEFAULT_ACTION;

		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'index_rel_link');
		remove_action('wp_head', 'wp_generator');

		add_action ('wp_enqueue_scripts', [$this, 'main_scripts']);
		add_action ('admin_enqueue_scripts', [$this, 'admin_scripts']);
		add_action ('admin_menu', [$this, 'admin_menu']);
		}

	public function set ($key = null, $value = null) {
		if (is_string ($key)) {
			switch ($key) {
				case 'scenario':
					$this->storage->set ($key, $value instanceof FH_Scenario ? $value->get () : $value);
					break;
				}
			}
		else
		if (is_array ($key)) {
			}
		return FALSE;
		}

	public function get ($key = null, $opts = null) {
		switch ((string) $key) {
			case 'assets':
				$out = [];

				$path = get_stylesheet_directory () . DIRECTORY_SEPARATOR . self::ASSETS . DIRECTORY_SEPARATOR;
				$folders = [ 'js', 'css' ];

				foreach ($folders as $folder) {
					$search_path = $path . $folder;
					if (!is_dir ($search_path)) continue;
					if (($dh = opendir ($search_path)) === FALSE) continue;
					while (($file = readdir ($dh)) !== FALSE) {
						if ($file[0] == '.') continue;
						$header = $this->get ('header', $search_path . DIRECTORY_SEPARATOR . $file);
						if (is_null ($header)) continue;
						$out[] = [
							'type' => $folder,
							'path' => get_template_directory_uri () . DIRECTORY_SEPARATOR . self::ASSETS . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $file,
							'name' => isset ($header['name']) ? $header['name'] : $file,
							'dependencies' => isset ($header['dependencies']) ? explode (',', $header['dependencies']) : [],
							'version' => isset ($header['version']) ? $header['version'] : '0.1',
							'footer' => strtolower(isset ($header['footer']) ? $header['footer'] : '') == 'true' ? TRUE : FALSE,
							'media' => isset ($header['media']) ? $header['media'] : 'all',
							'scope' => isset ($header['scope']) ? $header['scope'] : ''
							];
						}
					closedir ($dh);
					}

				return $out;
				break;
			case 'content':
				if (!is_array ($opts) && is_string ($opts) && is_numeric ($opts)) {
					$opts = [ 'id' => (int) $opts, 'echo' => TRUE ];
					}
				$page = get_post ($opts);
				$content = apply_filters ('the_content', $page->post_content);
				$content = str_replace (']]>', ']]&gt;', $content);
				if (!$opts['echo']) return $content;
				echo $content;
				break;
			case 'dir':
				if (strpos ($opts, 'request::') === 0) {
					$opts = substr ($opts, 9);
					$path = TEMPLATEPATH . DIRECTORY_SEPARATOR . self::ROLES_DIR . DIRECTORY_SEPARATOR . $this->user->get ('role') . DIRECTORY_SEPARATOR . $opts . DIRECTORY_SEPARATOR . $this->page . '.php';
					return $path;
					}
				if (strpos ($opts, 'role::') === 0) {
					$opts = substr ($opts, 6);
					$path = TEMPLATEPATH . DIRECTORY_SEPARATOR . self::ROLES_DIR . DIRECTORY_SEPARATOR . $this->user->get ('role') . DIRECTORY_SEPARATOR . $opts;
					return $path;
					}
				break;
			case 'url':
				$url = $_SERVER['REQUEST_URI'];
				if (($pos = strpos ($url, '?')) !== FALSE)
					$url = substr ($url, 0, $pos);

				$opts = is_array ($opts) ? $opts : [];
				$query = isset ($_GET[self::GET]) ? array_merge ([self::GET => $_GET[self::GET]], $opts) : $opts;
				return $url . '?' . http_build_query ($query);
				break;
			case 'header':
				$out = [];

				$state = 0;
				
				if (!file_exists ($opts)) return null;
				if (($fh = fopen ($opts, 'r')) === FALSE) return null;
				
				while ((($line = fgets ($fh, self::BUFFER)) !== FALSE) && ($state < 2)) {
					$line = trim ($line);
					if (strpos ($line, '/*') === 0) { $state = 1; continue; }
					if (strpos ($line, '*/') === 0) break;
					if ($state < 1) continue;
					list ($key, $value) = explode (':', $line);
					$out[str_replace (' ', '_', trim(strtolower($key)))] = trim($value);
					}
				if (empty ($out))
					return null;

				fclose ($fh);
				return $out;
				break;
			case 'page':
				$opts = is_null ($opts) ? 'slug' : $opts;
				if (is_string ($opts))
					switch ($opts) {
						case 'name':
							if (empty ($this->breadcrumbs)) $this->get ('breadcrumbs');
							return isset ($this->breadcrumbs[$this->page]) ? $this->breadcrumbs[$this->page] : '';
							break;
						case 'slug':
							return $this->page;
							break;
						}
				break;
			case 'pages':
				if (!empty ($this->pages)) return $this->pages;

				$pages = [];
				$search_path = $this->get ('dir', 'role::pages');

				if (!is_dir ($search_path)) return [];
				if (($dh = opendir ($search_path)) === FALSE) return [];

				while (($file = readdir ($dh)) !== FALSE) {
					if ($file[0] == '.') continue;
					$slug = substr ($file, 0, -4);
					if ($file != $slug . '.php') continue;
					$header = $this->get ('header', $search_path . DIRECTORY_SEPARATOR . $file);
					if (is_null ($header) || empty ($header)) continue;

					$p_children = null;
					if (isset ($header['children']) && class_exists ($header['children'])) {
						$p_children = new FH_List (trim ($header['children']));
						if (!$p_children->is ('empty') && isset ($header['hidden'])) unset ($header['hidden']);
						}

					if (!isset ($header['admin']) || (isset ($header['admin']) && $this->user->is ('admin'))) {
						$pages[] = (object) [
							'name'	=> isset ($header['name']) ? $header['name'] : '',
							'slug' => $slug,
							'parent' => isset ($header['parent']) ? $header['parent'] : 'root',
							'ord' => isset ($header['order']) ? $header['order'] : 0,
							'hidden' => isset ($header['hidden']) && (strtolower ($header['hidden']) == 'true') ? TRUE : FALSE
							];
						}

					if (!is_null ($p_children) && !$p_children->is ('empty')) {
						$p_children->sort ();
						$ord = 0;
						foreach ($p_children->get () as $p_child)
							$pages[] = (object) [
								'name'	=> $p_child->get ('name'),
								'slug' => $slug . '&' . $header['childslug'] . '=' . $p_child->get ('slug'),
								'parent' => $slug,
								'ord' => $ord++,
								'hidden' => FALSE
								];
						}
					}

				if (empty ($pages)) return [];
				$children = [];
				foreach ($pages as $page)
					$children[$page->parent ? $page->parent : 'root'][] = $page;

				foreach ($pages as $page)
					if (isset ($children[$page->slug])) {
						usort ($children[$page->slug], ['FH_Theme', '_cmp_ord']);
						$page->children = $children[$page->slug];
						}

				usort ($children['root'], ['FH_Theme', '_cmp_ord']);
				$this->pages = array_reverse ($children['root']);
				return $this->pages;
				break;
			case 'action':
				return $this->action;
				break;
			case 'breadcrumbs':
				if (empty ($this->pages)) $this->get ('pages');
				if (empty ($this->pages)) return [];
				if (!empty ($this->breadcrumbs)) return $this->breadcrumbs;

				$found = [];
				$search = $this->page;

				if ($search != 'dashboard') {
					while ($search != 'root') {
						$stack = $this->pages;
						while (!empty ($stack)) {
							$current = array_shift ($stack);
							if (is_null ($current)) break;
							if ($current->slug == $search) {
								$found[$current->slug] = $current->name;
								$search = $current->parent;
								break;
								}
							if (!empty ($current->children))
								$stack = array_merge ($stack, $current->children);
							}
						if ($search == $this->page)
							$search = 'root';
						}
					}

				$found['dashboard'] = self::NAME;
			
				$this->breadcrumbs = array_reverse ($found, TRUE);
				return $this->breadcrumbs;
				break;
			case 'user':
				if (is_null ($opts)) return $this->user;
				if (is_string ($opts) && is_object ($this->user))
					return $this->user->get ($opts);
				return null;
				break;
			case 'scenario':
				if (is_null ($opts)) return $this->scenario;
				if (is_string ($opts) && is_object ($this->scenario))
					return $this->scenario->get ($opts);
				return null;
				break;
			case 'currency':
				return '&euro;';
				break;
			}
		return null;
		}

	public function out ($key = null, $opts = null, $callback = null) {
		$content = $this->get ($key, $opts);
		if (!is_null ($callback) && is_callable ($callback))
			$content = call_user_func ($callback, $content);
		echo $content;
		}

	public function main_scripts () {
		if (empty ($this->assets)) $this->assets = $this->get ('assets');

		if (!empty ($this->assets))
		foreach ($this->assets as $asset) {
			if ($asset['scope'] != '') continue;
			if ($asset['type'] == 'js') wp_enqueue_script ($asset['name'], $asset['path'], $asset['dependencies'], $asset['version'], $asset['footer']);
			if ($asset['type'] == 'css') wp_enqueue_style ($asset['name'], $asset['path'], $asset['dependencies'], $asset['version'], $asset['media']);
			}

		wp_enqueue_script ('google-charts', 'https://www.gstatic.com/charts/loader.js', ['jquery'], '0.1', TRUE);
		}

	public function admin_menu () {
		add_menu_page (self::NAME . ' Menu', self::NAME . ' Menu', self::CAPABILITY, FH_Options::PREFIX . self::MENU, [$this, 'admin_page']);

		$options = new FH_Options ();
		$options->register (self::MENU, self::CAPABILITY);
		}

	public function admin_page () {
		}

	public function admin_scripts () {
		if (empty ($this->assets)) $this->assets = $this->get ('assets');

		if (!empty ($this->assets))
		foreach ($this->assets as $asset) {
			if ($asset['scope'] == '') continue;
			if (	$asset['scope'] == '' ||
				$asset['scope'] != 'admin' ||
				((strpos ($assets['scope'], self::GET . '=') === 0) && !(isset ($_GET[self::GET]) && in_array ($_GET[self::GET], explode (',', substr($assets['scope'], 5)))))
				) continue;
			if (!empty ($asset['dependencies'])) {
				foreach ($asset['dependencies'] as $index => $dependency) {
					if ($dependency != 'media') continue;
					unset ($asset['dependencies'][$index]);
					wp_enqueue_media ();
					}
				}
			if ($asset['type'] == 'js') wp_enqueue_script ($asset['name'], $asset['path'], $asset['dependencies'], $asset['version'], $asset['footer']);
			if ($asset['type'] == 'css') wp_enqueue_style ($asset['name'], $asset['path'], $asset['dependencies'], $asset['version'], $asset['media']);
			}
		}

	public function render ($key = null, $opts = null, $echo = TRUE) {
		$content = '';

		if (is_string ($key)) {
			switch ($key) {
				case 'menu':
					$stack = $this->get ('pages');
?><div class="navbar navbar-default" role="navigation"><div class="navbar-header"><button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-collapse"><span class="sr-only"><?php self::_e (/*T[*/'Toggle Navigation'/*]*/); ?></span></button><a class="navbar-brand" href="<?php echo self::HOME; ?>"><span><?php echo self::NAME; ?></span></a></div><div id="navbar-collapse" class="collapse navbar-collapse"><ul class="nav navbar-nav"><?php
					while (!empty ($stack)) {
						$current = array_pop ($stack);

						if (is_string ($current)) {
							echo $current;
							continue;
							}
						if ($current->hidden)
							continue;


						if (empty ($current->children)) {
							?><li><a href="?page=<?php echo $current->slug; ?>"><?php echo $current->name; ?></a><?php
							array_push ($stack, '</li>' . "\n");
							continue;
							}

						?><li class="dropdown"><a class="dropdown-toggle" href="?page=<?php echo $current->slug; ?>" data-toggle="dropdown"><?php echo $current->name; ?></a><?php
						$children = array_reverse ($current->children);
						array_unshift ($children, '</ul>' . "\n" . '</li>' . "\n");
						array_push ($children, '<ul class="dropdown-menu">' . "\n");
						$stack = array_merge ($stack, $children);
						}
?></ul><ul class="nav navbar-nav navbar-right"><li><a class="btn btn-danger pull-right" href="<?php echo self::HOME; ?>/?page=logout"><i class="fui-power"></i></a></li></ul></div></div><?php
					break;
				case 'breadcrumbs':
					if (empty ($this->breadcrumbs)) $this->get ('breadcrumbs');
					if (empty ($this->breadcrumbs)) break;
					foreach ($this->breadcrumbs as $slug => $name)
						?><a href="?<?php echo http_build_query ([self::GET => $slug]); ?>"><?php self::_e ($name); ?></a> / <?php
					break;
				case 'title':
					break;
				case 'header':
					?><h5><?php self::_e ($this->get ('page', 'name')); ?></h5><?php
					break;
				}
			}
		}

	public function __destruct () {
		}

	public static function _cmp_ord ($a, $b) {
		return $a->ord == $b->ord ? 0 : ($a->ord < $b->ord ? -1 : 1);
		}

	public static function __ ($text) {
		return __($text, self::TEXTDOMAIN);
		}

	public static function _e ($text) {
		_e ($text, self::TEXTDOMAIN);
		}

	public static function _h ($file, $line) {
		$help = new FH_Help ($file, $line);
		$message_content = $help->get ('text');

		if (current_user_can ('remove_users'))
			$content = '<div class="sd-help-window alert alert-success">
	<i class="arrow"></i>
	<i class="close fui-cross"></i>
	<div class="sd-message-read">
		<span>' . self::__($message_content) . '</span>
		<a href="#" class="sd-message-update"><i class="fui-new"></i>&nbsp;' . self::__ (/*T[*/'Edit Text'/*]*/) . '</a>
	</div>
	<div class="sd-message-update" data-message="FH_Help" data-message-id="' . $help->get () . '">
		<textarea class="form-control" name="message_content">' . $message_content . '</textarea>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 btn-left">
				<a href="" class="btn btn-xs btn-block btn-danger sd-cancel"><i class="fui-cross"></i>&nbsp;' . self::__ (/*T[*/'Cancel'/*]*/) . '</a>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 btn-right">
				<a href="" class="btn btn-xs btn-block btn-success sd-update"><i class="fui-check"></i>&nbsp;' . self::__ (/*T[*/'Save'/*]*/) . '</a>
			</div>
		</div>
	</div>
</div>';
		else
			$content = '<div class="sd-help-window alert alert-success">
	<i class="arrow"></i>
	<i class="close fui-cross"></i>
' . self::__($message_content) . '
</div>';
		$content = '<div class="sd-help">
	<i class="fui-question-circle"></i>
	' . $content . '
</div>';

		echo $content;
		}

	public static function _i ($file, $line) {
		$info = new FH_Info ($file, $line);
		$message_content = $info->get ('text');

		if (current_user_can ('remove_users'))
			$content = '<div class="alert alert-success">
	<div class="sd-message-read">
		<span>' . $message_content . '</span>
		<a href="" class="sd-message-update"><i class="fui-new"></i>&nbsp;' . self::__ (/*T[*/'Edit Text'/*]*/) . '</a>
	</div>
	<div class="sd-message-update" data-message="FH_Info" data-message-id="' . $info->get () . '">
		<textarea class="form-control" name="message_content">' . $message_content . '</textarea>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 btn-left">
				<a href="" class="btn btn-sm btn-block btn-danger sd-cancel"><i class="fui-cross"></i>&nbsp;' . self::__ (/*T[*/'Cancel'/*]*/) . '</a>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 btn-right">
				<a href="" class="btn btn-sm btn-block btn-success sd-update"><i class="fui-check"></i>&nbsp;' . self::__ (/*T[*/'Save'/*]*/) . '</a>
			</div>
		</div>
	</div>
</div>';
		else
			$content = '<div class="alert alert-success">
' . self::__($message_content) . '
</div>';
		echo $content;
		}

	public static function a ($text, $vars, $out = TRUE) {
		if (!empty ($vars) && is_array ($vars))
			foreach ($vars as $key => $value)
				$text = str_replace ('{' . $key . '}', $value, $text);

		if (!$out) return $text;
		echo $text;
		}

	public static function c ($data, $title = '', $echo = TRUE) {
		$chart = json_encode ((object) [
			'title'		=> $title,
			'data'		=> $data
			]);
		$chart = htmlspecialchars ($chart, ENT_QUOTES, 'UTF-8');
		$out = '<div class="fh-chart" data-chart="' . $chart . '"></div>';
		if (!$echo) return $out;
		echo $out;
		}

	public static function r ($key, $filter = null, $index = null) {
		$value = isset ($_POST[$key]) ? $_POST[$key] : (isset ($_GET[$key]) ? $_GET[$key] : null);
		if (is_array ($value) && !is_null ($index)) {
			if (is_numeric ($index) && (-1 < $index) && ($index < sizeof ($value))) return $value[$index];
			if (is_string ($index) && ($index == 'last')) return $value[sizeof ($value) - 1];
			}
		return $value;
		}

	public static function m ($from, $to, $subject, $body = '', $attachments = []) {
		if (!class_exists ('PHPMailer'))
			include_once (ABSPATH . WPINC . '/class-phpmailer.php');

		$smtp = get_option (FH_Plugin::PluginSlug . '_smtp', [
			'smtp_email'    => '',
			'smtp_host'     => '',
			'smtp_port'     => '',
			'smtp_security' => 'none',
			'smtp_username' => '',
			'smtp_password' => ''
			]);

		//var_dump ($smtp);

		$mail = new PHPMailer ();
		$mail->IsSMTP ();

		$mail->SMTPDebug	= 0;
		$mail->Host		= $smtp['smtp_host'];
		$mail->Port		= $smtp['smtp_port'];
		$mail->SMTPAuth		= true;
		$mail->Username		= $smtp['smtp_username'];
		$mail->Password		= $smtp['smtp_password'];
		$mail->SMTPSecure	= $smtp['smtp_security'];

		$mail->SetFrom		($smtp['smtp_email'], $from);
		$mail->AddReplyTo	($smtp['smtp_email'], $from);
		if (is_string ($to))
			$mail->AddAddress	($to);
		else
			if (is_array ($to)) {
				foreach ($to as $_to)
					$mail->AddAddress ($_to);
				}

		$mail->Subject		= $subject;
		$mail->MsgHTML		($body);

		if (!empty ($attachments))
			foreach ($attachments as $path => $name) {
				$mail->AddAttachment ($path, $name);
				}

		if (!$mail->send ()) throw new FH_Exception ();
		}

	public static function v ($var, $name = '') {
?>		<pre> <?php
		if (!empty ($name)) echo $name . " = \n";
		var_export ($var);
?>		</pre> <?php
		}

	public static function inp ($type, $key, $value = '', $opts = []) {
		if (empty ($value)) $value = self::r ($key);
		switch ((string) $type) {
			case 'select':
?><select class="form-control select select-sm select-info select-block" data-toggle="select" name="<?php echo $key; ?>"><?php
				if (isset ($opts['interval']) && isset ($opts['interval']['begin']) && isset ($opts['interval']['end'])) {
					$step = isset ($opts['interval']['step']) ? $opts['interval']['step'] : 1;

					if ($opts['interval']['begin'] < $opts['interval']['end']) {
						$begin = $opts['interval']['begin'];
						$end = $opts['interval']['end'];
						}
					else {
						$begin = $opts['interval']['end'];
						$end = $opts['interval']['begin'];
						}
					$interval = [];

					for ($count = $begin; $count <= $end; $count += abs($step))
						$interval[$count] = $count;

					if ($step < 0) array_reverse ($interval);
					}
				else
					$interval = $opts['data'];

				if (!empty ($interval))
					foreach ($interval as $key => $opt) :
?><option value="<?php echo $key; ?>"<?php if ($key == $value) echo ' selected'; ?>><?php echo $opt; ?></option><?php
					endforeach;
?></select><?php

				break;
			case 'slider':
?><div class="ui-slider" data-min="<?php echo $opts['min']; ?>" data-max="<?php echo $opts['max']; ?>"><input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>"/></div><?php
				break;
			case 'file':
?><div class="file-control"><div class="input-group input-group-sm"><input class="form-control input-sm" type="text" value="<?php echo $value; ?>" /><input class="hidden" type="file" name="<?php echo $key; ?>" /><span class="input-group-btn"><a href="#" class="btn btn-sm file-clear"><i class="fui-trash"></i></a><a href="#" class="btn btn-sm file-upload"><i class="fui-clip"></i></a></span></div></div><?php
				break;
			case 'textarea':
?><textarea name="<?php echo $key; ?>" class="form-control" rows="4"><?php echo stripslashes ($value); ?></textarea><?php
				break;
			case 'richtext':
?><textarea name="<?php echo $key; ?>" class="form-control sd-richtext" rows="4"><?php echo stripslashes ($value); ?></textarea><?php
				break;
			case 'switch':
?><div class="bootstrap-switch-square pull-right"><input type="checkbox"<?php if ($value) echo ' checked'; ?> data-toggle="switch" data-on-text="<i class='fui-check'></i>" data-off-text="<i class='fui-cross'></i>" name="<?php echo $key; ?>" /></div><?php
				break;
			default:
				if ($type == 'float' || $type == 'percent')
					$value = sprintf ('%.2f', (float) $value);
				if ($type == 'number' || $type == 'integer')
					$value = (int) $value;

				if (!empty ($unit)) :
?><div class="input-group input-group-sm"><input class="form-control input-sm sd-<?php echo $type; ?>" type="text" name="<?php echo $key; ?>" value="<?php echo $value; ?>" /><div class="input-group-btn btn-group-sm"><span class="btn btn-sm"><?php echo $unit; ?></span></div></div><?php
				else :
?><input class="form-control input-sm" type="text" name="<?php echo $key; ?>" value="<?php echo $value; ?>" /><?php
				endif;
			}
		}

	public static function prg ($error = null, $top = FALSE) {
		$url = $_SERVER['REQUEST_URI'];
		$get = $_GET;
		if ($top)
			$get = isset ($get[self::GET]) ? [self::GET => $get[self::GET]] : [];

		if (!is_null ($error))
			$get['error'] = json_encode ((object) $error);

		if (!isset ($get['error'])) $get['ok'] = 1;

		if (($pos = strpos ($url, '?')) !== FALSE)
			$url = !empty ($get) ? (substr ($url, 0, $pos + 1) . http_build_query ($get)) : substr ($url, 0, $pos);
		else
			$url .= '?' . http_build_query ($get);

		header ('Location: ' . $url, 303);
		exit (1);
		}
	}
?>
