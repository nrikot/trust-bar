<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Trust_Bar_DB {

    public static function install() {
        global $wpdb;
        $table      = $wpdb->prefix . TRUST_BAR_TABLE;
        $groups     = $wpdb->prefix . 'trust_bar_groups';
        $charset_collate = $wpdb->get_charset_collate();

        $sql_groups = "CREATE TABLE IF NOT EXISTS $groups (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(255)    NOT NULL DEFAULT '',
            settings    LONGTEXT        NOT NULL DEFAULT '{}',
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_logos = "CREATE TABLE IF NOT EXISTS $table (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id    BIGINT UNSIGNED NOT NULL DEFAULT 1,
            sort_order  INT             NOT NULL DEFAULT 0,
            title       VARCHAR(255)    NOT NULL DEFAULT '',
            alt_text    VARCHAR(255)    NOT NULL DEFAULT '',
            link_url    VARCHAR(500)    NOT NULL DEFAULT '',
            link_target VARCHAR(10)     NOT NULL DEFAULT '_self',
            attachment_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            image_url   VARCHAR(500)    NOT NULL DEFAULT '',
            is_active   TINYINT(1)      NOT NULL DEFAULT 1,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY group_id (group_id),
            KEY sort_order (sort_order)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_groups );
        dbDelta( $sql_logos );

        // Insert a default group if none exists
        $existing = $wpdb->get_var( "SELECT COUNT(*) FROM $groups" );
        if ( ! $existing ) {
            $wpdb->insert( $groups, array(
                'name'     => 'Default Trust Bar',
                'settings' => json_encode( self::default_settings() ),
            ) );
        }
    }

    public static function deactivate() {
        // Intentionally does NOT drop tables on deactivation
    }

    public static function default_settings() {
        return array(
            'rows'              => 1,
            'color_mode'        => 'full',    // full | grayscale | mono | dimmed
            'logo_height'       => 60,
            'logo_max_width'    => 160,
            'padding_x'         => 24,
            'padding_y'         => 16,
            'gap'               => 32,
            'carousel'          => true,
            'carousel_auto'     => true,
            'carousel_speed'    => 4000,
            'carousel_transition'=> 600,
            'show_text'         => false,
            'text_position'     => 'below',   // below | above | tooltip
            'heading'           => '',
            'heading_tag'       => 'p',
            'bg_color'          => '',
            'border'            => false,
            'border_color'      => '#e5e7eb',
            'border_radius'     => 8,
            'hover_effect'      => 'color',   // color | scale | none
        );
    }

    public static function get_groups() {
        global $wpdb;
        $table = $wpdb->prefix . 'trust_bar_groups';
        return $wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC" );
    }

    public static function get_group( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'trust_bar_groups';
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
        if ( $row ) {
            $row->settings = json_decode( $row->settings, true );
        }
        return $row;
    }

    public static function save_group( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'trust_bar_groups';
        $settings = json_encode( $data['settings'] );
        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table,
                array( 'name' => $data['name'], 'settings' => $settings ),
                array( 'id'   => (int) $data['id'] )
            );
            return (int) $data['id'];
        } else {
            $wpdb->insert( $table, array( 'name' => $data['name'], 'settings' => $settings ) );
            return $wpdb->insert_id;
        }
    }

    public static function delete_group( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'trust_bar_groups', array( 'id' => (int) $id ) );
        $wpdb->delete( $wpdb->prefix . TRUST_BAR_TABLE, array( 'group_id' => (int) $id ) );
    }

    public static function get_logos( $group_id ) {
        global $wpdb;
        $table = $wpdb->prefix . TRUST_BAR_TABLE;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE group_id = %d AND is_active = 1 ORDER BY sort_order ASC, id ASC",
            $group_id
        ) );
    }

    public static function get_all_logos( $group_id ) {
        global $wpdb;
        $table = $wpdb->prefix . TRUST_BAR_TABLE;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE group_id = %d ORDER BY sort_order ASC, id ASC",
            $group_id
        ) );
    }

    public static function save_logo( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . TRUST_BAR_TABLE;
        $fields = array(
            'group_id'      => (int) $data['group_id'],
            'sort_order'    => isset( $data['sort_order'] ) ? (int) $data['sort_order'] : 0,
            'title'         => sanitize_text_field( $data['title'] ?? '' ),
            'alt_text'      => sanitize_text_field( $data['alt_text'] ?? '' ),
            'link_url'      => esc_url_raw( $data['link_url'] ?? '' ),
            'link_target'   => in_array( $data['link_target'] ?? '_self', array( '_self', '_blank' ) ) ? $data['link_target'] : '_self',
            'attachment_id' => (int) ( $data['attachment_id'] ?? 0 ),
            'image_url'     => esc_url_raw( $data['image_url'] ?? '' ),
            'is_active'     => isset( $data['is_active'] ) ? (int) $data['is_active'] : 1,
        );
        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $fields, array( 'id' => (int) $data['id'] ) );
            return (int) $data['id'];
        } else {
            $wpdb->insert( $table, $fields );
            return $wpdb->insert_id;
        }
    }

    public static function delete_logo( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . TRUST_BAR_TABLE, array( 'id' => (int) $id ) );
    }

    public static function reorder_logos( $group_id, $ids ) {
        global $wpdb;
        $table = $wpdb->prefix . TRUST_BAR_TABLE;
        foreach ( $ids as $order => $id ) {
            $wpdb->update( $table, array( 'sort_order' => $order ), array( 'id' => (int) $id, 'group_id' => (int) $group_id ) );
        }
    }
}
