<?php
/*
Plugin Name: WebP Converter
Description: A plugin to convert selected images to WebP format and display the converted file names and URLs in the dashboard.
Version: 1.2
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

add_action( 'admin_menu', 'webp_converter_create_menu' );
add_action( 'admin_enqueue_scripts', 'webp_converter_enqueue_scripts' );

function webp_converter_create_menu() {
    add_menu_page(
        'WebP Converter',
        'WebP Converter',
        'manage_options',
        'webp-converter',
        'webp_converter_settings_page'
    );
}

function webp_converter_enqueue_scripts( $hook ) {
    if ( 'toplevel_page_webp-converter' !== $hook ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script( 'webp_converter_script', plugin_dir_url( __FILE__ ) . 'webp-converter-script.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_style( 'webp_converter_styles', plugin_dir_url( __FILE__ ) . 'webp-converter-styles.css' );
}

function webp_converter_settings_page() {
    ?>
    <div class="wrap">
        <h1>WebP Converter</h1>
        <button id="webp-converter-select-images" class="button button-primary">Select Images</button>
        <form id="webp-converter-form" method="post" action="">
            <input type="hidden" id="webp-converter-image-ids" name="webp_converter_image_ids" value="">
            <input type="submit" name="webp_converter_convert_images" class="button button-primary" value="Convert Images to WebP">
        </form>
        <?php webp_converter_display_converted_files(); ?>
    </div>
    <?php
}

function webp_converter_handle_image_upload() {
    if ( ! empty( $_POST['webp_converter_image_ids'] ) ) {
        $image_ids = explode( ',', $_POST['webp_converter_image_ids'] );
        $converted_files = array();

        foreach ( $image_ids as $image_id ) {
            $attachment = get_post( $image_id );
            $image_path = get_attached_file( $image_id );
            $image_info = pathinfo( $image_path );

            if ( in_array( strtolower( $image_info['extension'] ), array( 'jpg', 'jpeg', 'png' ) ) ) {
                $webp_image_path = $image_info['dirname'] . '/' . $image_info['filename'] . '.webp';

                if ( strtolower( $image_info['extension'] ) == 'jpg' || strtolower( $image_info['extension'] ) == 'jpeg' ) {
                    $image_resource = imagecreatefromjpeg( $image_path );
                } elseif ( strtolower( $image_info['extension'] ) == 'png' ) {
                    $image_resource = imagecreatefrompng( $image_path );
                }

                if ( $image_resource ) {
                    // Convert image to WebP
                    if (imagewebp( $image_resource, $webp_image_path )) {
                        $converted_files[] = array(
                            'file_name' => basename( $webp_image_path ),
                            'file_url' => str_replace( basename( $image_path ), basename( $webp_image_path ), wp_get_attachment_url( $image_id ) )
                        );
                    }
                    imagedestroy( $image_resource );
                }
            }
        }

        if ( ! empty( $converted_files ) ) {
            // Store converted files in an option to display later
            update_option( 'webp_converter_converted_files', $converted_files );
        }
    }
}

function webp_converter_display_converted_files() {
    $converted_files = get_option( 'webp_converter_converted_files', array() );

    if ( ! empty( $converted_files ) ) {
        echo '<h2>Converted Files</h2>';
        echo '<ul>';
        foreach ( $converted_files as $file ) {
            echo '<li>' . esc_html( $file['file_name'] ) . ' - <a href="' . esc_url( $file['file_url'] ) . '" target="_blank">' . esc_html( $file['file_url'] ) . '</a></li>';
        }
        echo '</ul>';
        // Clear the list after displaying
        delete_option( 'webp_converter_converted_files' );
    }
}

if ( isset( $_POST['webp_converter_convert_images'] ) ) {
    webp_converter_handle_image_upload();
}
?>
