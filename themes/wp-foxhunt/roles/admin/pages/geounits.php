<?php
/*
Name: Locations
Order: 1
*/
$types_select = FH_GeoUnit::$TYPES;
?>
<div class="fh-rounded fh-translucent fh-padded">
<?php switch ($action) :
/**	CREATE : */
	case 'create': ?>
	<div class="row">
		<div class="col-sm-12">
			<form action="" method="post">
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Name'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'name'); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Type'/*]*/); ?>:</label><?php FH_Theme::inp ('select', 'type', '', ['data' => $types_select]); ?></div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Latitude N-S'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'latitude'); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Longitude W-E'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'longitude'); ?></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'SIRUTA'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'siruta'); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Postal Code'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'phone'); ?></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Latitude (NE Corner)'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'ne_lat'); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Longitude (NE Corner)'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'ne_long'); ?></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Latitudine (SW Corner)'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'sw_lat'); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Longitudine (SW Corner)'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'sw_long'); ?></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
					</div>
					<div class="col-sm-6 text-right">
						<a href="<?php $fh_theme->out ('url', []); ?>" class="btn btn-wide btn-danger"><i class="fui-cross"></i>&nbsp;Cancel</a>
						<button class="btn btn-wide btn-primary"><i class="fui-check"></i>&nbsp;Add Location</button>
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
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Name'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'name', $object->get ('name')); ?></div>
				<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Type'/*]*/); ?>:</label><?php FH_Theme::inp ('select', 'type', $object->get ('type'), ['data' => $types_select]); ?></div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Latitude N-S'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'latitude', $object->get ('latitude')); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Longitude W-E'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'longitude', $object->get ('longitude')); ?></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'SIRUTA'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'siruta', $object->get ('siruta')); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Postal Code'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'phone', $object->get ('postal_code')); ?></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Latitude (NE Corner)'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'ne_lat', $object->get ('ne_lat')); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Longitude (NE Corner)'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'ne_long', $object->get ('ne_long')); ?></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Latitudine (SW Corner)'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'sw_lat', $object->get ('sw_lat')); ?></div>
					</div>
					<div class="col-sm-6">
						<div class="form-group"><label><?php FH_Theme::_e (/*T[*/'Longitudine (SW Corner)'/*]*/); ?>:</label><?php FH_Theme::inp ('string', 'sw_long', $object->get ('sw_long')); ?></div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
					</div>
					<div class="col-sm-6 text-right">
						<a href="<?php $fh_theme->out ('url', []); ?>" class="btn btn-wide btn-danger"><i class="fui-cross"></i>&nbsp;Cancel</a>
						<button class="btn btn-wide btn-primary"><i class="fui-check"></i>&nbsp;Add Location</button>
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
						<p><?php FH_Theme::_e (/*T[*/'Are you sure you want to delete this location?'/*]*/); ?></p>
						<div class="row"><div class="col-sm-3"><?php FH_Theme::_e (/*T[*/'Name'/*]*/); ?>:</div><div class="col-sm-9"><?php $object->out ('name'); ?></div></div>
						<div class="row"><div class="col-sm-3"><?php FH_Theme::_e (/*T[*/'Latitude N-S'/*]*/); ?>:</div><div class="col-sm-9"><?php $object->out ('latitude'); ?></div></div>
						<div class="row"><div class="col-sm-3"><?php FH_Theme::_e (/*T[*/'Longitude W-E'/*]*/); ?>:</div><div class="col-sm-9"><?php $object->out ('longitude'); ?></div></div>
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
		$parent_id = isset ($object) ? $object->get ('parent_id') : 0;
		$object_id = isset ($object) ? $object->get () : 0;
		$filter = [ sprintf ('parent_id=%d', $object_id)];
		$geounits = new FH_List ('FH_GeoUnit', $filter);
?>
	<div class="row">
		<div class="col-sm-6">
			<a href="<?php $fh_theme->out ('url', ['id' => $parent_id]); ?>" class="btn btn-sm btn-primary"><i class="fui-triangle-up"></i>&nbsp;<?php FH_Theme::_e (/*T[*/'Up'/*]*/); ?></a>
		</div>
		<div class="col-sm-6 text-right">
			<a href="<?php $fh_theme->out ('url', ['action' => 'create']); ?>" class="btn btn-sm btn-primary"><i class="fui-plus"></i>&nbsp;<?php FH_Theme::_e (/*T[*/'New Location'/*]*/); ?></a>
		</div>
	</div>
	<div class="row">
<?php		if ($geounits->is ('empty')) : ?>
<?php		else : ?>
		<div class="table-responsive">
			<table class="table table-condensed table-striped">
				<thead>
					<tr>
						<th><?php FH_Theme::_e (/*T[*/'No.'/*]*/); ?></th>
						<th><?php FH_Theme::_e (/*T[*/'Name'/*]*/); ?></th>
						<th><?php FH_Theme::_e (/*T[*/'Type'/*]*/); ?></th>
						<th><?php FH_Theme::_e (/*T[*/'Lat.'/*]*/); ?></th>
						<th><?php FH_Theme::_e (/*T[*/'Long.'/*]*/); ?></th>
						<th class="text-right"><?php FH_Theme::_e (/*T[*/'Actions'/*]*/); ?></th>
					</tr>
				</thead>
				<tbody>
<?php			$c = 1;
			foreach ($geounits->get () as $geounit) : ?> 
					<tr>
						<td><?php echo $c++; ?>.</td>
						<td><?php $geounit->out ('name'); ?></td>
						<td><?php $geounit->out ('type'); ?></td>
						<td><?php $geounit->out ('latitude'); ?></td>
						<td><?php $geounit->out ('longitude'); ?></td>
						<td class="text-right">
							<a class="btn btn-sm btn-info" href="<?php $fh_theme->out ('url', [ 'id' => $geounit->get (), 'action' => 'update' ]); ?>"><i class="fui-new"></i></a>
							<a class="btn btn-sm btn-danger" href="<?php $fh_theme->out ('url', [ 'id' => $geounit->get (), 'action' => 'delete' ]); ?>"><i class="fui-trash"></i></a>
							<a class="btn btn-sm btn-warning" href="<?php $fh_theme->out ('url', [ 'id' => $geounit->get () ]); ?>"><i class="fui-list-columned"></i></a>
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
