<?php
$object = null;
if (isset ($_GET['id']) && is_numeric ($_GET['id'])) {
	try {
		$object = new FH_User ($_GET['id']);
		}
	catch (FH_Exception $e) {
		}
	}

if (empty ($_POST)) return;

switch ($action) {
	case 'create':
		$data = [
			'user_login'	=> FH_Theme::r ('user_login'),
			'display_name'	=> FH_Theme::r ('display_name'),
			'user_email'	=> FH_Theme::r ('user_email'),
			'phone'		=> FH_Theme::r ('phone'),
			'role'		=> FH_Theme::r ('role'),
			];

		$password = FH_Theme::r ('password');
		$confirmp = FH_Theme::r ('confirm_password');
		if ($password == $confirmp)
			$data['user_pass'] = $password;

		try {
			$object = new FH_User ($data);
			$object->save ();
			$object->set ($data);
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
			'user_login'	=> FH_Theme::r ('user_login'),
			'display_name'	=> FH_Theme::r ('display_name'),
			'user_email'	=> FH_Theme::r ('user_email'),
			'phone'		=> FH_Theme::r ('phone'),
			'role'		=> FH_Theme::r ('role'),
			];
		$password = FH_Theme::r ('password');
		$confirmp = FH_Theme::r ('confirm_password');
		if ($password == $confirmp)
			$data['user_pass'] = $password;

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
