<?php
/**
 * Core of FH_*
 */

/**
 * User Class
 *
 * @category
 * @package FoxHunt
 * @subpackage None
 * @copyright Core Security Advisers SRL
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 0.1
 *
 */
class FH_User {
	const PREFIX = 'foxhunt_';

	const LOCALE	= '_fh_locale';
	const PHONE	= '_fh_phone';
	const POSITION	= '_fh_position';
	const TOKEN	= '_fh_token';

	public static $T = 'users';

	protected static $K = [
		'user_login',
		'user_pass',
		'user_nicename',
		'user_email',
		'display_name'
		];

	protected static $P_K = [
		'locale',
		'position',
		'phone',
		'token'
		];

	public static $ROLES = [
		'admin'		=> [
			'title'		=> /*T[*/'FoxHunt Administrator'/*]*/,
			'capabilities'	=> [],
			'group_id'	=> -97
			],
		'client'	=> [
			'title'		=> /*T[*/'FoxHunt Client'/*]*/,
			'capabilities'	=> [],
			'group_id'	=> -98
			],
		'agent'		=> [
			'title'		=> /*T[*/'FoxHunt Agent'/*]*/,
			'capabilities'	=> [],
			'group_id'	=> -99
			]
		];

	private $ID;

	private $object;
	private $data;

	public function __construct ($data = null) {
		$this->ID = 0;
		$this->object = null;
		$this->data = null;

		if (is_null ($data)) {
			$user = wp_get_current_user ();
			if ($user->ID) {
				$this->ID = $user->ID;
				$this->object = $user;
				}
			}
		else
		if (is_numeric ($data)) {
			$user = get_userdata ((int) $data);
			$this->ID = (int) $data;
			$this->data = $this->object = $user;
			}
		else
		if (is_array ($data)) {
			$this->data = $data;
			if (isset ($data['id'])) {
				$user = get_userdata ((int) $data['id']);
				if ($user->ID) {
					$this->ID = $user->ID;
					$this->data = $this->object = $user;
					}
				}
			}
		}

	public function get ($key = null, $opts = null) {
		if (is_string ($key)) {
			switch ($key) {
				case 'name':
					if ($this->object instanceof WP_User) {
						if ($this->object->display_name) return $this->object->display_name;
						if ($this->object->user_nicename) return $this->object->user_nicename;
						if ($this->object->user_login) return $this->object->user_login;
						}
					return FALSE;
					break;
				case 'role':
					if ($this->ID == 0) return 'default';
					if ($this->object instanceof WP_User) {
						if (!($this->data instanceof WP_User)) {
							$this->data = get_userdata ($this->object->ID);
							}

						$role = 'default';
						$max = -100;
						if (!empty ($this->data->roles))
							foreach ($this->data->roles as $_role) {
								$_role = str_replace (self::PREFIX, '', $_role);
								if (in_array ($_role, array_keys (self::$ROLES))) {
									if (self::$ROLES[$_role]['group_id'] > $max) {
										$max = self::$ROLES[$_role]['group_id'];
										$role = $_role;
										}
									}
								}
						return $role;
						}
					return 'default';
					break;
				case 'object':
					return $this->object;
					break;
				case 'locale':
					if ($this->ID == 0) return '';
					if ($this->object instanceof WP_User)
						return get_user_meta ($this->object->ID, self::LOCALE, TRUE);
					break;
				case 'phone':
					if ($this->ID == 0) return '';
					if ($this->object instanceof WP_User)
						return get_user_meta ($this->object->ID, self::PHONE, TRUE);
					break;
				case 'position':
					if ($this->ID == 0) return '';
					if ($this->object instanceof WP_User)
						return get_user_meta ($this->object->ID, self::POSITION, TRUE);
					break;
				case 'token':
					if ($this->ID == 0) return '';
					if ($this->object instanceof WP_User)
						return get_user_meta ($this->object->ID, self::TOKEN, TRUE);
					break;
				}
			}
		return is_object ($this->object) ? $this->object->get (is_null ($key) ? 'ID' : $key) : FALSE;
		}

	public function out ($key = null, $opts = null) {
		echo $this->get ($key, $opts);
		}

	public function set ($key = null, $value = null) {
		if ($this->object instanceof WP_User) {
			if (is_string ($key)) {
				switch ($key) {
					case 'user_email':
					case 'user_pass':
					case 'user_nicename':
						if (is_wp_error (wp_update_user (['ID' => $this->ID, $key => $value])))
							throw new FH_Exception;
						break;
					case 'locale':
						$locale = get_user_meta ($this->object->ID, self::LOCALE, TRUE);
						if ($locale !== '')
							update_user_meta ($this->object->ID, self::LOCALE, $value);
						else
							add_user_meta ($this->object->ID, self::LOCALE, $value, TRUE);
						break;
					case 'phone':
						$phone = get_user_meta ($this->object->ID, self::PHONE, TRUE);
						if ($phone !== '')
							update_user_meta ($this->object->ID, self::PHONE, $value);
						else
							add_user_meta ($this->object->ID, self::PHONE, $value, TRUE);
						break;
					case 'position':
						$position = get_user_meta ($this->object->ID, self::POSITION, TRUE);
						if ($position !== '')
							update_user_meta ($this->object->ID, self::POSITION, $value);
						else
							add_user_meta ($this->object->ID, self::POSITION, $value, TRUE);
						break;
					case 'token':
						$token = get_user_meta ($this->object->ID, self::TOKEN, TRUE);
						if ($token !== '')
							update_user_meta ($this->object->ID, self::TOKEN, $value);
						else
							add_user_meta ($this->object->ID, self::TOKEN, $value, TRUE);
						break;
					case 'user_pass':
						wp_set_password ($value, $this->ID);
						break;
					}
				}
			else
			if (is_array ($key)) {
				$keys = array_keys ($key);
				if (in_array ('locale', $keys)) {
					$this->set ('locale', $key['locale']);
					unset ($key['locale']);
					}
				if (in_array ('phone', $keys)) {
					$this->set ('phone', $key['phone']);
					unset ($key['phone']);
					}
				if (in_array ('position', $keys)) {
					$this->set ('position', $key['position']);
					unset ($key['position']);
					}
				if (in_array ('token', $keys)) {
					$this->set ('token', $key['token']);
					unset ($key['token']);
					}
				if (in_array ('user_pass', $keys)) {
					$this->set ('user_pass', $key['user_pass']);
					unset ($key['user_pass']);
					}
				if (isset ($key['role']))
					$key['role'] = self::PREFIX . $key['role'];

				$key['ID'] = $this->ID;

				if (is_wp_error (wp_update_user ($key)))
					throw new FH_Exception;
				}
			}
		}


	public function save () {
		$user_id = username_exists ($this->data['user_login']);
		if ($user_id && email_exists ($this->data['user_email']))
			throw new FH_Exception ();
		$this->ID = wp_create_user ($this->data['user_login'], $this->data['user_pass'], $this->data['user_email']);
		if (!$this->ID)
			throw new FH_Exception ();
		$this->object = get_userdata ($this->ID);
		}

	public function remove () {
		if (!$this->ID)
			throw new FH_Exception ();

		global $wpdb;
		if ($this->object instanceof WP_User) {
			$sql = $wpdb->prepare ('DELETE FROM `' . $wpdb->posts . '` WHERE post_author=%d', $this->ID);
			$wpdb->query ($sql);
			$sql = $wpdb->prepare ('DELETE FROM `' . $wpdb->links . '` WHERE link_owner=%d', $this->ID);
			$wpdb->query ($sql);
			$sql = $wpdb->prepare ('DELETE FROM `' . $wpdb->usermeta . '` WHERE user_id=%d', $this->ID);
			$wpdb->query ($sql);
			$wpdb->delete ($wpdb->users, ['ID' => $this->ID]);
			clean_user_cache ($this->object);
			}
		}

	public function is ($key = null) {
		if (is_null ($key))
			return $this->ID > 0;
		if (is_string ($key) && $key == 'admin')
			return current_user_can ('remove_users');
		return FALSE;
		}

	public static function install ($uninstall = FALSE) {
                /* stub */
                if (!empty (self::$ROLES))
                foreach (self::$ROLES as $role => $options)
                        add_role (self::PREFIX . $role, $options['title'], $options['capabilities']);
		}

	public function __destruct () {
		}
	}
?>
