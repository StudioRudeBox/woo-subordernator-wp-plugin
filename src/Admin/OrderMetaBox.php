<?php

namespace StudioRudeBox\SubOrdernator\Admin;

class OrderMetaBox {

    public function __construct( private string $meta_key, private array $locked_statuses ) {}

    public function register(): void {
        add_action( 'woocommerce_admin_order_data_after_order_details', [ $this, 'render_field' ] );
        add_action( 'woocommerce_process_shop_order_meta', [ $this, 'save_field' ] );
        add_filter( 'postbox_classes_shop_order_woocommerce-order-data', [ $this, 'metabox_classes' ] );
        add_action( 'wp_ajax_srb_search_orders', [ $this, 'search_orders_ajax' ] );
        add_action( 'wp_ajax_srb_disconnect_suborder', [ $this, 'disconnect_suborder_ajax' ] );
    }

    public function metabox_classes( array $classes ): array {
        global $post;
        if ( ! $post ) return $classes;
        $parent_id   = get_post_meta( $post->ID, $this->meta_key, true );
        $classes[]   = is_numeric( $parent_id ) ? 'srb-sub-order' : 'srb-main-order';
        return $classes;
    }

    public function render_field( $order ): void {
        $current_order_id  = $order->get_id();
        $selected_order_id = get_post_meta( $current_order_id, $this->meta_key, true );

        if ( empty( $selected_order_id ) && isset( $_GET['srb_parent_order_id'] ) ) {
            $selected_order_id = intval( $_GET['srb_parent_order_id'] );
        }

        $is_suborder = ! empty( $selected_order_id );

        $suborder_ids  = ! $is_suborder ? get_posts( [
            'post_type'      => 'shop_order',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [ [
                'key'     => $this->meta_key,
                'value'   => (string) $current_order_id,
                'compare' => '=',
            ] ],
        ] ) : [];
        $suborders     = array_filter( array_map( 'wc_get_order', $suborder_ids ) );
        $has_suborders = ! empty( $suborders );
        $is_locked   = in_array( $order->get_status(), $this->locked_statuses, true );

        $parent_custom_number = $is_suborder ? get_post_meta( intval( $selected_order_id ), '_order_number', true ) : '';
        $parent_display       = $is_suborder
            ? ( $parent_custom_number ? $selected_order_id . ' - #' . $parent_custom_number : '#' . $selected_order_id )
            : '';

        $badge_label     = $is_suborder
            ? sprintf( __( 'Sub-order of %s', 'woo-subordernator' ), $parent_display )
            : __( 'Main order', 'woo-subordernator' );

        $parent_edit_url = $is_suborder ? get_edit_post_link( intval( $selected_order_id ) ) : '#';

        printf(
            '<style>.woocommerce-order-data__heading::after { content: "%s"; }</style>',
            esc_js( $badge_label )
        );

        if ( ! $is_suborder && $has_suborders ) :
        ?>

        <input type="hidden" class="srb-disconnect-nonce" value="<?php echo esc_attr( wp_create_nonce( 'srb_disconnect_suborder' ) ); ?>">

        <p class="form-field form-field-wide srb-suborders-field">
            <h3><?php esc_html_e( 'Sub orders', 'woo-subordernator' ); ?></h3>
            <ul class="srb-suborder-list">
                <?php foreach ( $suborders as $sub ) :
                    $sub_id     = $sub->get_id();
                    $sub_number = get_post_meta( $sub_id, '_order_number', true );
                    $sub_display = $sub_number ? $sub_id . ' - #' . $sub_number : '#' . $sub_id;
                    $sub_status  = wc_get_order_status_name( $sub->get_status() );
                    $sub_url     = get_edit_post_link( $sub_id, 'raw' );
                ?>
                <li>
                    <a href="<?php echo esc_url( $sub_url ); ?>" target="_blank">
                        <?php echo esc_html( $sub_display . ' (' . $sub_status . ')' ); ?>
                    </a>
                    <?php if ( ! $is_locked ) : ?>
                    <button type="button" class="button button-small srb-btn-disconnect" data-sub-order-id="<?php echo esc_attr( $sub_id ); ?>"><?php esc_html_e( 'Disconnect', 'woo-subordernator' ); ?></button>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if ( ! $is_locked ) : ?>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=shop_order&srb_parent_order_id=' . $current_order_id ) ); ?>" class="button srb-btn-create-suborder"><?php esc_html_e( '+ Add sub-order', 'woo-subordernator' ); ?></a>
            <?php endif; ?>
        </p>

        <?php elseif ( $is_suborder ) : ?>

        <p class="form-field form-field-wide srb-connection-field">
            <h3><?php esc_html_e( 'Parent order', 'woo-subordernator' ); ?></h3>
            <a href="<?php echo esc_url( $parent_edit_url ); ?>" target="_blank">
                <?php echo esc_html( $parent_display ); ?>
            </a>
        </p>

        <?php else : ?>

        <?php if ( ! $is_locked && $current_order_id ) : ?>
        <p class="form-field form-field-wide srb-suborders-field">
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=shop_order&srb_parent_order_id=' . $current_order_id ) ); ?>" class="button srb-btn-create-suborder"><?php esc_html_e( '+ Add sub-order', 'woo-subordernator' ); ?></a>
        </p>
        <?php endif; ?>

        <?php endif; ?>
        <?php
    }

    public function save_field( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( $order && in_array( $order->get_status(), $this->locked_statuses, true ) ) {
            return;
        }

        $post_data = $_POST[ $this->meta_key ] ?? null;

        if ( isset( $post_data ) && ( is_numeric( $post_data ) || $post_data === '' ) ) {
            if ( $post_data === '' || intval( $post_data ) === 0 ) {
                delete_post_meta( $order_id, $this->meta_key );
            } else {
                update_post_meta( $order_id, $this->meta_key, sanitize_text_field( $post_data ) );
            }
        }
    }

    public function search_orders_ajax(): void {
        if ( ! check_ajax_referer( 'srb_search_orders', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid nonce', 403 );
        }

        $q                 = ltrim( sanitize_text_field( $_GET['q'] ?? '' ), '#' );
        $exclude_id        = intval( $_GET['exclude'] ?? 0 );
        $exclude_parent_id = intval( $_GET['exclude_parent'] ?? 0 );

        if ( strlen( $q ) < 1 ) {
            wp_send_json_success( [] );
        }

        $wc_locked        = array_map( fn( $s ) => 'wc-' . $s, $this->locked_statuses );
        $allowed_statuses = array_keys( array_diff_key( wc_get_order_statuses(), array_flip( $wc_locked ) ) );

        $base = [
            'limit'  => 20,
            'type'   => 'shop_order',
            'return' => 'objects',
            'status' => $allowed_statuses,
        ];

        $found = [];

        // Search by post ID
        if ( is_numeric( $q ) ) {
            foreach ( wc_get_orders( $base + [ 'post__in' => [ intval( $q ) ] ] ) as $o ) {
                $found[ $o->get_id() ] = $o;
            }
        }

        // Search by _order_number meta (Sequential Order Numbers for WooCommerce)
        $number_args = $base;
        $number_args['meta_query'] = [
            [ 'key' => '_order_number', 'value' => $q, 'compare' => 'LIKE' ],
        ];
        foreach ( wc_get_orders( $number_args ) as $o ) {
            $found[ $o->get_id() ] = $o;
        }

        // Post-process: remove sub-orders and the current order (reliable regardless of WC version)
        foreach ( $found as $id => $o ) {
            $parent = get_post_meta( $id, $this->meta_key, true );
            if ( is_numeric( $parent ) || $id === $exclude_id || ( $exclude_parent_id && $id === $exclude_parent_id ) ) {
                unset( $found[ $id ] );
            }
        }

        $results = [];
        foreach ( $found as $order ) {
            $id            = $order->get_id();
            $custom_number = get_post_meta( $id, '_order_number', true );
            $display       = $custom_number ? $id . ' - #' . $custom_number : '#' . $id;
            $status        = wc_get_order_status_name( $order->get_status() );
            $results[]     = [
                'id'       => $id,
                'display'  => $display,
                'label'    => $display . ' (' . $status . ')',
                'edit_url' => get_edit_post_link( $id, 'raw' ),
            ];
        }

        wp_send_json_success( $results );
    }

    public function disconnect_suborder_ajax(): void {
        if ( ! check_ajax_referer( 'srb_disconnect_suborder', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid nonce', 403 );
            return;
        }
        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( 'Forbidden', 403 );
            return;
        }
        $sub_order_id = intval( $_POST['sub_order_id'] ?? 0 );
        if ( ! $sub_order_id ) {
            wp_send_json_error( 'Invalid order ID', 400 );
            return;
        }
        delete_post_meta( $sub_order_id, $this->meta_key );
        wp_send_json_success();
    }
}
