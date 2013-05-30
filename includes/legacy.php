<?php
/**
 * Everything in this file is triggered when using a framework 
 * version prior to 2.3.
 */

/*-----------------------------------------------------------------------------------*/
/* Frontend Integration
/*
/* In the Theme Blvd framework, there are many processes that are completely 
/* separate depending on whether we're in the WordPress admin or the frontend
/* of the website. The items in this section are only relevant when the theme
/* loads on the frontend.
/*-----------------------------------------------------------------------------------*/

/**
 * Initialize frontend for framework versions prior to 2.3
 *  
 * @since 2.0.0
 *
 * @return string $option_name ID to use with get_option to retrieve theme's options
 */

function tb_wpml_frontent_init_legacy() {
	
	// Prior to Theme Blvd framework v2.3 only.
	if( ! defined( 'TB_FRAMEWORK_VERSION' ) || version_compare( TB_FRAMEWORK_VERSION, '2.3.0', '>=' ) )
		return;

	// Setup global theme settings to use current language.
	//
	// NOTE: Run after framework's function with priority 5, 
	// although in TB v2.1-2.2 frontent init has been hooked 
	// to wp, just before template_redirect
	add_action( 'template_redirect', 'tb_wpml_options', 6 );
	
	// Setup actions for theme locations. 
	// 
	// This appends a flag list to any locations provided by 
	// current theme and set to do so from:
	// WP Admin > WPML > {Theme Name}
	tb_wpml_actions();

	// Homepage custom layout swap
 	add_filter( 'themeblvd_frontend_config', 'tb_wpml_homepage_layout', 5 );

}
add_action( 'after_setup_theme', 'tb_wpml_frontent_init_legacy' );

/**
 * Get global theme options ID. 
 *
 * In some theme updates, we've re-structured how we get the ID to 
 * save options. In order to make sure this plugin works with older
 * themes, as well, we're creating this function to first check if 
 * the needed themeblvd function exists and only do the work if it 
 * hasn't been created yet.
 *  
 * @since 1.0.4
 *
 * @return string $option_name ID to use with get_option to retrieve theme's options
 */

function tb_wpml_get_option_name(){
	
	// Make sure we have a variable so no errors if it's empty.
	$option_name = '';
	
	// Check for newer function
	if( function_exists( 'themeblvd_get_option_name' ) ){
		
		// Let themeblvd framework do the work. 
		// This is an up-to-date theme.
		$option_name = themeblvd_get_option_name();
		
	} else {
	
		// Legacy method (just a backup for older themes)
		$config = get_option( 'optionsframework' );
		if ( isset( $config['id'] ) ) {
			$option_name = apply_filters( 'themeblvd_option_id', $config['id'] );
		}
		
	}
	
	// Return what we have, blank or not.
	return $option_name;
	
}

/**
 * Get theme options that correspond to the current language. 
 *
 * @since 1.0.2
 */

function tp_wpml_get_theme_options(){
	
	$options = array();
	
	// Only continue if WPML is running and the
	// current language constant has been defined.
	if( defined( 'ICL_LANGUAGE_CODE' ) ) {
	
		// Current language
		$current_lang = ICL_LANGUAGE_CODE;
		
		// Set default language
		$default_lang = 'en'; // backup
		$wpml_options = get_option( 'icl_sitepress_settings' );
		if( isset( $wpml_options['default_language'] ) ) 
			$default_lang = $wpml_options['default_language'];
		
		// Adjust theme settings to match language if 
		// it's different than the default language.
		if( $current_lang != $default_lang ) {
			$option_name = tb_wpml_get_option_name();
			$options = get_option( $option_name.'_'.$current_lang );
		}
		
	}
	return $options;
}

/**
 * Re-configure theme global settings if this isn't default 
 * language.
 *
 * When the theme runs on the frontend, the Theme Blvd framework 
 * creates a global array in which it stores all of the current 
 * theme's options. This makes it so every time themeblvd_get_option
 * is called, we don't have to pull from the database with WP's 
 * get_option.
 *
 * So, in this function, what we're doing is essentally setting up the 
 * global variable in the same way, but with the twist of taking into 
 * account the current language in pulling the option set. And then, 
 * we're hooking it with a priority "6" to template_redirect so it 
 * comes just AFTER the default framework function.
 *
 * Note: In some recent updates we've adjusted the global variable 
 * to get constructed at the "wp" action just before template_redirect, 
 * however, we'll leave this as template_redirect for now.
 *
 * @since 1.0.0
 */
 
function tb_wpml_options() {
	
	global $_themeblvd_theme_settings;
	
	// Get new options
	$new_options = tp_wpml_get_theme_options();
	
	// If WPML is installed, we should have a new set of 
	// options to apply to the global variable.
	if( $new_options )
		$_themeblvd_theme_settings = $new_options;
	
}

/**
 * Configure homepage layout.
 *
 * Since the theme options and the homepage layout are setup 
 * within the Theme Blvd framework in the same function, we 
 * need to write our own filter here to adjust the determined 
 * homepage layout based on the language.
 *
 * @since 1.0.2
 */

function tb_wpml_homepage_layout( $config ){

	// Only move forward if this is the posts homepage
	if( is_home() ) {
		
		$builder = false;
		$layout_post_id = '';
		$sidebar_layout = '';
		$featured = '';
		$featured_below = '';
		
		// Get new options
		$options = tp_wpml_get_theme_options();
		
		// Only move forward if WPML is installed and we've 
		// retrived options for current language properly.
		if( $options ) {
			if( isset( $options['homepage_content'] ) ){
				if( $options['homepage_content'] == 'custom_layout' && isset( $options['homepage_custom_layout'] ) ) {
					$layout_id = $options['homepage_custom_layout'];
					if( $layout_id ) {
						$builder = $layout_id;
						$layout_post_id = themeblvd_post_id_by_name( $layout_id, 'tb_layout' );
						$layout_settings = get_post_meta( $layout_post_id, 'settings', true );
						$sidebar_layout = $layout_settings['sidebar_layout'];
					} else {
						$builder = 'error';
					}
				}
			}
		}
		
		// Adjust featured area if needed
		if( $builder && $layout_post_id ) {

			// Adjust featured areas if needed
			$elements = get_post_meta( $layout_post_id, 'elements', true );
			
			$featured = themeblvd_featured_builder_classes( $elements, 'featured' );
			if( $featured )
				$config['featured'] = $featured;
			
			$featured_below = themeblvd_featured_builder_classes( $elements, 'featured_below' );
			if( $featured_below )
				$config['featured_below'] = $featured_below;

			// Modify builder for global config	
			$config['builder'] = $builder;
			$config['builder_post_id'] = $layout_post_id;

		}
		
		// Modify sidebar layout for global config if needed
		if( $sidebar_layout ) {
			
			// If sidebar layout is default, figure out the default sidebar layout
			if( $sidebar_layout == 'default' )
				$sidebar_layout = themeblvd_get_option( 'sidebar_layout', null, apply_filters( 'themeblvd_default_sidebar_layout', 'sidebar_right' ) );
			
			// Modify global config
			$config['sidebar_layout'] = $sidebar_layout;
			
		}
	
	}
	return $config;
}

/**
 * New display for action: themeblvd_breadcrumbs
 *
 * This function is a copy of the old tb_wpml_breadcrumbs 
 * function. It only gets added with Theme Blvd themes 
 * prior to framework v2.2. -- See tb_wpml_actions()
 * 
 * @since 1.1.3
 * @deprecated
 */

function tb_wpml_breadcrumbs_legacy() {
	
	// Fix for conflict with page.php and WPML 
	// CMS Nav v1.3 in older themes prior to TB 
	// framework v2.2
	if( is_page() )
		rewind_posts();

	wp_reset_query();
	global $post;
	$display = '';
	// Pages and Posts
	if( is_page() || is_single() )
		$display = get_post_meta( $post->ID, '_tb_breadcrumbs', true );
	// Standard site-wide option
	if( ! $display || $display == 'default' )
		$display = themeblvd_get_option( 'breadcrumbs', null, 'show' );
	// Disable on posts homepage
	if( is_home() )
		$display = 'hide';
	// Show breadcrumbs if not hidden
	if( $display == 'show' ) {
		$atts = array(
			//'delimiter' => '&raquo;', // Not using because plugin allows you to set it.
			'home' => themeblvd_get_local('home'),
			'home_link' => home_url(),
			'before' => '<span class="current">',
			'after' => '</span>'
		);
		$atts = apply_filters( 'themeblvd_breadcrumb_atts', $atts );	
		// Start output
		echo '<div id="breadcrumbs">';
		echo '<div class="breadcrumbs-inner tb-wpml-breadcrumbs">';
		echo '<div class="breadcrumbs-content">';
		do_action( 'icl_navigation_breadcrumb' ); // Display WPML breadcrumbs
		echo '</div><!-- .breadcrumbs-content (end) -->';
		do_action( 'tb_wpml_breadcrumbs_addon' );
		echo '<div class="clear"></div>';
		echo '</div><!-- .breadcrumbs-inner (end) -->';
		echo '</div><!-- #breadcrumbs (end) -->';
	}
}

/**
 * Action adjustments.
 *
 * Because the theme will run obviously run after this 
 * plugin, we will put any framework functions we want 
 * to unhook or swap here.
 *
 * @since 1.0.0
 */

function tb_wpml_actions(){
	
	// Only swap breadcrumbs if user has "WPML CMS Nav" add-on installed.
	if( class_exists( 'WPML_CMS_Navigation' ) ) {
		remove_action( 'themeblvd_breadcrumbs', 'themeblvd_breadcrumbs_default' );
		if( function_exists('themeblvd_show_breadcrumbs') )
			add_action( 'themeblvd_breadcrumbs', 'tb_wpml_breadcrumbs' );
		else
			add_action( 'themeblvd_breadcrumbs', 'tb_wpml_breadcrumbs_legacy' );
	}
	
	// Theme Locations
	$locations = tb_wpml_get_theme_locations();
	$options_name = tb_wpml_get_admin_page_id();
	$location_settings = get_option( $options_name );
	if( $locations ) {
		foreach( $locations as $id => $location ){
			if( isset( $location_settings[$id] ) ){
				if( $location_settings[$id] == 'true' ){
					add_action( $location['action'], 'tb_wpml_flaglist' );
				}
			}
		}
	}
}

/*-----------------------------------------------------------------------------------*/
/* Theme Options (Admin)
/*
/* The purpose of this section of our plugin is to allow the user to save theme
/* options based on each of the languages setup with WPML. This will result in a lot
/* redunant option selection, as most theme options won't actually effect the chosen 
/* language. However, this solution will provide the most flexibility.
/*-----------------------------------------------------------------------------------*/

/**
 * Add in options page to WP admin.
 * 
 * @since 1.1.0
 */

function tb_wpml_bridge_options_page_init() {
	
	// Theme Blvd Framework v2-2.2 only.
	if( ! defined( 'TB_FRAMEWORK_VERSION' ) || version_compare( TB_FRAMEWORK_VERSION, '2.3.0', '>=' ) )
		return;

	global $_themeblvd_theme_options_page;
	
	// Don't continue if the WPML plugin 
	// hasn't been installed.
	if( ! function_exists( 'icl_get_languages' ) )
		return;
	
	// If this is framework v2.2, un-hook default theme options page 
	// and replace with a modified version.
	if( is_object( $_themeblvd_theme_options_page ) ) {
		remove_action( 'admin_menu', array( $_themeblvd_theme_options_page, 'add_page' ) );
		add_action( 'admin_menu', 'tb_wpml_bridge_add_options_page' );
	}
	
}
add_action( 'after_setup_theme', 'tb_wpml_bridge_options_page_init', 1001 );

/**
 * Add options page that replaces default theme options page.
 * 
 * NOTE: This only happens if the user is running framework v2.2+.
 * 
 * @since 1.1.0
 */

function tb_wpml_bridge_add_options_page() {
	global $_themeblvd_theme_options_page;
	$admin_page = add_submenu_page( 'themes.php', __( 'Theme Options', 'tb_wpml' ), __( 'Theme Options', 'tb_wpml' ), themeblvd_admin_module_cap( 'options' ), themeblvd_get_option_name(), 'optionsframework_page' );
	add_action( 'admin_print_styles-'.$admin_page, array( $_themeblvd_theme_options_page, 'load_styles' ) );
	add_action( 'admin_print_scripts-'.$admin_page, array( $_themeblvd_theme_options_page, 'load_scripts' ) );
	add_action( 'admin_print_styles-'.$admin_page, 'optionsframework_mlu_css', 0 );
	add_action( 'admin_print_scripts-'.$admin_page, 'optionsframework_mlu_js', 0 );
}

/**
 * Initiate theme options after the framework has initiated it's default
 * theme options system.
 * 
 * In regards to the WPML plugin, we're using the default theme options
 * with the default WPML language. These default options are setup and
 * registered with the function "optionsframework_init" within the
 * framework.
 *
 * This function is hooked to "admin_init" with priority 11 so it comes 
 * just after the the framework's "optionsframework_init" function. Its
 * purpose is to register the additional settings groups for all WPML
 * languages other than the default language. This registration also links
 * these non-default languages up to this plugin's modified sanitation
 * function called "tb_wpml_optionsframework_validate".
 *
 * And additionally, this current function adds the action of including CSS 
 * files for all of the framework's admin modules.
 *
 * @since 1.0.0
 */

function tb_wpml_optionsframework_init() {
	
	// Theme Blvd Framework v2-2.2 only.
	if( ! defined( 'TB_FRAMEWORK_VERSION' ) || version_compare( TB_FRAMEWORK_VERSION, '2.3.0', '>=' ) )
		return;

	global $sitepress;

	// Don't continue if the WPML plugin 
	// hasn't been installed.
	if( ! is_object( $sitepress ) )
		return;
	
	// Global option name
	$option_name = tb_wpml_get_option_name();
	
	// Get all languages
	// $langs = icl_get_languages(); // Preferred way, but no longer works in wp-admin with WPML v2.6.3
	$langs = $sitepress->get_active_languages();
	
	// Set default language
	$default_lang = 'en'; // backup
	$wpml_options = get_option( 'icl_sitepress_settings' );
	if( isset( $wpml_options['default_language'] ) ) 
		$default_lang = $wpml_options['default_language'];
	
	// Register settings for each language only if its not the default 
	// language. The default language's options set wil be saved with 
	// no language code appended, and thus was already registered above.
	foreach( $langs as $key => $lang ) {
		if( $key != $default_lang ) {
			// Register settings
			register_setting( $option_name.'_'.$key, $option_name.'_'.$key, 'tb_wpml_optionsframework_validate' );
		}
	}
	
	// Add CSS files to framework's admin pages
	if( defined( 'TB_FRAMEWORK_VERSION' ) && version_compare( TB_FRAMEWORK_VERSION, '2.2.0', '>=' ) ) {
		add_action( 'admin_print_styles-appearance_page_'.themeblvd_get_option_name(),'tb_wpml_optionsframework_load_styles' );
		add_action( 'admin_print_styles-appearance_page_themeblvd_widget_areas','tb_wpml_optionsframework_load_styles' );
		add_action( 'admin_print_styles-toplevel_page_themeblvd_builder','tb_wpml_optionsframework_load_styles' );
		add_action( 'admin_print_styles-toplevel_page_themeblvd_sliders','tb_wpml_optionsframework_load_styles' );
		add_action( 'admin_print_styles-wpml_page_'.tb_wpml_get_admin_page_id(),'tb_wpml_optionsframework_load_styles' );
	} else {
		add_action( 'admin_print_styles-appearance_page_options-framework','tb_wpml_optionsframework_load_styles' );
		add_action( 'admin_print_styles-appearance_page_sidebar_blvd','tb_wpml_optionsframework_load_styles' );
		add_action( 'admin_print_styles-toplevel_page_builder_blvd','tb_wpml_optionsframework_load_styles' );
		add_action( 'admin_print_styles-toplevel_page_slider_blvd','tb_wpml_optionsframework_load_styles' );
	}
	
}
add_action( 'admin_init', 'tb_wpml_optionsframework_init', 11 ); // Priority 11 to execute AFTER Theme Blvd framework

/**
 * Load CSS files.
 * 
 * These are the CSS files used for Theme Blvd admin module pages 
 * only. Mainly they're intended for the Theme Options page only.
 * In the above function "tb_wpml_optionsframework_init" we make
 * sure to load these styles ONLY on our admin pages.
 *
 * @since 1.0.0
 */
 
function tb_wpml_optionsframework_load_styles() {
	wp_register_style( 'tb_wpml_optionsframework_styles', TB_WPML_BRIDGE_PLUGIN_URI . '/assets/css/options.css', false, '1.0' );
	wp_enqueue_style( 'tb_wpml_optionsframework_styles' );
}

/**
 * Setup sanitization for WPML's option set.
 *
 * The Theme Blvd options framework uses the function 
 * "optionsframework_validate" to santize options when they're 
 * saved. This is essentially a copy of that function with the 
 * slight modifications of allowing the user to "match" current 
 * language's options to default language's options. 
 * 
 * This new function is used when we call register_setting up
 * above in the "tb_wpml_optionsframework_init" function in order 
 * to register an option set specific to current theme for each 
 * language outside of the default language.
 *
 * @since 1.0.0
 */

function tb_wpml_optionsframework_validate( $input ) {
	
	// Match language's options to default language's options.
	if ( isset( $_POST['match'] ) ) {
		$default_lang_options = get_option( $_POST['option_page_base'] );
		return $default_lang_options;
	} 
	
	// Get unique identifier for this theme's options.
	$option_name = tb_wpml_get_option_name();
		
	// Restore Defaults.
	//
	// In the event that the user clicked the "Restore Defaults"
	// button, the options defined in the theme's options.php
	// file will be added to the option for the active theme.
	
	if ( isset( $_POST['reset'] ) ) {
		add_settings_error( $option_name.'_'.$_POST['current_lang'], 'restore_defaults', __( 'Default options restored.', 'tb_wpml' ), 'error fade' );
		return of_get_default_values();
	}
	
	// Clear options.
	//
	// This gives the user a chance to clear the options from 
	// the database.
	
	/* There's no button to do this with WPML Bridge
	 
	if ( isset( $_POST['clear'] ) ) {
		add_settings_error( $option_name, 'restore_defaults', __( 'Options cleared from database.', 'tb_wpml' ), 'error fade' );
		return null;
	}
	*/
	 
	// Udpdate Settings.
	// 
	// This runs through all registered options and sanitizes them. 
	// However, the catch here that is a bit different than the 
	// original options framework, is that we first check if each 
	// option was present in the $input before adding it our sanitized 
	// options to return.
	//
	// By doing this, when we save from the customizer, if it doesn't 
	// include ALL registered options, it will not effect those options 
	// upon saving that weren't included with the customizer.
	 
	$clean = array();
	$options = themeblvd_get_formatted_options();
	foreach( $options as $option ){

		// Skip if we don't have an ID or type.
		if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) )
			continue;
		
		// Make sure ID is formatted right.
		$id = preg_replace( '/\W/', '', strtolower( $option['id'] ) );

		// Skip if this is the customizer and current option wasn't 
		// sent in the input. This current method means we can't have 
		// any checkboxes or multichecks in the customizer.
		// (something to fix later hopefully)
		if( isset( $_POST['customized'] ) && ! isset( $input[$id] ) )
			continue;

		// Set checkbox to false if it wasn't sent in the $_POST
		if( 'checkbox' == $option['type'] && ! isset( $input[$id] ) )
			$input[$id] = '0';

		// Set each item in the multicheck to false if it wasn't sent in the $_POST
		if( 'multicheck' == $option['type'] && ! isset( $input[$id] ) )
			foreach ( $option['options'] as $key => $value )
				$input[$id][$key] = '0';

		// For a value to be submitted to database it must pass through a sanitization filter
		if( has_filter( 'themeblvd_sanitize_' . $option['type'] ) )
			$clean[$id] = apply_filters( 'themeblvd_sanitize_' . $option['type'], $input[$id], $option );
		elseif( has_filter( 'of_sanitize_' . $option['type'] ) )
			$clean[$id] = apply_filters( 'of_sanitize_' . $option['type'], $input[$id], $option );
			
	}
	
	// Add update message for page re-fresh
	add_settings_error( $option_name.'_'.$_POST['current_lang'], 'save_options', __( 'Options saved.', 'tb_wpml' ), 'updated fade' );
	
	// Return sanitized options
	return $clean;
}

/**
 * Builds the options panel.
 *
 * We're modifying the framework's theme options page slightly. 
 * The theme's options framework module already declares a
 * function called "optionsframework_page" and so by declaring it
 * here, we're overriding when WordPress arrives at the options 
 * framework later in its loading process.
 * 
 * So, to start this function, we just copied the function from 
 * the framework and made the following modifications:
 * 
 * (1) Add check for WPML plugin, and if it's not installed, kill 
 * the options page. 
 * 
 * (2) Add in all of our needed items to pull new inserted language 
 * variable in the $_GET and match it against all current WPML 
 * languages.
 * 
 * (3) When determing the $option_name parameter to pull the correct 
 * current option settings, we've added in the current language code 
 * into the mix.
 * 
 * (4) Moved wrapping <form> tags wider to make sure they include the
 * action "themeblvd_admin_module_header" WITHIN the form. This makes 
 * it possible for our hooked WPML bridge header to have a form button 
 * to match current language option set to default language.
 *
 * (5) When "settings_fields" is called, we've made it use a dynamic 
 * variable called $settings_fields instead of the static string 
 * 'optionsframework' -- This allows us to pull WordPress's hidden form 
 * fields for different option sets based on current language.
 * 
 * (6) Added button at bottom of form that allows user to match current 
 * language's option set to default option set. This will only show if
 * we're not currently on the default language.
 *
 * @since 1.0.0
 */

if( ! function_exists( 'optionsframework_page' ) ) { // This check is only needed for initial plugin activation
	function optionsframework_page() {
		
		global $_GET;
		global $sitepress;
		
		// Don't continue if the WPML plugin 
		// hasn't been installed.
		if( ! is_object( $sitepress ) ) {
			echo '<div class="tb-wpml-warning">';
			echo '<p><strong>'.__( 'WARNING: You\'ve activated the Theme Blvd WPML Bridge plugin, but you haven\'t installed the official WPML plugin. You\'ll need that plugin installed in order to move forward.', 'tb_wpml' ).'</strong></p>';
			echo '<p><a href="http://wpml.org/?aid=8007&affiliate_key=MNKoTksdyWns" target="_blank">'.__('Download WPML Plugin', 'tb_wpml' ).'</p></a>';
			echo '</div>';
			return;
		}
	
		// Get all languages
		// $langs = icl_get_languages(); // Preferred way, but no longer works in wp-admin with WPML v2.6.3
		$langs = $sitepress->get_active_languages();
		
		// Setup check array
		$langs_check = array();
		foreach( $langs as $key => $lang )
			$langs_check[] = $key;
		
		// Set default language
		$default_lang = 'en'; // backup
		$wpml_options = get_option( 'icl_sitepress_settings' );
		if( isset( $wpml_options['default_language'] ) ) 
			$default_lang = $wpml_options['default_language'];
		
		// Set current options language
		$current_lang = $default_lang;
		if( isset( $_GET['themeblvd_lang'] ) )
			$current_lang = $_GET['themeblvd_lang'];
		if( ! in_array( $current_lang, $langs_check ) )
			$current_lang = $default_lang;
		
		// Gets the unique option id
		$option_name = tb_wpml_get_option_name();
		$option_base = $option_name;
		
		// And here's our twist to the system --
		// If we're editing options for a specific language 
		// that is NOT the default, we adjust the options 
		// framework ID to append '_{language}'.
		if( $default_lang != $current_lang )
			$option_name .= '_'.$current_lang;
		
		// Get settings and form
		$settings = get_option($option_name);
	    $options = themeblvd_get_formatted_options();
		if( function_exists( 'themeblvd_option_fields' ) ) // If themeblvd_option_fields exists, we're using framework v2.2+
			$return = themeblvd_option_fields( $option_name, $options, $settings  );
		else
			$return = optionsframework_fields( $option_name, $options, $settings  );
		
		settings_errors();
		?>
		<div class="wrap">
			<form action="options.php" method="post">
				<div class="admin-module-header">
					<?php do_action( 'themeblvd_admin_module_header', 'options' ); ?>
				</div>
			    <?php screen_icon( 'themes' ); ?>
			    <h2 class="nav-tab-wrapper">
			        <?php echo $return[1]; ?>
			    </h2>
				<div class="metabox-holder">
				    <div id="optionsframework" class="tb-options-js">
						<input type="hidden" value="<?php echo $option_base; ?>" name="option_page_base">
						<input type="hidden" value="<?php echo $current_lang; ?>" name="current_lang">
						<?php settings_fields($option_name); ?>
						<?php echo $return[0]; /* Settings */ ?>
				        <div id="optionsframework-submit">
						<input type="submit" class="button-primary" name="update" value="<?php esc_attr_e( 'Save Options', 'tb_wpml' ); ?>" />
						<input type="submit" class="reset-button button-secondary" name="reset" value="<?php esc_attr_e( 'Restore Defaults', 'tb_wpml' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to reset. Any theme settings will be lost!', 'tb_wpml' ) ); ?>' );" />
						<?php if( $current_lang != $default_lang ) : ?>
							<input type="submit" class="reset-button button-secondary" name="match" value="<?php esc_attr_e( 'Match Default Language', 'tb_wpml' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to match options. You will lose your current settings for this language and they will be matched to whatever you\'ve set for your default language.', 'tb_wpml' ) ); ?>' );" />
						<?php endif; ?>
						<div class="clear"></div>
						</div>
						<div class="tb-footer-text">
							<?php do_action( 'themeblvd_options_footer_text' ); ?>
						</div><!-- .tb-footer-text (end) -->
					</div> <!-- #container (end) -->
					<div class="admin-module-footer">
						<?php do_action( 'themeblvd_admin_module_footer', 'options' ); ?>
					</div>
				</div>
			</form>
		</div><!-- .wrap (end) -->
	<?php
	}
}

/**
 * Add WPML title to top of theme options along with menu to switch 
 * language.
 * 
 * This is the header that allows the user to switch between option 
 * sets for each language. Its hooked onto "themeblvd_admin_module_header"
 * which is called from the above "optionsframework_page" function.
 *
 * @since 1.0.0
 */

function tb_wpml_admin_module_header( $page ) {
	
	// Theme Blvd Framework v2-2.2 only.
	if( ! defined( 'TB_FRAMEWORK_VERSION' ) || version_compare( TB_FRAMEWORK_VERSION, '2.3.0', '>=' ) )
		return;

	global $sitepress;
	
	$current_screen = get_current_screen();
	$possible_admin_pages = array( 'appearance_page_options-framework', 'appearance_page_'.tb_wpml_get_option_name() );
	
	if( in_array( $current_screen->base, $possible_admin_pages ) ) {
		
		// Don't continue if the WPML plugin 
		// hasn't been installed.
		if( ! is_object( $sitepress ) )
			return;
		
		// Get all languages
		// $langs = icl_get_languages(); // Preferred way, but no longer works in wp-admin with WPML v2.6.3
		$langs = $sitepress->get_active_languages();
	
		// Setup check array
		$langs_check = array();
		foreach( $langs as $key => $lang )
			$langs_check[] = $key;
		
		// Set default language
		$default_lang = 'en'; // backup
		$wpml_options = get_option( 'icl_sitepress_settings' );
		if( isset( $wpml_options['default_language'] ) ) 
			$default_lang = $wpml_options['default_language'];
		
		// Set current options language
		$current_lang = $default_lang;
		if( isset( $_GET['themeblvd_lang'] ) )
			$current_lang = $_GET['themeblvd_lang'];
		if( ! in_array( $current_lang, $langs_check ) )
			$current_lang = $default_lang;
		
		// Options page ID
		$options_page = version_compare( TB_FRAMEWORK_VERSION, '2.2.0', '>=' ) ? themeblvd_get_option_name() : 'options-framework';
		
		// Current Flag
		$current_flag = $sitepress->get_flag( $current_lang );
        if( $current_flag->from_template ){
            $wp_upload_dir = wp_upload_dir();
            $current_flag_url = $wp_upload_dir['baseurl'].'/flags/'.$current_flag->flag;
        } else {
            $current_flag_url = ICL_PLUGIN_URL.'/res/flags/'.$current_flag->flag;
        }
		?>
		<div class="tb-wpml-header">
			<h3>
				<span class="tb-wpml-flag"><img src="<?php echo $current_flag_url; ?>" /></span>
				<?php printf( __( '%1$s Theme Options', 'tb_wpml' ), $langs[$current_lang]['translated_name'] ); ?>
			</h3>
			<span class="tb-wpml-logo">Theme Blvd WPML Bridge</span>
			<div class="tb-wpml-nav">
				<?php if( $langs ) : ?>
					<ul>
						<?php foreach( $langs as $key => $lang ) : ?>
							<li<?php if( $key == $current_lang ) echo ' class="active"'; ?>>
								<a href="?page=<?php echo $options_page; ?>&themeblvd_lang=<?php echo $key ?>">
									<?php echo $lang['display_name']; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if( $current_lang != $default_lang ) : ?>
					<input type="submit" class="reset-button button-secondary" name="match" value="<?php esc_attr_e( 'Match Default Language', 'tb_wpml' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to match options. You will lose your current settings for this language and they will be matched to whatever you\'ve set for your default language.', 'tb_wpml' ) ); ?>' );" />
				<?php endif; ?>
			</div><!-- .tb-wpml-nav (end) -->
		</div><!-- .tb-wpml-header (end) -->
		<?php
	}
}
add_action( 'themeblvd_admin_module_header', 'tb_wpml_admin_module_header');

/*-----------------------------------------------------------------------------------*/
/* Settings page
/*
/* This section adds an Admin page to the WPML menu. Currently this settings page 
/* only include options for displaying flags in theme locations. The user can select 
/* from their current Theme Blvd theme's supported locations.
/*
/* This section has many similar processes to the previous section in working with
/* theme options, however keep in mind that everything here is completely separate.
/*-----------------------------------------------------------------------------------*/

/**
 * Initiate settings page.
 *
 * @since 1.0.0
 */

function tb_wpml_admin_page_init() {
	
	// Theme Blvd Framework v2-2.2 only.
	if( ! defined( 'TB_FRAMEWORK_VERSION' ) || version_compare( TB_FRAMEWORK_VERSION, '2.3.0', '>=' ) )
		return;

	// Double check for WPML
	if( ! defined( 'ICL_PLUGIN_PATH' ) )
		return;

	if( class_exists( 'Theme_Blvd_Options_Page' ) ) {
		
		global $_tb_wpml_admin;
		
		$theme_data = wp_get_theme( get_stylesheet() );
		$theme_name = $theme_data->get('Name');
		
		// Use framework v2.2+ options class and skip using all 
		// functions below in this section.
		$args = array(
			'parent'		=> apply_filters( 'icl_menu_main_page', basename( ICL_PLUGIN_PATH ).'/menu/languages.php' ),
			'page_title' 	=> $theme_name.' '.__( 'WPML Options', 'tb_wpml' ),
			'menu_title' 	=> $theme_name,
			'cap'			=> apply_filters( 'tb_wpml_settings_cap', 'edit_theme_options' ),
			'icon'			=> 'wpml',
			'closer'		=> false
		);
		$_tb_wpml_admin = new Theme_Blvd_Options_Page( tb_wpml_get_admin_page_id(), tb_wpml_get_options(), $args );
	
	} else {
		// Old way of setting up options page manually
		add_action( 'admin_init', 'tb_wpml_admin_page_register' );
		add_action( 'admin_menu', 'tb_wpml_add_admin_page', 999 ); // Always place after WPML's add-ons
	}

}
add_action( 'init', 'tb_wpml_admin_page_init' );

/**
 * Register settings page.
 *
 * @since 1.0.0
 */

function tb_wpml_admin_page_register() {
	// Get options name
	$option_name = tb_wpml_get_admin_page_id();
	// If the option has no saved data, load the defaults
	if ( ! get_option( $option_name ) ) {
		// Get default values
		$values = tb_wpml_get_default_values();
		// Add option with default settings
		if( $values )
			add_option( $option_name, $values );
	}
	// Registers the settings fields and callback
	register_setting( 'optionsframework_tb_wpml', $option_name, 'tb_wpml_validate' );
}

/**
 * Add settings page.
 *
 * @since 1.0.0
 */

function tb_wpml_add_admin_page(){
	// Only move forward if WPML is installed and the proper constants are defined.
	if( defined( 'ICL_PLUGIN_PATH' ) && defined( 'WPML_CMS_NAV_PLUGIN_PATH' ) ) {
		// Get theme name.
		$theme_name = '';
		$theme_id = '';
		// Use wp_get_theme for WP 3.4+
		$theme_data = wp_get_theme( get_stylesheet() );
		$theme_name = $theme_data->get('Name');
		$theme_id = preg_replace('/\W/', '', strtolower( $theme_name ) );
		// Make sure vars are set and this is a Theme Blvd theme.
		if( $theme_name && $theme_id && defined( 'TB_FRAMEWORK_VERSION' ) ) {
			// Get top level WPML plugin page
			$top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH).'/menu/languages.php');
			// Add subpage
	        $of_page = add_submenu_page( $top_page, $theme_name, $theme_name, 'manage_options', 'tb_wpml_'.$theme_id, 'tb_wpml_display_admin_page' );
			// Adds actions to hook in the required css and javascript
			add_action( "admin_print_styles-$of_page",'optionsframework_load_styles' );
			add_action( "admin_print_styles-$of_page",'tb_wpml_optionsframework_load_styles' );
			add_action( "admin_print_scripts-$of_page", 'optionsframework_load_scripts' );
		}
	}
}

/**
 * Get default values.
 *
 * @since 1.0.0
 */

function tb_wpml_get_default_values() {
	$values = array();
	$config = tb_wpml_get_options();
	foreach ( $config as $option ) {
		if ( ! isset( $option['id'] ) || ! isset( $option['std'] ) || ! isset( $option['type'] ) )
			continue;
		if ( has_filter( 'themeblvd_sanitize_' . $option['type'] ) )
			$values[$option['id']] = apply_filters( 'themeblvd_sanitize_' . $option['type'], $option['std'], $option );
		elseif ( has_filter( 'of_sanitize_' . $option['type'] ) )
			$values[$option['id']] = apply_filters( 'of_sanitize_' . $option['type'], $option['std'], $option );
	}
	return $values;
}

/**
 * Validate settings page.
 *
 * @since 1.0.0
 */

function tb_wpml_validate( $input ) {
	
	// Restore defaults
	if ( isset( $_POST['reset'] ) ) {
		add_settings_error( 'options-framework', 'restore_defaults', __( 'Default options restored.', 'tb_wpml' ), 'updated fade' );
		return tb_wpml_get_default_values();
	}

	// Update Settings
	if ( isset( $_POST['update'] ) ) {
		$clean = array();
		$options = tb_wpml_get_options();
		foreach ( $options as $option ) {
			// Skip if option ID or type is not set
			if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) )
				continue;
			// Format ID
			$id = preg_replace( '/\W/', '', strtolower( $option['id'] ) );
			// Set checkbox to false if it wasn't sent in the $_POST
			if ( 'checkbox' == $option['type'] && ! isset( $input[$id] ) ) {
				$input[$id] = '0';
			}
			// Set each item in the multicheck to false if it wasn't sent in the $_POST
			if ( 'multicheck' == $option['type'] && ! isset( $input[$id] ) ) {
				foreach ( $option['options'] as $key => $value ) {
					$input[$id][$key] = '0';
				}
			}
			// For a value to be submitted to database it must pass through a sanitization filter
			if( has_filter( 'themeblvd_sanitize_' . $option['type'] ) ) {
				$clean[$id] = apply_filters( 'themeblvd_sanitize_' . $option['type'], $input[$id], $option );
			} elseif( has_filter( 'of_sanitize_' . $option['type'] ) ) {
				$clean[$id] = apply_filters( 'of_sanitize_' . $option['type'], $input[$id], $option );
			}
		}
		add_settings_error( 'options-framework', 'save_options', __( 'Options saved.', 'tb_wpml' ), 'updated fade' );
		return $clean;
	}

	// Request not recognized.
	return tb_wpml_get_default_values();
}

/**
 * Get admin page ID.
 *
 * @since 1.0.0
 */

function tb_wpml_get_admin_page_id() {
	// Get global options ID
	$option_name = tb_wpml_get_option_name();
	// Add on our plugin's unique identifier
	$option_name .= '_tb_wpml';
	// Return with filters applied	
	return apply_filters( 'tb_wpml_admin_page_id', $option_name );
}

/**
 * Display settings page.
 *
 * @since 1.0.0
 */
 
function tb_wpml_display_admin_page() {
	// Use wp_get_theme for WP 3.4+
	$theme_data = wp_get_theme( get_stylesheet() );
	$theme_name = $theme_data->get('Name');
	// Setup options
	$option_name = tb_wpml_get_admin_page_id();
	$settings = get_option( $option_name );
    $options = tb_wpml_get_options();
	$return = optionsframework_fields( $option_name, $options, $settings, false );
	settings_errors();
	?>
	<div class="wrap">
	    <?php screen_icon( 'wpml' ); ?>
	    <h2><?php echo $theme_name.' '.__( 'WPML Options', 'tb_wpml' ); ?></h2>
	    <div class="metabox-holder">
		    <div id="optionsframework">
				<form action="options.php" method="post">
					<?php settings_fields( 'optionsframework_tb_wpml' ); ?>
					<?php echo $return[0]; /* Settings */ ?>
			        <div id="optionsframework-submit">
						<input type="submit" class="button-primary" name="update" value="<?php esc_attr_e( 'Save Options', 'tb_wpml' ); ?>" />
			            <input type="submit" class="reset-button button-secondary" name="reset" value="<?php esc_attr_e( 'Restore Defaults', 'tb_wpml' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to reset. Any theme settings will be lost!', 'tb_wpml' ) ); ?>' );" />
			            <div class="clear"></div>
					</div>
				</form>
				<div class="tb-footer-text">
					<?php do_action( 'themeblvd_options_footer_text' ); ?>
				</div><!-- .tb-footer-text (end) -->
			</div><!-- #optionsframework (end) -->
		</div><!-- .metabox-holder (end) -->
		<div class="admin-module-footer">
			<?php do_action( 'themeblvd_admin_module_footer', 'options' ); ?>
		</div>
	</div><!-- .wrap (end) -->
	<?php	
}

/**
 * Get theme locations.
 *
 * @since 1.0.0
 */

function tb_wpml_get_theme_locations() {
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
 * Setup options formatted for options framework.
 *
 * @since 1.0.0
 */

function tb_wpml_get_options() {
	
	// Start options and location section
	$options = array(
		/*
		'start_display_tab' => array(
			'name' => __( 'Display', 'tb_wpml' ),
			'type' => 'heading'
		),
		*/
		'start_locations' => array( 
			'name' => __( 'Theme Locations', 'tb_wpml' ),		
			'type' => 'section_start',
			'desc' => __( 'In this section, you can select where you\'d like the Theme Blvd WPML Bridge\'s flaglists to display. You can see below all of your current theme\'s supported locations.<br><br>Note: This is completely separate from WPML\'s <a href="admin.php?page=sitepress-multilingual-cms/menu/languages.php#lang-sec-3">Language switcher options</a>.', 'tb_wpml' )
		)
	);
	
	// Setup dynamic options based on theme locations
	$theme_locations = tb_wpml_get_theme_locations();
	if( $theme_locations ) {
		foreach( $theme_locations as $id => $location) {
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
	
	/* We will add this section in a later version
	// Developer info section
	$developer_section = array(
		'start_dev_tab' => array(
			'name' => __( 'Developers', 'tb_wpml' ),
			'type' => 'heading'
		),
		'start_dev' => array( 
			'name' => __( 'Developer Information', 'tb_wpml' ),		
			'type' => 'section_start',
			'desc' => __( 'This section contains some general information for developers working with WPML and placing elements within a Theme Blvd theme from a <a href="http://www.wpjumpstart.com/tutorial/the-child-theme-concept/" target="_blank">Child theme</a>.', 'tb_wpml' )
		),
		'dev_info' => array(
			'name' => __( 'Framework Action Hooks', 'tb_wpml' ),	
			'type' => 'info',
			'desc' => __( 'hello?', 'tb_wpml' ),
		),
		'end_dev' => array( 	
			'type' => 'section_end'
		)
		
	);
	// Merge sections
	$options = array_merge( $options, $developer_section );
	*/
	
	// Return with filters applied
	return apply_filters( 'tb_wpml_options', $options );
}