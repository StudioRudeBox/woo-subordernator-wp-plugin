<?php

namespace StudioRudeBox\SubOrdernator\Admin;

class OrderSorting {

    public function __construct( private string $meta_key, private array $locked_statuses ) {}

    public function register(): void {
        add_filter( 'woocommerce_admin_order_actions', [ $this, 'add_create_suborder_action' ], 10, 2 );
        add_filter( 'posts_clauses', [ $this, 'sort_clauses' ], 10, 2 );
        add_filter( 'post_class', [ $this, 'row_class' ], 10, 3 );
    }

    public function add_create_suborder_action( array $actions, \WC_Order $order ): array {
        $parent_id = get_post_meta( $order->get_id(), $this->meta_key, true );
        if ( is_numeric( $parent_id ) || in_array( $order->get_status(), $this->locked_statuses, true ) ) {
            return $actions;
        }

        $url = admin_url( 'post-new.php?post_type=shop_order&srb_parent_order_id=' . $order->get_id() );
        $actions['srb_create_suborder'] = [
            'url'    => esc_url( $url ),
            'name'   => __( 'Create sub-order', 'woo-subordernator' ),
            'action' => 'srb-create-suborder',
        ];
        return $actions;
    }

    public function sort_clauses( array $clauses, \WP_Query $query ): array {
        global $wpdb;

        if ( ! $query->is_main_query() ) return $clauses;
        if ( $query->get( 'post_type' ) !== 'shop_order' ) return $clauses;
        if ( isset( $_GET['orderby'] ) ) return $clauses;

        $clauses['join'] .= $wpdb->prepare(
            " LEFT JOIN {$wpdb->postmeta} srb_pm ON srb_pm.post_id = {$wpdb->posts}.ID AND srb_pm.meta_key = %s AND srb_pm.meta_value != ''",
            $this->meta_key
        );

        $clauses['orderby'] = "CAST(COALESCE(srb_pm.meta_value, {$wpdb->posts}.ID) AS UNSIGNED) DESC,
            CASE WHEN srb_pm.meta_value IS NULL THEN 0 ELSE 1 END ASC,
            {$wpdb->posts}.ID DESC";

        return $clauses;
    }

    public function row_class( array $classes, array|string $class, int $post_id ): array {
        global $typenow, $wpdb;
        if ( $typenow !== 'shop_order' ) return $classes;

        static $main_order_index   = 0;
        static $stripe_map         = [];
        static $has_children       = null; // [ parent_id => true ]
        static $last_child_of      = null; // [ parent_id => min_child_id ]

        if ( $has_children === null ) {
            $has_children  = [];
            $last_child_of = [];
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT post_id, meta_value FROM {$wpdb->postmeta}
                  WHERE meta_key = %s AND meta_value != ''",
                $this->meta_key
            ) );
            foreach ( $rows as $row ) {
                $parent = (int) $row->meta_value;
                $child  = (int) $row->post_id;
                $has_children[ $parent ] = true;
                // Sub-orders are sorted DESC by ID, so the last visible row is the lowest ID.
                if ( ! isset( $last_child_of[ $parent ] ) || $child < $last_child_of[ $parent ] ) {
                    $last_child_of[ $parent ] = $child;
                }
            }
        }

        $parent_id   = get_post_meta( $post_id, $this->meta_key, true );
        $is_suborder = is_numeric( $parent_id );

        if ( $is_suborder ) {
            $classes[] = 'is-suborder';
            $stripe    = $stripe_map[ (int) $parent_id ] ?? 0;
            if ( ( $last_child_of[ (int) $parent_id ] ?? null ) === $post_id ) {
                $classes[] = 'srb-last-suborder';
            }
        } else {
            $stripe               = $main_order_index % 2;
            $stripe_map[$post_id] = $stripe;
            $main_order_index++;
            if ( isset( $has_children[ $post_id ] ) ) {
                $classes[] = 'srb-has-children';
            }
        }

        $classes   = array_filter( $classes, fn( $c ) => $c !== 'alternate' );
        $classes[] = 'srb-stripe-' . $stripe;

        return array_values( $classes );
    }
}
