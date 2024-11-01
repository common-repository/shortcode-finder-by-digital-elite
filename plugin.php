<?php
/**
 * Shortcode Finder by Digital Elite
 *
 * @link              https://github.com/digitalelite/shortcode-finder
 * @package           digital-elite\shortcode-finder
 *
 * Plugin Name:       Shortcode Finder by Digital Elite 
 * Plugin URI:        https://github.com/digitalelite/shortcode-finder
 * Description:       Find all of the shortcodes in your site, and quickly navigate to where they can be edited
 * Version:           1.0.3
 * Author:            Digital Elite <stuart@digitalelite.co.uk>
 * Author URI:        https://www.digitalelite.co.uk/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       shortcode-finder
 * Domain Path:       /languages
 */

/**
 * Copyright (C) 2018  Digital Elite  stuart@digitalelite.co.uk
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace digital_elite\shortcode_finder;

// Abort if this file is called directly.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Constants.
define( 'DIGITAL_ELITE_SHORTCODE_FINDER_ROOT', __FILE__ );
define( 'DIGITAL_ELITE_SHORTCODE_FINDER_PREFIX', 'digital_elite_shortcode_finder' );

/**
 * The main loader for this plugin
 */
class Main {

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

		/**
		 * Load Text Domain
		 */
		load_plugin_textdomain( 'shortcode-finder', false, DIGITAL_ELITE_SHORTCODE_FINDER_ROOT . '\languages' );

		/**
		 * Actions and Hooks
		 */

		// Load Assets
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) ); // Load Admin Assets
	}

	/**
	 * Enqueue Admin Styles.
	 */
	public function admin_assets() {

		$styles  = '/assets/css/plugin-admin.min.css';
		$scripts = '/assets/js/plugin-admin.min.js';

		// Enqueue Styles.
		wp_enqueue_style(
			'shortcode-finder-admin-css',
			plugins_url( $styles, __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . $styles )
		);

		// Enqueue Scripts.
		wp_enqueue_script(
			'shortcode-finder-plugin-admin-js',
			plugins_url( $scripts, __FILE__ ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( __FILE__ ) . $scripts ),
			true
		);

		$vars = array(
			'ajaxurl'   => esc_url( admin_url( 'admin-ajax.php' ) ),
			'ajaxnonce' => wp_create_nonce( 'ajax_nonce' ),
		);
		wp_localize_script( 'shortcode-finder-plugin-admin-js', 'ajax_object', $vars );
	}
}

// Load Classes
require_once 'vendor/wp-background-processing/wp-background-processing.php';
require_once 'php/class-ajax.php';
require_once 'php/class-background-processing.php';
require_once 'php/class-settings.php';

$main                  = new Main();
$ajax                  = new AJAX();
$background_processing = new Background_Processing();
$settings              = new Settings( $ajax, $background_processing );

$ajax->run();
$settings->run();
$main->run();
