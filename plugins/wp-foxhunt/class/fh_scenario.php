<?php
class FH_Scenario {
	const ARCHIVE		= 'archive';
	const TEMPORARY		= 'temporary';
	const SCENARIO_DIR	= 'scenarios';
	const SCENARIO_FILE	= 'scenario.php';
	const ASSETS_DIR	= 'assets';
	const GET		= 'scenario';
	const BUFFER		= 1024;
	const EXTENSION		= 'scn';

	private $ID;
	private $path;
	private $data;
	private $meta;

	public static $S = [
		'neutral'	=> /*T[*/'Neutral'/*]*/,
		'thinking'	=> /*T[*/'Thinking'/*]*/,
		'player'	=> /*T[*/'Player'/*]*/
		];

	protected static $K = [
		'name',
		'public',
		'readiness',
		'editable',
		'owner',
		'stamp'
		];

	protected static $M_K = [
		'company_name',
		'company_description',

		'financing_cost',
		'currency',

		'buying_mode',
		'price_weight',
		'adv_budg_weight',
		'paym_term_weight',
		'delivery_weight',
		'features_weight',
		'warranty_weight',

		'negotiation_images',
		'aggressiveness',
		'ask_for_warranty',
		'ask_for_features',
		'score_weight',
		'sweetener',
		'hints',

		'conversation_timer',
		'1st_round_timer',
		'offer_timer',
		'default_delay',
		'presentation_timer',
		'negotiation_answer_timer',
		'negotiation_timer',
		'timeout_image',

		'round0_begin_message',
		'round0_end_message',
		'round1_begin_message',
		'round1_end_message',
		'round2_begin_message',
		'round2_end_message',
		'round3_begin_message',
		'round3_end_message',
		'round4_begin_message',
		'round4_end_message',

		'allow_sending_emails',
		'reusable_questions',
		'enable_purchase',
		'round_3_min_score',
		'round_3_max_score',

		'round_3_email_sender',
		'round_3_email_subject',
		'round_3_email_content',
		'round_3_email_attachment',

		'round_4_email_sender',
		'round_4_email_subject',
		'round_4_email_content',
		'round_4_email_attachment',
		];

	public function __construct ($data = null) {
		if (is_string ($data) && is_dir (($path = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR . DIRECTORY_SEPARATOR . $data))) {
			$head = self::head ($path . DIRECTORY_SEPARATOR . self::SCENARIO_FILE);
			if (!empty ($head)) {
				$this->ID = $data;
				$this->path = $path . DIRECTORY_SEPARATOR . self::SCENARIO_FILE;
				$this->data = $head;
				$this->meta = [];

				$meta = include ($this->path);
				if (is_array ($meta) && !empty ($meta))
					foreach ($meta as $key => $value)
						$this->meta[$key] = $value;
				}
			else
				throw new FH_Exception ();
			}
		else
		if (is_null ($data)) {
			$this->ID = null;
			$this->path = null;
			$this->data = [];
			$this->meta = [];
			}
		else
			throw new FH_Exception ();
		}

	public function set ($key = null, $value = null) {
		if (is_string ($key)) {
			if (in_array ($key, self::$K))
				$this->data[$key] = $value;
			if (in_array ($key, self::$M_K))
				$this->meta[$key] = $value;
			}
		else
		if (is_array ($key)) {
			foreach ($key as $_k => $_v) {
				if (in_array ($_k, self::$K))
					$this->data[$_k] = $_v;
				if (in_array ($_k, self::$M_K))
					$this->meta[$_k] = $_v;
				}
			}
		else
		if ($key instanceof FH_Scenario) {
			$copy = $key->get ('copy');

			if (!empty ($value))
				$copy['name'] = $value;

			foreach ($copy as $_k => $_v) {
				if (in_array ($_k, self::$K))
					$this->data[$_k] = $_v;
				if (in_array ($_k, self::$M_K))
					$this->meta[$_k] = $_v;
				}
			}

		if (is_null ($this->ID) && is_null ($this->path) && isset ($this->data['name'])) {
			$path = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR . DIRECTORY_SEPARATOR . self::slug ($this->data['name']);

			$count = 0;
			$try_name = $this->data['name'];
			while (is_dir ($path)) {
				$try_name = $this->data['name'] . ' (' . (++$count) . ')';
				$path = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR . DIRECTORY_SEPARATOR . self::slug ($try_name);
				}
			$this->data['name'] = $try_name;
			$this->ID = self::slug ($this->data['name']);

			$this->data['owner'] = get_current_user_id ();
			$this->data['stamp'] = time ();

			if (!@mkdir ($path, 0755, TRUE))
				throw new FH_Exception ();
			$this->path = $path . DIRECTORY_SEPARATOR . self::SCENARIO_FILE;

			if ($key instanceof FH_Scenario) {
				$old_dir = $key->get ('path');
				$new_dir = $path;

				$entries = scandir ($old_dir);
				foreach ($entries as $entry) {
					if ($entry[0] == '.') continue;
					if (is_dir ($old_dir . DIRECTORY_SEPARATOR . $entry)) {
						if (!@mkdir ($new_dir . DIRECTORY_SEPARATOR . $entry, 0755, TRUE))
							throw new FH_Exception ();
						$files = scandir ($old_dir . DIRECTORY_SEPARATOR . $entry);
						foreach ($files as $file) {
							if ($file[0] == '.') continue;
							if (
								file_exists ($old_dir . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . $file) &&
								!@copy ($old_dir . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . $file,
									$new_dir . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . $file)) {
									throw new FH_Exception ();
									}
							}
						}
					}

				self::fix_paths ($new_dir, $this->data, $this->meta);
				}
			}
		$this->save ();
		}

	public function get ($key = null, $opts = null) {
		global $fh_game;

		if (is_null($key)) return $this->ID;
		$slug = static::slug ($key);
		if ($slug == 'owner_name') {
			$owner_id = isset ($this->data['owner']) ? $this->data['owner'] : 1;
			$user = new WP_User ((int) $owner_id);
			return $user->first_name . ' ' . $user->last_name;
			}
		if ($slug == 'last_update') {
			$stamp = isset ($this->data['stamp']) ? $this->data['stamp'] : 0;
			return date ('d-m-Y, H:i', $stamp);
			}
		if ($slug == 'self')
			return get_class ($this) . '-' . $this->ID;
		if ($slug == 'keys')
			return static::$K;
		if ($slug == 'class')
			return get_class ($this);
		if ($slug == 'path')
			return dirname ($this->path);
		if ($slug == 'assets_path')
			return dirname ($this->path) . DIRECTORY_SEPARATOR . self::ASSETS_DIR;
		if ($slug == 'url') {
			$url = $_SERVER['REQUEST_URI'];

			$get = $_GET;
			$get[static::GET] = $this->ID;
			if (isset ($opts) && is_string ($opts) && in_array ($opts, FH_Theme::$A))
				$get[FH_Theme::ACTION] = $opts;

			if (($pos = strpos ($url, '?')) !== FALSE)
				$url = substr ($url, 0, $pos + 1) . http_build_query ($get);
			else
				$url .= '?' . http_build_query ($get);

			return $url;
			}
		if ($slug == 'copy')
			return array_merge ($this->data, $this->meta);
		if ($slug == 'meta')
			return $this->meta;
		if (strpos ($slug, 'negotiation_image_') !== FALSE) {
			$slug = substr ($slug, 18);
			$image = in_array ($slug, array_keys (self::$S)) ? (isset ($this->meta['negotiation_images'][$slug]) ? $this->meta['negotiation_images'][$slug] : null) : null;
			if (is_null ($image)) return null;
			return plugins_url (str_replace (dirname (__DIR__), '', $image), __DIR__);
			}
		if ((strpos ($slug, 'message') !== FALSE) && in_array ($slug, self::$M_K)) {
			if (!isset ($this->meta[$slug])) return null;
			return stripslashes (trim (implode ('', explode ('\\', $this->meta[$slug]))));
			}
		if (in_array ($slug, self::$K))
			return isset ($this->data[$slug]) ? $this->data[$slug] : null;
		if (in_array ($slug, self::$M_K)) {
			$variables = (isset ($fh_game) && $fh_game instanceof FH_Game) ? $fh_game->get ('variables') : [];
			$out = isset ($this->meta[$slug]) ? $this->meta[$slug] : null;
			if (!empty ($variables) && isset ($variables[$slug]))
				$out = $variables[$slug];
			return $out;
			}

		return $this->ID;
		}

	public function out ($key = null, $opts = null, $callback = null) {
		$content = $this->get ($key, $opts);
		if (!is_null ($callback) && is_callable ($callback))
			$content = call_user_func ($callback, $content);
		echo $content;
		}

	public function save () {
		if (empty ($this->path)) throw new FH_Exception ();

		$content = '<' . '?php' . "\n";
		$content .= self::head ($this->data, FALSE);
		$content .= 'return ' . var_export ($this->meta, TRUE) . ';' . "\n";
		$content .= '?' . '>';

		if (($fh = fopen ($this->path, 'w+')) === FALSE) throw new FH_Exception ();
		fwrite ($fh, $content);
		fclose ($fh);

		return TRUE;
		}

	public function is ($what = null) {
		if (is_string ($what)) {
			switch ($what) {
				case 'mine':
					return get_current_user_id() == $this->data['owner'];
					break;
				case 'public':
					return $this->data['public'] ? TRUE : FALSE;
					break;
				case 'in_use':
					$games = new FH_List ('FH_Game', [ sprintf ('scenario="%s"', $this->ID), sprintf ('state<%d', FH_Game::END_GAME), sprintf ('owner<>%d', get_current_user_id ()) ]);
					return $games->is ('empty');
					break;
				case 'editable':
				case 'writable':
					return ($this->is ('mine') || $this->is ('public')) && !$this->is ('in_use');
					break;
				case 'locked':
				case 'readonly':
					return !$this->is ('editable');
					break;
				default:
					return TRUE;
				}
			}
		return TRUE;
		}

	public function export ($echo = TRUE) {
		$path = dirname ($this->path);
		$zip = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR . DIRECTORY_SEPARATOR . self::ARCHIVE . DIRECTORY_SEPARATOR . $this->ID . '.tar';
		$scn = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR . DIRECTORY_SEPARATOR . self::ARCHIVE . DIRECTORY_SEPARATOR . $this->ID . '.' . self::EXTENSION;

		$archive = new ZipArchive ();
		$archive->open ($zip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

		$files = new RecursiveIteratorIterator (
				new RecursiveDirectoryIterator ($path),
				RecursiveIteratorIterator::LEAVES_ONLY
				);

		foreach ($files as $name => $file) {
			if (!$file->isDir ()) {
				$file_path = $file->getRealPath ();
				$relf_path = substr ($file_path, strlen ($path) + 1);
				$archive->addFile ($file_path, $relf_path);
				}
			}

		$archive->close ();

		if (!@rename ($zip, $scn))
			throw new FH_Exception ();

		if ($echo) {
			header ('Content-Description: File Transfer');
			header ('Content-Type: application/octet-stream');
			header ('Content-Disposition: attachment; filename="' . basename($scn) . '"');
			header ('Expires: 0');
			header ('Cache-Control: must-revalidate');
			header ('Pragma: public');
			header ('Content-Length: ' . filesize($scn));
			readfile ($scn);
			exit (0);
			}

		return $scn;
		}

	public function touch () {
		$this->data['stamp'] = time ();
		$this->save ();
		}

	public function delete () {
		if (!file_exists ($this->path))
			throw new FH_Exception ();

		$stack = [];
		$folders = [ dirname ($this->path) ];

		while (!is_null($folder = array_pop ($folders))) {
			if (is_dir ($folder)) {
				if (($fh = opendir ($folder))) {
					array_unshift ($stack, $folder);
					while (($file = readdir ($fh)) !== FALSE) {
						if ($file[0] == '.') continue;
						if (is_dir ($folder . DIRECTORY_SEPARATOR . $file))
							$folders[] = $folder . DIRECTORY_SEPARATOR . $file;
						else
							@unlink ($folder . DIRECTORY_SEPARATOR . $file);
						}
					closedir ($fh);
					}
				}
			}

		foreach ($stack as $folder)
			@rmdir ($folder);
		
		return TRUE;
		}

	public static function head ($file, $read = TRUE) {
		$out = null;
		if ($read) {
			if (!file_exists ($file)) return [];
			$head = [];
			$state = 0;
			if (($fh = fopen ($file, 'r')) === FALSE) continue;
			
			while ((($line = fgets ($fh, self::BUFFER)) !== FALSE) && ($state < 2)) {
				$line = trim ($line);
				if (strpos ($line, '/*') === 0) { $state = 1; continue; }
				if (strpos ($line, '*/') === 0) break;
				if ($state < 1) continue;
				list ($key, $value) = explode (':', $line);

				$key = str_replace (' ', '_', trim (strtolower ($key)));
				$value = trim ($value);

				if (strtolower ($value) == 'false')
					$value = FALSE;
				if (strtolower ($value) == 'true')
					$value = TRUE;

				$head[$key] = $value;
				}

			fclose ($fh);

			if (empty ($head)) return [];

			$out = [];
			foreach ($head as $key => $value)
				if (in_array ($key, self::$K))
					$out[$key] = $value;

			}
		else {
			$out = '/*' . "\n";
			foreach (self::$K as $key)
				if (isset ($file[$key])) {
					$value = $file[$key];
					if ($value === TRUE)
						$value = 'true';
					if ($value === FALSE)
						$value = 'false';
					$out .= self::slug ($key, TRUE) . ': ' . $value . "\n";
					}
			$out .= "*/\n";
			}
		return $out;
		}

	public static function scan ($path = null, $filter = null) {
		$search_path = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR;
		
		if (!is_dir ($search_path)) return [];
		if (($dh = opendir ($search_path)) === FALSE) return [];
	
		$out = [];

		while (($dir = readdir ($dh)) !== FALSE) {
			if ($dir[0] == '.') continue;
			$path = $search_path . DIRECTORY_SEPARATOR . $dir;
			if (!is_dir ($path)) continue;
			$file = $path . DIRECTORY_SEPARATOR . self::SCENARIO_FILE;
			if (!file_exists ($file)) continue;
	
			$head = self::head ($file);
			if (empty ($head)) continue;

			if (!is_null ($filter)) {
				$prepare_head = [];
				foreach ($head as $key => $value) {
					switch ($key) {
						case 'owner':
							$owner = new WP_User ((int) $value);
							$prepare_head[$key] = strtolower ($owner->first_name . ' ' . $owner->last_name);
							break;
						case 'stamp':
							$prepare_head[$key] = date ('d-m-Y, H:i', $value);
							break;
						default:
							$prepare_head[$key] = strtolower ($value);
						}
					}
				$filtered = FALSE;
				if (is_string ($filter)) {
					$filter = strtolower ($filter);
					foreach ($prepare_head as $key => $value)
						$filtered = strpos ($value, $filter) !== FALSE ? TRUE : $filtered;
					}
				if (!$filtered) continue;
				}

			$out[$dir] = $head;
			}

		closedir ($dh);

		return $out;
		}

	public static function slug ($key, $reverse = FALSE) {
		if ($reverse)
			return ucwords (str_replace ('_', ' ', $key));
		else
			return trim (preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($key))), '_');
		}

	public static function open_archive ($path) {
		$stamp = date ('dmYHis');
		$destination = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR . DIRECTORY_SEPARATOR . self::TEMPORARY . DIRECTORY_SEPARATOR . $stamp . DIRECTORY_SEPARATOR;

		if (!@mkdir ($destination, 0755, TRUE))
			throw new FH_Exception ();

		$archive = new ZipArchive ();
		if ($archive->open ($path) !== TRUE)
			throw new FH_Exception ();
		if ($archive->extractTo ($destination) !== TRUE)
			throw new FH_Exception ();
		$archive->close ();

		return $stamp;
		}

	public static function temp_header ($stamp) {
		$path = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR . DIRECTORY_SEPARATOR . self::TEMPORARY . DIRECTORY_SEPARATOR . $stamp . DIRECTORY_SEPARATOR . self::SCENARIO_FILE;
		return self::head ($path);
		}

	public static function fix_paths ($path, $head = null, $meta = null) {
		if (!is_dir ($path))
			throw new FH_Exception ();

		$file = $path . DIRECTORY_SEPARATOR . self::SCENARIO_FILE;
		$assets = $path . DIRECTORY_SEPARATOR . self::ASSETS_DIR;

		if (is_null ($head)) {
			if (!file_exists ($file))
				throw new FH_Exception ();

			$head = self::head ($file);
			if (empty ($head))
				throw new FH_Exception ();
			}

		if (is_null ($meta))
			$meta = include ($file);

		if (isset ($meta['negotiation_images']) && !empty ($meta['negotiation_images'])) {
			foreach ($meta['negotiation_images'] as $key => $value)
				$meta['negotiation_images'][$key] = $assets . DIRECTORY_SEPARATOR . basename ($value);
			}
		if (isset ($meta['round_3_email_attachment']) && !empty ($meta['round_3_email_attachment'])) {
			$attachment = unserialize ($meta['round_3_email_attachment']);
			if (isset ($attachment['file']) && !empty ($attachment['file']))
				$attachment['file'] = $assets . DIRECTORY_SEPARATOR . basename ($attachment['file']);
			$meta['round_3_email_attachment'] = serialize ($attachment);
			}

		$content = '<' . '?php' . "\n";
		$content .= self::head ($head, FALSE);
		$content .= 'return ' . var_export ($meta, TRUE) . ';' . "\n";
		$content .= '?' . '>';

		if (($fh = fopen ($file, 'w+')) === FALSE) throw new FH_Exception ();
		fwrite ($fh, $content);
		fclose ($fh);

		$search_path = $path . DIRECTORY_SEPARATOR . FH_Character::$T;
		if (!is_dir ($search_path)) throw new FH_Exception ();
		if (($dh = opendir ($search_path)) === FALSE) throw new FH_Exception ();
	
		while (($file = readdir ($dh)) !== FALSE) {
			if ($file[0] == '.') continue;
			if (strpos ($file, '.php') === FALSE) continue;

			$file = $search_path . DIRECTORY_SEPARATOR . $file;
			if (is_dir ($file)) continue;

			$meta = include ($file);
			if (!isset ($meta['images']) || empty ($meta['images'])) continue;

			foreach ($meta['images'] as $key => $value)
				$meta['images'][$key] = $assets . DIRECTORY_SEPARATOR . basename ($value);

			$content = '<' . '?php' . "\n";
			$content .= 'return ' . var_export ($meta, TRUE) . ';' . "\n";
			$content .= '?' . '>';
			
			if (($fh = fopen ($file, 'w+')) === FALSE) throw new FH_Exception ();
			fwrite ($fh, $content);
			fclose ($fh);
			}
		closedir ($dh);
		}

	public static function import ($stamp, $name) {
		$try_name = $name;
		$count = 0;
		$source = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR . DIRECTORY_SEPARATOR . self::TEMPORARY . DIRECTORY_SEPARATOR . $stamp;
		$destination = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR . DIRECTORY_SEPARATOR . self::slug ($try_name);

		if (!is_dir ($source))
			throw new FH_Exception ();

		while (is_dir ($destination)) {
			$try_name = $name . ' (' . (++$count) . ')';
			$destination = dirname (__DIR__) . DIRECTORY_SEPARATOR . self::SCENARIO_DIR . DIRECTORY_SEPARATOR . self::slug ($try_name);
			}

		$name = $try_name;

		if (!@rename ($source, $destination))
			throw new FH_Exception ();

		self::fix_paths ($destination, [
			'name'		=> $name,
			'public'	=> FALSE,
			'owner'		=> get_current_user_id (),
			'stamp'		=> time ()
			]);
		}
	}
?>
