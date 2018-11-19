<?php
function rsv_gmaps_list() {
	global $wpdb;
	$args = array( 'post_type'   => 'rsvgmap',  'post_status' => 'publish','posts_per_page' => -1);
$myposts = get_posts( $args );
$rsv_center_of_map=get_option('rsv_center_of_map');
echo "<select id='rsv_center_of_map' name='rsv_center_of_map' class='regular-text'><option value=''>Select Location</option>";
foreach ( $myposts as $post ) : setup_postdata( $post ); 
if($post->ID == $rsv_center_of_map){
	?>
<option value="<?php echo $post->ID; ?>" selected="selected"><?php echo $post->post_title; ?></option>
<?php
}else{
	?>
<option value="<?php echo $post->ID; ?>"><?php echo $post->post_title; ?></option>
<?php
}
 endforeach; 
 	echo "</select>";
//wp_reset_postdata();
}
?>