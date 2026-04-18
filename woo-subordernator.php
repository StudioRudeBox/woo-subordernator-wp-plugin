<?php
/**
 * Plugin Name: SubOrdernator for WooCommerce
 * Plugin URI: https://github.com/StudioRudeBox/woo-subordernator-wp-plugin
 * Description: Add the ability to link a WooCommerce order to another order, creating a parent–suborder relationship.
 * Version: 2.3.0
 * Author: Studio Rude Box
 * Author URI: https://studiorudebox.nl
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-subordernator
 * Domain Path: /languages
 */


/**
 * Add a feature to WP-Admin by creating a custom number field in an order
 * This adds the functionality of linking to another main order ID and create internal sub orders
 * 
 * Only use this code in WP-Admin
 */

if(is_admin())
{
    /**
     * Define the plugin post meta parameter name
     */
    define('SRB_POST_META_PARAM_NAME', 'srb_subordernator_order_reference' );
    
    /**
     * Add CSS style to plugin
     * 
     * @return void             return nothing
     */
    
    function srb_subordernator_enqueue_plugin_css():void
    {
        wp_enqueue_style('woo-subordernator', plugin_dir_url(__FILE__) . 'style.css');

        global $typenow;
        if ($typenow === 'shop_order')
        {
            wp_enqueue_script('woo-subordernator', plugin_dir_url(__FILE__) . 'woo-subordernator.js', [], '2.3.0', true);
        }
    }
    add_action('admin_enqueue_scripts', 'srb_subordernator_enqueue_plugin_css');
  
    /**
     * Add the field in a meta box
     * 
     * @param object $order     WooCommerce WC_Order object
     * @return void             return nothing
     */

    function srb_subordernator_add_suborder_field($order): void
    {
        // get order info
        $current_order_id = $order->get_id();
        $selected_order_id = get_post_meta($current_order_id, SRB_POST_META_PARAM_NAME, true);

        // pre-fill when arriving via the "+" create sub-order button
        if (empty($selected_order_id) && isset($_GET['srb_parent_order_id']))
        {
            $selected_order_id = intval($_GET['srb_parent_order_id']);
        }

        // add a section to the right column
        echo '<p class="form-field form-field-wide">';
        echo '<label for="srb_subordernator_order_reference">' . __('Link to a parent order ID (optional):', 'woo-subordernator') . '</label>';

        // display input field
        printf('<input type="number" name="srb_subordernator_order_reference" value="%s" min="0" placeholder="order ID" />',
            $selected_order_id
        );
        
        echo '</p>';
    }
    add_action('woocommerce_admin_order_data_after_order_details', 'srb_subordernator_add_suborder_field');
   
    /**
     * Save the selected value to the current order (optional)
     * 
     * @param int $order_id     WooCommerce order id of WC_Order object
     * @return void             return nothing
     */

    function srb_subordernator_save_suborder_field_value($order_id): void
    {
        $post_data = $_POST[SRB_POST_META_PARAM_NAME];
        
        if (isset($post_data) && is_numeric($post_data))
        {
            update_post_meta($order_id, SRB_POST_META_PARAM_NAME, sanitize_text_field($_POST[SRB_POST_META_PARAM_NAME]));
        }
    }
    add_action('woocommerce_process_shop_order_meta', 'srb_subordernator_save_suborder_field_value');

    /**
     * Add custom columns to the order list for sub orders
     * 
     * @since 1.2 new function execution and added the ID column
     * 
     * @param array $columns    Wordpress current array with columns
     * @return array            return new array including the added column
     */
        
    function srb_subordernator_add_custom_columns_head($columns): array
    {
        $new_columns = [];     
        
        // add the ID column after the checkbox column
        foreach ($columns as $key => $column)
        {
            $new_columns[$key] = $column;
            if ($key === 'cb')
            {
                $new_columns['srb_subordernator_order_id'] = 'ID';
            }
        }
        
        return $new_columns;
    }
    add_filter('manage_edit-shop_order_columns', 'srb_subordernator_add_custom_columns_head', 20);

    /**
     * Add data to the new columns for sub orders
     * 
     * @since 2.0              now with emoticons :)
     * 
     * @param string $column   Wordpress current column name
     * @param int $post_id     Wordpress post ID
     * 
     * @return void            return nothing
     */
    
    function srb_subordernator_add_custom_columns_content($column, $post_id): void
    {
        // get order ID of main order (only available for sub orders)
        $main_order_id = get_post_meta($post_id, SRB_POST_META_PARAM_NAME, true);
        $is_suborder = is_numeric($main_order_id);

        // fill columns for all orders
        if($column === "srb_subordernator_order_id")
        {
            echo $post_id;
            printf('<span class="srb-order-meta" data-order-id="%d" data-parent-id="%s" style="display:none"></span>',
                $post_id,
                esc_attr($main_order_id)
            );
        }

        // add icon and parent info to order number column
        if($column === "order_number" && $is_suborder)
        {
            echo "↳ ";
        }
    }
    add_action('manage_shop_order_posts_custom_column', 'srb_subordernator_add_custom_columns_content', 10, 2);

    /**
     * Add filter on top of order table to order on main or sub orders
     * 
     * @return void            return nothing
     */

    function srb_subordernator_add_suborders_filter(): void
    {
        global $typenow;
        if ($typenow == 'shop_order')
        {
            $selected = isset($_GET['main_sub_order_filter']) ? $_GET['main_sub_order_filter'] : '';
            $options = [
                'main' => __('Main orders', 'woo-subordernator'),
                'sub' => __('Sub orders', 'woo-subordernator')
            ];            

            echo '<select name="main_sub_order_filter">';
            echo '<option value="" ' . selected($selected, '', false) . '>' . __('All orders', 'woo-subordernator') . '</option>';
            
            foreach ($options as $key => $label)
            {
                echo '<option value="' . esc_attr($key) . '" ' . selected($selected, $key, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
        }
    }
    add_action('restrict_manage_posts', 'srb_subordernator_add_suborders_filter');

    /**
     * Change query based on custom filter
     * 
     * @param object $query    Wordpress query object
     * @return void            return nothing
     */
    
    function srb_subordernator_filter_query($query): void
    {
        global $pagenow;

        if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'shop_order' && isset($_GET['main_sub_order_filter']))
        {
            $main_sub_order_filter = sanitize_text_field($_GET['main_sub_order_filter']);
            $meta_query = $query->get('meta_query');

            // check if meta query is empty
            if (empty($meta_query))
            {
                $meta_query = [];
            }

            // check if custom sub / main order filter is used and add items to the meta query
            if ($main_sub_order_filter == 'sub')
            {
                $meta_query[] = [
                    'relation' => 'AND',
                    [
                        'key' => SRB_POST_META_PARAM_NAME,
                        'value' => '',
                        'compare' => '!=',
                    ],
                    [
                        'key' => SRB_POST_META_PARAM_NAME,
                        'compare' => 'EXISTS',
                    ],
                ];
            }
            elseif ($main_sub_order_filter == 'main')
            {
                $meta_query[] = [
                    'relation' => 'OR',
                    [
                        'key' => SRB_POST_META_PARAM_NAME,
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => SRB_POST_META_PARAM_NAME,
                        'value' => '',
                        'compare' => '=',
                    ],
                ];
            }

            // set new meta query
            $query->set('meta_query', $meta_query);
        }
    }
    add_action('pre_get_posts', 'srb_subordernator_filter_query');

    /**
     * Add a "+" action button to each order row to create a linked sub-order
     *
     * @param array    $actions  Existing order actions
     * @param WC_Order $order    Current order object
     * @return array
     */

    function srb_subordernator_add_create_suborder_action($actions, $order): array
    {
        $parent_id = get_post_meta($order->get_id(), SRB_POST_META_PARAM_NAME, true);
        if (is_numeric($parent_id))
        {
            return $actions;
        }

        $url = admin_url('post-new.php?post_type=shop_order&srb_parent_order_id=' . $order->get_id());
        $actions['srb_create_suborder'] = [
            'url'    => esc_url($url),
            'name'   => __('Create sub-order', 'woo-subordernator'),
            'action' => 'srb-create-suborder',
        ];
        return $actions;
    }
    add_filter('woocommerce_admin_order_actions', 'srb_subordernator_add_create_suborder_action', 10, 2);

    /**
     * Sort sub-orders directly under their parent order in the order list (server-side).
     * Avoids JS-based row reordering and the layout shift it causes.
     *
     * @param array    $clauses  SQL clauses
     * @param WP_Query $query    Current query
     * @return array
     */

    function srb_subordernator_order_clauses($clauses, $query): array
    {
        global $wpdb;

        if (!$query->is_main_query()) return $clauses;
        if ($query->get('post_type') !== 'shop_order') return $clauses;
        if (isset($_GET['orderby'])) return $clauses;

        $clauses['join'] .= $wpdb->prepare(
            " LEFT JOIN {$wpdb->postmeta} srb_pm ON srb_pm.post_id = {$wpdb->posts}.ID AND srb_pm.meta_key = %s AND srb_pm.meta_value != ''",
            SRB_POST_META_PARAM_NAME
        );

        $clauses['orderby'] = "CAST(COALESCE(srb_pm.meta_value, {$wpdb->posts}.ID) AS UNSIGNED) DESC,
            CASE WHEN srb_pm.meta_value IS NULL THEN 0 ELSE 1 END ASC,
            {$wpdb->posts}.ID DESC";

        return $clauses;
    }
    add_filter('posts_clauses', 'srb_subordernator_order_clauses', 10, 2);

    /**
     * Add is-suborder CSS class to sub-order rows in the order list.
     *
     * @param array  $classes  Current row classes
     * @param string $class    Additional class string
     * @param int    $post_id  Post ID
     * @return array
     */

    function srb_subordernator_row_class($classes, $class, $post_id): array
    {
        global $typenow;
        if ($typenow !== 'shop_order') return $classes;

        $parent_id = get_post_meta($post_id, SRB_POST_META_PARAM_NAME, true);
        if (is_numeric($parent_id))
        {
            $classes[] = 'is-suborder';
        }
        return $classes;
    }
    add_filter('post_class', 'srb_subordernator_row_class', 10, 3);
}