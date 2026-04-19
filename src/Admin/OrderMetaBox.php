<?php

namespace StudioRudeBox\SubOrdernator\Admin;

class OrderMetaBox {

    public function __construct( private string $meta_key ) {}

    public function register(): void {
        add_action( 'woocommerce_admin_order_data_after_order_details', [ $this, 'render_field' ] );
        add_action( 'woocommerce_process_shop_order_meta', [ $this, 'save_field' ] );
    }

    public function render_field( $order ): void {
        $current_order_id  = $order->get_id();
        $selected_order_id = get_post_meta( $current_order_id, $this->meta_key, true );

        if ( empty( $selected_order_id ) && isset( $_GET['srb_parent_order_id'] ) ) {
            $selected_order_id = intval( $_GET['srb_parent_order_id'] );
        }

        echo '<p class="form-field form-field-wide">';
        echo '<label for="srb_subordernator_order_reference">' . __( 'Link to a parent order ID (optional):', 'woo-subordernator' ) . '</label>';
        printf( '<input type="number" name="srb_subordernator_order_reference" value="%s" min="0" placeholder="order ID" />', $selected_order_id );
        echo '</p>';
    }

    public function save_field( int $order_id ): void {
        $post_data = $_POST[ $this->meta_key ] ?? null;

        if ( isset( $post_data ) && is_numeric( $post_data ) ) {
            update_post_meta( $order_id, $this->meta_key, sanitize_text_field( $post_data ) );
        }
    }
}
