<?php
/*
Plugin Name: Guest Image Posts
Forked from Ross Elliot's User Image Posts (http://wp.rosselliot.co.nz/user-images/)
Version: 1.0
License: GPLv2
Author: Rodrigo Davies
Author URI: http://www.rodrigodavies.com
*/

define('MAX_UPLOAD_SIZE', 1000000);
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


/*
    if(isset($_POST['gip_image_delete_id'])){
    
      if($user_images_deleted = gip_delete_user_images($_POST['gip_image_delete_id'])){        
      
        echo '<p>' . $user_images_deleted . ' images(s) deleted!</p>';
        
      }
    }
  } */
  
  echo gip_get_upload_image_form($gip_image_caption = $_POST['gip_image_caption']);
/*  echo gip_get_upload_image_form($gip_image_caption = $_POST['gip_image_caption'], $gip_image_category = $_POST['gip_image_category']); */
  /*
  if($user_images_table = gip_get_user_images_table($current_user->ID)){
  
    echo $user_images_table;
    
  } */

}

/*
function gip_delete_user_images($images_to_delete){

  $images_deleted = 0;

  foreach($images_to_delete as $user_image){

    if (isset($_POST['gip_image_delete_id_' . $user_image]) && wp_verify_nonce($_POST['gip_image_delete_id_' . $user_image], 'gip_image_delete_' . $user_image)){
    
      if($post_thumbnail_id = get_post_thumbnail_id($user_image)){

        wp_delete_attachment($post_thumbnail_id);      

      }  

      wp_trash_post($user_image);
      
      $images_deleted ++;

    }
  }

  return $images_deleted;

} */

/*
function gip_get_user_images_table($user_id){

  $args = array(
    'author' => $user_id,
    'post_type' => 'post',
    'post_status' => 'pending'    
  );
  
  $user_images = new WP_Query($args);

  if(!$user_images->post_count) return 0;
  
  $out = '';
  $out .= '<p>Your unpublished images - Click to see full size</p>';
  
  $out .= '<form method="post" action="">';
  
  $out .= wp_nonce_field('gip_form_delete', 'gip_form_delete_submitted');  
  
  $out .= '<table id="user_images">';
  $out .= '<thead><th>Image</th><th>Caption</th><th>Category</th><th>Delete</th></thead>';
    
  foreach($user_images->posts as $user_image){
  
    $user_image_cats = get_the_terms($user_image->ID, 'gip_image_category');
    
    foreach($user_image_cats as $cat){
    
      $user_image_cat = $cat->name;
    
    }
    
    $post_thumbnail_id = get_post_thumbnail_id($user_image->ID);   

    $out .= wp_nonce_field('gip_image_delete_' . $user_image->ID, 'gip_image_delete_id_' . $user_image->ID, false); 
       
    $out .= '<tr>';
    $out .= '<td>' . wp_get_attachment_link($post_thumbnail_id, 'thumbnail') . '</td>';    
    $out .= '<td>' . $user_image->post_title . '</td>';
    $out .= '<td>' . $user_image_cat . '</td>';    
    $out .= '<td><input type="checkbox" name="gip_image_delete_id[]" value="' . $user_image->ID . '" /></td>';          
    $out .= '</tr>';
    
  }

  $out .= '</table>';
    
  $out .= '<input type="submit" name="gip_delete" value="Delete Selected Images" />';
  $out .= '</form>';  
  
  return $out;

}

*/

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
  
    $result['error'] = 'Your image was ' . $file['size'] . ' bytes! It must not exceed 1MB.';
    
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
  $out .= '<input type="submit" id="gip_submit" name="gip_submit" value="Submit your post">';

  $out .= '</form>';

  return $out;
  
}


/*
function gip_get_image_categories_dropdown($taxonomy, $selected){

  return wp_dropdown_categories(array('taxonomy' => $taxonomy, 'name' => 'gip_image_category', 'selected' => $selected, 'hide_empty' => 0, 'echo' => 0));

} */


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
