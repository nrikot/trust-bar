<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Trust_Bar_Shortcode {

    public function __construct() {
        add_shortcode( 'trust_bar', array( $this, 'render' ) );
        add_shortcode( 'trustbar',  array( $this, 'render' ) ); // alias
    }

    /**
     * [trust_bar id="1" color_mode="grayscale" rows="2" carousel="true"]
     */
    public function render( $atts ) {
        $atts = shortcode_atts( array(
            'id'         => 1,
            'color_mode' => null,
            'rows'       => null,
            'carousel'   => null,
            'heading'    => null,
            'class'      => '',
        ), $atts, 'trust_bar' );

        $group = Trust_Bar_DB::get_group( (int) $atts['id'] );
        if ( ! $group ) return '<!-- Trust Bar: group not found -->';

        $settings = $group->settings;

        // Allow shortcode attribute overrides
        if ( $atts['color_mode'] !== null ) $settings['color_mode'] = $atts['color_mode'];
        if ( $atts['rows']       !== null ) $settings['rows']       = (int) $atts['rows'];
        if ( $atts['carousel']   !== null ) $settings['carousel']   = filter_var( $atts['carousel'], FILTER_VALIDATE_BOOLEAN );
        if ( $atts['heading']    !== null ) $settings['heading']    = $atts['heading'];

        $logos = Trust_Bar_DB::get_logos( $group->id );
        return self::render_html( $logos, $settings, $group->id, $atts['class'] );
    }

    /**
     * Core HTML renderer – used by shortcode, block, and widget.
     */
    public static function render_html( $logos, $settings, $group_id = 0, $extra_class = '' ) {
        if ( empty( $logos ) ) return '';

        $s = wp_parse_args( $settings, Trust_Bar_DB::default_settings() );

        $wrapper_id  = 'tb-' . $group_id . '-' . wp_rand( 1000, 9999 );
        $color_class = 'tb-mode-' . esc_attr( $s['color_mode'] );
        $rows_class  = 'tb-rows-' . (int) $s['rows'];
        $extra       = $extra_class ? ' ' . esc_attr( $extra_class ) : '';

        // Inline CSS variables
        $css_vars  = '--tb-logo-height:' . (int) $s['logo_height'] . 'px;';
        $css_vars .= '--tb-logo-max-width:' . (int) $s['logo_max_width'] . 'px;';
        $css_vars .= '--tb-gap:' . (int) $s['gap'] . 'px;';
        $css_vars .= '--tb-pad-x:' . (int) $s['padding_x'] . 'px;';
        $css_vars .= '--tb-pad-y:' . (int) $s['padding_y'] . 'px;';
        $css_vars .= '--tb-transition:' . (int) $s['carousel_transition'] . 'ms;';
        if ( $s['bg_color'] ) $css_vars .= '--tb-bg:' . esc_attr( $s['bg_color'] ) . ';';
        if ( $s['border'] )   $css_vars .= '--tb-border-color:' . esc_attr( $s['border_color'] ) . ';';
        $css_vars .= '--tb-border-radius:' . (int) $s['border_radius'] . 'px;';

        $data_attrs  = ' data-group="' . (int) $group_id . '"';
        $data_attrs .= ' data-carousel="' . ( $s['carousel'] ? '1' : '0' ) . '"';
        $data_attrs .= ' data-auto="' . ( $s['carousel_auto'] ? '1' : '0' ) . '"';
        $data_attrs .= ' data-speed="' . (int) $s['carousel_speed'] . '"';
        $data_attrs .= ' data-rows="' . (int) $s['rows'] . '"';

        $border_class = $s['border'] ? ' tb-has-border' : '';
        $hover_class  = ' tb-hover-' . esc_attr( $s['hover_effect'] );

        ob_start(); ?>
        <div id="<?php echo esc_attr( $wrapper_id ); ?>"
             class="trust-bar-wrap <?php echo $color_class . $border_class . $hover_class . $extra; ?>"
             style="<?php echo esc_attr( $css_vars ); ?>"
             <?php echo $data_attrs; ?>>

            <?php if ( ! empty( $s['heading'] ) ) : ?>
            <<?php echo tag_escape( $s['heading_tag'] ); ?> class="tb-heading">
                <?php echo esc_html( $s['heading'] ); ?>
            </<?php echo tag_escape( $s['heading_tag'] ); ?>>
            <?php endif; ?>

            <div class="tb-track-outer">
                <button class="tb-arrow tb-prev" aria-label="<?php esc_attr_e( 'Previous', 'trust-bar' ); ?>">&#8249;</button>
                <div class="tb-viewport">
                    <div class="tb-track <?php echo $rows_class; ?>">
                        <?php foreach ( $logos as $logo ) : ?>
                        <div class="tb-item">
                            <?php if ( $logo->link_url ) : ?>
                            <a href="<?php echo esc_url( $logo->link_url ); ?>"
                               target="<?php echo esc_attr( $logo->link_target ); ?>"
                               rel="<?php echo $logo->link_target === '_blank' ? 'noopener noreferrer' : ''; ?>"
                               <?php echo $logo->title ? 'title="' . esc_attr( $logo->title ) . '"' : ''; ?>>
                            <?php endif; ?>

                            <img src="<?php echo esc_url( $logo->image_url ); ?>"
                                 alt="<?php echo esc_attr( $logo->alt_text ?: $logo->title ); ?>"
                                 loading="lazy"
                                 class="tb-logo-img" />

                            <?php if ( $s['show_text'] && $logo->title ) : ?>
                            <span class="tb-logo-title tb-text-<?php echo esc_attr( $s['text_position'] ); ?>">
                                <?php echo esc_html( $logo->title ); ?>
                            </span>
                            <?php endif; ?>

                            <?php if ( $logo->link_url ) : ?></a><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div><!-- .tb-track -->
                </div><!-- .tb-viewport -->
                <button class="tb-arrow tb-next" aria-label="<?php esc_attr_e( 'Next', 'trust-bar' ); ?>">&#8250;</button>
            </div><!-- .tb-track-outer -->

            <div class="tb-dots" aria-label="<?php esc_attr_e( 'Carousel navigation', 'trust-bar' ); ?>"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
