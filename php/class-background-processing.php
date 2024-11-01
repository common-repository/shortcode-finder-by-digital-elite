<?php
/**
 * Class Background_Processing
 *
 * @since 1.0.0
 *
 * @package digital_elite\shortcode_finder
 */

namespace digital_elite\shortcode_finder;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Background_Processing functions
 */
class Background_Processing extends \WP_Background_Process {

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	protected function task( $post_id ) {

		$shortcode_list = get_option( DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX . '_shortcode_list', array() );
		$post           = get_post( $post_id );
		$shortcode_list = $this->get_shortcodes_from_content( $shortcode_list, $post->post_content, 'Content', $post_id );
		$meta_data      = get_post_meta( $post_id );

		if ( is_array( $meta_data ) && ! empty( $meta_data ) ) {
			$meta_data      = serialize( $meta_data );
			$shortcode_list = $this->get_shortcodes_from_content( $shortcode_list, $meta_data, 'Meta Data', $post_id );
		}

		update_option( DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX . '_shortcode_list', $shortcode_list );

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();
		// Show notice to user or perform some other arbitrary task...
	}

	/**
	 * Get Shortcodes
	 *
	 * @param  [type] $shortcode_list [description]
	 * @param  [type] $content        [description]
	 * @param  [type] $type           [description]
	 * @param  [type] $post_id        [description]
	 * @return [type]                 [description]
	 */
	public function get_shortcodes_from_content( &$shortcode_list, $content, $type, $post_id ) {

		$regex = '/\[([a-zA-Z][^\]]*)\]/m';

		if ( is_array( $content ) ) {
			return $shortcode_list;
		}

		preg_match_all( $regex, $content, $matches );

		if ( count( $matches ) > 0 ) {
			foreach ( $matches as $match_key => $shortcode ) {
				if ( 0 !== $match_key ) {

					$code = str_replace( '[', '', $shortcode );
					$code = str_replace( ']', '', $code );
					if ( is_array( $code ) && isset( $code[0] ) ) {
						$code = $code[0];
					}
					$inner_code_parts = shortcode_parse_atts( $code );
					$command          = $inner_code_parts[0];
					if ( strlen( $command ) > 2 ) {
						$attributes = array();
						$keys       = array();
						if ( count( $inner_code_parts ) > 1 ) {
							foreach ( $inner_code_parts as $key => $inner_code_part ) {
								if ( 0 !== $key ) {
									$attributes[ $key ] = $inner_code_part;
									$keys[]             = $key;
								}
							}
						}

						$post         = get_post( $post_id );
						$s_code       = '[' . ( is_array( $shortcode ) ? $shortcode[0] : $shortcode ) . ']';
						$add_to_array = true;
						if ( $add_to_array ) {
							$shortcode_list[ $command ][] = array(
								'shortcode'  => $s_code,
								'post_id'    => $post_id,
								'location'   => $type,
								'attributes' => $attributes,
								'title'      => $post->post_title,
							);
						}
					}
				}
			}
		}

		return $shortcode_list;
	}
}
