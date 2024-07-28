<?php
/*
Plugin Name: Image to WebP Converter
Description: A plugin to convert PNG, JPG, JPEG images to WebP format.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Hook for adding admin menus
add_action('admin_menu', 'image_to_webp_converter_menu');

function image_to_webp_converter_menu() {
    add_menu_page('Image to WebP Converter', 'WebP Converter', 'manage_options', 'image-to-webp-converter', 'image_to_webp_converter_admin_page');
}

function image_to_webp_converter_admin_page() {
    ?>
    <div class="wrap">
        <h2>Image to WebP Converter</h2>
        <div id="section-1">
            <h3>Convert Single Image</h3>
            <input type="file" id="image-upload" accept="image/png, image/jpeg">
            <button id="convert-image" class="button button-primary">Convert Selected Image</button>
        </div>
        <div id="section-2">
            <h3>Convert All Images in Media Library</h3>
            <button id="convert-all-images" class="button button-primary">Convert All Images to WebP</button>
        </div>
        <div id="conversion-result"></div>
    </div>
    <?php
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'image_to_webp_converter_admin_scripts');

function image_to_webp_converter_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_image-to-webp-converter') {
        return;
    }
    wp_enqueue_script('image-to-webp-converter', plugins_url('image-to-webp-converter.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('image-to-webp-converter', 'webpConverter', array('ajax_url' => admin_url('admin-ajax.php')));
}

// Handle AJAX request for single image conversion
add_action('wp_ajax_convert_single_image', 'convert_single_image_callback');

function convert_single_image_callback() {
    if (!empty($_FILES['image'])) {
        $uploaded_file = $_FILES['image'];
        $file_type = wp_check_filetype($uploaded_file['name']);
        if (in_array($file_type['ext'], array('jpg', 'jpeg', 'png'))) {
            $image_path = $uploaded_file['tmp_name'];
            $original_name = $uploaded_file['name'];
            $webp_name = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $original_name);
            $webp_path = dirname($image_path) . '/' . $webp_name;

            if (convert_to_webp($image_path, $webp_path, $file_type['ext'])) {
                wp_send_json_success(array('message' => 'Image converted successfully!', 'webp_url' => $webp_path));
            } else {
                wp_send_json_error(array('message' => 'Image conversion failed.'));
            }
        } else {
            wp_send_json_error(array('message' => 'Invalid image format.'));
        }
    } else {
        wp_send_json_error(array('message' => 'No image uploaded.'));
    }
}

// Handle AJAX request for converting all images
add_action('wp_ajax_convert_all_images', 'convert_all_images_callback');

function convert_all_images_callback() {
    $args = array(
        'post_type' => 'attachment',
        'post_mime_type' => array('image/jpeg', 'image/png'),
        'posts_per_page' => -1,
    );
    $query = new WP_Query($args);
    $converted_images = 0;
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $image_path = get_attached_file(get_the_ID());
            $file_type = wp_check_filetype($image_path);
            if (in_array($file_type['ext'], array('jpg', 'jpeg', 'png'))) {
                $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_path);
                if (convert_to_webp($image_path, $webp_path, $file_type['ext'])) {
                    // Update the attachment metadata to reflect the new WebP file
                    update_attached_file(get_the_ID(), $webp_path);
                    $converted_images++;
                }
            }
        }
        wp_reset_postdata();
    }
    wp_send_json_success(array('message' => "$converted_images images converted to WebP."));
}

// Function to convert image to WebP
function convert_to_webp($image_path, $webp_path, $image_type) {
    switch ($image_type) {
        case 'jpeg':
        case 'jpg':
            $image = imagecreatefromjpeg($image_path);
            break;
        case 'png':
            $image = imagecreatefrompng($image_path);
            break;
        default:
            return false;
    }
    if ($image) {
        if (imagewebp($image, $webp_path)) {
            imagedestroy($image);
            unlink($image_path); // Delete the original image
            return true;
        } else {
            imagedestroy($image);
            return false;
        }
    }
    return false;
}
?>
