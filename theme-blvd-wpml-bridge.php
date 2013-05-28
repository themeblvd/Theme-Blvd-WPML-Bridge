<?php
/*
Plugin Name: Theme Blvd WPML Bridge
Plugin URI: http://wpml.themeblvd.com
Description: This plugin creates a bridge between the Theme Blvd framework and the WPML plugin.
Version: 2.0.0
Author: Jason Bobich
Author URI: http://jasonbobich.com
License: GPL2
*/

/*
Copyright 2012 JASON BOBICH

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

define( 'TB_WPML_BRIDGE_PLUGIN_VERSION', '2.0.0' );
define( 'TB_WPML_BRIDGE_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'TB_WPML_BRIDGE_PLUGIN_URI', plugins_url( '' , __FILE__ ) );

/**
 * Legacy version of plugin for those using a Theme Blvd framework 
 * version prior to 2.3.
 */
include_once( TB_WPML_BRIDGE_PLUGIN_DIR . '/includes/legacy.php' );