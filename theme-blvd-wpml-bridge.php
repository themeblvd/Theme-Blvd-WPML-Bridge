<?php
/*
Plugin Name: Theme Blvd WPML Bridge
Plugin URI: http://wpml.themeblvd.com
Description: This plugin creates a bridge between the Theme Blvd framework and the WPML plugin.
Version: 2.0.1
Author: Jason Bobich
Author URI: http://jasonbobich.com
License: GPL2
*/

/*
Copyright 2014 JASON BOBICH

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'TB_WPML_BRIDGE_PLUGIN_VERSION', '2.0.1' );
define( 'TB_WPML_BRIDGE_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'TB_WPML_BRIDGE_PLUGIN_URI', plugins_url( '' , __FILE__ ) );

/**
 * Initialize plugin.
 *
 * @since 2.0.0
 */
function themeblvd_wpml_init() {

	// Run plugin v2, for Theme Framework v2.3+
	Theme_Blvd_WPML::get_instance();

	// Include frontend functions file.
	include_once( TB_WPML_BRIDGE_PLUGIN_DIR . '/includes/frontend.php' );

	// Include legacy version of plugin for those using a Theme
	// Blvd framework version prior to 2.3.
	include_once( TB_WPML_BRIDGE_PLUGIN_DIR . '/includes/legacy.php' );

}
add_action('plugins_loaded', 'themeblvd_wpml_init');

/**
 * Theme Blvd WPML
 *
 * @since 2.0.0
 */
class Theme_Blvd_WPML {

	/*--------------------------------------------*/
	/* Properties, private
	/*--------------------------------------------*/

	/**
	 * A single instance of this class.
	 *
	 * @since 2.3.0
	 */
	private static $instance = null;

	/**
	 * Original theme option ID before any mods
	 * by our plugin.
	 *
	 * @since 2.3.0
	 */
	private $theme_option_id;

	/**
	 * The options ID for the WPML settings page.
	 * (NOT theme options)
	 *
	 * @since 2.3.0
	 */
	private $wpml_option_id;

	/*--------------------------------------------*/
	/* Properties, public
	/*--------------------------------------------*/

	/**
	 * The WPML settings page object
	 *
	 * @since 2.3.0
	 * @var Theme_Blvd_Options_Page
	 */
	public $wpml_page;

	/*--------------------------------------------*/
	/* Constructor
	/*--------------------------------------------*/

	/**
     * Creates or returns an instance of this class.
     *
     * @since 2.3.0
     *
     * @return Theme_Blvd_WPML A single instance of this class.
     */
	public static function get_instance() {

		if ( self::$instance == null ) {
            self::$instance = new self;
        }

        return self::$instance;
	}

	/**
	 * Constructor. Hook everything in and setup API.
	 *
	 * @since 2.3.0
	 */
	private function __construct() {

		// If WPML isn't running, get out of here.
		if ( ! defined( 'ICL_PLUGIN_PATH' ) ) {
			return;
		}

		// General Filters
		add_filter( 'themeblvd_option_id', array( $this, 'option_id' ), 15 );

		// General mutators
		add_action( 'after_setup_theme', array( $this, 'set_wpml_option_id' ) );

		// Admin
		add_action( 'after_setup_theme', array( $this, 'admin_init' ) );

		// Frontend
		add_action( 'after_setup_theme',  array( $this, 'frontend_init' ) );

	}

	/*--------------------------------------------*/
	/* General Accessors
	/*--------------------------------------------*/

	/**
	 * Get original theme options ID before any mods
	 * by our plugin.
	 *
	 * @since 2.0.0
	 */
	public function get_theme_option_id() {
		return $this->theme_option_id;
	}

	/**
	 * Get WPML settings page id.
	 *
	 * @since 2.0.0
	 */
	public function get_wpml_option_id() {
		return $this->wpml_option_id;
	}

	/*--------------------------------------------*/
	/* General Filters
	/*--------------------------------------------*/

	/**
	 * Theme Options ID.
	 *
	 * @since 2.0.0
	 */
	public function option_id( $current ) {

		if ( ! defined( 'TB_FRAMEWORK_VERSION' ) || version_compare( TB_FRAMEWORK_VERSION, '2.3.0', '<' ) ) {
			return $current;
		}

		$this->theme_option_id = $current; // Store original
		$option_id = $current;

		// Only continue if WPML is running and the
		// current language constant has been defined.
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {

			// Current language
			$current_lang = ICL_LANGUAGE_CODE;

			// Set default language
			$default_lang = 'en'; // backup
			$wpml_options = get_option( 'icl_sitepress_settings' );
			if ( isset( $wpml_options['default_language'] ) ) {
				$default_lang = $wpml_options['default_language'];
			}

			// Adjust theme settings to match language if
			// it's different than the default language.
			if ( $current_lang != $default_lang && $current_lang != 'all' ) {
				$option_id = $option_id.'_'.$current_lang;
			}

		}

		return $option_id;
	}

	/*--------------------------------------------*/
	/* Frontend
	/*--------------------------------------------*/

	/**
	 * Initiate frotnend.
	 *
	 * @since 2.0.0
	 */
	public function frontend_init() {

		// For Theme Blvd framework v2.3+ only.
		if ( ! defined( 'TB_FRAMEWORK_VERSION' ) || version_compare( TB_FRAMEWORK_VERSION, '2.3.0', '<' ) ) {
			return;
		}

		// Only swap breadcrumbs if user has "WPML CMS Nav" add-on installed.
		if ( class_exists( 'WPML_CMS_Navigation' ) && apply_filters( 'tb_wpml_breadcrumbs_replace', true ) ) {
			remove_action( 'themeblvd_breadcrumbs', 'themeblvd_breadcrumbs_default' );
			add_action( 'themeblvd_breadcrumbs', 'tb_wpml_breadcrumbs' );
		}

		// Theme Locations
		$locations = $this->get_wpml_locations();
		$settings = get_option( $this->wpml_option_id );

		if ( $locations ) {
			foreach ( $locations as $id => $location ) {
				if ( isset( $settings[$id] ) && $settings[$id] == 'true' ) {
					add_action( $location['action'], 'tb_wpml_flaglist' );
				}
			}
		}

	}

	/*--------------------------------------------*/
	/* Admin: General
	/*--------------------------------------------*/

	/**
	 * Initiate admin.
	 *
	 * @since 2.0.0
	 */
	public function admin_init() {

		// Admin only.
		if ( ! is_admin() ) {
			return;
		}

		// For Theme Blvd framework v2.3+ only.
		if ( ! defined( 'TB_FRAMEWORK_VERSION' ) || version_compare( TB_FRAMEWORK_VERSION, '2.3.0', '<' ) ) {
			return;
		}

		// Filter in theme Options menu slug to not use
		// language extension.
		add_filter( 'themeblvd_theme_options_args', array( $this, 'options_args' ) );

		// Add functionality for "Match Default Language" button
		// on Theme Options page.
		add_action( 'admin_init', array( $this, 'options_match' ) );

		// Add header to theme options page to change languages
		// and any needed assets.
		add_action( 'themeblvd_admin_module_header', array( $this, 'options_header' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'options_styles' ) );

		// WPML settings page
		add_action( 'init', array( $this, 'wpml_options_page' ) );

	}

	/*--------------------------------------------*/
	/* Admin: Theme Options
	/*--------------------------------------------*/

	/**
	 * Is this the theme options page?
	 *
	 * @since 2.0.0
	 *
	 * @return bool Whether this is the theme options page
	 */
	public function is_options_page() {

		$current = get_current_screen();
		$base = sprintf( 'appearance_page_%s', $this->theme_option_id );

		if ( $current->base == $base ) {
			return true;
		}

		return false;
	}

	/**
	 * Filter in theme Options menu slug.
	 *
	 * @since 2.0.0
	 */
	public function options_args( $args ) {

		if ( ! defined('ICL_LANGUAGE_CODE') ) {
			return;
		}

		// Options form action
		$args['form_action'] = sprintf( 'options.php?lang=%s', ICL_LANGUAGE_CODE );

		// Menu slug
		$args['menu_slug'] = $this->theme_option_id; // Original stored options ID before our mods

		return $args;
	}

	/**
	 * Add to sanitization of options page for
	 * "Match Default Language" button.
	 *
	 * @since 2.0.0
	 *
	 * @param array $options Current options being sanitized
	 */
	public function options_match() {

		if ( ! isset( $_POST['tb_wpml_match'] ) ) {
			return;
		}

		$options_id = themeblvd_get_option_name();
		$settings = get_option( $this->theme_option_id );

		// Update options in datatbase to match defualt
		// language's current options.
		update_option( $options_id, $settings );

		// Add success message
		add_settings_error( $options_id, 'save_options', __( 'Options matched.', 'tb_wpml' ), 'updated fade' );

	}

	/**
	 * Add CSS to Theme Options page.
	 *
	 * @since 2.0.0
	 */
	public function options_styles() {
		if ( $this->is_options_page() ) {
			wp_register_style( 'tb_wpml_options_styles', TB_WPML_BRIDGE_PLUGIN_URI . '/assets/css/options.css', false, '1.0' );
			wp_enqueue_style( 'tb_wpml_options_styles' );
		}
	}

	/**
	 * Theme Options header.
	 *
	 * @since 2.0.0
	 */
	public function options_header() {

		if ( ! $this->is_options_page() ) {
			return;
		}

		global $sitepress;

		// Set default language
		$default_lang = 'en'; // backup
		$wpml_options = get_option( 'icl_sitepress_settings' );
		if ( isset( $wpml_options['default_language'] ) ) {
			$default_lang = $wpml_options['default_language'];
		}

		// Get all languages
		$core_langs = $sitepress->get_active_languages(true);

		// Re-arrange so default language is always first.
		$langs = array();
		$langs[$default_lang] = $core_langs[$default_lang];
		unset( $core_langs[$default_lang] );
		foreach ( $core_langs as $key => $value ) {
			$langs[$key] = $value;
		}

		// Set current language
		$current_lang = ICL_LANGUAGE_CODE;
		if ( $current_lang == 'all' ) {
			$current_lang = $default_lang;
		}

		// Current Flag
		$current_flag = $sitepress->get_flag( $current_lang );
        if ( $current_flag->from_template ){
            $wp_upload_dir = wp_upload_dir();
            $current_flag_url = $wp_upload_dir['baseurl'].'/flags/'.$current_flag->flag;
        } else {
            $current_flag_url = ICL_PLUGIN_URL.'/res/flags/'.$current_flag->flag;
        }
		?>
		<div class="tb-wpml-header">
			<h3>
				<span class="tb-wpml-flag"><img src="<?php echo $current_flag_url; ?>" /></span>
				<?php printf( __( '%1$s Theme Options', 'tb_wpml' ), $langs[$current_lang]['native_name'] ); ?>
			</h3>
			<span class="tb-wpml-logo">Theme Blvd WPML Bridge</span>
			<div class="tb-wpml-nav">
				<?php if ( $langs ) : ?>
					<ul>
						<?php foreach ( $langs as $key => $lang ) : ?>
							<li<?php if ( $key == $current_lang ) echo ' class="active"'; ?>>
								<a href="?page=<?php echo $this->theme_option_id; ?>&lang=<?php echo $key ?>">
									<?php echo $lang['display_name']; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( $current_lang != $default_lang ) : ?>
					<form action="" method="post">
						<input type="submit" class="reset-button button-secondary" name="tb_wpml_match" value="<?php esc_attr_e( 'Match Default Language', 'tb_wpml' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to match options. You will lose your current settings for this language and they will be matched to whatever you\'ve set for your default language.', 'tb_wpml' ) ); ?>' );" />
					</form>
				<?php endif; ?>
			</div><!-- .tb-wpml-nav (end) -->
		</div><!-- .tb-wpml-header (end) -->
		<?php

	}

	/*--------------------------------------------*/
	/* Admin: WPML Options
	/*--------------------------------------------*/

	/**
	 * Set options ID for the WPML settings page.
	 * (NOT theme options)
	 *
	 * @since 2.0.0
	 */
	public function set_wpml_option_id() {
		$this->wpml_option_id = sprintf( '%s_tb_wpml', $this->theme_option_id );
		$this->wpml_option_id = apply_filters( 'tb_wpml_admin_page_id', $this->wpml_option_id );
	}

	/**
	 * Setup WPML Settings page.
	 * WP Admin > WPML > Theme Name
	 *
	 * @since 2.0.0
	 *
	 * @return bool Whether this is the theme options page
	 */
	public function wpml_options_page() {

		// Theme Name
		$theme_data = wp_get_theme( get_stylesheet() );
		$theme_name = $theme_data->get('Name');

		// Setup Options page
		$args = array(
			'parent'		=> apply_filters( 'icl_menu_main_page', basename( ICL_PLUGIN_PATH ).'/menu/languages.php' ),
			'page_title' 	=> sprintf( __( '%s WPML Options', 'tb_wpml' ), $theme_name ),
			'menu_title' 	=> $theme_name,
			'cap'			=> apply_filters( 'tb_wpml_settings_cap', 'edit_theme_options' ),
			'icon'			=> 'wpml',
			'closer'		=> false
		);
		$this->wpml_page = new Theme_Blvd_Options_Page( $this->wpml_option_id, $this->get_wpml_options(), $args );

	}

	/**
	 * Get wpml theme locations to integrate flaglists
	 * into on the frontend.
	 *
	 * @since 2.0.0
	 *
	 * @return array $locations
	 */
	public function get_wpml_locations() {
		$locations = array(
			'menu_addon' => array(
				'name' 		=> __( 'Header Menu Addon', 'tb_wpml' ),
				'desc' 		=> __( 'This will display your language flags in the right side of the Primay Navigation.', 'tb_wpml' ),
				'action' 	=> 'themeblvd_header_menu_addon'
			),
			'breadcrumbs' => array(
				'name' 		=> __( 'Breadcrumbs', 'tb_wpml' ),
				'desc' 		=> __( 'This will display your language flags off to the right of your Breadrumbs.', 'tb_wpml' ),
				'action' 	=> 'tb_wpml_breadcrumbs_addon'
			)
		);
		return apply_filters( 'tb_wpml_theme_locations', $locations );
	}

	/**
	 * Get options for WPML options page.
	 *
	 * @since 2.0.0
	 *
	 * @return array $options
	 */
	public function get_wpml_options() {

		// Start options and location section
		$options = array(
			'start_locations' => array(
				'name' => __( 'Theme Locations', 'tb_wpml' ),
				'type' => 'section_start',
				'desc' => __( 'In this section, you can select where you\'d like the Theme Blvd WPML Bridge\'s flaglists to display. You can see below all of your current theme\'s supported locations.<br><br>Note: This is completely separate from WPML\'s <a href="admin.php?page=sitepress-multilingual-cms/menu/languages.php#lang-sec-3">Language switcher options</a>.', 'tb_wpml' )
			)
		);

		// Setup dynamic options based on theme locations
		$theme_locations = $this->get_wpml_locations();
		if ( $theme_locations ) {
			foreach ( $theme_locations as $id => $location ) {
				$options[$id] = array(
					'name' 		=> $location['name'],
					'desc'		=> $location['desc'],
					'id'		=> $id,
					'std'		=> 'false',
					'type' 		=> 'radio',
					'options'	=> array(
						'true' => 	__( 'Yes, display flags in this location.', 'tb_wpml' ),
						'false' => 	__( 'No, don\'t show them.', 'tb_wpml' )
					)
				);
			}
		}

		// End locations section
		$options['end_locations'] = array(
			'type' => 'section_end'
		);

		// Return with filters applied
		return apply_filters( 'tb_wpml_options', $options );
	}

}