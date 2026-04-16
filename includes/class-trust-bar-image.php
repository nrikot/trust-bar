<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Trust_Bar_Image {

    /**
     * Process an uploaded image: auto-crop to consistent dimensions.
     *
     * @param int    $attachment_id WP attachment ID
     * @param int    $target_width  Target width in px
     * @param int    $target_height Target height in px
     * @param string $fit           'contain' (letterbox) | 'cover' (crop center) | 'fill'
     * @return string|WP_Error URL of the processed image
     */
    public static function process_logo( $attachment_id, $target_width = 320, $target_height = 160, $fit = 'contain' ) {
        $file = get_attached_file( $attachment_id );
        if ( ! $file || ! file_exists( $file ) ) {
            return new WP_Error( 'missing_file', __( 'Attachment file not found.', 'trust-bar' ) );
        }

        $mime = get_post_mime_type( $attachment_id );

        // SVGs: return as-is (already scalable)
        if ( $mime === 'image/svg+xml' ) {
            return wp_get_attachment_url( $attachment_id );
        }

        // Build a deterministic cache filename
        $info      = pathinfo( $file );
        $cache_name = $info['filename'] . "-tb{$target_width}x{$target_height}-{$fit}." . $info['extension'];
        $cache_path = $info['dirname'] . '/' . $cache_name;
        $upload_dir = wp_get_upload_dir();
        $cache_url  = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $cache_path );

        // Return cached version if exists
        if ( file_exists( $cache_path ) ) {
            return $cache_url;
        }

        // Load the editor
        $editor = wp_get_image_editor( $file );
        if ( is_wp_error( $editor ) ) {
            return $editor;
        }

        $size = $editor->get_size();
        $src_w = $size['width'];
        $src_h = $size['height'];

        if ( $fit === 'cover' ) {
            // Crop to center filling target box
            $src_ratio    = $src_w / $src_h;
            $target_ratio = $target_width / $target_height;
            if ( $src_ratio > $target_ratio ) {
                $crop_h = $src_h;
                $crop_w = (int) round( $src_h * $target_ratio );
            } else {
                $crop_w = $src_w;
                $crop_h = (int) round( $src_w / $target_ratio );
            }
            $x = (int) round( ( $src_w - $crop_w ) / 2 );
            $y = (int) round( ( $src_h - $crop_h ) / 2 );
            $editor->crop( $x, $y, $crop_w, $crop_h );
            $editor->resize( $target_width, $target_height, true );
        } elseif ( $fit === 'contain' ) {
            // Resize to fit within box, preserving ratio
            $editor->resize( $target_width, $target_height, false );
        } else {
            // fill — stretch
            $editor->resize( $target_width, $target_height, true );
        }

        $saved = $editor->save( $cache_path );
        if ( is_wp_error( $saved ) ) {
            return $saved;
        }

        return $cache_url;
    }

    /**
     * Generate a trimmed (whitespace-stripped) version of the logo
     * by trimming transparent/white borders.
     */
    public static function trim_whitespace( $attachment_id ) {
        // GD-based whitespace trim for PNG/JPEG logos
        $file = get_attached_file( $attachment_id );
        if ( ! $file || ! extension_loaded( 'gd' ) ) {
            return wp_get_attachment_url( $attachment_id );
        }

        $mime = get_post_mime_type( $attachment_id );
        switch ( $mime ) {
            case 'image/png':
                $img = @imagecreatefrompng( $file );
                break;
            case 'image/jpeg':
                $img = @imagecreatefromjpeg( $file );
                break;
            case 'image/webp':
                $img = @imagecreatefromwebp( $file );
                break;
            default:
                return wp_get_attachment_url( $attachment_id );
        }

        if ( ! $img ) {
            return wp_get_attachment_url( $attachment_id );
        }

        $w = imagesx( $img );
        $h = imagesy( $img );

        // Detect bounding box
        $min_x = $w; $min_y = $h; $max_x = 0; $max_y = 0;
        for ( $y = 0; $y < $h; $y++ ) {
            for ( $x = 0; $x < $w; $x++ ) {
                $rgba  = imagecolorat( $img, $x, $y );
                $alpha = ( $rgba >> 24 ) & 0x7F;
                $r     = ( $rgba >> 16 ) & 0xFF;
                $g     = ( $rgba >> 8 )  & 0xFF;
                $b     = $rgba & 0xFF;
                $is_white_or_transparent = ( $alpha > 100 ) || ( $r > 240 && $g > 240 && $b > 240 );
                if ( ! $is_white_or_transparent ) {
                    $min_x = min( $min_x, $x );
                    $min_y = min( $min_y, $y );
                    $max_x = max( $max_x, $x );
                    $max_y = max( $max_y, $y );
                }
            }
        }

        if ( $min_x >= $max_x || $min_y >= $max_y ) {
            imagedestroy( $img );
            return wp_get_attachment_url( $attachment_id );
        }

        $new_w  = $max_x - $min_x + 1;
        $new_h  = $max_y - $min_y + 1;
        $result = imagecreatetruecolor( $new_w, $new_h );
        imagealphablending( $result, false );
        imagesavealpha( $result, true );
        $transparent = imagecolorallocatealpha( $result, 0, 0, 0, 127 );
        imagefill( $result, 0, 0, $transparent );
        imagecopy( $result, $img, 0, 0, $min_x, $min_y, $new_w, $new_h );

        $info      = pathinfo( $file );
        $out_path  = $info['dirname'] . '/' . $info['filename'] . '-tb-trimmed.png';
        $upload_dir = wp_get_upload_dir();
        $out_url   = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $out_path );

        imagepng( $result, $out_path );
        imagedestroy( $img );
        imagedestroy( $result );

        return $out_url;
    }
}
