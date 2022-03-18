<?php
/*
Plugin Name: AR Model
Description: Embed AR View for any device with upload.
Author: Vikas Rattan
Author URI: http://equasar.com/
Version: 1.0.0
*/

//Activation hook
register_activation_hook( __FILE__, 'ar_activate');
function ar_activate(){
  ar_create_folder();
  register_uninstall_hook( __FILE__, 'ar_uninstall');
}

//Uninstall hook
function ar_uninstall(){
  $ar_upload = wp_upload_dir();
  $ar_upload_dir = $ar_upload['basedir'] . '/ar-model';
  ar_removeDirectory($ar_upload_dir);
}

function ar_removeDirectory($path){
  $files = glob($path . '/*');
  foreach($files as $file){
    is_dir($file)? ar_removeDirectory($file) : unlink($file);
  }
  rmdir($path);
  return;
}

//Create uploads folder
function ar_create_folder(){
  $ar_upload = wp_upload_dir();
  $ar_upload_dir = $ar_upload['basedir'] . '/ar-model/';
  if(!is_dir($ar_upload_dir)){
    mkdir($ar_upload_dir, 0755);
  }
}

// AR Menu
function ar_create_menu(){
 
  $labels = array(
    'name'               => 'AR Models',
    'singular_name'      => 'AR Model',
    'add_new'            => 'Add New',
    'add_new_item'       => 'Add New AR Model',
    'edit_item'          => 'Edit AR Model',
    'new_item'           => 'New AR Model',
    'all_items'          => 'All AR Models',
    'view_item'          => 'View AR Model',
    'search_items'       => 'Search AR Models',
    'not_found'          =>  'No AR Models found',
    'not_found_in_trash' => 'No AR Models found in Trash',
    'parent_item_colon'  => '',
    'menu_name'          => 'AR Models'
  );
 
  $args = array(
    'labels'             => $labels,
    'public'             => true,
    'publicly_queryable' => true,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'query_var'          => true,
    'rewrite'            => array( 'slug' => 'ar-model' ),
    'capability_type'    => 'post',
    'has_archive'        => true,
    'hierarchical'       => false,
    'menu_position'      => null,
    'supports'           => array( 'title', 'author', 'thumbnail' )
  );
 
  register_post_type( 'ar-model', $args );
 
}
add_action( 'init', 'ar_create_menu' );

add_action( 'init', 'register_ar_shortcodes');
function register_ar_shortcodes(){
   add_shortcode('ar_model', 'ar_shortcode_query');
}

function ar_shortcode_query( $atts ) {
    global $post;
    $attributes = shortcode_atts(
        array(
            'id' => null
        ), $atts );

    $post   = get_post( $atts['id'] );
  $glb = get_post_meta( $post->ID, 'wp_glb_attachment', true );
  $usdz = get_post_meta( $post->ID, 'wp_usdz_attachment', true );
    $output = '';
        
    $output .= '<model-viewer src="'.$glb['url'].'"
              ios-src="'.$usdz['url'].'"
              alt="'.$post->post_title.'"
              ar
              auto-rotate
              camera-controls></model-viewer>
              <style>
                  model-viewer{width: 100%;height: 500px;}
              </style>
        <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.js"></script>
        <script nomodule src="https://unpkg.com/@google/model-viewer/dist/model-viewer-legacy.js">';

    wp_reset_query();
    return $output;
}
 
function add_ar_file_meta_boxes() {  
    add_meta_box('wp_glb_attachment', 'AR Model (.gbl) for Android', 'wp_glb_attachment', 'ar-model', 'normal', 'high'); 
    add_meta_box('wp_usdz_attachment', 'AR Model (.usdz) for iPhone', 'wp_usdz_attachment', 'ar-model', 'normal', 'high');  
}
add_action('add_meta_boxes', 'add_ar_file_meta_boxes');  

function wp_glb_attachment() {  
    wp_nonce_field(plugin_basename(__FILE__), 'wp_glb_attachment_nonce');
    $html = '<p class="description">';
    $html .= 'Upload your file here.';
    $html .= '</p>';
    $html .= '<input type="file" id="wp_glb_attachment" name="wp_glb_attachment" value=""><br/>';
    if (get_post_meta( get_the_ID(), 'wp_glb_attachment', true ) != '') {
      $glb = get_post_meta( get_the_ID(), 'wp_glb_attachment', true );
      $html .= "URL: ".$glb['url'];
    }
    echo $html;
}

function wp_usdz_attachment() {  
    wp_nonce_field(plugin_basename(__FILE__), 'wp_usdz_attachment_nonce');
    $html = '<p class="description">';
    $html .= 'Upload your file here.';
    $html .= '</p>';
    $html .= '<input type="file" id="wp_usdz_attachment" name="wp_usdz_attachment" value=""><br/>';
    if (get_post_meta( get_the_ID(), 'wp_usdz_attachment', true ) != '') {
        $usdz = get_post_meta( get_the_ID(), 'wp_usdz_attachment', true );
        $html .= "URL: ".$usdz['url'];
    }
    echo $html;
}

add_action('save_post', 'save_custom_meta_data');
function save_custom_meta_data($id) {
    if(!empty($_FILES['wp_glb_attachment']['name'])) {
        $supported_types = array('model/gltf-binary|application/octet-stream|model');
        $arr_file_type = wp_check_filetype(basename($_FILES['wp_glb_attachment']['name']));
        $uploaded_type = $arr_file_type['type'];

        if(in_array($uploaded_type, $supported_types)) {
            $upload = wp_upload_bits($_FILES['wp_glb_attachment']['name'], null, file_get_contents($_FILES['wp_glb_attachment']['tmp_name']));
            if(isset($upload['error']) && $upload['error'] != 0) {
                wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
            } else {
                update_post_meta($id, 'wp_glb_attachment', $upload);
            }
        }
        else {
            wp_die("The file type that you've uploaded is not a glb.");
        }
    }
    if(!empty($_FILES['wp_usdz_attachment']['name'])) {
        $supported_types2 = array('model/vnd.usdz+zip|application/octet-stream|model/x-vnd.usdz+zip');
        $arr_file_type2 = wp_check_filetype(basename($_FILES['wp_usdz_attachment']['name']));
        $uploaded_type2 = $arr_file_type2['type'];

        if(in_array($uploaded_type2, $supported_types2)) {
            $upload2 = wp_upload_bits($_FILES['wp_usdz_attachment']['name'], null, file_get_contents($_FILES['wp_usdz_attachment']['tmp_name']));
            if(isset($upload2['error']) && $upload2['error'] != 0) {
                wp_die('There was an error uploading your file. The error is: ' . $upload2['error']);
            } else {
                update_post_meta($id, 'wp_usdz_attachment', $upload2);
            }
        }
        else {
            wp_die("The file type that you've uploaded is not a usdz.");
        }
    }
}

function update_edit_form() {
    echo ' enctype="multipart/form-data"';
}
add_action('post_edit_form_tag', 'update_edit_form');

function my_myme_types($mime_types){
    $mime_types['svg'] = 'image/svg+xml'; //Adding svg extension
    $mime_types['usdz'] = 'model/vnd.usdz+zip|application/octet-stream|model/x-vnd.usdz+zip';
  $mime_types['glb'] = 'model/gltf-binary|application/octet-stream|model';
    return $mime_types;
}
add_filter('upload_mimes', 'my_myme_types', 1, 1);

function custom_meta_box_markup(){
    echo '<p>[ar_model id="'.$_REQUEST['post'].'"]</p>';
}

function add_custom_meta_box(){
    add_meta_box("demo-meta-box", "Use Shortcode", "custom_meta_box_markup", "ar-model", "side", "high", null);
}
add_action("add_meta_boxes", "add_custom_meta_box");


add_filter('manage_ar-model_posts_columns', function($columns) {
    
    return array_merge($columns, ['shortcodes' => __('Shortcodes', 'ar-model')]);
});

add_action('manage_ar-model_posts_custom_column', function($column_key, $post_id) {
    if ($column_key == 'shortcodes') {
        echo '[ar_model id="'.$post_id.'"]';
    }
}, 10, 2);