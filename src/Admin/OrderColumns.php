<?php

namespace StudioRudeBox\SubOrdernator\Admin;

class OrderColumns {

    public function __construct( private string $meta_key ) {}

    public function register(): void {
        add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_columns_head' ], 20 );
        add_action( 'manage_shop_order_posts_custom_column', [ $this, 'add_columns_prefix' ], 1, 2 );
        add_action( 'manage_shop_order_posts_custom_column', [ $this, 'add_columns_content' ], 20, 2 );
    }

    public function add_columns_prefix( string $column, int $post_id ): void {
        if ( $column !== 'order_number' ) return;
        $parent_id = get_post_meta( $post_id, $this->meta_key, true );
        if ( is_numeric( $parent_id ) ) {
            echo '&#x21AA; ';
        }
    }

    public function add_columns_head( array $columns ): array {
        $new_columns = [];

        foreach ( $columns as $key => $column ) {
            $new_columns[ $key ] = $column;
            if ( $key === 'cb' ) {
                $new_columns['srb_subordernator_order_id'] = 'ID';
            }
        }

        return $new_columns;
    }

    public function add_columns_content( string $column, int $post_id ): void {
        $main_order_id = get_post_meta( $post_id, $this->meta_key, true );
        $is_suborder   = is_numeric( $main_order_id );

        if ( $column === 'srb_subordernator_order_id' ) {
            echo $post_id;
            printf(
                '<span class="srb-order-meta" data-order-id="%d" data-parent-id="%s" style="display:none"></span>',
                $post_id,
                esc_attr( $main_order_id )
            );
        }

    }
}
