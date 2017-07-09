<?php
/*
Name: Users
Order: 1
*/
$user_types = [];
foreach (FH_User::$ROLES as $role_slug => $role_data)
	$user_types[$role_slug] = $role_data['title'];
?>
<div class="fh-rounded fh-translucent fh-padded">
<?php switch ($action) :
/**	CREATE : */
	case 'create': ?>
	<div class="row">
		<div class="col-sm-12">
			<form action="" method="post">
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Login'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'user_login'); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Name'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'display_name'); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'E-Mail'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'user_email'); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Phone'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'phone'); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Type'/*]*/); ?>:</label><?php FH_Theme::inp ('select', 'role', '', ['data' => $user_types]); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Password'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'password'); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Confirm Password'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'confirm_password'); ?></div>
				<div class="row">
					<div class="col-sm-6">
					</div>
					<div class="col-sm-6 text-right">
						<a href="<?php $fh_theme->out ('url', []); ?>" class="btn btn-wide btn-danger"><i class="fui-cross"></i>&nbsp;Cancel</a>
						<button class="btn btn-wide btn-primary"><i class="fui-check"></i>&nbsp;Add User</button>
					</div>
				</div>
			</form>
		</div>
	</div>
<?php		break;
/** END CREATE:
	READ: */
	case 'read':
		break;
/** END READ:
	UPDATE: */
	case 'update': ?>
	<div class="row">
		<div class="col-sm-12">
			<form action="" method="post">
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Login'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'user_login', $object->get ('user_login')); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Name'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'display_name', $object->get ('display_name')); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'E-Mail'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'user_email', $object->get ('user_email')); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Phone'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'phone', $object->get ('phone')); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Type'/*]*/); ?>:</label><?php FH_Theme::inp ('select', 'role', $object->get ('role'), ['data' => $user_types]); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Password'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'password'); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Confirm Password'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'confirm_password'); ?></div>
				<div class="row">
					<div class="col-sm-6">
					</div>
					<div class="col-sm-6 text-right">
						<a href="<?php $fh_theme->out ('url', []); ?>" class="btn btn-wide btn-danger"><i class="fui-cross"></i>&nbsp;Cancel</a>
						<button class="btn btn-wide btn-primary"><i class="fui-check"></i>&nbsp;Update User</button>
					</div>
				</div>
			</form>
		</div>
	</div>
<?php		break;
/** END UPDATE:
	DELETE: */
	case 'delete': ?>
	<div class="row">
		<div class="col-sm-12">
			<form action="" method="post">
				<div class="row">
					<div class="col-sm-3">
					</div>
					<div class="col-sm-6">
						<p><?php FH_Theme::_e (/*T[*/'Are you sure you want to delete this user?'/*]*/); ?></p>
						<div class="row"><div class="col-sm-3"><?php FH_Theme::_e (/*T[*/'Name'/*]*/); ?>:</div><div class="col-sm-9"><?php $object->out ('name'); ?></div></div>
						<div class="row"><div class="col-sm-3"><?php FH_Theme::_e (/*T[*/'Email'/*]*/); ?>:</div><div class="col-sm-9"><?php $object->out ('user_email'); ?></div></div>
						<div class="row"><div class="col-sm-3"><?php FH_Theme::_e (/*T[*/'Phone'/*]*/); ?>:</div><div class="col-sm-9"><?php $object->out ('phone'); ?></div></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
					</div>
					<div class="col-sm-6 text-right">
						<a href="<?php $fh_theme->out ('url', []); ?>" class="btn btn-wide btn-danger"><i class="fui-cross"></i>&nbsp;Cancel</a>
						<button name="delete" class="btn btn-wide btn-primary"><i class="fui-check"></i>&nbsp;Delete User</button>
					</div>
				</div>
			</form>
		</div>
	</div>
<?php		break;
/** END DELETE:
	LIST: */
	default :
		$users = new FH_List ('FH_User', ['ID>1']);
?>
	<div class="row">
		<div class="col-sm-12 text-right">
			<a href="<?php $fh_theme->out ('url', ['action' => 'create']); ?>" class="btn btn-sm btn-primary"><i class="fui-plus"></i>&nbsp;<?php FH_Theme::_e (/*T[*/'New User'/*]*/); ?></a>
		</div>
	</div>
	<div class="row">
<?php		if ($users->is ('empty')) : ?>
<?php		else : ?>
		<div class="table-responsive">
			<table class="table table-condensed table-striped">
				<thead>
					<tr>
						<th><?php FH_Theme::_e (/*T[*/'No.'/*]*/); ?></th>
						<th><?php FH_Theme::_e (/*T[*/'Name'/*]*/); ?></th>
						<th><?php FH_Theme::_e (/*T[*/'Login'/*]*/); ?></th>
						<th><?php FH_Theme::_e (/*T[*/'Type'/*]*/); ?></th>
						<th class="text-right"><?php FH_Theme::_e (/*T[*/'Actions'/*]*/); ?></th>
					</tr>
				</thead>
				<tbody>
<?php			$c = 1;
			foreach ($users->get () as $user) : ?> 
					<tr>
						<td><?php echo $c++; ?>.</td>
						<td><?php $user->out ('name'); ?></td>
						<td><?php $user->out ('user_login'); ?></td>
						<td><?php $user->out ('role'); ?></td>
						<td class="text-right">
							<a class="btn btn-sm btn-info" href="<?php $fh_theme->out ('url', [ 'id' => $user->get (), 'action' => 'update' ]); ?>"><i class="fui-new"></i></a>
							<a class="btn btn-sm btn-danger" href="<?php $fh_theme->out ('url', [ 'id' => $user->get (), 'action' => 'delete' ]); ?>"><i class="fui-trash"></i></a>
						</td>
					</tr>
<?php			endforeach; ?>
				</tbody>
			</table>
		</div>
<?php		endif; ?>
	</div>
<?php	break;
endswitch; ?>
</div>
