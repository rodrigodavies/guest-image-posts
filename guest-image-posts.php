<?php
/*
Plugin Name: Guest Image Posts
Forked from Ross Elliot's User Image Posts (http://wp.rosselliot.co.nz/user-images/)
Version: 1.0
License: GPLv2
Author: Rodrigo Davies
Author URI: http://www.rodrigodavies.com
*/

define('MAX_UPLOAD_SIZE', 6000000);
define('TYPE_WHITELIST', serialize(array(
  'image/jpeg',
  'image/png',
  'image/gif'
  )));


add_shortcode('gip_form', 'gip_form_shortcode');


function gip_form_shortcode(){

  global $current_user;
    
  if(isset( $_POST['gip_upload_image_form_submitted'] ) && wp_verify_nonce($_POST['gip_upload_image_form_submitted'], 'gip_upload_image_form') ){  

    $result = gip_parse_file_errors($_FILES['gip_image_file'], $_POST['gip_image_caption'], $_POST['gip_image_tags'], $_POST['geo_address']);
    $geo_address = $_POST['geo_address'];
	
    if($result['error']){
    
      echo '<p style="color: red">Error: ' . $result['error'] . '</p>';
    
    }else{
		$lat = $_POST['latitude'];
		$lon = $_POST['longitude'];
		$user_image_data = array(
			'post_title' => $result['caption'],
			'post_status' => 'pending',
			'post_type' => 'post'     
		  );
      
		if (strlen($geo_address) > 1) {
			$address = str_replace(" ", "+", $geo_address);
			$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";
			$geoinfo = wp_remote_get( $url );
			if( "OK" == $geoinfo['response']['message'] ) {
				$geoinfo = json_decode($geoinfo['body']);
				$lat = $geoinfo->results[0]->geometry->location->lat;
				$lon = $geoinfo->results[0]->geometry->location->lng;
			}
		} else {
			$geo_address = $_POST['accuracy'];
		}
		
      echo '<p style="color: green"><b>Thank you! Your image has been submitted, and the Fresh Food Boston team will review it soon.</b></p>';
      
      if($post_id = wp_insert_post($user_image_data)){
      
        gip_process_image('gip_image_file', $post_id, $result['caption']);
      	update_post_meta($post_id, 'geo_latitude', $lat);
		update_post_meta($post_id, 'geo_longitude', $lon);
		update_post_meta($post_id, 'geo_address', $geo_address);

        //wp_set_object_terms($post_id, (int)$_POST['gip_image_category'], 'geo_address', 'gip_image_category');
      
      }
    }
  }  



	echo gip_get_upload_image_form($gip_image_caption = $_POST['gip_image_caption'], $gip_image_tags = $_POST['gip_image_tags'], $geo_address = $_POST['geo_address']);
    echo gip_get_geolocation_form();
    echo gip_get_upload_image_submit();
}



function gip_process_image($file, $post_id, $caption){
 
  require_once(ABSPATH . "wp-admin" . '/includes/image.php');
  require_once(ABSPATH . "wp-admin" . '/includes/file.php');
  require_once(ABSPATH . "wp-admin" . '/includes/media.php');
 
  $attachment_id = media_handle_upload($file, $post_id);
 
  update_post_meta($post_id, '_thumbnail_id', $attachment_id, $gip_image_tags);
  update_post_meta($post_id, 'geo_latitude', $lat);
  update_post_meta($post_id, 'geo_longitude', $lon);
  update_post_meta($post_id, 'geo_address', $geo_address);

	$attachment_data = array(
		'ID' => $attachment_id,
		'post_excerpt' => $caption,
		'tags_input' => $gip_image_tags
	);
  
  wp_update_post($attachment_data);

  return $attachment_id;

}


function gip_parse_file_errors($file = '', $image_caption){

  $result = array();
  $result['error'] = 0;
  
  if($file['error']){
  
    $result['error'] = "No file uploaded or there was an upload error!";
    
    return $result;
  
  }

  $image_caption = trim(preg_replace('/[^a-zA-Z0-9\s]+/', ' ', $image_caption));
  
  if($image_caption == ''){

    $result['error'] = "Your caption may only contain letters, numbers and spaces!";
    
    return $result;
  
  }
  
  $result['caption'] = $image_caption;  

  $image_data = getimagesize($file['tmp_name']);
  
  if(!in_array($image_data['mime'], unserialize(TYPE_WHITELIST))){
  
    $result['error'] = 'Your image must be a jpeg, png or gif!';
    
  }elseif(($file['size'] > MAX_UPLOAD_SIZE)){
  
    $result['error'] = 'Your image was ' . $file['size'] . ' bytes! It must not exceed 6MB.';
    
  }
    
  return $result;

}



function gip_get_upload_image_form($gip_image_caption = '', $gip_image_category = 0, $gip_image_tags = '', $geo_address = ''){

  $out = '';
  $out .= '<form id="gip_upload_image_form" method="post" action="" enctype="multipart/form-data">';

  $out .= wp_nonce_field('gip_upload_image_form', 'gip_upload_image_form_submitted');
  $out .= '<input type="hidden" name="latitude" id="latitude" value=""><input type="hidden" id="longitude" name="longitude" value=""><input type="hidden" id="accuracy"  name="accuracy" value="">';
  $out .= '<label for="gip_image_file">Step 1. Choose your photo (up to 6MB, JPEG, GIF or PNG format)</label>';  
  $out .= '<input type="file" size="60" name="gip_image_file" id="gip_image_file"><br/>';
  $out .= '<label for="gip_image_caption">Step 2. Describe what you found and how much it cost.</label>';
  $out .= '<input type="text" id="gip_image_caption" name="gip_image_caption" placeholder = "Three bananas for $1" value="' . $gip_image_caption . '"/><br/>';
  $out .= '<br/><label for="gip_image_tags">Step 3. Add some tags, like <i>vegetable</i>, <i>cooked meal</i> or <i>Dorchester</i></label>';
  $out .= '<input type="text" id="gip_image_tags" name="gip_image_tags" placeholder = "Tags" value="' . $gip_image_tags . '"/><br/>';
  $out .= '<br/><label for="geo_address">Step 4. Where did you find it? If the map below is incorrect or missing, type the address here</label>';
  $out .= '<input type="text" id="geo_address" name="geo_address" placeholder = "Address" value="' . $geo_address . '"/><br/><br/>';

  return $out;
  
}

function gip_get_upload_image_submit(){

  $out2 .= '';
  $out2 .= '<input type="submit" id="gip_submit" name="gip_submit" value="Submit your photo">';
  $out2 .= '</form>';

  return $out2;

}

function gip_get_geolocation_form(){
?>

	<script language="javascript" type="text/javascript" src="<?php echo site_url(); ?>/wp-includes/js/jquery/jquery2.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo site_url(); ?>/wp-includes/js/jquery/jquery.cookie.js"></script>
	</head>

	<script type="text/javascript">
				
		var latitude;
		var longitude;
		var accuracy;
		
	window.onload = function(){
		
			if(navigator.geolocation) {
				//document.getElementById("status").innerHTML = "HTML5 Geolocation is supported in your browser.";
				//document.getElementById("status").style.color = "#1ABC3C";
				
				if($.cookie("posLat")) {
					latitude = $.cookie("posLat");
					longitude = $.cookie("posLon");
					accuracy = $.cookie("posAccuracy");
					document.getElementById("status").innerHTML = "We saved your location from a previous visit. <a id=\"clear_cookies\" href=\" javascript:clear_cookies();\" style=\"cursor:pointer;\">Click here to clear it (you'll need to refresh your browser to detect a new location).</a>";
					//document.getElementById("status").innerHTML = "Location data retrieved from cookies. <a id=\"clear_cookies\" href=\" javascript:clear_cookies();\" style=\"cursor:pointer; margin-left: 15px;\"> clear cookies</a>";
					updateDisplay();
					
				} else {
					navigator.geolocation.getCurrentPosition(
										success_handler, 
										error_handler, 
										{timeout:10000});
				}
			}
		}

		function success_handler(position) {
			latitude = position.coords.latitude;
			longitude = position.coords.longitude;
			accuracy = position.coords.accuracy;
			
			if (!latitude || !longitude) {
				document.getElementById("status").innerHTML = "HTML5 Geolocation supported, but location data is currently unavailable.";
				return;
			}
			
			updateDisplay();
			
			$.cookie("posLat", latitude);
			$.cookie("posLon", longitude);
			$.cookie("posAccuracy", accuracy);
		  
		}
		
		function updateDisplay() {
			//var gmapdata = '<iframe width="425" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;ie=UTF8&amp;hq=&amp;ll=' + latitude + ',' + longitude + '&amp;output=embed"></iframe>';
			var gmapdata = '<img src="http://maps.google.com/maps/api/staticmap?center=' + latitude + ',' + longitude + '&zoom=16&size=400x350&sensor=true" />';
					
			document.getElementById("placeholder").innerHTML = gmapdata;
			document.getElementById("latitude").value = latitude;
			document.getElementById("longitude").value = longitude;
			document.getElementById("accuracy").value = accuracy;
		}
		
		
		function error_handler(error) {
			var locationError = '';
			
			switch(error.code){
			case 0:
				locationError = "There was an error while retrieving your location: " + error.message;
				break;
			case 1:
				locationError = "The user prevented this page from retrieving a location.";
				break;
			case 2:
				locationError = "The browser was unable to determine your location: " + error.message;
				break;
			case 3:
				locationError = "The browser timed out before retrieving the location.";
				break;
			}

			document.getElementById("status").innerHTML = locationError;
			document.getElementById("status").style.color = "#D03C02";
		}
		
		function clear_cookies() {
			$.cookie('posLat', null);
			document.getElementById("status").innerHTML = "Cookies cleared.";
		}
		
	   
	</script>

	<div class="content">
			<strong><span id="status"></span></strong>
			<span id="latitude"></span>
			<span id="longitude"></span>
			<span id="accuracy"></span>
			<div id="placeholder" style="width: 100%; height: 100%; position: relative;">
			<i>Note: May take a few seconds to get the location.</i>
			</div>
	</div>

<?php
}


add_action('init', 'gip_plugin_init');

function gip_plugin_init(){

  $image_type_labels = array(
    'name' => _x('User images', 'post type general name'),
    'singular_name' => _x('User Image', 'post type singular name'),
    'add_new' => _x('Add New User Image', 'image'),
    'add_new_item' => __('Add New User Image'),
    'edit_item' => __('Edit User Image'),
    'new_item' => __('Add New User Image'),
    'all_items' => __('View User Images'),
    'view_item' => __('View User Image'),
    'search_items' => __('Search User Images'),
    'not_found' =>  __('No User Images found'),
    'not_found_in_trash' => __('No User Images found in Trash'), 
    'parent_item_colon' => '',
    'menu_name' => 'User Images' 
  );
  
  $image_type_args = array(
    'labels' => $image_type_labels,
    'geo_address' => $geo_address,
    'public' => true,
    'query_var' => true,
    'rewrite' => true,
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'map_meta_cap' => true,
    'menu_position' => null,
    'supports' => array('title', 'editor', 'author', 'thumbnail')
  ); 
  
    
} 