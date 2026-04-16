<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Trust_Bar_Admin {

    public function __construct() {
        add_action( 'admin_menu',             array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_tb_save_logo',   array( $this, 'ajax_save_logo' ) );
        add_action( 'wp_ajax_tb_delete_logo', array( $this, 'ajax_delete_logo' ) );
        add_action( 'wp_ajax_tb_reorder',     array( $this, 'ajax_reorder' ) );
        add_action( 'wp_ajax_tb_save_group',  array( $this, 'ajax_save_group' ) );
        add_action( 'wp_ajax_tb_delete_group',array( $this, 'ajax_delete_group' ) );
        add_action( 'wp_ajax_tb_process_img', array( $this, 'ajax_process_img' ) );
        add_action( 'wp_ajax_tb_toggle_logo', array( $this, 'ajax_toggle_logo' ) );
    }

    public function register_menu() {
        add_menu_page(
            __( 'Trust Bar', 'trust-bar' ),
            __( 'Trust Bar', 'trust-bar' ),
            'manage_options',
            'trust-bar',
            array( $this, 'render_page' ),
            'dashicons-awards',
            58
        );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'trust-bar' ) === false ) return;

        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_style( 'trust-bar-admin', TRUST_BAR_URL . 'assets/css/admin.css', array(), TRUST_BAR_VERSION );
        wp_enqueue_script( 'trust-bar-admin', TRUST_BAR_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), TRUST_BAR_VERSION, true );
        wp_localize_script( 'trust-bar-admin', 'TrustBarAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'trust_bar_admin' ),
            'strings' => array(
                'confirmDelete'  => __( 'Are you sure you want to delete this logo?', 'trust-bar' ),
                'confirmDeleteGroup' => __( 'Delete this entire group and all its logos?', 'trust-bar' ),
                'saved'          => __( 'Saved!', 'trust-bar' ),
                'error'          => __( 'Error. Please try again.', 'trust-bar' ),
                'uploading'      => __( 'Uploading…', 'trust-bar' ),
                'processing'     => __( 'Processing image…', 'trust-bar' ),
                'selectImage'    => __( 'Select Logo Image', 'trust-bar' ),
                'useImage'       => __( 'Use This Image', 'trust-bar' ),
            ),
        ) );
    }

    public function render_page() {
        $groups  = Trust_Bar_DB::get_groups();
        $active  = isset( $_GET['group'] ) ? (int) $_GET['group'] : ( $groups ? $groups[0]->id : 0 );
        $group   = $active ? Trust_Bar_DB::get_group( $active ) : null;
        $logos   = $active ? Trust_Bar_DB::get_all_logos( $active ) : array();
        include TRUST_BAR_DIR . 'templates/admin-page.php';
    }

    /* ─── AJAX handlers ─── */

    private function check_nonce() {
        check_ajax_referer( 'trust_bar_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
    }

    public function ajax_save_logo() {
        $this->check_nonce();
        $data = array(
            'id'            => (int) ( $_POST['id'] ?? 0 ),
            'group_id'      => (int) ( $_POST['group_id'] ?? 1 ),
            'sort_order'    => (int) ( $_POST['sort_order'] ?? 0 ),
            'title'         => sanitize_text_field( $_POST['title'] ?? '' ),
            'alt_text'      => sanitize_text_field( $_POST['alt_text'] ?? '' ),
            'link_url'      => esc_url_raw( $_POST['link_url'] ?? '' ),
            'link_target'   => sanitize_text_field( $_POST['link_target'] ?? '_self' ),
            'attachment_id' => (int) ( $_POST['attachment_id'] ?? 0 ),
            'image_url'     => esc_url_raw( $_POST['image_url'] ?? '' ),
            'is_active'     => (int) ( $_POST['is_active'] ?? 1 ),
        );
        $id = Trust_Bar_DB::save_logo( $data );
        wp_send_json_success( array( 'id' => $id ) );
    }

    public function ajax_delete_logo() {
        $this->check_nonce();
        Trust_Bar_DB::delete_logo( (int) $_POST['id'] );
        wp_send_json_success();
    }

    public function ajax_toggle_logo() {
        $this->check_nonce();
        global $wpdb;
        $id    = (int) $_POST['id'];
        $state = (int) $_POST['is_active'];
        $wpdb->update( $wpdb->prefix . TRUST_BAR_TABLE, array( 'is_active' => $state ), array( 'id' => $id ) );
        wp_send_json_success();
    }

    public function ajax_reorder() {
        $this->check_nonce();
        $ids      = array_map( 'intval', $_POST['ids'] );
        $group_id = (int) $_POST['group_id'];
        Trust_Bar_DB::reorder_logos( $group_id, $ids );
        wp_send_json_success();
    }

    public function ajax_save_group() {
        $this->check_nonce();
        $settings = array();
        $defaults = Trust_Bar_DB::default_settings();
        $post_settings = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? $_POST['settings'] : array();
        foreach ( $defaults as $key => $default ) {
            if ( isset( $post_settings[ $key ] ) ) {
                $val = $post_settings[ $key ];
                $settings[ $key ] = is_int( $default ) ? (int) $val : ( is_bool( $default ) ? (bool) $val : sanitize_text_field( $val ) );
            } else {
                $settings[ $key ] = $default;
            }
        }
        // Boolean handling
        foreach ( array( 'carousel', 'carousel_auto', 'show_text', 'border' ) as $bool_key ) {
            $settings[ $bool_key ] = isset( $post_settings[ $bool_key ] ) && $post_settings[ $bool_key ] === '1';
        }
        $data = array(
            'id'       => (int) ( $_POST['id'] ?? 0 ),
            'name'     => sanitize_text_field( $_POST['name'] ?? 'Trust Bar' ),
            'settings' => $settings,
        );
        $id = Trust_Bar_DB::save_group( $data );
        wp_send_json_success( array( 'id' => $id ) );
    }

    public function ajax_delete_group() {
        $this->check_nonce();
        Trust_Bar_DB::delete_group( (int) $_POST['id'] );
        wp_send_json_success();
    }

    public function ajax_process_img() {
        $this->check_nonce();
        $attachment_id = (int) $_POST['attachment_id'];
        $width         = (int) ( $_POST['width'] ?? 320 );
        $height        = (int) ( $_POST['height'] ?? 160 );
        $fit           = sanitize_text_field( $_POST['fit'] ?? 'contain' );
        $trim          = ! empty( $_POST['trim'] );

        if ( $trim ) {
            $url = Trust_Bar_Image::trim_whitespace( $attachment_id );
        } else {
            $url = Trust_Bar_Image::process_logo( $attachment_id, $width, $height, $fit );
        }

        if ( is_wp_error( $url ) ) {
            wp_send_json_error( $url->get_error_message() );
        }
        wp_send_json_success( array( 'url' => $url ) );
    }
}
