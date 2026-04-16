<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap tb-admin-wrap">

    <div class="tb-admin-header">
        <h1 class="tb-admin-title">
            <span class="dashicons dashicons-awards"></span>
            <?php esc_html_e( 'Trust Bar', 'trust-bar' ); ?>
        </h1>
        <p class="tb-admin-sub"><?php esc_html_e( 'Manage logo strips for your website. Use shortcodes, Gutenberg blocks, or widgets to place them anywhere.', 'trust-bar' ); ?></p>
    </div>

    <!-- Group tabs -->
    <div class="tb-groups-bar">
        <ul class="tb-group-tabs">
            <?php foreach ( $groups as $g ) : ?>
            <li class="tb-group-tab <?php echo $active === $g->id ? 'active' : ''; ?>">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=trust-bar&group=' . $g->id ) ); ?>">
                    <?php echo esc_html( $g->name ); ?>
                    <code class="tb-shortcode-hint">[trust_bar id="<?php echo (int) $g->id; ?>"]</code>
                </a>
            </li>
            <?php endforeach; ?>
            <li class="tb-group-tab tb-group-add">
                <a href="#" id="tb-add-group"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'New Group', 'trust-bar' ); ?></a>
            </li>
        </ul>
    </div>

    <?php if ( $group ) : ?>
    <div class="tb-admin-layout">

        <!-- Left: Logo manager -->
        <div class="tb-panel tb-panel-logos">
            <div class="tb-panel-header">
                <h2><?php esc_html_e( 'Logos', 'trust-bar' ); ?></h2>
                <button type="button" class="button button-primary tb-add-logo" data-group="<?php echo (int) $group->id; ?>">
                    <span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Logo', 'trust-bar' ); ?>
                </button>
            </div>

            <!-- Preview -->
            <div class="tb-live-preview-wrap">
                <div class="tb-live-preview-label"><?php esc_html_e( 'Live Preview', 'trust-bar' ); ?></div>
                <div class="tb-live-preview" id="tb-preview-<?php echo (int) $group->id; ?>">
                    <?php echo Trust_Bar_Shortcode::render_html( $logos, $group->settings, $group->id ); ?>
                </div>
            </div>

            <!-- Sortable logo grid -->
            <ul class="tb-logo-list" id="tb-logo-list-<?php echo (int) $group->id; ?>" data-group="<?php echo (int) $group->id; ?>">
                <?php if ( empty( $logos ) ) : ?>
                <li class="tb-logo-empty"><em><?php esc_html_e( 'No logos yet. Click "Add Logo" to get started.', 'trust-bar' ); ?></em></li>
                <?php else : ?>
                <?php foreach ( $logos as $logo ) : ?>
                <li class="tb-logo-item <?php echo $logo->is_active ? '' : 'tb-inactive'; ?>" data-id="<?php echo (int) $logo->id; ?>">
                    <span class="tb-drag-handle dashicons dashicons-menu"></span>
                    <div class="tb-logo-thumb">
                        <?php if ( $logo->image_url ) : ?>
                        <img src="<?php echo esc_url( $logo->image_url ); ?>" alt="">
                        <?php else : ?>
                        <span class="tb-no-img dashicons dashicons-format-image"></span>
                        <?php endif; ?>
                    </div>
                    <div class="tb-logo-info">
                        <strong><?php echo esc_html( $logo->title ?: __( '(no title)', 'trust-bar' ) ); ?></strong>
                        <?php if ( $logo->link_url ) : ?>
                        <em><?php echo esc_url( $logo->link_url ); ?></em>
                        <?php endif; ?>
                    </div>
                    <div class="tb-logo-actions">
                        <label class="tb-toggle" title="<?php esc_attr_e( 'Active/Inactive', 'trust-bar' ); ?>">
                            <input type="checkbox" class="tb-logo-active" <?php checked( $logo->is_active, 1 ); ?> data-id="<?php echo (int) $logo->id; ?>">
                            <span class="tb-toggle-slider"></span>
                        </label>
                        <button type="button" class="button button-small tb-edit-logo" data-logo='<?php echo esc_attr( json_encode( $logo ) ); ?>'>
                            <?php esc_html_e( 'Edit', 'trust-bar' ); ?>
                        </button>
                        <button type="button" class="button button-small button-link-delete tb-delete-logo" data-id="<?php echo (int) $logo->id; ?>">
                            <?php esc_html_e( 'Delete', 'trust-bar' ); ?>
                        </button>
                    </div>
                </li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Right: Settings -->
        <div class="tb-panel tb-panel-settings">
            <div class="tb-panel-header">
                <h2><?php esc_html_e( 'Group Settings', 'trust-bar' ); ?></h2>
                <div class="tb-shortcode-box">
                    <strong><?php esc_html_e( 'Shortcode:', 'trust-bar' ); ?></strong>
                    <code>[trust_bar id="<?php echo (int) $group->id; ?>"]</code>
                    <button type="button" class="tb-copy-shortcode button button-small" data-code='[trust_bar id="<?php echo (int) $group->id; ?>"]'>
                        <?php esc_html_e( 'Copy', 'trust-bar' ); ?>
                    </button>
                </div>
            </div>

            <form id="tb-settings-form" data-group="<?php echo (int) $group->id; ?>">
                <?php $s = $group->settings; $d = Trust_Bar_DB::default_settings(); ?>

                <div class="tb-form-section">
                    <h3><?php esc_html_e( 'Group Name', 'trust-bar' ); ?></h3>
                    <input type="text" name="name" class="widefat" value="<?php echo esc_attr( $group->name ); ?>">
                </div>

                <div class="tb-form-section">
                    <h3><?php esc_html_e( 'Layout', 'trust-bar' ); ?></h3>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Rows of logos', 'trust-bar' ); ?></label>
                        <select name="settings[rows]">
                            <?php for ( $r = 1; $r <= 5; $r++ ) : ?>
                            <option value="<?php echo $r; ?>" <?php selected( $s['rows'], $r ); ?>><?php echo $r; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Logo height (px)', 'trust-bar' ); ?></label>
                        <input type="number" name="settings[logo_height]" value="<?php echo (int) $s['logo_height']; ?>" min="20" max="300" class="tb-small-input">
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Logo max-width (px)', 'trust-bar' ); ?></label>
                        <input type="number" name="settings[logo_max_width]" value="<?php echo (int) $s['logo_max_width']; ?>" min="40" max="500" class="tb-small-input">
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Gap between logos (px)', 'trust-bar' ); ?></label>
                        <input type="number" name="settings[gap]" value="<?php echo (int) $s['gap']; ?>" min="0" max="200" class="tb-small-input">
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Padding horizontal (px)', 'trust-bar' ); ?></label>
                        <input type="number" name="settings[padding_x]" value="<?php echo (int) $s['padding_x']; ?>" min="0" max="200" class="tb-small-input">
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Padding vertical (px)', 'trust-bar' ); ?></label>
                        <input type="number" name="settings[padding_y]" value="<?php echo (int) $s['padding_y']; ?>" min="0" max="200" class="tb-small-input">
                    </div>
                </div>

                <div class="tb-form-section">
                    <h3><?php esc_html_e( 'Color Mode', 'trust-bar' ); ?></h3>
                    <div class="tb-color-mode-grid">
                        <?php foreach ( array( 'full' => 'Full Color', 'grayscale' => 'Grayscale', 'mono' => 'Monochrome', 'dimmed' => 'Dimmed' ) as $val => $lbl ) : ?>
                        <label class="tb-color-mode-option <?php echo $s['color_mode'] === $val ? 'active' : ''; ?>">
                            <input type="radio" name="settings[color_mode]" value="<?php echo $val; ?>" <?php checked( $s['color_mode'], $val ); ?>>
                            <span class="tb-cm-icon tb-cm-<?php echo $val; ?>"></span>
                            <span><?php esc_html_e( $lbl, 'trust-bar' ); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Hover effect', 'trust-bar' ); ?></label>
                        <select name="settings[hover_effect]">
                            <option value="color" <?php selected( $s['hover_effect'], 'color' ); ?>><?php esc_html_e( 'Reveal color', 'trust-bar' ); ?></option>
                            <option value="scale" <?php selected( $s['hover_effect'], 'scale' ); ?>><?php esc_html_e( 'Scale up', 'trust-bar' ); ?></option>
                            <option value="none"  <?php selected( $s['hover_effect'], 'none' ); ?>><?php esc_html_e( 'None', 'trust-bar' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="tb-form-section">
                    <h3><?php esc_html_e( 'Carousel', 'trust-bar' ); ?></h3>
                    <div class="tb-form-row tb-form-toggle">
                        <label><?php esc_html_e( 'Enable carousel', 'trust-bar' ); ?></label>
                        <label class="tb-toggle">
                            <input type="checkbox" name="settings[carousel]" value="1" <?php checked( $s['carousel'] ); ?>>
                            <span class="tb-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="tb-form-row tb-form-toggle">
                        <label><?php esc_html_e( 'Auto-play', 'trust-bar' ); ?></label>
                        <label class="tb-toggle">
                            <input type="checkbox" name="settings[carousel_auto]" value="1" <?php checked( $s['carousel_auto'] ); ?>>
                            <span class="tb-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Auto-play interval (ms)', 'trust-bar' ); ?></label>
                        <input type="number" name="settings[carousel_speed]" value="<?php echo (int) $s['carousel_speed']; ?>" min="500" max="30000" step="500" class="tb-small-input">
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Transition duration (ms)', 'trust-bar' ); ?></label>
                        <input type="number" name="settings[carousel_transition]" value="<?php echo (int) $s['carousel_transition']; ?>" min="100" max="2000" step="50" class="tb-small-input">
                    </div>
                </div>

                <div class="tb-form-section">
                    <h3><?php esc_html_e( 'Text & Labels', 'trust-bar' ); ?></h3>
                    <div class="tb-form-row tb-form-toggle">
                        <label><?php esc_html_e( 'Show logo title text', 'trust-bar' ); ?></label>
                        <label class="tb-toggle">
                            <input type="checkbox" name="settings[show_text]" value="1" <?php checked( $s['show_text'] ); ?>>
                            <span class="tb-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Text position', 'trust-bar' ); ?></label>
                        <select name="settings[text_position]">
                            <option value="below"   <?php selected( $s['text_position'], 'below' ); ?>><?php esc_html_e( 'Below logo', 'trust-bar' ); ?></option>
                            <option value="above"   <?php selected( $s['text_position'], 'above' ); ?>><?php esc_html_e( 'Above logo', 'trust-bar' ); ?></option>
                            <option value="tooltip" <?php selected( $s['text_position'], 'tooltip' ); ?>><?php esc_html_e( 'Tooltip on hover', 'trust-bar' ); ?></option>
                        </select>
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Section heading', 'trust-bar' ); ?></label>
                        <input type="text" name="settings[heading]" class="widefat" value="<?php echo esc_attr( $s['heading'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Trusted by leading brands', 'trust-bar' ); ?>">
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Heading tag', 'trust-bar' ); ?></label>
                        <select name="settings[heading_tag]">
                            <?php foreach ( array( 'p', 'h2', 'h3', 'h4', 'h5', 'div' ) as $tag ) : ?>
                            <option value="<?php echo $tag; ?>" <?php selected( $s['heading_tag'], $tag ); ?>><?php echo esc_html( '<' . $tag . '>' ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="tb-form-section">
                    <h3><?php esc_html_e( 'Appearance', 'trust-bar' ); ?></h3>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Background color', 'trust-bar' ); ?></label>
                        <input type="text" name="settings[bg_color]" class="tb-color-picker" value="<?php echo esc_attr( $s['bg_color'] ); ?>">
                    </div>
                    <div class="tb-form-row tb-form-toggle">
                        <label><?php esc_html_e( 'Show border', 'trust-bar' ); ?></label>
                        <label class="tb-toggle">
                            <input type="checkbox" name="settings[border]" value="1" <?php checked( $s['border'] ); ?>>
                            <span class="tb-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Border color', 'trust-bar' ); ?></label>
                        <input type="text" name="settings[border_color]" class="tb-color-picker" value="<?php echo esc_attr( $s['border_color'] ); ?>">
                    </div>
                    <div class="tb-form-row">
                        <label><?php esc_html_e( 'Border radius (px)', 'trust-bar' ); ?></label>
                        <input type="number" name="settings[border_radius]" value="<?php echo (int) $s['border_radius']; ?>" min="0" max="100" class="tb-small-input">
                    </div>
                </div>

                <div class="tb-form-actions">
                    <button type="submit" class="button button-primary button-large" id="tb-save-settings">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e( 'Save Settings', 'trust-bar' ); ?>
                    </button>
                    <button type="button" class="button button-link-delete tb-delete-group" data-id="<?php echo (int) $group->id; ?>">
                        <?php esc_html_e( 'Delete Group', 'trust-bar' ); ?>
                    </button>
                </div>
                <div class="tb-save-notice" id="tb-save-notice" style="display:none;"></div>
            </form>
        </div><!-- .tb-panel-settings -->

    </div><!-- .tb-admin-layout -->
    <?php else : ?>
    <div class="notice notice-info"><p><?php esc_html_e( 'Create your first group by clicking "New Group" above.', 'trust-bar' ); ?></p></div>
    <?php endif; ?>

</div><!-- .tb-admin-wrap -->

<!-- Add/Edit Logo Modal -->
<div id="tb-logo-modal" class="tb-modal" style="display:none;">
    <div class="tb-modal-overlay"></div>
    <div class="tb-modal-box">
        <div class="tb-modal-header">
            <h2 class="tb-modal-title"><?php esc_html_e( 'Add / Edit Logo', 'trust-bar' ); ?></h2>
            <button type="button" class="tb-modal-close">&times;</button>
        </div>
        <form id="tb-logo-form">
            <input type="hidden" name="id" id="tb-logo-id" value="">
            <input type="hidden" name="group_id" id="tb-logo-group-id" value="">
            <input type="hidden" name="attachment_id" id="tb-logo-attachment-id" value="">
            <input type="hidden" name="image_url" id="tb-logo-image-url" value="">

            <div class="tb-modal-body">
                <!-- Image upload area -->
                <div class="tb-upload-area" id="tb-upload-area">
                    <div class="tb-upload-preview" id="tb-upload-preview">
                        <span class="dashicons dashicons-format-image"></span>
                        <p><?php esc_html_e( 'Click to upload or drag an image here', 'trust-bar' ); ?></p>
                    </div>
                    <div class="tb-upload-actions">
                        <button type="button" class="button" id="tb-choose-image">
                            <?php esc_html_e( 'Choose Image', 'trust-bar' ); ?>
                        </button>
                        <button type="button" class="button" id="tb-remove-image" style="display:none;">
                            <?php esc_html_e( 'Remove', 'trust-bar' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Auto-crop settings -->
                <div class="tb-crop-settings">
                    <details>
                        <summary><?php esc_html_e( 'Image Processing Options', 'trust-bar' ); ?></summary>
                        <div class="tb-crop-inner">
                            <div class="tb-form-row tb-form-toggle">
                                <label><?php esc_html_e( 'Auto-trim whitespace', 'trust-bar' ); ?></label>
                                <label class="tb-toggle">
                                    <input type="checkbox" id="tb-trim-whitespace" checked>
                                    <span class="tb-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="tb-form-row tb-form-toggle">
                                <label><?php esc_html_e( 'Normalize size', 'trust-bar' ); ?></label>
                                <label class="tb-toggle">
                                    <input type="checkbox" id="tb-normalize-size" checked>
                                    <span class="tb-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="tb-form-row">
                                <label><?php esc_html_e( 'Fit mode', 'trust-bar' ); ?></label>
                                <select id="tb-fit-mode">
                                    <option value="contain"><?php esc_html_e( 'Contain (letterbox)', 'trust-bar' ); ?></option>
                                    <option value="cover"><?php esc_html_e( 'Cover (crop center)', 'trust-bar' ); ?></option>
                                </select>
                            </div>
                            <button type="button" class="button" id="tb-process-image"><?php esc_html_e( 'Process Image', 'trust-bar' ); ?></button>
                        </div>
                    </details>
                </div>

                <div class="tb-form-row">
                    <label><?php esc_html_e( 'Title / Label', 'trust-bar' ); ?></label>
                    <input type="text" name="title" id="tb-logo-title" class="widefat" placeholder="<?php esc_attr_e( 'Company Name', 'trust-bar' ); ?>">
                </div>
                <div class="tb-form-row">
                    <label><?php esc_html_e( 'Alt text', 'trust-bar' ); ?></label>
                    <input type="text" name="alt_text" id="tb-logo-alt" class="widefat" placeholder="<?php esc_attr_e( 'Logo alt text for accessibility', 'trust-bar' ); ?>">
                </div>
                <div class="tb-form-row">
                    <label><?php esc_html_e( 'Link URL', 'trust-bar' ); ?></label>
                    <input type="url" name="link_url" id="tb-logo-link" class="widefat" placeholder="https://">
                </div>
                <div class="tb-form-row">
                    <label><?php esc_html_e( 'Link target', 'trust-bar' ); ?></label>
                    <select name="link_target" id="tb-logo-target">
                        <option value="_self"><?php esc_html_e( 'Same window', 'trust-bar' ); ?></option>
                        <option value="_blank"><?php esc_html_e( 'New window/tab', 'trust-bar' ); ?></option>
                    </select>
                </div>
                <div class="tb-form-row tb-form-toggle">
                    <label><?php esc_html_e( 'Active', 'trust-bar' ); ?></label>
                    <label class="tb-toggle">
                        <input type="checkbox" name="is_active" id="tb-logo-active" value="1" checked>
                        <span class="tb-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="tb-modal-footer">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Logo', 'trust-bar' ); ?></button>
                <button type="button" class="button tb-modal-cancel"><?php esc_html_e( 'Cancel', 'trust-bar' ); ?></button>
                <span class="tb-modal-spinner spinner"></span>
            </div>
        </form>
    </div>
</div>

<!-- New Group Modal -->
<div id="tb-group-modal" class="tb-modal" style="display:none;">
    <div class="tb-modal-overlay"></div>
    <div class="tb-modal-box tb-modal-small">
        <div class="tb-modal-header">
            <h2 class="tb-modal-title"><?php esc_html_e( 'New Group', 'trust-bar' ); ?></h2>
            <button type="button" class="tb-modal-close">&times;</button>
        </div>
        <form id="tb-group-form">
            <div class="tb-modal-body">
                <div class="tb-form-row">
                    <label><?php esc_html_e( 'Group Name', 'trust-bar' ); ?></label>
                    <input type="text" name="name" id="tb-new-group-name" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Partner Logos', 'trust-bar' ); ?>">
                </div>
            </div>
            <div class="tb-modal-footer">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Create Group', 'trust-bar' ); ?></button>
            </div>
        </form>
    </div>
</div>
