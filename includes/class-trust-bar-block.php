<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Trust_Bar_Block {

    public function __construct() {
        add_action( 'init', array( $this, 'register_block' ) );
    }

    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) return;

        wp_register_script(
            'trust-bar-block-editor',
            TRUST_BAR_URL . 'blocks/index.js',
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
            TRUST_BAR_VERSION,
            true
        );

        $groups = Trust_Bar_DB::get_groups();
        $group_options = array();
        foreach ( $groups as $g ) {
            $group_options[] = array( 'label' => $g->name, 'value' => (int) $g->id );
        }

        wp_localize_script( 'trust-bar-block-editor', 'TrustBarBlock', array(
            'groups' => $group_options,
        ) );

        register_block_type( 'trust-bar/trust-bar', array(
            'editor_script'   => 'trust-bar-block-editor',
            'render_callback' => array( $this, 'render_callback' ),
            'attributes'      => array(
                'groupId'    => array( 'type' => 'integer', 'default' => 1 ),
                'colorMode'  => array( 'type' => 'string',  'default' => '' ),
                'rows'       => array( 'type' => 'integer', 'default' => 0 ),
                'carousel'   => array( 'type' => 'string',  'default' => '' ),
                'extraClass' => array( 'type' => 'string',  'default' => '' ),
            ),
        ) );
    }

    public function render_callback( $attrs ) {
        $group_id = isset( $attrs['groupId'] ) ? (int) $attrs['groupId'] : 1;
        $group    = Trust_Bar_DB::get_group( $group_id );
        if ( ! $group ) return '';

        $settings = $group->settings;
        if ( ! empty( $attrs['colorMode'] ) )  $settings['color_mode'] = $attrs['colorMode'];
        if ( ! empty( $attrs['rows'] ) )        $settings['rows']       = (int) $attrs['rows'];
        if ( $attrs['carousel'] !== '' )        $settings['carousel']   = filter_var( $attrs['carousel'], FILTER_VALIDATE_BOOLEAN );

        $logos = Trust_Bar_DB::get_logos( $group_id );
        return Trust_Bar_Shortcode::render_html( $logos, $settings, $group_id, $attrs['extraClass'] ?? '' );
    }
}
