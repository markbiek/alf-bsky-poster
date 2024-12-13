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
	 * Option group name.
	 *
	 * @var string
	 */
	private const OPTION_GROUP = 'alf_bsky_settings';
	private const SETTINGS_SECTION = 'alf_bsky_main_section';
	private const OPTION_IDENTIFIER = 'alf_bsky_identifier';
	private const OPTION_APP_PASSWORD = 'alf_bsky_app_password';
	private const OPTION_CATEGORIES = 'alf_bsky_categories';

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
			self::OPTION_IDENTIFIER,
			array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default' => '',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_APP_PASSWORD,
			array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default' => '',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_CATEGORIES,
			array(
				'type' => 'array',
				'default' => array(),
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

		add_settings_field(
			'categories',
			__('Categories to Post', 'alf-bsky-poster'),
			array($this, 'render_categories_field'),
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
		echo esc_html__( 'Enter your Bluesky credentials below.', 'alf-bsky-poster' );
	}

	/**
	 * Render the identifier field
	 */
	public function render_identifier_field(): void {
		$value = get_option(self::OPTION_IDENTIFIER);
		?>
		<input type="text" 
			name="<?php echo esc_attr(self::OPTION_IDENTIFIER); ?>" 
			value="<?php echo esc_attr($value); ?>" 
			class="regular-text"
			placeholder="username.bsky.social"
		/>
		<?php
	}

	/**
	 * Render the app password field
	 */
	public function render_app_password_field(): void {
		$value = get_option(self::OPTION_APP_PASSWORD);
		?>
		<input type="password" 
			name="<?php echo esc_attr(self::OPTION_APP_PASSWORD); ?>" 
			value="<?php echo esc_attr($value); ?>" 
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

	/**
	 * Render the categories field.
	 */
	public function render_categories_field(): void {
		$categories = get_categories(array( 'hide_empty' => false ));
		$selected_categories = get_option(self::OPTION_CATEGORIES, array());

		echo '<fieldset>';
		foreach ($categories as $category) {
			$checked = in_array($category->term_id, $selected_categories, true) ? 'checked' : '';
			printf(
				'<label><input type="checkbox" name="%s[]" value="%d" %s> %s</label><br>',
				esc_attr(self::OPTION_CATEGORIES),
				esc_attr($category->term_id),
				esc_attr($checked),
				esc_html($category->name)
			);
		}
		echo '</fieldset>';
		echo '<p class="description">' . 
			esc_html__('Select which categories should be posted to Bluesky.', 'alf-bsky-poster') . 
			'</p>';
	}
} 