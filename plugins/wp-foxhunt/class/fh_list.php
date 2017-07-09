<?php
/**
 * Core of FH_*
 */

/**
 * List Objects
 *
 * @category
 * @package FoxHunt
 * @subpackage None
 * @copyright Core Security Advisers SRL
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 0.1
 *
 */
class FH_List {
	private $list;

	public function __construct ($object, $filter = null, $orderby = null) {
		global
			$wpdb,
			$fh_theme;
		$this->list = [];

		if (is_string ($object)) {
			if (strpos ($object, '-') !== FALSE) {
				}
			else
			if (class_exists ($object)) {
				if ($object == 'FH_GeoUnit') {
					$sql = 'SELECT
						a.*,
						IFNULL(SUM(b.status=\'taken\'),0) AS taken,
						IFNULL(SUM(b.status=\'uploaded\'),0) AS uploaded,
						IFNULL(SUM(b.status=\'approved\'),0) AS approved,
						IFNULL(SUM(b.status=\'deleted\'),0) AS deleted
					FROM (SELECT * FROM `' . $wpdb->prefix . $object::$T . '` WHERE ' . (empty ($filter) ? 1 : implode (' AND ', $filter)) . ') a LEFT JOIN `' . $wpdb->prefix . FH_GeoImage::$T . '` b
						on a.id=b.geounit_id
					GROUP BY a.id
					ORDER BY ' . (is_null ($orderby) ? 'a.id' : $orderby);
					$objects = $wpdb->get_results ($sql, ARRAY_A);
					}
				else
				if (property_exists ($object, 'Q')) {
					$sql = 'SELECT
						*
						FROM `' . $wpdb->prefix . $object::$T . '`
						WHERE ' . (empty ($filter) ? 1 : implode (' AND ', $filter)) . '
						ORDER BY ' . (is_null ($orderby) ? 'id' : $orderby);
					$objects = $wpdb->get_results ($sql, ARRAY_A);
					}
				else
				if (property_exists ($object, 'T')) {
					$sql = 'SELECT
						id
						FROM `' . $wpdb->prefix . $object::$T . '`
						WHERE ' . (empty ($filter) ? 1 : implode (' AND ', $filter)) . '
						ORDER BY ' . (is_null ($orderby) ? 'id' : $orderby);
					$objects = $wpdb->get_results ($sql, ARRAY_A);
					}
				}
			}

		if (!empty ($objects))
			foreach ($objects as $object_data) {
				$this->list[$object_data['id']] = new $object ($object_data);
				}
		}

	public function get ($key = null, $opts = null) {
		if (is_string ($key)) {
			switch ($key) {
				case 'sizeof':
				case 'count':
					return sizeof ($this->list);
					break;
				case 'first':
					reset ($this->list);
					return current ($this->list);
					break;
				case 'last':
					if (empty ($this->list)) return null;
					$out = end ($this->list);
					reset ($this->list);
					return $out;
					break;
				case 'select':
					if (empty ($this->list)) return [];
					$out = [];
					foreach ($this->list as $id => $object)
						$out[$id] = $object->get ($opts);
					return $out;
					break;
				}
			}
		return $this->list;
		}

	public function is ($what = null) {
		if (is_string ($what)) {
			switch ($what) {
				case 'empty':
					return empty ($this->list);
					break;
				}
			}
		}

	public function sort ($by = '', $ord = 'asc') {
		if (empty ($this->list))
			$this->get ();
		
		switch ($by) {
			case 'name':
				uasort ($this->list, [$this, '_cmp_onm']);
				break;
			case 'owner':
				uasort ($this->list, [$this, '_cmp_own']);
				break;
			case 'date':
			case 'stamp':
			case 'time':
				uasort ($this->list, [$this, '_cmp_stm']);
				break;
			default:
				uasort ($this->list, [$this, '_cmp_ord']);
			}
		if ($ord == 'desc')
			$this->list = array_reverse ($this->list, TRUE);
		}
	
	private function _cmp_ord ($a, $b) {
		$va = $a->get ('order');
		$vb = $b->get ('order');
		return $va == $vb ? 0 : ($va < $vb ? -1 : 1);
		}

	private function _cmp_stm ($a, $b) {
		$va = $a->get ('stamp');
		$vb = $b->get ('stamp');
		return $va == $vb ? 0 : ($va < $vb ? -1 : 1);
		}

	private function _cmp_onm ($a, $b) {
		$va = strtolower ($a->get ('name'));
		$vb = strtolower ($b->get ('name'));
		return strcmp ($va, $vb);
		}

	private function _cmp_own ($a, $b) {
		$va_id = $a->get ('owner');
		$vb_id = $b->get ('owner');
		$va_ob = new WP_User ((int) $va_id);
		$vb_ob = new WP_User ((int) $vb_id);
		$va = strtolower ($va_ob->first_name . ' ' . $va_ob->last_name);
		$vb = strtolower ($vb_ob->first_name . ' ' . $vb_ob->last_name);
		return strcmp ($va, $vb);
		}
	}
?>
