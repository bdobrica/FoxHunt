<?php
$object = null;
if (isset ($_GET['id']) && is_numeric ($_GET['id'])) {
	try {
		$object = new FH_GeoUnit ($_GET['id']);
		}
	catch (FH_Exception $e) {
		}
	}

if (empty ($_POST)) return;

switch ($action) {
	case 'create':
		$data = [
			'name'		=> FH_Theme::r ('name'),
			'type'		=> FH_Theme::r ('type'),
			'latitude'	=> FH_Theme::r ('latitude'),
			'longitude'	=> FH_Theme::r ('longitude'),
			'siruta'	=> FH_Theme::r ('siruta'),
			'postal_code'	=> FH_Theme::r ('postal_code'),
			'ne_lat'	=> FH_Theme::r ('ne_lat'),
			'ne_long'	=> FH_Theme::r ('ne_long'),
			'sw_lat'	=> FH_Theme::r ('sw_lat'),
			'sw_long'	=> FH_Theme::r ('sw_long')
			];

		try {
			$object = new FH_GeoUnit ($data);
			$object->save ();
			unset ($_GET['action']);
			unset ($_GET['error']);
			}
		catch (FH_Exception $e) {
			}

		break;
	case 'read':
		break;
	case 'update':
		$data = [
			'name'		=> FH_Theme::r ('name'),
			'type'		=> FH_Theme::r ('type'),
			'latitude'	=> FH_Theme::r ('latitude'),
			'longitude'	=> FH_Theme::r ('longitude'),
			'siruta'	=> FH_Theme::r ('siruta'),
			'postal_code'	=> FH_Theme::r ('postal_code'),
			'ne_lat'	=> FH_Theme::r ('ne_lat'),
			'ne_long'	=> FH_Theme::r ('ne_long'),
			'sw_lat'	=> FH_Theme::r ('sw_lat'),
			'sw_long'	=> FH_Theme::r ('sw_long')
			];

		try {
			$object->set ($data);
			unset ($_GET['action']);
			unset ($_GET['error']);
			}
		catch (FH_Exception $e) {
			}
		break;
	case 'delete':
		try {
			$object->remove ();
			unset ($_GET['action']);
			unset ($_GET['error']);
			}
		catch (FH_Exception $e) {
			}
		break;
	default:
		break;
	}

$fh_theme->prg ();
