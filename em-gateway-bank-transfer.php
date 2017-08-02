<?php
/*
Plugin Name: Events Manager Pro - Bank Transfer gateway
Plugin URI: https://wordpress.org/plugins/em-gateway-bank-transfer/
Description: Bank Transfer gateway plugin for Events Manager Pro
Version: 0.1
Depends: Events Manager Pro
Author: Florent Maillefaud
Author URI: https://restezconnectes.fr
Domain Path: /languages
Text Domain: em-gateway-bank-transfer
*/

/*  Copyright 2007-2015 Florent Maillefaud (email: contact at restezconnectes.fr)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class EM_Pro_Banktransfer {

	function __construct() {
		global $wpdb;
		//Set when to run the plugin : after EM is loaded.
		add_action( 'plugins_loaded', array(&$this,'init'), 100 );
	}

	function init() {
		//add-ons
		if( is_plugin_active('events-manager/events-manager.php') && is_plugin_active('events-manager-pro/events-manager-pro.php') ) {
			//add-ons
			include('add-ons/gateways/gateway.banktransfer.php');
		}else{
			add_action( 'admin_notices', array(&$this,'not_activated_error_notice') );
		}
        // Enable localization
        add_action( 'init', array(&$this,'_empbt_load_translation' ));        
        add_action( 'admin_menu', array( $this, 'empbt_add_admin') );
        add_filter( 'plugin_action_links', array( $this, 'empbt_plugin_actions'), 10, 2 );
	}

    function empbt_add_admin() {
       
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'my-script-handle', plugin_dir_url( __FILE__ ).'js/gateway-banktransfer-color-options.js', array( 'wp-color-picker' ), false, true );
    }
    
    // Add "Réglages" link on plugins page
    function empbt_plugin_actions( $links, $file ) {

        if ( $file != plugin_basename( __FILE__ ) ) {
		  return $links;
        } else {
            $wpm_settings_link = '<a href="edit.php?post_type=event&page=events-manager-gateways&action=edit&gateway=banktransfer">'
                . esc_html( __( 'Settings', 'em-gateway-bank-transfer' ) ) . '</a>';

            array_unshift( $links, $wpm_settings_link );

            return $links;
        }
    }
    
    function _empbt_load_translation() {
        load_plugin_textdomain( 'em-gateway-bank-transfer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
    
    
	function not_activated_error_notice() {
		$class = "error";
		$message = __('Please ensure both Events Manager and Events Manager Pro be enabled for the Bank Transfer gateway to work.', 'em-pro');
		echo '<div class="'.$class.'"> <p>'.$message.'</p></div>';
	}
}

// Start plugin
global $EM_Pro_Banktransfer;
$EM_Pro_Banktransfer = new EM_Pro_Banktransfer();