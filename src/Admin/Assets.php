<?php

namespace StudioRudeBox\SubOrdernator\Admin;

class Assets {

    public function __construct(
        private string $url,
        private string $version,
    ) {}

    public function register(): void {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function enqueue(): void {
        wp_enqueue_style( 'woo-subordernator', $this->url . 'assets/css/style.css' );

        global $typenow;
        if ( $typenow === 'shop_order' ) {
            wp_enqueue_script( 'woo-subordernator', $this->url . 'assets/js/woo-subordernator.js', [], $this->version, true );
        }
    }
}
