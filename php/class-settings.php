<?php
/**
 * Class Settings
 *
 * @since 1.0.0
 *
 * @package digital_elite\shortcode_finder
 */

namespace digital_elite\shortcode_finder;

/**
 * The main plugin settings page
 */
class Settings {

	private $ajax;
	private $background_processing;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( AJAX $ajax, Background_Processing $background_processing ) {
		$this->ajax                  = $ajax;
		$this->background_processing = $background_processing;
	}


	/**
	 * Run all of the plugin functions.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'plugin_action_links_' . plugin_basename( DIGITAL_ELITE_SHORTCODE_FINDER_ROOT ), array( $this, 'add_setings_link' ) );
		add_action( 'init', array( $this, 'download_csv' ) );
		add_action( 'init', array( $this, 'generate' ), 9999 );
	}

	/**
	 * Add the settings page.
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page() {
		add_submenu_page( 'options-general.php',
			esc_html__( 'Shortcode Finder', 'shortcode-finder' ),
			esc_html__( 'Shortcode Finder', 'shortcode-finder' ),
			'manage_options',
			'shortcode_finder',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {

		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Shortcode Finder', 'shortcode-finder' ); ?></h2>
			<div class="shortcode-list">
				<?php
				if ( isset( $_POST['shortcode_list__generate_nonce'] ) && wp_verify_nonce( $_POST['shortcode_list__generate_nonce'], 'shortcode_list__generate_action' ) && is_admin() ) {
					?>
					<p><?php esc_html_e( 'Indexing site in the background (you can navigate away from this page). This page will refresh automatically.', 'shortcode-finder' ); ?></p>
					<img src="/wp-includes/images/spinner-2x.gif"/>

					<?php
				} else {
					echo $this->ajax->build_table();
				}
				?>
			</div>
		</div>
	<?php
	}

	/**
	 * Add 'Settings' action on installed plugin list.
	 *
	 * @param array $links An array of plugin action links.
	 *
	 * @since 1.0.0
	 */
	function add_setings_link( $links ) {
		array_unshift( $links, '<a href="options-general.php?page=' . esc_attr( 'shortcode_finder' ) . '">' . esc_html__( 'Settings', 'shortcode-finder' ) . '</a>' );
		return $links;
	}

	function generate() {

		if ( isset( $_POST['shortcode_list__generate_nonce'] ) && wp_verify_nonce( $_POST['shortcode_list__generate_nonce'], 'shortcode_list__generate_action' ) && is_admin() ) {

			update_option( DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX . '_shortcode_list', array() );
			
			$post_ids   = array();
			$query      = array(
				'posts_per_page' => -1,
				'post_type'      => get_post_types(),
				'post_status'    => 'publish',
			);
			$all_posts = new \WP_Query( $query );

			if ( $all_posts->have_posts() ) {
				$post_ids = wp_list_pluck( $all_posts->posts, 'ID' );
			}

			foreach ( $post_ids as $post_id ) {
				$this->background_processing->push_to_queue( $post_id );
			}

			$this->background_processing->save()->dispatch();
		}
	}

	function download_csv() {

		if ( isset( $_POST['shortcode_list_download_nonce'] ) && wp_verify_nonce( $_POST['shortcode_list_download_nonce'], 'shortcode_list_download' ) && is_admin() ) {

			$shortcodes      = array();
			$shortcodes_keys = array();
			$shortcode_list  = get_option( DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX . '_shortcode_list', array() );
			if ( is_array( $shortcode_list ) && ! empty( $shortcode_list ) ) {
				ksort( $shortcode_list );

				foreach ( $shortcode_list as $key => $shortcode ) {
					uasort( $shortcode, function( $a, $b ) {
						if ( isset( $a['title'] ) && isset( $b['title'] ) ) {
							return $a['title'] <=> $b['title'];
						}
						return false;
					} );

					if ( isset( $shortcode['keys'] ) ) {
						foreach ( $shortcode['keys'] as $key ) {
							$shortcodes_keys[] = $key;
						}
					}
				}

				header( 'Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
				header( 'Content-Disposition: attachment; filename=shortcode-list.xlsx' );
				header( 'Pragma: no-cache' );
				header( 'Expires: 0' );

				ob_start();

				?>

				<table>
					<tr>
						<th>name</th>
						<th>post id</th>
						<th>link</th>
						<th>post_type</th>
						<th>location</th>
						<th>shortcode</th>
						<?php
						foreach ( $shortcodes_keys as $shortcode_key ) {
							?>
							<th><?php echo esc_html( $shortcode_key ); ?></th>
							<?php
						}
						?>
					</tr>

				<?php

				foreach ( $shortcode_list as $key => $shortcode ) {
					uasort( $shortcode, function( $a, $b ) {
						if ( isset( $a['title'] ) && isset( $b['title'] ) ) {
							return $a['title'] <=> $b['title'];
						}
						return false;
					} );

					foreach ( $shortcode as $k => $s ) {

						if ( 'keys' !== $k ) {

							$post = get_post( $s['post_id'] );
							?>

							<tr>
								<td><?php echo esc_html( $post->post_title ); ?></td>
								<td><?php echo esc_html( $s['post_id'] ); ?></td>
								<td><?php echo esc_url( get_edit_post_link( $s['post_id'] ) ); ?></td>
								<td><?php echo esc_html( $post->post_type ); ?></td>
								<td><?php echo esc_html( $s['location'] ); ?></td>
								<td><?php echo esc_html( $s['shortcode'] ); ?></td>
								<?php
								foreach ( $shortcodes_keys as $shortcode_key ) {
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
				}

				?>
				</table>
				<?php
				$html .= ob_get_contents();
				ob_end_clean();

				echo $html;

				exit;
			}
		}
	}
}
