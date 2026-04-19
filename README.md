# WooCommerce SubOrdernator

**Plugin by [Studio Rude Box](https://studiorudebox.nl)**  
Easily link WooCommerce orders together by assigning suborders to a main order — for a cleaner, more organized order management system in WP-Admin.

## What is WooCommerce SubOrdernator?

The **SubOrdernator** plugin adds functionality to the WordPress Admin that allows store owners to link one order to another. This creates a clear parent–child relationship between orders: a *main order* and one or more *suborders*.

It's ideal for workflows where:
- Multiple orders belong to the same transaction or customer journey.
- You want to group related orders for clarity or reporting purposes.
- You need to track dependencies between orders (e.g., separate shipments or partial fulfillment).

## Features

- Add a **parent order ID field** to any order in the WooCommerce Admin to link it as a suborder.
- **ID column** in the order list for quick reference.
- **↳ indicator** on suborders shown directly in the order number column.
- **Filter dropdown** above the order list to show all orders, main orders only, or suborders only.
- **+ action button** on main orders to instantly create a linked suborder with the parent pre-filled.
- **Server-side sorting** — suborders always appear directly beneath their parent order.
- **Collapse/expand toggle** on main order rows to show or hide their suborders. State is remembered per browser session.

## How it works

Once the plugin is activated:
1. Go to any WooCommerce order in the admin panel.
2. Enter the ID of the main order in the "Link to a parent order ID" field.
3. Save the order — the relationship is now established.

In the order overview:
- Suborders appear directly beneath their parent order.
- A ↳ symbol identifies suborders in the order number column.
- Use the collapse toggle (▼/▶) on main order rows to show or hide their suborders.
- Use the + action button on any main order to quickly create a linked suborder.
- Use the filter at the top to show only main orders or only suborders.

## Requirements

- WordPress 5.0+
- WooCommerce 4.0+
- PHP 8.1+ (constructor property promotion, named arguments)
- [Composer](https://getcomposer.org/) (to generate the autoloader)
- WooCommerce HPOS (High-Performance Order Storage) must be **disabled** — the plugin uses legacy CPT-based order storage.
- Admin access to WooCommerce orders

## Installation

1. Clone or download the plugin folder to `/wp-content/plugins/woo-subordernator/`.
2. Run `composer install` inside the plugin folder to generate the autoloader.
3. Activate the plugin via the Plugins menu in WP Admin.
4. Go to WooCommerce > Orders to start linking.

## Development

```bash
composer install        # generate autoloader
npm install             # install build tools
npm run makemo          # compile .po → .mo translation files
npm run zip             # package plugin into build/woo-subordernator-x.x.x.zip
npm run build           # composer install (no-dev) + zip
```

## Plugin Info

- **Plugin Name:** WooCommerce SubOrdernator
- **Version:** 2.3.1
- **Author:** Studio Rude Box
- **License:** GPL-2.0-or-later
- **Repository:** [woo-subordernator-wp-plugin](https://github.com/StudioRudeBox/woo-subordernator-wp-plugin)

## Feedback or Feature Requests?

This plugin is developed by [Studio Rude Box](https://studiorudebox.nl).  
Feel free to open an issue on [GitHub](https://github.com/StudioRudeBox/woo-subordernator-wp-plugin) or contact us directly.
