<?php
/**
 * Settings page handler for ALF Bluesky Poster
 *
 * @package ALF_Bsky_Poster
 */

namespace AlfBsky;

/**
 * Handles the settings page and options management
 */
class AlfBskySettings {
	/**
	 * Option group name
	 *
	 * @var string
	 */
	private const OPTION_GROUP = 'alf_bsky_settings';

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
			__( 'ALF Bluesky Poster Settings', 'alf-bsky-poster' ),
			__( 'ALF Bluesky Poster', 'alf-bsky-poster' ),
			'manage_options',
			'alf-bsky-poster',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the settings and fields
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			'alf_bsky_identifier',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'alf_bsky_app_password',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		add_settings_section(
			'alf_bsky_main_section',
			__( 'Bluesky Connection Settings', 'alf-bsky-poster' ),
			array( $this, 'render_section_info' ),
			'alf-bsky-poster'
		);

		add_settings_field(
			'alf_bsky_identifier',
			__( 'Bluesky Identifier', 'alf-bsky-poster' ),
			array( $this, 'render_identifier_field' ),
			'alf-bsky-poster',
			'alf_bsky_main_section'
		);

		add_settings_field(
			'alf_bsky_app_password',
			__( 'Bluesky Application Password', 'alf-bsky-poster' ),
			array( $this, 'render_app_password_field' ),
			'alf-bsky-poster',
			'alf_bsky_main_section'
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
		echo esc_html__( 'Enter your Bluesky credentials below.', 'alf-bsky-poster' );
	}

	/**
	 * Render the identifier field
	 */
	public function render_identifier_field(): void {
		$value = get_option( 'alf_bsky_identifier' );
		?>
		<input type="text" 
			name="alf_bsky_identifier" 
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
		$value = get_option( 'alf_bsky_app_password' );
		?>
		<input type="password" 
			name="alf_bsky_app_password" 
			value="<?php echo esc_attr( $value ); ?>" 
			class="regular-text"
		/>
		<p class="description">
			<?php
			printf(
				/* translators: %s: URL to Bluesky app passwords page */
				esc_html__( 'You can generate an app password in your %s.', 'alf-bsky-poster' ),
				'<a href="https://bsky.app/settings/app-passwords" target="_blank">' . 
				esc_html__( 'Bluesky account settings', 'alf-bsky-poster' ) . 
				'</a>'
			);
			?>
		</p>
		<?php
	}
} 