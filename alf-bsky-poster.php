<?php
/**
 * Plugin Name: Antelope Bluesky Poster
 * Plugin URI: https://github.com/markbiek/alf-bsky-poster
 * Description: A WordPress plugin to automatically post content to Bluesky
 * Version: 0.1.0
 * Author: Mark Biek
 * Author URI: https://mark.biek.org
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Text Domain: antelope-bluesky-poster
 * Requires PHP: 8.1
 * Requires at least: 6.0
 *
 * @category  Social_Media
 * @package   ALF_Bsky_Poster
 * @author    Mark Biek <markbiek@duck.com>
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

function alf_bsky_after_insert_post( $post_id, $post, $update, $post_before ) {
	// Skip if this isn't a new post.
	if ( 'publish' !== $post->post_status ) {
		return;
	}

	// Get plugin settings.
	$identifier         = get_option( \AlfBsky\AlfBskySettings::OPTION_IDENTIFIER );
	$password           = get_option( \AlfBsky\AlfBskySettings::OPTION_APP_PASSWORD );
	$allowed_categories = get_option( \AlfBsky\AlfBskySettings::OPTION_CATEGORIES, array() );

	// Skip if plugin isn't configured.
	if ( empty( $identifier ) || empty( $password ) || empty( $allowed_categories ) ) {
		return;
	}

	// Get post categories.
	$post_categories = wp_get_post_categories( $post_id );

	// Check if post is in an allowed category.
	$should_post = false;
	foreach ( $post_categories as $cat_id ) {
		if ( in_array( $cat_id, $allowed_categories ) ) {
			$should_post = true;
			break;
		}
	}

	if ( ! $should_post ) {
		return;
	}

	// Prepare post content.
	$title     = $post->post_title;
	$permalink = get_permalink( $post_id );
	$content   = wp_strip_all_tags( $post->post_content );

	// If content is less than 300 characters, use full content.
	// Otherwise use excerpt and link.
	if ( strlen( $content ) <= 300 ) {
		$bsky_content = $content;
	} else {
		$excerpt      = wp_strip_all_tags( get_the_excerpt( $post ) );
		$bsky_content = $excerpt . "\n\n" . $permalink;
	}

	try {
		$bsky_client = new \AlfBsky\Api\BskyClient( $identifier, $password );
		$bsky_client->create_post( $bsky_content );
	} catch ( \Exception $e ) {
		set_transient( 'alf_bsky_error', $e->getMessage(), 60 );
	}
}
add_action( 'wp_after_insert_post', '\AlfBsky\alf_bsky_after_insert_post', 10, 4 );

// Add error notice handler.
add_action(
	'admin_notices',
	function() {
		$error = get_transient( 'alf_bsky_error' );
		if ( $error ) {
			delete_transient( 'alf_bsky_error' );
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: %s: Error message */
						esc_html__( 'Error posting to Bluesky: %s', 'alf-bsky-poster' ),
						esc_html( $error )
					);
					?>
				</p>
			</div>
			<?php
		}
	}
);
