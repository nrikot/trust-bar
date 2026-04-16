<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Trust_Bar_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'trust_bar_widget',
            __( 'Trust Bar', 'trust-bar' ),
            array( 'description' => __( 'Display a Trust Bar logo strip.', 'trust-bar' ) )
        );
        add_action( 'widgets_init', array( $this, 'register' ) );
    }

    public function register() {
        register_widget( 'Trust_Bar_Widget' );
    }

    public function widget( $args, $instance ) {
        $group_id = ! empty( $instance['group_id'] ) ? (int) $instance['group_id'] : 1;
        $group    = Trust_Bar_DB::get_group( $group_id );
        if ( ! $group ) return;

        $settings = $group->settings;
        if ( ! empty( $instance['color_mode'] ) ) $settings['color_mode'] = $instance['color_mode'];

        $logos = Trust_Bar_DB::get_logos( $group_id );

        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }
        echo Trust_Bar_Shortcode::render_html( $logos, $settings, $group_id );
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title      = $instance['title']      ?? '';
        $group_id   = $instance['group_id']   ?? 1;
        $color_mode = $instance['color_mode'] ?? '';
        $groups     = Trust_Bar_DB::get_groups();
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Widget Title:', 'trust-bar' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'group_id' ); ?>"><?php esc_html_e( 'Trust Bar Group:', 'trust-bar' ); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id( 'group_id' ); ?>"
                    name="<?php echo $this->get_field_name( 'group_id' ); ?>">
                <?php foreach ( $groups as $g ) : ?>
                <option value="<?php echo (int) $g->id; ?>" <?php selected( $group_id, $g->id ); ?>>
                    <?php echo esc_html( $g->name ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'color_mode' ); ?>"><?php esc_html_e( 'Color Mode Override:', 'trust-bar' ); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id( 'color_mode' ); ?>"
                    name="<?php echo $this->get_field_name( 'color_mode' ); ?>">
                <option value=""><?php esc_html_e( '— Use group setting —', 'trust-bar' ); ?></option>
                <option value="full"       <?php selected( $color_mode, 'full' ); ?>><?php esc_html_e( 'Full Color', 'trust-bar' ); ?></option>
                <option value="grayscale"  <?php selected( $color_mode, 'grayscale' ); ?>><?php esc_html_e( 'Grayscale', 'trust-bar' ); ?></option>
                <option value="mono"       <?php selected( $color_mode, 'mono' ); ?>><?php esc_html_e( 'Monochrome', 'trust-bar' ); ?></option>
                <option value="dimmed"     <?php selected( $color_mode, 'dimmed' ); ?>><?php esc_html_e( 'Dimmed', 'trust-bar' ); ?></option>
            </select>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        return array(
            'title'      => sanitize_text_field( $new_instance['title'] ),
            'group_id'   => (int) $new_instance['group_id'],
            'color_mode' => sanitize_text_field( $new_instance['color_mode'] ),
        );
    }
}
