<?php

namespace StudioRudeBox\SubOrdernator;

use StudioRudeBox\SubOrdernator\Admin\Assets;
use StudioRudeBox\SubOrdernator\Admin\OrderColumns;
use StudioRudeBox\SubOrdernator\Admin\OrderFilter;
use StudioRudeBox\SubOrdernator\Admin\OrderMetaBox;
use StudioRudeBox\SubOrdernator\Admin\OrderSorting;

class Plugin {

    const META_KEY        = 'srb_subordernator_order_reference';
    const VERSION         = '2.3.1';
    const LOCKED_STATUSES = [ 'completed', 'failed', 'cancelled', 'refunded' ];

    private string $url;

    public function __construct( string $plugin_file ) {
        $this->url = plugin_dir_url( $plugin_file );
    }

    public function run(): void {
        if ( ! is_admin() ) {
            return;
        }

        ( new Assets( $this->url, self::VERSION ) )->register();
        ( new OrderMetaBox( self::META_KEY, self::LOCKED_STATUSES ) )->register();
        ( new OrderColumns( self::META_KEY ) )->register();
        ( new OrderFilter( self::META_KEY ) )->register();
        ( new OrderSorting( self::META_KEY, self::LOCKED_STATUSES ) )->register();
    }
}
