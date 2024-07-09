<?php

/**
 * Plugin Name: Image Uploading API
 * Description: Provides widget for image uploads
 * Requires Plugins: woocommerce
 * Author: Artem Avvakumov
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 0.0.1
 */

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once 'includes.php';


define( 'IUA_VERSION', '0.0.1' );
define( 'IUA_TEXT_DOMAIN', 'image-upload-api' );

$plugin_root = __FILE__;

register_activation_hook( $plugin_root, array('Iua_Plugin', 'install' ) );
register_deactivation_hook( $plugin_root, array('Iua_Plugin', 'uninstall' ) );

/**** Initialise Plugin ****/

$iua_plugin = new Iua_Plugin( $plugin_root );
