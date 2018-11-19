<?php 
/*
Plugin Name: RSV Google Maps
Plugin URI: http://www.rapidsort.in
Description: Plugin for displaying Google Maps.
Author: Rapid Sort
Version: 1.5
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
function rsv_gmaps_enqueued_assets() {
	//wp_enqueue_style( 'owl-style', plugin_dir_url( __FILE__ ) . 'owl.carousel.css');
	wp_enqueue_script( 'j-script', 'http://maps.google.com/maps/api/js?sensor=false', array(), '1.0', false );
}
add_action( 'wp_enqueue_scripts', 'rsv_gmaps_enqueued_assets',10 );
include_once('cpt.php');
$rsvgmaps = new CPT_RSV_GMAPS('RSV GMap', array('supports' => array('title', 'thumbnail')));
$rsvgmaps->register_taxonomy('RSV GMap Category');
$rsvgmaps->menu_icon("dashicons-location");
include_once('functions.php');
add_action( 'add_meta_boxes', 'rsv_gmaps_rsv_gmaps_add_events_metaboxes' );
function rsv_gmaps_rsv_gmaps_add_events_metaboxes() {
	add_meta_box('rsv_gmaps_wpt_events_location', 'Location', 'rsv_gmaps_wpt_events_location', 'rsvgmap', 'advanced', 'default');
}
	// Save the Metabox Data
function rsv_gmaps_wpt_save_events_meta($post_id, $post) {
	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['eventmeta_noncename'], plugin_basename(__FILE__) )) {
	return $post->ID;
}
	// Is the user allowed to edit the post or page?
	if ( !current_user_can( 'edit_post', $post->ID ))
		return $post->ID;
	// OK, we're authenticated: we need to find and save the data
	// We'll put it into an array to make it easier to loop though.
	$events_meta['_location'] = sanitize_text_field($_POST['_location']);
	$events_meta['_city'] = sanitize_text_field($_POST['_city']);
	$events_meta['_state'] = sanitize_text_field($_POST['_state']);
	$events_meta['_country'] = sanitize_text_field($_POST['_country']);
	$events_meta['_zip'] = sanitize_text_field($_POST['_zip']);
	$address = $events_meta['_location'].", ".$events_meta['_city'].", ".$events_meta['_state'].", ".$events_meta['_zip'].", ".$events_meta['_country'];
	$coordinates = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&sensor=true');
	$coordinates = json_decode($coordinates);
	$events_meta['_lat']=$coordinates->results[0]->geometry->location->lat;
	$events_meta['_lng']=$coordinates->results[0]->geometry->location->lng;
	// Add values of $events_meta as custom fields
	foreach ($events_meta as $key => $value) { // Cycle through the $events_meta array!
		if( $post->post_type == 'revision' ) return; // Don't store custom data twice
		$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
		if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
			update_post_meta($post->ID, $key, $value);
		} else { // If the custom field doesn't have a value
		add_post_meta($post->ID, $key, $value);
		}
		if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
	}
}
add_action('save_post', 'rsv_gmaps_wpt_save_events_meta', 1, 2); // save the custom fields
	// The Event Location Metabox
function rsv_gmaps_wpt_events_location() {
	global $post;
	// Noncename needed to verify where the data originated
	echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' . 
	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	// Get the location data if its already been entered
	$location = esc_html(get_post_meta($post->ID, '_location', true));
    $dresscode = esc_html(get_post_meta($post->ID, '_city', true));
	$state = esc_html(get_post_meta($post->ID, '_state', true));
	$country = esc_html(get_post_meta($post->ID, '_country', true));
	$zip = esc_html(get_post_meta($post->ID, '_zip', true));
	$lat = esc_html(get_post_meta($post->ID, '_lat', true));
	$lng = esc_html(get_post_meta($post->ID, '_lng', true));
// Echo out the field
	echo '<p>Address:</p>';
	echo '<input type="text" name="_location" value="' . $location  . '" class="widefat" required />';
	echo '<p>City:</p>';
	echo '<input type="text" name="_city" value="' . $dresscode  . '" class="widefat" required />';
	echo '<p>State:</p>';
	echo '<input type="text" name="_state" value="' . $state  . '" class="widefat" required />';
	echo '<p>Country:</p>';
	echo '<input type="text" name="_country" value="' . $country  . '" class="widefat" required />';
	echo '<p>Zip Code:</p>';
	echo '<input type="text" name="_zip" value="' . $zip  . '" class="widefat" required />';
	if($lat!="" && $lng!=""){
	echo '<p>Latitude:</p>';
	echo '<input type="text" name="_zip" value="' . $lat  . '" class="widefat" readonly />';
	echo '<p>Longitude:</p>';
	echo '<input type="text" name="_zip" value="' . $lng  . '" class="widefat" readonly />';
	}
}
// [rsv_gmaps foo="foo-value"]
function rsv_gmaps( $atts ) {
$rsv_zoom_of_map=get_option('rsv_zoom_of_map',6);
$rsv_center_of_map=get_option('rsv_center_of_map');

$pointer="[";


    $a = shortcode_atts( array(
        'id' => 'all',
    ), $atts );
	$ids="{$a['id']}";
	$pos = strpos($ids, ",");
	if($pos){
		if($ids!="all"){
			$ids=explode(",", $ids);
		}
	}
	
	
	
$latlng="[";
if($ids=="all"){
	
	  $temp = $wp_query; 
	  $wp_query = null; 
	  $wp_query = new WP_Query(); 
	  $wp_query->query('showposts=-1&post_type=rsvgmap'); 
	  while ($wp_query->have_posts()) : $wp_query->the_post(); 
	    $id = get_the_id();
	    $_location= esc_html(get_post_meta($id, "_location", true));
		$_city= esc_html(get_post_meta($id, "_city", true));
		$_state= esc_html(get_post_meta($id, "_state", true));
		$_country= get_post_meta($id, "_country", true);
		$_zip= esc_html(get_post_meta($id, "_zip", true));
		$_lat= esc_html(get_post_meta($id, "_lat", true));
		$_lng= esc_html(get_post_meta($id, "_lng", true));
		
		if(has_post_thumbnail()){
			$pointer.= "['".wp_get_attachment_url( get_post_thumbnail_id($post->ID) )."'],";			
		}else{
			$pointer.="['noimage'],";
		}
		

	$display_address="<strong>Street: </strong>".$_location."<br/><strong>City: </strong>".$_city."<br/><strong>State: </strong>".$_state."<br/><strong>ZIP: </strong>".$_zip."<br/><strong>Country: </strong>".$_country;
		$latlng.="['$display_address', $_lat,$_lng, 1],";
	 endwhile;
	  $wp_query = null; 
	  $wp_query = $temp; 
	}else{
	if(!is_array($ids)){
		$_location= esc_html(get_post_meta($ids, "_location", true));
		$_city= esc_html(get_post_meta($ids, "_city", true));
		$_state= esc_html(get_post_meta($ids, "_state", true));
		$_country= esc_html(get_post_meta($ids, "_country", true));
		$_zip= esc_html(get_post_meta($ids, "_zip", true));
		$_lat= esc_html(get_post_meta($ids, "_lat", true));
		$_lng= esc_html(get_post_meta($ids, "_lng", true));
		
		if(wp_get_attachment_url( get_post_thumbnail_id($ids))!=""){
			$pointer.= "['".wp_get_attachment_url( get_post_thumbnail_id($ids) )."'],";			
		}else{
			$pointer.="['noimage'],";
		}
		
		$display_address="<strong>Street: </strong>".$_location."<br/><strong>City: </strong>".$_city."<br/><strong>State: </strong>".$_state."<br/><strong>ZIP: </strong>".$_zip."<br/><strong>Country: </strong>".$_country;
$latlng.="['$display_address', $_lat,$_lng, 1],";
}else{		
	foreach($ids as $id){		
		$_location= esc_html(get_post_meta($id, "_location", true));
		$_city= esc_html(get_post_meta($id, "_city", true));
		$_state= esc_html(get_post_meta($id, "_state", true));
		$_country= esc_html(get_post_meta($id, "_country", true));
		$_zip= esc_html(get_post_meta($id, "_zip", true));
		$_lat= esc_html(get_post_meta($id, "_lat", true));
		$_lng= esc_html(get_post_meta($id, "_lng", true));
		
		if(wp_get_attachment_url( get_post_thumbnail_id($id))!=""){
			$pointer.= "['".wp_get_attachment_url( get_post_thumbnail_id($id) )."'],";			
		}else{
			$pointer.="['noimage'],";
		}
		
		?>
        <script>
		alert(<?php echo $pointer; ?>);
		</script>
        <?php
		
		$display_address="<strong>Street: </strong>".$_location."<br/><strong>City: </strong>".$_city."<br/><strong>State: </strong>".$_state."<br/><strong>ZIP: </strong>".$_zip."<br/><strong>Country: </strong>".$_country;
	$latlng.="['$display_address',$_lat,$_lng, 1],";
	}
}
}
//-----------------------------------Center Element--------------------------------------------------------------------------
	if($rsv_center_of_map!=""){
	$cen_lat= esc_html(get_post_meta($rsv_center_of_map, "_lat", true));
	$cen_lng= esc_html(get_post_meta($rsv_center_of_map, "_lng", true));
	}else{
		$cen_lat=$_lat;
		$cen_lng=$_lng;
	}
//----------------------------------------end of center element------------------------------------------------------------------------
$latlng.="]";
$pointer.="]";

$rsvgmapspath = plugins_url().'/rsv-google-maps/images/icon.png';

$output.="<div id='map' style='width: 100%; height: 400px;'></div>";
  $output.="<script type='text/javascript'>
   var locations = $latlng;
   var pointer=$pointer;
  var map = new google.maps.Map(document.getElementById('map'), {
     zoom: $rsv_zoom_of_map,
     center: new google.maps.LatLng($cen_lat,$cen_lng),
     mapTypeId: google.maps.MapTypeId.ROADMAP
    });
   var infowindow = new google.maps.InfoWindow();
   var marker,i,icon;
   for (i = 0; i < locations.length; i++) { 
   icon=String(pointer[i]);
   if(icon=='noimage'){
	   icon='$rsvgmapspath';
   }
      marker = new google.maps.Marker({
        position: new google.maps.LatLng(locations[i][1], locations[i][2]),
        map: map, 
		icon:icon
      });


  google.maps.event.addListener(marker, 'click', (function(marker, i) {
  return function() {
  infowindow.setContent(locations[i][0]);
  infowindow.open(map, marker);
  }
  })(marker, i));
  
  }
 </script>";
return $output;


//return "foo = {$a['foo']}, bar = {$a['bar']}";
}
add_shortcode( 'RSV_GMaps', 'rsv_gmaps' );
add_action( 'admin_menu', 'rsv_gmais_plugin_menu' );
function rsv_gmais_plugin_menu() {
//add_options_page( 'RSV GMaps Options', 'RSV GMaps Options', 'manage_options', 'rsv-gmaps-unique-identifier', 'rsv_gmaps_plugin_options' );
add_submenu_page('edit.php?post_type=rsvgmap', 'RSV GMaps Options', 'RSV GMaps Options', 'manage_options', 'rsv-gmaps-unique-identifier', 'rsv_gmaps_plugin_options');
}
function rsv_gmaps_plugin_options() {
 global $wpdb;
 $lptable_name = $wpdb->prefix . 'buzztour_clients';
 if($_POST['rsv_data_save']){
 $rsv_zoom_of_map = $_POST['rsv_zoom_of_map'];   
 update_option('rsv_zoom_of_map',$rsv_zoom_of_map);
 $rsv_center_of_map = $_POST['rsv_center_of_map'];   
 update_option('rsv_center_of_map',$rsv_center_of_map);
}
if ( !current_user_can( 'manage_options' ) )  {
wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}
echo '<div class="wrap">';
?>
<h2>RSV GMaps Options</h2>
<p>RSV GMaps this Plugin for displaying Multiple Pointers in Google Maps. It will produce clean HTML code without any iframe.</p>
<div class="title">
  <p><strong>Use This Short Code to Display All pointers:</strong> [RSV_GMaps]</p>
</div>
<div class="title">
  <p><strong>Use This Short Code to Display Single Pointer:</strong> [RSV_GMaps id=16]</p>
</div>
<div class="title">
  <p><strong>Use This Short Code to Display Multiple Pointers:</strong> [RSV_GMaps id=16,20,36]</p>
</div>
<h3>Other Properties</h3>
<form method="post" action="">
  <table class="form-table">
    <tr>
      <th scope="row"><label for="subject">Zoom of Map <small>(between 5 and 20)</small></label></th>
      <td><input type="number" required name="rsv_zoom_of_map" id="rsv_zoom_of_map" value="<?php echo get_option('rsv_zoom_of_map');?>" class="regular-text" min="5" max="20"></td>
    </tr>
    <?php
$count_posts = wp_count_posts('rsvgmap');
	if($count_posts->publish > 1){
	?>
    <tr>
      <th scope="row"><label for="subject">Select Center Location</label></th>
      <td><?php echo rsv_gmaps_list(); ?></td>
    </tr>
    <?php
}
?>
 </table>
 <p class="submit">
 <input type="submit" value="Save" class="button button-primary" id="save" name="rsv_data_save">
 </p>
</form>
<?php	
	echo '</div>';
}
?>