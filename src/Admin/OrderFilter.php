<?php

namespace StudioRudeBox\SubOrdernator\Admin;

class OrderFilter {

    public function __construct( private string $meta_key ) {}

    public function register(): void {
        add_action( 'restrict_manage_posts', [ $this, 'render_filter' ] );
        add_action( 'pre_get_posts', [ $this, 'apply_filter' ] );
    }

    public function render_filter(): void {
        global $typenow;
        if ( $typenow !== 'shop_order' ) {
            return;
        }

        $selected = isset( $_GET['main_sub_order_filter'] ) ? $_GET['main_sub_order_filter'] : '';
        $options  = [
            'main' => __( 'Main orders', 'woo-subordernator' ),
            'sub'  => __( 'Sub orders', 'woo-subordernator' ),
        ];

        echo '<select name="main_sub_order_filter">';
        echo '<option value="" ' . selected( $selected, '', false ) . '>' . __( 'All orders', 'woo-subordernator' ) . '</option>';

        foreach ( $options as $key => $label ) {
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $selected, $key, false ) . '>' . esc_html( $label ) . '</option>';
        }

        echo '</select>';
    }

    public function apply_filter( \WP_Query $query ): void {
        global $pagenow;

        if ( $pagenow !== 'edit.php'
            || ! isset( $_GET['post_type'] )
            || $_GET['post_type'] !== 'shop_order'
            || ! isset( $_GET['main_sub_order_filter'] )
        ) {
            return;
        }

        $filter     = sanitize_text_field( $_GET['main_sub_order_filter'] );
        $meta_query = $query->get( 'meta_query' ) ?: [];

        if ( $filter === 'sub' ) {
            $meta_query[] = [
                'relation' => 'AND',
                [ 'key' => $this->meta_key, 'value' => '', 'compare' => '!=' ],
                [ 'key' => $this->meta_key, 'compare' => 'EXISTS' ],
            ];
        } elseif ( $filter === 'main' ) {
            $meta_query[] = [
                'relation' => 'OR',
                [ 'key' => $this->meta_key, 'compare' => 'NOT EXISTS' ],
                [ 'key' => $this->meta_key, 'value' => '', 'compare' => '=' ],
            ];
        }

        $query->set( 'meta_query', $meta_query );
    }
}
