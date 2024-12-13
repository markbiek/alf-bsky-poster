<?php
/**
 * Plugin Name: ALF Bluesky Poster
 * Plugin URI: https://github.com/mark.biek.org/alf-bsky-poster
 * Description: A WordPress plugin to automatically post content to Bluesky
 * Version: 0.1.0
 * Author: Mark Biek
 * Author URI: https://mark.biek.org
 * Text Domain: alf-bsky-poster
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.0
 *
 * @category  Social_Media
 * @package   ALF_Bsky_Poster
 * @author    Mark Biek <mark@biek.org>
 * @copyright 2024 Mark Biek
 * @license   GPL v2 or newer <https://www.gnu.org/licenses/gpl.txt>
 */

namespace AlfBsky;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	wp_die( esc_html__( 'Please run composer install to use the ALF Bluesky Poster plugin.', 'alf-bsky-poster' ) );
}

// Initialize the settings page.
$settings = new \AlfBsky\AlfBskySettings();
$settings->init();

// Add admin notice check.
add_action(
	'admin_init',
	function() {
		// Skip if user can't manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$identifier = get_option( \AlfBsky\AlfBskySettings::OPTION_IDENTIFIER );
		$password   = get_option( \AlfBsky\AlfBskySettings::OPTION_APP_PASSWORD );
		$categories = get_option( \AlfBsky\AlfBskySettings::OPTION_CATEGORIES, array() );

		if ( empty( $identifier ) || empty( $password ) || empty( $categories ) ) {
			add_action(
				'admin_notices',
				function() {
					?>
			<div class="notice notice-warning">
				<p>
						<?php
						printf(
							/* translators: %s: Settings page URL */
							esc_html__( 'ALF Bluesky Poster needs to be configured. Please visit the %s to set it up.', 'alf-bsky-poster' ),
							sprintf(
								'<a href="%s">%s</a>',
								esc_url( admin_url( 'options-general.php?page=alf-bsky-poster' ) ),
								esc_html__( 'settings page', 'alf-bsky-poster' )
							)
						);
						?>
				</p>
			</div>
					<?php
				}
			);
		}
	}
);
