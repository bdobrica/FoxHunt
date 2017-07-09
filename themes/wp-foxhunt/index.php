<?php
ini_set ('display_errors', 'on');
$header_file = $fh_theme->get ('dir', 'request::header');
//FH_Theme::v ($header_file, 'header_file');

$fh_user = $fh_theme->get ('user');
$user_role = $fh_theme->get ('user', 'role');

$action = '';
if (isset ($_GET['action']) && in_array ($_GET['action'], [
	'create',
	'read',
	'update',
	'delete'
	])) $action = $_GET['action'];

if (file_exists ($header_file))
	include ($header_file);

get_header();
?>
<div class="container <?php echo !empty ($user_role) ? 'sd-' . $user_role : ''; ?>">
<?php
if ($user_role != 'default'):
	$fh_theme->render ('menu');
?>
	<div class="sd-rounded sd-padded sd-translucent">
		<div class="row">
			<div class="col-lg-6">
				<?php $fh_theme->render ('breadcrumbs'); ?>
			</div>
			<div class="col-lg-6">
			</div>
		</div>
<?php
endif;

$page_file = $fh_theme->get ('dir', 'request::pages');
//FH_Theme::v ($page_file, 'page_file');
if (file_exists ($page_file)) :
	include ($page_file);
endif;
get_footer();
?>
