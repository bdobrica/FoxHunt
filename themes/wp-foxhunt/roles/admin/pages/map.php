<?php
/*
Name: Map
Order: 2
*/
$users = new FH_List ('FH_User', ['ID>1']);
$users_select = $users->get ('select', 'name');
?>
<div class="fh-rounded fh-translucent fh-padded">
<div class="row">
<div class="fh-map">
</div>
</div>
</div>

<div class="create-image modal">
	<div class="modal-dialog">
		<form class="modal-content">
			<input type="hidden" value="" name="geounit_id">
			<div class="modal-header">
				<button type="button" class="close fui-cross" data-dismiss="modal" aria-hidden="true"></button>
				<h4 class="modal-title"><?php FH_Theme::_e (/*T[*/'Add New Image'/*]*/); ?></h4>
			</div>
			<div class="modal-body">
				<div class="form-group"><img src="" class="fh-image img-rounded"><?php FH_Theme::inp ('file', 'file'); ?></div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Latitude'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'latitude'); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Longitude'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'longitude'); ?></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Date'/*]*/); ?>:</label><?php FH_Theme::inp ('date', 'date'); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Time'/*]*/); ?>:</label><?php FH_Theme::inp ('time', 'time'); ?></div>
					</div>
				</div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'User'/*]*/); ?>:</label><?php FH_Theme::inp ('select', 'user_id', $fh_user->get(), [ 'data' => $users_select ]); ?></div>
			</div>
			<div class="modal-footer">
				<a href="#" class="btn btn-wide btn-danger" data-dismiss="modal"><i class="fui-cross"></i>&nbsp;Cancel</a>
				<a href="#" class="btn btn-wide btn-primary fh-upload"><i class="fui-check"></i>&nbsp;Add Image</a>
			</div>
		</form>
	</div>
</div>
<div class="update-image modal">
	<div class="modal-dialog">
		<form class="modal-content">
			<input type="hidden" value="" name="id">
			<div class="modal-header">
				<button type="button" class="close fui-cross" data-dismiss="modal" aria-hidden="true"></button>
				<h4 class="modal-title"><?php FH_Theme::_e (/*T[*/'Process Image'/*]*/); ?></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-sm-12">
						<img src="" class="fh-image img-rounded">
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Latitude'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'latitude'); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Longitude'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'longitude'); ?></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Date'/*]*/); ?>:</label><?php FH_Theme::inp ('date', 'date'); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Time'/*]*/); ?>:</label><?php FH_Theme::inp ('time', 'time'); ?></div>
					</div>
				</div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'User'/*]*/); ?>:</label><?php FH_Theme::inp ('select', 'user_id', $fh_user->get(), [ 'data' => $users_select ]); ?></div>
			</div>
			<div class="modal-footer">
				<a href="#" class="btn btn-wide btn-warning" data-dismiss="modal"><i class="fui-cross"></i>&nbsp;Cancel</a>
				<a href="#" class="btn btn-wide btn-danger fh-delete"><i class="fui-trash"></i>&nbsp;Delete</a>
				<a href="#" class="btn btn-wide btn-primary fh-approve"><i class="fui-check"></i>&nbsp;Approve</a>
				<a href="#" class="btn btn-wide btn-primary fh-update"><i class="fui-check"></i>&nbsp;Update</a>
			</div>
		</form>
	</div>
</div>
