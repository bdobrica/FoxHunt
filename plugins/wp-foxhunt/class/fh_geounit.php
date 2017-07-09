<?php
/**
 * Core of FH_*
 */

/**
 * GeoUnit Class
 *
 * @category
 * @package FoxHunt
 * @subpackage None
 * @copyright Core Security Advisers SRL
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 0.1
 *
 */
class FH_GeoUnit extends FH_Model {
	const GET = 'geounit';
	const ZOOM_COUNTRY	= 7;
	const ZOOM_COUNTY	= 10;
	const ZOOM_CITY		= 14;
	const MIN_GEOIMAGES	= 5;

	public static $version = '1.0.0';

	public static $human = 'GeoUnit';

	public static $scheme = [];

	public static $T = 'geounits';

	protected static $K = [
		'parent_id',
		'county_id',
		'name',
		'type',
		'medium',
		'siruta',
		'postal_code',
		'latitude',
		'longitude',
		'ne_lat',
		'ne_long',
		'sw_lat',
		'sw_long'
		];

	protected static $P_K = [
		'taken',
		'uploaded',
		'approved',
		'deleted'
		];

	protected static $Q = [
		'`id` int(11) NOT NULL AUTO_INCREMENT',
		'`parent_id` int(11) NOT NULL DEFAULT 0',
		'`county_id` int(11) NOT NULL DEFAULT 0',
		'`name` varchar(128) NOT NULL DEFAULT \'\'',
		'`type` enum(\'village\',\'commune\',\'basecity\',\'city\',\'sector\',\'county\') NOT NULL DEFAULT \'city\'',
		'`medium` enum(\'rural\',\'urban\') DEFAULT \'urban\'',
		'`siruta` int(6) NOT NULL DEFAULT 0',
		'`postal_code` int(6) NOT NULL DEFAULT 0',
		'`latitude` float(10,7) NOT NULL DEFAULT 0.0000000',
		'`longitude` float(10,7) NOT NULL DEFAULT 0.0000000',
		'`ne_lat` float(10,7) NOT NULL DEFAULT 0.0000000',
		'`ne_long` float(10,7) NOT NULL DEFAULT 0.0000000',
		'`sw_lat` float(10,7) NOT NULL DEFAULT 0.0000000',
		'`sw_long` float(10,7) NOT NULL DEFAULT 0.0000000',
		'PRIMARY KEY (`id`)',
		'KEY `parent_id` (`parent_id`)',
		'KEY `type` (`type`)',
		'KEY `medium` (`medium`)',
		'KEY `county_id` (`county_id`)'
		];

	public static $TYPES = [
		'county'	=> /*T[*/'Judet'/*]*/,
		'city'		=> /*T[*/'Oras'/*]*/,
		'commune'	=> /*T[*/'Comuna'/*]*/,
		'sector'	=> /*T[*/'Sector'/*]*/,
		'basecity'	=> /*T[*/'Localitate Componenta'/*]*/,
		'village'	=> /*T[*/'Sat'/*]*/,
		];

	public function get ($key = null, $opts = null) {
		if (is_string ($key)) {
			switch ($key) {
				case 'types':
					return self::$TYPES;
					break;
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
					return self::name ($this->data['name'], $this->data['type']);
					break;
				case 'geopath':
					global $wpdb;
					$parent_id = $this->data['parent_id'];
					$path = $this->data['name'];
					while ($parent_id) {
						$sql = $wpdb->prepare ('SELECT name,parent_id FROM `' . $wpdb->prefix . self::$T . '` WHERE id=%d', $parent_id);
						$parent_geounit = $wpdb->get_row ($sql);
						if (!is_null ($parent_geounit)) {
							$path = $parent_geounit->name . '/' . $path;
							$parent_id = $parent_geounit->parent_id;
							}
						else
							$parent_id = 0;
						}
					return str_replace ([' ', '-'], '_', $path);
					break;
				case 'geoimages':
					global $wpdb;

					$out = [];
					switch ($this->data['type']) {
						case 'county':
							$out[$this->data['id']] = (object) [
								'id'    => $this->data['id'],
								'name'  => $this->get ('name'),
								'type'  => $this->data['type'],
								'children' => []
								];

							$sql = $wpdb->prepare ('SELECT id,name,type FROM `' . $wpdb->prefix . self::$T . '` WHERE parent_id=%d ORDER BY name', $this->data['id']);
							$results = $wpdb->get_results ($sql);
							if (!empty ($results))
								foreach ($results as $result)
									$out[$this->data['id']]->children[$result->id] = (object) [
										'id'	=> $result->id,
										'name'	=> self::name ($result->name, $result->type),
										'type'	=> $result->type,
										'children' => []
										];


							$sql = $wpdb->prepare ('SELECT
								b.id,
								b.taken_stamp,
								b.upload_stamp,
								b.status,
								b.latitude,
								b.longitude,
								b.path,
								a.id as geounit_id,
								a.name as geounit_name,
								a.type as geounit_type,
								a.parent_id as geounit_parent,
								a.county_id as geounit_county
							FROM (SELECT * FROM `' . $wpdb->prefix . self::$T . '` WHERE county_id=%d) a LEFT JOIN `' . $wpdb->prefix . FH_GeoImage::$T . '` b
								ON a.id=b.geounit_id
							ORDER BY a.name, b.id', $this->data['id']);
							$results = $wpdb->get_results ($sql);

							if (!empty ($results))
								foreach ($results as $result) {
									if (!isset ($out[$result->geounit_parent]->children[$result->geounit_id]))
										$out[$this->data['id']]->children[$result->geounit_parent]->children[$result->geounit_id] = (object) [
											'id'	=> $result->geounit_id,
											'name'	=> self::name ($result->geounit_name, $result->geounit_type),
											'type'	=> $result->geounit_type,
											'required' => self::MIN_GEOIMAGES,
											'images' => []
											];
									if (is_null ($result->id)) continue;
									$out[$this->data['id']]->children[$result->geounit_parent]->children[$result->geounit_id]->images[] = (object) [
											'id' => $result->id,
											'ts'	=> date ('d-m-Y H:i:s', $result->taken_stamp),
											'us' => date ('d-m-Y H:i:s', $result->upload_stamp),
											'status' => $result->status,
											'lat' => $result->latitude,
											'lng' => $result->longitude,
											'path' => $result->path
											];
									}
							break;
						case 'city':
						case 'commune':
							$out[$this->data['id']] = (object) [
								'id'    => $this->data['id'],
								'name'  => $this->get ('name'),
								'type'  => $this->data['type'],
								'children' => []
								];

							$sql = $wpdb->prepare ('SELECT
								b.id,
								b.taken_stamp,
								b.upload_stamp,
								b.status,
								b.latitude,
								b.longitude,
								b.path,
								a.id as geounit_id,
								a.name as geounit_name,
								a.type as geounit_type,
								a.parent_id as geounit_parent,
								a.county_id as geounit_county
							FROM (SELECT * FROM `' . $wpdb->prefix . self::$T . '` WHERE parent_id=%d) a LEFT JOIN `' . $wpdb->prefix . FH_GeoImage::$T . '` b
								ON a.id=b.geounit_id
							ORDER BY a.name, b.id', $this->data['id']);
							$results = $wpdb->get_results ($sql);

							if (!empty ($results))
								foreach ($results as $result) {
									if (!isset ($out[$this->data['id']]->children[$result->geounit_id]))
										$out[$this->data['id']]->children[$result->geounit_id] = (object) [
											'id'	=> $result->geounit_id,
											'name'	=> self::name ($result->geounit_name, $result->geounit_type),
											'type'	=> $result->geounit_type,
											'required' => self::MIN_GEOIMAGES,
											'images' => []
											];
									if (is_null ($result->id)) continue;
									$out[$this->data['id']]->children[$result->geounit_id]->images[] = (object) [
											'id' => $result->id,
											'ts'	=> date ('d-m-Y H:i:s', $result->taken_stamp),
											'us' => date ('d-m-Y H:i:s', $result->upload_stamp),
											'status' => $result->status,
											'lat' => $result->latitude,
											'lng' => $result->longitude,
											'path' => $result->path
											];
									}
							break;
						case 'sector':
						case 'basecity':
						case 'village':
						default:
							$out[$this->data['id']] = (object) [
								'id'    => $this->data['id'],
								'name'  => $this->get ('name'),
								'type'  => $this->data['type'],
								'required' => self::MIN_GEOIMAGES,
								'images' => []
								];

							$sql = $wpdb->prepare ('SELECT * FROM `' . $wpdb->prefix . FH_GeoImage::$T . '` WHERE geounit_id=%d ORDER BY id', $this->data['parent_id']);
							$results = $wpdb->get_results ($sql);

							if (!empty ($results))
								foreach ($results as $result) {
									if (is_null ($result->id)) continue;
									$out[$this->data['id']]->images[] = (object) [
											'id' => $result->id,
											'ts'	=> date ('d-m-Y H:i:s', $result->taken_stamp),
											'us' => date ('d-m-Y H:i:s', $result->upload_stamp),
											'status' => $result->status,
											'lat' => $result->latitude,
											'lng' => $result->longitude,
											'path' => $result->path
											];
									}
							break;
						}
					return $out;
					break;
				}
			}
		return parent::get ($key, $opts);
		}

	public static function name ($name, $type = '') {
		if (strpos ($name, '-') !== FALSE) {
			$pieces = explode ('-', $name);
			foreach ($pieces as $key => $value) $pieces[$key] = ucwords (strtolower ($value));
			$name = implode ('-', $pieces);
			}
		else
			$name = ucwords (strtolower ($name));

		if ($type == 'county') $name = 'Judetul ' . $name;
		if ($type == 'commune') $name = 'Comuna ' . $name;
	
		return $name;
		}
	}
