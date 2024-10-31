<?php
/*
Plugin Name: ScrollTo Bottom
Plugin URI: http://www.danielimhoff.com/wordpress-plugins/scrollto-bottom/
Description: Uses the jQuery plugin ScrollTo by Ariel Flesler to smoothly scroll the user's browser to the top of the page when the user clicks the unobtrusive go-to-top image.
Version: 1.1.1
Author: Daniel Imhoff
Author URI: http://www.danielimhoff.com/
License: GPL2
Tags: scrollto, scroll, go to bottom, bottom of page, dwieeb

   Copyright 2012  Daniel Imhoff  (email : dwieeb@gmail.com)

   This file is part of ScrollTo Bottom

   ScrollTo Bottom is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   ScrollTo Bottom is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with ScrollTo Bottom. If not, see <http://www.gnu.org/licenses/>.
*/

if( !function_exists( 'add_action' ) ) {
   die( __( 'You are not allowed to access this file outside of WordPress.', 'scrollto-bottom' ) );
}

if ( !defined( 'WP_CONTENT_URL' ) ) {
   define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
}

if ( !defined( 'WP_CONTENT_DIR' ) ) {
   define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( !defined( 'WP_PLUGIN_URL' ) ) {
   define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
}

if ( !defined( 'WP_PLUGIN_DIR' ) ) {
   define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

define( 'STB_VERSION', '1.1.1' );

// Did some nub rename the folder? 
define( 'STB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'STB_PLUGIN_DIRNAME', dirname( STB_PLUGIN_BASENAME ) );

// Define some absolute paths to certain plugin directories
define( 'STB_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . STB_PLUGIN_DIRNAME );
define( 'STB_IMAGES_DIR', WP_CONTENT_DIR . '/stb-images' );

// Define some URLs to certain plugin directories
define( 'STB_PLUGIN_URL', WP_PLUGIN_URL . '/' . STB_PLUGIN_DIRNAME );
define( 'STB_IMAGES_URL', WP_CONTENT_URL . '/stb-images' );

// Where's the options page?
define( 'STB_OPTIONS_URL', get_bloginfo( 'wpurl' ) . '/wp-admin/options-general.php?page=' . STB_PLUGIN_BASENAME );

// If the class does not exist already somehow, lay it out.
if( !class_exists( 'ScrollToBottom' ) ) {

   /**
    * ScrollTo Bottom class.
    *
    * This class is the container for the ScrollTo Bottom plugin. 
    *
    * @author Daniel Imhoff
    * @package Wordpress
    * @subpackage ScrollToBottom
    * @since 1.1.1
    */
   class ScrollToBottom {

      /**
       * Array of ScrollTo Bottom Options.
       *
       * @since 1.1.1
       */
      public $options = array();

      /**
       * Array of ScrollTo Bottom Errors.
       *
       * @since 1.1.1
       */
      public $errors = array();

      /**
       * Array of acceptable file types for images.
       *
       * @since 1.1.1
       */
      public $allowed_filetypes = array( 'image/jpeg', 'image/pjpeg', 'image/gif', 'image/png', 'image/x-png' );

      /**
       * Array of acceptable file extensions for images.
       *
       * @since 1.1.1
       */
      public $allowed_fileext = array( 'jpg', 'jpeg', 'gif', 'png' );

      /**
       * Constructor.
       *
       * @since 1.1.1
       * @return ScrollToBottom
       */
      public function __construct()
      {
         $default_options = array(
            'enable_scroll_event' => 0,
            'scroll_speed' => 750,
            'scroll_event_location' => 1000,
            'image' => 'dwieeb_arrow_darker.png',
            'image_width' => 30,
            'image_height' => 30,
            'location_y' => 'top',
            'location_x' => 'right',
            'location_y_amt' => 40,
            'location_x_amt' => 40,
         );

         // If there was an update
         if( version_compare( get_option( 'scrollto-bottom_version' ), STB_VERSION, '<' ) ) {
            update_option( 'scrollto-bottom_version', STB_VERSION );

            // Add default options if STT options do not exist in the database
            if( !$this->options = get_option( 'scrollto-bottom_options' ) ) {
               add_option( 'scrollto-bottom_options', $this->options = $default_options );
            } else {
               update_option( 'scrollto-bottom_options', $this->options = wp_parse_args( $this->options, $default_options ) );
            }
         } else {
            $this->options = get_option( 'scrollto-bottom_options' );
         }

         $this->errors = new WP_Error();

         if( !is_dir( STB_IMAGES_DIR ) ) {
            if(! @mkdir( STB_IMAGES_DIR, 0755 ) ) {
               $this->errors->add( 'scrollto-bottom_nodir', __( 'ScrollTo Bottom cannot create the images directory in /wp-content!', 'scrollto-bottom' ) );
            }
         }

         if( $handle = opendir( STB_PLUGIN_DIR . '/img/' ) ) {
            while( false !== ( $file = readdir( $handle ) ) ) {
               if( in_array( strtolower( end( explode( '.', $file ) ) ), $this->allowed_fileext ) ) {
                  if(! @rename( STB_PLUGIN_DIR . '/img/' . $file, STB_IMAGES_DIR . '/' . $file ) ) {
                     $this->errors->add( 'scrollto-bottom_renamedir', __( 'ScrollTo Bottom cannot move the images into the images directory in /wp-content!', 'scrollto-bottom' ) );
                     break;
                  }
               }
            }
         }

         closedir( $handle );

         // Hook into Wordpress
         $this->add_hooks();

         // Load the textdomain of this plugin
         load_plugin_textdomain( 'scrollto-bottom', false, STB_PLUGIN_DIR . '/languages/' );
      }

      /**
       * Destructor.
       *
       * @since 1.1.1
       */
      public function __destruct()
      {}

      /**
       * Hook into Wordpress.
       *
       * @since 1.1.1
       */
      private function add_hooks()
      {
         if( !is_admin() && substr($_SERVER['REQUEST_URI'], 1, 3) != 'wp-' ) {
            wp_enqueue_style( 'scrollto-bottom', STB_PLUGIN_URL . '/css/scrollto-bottom-css.php' );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'scrollTo', STB_PLUGIN_URL . '/js/jquery.scrollTo-1.4.2-min.js', array('jquery'), '1.4.2' );
            wp_enqueue_script( 'scrollto-bottom', STB_PLUGIN_URL . '/js/scrollto-bottom.js.php', array('jquery', 'scrollTo'), STB_VERSION );
         }

         add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
      }

      /**
       * This function is hooked into Wordpress via the 'admin_menu' hook.
       *
       * @since 1.1.1
       */
      public function admin_menu()
      {
         if( current_user_can( 'manage_options' ) ) {
            add_filter( 'plugin_action_links_' . STB_PLUGIN_BASENAME, array( &$this, 'plugin_action_links' ) );
            add_options_page( 'ScrollTo Bottom', 'ScrollTo Bottom', 'manage_options', STB_PLUGIN_BASENAME, array( &$this, 'options_page' ) );
         }
      }

      /**
       * This function is hooked into Wordpress via the 'plugin_action_links' filter.
       *
       * @since 1.1.1
       */
      public function plugin_action_links( $action_links )
      {
         array_unshift( $action_links, "<a href=\"" . STB_OPTIONS_URL . "\">" . __( 'Settings', 'scrollto-bottom' ) . "</a>" );

         return $action_links;
      }

      /**
       * This function is a callback of the add_options_page function. It outputs the HTML of the settings page in the Wordpress Administration Panel.
       *
       * @since 1.1.1
       */
      public function options_page()
      {
         if ( !current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'scrollto-bottom' ) );
         }

         $is_dir = is_dir( STB_IMAGES_DIR );
         $is_writable = is_writable( STB_IMAGES_DIR );

         if( !empty( $_FILES['icon_upload']['name'] ) ) { // Did they try to upload a JS file?
            $file_name = preg_replace( "/[^\w\.-]/", '', strtolower( $_FILES['icon_upload']['name'] ) );

            if( $is_dir && $is_writable ) {
               $file_path = STB_IMAGES_DIR . '/' . $file_name;
               $file_error = $_FILES['icon_upload']['error'];

               if ( !in_array( $_FILES['icon_upload']['type'], $this->allowed_filetypes ) ) {
                  $file_error = 1;
               }

               list( $width, $height ) = getimagesize( $_FILES['icon_upload']['tmp_name'] );

               if( $width > 250 || $height > 250 ) {
                  $file_error_size = 1;
                  $file_error = 1;
               }

               if( $file_error == 0 && $file_name != "" ) {
                  if( $file_error = !move_uploaded_file( $_FILES['icon_upload']['tmp_name'], $file_path ) ) {
                     @chmod( $file_path, 0644 );
                  }
               }
            }
         }

         $image_array = array();

         if( $handle = opendir( STB_IMAGES_DIR ) ) {
            while( false !== ( $file = readdir( $handle ) ) ) {
               if( in_array( end( explode( '.', $file ) ), $this->allowed_fileext ) ) {
                  $image_array[] = $file;
               }
            }
         }

         sort( $image_array );

         if( isset( $_POST['submit'] ) ) {
            if( !wp_verify_nonce( isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '', 'scrollto-bottom' ) ) {
               die('Security check');
            }

            list( $width, $height ) = getimagesize( STB_IMAGES_DIR . '/' . $_POST['image'] );

            $options = array(
               'enable_scroll_event' => ( intval( $_POST['enable_scroll_event'] ) < 0 ) ? 0 : intval( $_POST['enable_scroll_event'] ),
               'scroll_speed' => $_POST['scroll_speed'],
               'scroll_event_location' => $_POST['scroll_event_location'],
               'image' => $_POST['image'],
               'image_width' => $width,
               'image_height' => $height,
               'location_y' => ( intval( $_POST['location'] ) == 0 || intval( $_POST['location'] ) == 1 ? 'top' : 'bottom' ),
               'location_x' => ( intval( $_POST['location'] ) == 0 || intval( $_POST['location'] ) == 2 ? 'left' : 'right' ),
               'location_y_amt' => $_POST['location_y_amt'],
               'location_x_amt' => $_POST['location_x_amt'],
            );

            update_option( 'scrollto-bottom_options', $options );
            $this->options = get_option( 'scrollto-bottom_options' );
         }

         include 'includes/settings-page.php';
      }
	}
}

// The ape will always fail you.
$ScrollToBottom = new ScrollToBottom();