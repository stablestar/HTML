<?php
/*
Plugin Name: WP Ticket System
Description: A simple ticket management system integrated with WordPress.
Version: 1.0.0
Author: ChatGPT
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . "includes/functions.php";
require_once plugin_dir_path(__FILE__) . "includes/class-email-ingest.php";
require_once plugin_dir_path(__FILE__) . "includes/class-ticket-api.php";
class WPTS_Plugin {
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( "add_meta_boxes", array( $this, "add_meta_boxes" ) );
        add_action( "save_post_wpts_ticket", array( $this, "save_ticket_meta" ) );
        add_filter( "manage_wpts_ticket_posts_columns", array( $this, "set_columns" ) );
        add_action( "manage_wpts_ticket_posts_custom_column", array( $this, "render_columns" ), 10, 2 );
        $this->email_ingest = new WPTS_Email_Ingest();
        $this->ticket_api = new WPTS_Ticket_API();
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
    }

    public function register_post_type() {
        register_post_type( 'wpts_ticket', array(
            'labels' => array(
                'name' => __( 'Tickets', 'wpts' ),
                'singular_name' => __( 'Ticket', 'wpts' ),
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array( 'title', 'editor', 'author' ),
        ) );
    }

    public function register_taxonomies() {
        register_taxonomy( 'wpts_priority', 'wpts_ticket', array(
            'labels' => array(
                'name' => __( 'Priority', 'wpts' ),
                'singular_name' => __( 'Priority', 'wpts' ),
            ),
            'public' => false,
            'show_ui' => true,
            'hierarchical' => false,
        ) );
    }

    public function add_admin_pages() {
        add_submenu_page(
            'edit.php?post_type=wpts_ticket',
            __( 'Ticket Settings', 'wpts' ),
            __( 'Settings', 'wpts' ),
            'manage_options',
            'wpts-settings',
            array( $this, 'settings_page' )
        );
    }


    public function settings_page() {
        echo '<div class="wrap"><h1>WP Ticket System</h1>';
        echo '<p>Settings will appear here.</p></div>';
    }

    public function add_meta_boxes() {
        add_meta_box(
            'wpts_details',
            __('Ticket Details', 'wpts'),
            array($this, 'render_meta_box'),
            'wpts_ticket',
            'side'
        );
    }

    public function render_meta_box($post) {
        $assigned = get_post_meta($post->ID, 'wpts_assigned', true);
        $priority = wp_get_object_terms($post->ID, 'wpts_priority', array('fields' => 'ids'));
        wp_nonce_field('wpts_save_ticket', 'wpts_nonce');
        echo '<p><label>Assigned to:</label><br>';
        wp_dropdown_users(array('name' => 'wpts_assigned', 'selected' => $assigned));
        echo '</p><p><label>Priority:</label><br>';
        wp_dropdown_categories(array('taxonomy' => 'wpts_priority', 'hide_empty' => false, 'name' => 'wpts_priority', 'selected' => ($priority ? $priority[0] : 0)));
        echo '</p>';
    }

    public function save_ticket_meta($post_id) {
        if ( ! isset($_POST['wpts_nonce']) || ! wp_verify_nonce($_POST['wpts_nonce'], 'wpts_save_ticket') ) {
            return;
        }
        if ( isset($_POST['wpts_assigned']) ) {
            update_post_meta($post_id, 'wpts_assigned', intval($_POST['wpts_assigned']));
        }
        if ( isset($_POST['wpts_priority']) ) {
            wp_set_object_terms($post_id, intval($_POST['wpts_priority']), 'wpts_priority');
        }
    }

    public function set_columns($cols) {
        $cols['priority'] = __('Priority', 'wpts');
        $cols['assigned'] = __('Assigned', 'wpts');
        return $cols;
    }

    public function render_columns($column, $post_id) {
        if ( $column === 'priority' ) {
            $terms = get_the_terms($post_id, 'wpts_priority');
            if ( $terms ) {
                $t = array_shift($terms);
                echo '<span style="background:#' . substr(md5($t->slug), 0, 6) . ';color:#fff;padding:2px 6px;border-radius:4px">' . esc_html($t->name) . '</span>';
            }
        } elseif ( $column === 'assigned' ) {
            $uid = get_post_meta($post_id, 'wpts_assigned', true);
            if ( $uid ) {
                $user = get_user_by('id', $uid);
                echo esc_html($user->display_name);
            }
        }
    }
}

register_activation_hook(__FILE__, 'wpts_activate');
register_deactivation_hook(__FILE__, 'wpts_deactivate');
function wpts_activate() {
    $plugin = new WPTS_Email_Ingest();
    $plugin->schedule_event();
}
function wpts_deactivate() {
    wp_clear_scheduled_hook('wpts_check_inbox');
}
new WPTS_Plugin();
