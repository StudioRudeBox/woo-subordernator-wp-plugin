# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Plugin Overview

**WooCommerce SubOrdenator** is a single-file WordPress plugin that adds parent–child order relationships to WooCommerce. It runs exclusively in `WP-Admin` (wrapped in `is_admin()`).

- **Main file:** `woo-subordernator.php` — all plugin logic lives here
- **Styles:** `style.css` — admin UI styles only
- **i18n:** `languages/` — `.pot`, `.po`, `.mo` files for `woo-subordernator` text domain

## Architecture

The plugin is a flat collection of WordPress hooks — no classes, no autoloading. All functions are prefixed `srb_subordernator_` to avoid collisions.

**Data model:** A single post meta key (`srb_subordernator_order_reference`, defined as `SRB_POST_META_PARAM_NAME`) stores the parent order ID on a sub-order. An order with no meta (or empty meta) is treated as a main order.

**Hook map:**
| Hook | Function | Purpose |
|---|---|---|
| `admin_enqueue_scripts` | `srb_subordernator_enqueue_plugin_css` | Load `style.css` |
| `woocommerce_admin_order_data_after_order_details` | `srb_subordernator_add_suborder_field` | Render parent-order input on order edit screen |
| `woocommerce_process_shop_order_meta` | `srb_subordernator_save_suborder_field_value` | Persist parent order ID |
| `manage_edit-shop_order_columns` (filter) | `srb_subordernator_add_custom_columns_head` | Add ID + "Connected order" columns to order list |
| `manage_shop_order_posts_custom_column` | `srb_subordernator_add_custom_columns_content` | Populate those columns + inject order-type icons |
| `restrict_manage_posts` | `srb_subordernator_add_suborders_filter` | Render main/sub filter dropdown |
| `pre_get_posts` | `srb_subordernator_filter_query` | Apply meta_query based on filter selection |

## Development Notes

- No build step, composer, or npm — plain PHP + CSS.
- Test by installing in a local WordPress + WooCommerce environment (e.g. LocalWP).
- When bumping the version, update it in **three places**: the plugin header comment in `woo-subordernator.php`, and the comment block in `style.css`.
- The plugin targets `shop_order` post type. WooCommerce HPOS (High-Performance Order Storage) is **not** supported — the filter query uses `pre_get_posts` / `meta_query` which only works with the legacy CPT storage.
- Translations use the `woo-subordernator` text domain; run `wp i18n make-pot` from the plugin root to regenerate the `.pot` file.
