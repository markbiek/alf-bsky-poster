<?php
/**
 * Settings page handler for ALF Bluesky Poster
 *
 * @package ALF_Bsky_Poster
 */

namespace AlfBsky;

use AlfBsky\Util\AlfBskyEncryption;

/**
 * Handles the settings page and options management
 */
class AlfBskySettings {
	/**
	 * Option group name.
	 *
	 * @var string
	 */
	private const OPTION_GROUP       = 'alf_bsky_settings';
	private const SETTINGS_SECTION   = 'alf_bsky_main_section';
	public const OPTION_IDENTIFIER   = 'alf_bsky_identifier';
	public const OPTION_APP_PASSWORD = 'alf_bsky_app_password';
	public const OPTION_CATEGORIES   = 'alf_bsky_categories';

	/**
	 * Initialize the settings
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add the settings page to the WordPress admin menu
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'Antelope Bluesky Poster Settings', 'antelope-bluesky-poster' ),
			__( 'Antelope Bluesky Poster', 'antelope-bluesky-poster' ),
			'manage_options',
			'alf-bsky-poster',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Sanitize and encrypt the app password.
	 *
	 * @param string $value The password to sanitize and encrypt.
	 * @return string The sanitized and encrypted password.
	 */
	public function sanitize_app_password( $value ): string {
		$sanitized = sanitize_text_field( $value );
		return AlfBskyEncryption::encrypt( $sanitized );
	}

	/**
	 * Register the settings and fields
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_IDENTIFIER,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_APP_PASSWORD,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_app_password' ),
				'default'           => '',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_CATEGORIES,
			array(
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => array( $this, 'sanitize_categories' ),
			)
		);

		add_settings_section(
			'alf_bsky_main_section',
			__( 'Bluesky Connection Settings', 'antelope-bluesky-poster' ),
			array( $this, 'render_section_info' ),
			'alf-bsky-poster'
		);

		add_settings_field(
			'alf_bsky_identifier',
			__( 'Bluesky Identifier', 'antelope-bluesky-poster' ),
			array( $this, 'render_identifier_field' ),
			'alf-bsky-poster',
			'alf_bsky_main_section'
		);

		add_settings_field(
			'alf_bsky_app_password',
			__( 'Bluesky Application Password', 'antelope-bluesky-poster' ),
			array( $this, 'render_app_password_field' ),
			'alf-bsky-poster',
			'alf_bsky_main_section'
		);

		add_settings_field(
			'categories',
			__( 'Categories to Post', 'antelope-bluesky-poster' ),
			array( $this, 'render_categories_field' ),
			'alf-bsky-poster',
			self::SETTINGS_SECTION
		);
	}

	/**
	 * Render the settings page
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'alf-bsky-poster' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the section information
	 */
	public function render_section_info(): void {
		echo esc_html__( 'Enter your Bluesky credentials below.', 'antelope-bluesky-poster' );
	}

	/**
	 * Render the identifier field
	 */
	public function render_identifier_field(): void {
		$value = get_option( self::OPTION_IDENTIFIER );
		?>
		<input type="text" 
			name="<?php echo esc_attr( self::OPTION_IDENTIFIER ); ?>" 
			value="<?php echo esc_attr( $value ); ?>" 
			class="regular-text"
			placeholder="username.bsky.social"
		/>
		<?php
	}

	/**
	 * Render the app password field
	 */
	public function render_app_password_field(): void {
		$encrypted_value = get_option( self::OPTION_APP_PASSWORD );
		$value           = AlfBskyEncryption::decrypt( $encrypted_value );
		?>
		<input type="password" 
			name="<?php echo esc_attr( self::OPTION_APP_PASSWORD ); ?>" 
			value="<?php echo esc_attr( $value ); ?>" 
			class="regular-text"
		/>
		<p class="description">
			<?php
			printf(
				/* translators: %s: URL to Bluesky app passwords page */
				esc_html__( 'You can generate an app password in your %s.', 'antelope-bluesky-poster' ),
				'<a href="https://bsky.app/settings/app-passwords" target="_blank">' .
				esc_html__( 'Bluesky account settings', 'antelope-bluesky-poster' ) .
				'</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the categories field.
	 */
	public function render_categories_field(): void {
		$categories          = get_categories( array( 'hide_empty' => false ) );
		$selected_categories = get_option( self::OPTION_CATEGORIES, array() );

		if ( empty( $selected_categories ) ) {
			$selected_categories = array();
		}

		echo '<fieldset>';
		foreach ( $categories as $category ) {
			$checked = in_array( intval( $category->cat_ID ), $selected_categories ) ? 'checked' : '';
			printf(
				'<label><input type="checkbox" name="%s[]" value="%d" %s> %s</label><br>',
				esc_attr( self::OPTION_CATEGORIES ),
				esc_attr( $category->term_id ),
				esc_attr( $checked ),
				esc_html( $category->name )
			);
		}
		echo '</fieldset>';
		echo '<p class="description">' .
			esc_html__( 'Select which categories should be posted to Bluesky.', 'antelope-bluesky-poster' ) .
			'</p>';
	}

	/**
	 * Sanitize the categories array to ensure all values are integers.
	 *
	 * @param array|mixed $categories The categories array to sanitize.
	 * @return array The sanitized categories array.
	 */
	public function sanitize_categories( $categories ): array {
		if ( ! is_array( $categories ) ) {
			return array();
		}

		return array_map( 'intval', $categories );
	}
}
