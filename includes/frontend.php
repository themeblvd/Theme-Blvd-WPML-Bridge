<?php
/**
 * Get flag list.
 *
 * This function returns a simple list of flags for all
 * available languages for the current page.
 *
 * @since 1.0.0
 */
function tb_wpml_get_flaglist() {

	$output = '';

	// Get languages
	$langs = icl_get_languages();

	if( $langs ) {

		$output .= '<div class="tb-wpml-flaglist">';
		$output .= '<div class="tb-wpml-flaglist-inner">';
		$output .= '<ul>';

		foreach( $langs as $lang ) {

			$classes = $lang['language_code'];
			if( $lang['active'] )
				$classes .= ' active';

			$output .= '<li class="'.$classes.'">';
			$output .= '<a href="'.$lang['url'].'" title="'.$lang['translated_name'].'">';
			$output .= '<img src="'.$lang['country_flag_url'].'" alt="'.$lang['translated_name'].'" />';
			$output .= '</a>';
			$output .= '</li>';

		}

		$output .= '</ul>';
		$output .= '</div><!-- .tb-wpml-flaglist-inner (end) -->';
		$output .= '</div><!-- .tb-wpml-flaglist (end) -->';
	}

	return apply_filters( 'tb_wpml_flaglist', $output );
}

/**
 * Display flag list.
 *
 * Any compatible theme to automatically show the flaglist
 * will have do_action('themeblvd_wpml_nav') somewhere in
 * the theme.
 *
 * This can also be removed from automatically showing easily
 * from a Child theme with:
 * remove_action('themeblvd_wpml_nav', 'tb_wpml_flaglist' );
 *
 * @since 1.0.0
 */
function tb_wpml_flaglist() {
	echo tb_wpml_get_flaglist();
}
add_action( 'themeblvd_wpml_nav', 'tb_wpml_flaglist' );

/**
 * New display for action: themeblvd_breadcrumbs
 *
 * @since 1.0.0
 */
function tb_wpml_breadcrumbs() {
	if( themeblvd_show_breadcrumbs() ){
		?>
		<div id="breadcrumbs" class="tb-wpml-breadcrumbs">
			<div class="breadcrumbs-inner clearfix">
				<?php do_action( 'tb_wpml_breadcrumbs_before' ); ?>
				<div class="breadcrumbs-content">
					<div class="breadcrumb">
						<?php do_action( 'icl_navigation_breadcrumb' ); // Display WPML breadcrumbs ?>
					</div><!-- .breadcrumb (end) -->
				</div><!-- .breadcrumbs-content (end) -->
				<?php do_action( 'tb_wpml_breadcrumbs_addon' ); ?>
			</div><!-- .breadcrumbs-inner (end) -->
		</div><!-- #breadcrumbs (end) -->
		<?php
	}
}