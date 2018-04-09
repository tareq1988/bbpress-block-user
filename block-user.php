<?php
/**
 * Plugin Name: bbPress - Block User
 * Description: Block user in bbPress from creating new topics and replies
 * Plugin URI: http://wordpress.org/plugins/bbpress-block-user/
 * Author: Tareq Hasan
 * Author URI: http://tareq.wedevs.com
 * Version: 1.0
 * License: GPL2
 * Text Domain: bbp-block-user
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 Tareq Hasan (email: tareq@wedevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * User Blocking
 */
class BBP_Block_User {

    function __construct() {
        add_action( 'bbp_current_user_can_publish_topics', array( $this, 'maybe_block_user' ) );
        add_action( 'bbp_current_user_can_publish_replies', array( $this, 'maybe_block_user' ) );

        add_filter( 'bbp_admin_get_settings_sections', array( $this, 'admin_settings_section' ) );
        add_filter( 'bbp_admin_get_settings_fields', array( $this, 'admin_settings_fields' ) );
        add_filter( 'bbp_map_settings_meta_caps', array( $this, 'meta_caps' ), 10, 2 );

        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_action_links' ) );
    }

    /**
     * Maps settings capabilities
     *
     * @param  array  $caps Capabilities for meta capability
     * @param  string $cap Capability name
     *
     * @return array  Actual capabilities for meta capability
     */
    public function meta_caps( $caps, $cap ) {

        switch ( $cap ) {
            case 'bbp_settings_block' : // User blocking settings
                $caps = array( bbpress()->admin->minimum_capability );
                break;
        }

        return $caps;
    }

    /**
     * Block a user by username
     *
     * @param  boolean  $permission
     *
     * @return boolean
     */
    function maybe_block_user( $permission ) {
        $user    = wp_get_current_user();
        $blocked = get_option( '_bbp_block_user_list', '' );
        $blocked = explode( "\n", $blocked );
        
		if ( empty( $blocked ) ) {
			return true;
		}

        if ( in_array( $user->user_login, $blocked ) ) {
            return false;
        }

        return $permission;
    }

    /**
     * Register a new settings section
     *
     * @param  array  $sections
     *
     * @return array
     */
    public function admin_settings_section( $sections ) {
        $sections['bbp_settings_block'] = array(
            'title'    => __( 'Forum User Blocking', 'bbp-block-user' ),
            'callback' => array( $this, 'callback_section' ),
        );

        return $sections;
    }

    /**
     * Register settings for the section
     *
     * @param  array  $fields
     *
     * @return array
     */
    public function admin_settings_fields( $fields ) {
        $fields['bbp_settings_block']['_bbp_block_user_list'] = array(
            'title'             => __( 'Block List', 'bbp-block-user' ),
            'callback'          => array( $this, 'callback_blocklist' ),
            'sanitize_callback' => array( $this, 'sanitize_callback' ),
            'args'              => array()
        );

        return $fields;
    }

    /**
     * Settings description
     *
     * @return void
     */
    public function callback_section() {
        echo '<p>';
        esc_html_e( 'Block users from creating topics and replies.', 'bbp-block-user' );
        echo '</p>';
    }

    /**
     * Blocklist settings field
     *
     * @return void
     */
    public function callback_blocklist() {
        ?>
        <textarea name="_bbp_block_user_list" id="bbp-user-block-list" cols="20" rows="10"><?php bbp_form_option( '_bbp_block_user_list' ); ?></textarea>
        <p class="help"><?php _e( 'Enter username (one per line)', 'bbp-block-user' ); ?></p>
        <?php
    }

    /**
     * Sanitize our form input
     *
     * @param  string  $value
     *
     * @return string
     */
    public function sanitize_callback( $value ) {
        if ( empty( $value ) ) {
            return;
        }

        $value = explode( "\n", $value );
        $value = array_map( 'strip_tags', $value );
        $value = array_map( 'trim', $value );

        return implode( "\n", $value );
    }


    /**
     * Plugin action links
     *
     * @param  array  $links
     *
     * @return array
     */
    function plugin_action_links( $links ) {

        $links[] = '<a href="' . admin_url( 'options-general.php?page=bbpress#bbp-user-block-list' ) . '">Settings</a>';

        return $links;
    }
}

new BBP_Block_User();
