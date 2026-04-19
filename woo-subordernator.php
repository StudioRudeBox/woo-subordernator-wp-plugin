<?php
/**
 * Plugin Name: SubOrdernator for WooCommerce
 * Plugin URI: https://github.com/StudioRudeBox/woo-subordernator-wp-plugin
 * Description: Add the ability to link a WooCommerce order to another order, creating a parent–suborder relationship.
 * Version: 2.3.1
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

require_once __DIR__ . '/vendor/autoload.php';

( new StudioRudeBox\SubOrdernator\Plugin( __FILE__ ) )->run();
