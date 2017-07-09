<?php
/**
 * Core of FH_*
 */

/**
 * GeoImage Class
 *
 * @category
 * @package FoxHunt
 * @subpackage None
 * @copyright Core Security Advisers SRL
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 0.1
 *
 */
class FH_GeoImage extends FH_Model {
	const GET = 'geoimage';
	const PATH = 'geoimages';
	const HASH_ALGO = 'sha256';

	public static $version = '1.0.0';

	public static $human = 'GeoImages';

	public static $scheme = [];

	public static $T = 'geoimages';

	protected static $K = [
		'user_id',
		'geounit_id',
		'taken_stamp',
		'upload_stamp',
		'hash',
		'status',
		'name',
		'path',
		'latitude',
		'longitude'
		];

	protected static $Q = [
		'`id` int(11) NOT NULL AUTO_INCREMENT',
		'`user_id` int(11) NOT NULL DEFAULT 0',
		'`geounit_id` int(11) NOT NULL DEFAULT 0',
		'`taken_stamp` int(11) NOT NULL DEFAULT 0',
		'`upload_stamp` int(11) NOT NULL DEFAULT 0',
		'`hash` varchar(64) NOT NULL DEFAULT \'\'',
		'`status` enum(\'taken\',\'uploaded\',\'approved\',\'deleted\') NOT NULL DEFAULT \'taken\'',
		'`name` varchar(256) NOT NULL DEFAULT \'\'',
		'`path` text NOT NULL',
		'`latitude` float(10,7) NOT NULL DEFAULT 0.0000000',
		'`longitude` float(10,7) NOT NULL DEFAULT 0.0000000',
		'PRIMARY KEY (`id`)',
		'KEY `geounit_id` (`geounit_id`)',
		'KEY `user_id` (`user_id`)',
		'KEY `status` (`status`)'
		];

	public function __construct ($data = null) {
		if (is_array ($data) && is_array($data['path'])) {
			$filename = $data['path']['filename'];
			$tmp_path = $data['path']['tmp_path'];

			try {
				$geounit = new FH_GeoUnit ((int) $data['geounit_id']);
				$data['path'] = $geounit->get ('geopath') . '/' . $filename;
				}
			catch (FH_Exception $e) {
				$data['path'] = 'temp/' . $filename;
				}

			$end_path = self::path ($data['path']);
			$end_dir = dirname ($end_path);

			if (!is_dir ($end_dir) && !@mkdir ($end_dir, 0775, TRUE))
				throw new FH_Exception ();

			if ($tmp_path == 'php://input') {
				if (!@file_put_contents ($end_path, file_get_contents ($tmp_path)))
					throw new FH_Exception ();

				if (FALSE === ($ext = array_search (mime_content_type ($end_path), [
					'jpg'	=> 'image/jpeg'
					], TRUE))) {
					@unlink ($end_path);
					throw new FH_Exception ();
					}
				}
			else
			if (is_uploaded_file ($tmp_path)) {
				if (FALSE == ($ext = array_search (mime_content_type ($end_path), [
					'jpg'	=> 'image/jpeg'
					], true))) {
					@unlink ($tmp_path);
					throw new FH_Exception ();
					}
				if (!@move_uploaded_file ($tmp_path, $end_path))
					throw new FH_Exception ();
				}
			else
				throw new FH_Exception ();

			$data['status'] = 'uploaded';
			$data['hash'] = hash_file (self::HASH_ALGO, $end_path);
			}

		parent::__construct ($data);
		}

	public function get ($key = null, $opts = null) {
		if (is_string ($key)) {
			switch ($key) {
				case 'marker':
					return (object) [
						'id' => $this->get (),
						'position' => (object) [
							'lat' => (float) $this->get ('latitude'),
							'lng' => (float) $this->get ('longitude')
							],
						'title' => $this->get ('name')
						];
					break;
				case 'name':
					$name = $this->data['name'];
					if (strpos ($name, '-') !== FALSE) {
						$pieces = explode ('-', $name);
						foreach ($pieces as $key => $value) $pieces[$key] = ucwords (strtolower ($value));
						$name = implode ('-', $pieces);
						}
					else
						$name = ucwords (strtolower ($name));

					if ($this->data['type'] == 'county') $name = 'Judetul ' . $name;
					if ($this->data['type'] == 'commune') $name = 'Comuna ' . $name;
				
					return $name;
					break;
				}
			}
		return parent::get ($key, $opts);
		}

	public static function path ($path = '') {
		return WP_CONTENT_DIR . '/' . self::PATH . '/' . $path;
		}

	public static function uri ($path = '') {
		return WP_CONTENT_URL . '/' . self::PATH . '/' . $path;
		}
	}
