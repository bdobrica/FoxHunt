<?php
/*
Name: Statistics
Order: 1
*/
global $wpdb;

$sql = 'SELECT id,name FROM `' . $wpdb->prefix . FH_GeoUnit::$T . '` WHERE parent_id=0 ORDER BY name;';
$_counties = $wpdb->get_results ($sql);
$counties = [];
foreach ($_counties as $county)
	$counties[$county->id] = ['name' => FH_GeoUnit::name ($county->name)];
unset ($_counties);

$sql = 'SELECT
	a.county_id as id,
	IFNULL(SUM(b.status=\'taken\'),0) AS taken,
	IFNULL(SUM(b.status=\'uploaded\'),0) AS uploaded,
	IFNULL(SUM(b.status=\'approved\'),0) AS approved,
	IFNULL(SUM(b.status=\'deleted\'),0) AS deleted
FROM (SELECT * FROM `' . $wpdb->prefix . FH_GeoUnit::$T . '` WHERE county_id>0) a LEFT JOIN `' . $wpdb->prefix . FH_GeoImage::$T . '` b
	ON a.id=b.geounit_id
GROUP BY a.county_id
ORDER BY a.county_id';
$_county_stats = $wpdb->get_results ($sql);
foreach ($_county_stats as $county_stats) {
	$counties[$county_stats->id]['taken']		= $county_stats->taken;
	$counties[$county_stats->id]['uploaded']	= $county_stats->uploaded;
	$counties[$county_stats->id]['approved']	= $county_stats->approved;
	$counties[$county_stats->id]['deleted']		= $county_stats->deleted;
	}
unset ($_county_stats);

$sql = 'SELECT county_id as id,count(1) as required FROM `' . $wpdb->prefix . FH_GeoUnit::$T . '` WHERE type=\'village\' OR type=\'basecity\' GROUP BY county_id';
$_required = $wpdb->get_results ($sql);
foreach ($_required as $required) {
	$counties[$required->id]['required']		= $required->required * FH_GeoUnit::MIN_GEOIMAGES;
	}

$chart = [['County', 'Taken', 'Uploaded', 'Approved', 'Deleted', 'Required', (object)['role' => 'annotation']]];
foreach ($counties as $county)
	#$chart[] = [$county['name'], (int) $county['taken'], (int) $county['uploaded'], (int) $county['approved'], (int) $county['deleted'], (int) $county['required']];
	$chart[] = [$county['name'], rand (0, $county['required']), rand (0, $county['required']), rand (0, $county['required']), rand (0, $county['required']), (int) $county['required'], ''];

?>
<div class="fh-rounded fh-translucent fh-padded">
	<?php FH_Theme::c ($chart, 'County Report'); ?>
	<table class="table table-striped table-condensed">
		<thead>
			<tr>
				<th>County</th>
				<th>Taken</th>
				<th>Uploaded</th>
				<th>Approved</th>
				<th>Deleted</th>
				<th>Required</th>
			</tr>
		</thead>
		<tbody>
<?php foreach ($counties as $county) : ?>
			<tr>
				<td><?php echo $county['name']; ?></td>
				<td><?php echo $county['taken']; ?></td>
				<td><?php echo $county['uploaded']; ?></td>
				<td><?php echo $county['approved']; ?></td>
				<td><?php echo $county['deleted']; ?></td>
				<td><?php echo $county['required']; ?></td>
			</tr>
<?php endforeach; ?>
		</tbody>
	</table>
</div>
