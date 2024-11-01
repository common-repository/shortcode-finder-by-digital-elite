<?php
/**
 * Class AJAX
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
 * AJAX functions
 */
class Ajax {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {}

	/**
	 * Run all of the plugin functions.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		add_action( 'init', array( $this, 'register_ajax_calls' ) );
	}

	/**
	 * Register Ajax Calls
	 */
	public function register_ajax_calls() {
		add_action( 'wp_ajax_get_all_posts', array( $this, 'get_all_posts' ) );
		add_action( 'wp_ajax_nopriv_get_all_posts', array( $this, 'get_all_posts' ) );
		add_action( 'wp_ajax_has_shortcode', array( $this, 'has_shortcode' ) );
		add_action( 'wp_ajax_nopriv_has_shortcode', array( $this, 'has_shortcode' ) );
		add_action( 'wp_ajax_build_table', array( $this, 'build_table' ) );
		add_action( 'wp_ajax_nopriv_build_table', array( $this, 'build_table' ) );
		add_action( 'wp_ajax_save_list_filter', array( $this, 'save_list_filter' ) );
		add_action( 'wp_ajax_nopriv_save_list_filter', array( $this, 'save_list_filter' ) );
	}

	/**
	 * Get All Posts
	 */
	public function get_all_posts() {

		update_option( DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX . '_shortcode_list', array() );

		$post_types = get_post_types( '', 'names' );
		$post_ids   = array();
		$query      = array(
			'posts_per_page' => -1,
			'post_type'      => $post_types,
			'post_status'    => 'publish',
		);
		$all_posts = new \WP_Query( $query );

		if ( $all_posts->have_posts() ) {
			$post_ids = wp_list_pluck( $all_posts->posts, 'ID' );
		}

		wp_send_json( $post_ids );
		exit();
	}

	/**
	 * Has Shortcode
	 */
	public function has_shortcode() {

		$shortcode_list = get_option( DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX . '_shortcode_list', array() );
		$post_id        = wp_unslash( sanitize_text_field( $_POST['post_id'] ) );

		$post           = get_post( $post_id );
		$shortcode_list = $this->get_shortcodes_from_content( $shortcode_list, $post->post_content, 'Content', $post_id );
		$meta_data      = get_post_meta( $post_id );


		if ( is_array( $meta_data ) && ! empty( $meta_data ) ) {
			foreach ( $meta_data as $meta ) {
				$shortcode_list = $this->get_shortcodes_from_content( $shortcode_list, $meta, 'Meta Data', $post_id );
			}
		}

		update_option( DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX . '_shortcode_list', $shortcode_list );

		wp_send_json( $shortcode_list );
		exit();
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

		$regex = '/\[([a-zA-Z][^\]]*)\]/';

		preg_match_all( $regex, $content, $matches );

		if ( count( $matches ) > 0 ) {
			foreach ( $matches as $match_key => $shortcode ) {
				if ( 0 !== $match_key ) {

					$code = str_replace( '[', '', $shortcode );
					$code = str_replace( ']', '', $code );
					if ( is_array( $code ) ) {
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

						if ( isset( $shortcode_list[ $command ] ) && isset( $shortcode_list[ $command ]['keys'] ) ) {
							$shortcode_list[ $command ]['keys'] = array_unique( array_merge( $keys, $shortcode_list[ $command ]['keys'] ) );
						} else {
							$shortcode_list[ $command ]['keys'] = $keys;
						}
						$post = get_post( $post_id );
						$shortcode_list[ $command ][] = array(
							'shortcode'  => '[' . ( is_array( $shortcode ) ? $shortcode[0] : $shortcode ) . ']',
							'post_id'    => $post_id,
							'location'   => $type,
							'attributes' => $attributes,
							'title'      => $post->post_title,
						);
					}
				}
			}
		}

		return $shortcode_list;
		exit;
	}

	public function build_table() {

		$html                   = '';
		$shortcode_list         = get_option( DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX . '_shortcode_list', array() );
		$shortcode_list_display = get_option( DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX . '_shortcode_list_display', array() );

		if ( is_array( $shortcode_list ) && ! empty( $shortcode_list ) ) {
			ksort( $shortcode_list );
			ob_start();
			?>
			<p><?php esc_html_e( 'The Following shortcodes were found in your site. You can refresh this list by clicking the \'Refresh Shortcodes\' button.', 'shortcode-finder' ); ?></p>
			<div class="shortcode-list__button">
				<form method="post" class="shortcode-list__generate">
					<input type="submit" name="shortcode-list__generate" class="button button--primary button-primary shortcode-list__refresh" value="<?php esc_html_e( 'Refresh Shortcodes', 'shortcode-finder' ); ?>"/>
					<?php wp_nonce_field( 'shortcode_list__generate_action', 'shortcode_list__generate_nonce' ); ?>
				</form>
				<form method="post" class="shortcode-list__download">
					<input type="submit" name="shortcode-list__download" class="button button--secondary" value="Download as Excel File"/>
					<?php wp_nonce_field( 'shortcode_list_download', 'shortcode_list_download_nonce' ); ?>
				</form>
			</div>
			<div class="shortcode-list__filter">
				<?php esc_html_e( 'Showing the following shortcodes:', 'shortcode-finder' ); ?>
				<ul>
					<?php
					foreach ( $shortcode_list as $key => $shortcode ) {
						?>
						<li>
							<label><input type="checkbox" name="shortcode_list_display[]" value="<?php echo esc_attr( $key ); ?>" <?php echo ( ! in_array( $key, $shortcode_list_display ) ? 'checked' : '' ); ?> /><?php echo esc_html( $key ); ?></label>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
			<?php
			$html .= ob_get_contents();
			ob_end_clean();

			foreach ( $shortcode_list as $key => $shortcode ) {
				if ( ! in_array( $key, $shortcode_list_display ) ) {
					uasort( $shortcode, function( $a, $b ) {
						if ( isset( $a['title'] ) && isset( $b['title'] ) ) {
							return $a['title'] <=> $b['title'];
						}
						return false;
					} );

					ob_start();

					?>
					<h3><?php echo esc_html( $key ); ?></h3>
					<div class="shortcode-list__item">
						<table class="table shortcode-list__table">
							<tr>
								<th>Name</th>
								<th>Post Type</th>
								<th>Location</th>
								<th>Shortcode</th>
								<?php
								if ( is_array( $shortcode['keys'] ) && ! empty( $shortcode['keys'] ) ) {
									foreach ( $shortcode['keys'] as $shortcode_key ) {
										?>
										<th><?php echo esc_html( $shortcode_key ); ?></th>
										<?php
									}
								}
								?>
							</tr>
							<?php

							foreach ( $shortcode as $k => $s ) {
								if ( 'keys' !== $k ) {
									$post = get_post( $s['post_id'] );
									?>
									<tr>
									<td><a href="<?php echo esc_url( get_edit_post_link( $s['post_id'] ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a></td>
									<td><?php echo esc_html( $post->post_type ); ?></td>
									<td><?php echo esc_html( $s['location'] ); ?></td>
									<td><?php echo esc_html( $s['shortcode'] ); ?></td>
									<?php
									foreach ( $shortcode['keys'] as $shortcode_key ) {
										if ( isset( $s['attributes'][ $shortcode_key ] ) ) {
											?>
											<td><?php echo esc_html( $s['attributes'][ $shortcode_key ] ); ?></td>
											<?php
										} else {
											?>
											<td></td>
											<?php
										}
									}
									?>
								</tr>
									<?php
								}
							}
							?>
						</table>
					</div>
					<?php

					$html .= ob_get_contents();
					ob_end_clean();
				}
			}
		} else {
			ob_start();
			?>
			<p><?php esc_html_e( 'No shortcodes were detected in your site. Click the \'Find Shortcodes\' button to detect shortcodes.', 'shortcode-finder' ); ?></p>
			<form method="post" class="shortcode-list__generate">
				<input type="submit" name="shortcode-list__generate" class="button button--primary button-primary" value="<?php esc_html_e( 'Find Shortcodes', 'shortcode-finder' ); ?>"/>
				<?php wp_nonce_field( 'shortcode_list__generate_action', 'shortcode_list__generate_nonce' ); ?>
			</form>
			<?php
			$html .= ob_get_contents();
			ob_end_clean();
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			echo $html;
		} else {
			return $html;
		}
		exit;
	}

	/**
	 * Save List Filter
	 */
	public function save_list_filter() {
		$keys = $_POST['keys'];

		update_option( DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX . '_shortcode_list_display', $keys );
		exit;
	}

}
