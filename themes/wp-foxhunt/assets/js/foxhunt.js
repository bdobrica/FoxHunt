/*
Name: foxhunt-script
Dependencies: flat-ui,google-charts
Version: 0.1
Footer: true
*/

// Some general UI pack related JS
// Extend JS String with repeat method
String.prototype.repeat = function (num) {return new Array(num + 1).join(this);};
Array.prototype.mark = function (p, m) {
	if (this == null) return -1;
	var o = Object(this);
	var l = o.length >>> 0;
	if (l === 0) return -1;
	var k = 0;
	if (p) {
		while (k < l) {
			if (o[k].id == p) {
				o[k].present = m;
				return k;
				}
			k ++;
			}
		}
	else
		while (k < l) o[k++].present = m;
	return -1;
	};

(function ($) {
var $rpc = '/wp-content/plugins/wp-foxhunt/rpc/index.php';
var $img = '/wp-content/geoimages/';
var $ajq = [];

var $charts = function () {
	var charts = $('.fh-chart');
	var c;
	for (c = 0; c < charts.length; c++) {
		var chart = $(charts[c]);
		var json = chart.data('chart');

		var data = google.visualization.arrayToDataTable(json.data);
		var options = { 'title': json.title, 'tooltip': {'trigger':'selection'}, 'legend': {'maxLines': 4}, 'chartArea': {'left': 0, 'top': 5, 'right': 0, 'height': '100%'}};
		var cdraw = new google.visualization.ColumnChart(chart[0]);
		cdraw.draw(data, options);
		}
	};
if ($('.fh-chart').length > 0) {
	google.charts.load('current', {'packages':['corechart']});
	google.charts.setOnLoadCallback($charts);
	}

var $maps = {
	className: 'fh-map',
	icons: { blue:'/wp-content/themes/wp-foxhunt/assets/img/map-azure.png', red:'/wp-content/themes/wp-foxhunt/assets/img/map-pink.png', green:'/wp-content/themes/wp-foxhunt/assets/img/map-green.png' },
	map: {},
	center: { lat: 45.943161, lng: 24.966760 },
	key: 'AIzaSyAiyCvqzTTK_4f0wIxXx1GMkl7wDULWsws',
	zoom: { country: 7, county: 10, city: 14 },
	markers: [], // {position:{lat:,lng:},map:,title:,icon:,present:,object}
	init: function () {
		var obj = $('.' + this.className)[0];
		this.map = new google.maps.Map ($('.' + this.className)[0], { zoom: this.zoom.country, center: this.center });

//		this.map.addListener('dragend', this.drawMarkers);
//		this.map.addListener('zoom_changed', this.drawMarkers);
		this.map.addListener('bounds_changed', this.drawMarkers);
		},
	drawMarkers: function () {
		var a = {lat:$maps.map.getCenter().lat(),lng:$maps.map.getCenter().lng(),ne_lat:$maps.map.getBounds().getNorthEast().lat(),ne_lng:$maps.map.getBounds().getNorthEast().lng(),sw_lat:$maps.map.getBounds().getSouthWest().lat(),sw_lng:$maps.map.getBounds().getSouthWest().lng(),zoom:$maps.map.zoom};
		$.getJSON($rpc + '?f=markers&p=' + escape(JSON.stringify(a)), function(data){
			var symbol = { path: 'M 0,0 -16,-5 -16,-16 16,-16 16,-5 0,0 z', fillColor: 'red', fillOpacity: 0.8, scale: 1, strokeColor: 'black', strokeWeight: 1, labelOrigin:{x:0,y:-10} };
			var c;
			var n = data.length;
			$maps.markers.mark(0, 0);
			for (c = 0; c < n; c++)
				if ($maps.markers.mark(data[c].id, 1) < 0) {
					var m = {id:data[c].id, position:data[c].position, map:$maps.map, title: data[c].title, icon: symbol, present: 1, label:{color: 'white', fontFamily: 'Arial', fontSize: '11px', text: '100%'}};
					var o = new google.maps.Marker(m);
					o.addListener('click', function(){
						if (!this.info_win)
							this.info_win = new google.maps.InfoWindow({content:''});
						$.ajax($rpc + '?f=geoinfo&p=' + escape(JSON.stringify({id:this.id,lat:this.position.lat(),lng:this.position.lng()})), {context: this, success: function (data){
							var j = $.parseJSON(data);
							var h = $('<div>',{class:'fh-infowindow'}).append($maps.contentInfoWindow(j, this.position)).on('wheel',function(e){
								e.stopPropagation ();
								});
							this.info_win.setContent(h[0]);
							this.info_win.open(this.map, this);
							}});
						});
					m.object = o;
					$maps.markers.push (m);
					}
			c = 0;
			while (c < $maps.markers.length) {
				if ($maps.markers[c].present == 0) {
					$maps.markers[c].object.setMap(null);
					$maps.markers.splice(c, 1);
					continue;
					}
				c ++;
				}
			});
		},
	contentInfoWindow: function(j,p) {
		var c;
		var h = $('<div>', {class:j.length == 1 ? 'fh-geounit' : 'fh-geochildren'});
		h.images = {required:0,taken:0,uploaded:0,approved:0,deleted:0};
		for (c in j) {
			var imgs = {required:0,taken:0,uploaded:0,approved:0,deleted:0};
			if (!j.hasOwnProperty(c)) continue;
			var t = j.length == 1 ? h : $('<div>', {class: 'fh-geounit'}).appendTo(h);
			if (j[c].hasOwnProperty('children')) {
				var m = $maps.contentInfoWindow(j[c].children, p);
				t.append(m);
				imgs.required += m.images.required;
				imgs.taken += m.images.taken;
				imgs.uploaded += m.images.uploaded;
				imgs.approved += m.images.approved;
				imgs.deleted += m.images.deleted;
				}
			if (j[c].hasOwnProperty('images')) {
				var i = $('<div>', {class:'fh-geoimages'});
				imgs.required += j[c].required;
				for (d in j[c].images) {
					if (!j[c].images.hasOwnProperty(d)) continue;
					if (j[c].images[d].status == 'taken') imgs.taken ++;
					if (j[c].images[d].status == 'uploaded') imgs.uploaded ++;
					if (j[c].images[d].status == 'approved') imgs.approved ++;
					if (j[c].images[d].status == 'deleted') imgs.deleted ++;
					i.append(
						$('<div>', {class:'fh-geoimage'}).append($('<img>', {class:'img-rounded pull-left', alt:'fox-img', src:$img + j[c].images[d].path}).on('click', {img:j[c].images[d],pos:p}, function(e){
							e.preventDefault();
							var m = $('.modal.update-image');
							var date = new Date();
							$('[name="id"]',m).val(e.data.img.id);
							$('[name="latitude"]',m).val(e.data.img.lat);
							$('[name="longitude"]',m).val(e.data.img.lng);
							$('[name="date"]',m).val(date.getDate() + '-' + (date.getMonth()+1) + '-' + date.getFullYear());
							$('[name="time"]',m).val(date.getHours() + ':' + date.getMinutes());
							$('.fh-image',m)[0].src = $img + e.data.img.path;
							console.log($('.fh-image',m).length);

							var abtn = $('.fh-approved',m);
							var ubtn = $('.fh-update',m);

							if (e.data.img.status == 'approved') {
								if (!abtn.hasClass('hidden')) abtn.addClass('hidden');
								if (ubtn.hasClass('hidden')) ubtn.removeClass('hidden');
								}
							else {
								if (abtn.hasClass('hidden')) abtn.removeClass('hidden');
								if (!ubtn.hasClass('hidden')) ubtn.addClass('hidden');
								}
							m.modal('show');
							console.log(e.data.img);
							}))
						);
					}
				i.append($('<div>', {class:'fh-geoimage'}).append($('<a>', {href:'#',class:'btn btn-primary btn-lg fui-photo'}).on('click', {geo:j[c],pos:p}, function(e){
					e.preventDefault();
					var m = $('.modal.create-image');
					var date = new Date();
					$('[name="geounit_id"]',m).val(e.data.geo.id);
					$('[name="latitude"]',m).val(e.data.pos.lat().toFixed(7));
					$('[name="longitude"]',m).val(e.data.pos.lng().toFixed(7));
					$('[name="date"]',m).val(date.getDate() + '-' + (date.getMonth()+1) + '-' + date.getFullYear());
					$('[name="time"]',m).val(date.getHours() + ':' + date.getMinutes());
					m.modal('show');
					})));
				t.append(i);
				}
			var n = $('<div>', {class:'fh-geotitle'}).append($('<h6>', {text:j[c].name}));
			if (j[c].hasOwnProperty('children'))
				n.append($('<a href="" class="fui-triangle-down"></a>').on('click', function(e){
					e.preventDefault();
					var a = $(this);
					if (a.hasClass('fui-triangle-up'))
						a.removeClass('fui-triangle-up').addClass('fui-triangle-down');
					else
						a.removeClass('fui-triangle-down').addClass('fui-triangle-up');
					a.parent().next().toggle();
					}));
			t.prepend(n.append($('<div>', {class:'fh-geostatus', text: (100 * imgs.uploaded / imgs.required).toFixed(2) + '%'})));
			h.images.required += imgs.required;
			h.images.taken += imgs.taken;
			h.images.uploaded += imgs.uploaded;
			h.images.approved += imgs.approved;
			h.images.deleted += imgs.deleted;
			}
		return h;
		},
	};

if ($('.' + $maps.className).length)
	jQuery.getScript ('https://maps.googleapis.com/maps/api/js?key=' + $maps.key, function(){ $maps.init (); });

if ($('[data-toggle="select"]').length) $('[data-toggle="select"]').select2();

$('.file-control').each(function(n,i){
	$('.file-upload', i).on('click', i, function(ev){
		ev.preventDefault();
		$('[type="file"]', ev.data).click().on('change', ev.data, function(ev){
			console.log(ev.data);
			var i = $('img', $(ev.data).closest('.form-group'));
			if (i.length) i[0].src = URL.createObjectURL(ev.target.files[0]);
			$('[type="text"]', ev.data).val(this.value);
			$('.file-clear', ev.data).show();
			});
		});
	$('.file-clear', i).on('click', i, function(ev){
		ev.preventDefault();
		var i = $('img', $(ev.data).closest('.form-group'));
		if (i.length) i[0].src = '';
		$('[type="text"]', ev.data).val('');
		$('.file-clear', ev.data).hide();
		}).hide();
	$('[type="text"]', i).on('keydown', function(ev){
		ev.preventDefault();
		});
	});

var mci = $('.modal.create-image');
var mui = $('.modal.update-image');
if (mci.length) {
	$('.fh-upload', mci).on('click', function(e){
		e.preventDefault ();
		console.log('test');
		});
	}
if (mui.length) {
	$('.fh-delete', mui).on('click', function(e){
		e.preventDefault ();
		console.log('test');
		});
	$('.fh-update', mui).on('click', function(e){
		e.preventDefault ();
		console.log('test');
		});
	$('.fh-approve', mui).on('click', function(e){
		e.preventDefault ();
		console.log('test');
		});
	}
})(jQuery);
