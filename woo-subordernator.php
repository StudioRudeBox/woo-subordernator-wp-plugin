<?php
/**
 * Plugin Name: WooCommerce SubOrdernator
 * Plugin URI: https://github.com/StudioRudeBox/woo-subordernator-wp-plugin
 * Description: Add the ability to link a WooCommerce order to another order, creating a parent–suborder relationship.
 * Version: 2.3.2
 * Author: Studio Rude Box
 * Author URI: https://studiorudebox.nl
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-subordernator
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

spl_autoload_register( function ( string $class ): void {
    $prefix = 'StudioRudeBox\\SubOrdernator\\';
    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
    if ( is_file( $file ) ) {
        require $file;
    }
} );

( new StudioRudeBox\SubOrdernator\Plugin( __FILE__ ) )->run();
