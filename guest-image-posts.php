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

    $result = gip_parse_file_errors($_FILES['gip_image_file'], $_POST['gip_image_caption']);
    
    if($result['error']){
    
      echo '<p>ERROR: ' . $result['error'] . '</p>';
    
    }else{

      $user_image_data = array(
      	'post_title' => $result['caption'],
        'post_status' => 'pending',

        /* 'post_author' => $current_user->ID, */
        'post_type' => 'post'     
      );

      echo '<p><b>Thank you! Your image has been submitted, and the Fresh Food Boston team will review it soon.</b></p>';
      
      if($post_id = wp_insert_post($user_image_data)){
      
        gip_process_image('gip_image_file', $post_id, $result['caption']);
      
        wp_set_object_terms($post_id, (int)$_POST['gip_image_category'], 'gip_image_category');
      
      }
    }
  }  


  
  echo gip_get_upload_image_form($gip_image_caption = $_POST['gip_image_caption']);
/*  echo gip_get_upload_image_form($gip_image_caption = $_POST['gip_image_caption'], $gip_image_category = $_POST['gip_image_category']); */
  /*
  if($user_images_table = gip_get_user_images_table($current_user->ID)){
  
    echo $user_images_table;
    
  } */

}



function gip_process_image($file, $post_id, $caption){
 
  require_once(ABSPATH . "wp-admin" . '/includes/image.php');
  require_once(ABSPATH . "wp-admin" . '/includes/file.php');
  require_once(ABSPATH . "wp-admin" . '/includes/media.php');
 
  $attachment_id = media_handle_upload($file, $post_id);
 
  update_post_meta($post_id, '_thumbnail_id', $attachment_id);

  $attachment_data = array(
  	'ID' => $attachment_id,
    'post_excerpt' => $caption
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



function gip_get_upload_image_form($gip_image_caption = '', $gip_image_category = 0){

  $out = '';
  $out .= '<form id="gip_upload_image_form" method="post" action="" enctype="multipart/form-data">';

  $out .= wp_nonce_field('gip_upload_image_form', 'gip_upload_image_form_submitted');
  
  $out .= '<br/><label for="gip_image_caption">Tell us what you found, where, and how much it cost.</label><br/>';
  $out .= '<input type="text" id="gip_image_caption" name="gip_image_caption" placeholder = "Caption for your post" value="' . $gip_image_caption . '"/><br/><br/>';
  $out .= '<label for="gip_image_file">Select your photo (up to 500kb, JPEG, GIF or PNG format)</label><br/>';  
  $out .= '<input type="file" size="60" name="gip_image_file" id="gip_image_file"><br/><br/>';
  
  $out .= '
    <div>Where did you take your photo?</div>
    <input type="text" id="geolocation-address" name="geolocation-address" class="newtag form-input-tip" size="25" autocomplete="off" value="" />
    <input id="geolocation-load" type="button" class="button geolocationadd" value="Load" tabindex="3" />
    <input type="hidden" id="geolocation-latitude" name="geolocation-latitude" />
    <input type="hidden" id="geolocation-longitude" name="geolocation-longitude" />
    <div id="geolocation-map" style="border:solid 1px #c6c6c6;width:265px;height:200px;margin-top:5px;"></div>
    <div style="margin:5px 0 0 0;">
      <input id="geolocation-public" name="geolocation-public" type="hidden" checked="checked" value="1" />
      <input id="geolocation-enabled" name="geolocation-on" type="hidden" value="1" checked="checked" />
      </div>
    </div>
  ';
  $out .= '<input type="submit" id="gip_submit" name="gip_submit" value="Submit your post">';
  $out .= '</form>';

  return $out;
  
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
