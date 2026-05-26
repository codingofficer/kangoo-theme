# Kangoo Pouches Developer Documentation

Last updated: 2026-05-10

This document is the engineering handover for the Kangoo Pouches WordPress theme, WooCommerce customisations, mascot plugin, Android app, and app API plugin. It is written for future Codex sessions, AI coding agents, and human developers who need to understand what exists before changing it.

## Project Map

Root workspace:

`C:\Users\sheri\Documents\GitHub\kp`

Main systems:

- `functions.php`: the central theme logic file. It contains WooCommerce behaviour, rewards, pack pricing, app APK helpers, AJAX endpoints, SEO/schema helpers, account drawer APIs, ACF registrations, and filtering.
- `header.php` and `footer.php`: site shell, age gate, mega menu, header actions, search, account, cart drawer trigger, footer links.
- `front-page.php`: ACF-driven homepage layout. It injects the app download CTA directly after the hero.
- `template-parts/`: reusable front-page, global, and account drawer partials.
- `woocommerce/`: WooCommerce template overrides for product archives, product cards, single product pages, and variation add-to-cart markup.
- `page-templates/`: custom landing/tool pages such as Pouch Finder, Pouch Comparison, Strength Ladder, Flavour Explorer, Build a Box, and Kangoo App.
- `assets/css/`: CSS split by feature/page.
- `assets/js/`: browser-side logic for search, cart drawer, AJAX cart, rewards UI, account drawer, age gate, pouch finder, comparison, and build-a-box.
- `kangoo-helper-mascot/`: standalone WordPress plugin for the animated pouch finder mascot.
- `kangoo-app-api/`: standalone WordPress plugin exposing private REST endpoints for the Android app.
- `android-app/`: native Android APK source.
- `docs/`: developer documentation.

## High-Level Architecture

Kangoo Pouches is a custom WordPress theme built around WooCommerce. The theme handles most storefront behaviour directly rather than depending on many small plugins. WooCommerce remains the source of truth for products, prices, stock, orders, checkout, tax, shipping, and customer accounts.

The website adds:

- A modern dark storefront theme.
- A custom product card and product page experience.
- Pack pricing for 1-pack, 5-pack, 10-pack, or similar tiered product quantities.
- Stock-aware low stock messaging and stock-limited add-to-cart logic.
- Kangoo Rewards points, redemption, and customer account rewards pages.
- URL coupon application.
- Free shipping nudges for the 14.95 GBP threshold.
- AJAX cart drawer and mini-cart quantity controls.
- Account drawer login/register/logout.
- Pouch Finder, comparison, strength ladder, flavour explorer, and build-a-box pages.
- Age gate and checkout age verification.
- Blog custom post type and SEO schema.
- Android app landing page and APK download helpers.
- Android app REST API plugin for native app features.

## Business Rules

### Age Restriction

The store is 18+ only. The site uses:

- An age gate in `header.php`.
- Age gate logic and settings in `functions.php`.
- Checkout age verification fields in `functions.php`.
- JavaScript enhancement in `assets/js/main.js`.

Customers must confirm they are 18 or over before browsing. Checkout stores age verification metadata on the order.

### Free Shipping

Free UK delivery unlocks at 14.95 GBP.

The canonical threshold is:

```php
function kangoo_free_shipping_threshold() {
    return 15;
}
```

This threshold is used by:

- Cart drawer nudge.
- Cart page nudge.
- Checkout nudge.
- JavaScript updates in `assets/js/main.js`.
- Localized `kangooRewards.free_shipping_threshold`.

Important: rewards discounts should not affect the free shipping threshold. WooCommerce free shipping should be configured as minimum order amount 14.95 GBP, and the "apply minimum order rule before coupon discount" setting should be selected if the aim is to let customers qualify before coupon/rewards deductions.

### Coupons

URL coupons are accepted through:

`https://kangoopouches.co.uk/?coupon=CODE`

The code path is:

- `kangoo_get_url_coupon_code()`
- `kangoo_apply_url_coupon()`
- `kangoo_apply_pending_url_coupon()`
- `kangoo_ajax_apply_url_coupon()`

Rewards coupon code is reserved:

`kangoo-rewards`

Do not create a real WooCommerce coupon with that code. It is a virtual coupon-like system handled in code.

### Kangoo Rewards

Rewards rules:

- Earn rate: 1 point per 1 GBP cart/order value.
- Redemption value: 100 points = 1 GBP.
- Minimum redemption: 100 points.
- Website reward max is based on cart subtotal and balance.
- App reward max is currently capped to 500 points per checkout by the app API.
- Rewards cannot combine with normal coupon codes in the Android app API checkout.
- Rewards should not affect free shipping.
- Signup bonus in the app API plugin: 50 points.

Storage:

- Balance: user meta `kangoo_rewards_points_balance`
- History: user meta `kangoo_rewards_points_history`
- Active redemption: user meta `kangoo_rewards_redeem_points`

Order events:

- Points are awarded when an order reaches completed status.
- Points are redeemed during checkout order processing.
- Points are returned when orders are cancelled, refunded, or failed.

### Pack Pricing

Pack pricing is managed with ACF fields:

- `pack_pricing_enabled`
- `pack_pricing_tiers`
- Each tier has `quantity`, `pack_price`, `badge`, and `default_selected`.

The code calculates:

- Pack price.
- Unit price.
- Default selected pack.
- Disabled pack options when stock is lower than pack quantity.

If stock is 8 and a 10-pack exists, the 10-pack must not be selectable.

Cart prices are applied by changing the WooCommerce product unit price based on the selected cart quantity. For example, if a 10-pack total price is 34.90, the cart item unit price becomes 3.49 when quantity is 10.

### Stock And Low Stock

Low stock messaging appears when a product is managing stock and has 1 to 9 units remaining.

The key helpers are:

- `kangoo_get_product_stock_limit()`
- `kangoo_get_low_stock_message()`

Displayed text:

`Low stock: only X left`

Stock limits are enforced in:

- Product cards.
- Pack modal.
- Single product add-to-cart.
- AJAX add-to-cart endpoint.
- Mini-cart quantity controls.
- Android native app cart.

### Product Badges

Product badge logic is automatic/custom:

- Out of stock overrides other badges.
- ACF `badge` choices can mark products.
- "New" can be automatic based on publish date.
- "Best seller" can be automatic based on sales.

Relevant helpers:

- `kangoo_normalize_product_badge()`
- `kangoo_product_is_new()`
- `kangoo_product_is_auto_best_seller()`
- `kangoo_get_product_badge()`
- `kangoo_get_product_badge_key()`

### App Download And APK Updates

APK files should be uploaded to:

`/app/android/`

Use this naming format:

`kangoo-pouches-v0.6.0.apk`

The theme and app API can auto-detect the newest APK filename by version number. The homepage app CTA and Kangoo App page use:

- `kangoo_get_app_apk_release()`
- `kangoo_get_app_version_from_apk_filename()`

The app API plugin has a similar detector:

- `kangoo_app_api_latest_apk_release()`
- `kangoo_app_api_version_from_apk_filename()`

Leave the APK URL field blank in WooCommerce > Kangoo App if you want auto-detection.

## Theme File Reference

### Root PHP Files

`style.css`

Theme metadata only. WordPress uses this to identify the theme.

`functions.php`

Main theme behaviour file. This is where most feature logic lives. See the Function Index below.

`header.php`

Renders:

- HTML document start.
- Age gate markup.
- Mega menu configuration and desktop/mobile menu panels.
- Site logo.
- Search trigger.
- Account trigger.
- Mobile menu trigger.
- Cart trigger.

Important dependencies:

- `kangoo_get_age_gate_settings()`
- `kangoo_is_age_gate_enabled()`
- `kangoo_get_mega_menu_settings()`
- `kangoo_acf_link_url()`
- `kangoo_acf_link_target()`

`footer.php`

Renders footer columns: Browse, Support, Legal, and brand copy. Calls `wp_footer()`.

`front-page.php`

Uses the ACF flexible content field `homepage_sections`. It renders section template parts based on layout names. The app download CTA and Pouch Finder CTA are inserted immediately after the hero layout.

`page.php`, `single.php`, `archive.php`, `404.php`, `index.php`

Standard WordPress templates with Kangoo styling.

`archive-kangoo_blog.php`, `single-kangoo_blog.php`, `taxonomy-blog_topic.php`

Custom blog templates tied to the `kangoo_blog` CPT and `blog_topic` taxonomy.

`taxonomy-pa_flavour.php`, `taxonomy-pa_strength.php`

Attribute landing page wrappers for flavour and strength term archives.

## Template Parts

`template-parts/home/hero.php`

Homepage hero. Content comes from ACF fields in the flexible content row.

`template-parts/home/app-download-cta.php`

Homepage app CTA. Uses `kangoo_get_app_apk_release()` for dynamic APK URL and links to the app detail page.

`template-parts/home/pouch-finder-cta.php`

Homepage tool CTA with buttons for Pouch Finder, Compare Pouches, Pick n Mix Bundle, Strength Ladder, and Flavour Explorer.

`template-parts/home/quick-links.php`

Homepage quick link row.

`template-parts/home/product-section.php`

Renders configured product sections from ACF.

`template-parts/home/auto-products.php`

Automatically selects products for homepage sections. Supports sources such as best sellers, new arrivals, sale products, and summer collection.

`template-parts/home/brand-grid.php`

Brand-focused homepage grid.

`template-parts/home/categories.php`

Category links.

`template-parts/home/why.php`

Trust/why-shop section.

`template-parts/home/faq.php`

Homepage FAQ.

`template-parts/home/seo.php`

SEO content area.

`template-parts/global/account-drawer.php`

Markup for the account drawer. JavaScript in `assets/js/account-drawer.js` handles login, register, logout, status refresh, and messages.

`template-parts/global/template-info-page.php`

Shared info-page layout.

## Page Templates

`page-templates/template-kangoo-app.php`

Android app landing page. Uses dynamic APK detection for download links. Explains APK installation, install warnings, updates, app features, and FAQs.

`page-templates/template-pouch-finder.php`

Multi-step guided finder. It emits product data and question markup consumed by `assets/js/pouch-finder.js`. It is a questionnaire, not a generic filter.

`page-templates/template-pouch-comparison.php`

Allows customers to compare multiple pouches. JavaScript controls product selection and comparison rows.

`page-templates/template-build-a-box.php`

Pick n Mix / bundle builder page. JavaScript controls the interactive build flow.

`page-templates/template-strength-ladder.php`

Education page explaining strength levels.

`page-templates/template-flavour-explorer.php`

Education/discovery page for flavour groups.

`page-templates/template-info-page.php`

Generic modern content page template.

## WooCommerce Template Overrides

`woocommerce/archive-product.php`

Custom shop/category archive structure. Works with filters, search, product grids, and the theme product cards.

`woocommerce/content-product.php`

Custom product card. Responsibilities:

- Product image/title/price.
- Strength badge.
- Out of stock badge.
- Automatic/custom badges.
- Low stock message.
- Quantity controls.
- Pack modal trigger.
- AJAX add-to-cart buttons.
- Variable product quick-add modal.
- Pack tiers and stock-aware pack disabling.

`woocommerce/single-product.php`

Custom single product page. Responsibilities:

- Gallery and thumbnails.
- Product title, strength badge, stock badge.
- Price and saving display.
- Low stock message.
- Strength pill UI for variable products.
- WooCommerce add-to-cart form.
- Trust bullets.
- Product accordions and product FAQ schema.

`woocommerce/single-product/add-to-cart/variation-add-to-cart-button.php`

Variation add-to-cart button override, used so the custom single-product UI can keep consistent markup/states.

## CSS Files

`assets/css/base.css`

Global reset, typography, body, links, forms, base layout rules.

`assets/css/components.css`

Shared components such as buttons, cards, section headers, badges, and reusable layouts.

`assets/css/header-footer.css`

Header, mega menu, mobile drawer, search UI, cart trigger, account trigger, age gate, and footer styling.

`assets/css/home.css`

Homepage sections, hero, app CTA, product rows, brand grid, tool CTA.

`assets/css/shop.css`

Shop archive, filters, product grid, product card layout.

`assets/css/product.css`

Single product page, gallery, strength UI, pack pricing, product accordions.

`assets/css/woocommerce.css`

Cart, checkout, mini cart, rewards cart box, free shipping nudge, WooCommerce overrides, notices, account-adjacent WooCommerce styling.

`assets/css/account-drawer.css`

Account drawer UI.

`assets/css/account-page.css`

My Account page layout and mobile account menu.

`assets/css/kangoo-app.css`

Android app landing page.

`assets/css/pouch-finder.css`, `pouch-comparison.css`, `build-a-box.css`, `strength-ladder.css`, `flavour-explorer.css`

Feature-specific page styling.

`assets/css/blog.css`

Custom blog archive/single styling.

`assets/css/info-page.css`

Generic info-page styling.

## JavaScript Files

`assets/js/main.js`

General storefront behaviour:

- Header/mobile menu interactions.
- Search UI.
- Mega menu interactions.
- Age gate storage and focus management.
- Checkout age field enhancement.
- Checkout/cart free shipping nudge updates.
- Terms text linking.

`assets/js/ajax-cart.js`

Large cart interaction file. Responsibilities:

- Product card quantity controls.
- Pack modal controls.
- Variable quick-add controls.
- AJAX add-to-cart.
- Single product add-to-cart states (`ADDING...`, `ADDED [check]`).
- Limited availability messages.
- Mini-cart drawer refresh.
- Mini-cart quantity increase/decrease.
- Cart item removal.
- Clear cart.
- Cart fragment sync on cart page and drawer.
- Stock max visual clamping.

`assets/js/account-drawer.js`

Handles account drawer open/close, login/register/logout AJAX calls, messages, and post-login redirects.

`assets/js/account-page.js`

Enhances the My Account page on mobile with a collapsible account menu and password visibility controls.

`assets/js/pouch-finder.js`

Runs the questionnaire on the Pouch Finder page. Scores products by experience, flavour, strength, brand/product data, and use case.

`assets/js/pouch-comparison.js`

Lets users select up to four products and compare brand, strength, flavour, price, and best-use notes.

`assets/js/build-a-box.js`

Interactive bundle builder for pick n mix flows.

## Kangoo Helper Mascot Plugin

Plugin folder:

`kangoo-helper-mascot/`

Current main file:

`kangoo-helper-mascot/kangoo-helper-mascot.php`

The plugin is intentionally separate from the base theme so the mascot can be maintained, disabled, or replaced without touching core storefront files.

Behaviour:

- Enqueues its own CSS and JS.
- Renders an animated helper linked to the Pouch Finder.
- Does not render on the Pouch Finder page itself.
- Uses localStorage key `kangoo_helper_mascot_dismissed` so dismissed visitors do not see it repeatedly.
- Uses sprite frames from `assets/kangoo-mascot-sprite.png`.
- Close button is positioned `top: -30px`.
- Mascot touches the bottom safe area of the screen.

Mascot functions:

- `kangoo_helper_mascot_asset_version($relative_path)`: cache-busts plugin CSS/JS with filemtime.
- `kangoo_helper_mascot_enqueue_assets()`: enqueues CSS/JS unless admin or Pouch Finder page.
- `kangoo_helper_mascot_is_pouch_finder()`: detects Pouch Finder page/template.
- `kangoo_helper_mascot_render()`: outputs mascot link, bubble, character, and dismiss button.

Mascot JavaScript:

- `setMascotFrame(character, frame)`: moves sprite background to the correct frame.
- `playMascotFrames(mascot)`: plays intro frames then slow idle frames.
- `initMascot()`: checks localStorage, starts delayed entrance, binds dismiss.

## Kangoo App API Plugin

Plugin folder:

`kangoo-app-api/`

Main file:

`kangoo-app-api/kangoo-app-api.php`

Current packaged zip:

`kangoo-app-api-v0.3.1-forward-slashes.zip`

Important: when packaging for WordPress, zip paths must use forward slashes:

`kangoo-app-api/kangoo-app-api.php`

Backslash paths can cause "Plugin file does not exist" after upload.

### API Purpose

The native Android app does not contain product/order/customer data. It calls:

- WooCommerce Store API for public product data.
- Kangoo App API plugin for login, register, rewards, orders, addresses, checkout transfer, app update config, event tracking, and push token storage.

Checkout still happens on the secure WooCommerce checkout page.

### REST Namespace

`kangoo-app/v1`

### REST Endpoints

- `GET /config`: app update config and APK URL.
- `POST /event`: anonymous/authenticated app event tracking.
- `POST /login`: login with email or username and password.
- `POST /register`: create WooCommerce customer and app token.
- `POST /logout`: remove current app token.
- `GET /me`: current user, rewards summary.
- `GET /rewards`: rewards balance/history/rules.
- `GET /orders`: recent orders and reorder payloads.
- `POST /reorder`: builds checkout transfer from a previous order.
- `GET /addresses`: billing/shipping address payload.
- `POST /addresses`: update billing/shipping address meta.
- `POST /checkout`: accepts native cart items and returns a short-lived Woo checkout transfer URL.
- `GET /alerts`: low stock/back in stock alert product IDs.
- `POST /alerts`: update alert product IDs.
- `POST /push-token`: save future push notification token.

Authenticated endpoints use:

`Authorization: Bearer <token>`

### Checkout Transfer Flow

1. App builds native cart.
2. App posts items, optional coupon, optional rewards points, install ID, app version to `/checkout`.
3. API validates product IDs, variation IDs, stock, quantity limits, rewards/coupon conflicts.
4. API creates a transient with a random transfer key.
5. API returns `checkout_url`.
6. App opens `checkout_url` in browser/webview.
7. Website sees `?kangoo_app_cart=KEY`, empties existing cart, rebuilds Woo cart, applies coupon or rewards, logs user in if token user exists, then redirects to WooCommerce checkout.
8. Order metadata is saved with `_kangoo_order_source = android_app`.

### App Tracking

Plugin records totals/daily counts for:

- `install`
- `open`
- `checkout_start`
- `checkout_transfer`
- `order_created`

Stats are stored in option `kangoo_app_api_stats` and shown under WooCommerce > Kangoo App.

### App Update Flow

The app calls `/config` with:

- `version_code`
- `version`
- `install_id`

The API returns:

- latest version.
- latest version code.
- minimum version code.
- APK URL.
- update available/required flags.
- update message.

The API can auto-detect newest uploaded APK in `/app/android/` using `kangoo-pouches-v*.apk`.

## Android App

Project folder:

`android-app/`

Main app code:

`android-app/app/src/main/java/co/uk/kangoopouches/app/MainActivity.java`

Current package:

`co.uk.kangoopouches.app`

Current version:

- `versionCode 19`
- `versionName "0.6.0"`

Build command:

```powershell
$env:JAVA_HOME="C:\Program Files\Android\Android Studio\jbr"
$env:ANDROID_HOME="C:\Users\sheri\AppData\Local\Android\Sdk"
$env:ANDROID_SDK_ROOT="C:\Users\sheri\AppData\Local\Android\Sdk"
.\gradlew.bat assembleDebug --no-daemon
```

APK output:

`android-app/app/build/outputs/apk/debug/app-debug.apk`

Named release copy:

`android-app/app/build/outputs/apk/debug/kangoo-pouches-v0.6.0.apk`

### App UX

The app is native Android Java. It uses programmatic layouts rather than XML screens. It includes:

- Splash screen.
- Search-first home screen.
- Product browsing.
- Featured products and summer collection.
- Product search with live results.
- Filter drawer.
- Native cart.
- Fixed cart summary/free delivery area.
- Rewards page.
- Account login/register.
- Orders and reorder.
- App update notice.
- External secure checkout handoff.

### App Data

The app fetches live product data from:

`https://kangoopouches.co.uk/wp-json/wc/store/v1/products?per_page=60`

The app API base is:

`https://kangoopouches.co.uk/wp-json/kangoo-app/v1`

No WooCommerce consumer secret should be hardcoded into the app. The REST bridge plugin should handle private logic.

## Build And Release Checklist

### Theme

1. Edit files locally.
2. Test cart, checkout, account, product cards, product pages, and app landing page.
3. Upload changed theme files to the WordPress theme.
4. Clear any site/page cache.
5. Test dynamic cart and checkout with a real low-stock product.

### App API Plugin

1. Update plugin source in `kangoo-app-api/`.
2. Package zip with forward slashes.
3. Upload zip in WordPress Plugins.
4. Activate/update plugin.
5. Visit WooCommerce > Kangoo App.
6. Leave APK URL blank for auto-detection, or override manually if needed.

### Android APK

1. Update `versionCode` and `versionName` in `android-app/app/build.gradle`.
2. Build with Gradle.
3. Rename/copy APK to `kangoo-pouches-vX.Y.Z.apk`.
4. Upload to `/app/android/`.
5. Confirm app landing page button points to the newest APK.
6. Open existing app and confirm update notice appears.

## Function Index: Theme `functions.php`

### Bootstrap, APK, Coupons, Assets

- `kangoo_mark_dynamic_commerce_pages_uncacheable()`: disables page/object cache constants for cart, checkout, and account URLs.
- `kangoo_get_app_apk_release()`: detects newest APK release by asking plugin helper or scanning `/app/android/`.
- `kangoo_get_app_version_from_apk_filename($file)`: extracts semantic version from `kangoo-pouches-v*.apk`.
- `kangoo_get_url_coupon_code()`: reads and normalizes `?coupon=CODE`.
- `kangoo_apply_url_coupon()`: applies URL coupon during `wp_loaded` when cart/session is available.
- `kangoo_apply_pending_url_coupon()`: applies a coupon stored in Woo session once cart loads.
- `kangoo_ajax_apply_url_coupon()`: AJAX endpoint for applying URL coupons.
- `kangoo_theme_setup()`: registers theme supports and menus.
- `kangoo_enqueue_assets()`: enqueues all CSS/JS and localizes frontend settings/nonces.

### Checkout Age Verification And Email

- `kangoo_checkout_county_field_required($fields)`: makes county/state required and labels it County.
- `kangoo_checkout_age_verification_html()`: renders classic checkout DOB/age confirmation UI.
- `kangoo_calculate_age_from_date($date)`: returns age from date string.
- `kangoo_normalize_dob_parts($day, $month, $year)`: converts day/month/year fields to `YYYY-MM-DD`.
- `kangoo_checkout_number_options($start, $end, $pad, $descending)`: utility for DOB dropdown options.
- `kangoo_validate_checkout_age_verification()`: validates classic checkout age fields.
- `kangoo_get_posted_checkout_age_verification()`: reads posted DOB and confirmation values.
- `kangoo_save_checkout_age_verification($order, $data)`: saves age metadata to order.
- `kangoo_admin_order_age_verification_meta($order)`: displays age metadata in admin order screen.
- `kangoo_register_checkout_block_age_verification_fields()`: registers checkout block fields.
- `kangoo_validate_checkout_block_age_fields()`: validates checkout block age fields.
- `kangoo_email_logo_url()`: returns email logo URL.
- `kangoo_email_header_image()`: overrides Woo email header image.
- `kangoo_email_from_name()`: overrides Woo email sender name.
- `kangoo_email_colours()`: central Woo email color palette.
- `kangoo_email_footer_text()`: custom email footer.
- `kangoo_email_order_items_args()`: custom Woo email item display args.
- `kangoo_woocommerce_email_styles($css, $email)`: injects modern Woo email CSS.
- `kangoo_no_cache_cart_pages()`: sends no-cache headers for cart/checkout/account.

### Blog, ACF, Schema, Search

- `kangoo_register_blog_post_type()`: registers `kangoo_blog` CPT.
- `kangoo_register_blog_taxonomy()`: registers blog topic taxonomy.
- `kangoo_flush_rewrite_rules_on_theme_switch()`: flushes rewrites after theme switch.
- `kangoo_maybe_flush_blog_rewrite_rules()`: one-time rewrite flush for blog changes.
- `kangoo_register_blog_acf_fields()`: registers blog ACF fields.
- `kangoo_register_pack_pricing_acf_fields()`: registers pack pricing ACF fields.
- `kangoo_normalize_product_badge($badge)`: normalizes badge choice.
- `kangoo_add_automatic_product_badge_choice($field)`: adds automatic badge choice to ACF.
- `kangoo_product_is_new($product_id)`: true when product is new enough.
- `kangoo_product_is_auto_best_seller($product_id)`: true when product qualifies as best seller.
- `kangoo_get_product_badge($product_id)`: returns badge metadata for card/template.
- `kangoo_get_product_price_html($product)`: custom sale/regular price HTML.
- `kangoo_get_product_badge_key($product_id)`: returns badge key only.
- `kangoo_blog_get_field($field, $post_id)`: safe ACF/get_post_meta wrapper.
- `kangoo_blog_estimated_read_time($post_id)`: calculates read time.
- `kangoo_blog_meta_description($post_id)`: returns SEO description.
- `kangoo_blog_fallback_image_url()`: fallback blog image.
- `kangoo_blog_featured_image_url($post_id)`: featured image URL.
- `kangoo_blog_featured_image_html($post_id, $size)`: featured image markup.
- `kangoo_blog_document_title_parts($title)`: adjusts document title on blog pages.
- `kangoo_blog_head_meta()`: prints blog meta tags.
- `kangoo_blog_schema()`: prints blog JSON-LD.
- `kangoo_print_json_ld($data)`: shared JSON-LD printer.
- `kangoo_schema_url($path)`: creates canonical schema URL.
- `kangoo_site_logo_url()`: logo URL for schema.
- `kangoo_site_identity_schema()`: prints Organization/WebSite schema.
- `kangoo_schema_breadcrumb_item($position, $name, $url)`: breadcrumb schema item.
- `kangoo_breadcrumb_schema()`: prints breadcrumb JSON-LD.
- `kangoo_product_schema()`: prints Product schema.
- `kangoo_product_archive_itemlist_schema()`: prints ItemList schema on archives.
- `kangoo_ajax_search()`: AJAX search returning products and guide links.

### Pack Pricing And Stock

- `kangoo_get_pack_pricing_product_id($product_id)`: resolves variations to parent product for tiers.
- `kangoo_get_pack_pricing_tiers($product_id)`: reads ACF pack tiers and calculates unit prices.
- `kangoo_get_pack_pricing_tier_for_quantity($product_id, $quantity)`: selects best tier for quantity.
- `kangoo_get_product_stock_limit($product)`: returns managed stock quantity or null.
- `kangoo_get_low_stock_message($product)`: returns low-stock text for 1-9 stock.
- `kangoo_render_pack_pricing_selector()`: renders single product pack selector.
- `kangoo_apply_pack_pricing_to_cart($cart)`: applies tier unit price in cart calculations.

### Kangoo Rewards

- `kangoo_rewards_points_per_pound()`: earn rate, currently 1.
- `kangoo_rewards_points_per_pound_value()`: redemption conversion, currently 100 points per GBP.
- `kangoo_rewards_min_redemption_points()`: minimum redemption, currently 100.
- `kangoo_rewards_max_cart_discount_ratio()`: max cart ratio, currently 1.
- `kangoo_rewards_coupon_code()`: reserved virtual code, `kangoo-rewards`.
- `kangoo_rewards_get_balance($user_id)`: reads points balance.
- `kangoo_rewards_get_history($user_id)`: reads points history.
- `kangoo_rewards_add_history_entry($user_id, $points, $label, $order_id)`: prepends history entry.
- `kangoo_rewards_adjust_points($user_id, $points, $label, $order_id)`: changes balance and logs history.
- `kangoo_rewards_points_to_money($points)`: converts points to GBP.
- `kangoo_rewards_money_to_points($amount)`: converts GBP to points.
- `kangoo_rewards_get_cart_subtotal()`: returns current cart subtotal.
- `kangoo_rewards_get_max_redeemable_points($user_id)`: caps redemption by balance/cart.
- `kangoo_rewards_get_session_points()`: reads active redemption points.
- `kangoo_rewards_set_session_points($points)`: stores/removes active redemption.
- `kangoo_rewards_current_url()`: current URL helper.
- `kangoo_rewards_redirect_url()`: safe redirect URL after rewards form.
- `kangoo_rewards_handle_cart_actions()`: handles rewards form apply/remove.
- `kangoo_rewards_virtual_coupon_data($coupon_data, $coupon_code)`: exposes virtual Woo coupon.
- `kangoo_ajax_rewards_set_coupon_state()`: AJAX rewards apply/remove.
- `kangoo_rewards_coupon_label($label, $coupon)`: displays "Kangoo Rewards".
- `kangoo_rewards_apply_requested_discount($requested, $requested_discount)`: validates and applies requested redemption.
- `kangoo_rewards_validate_session_discount()`: keeps active redemption valid.
- `kangoo_rewards_apply_cart_discount($cart)`: adds negative cart fee.
- `kangoo_rewards_get_cart_box_html()`: renders rewards box on cart/checkout.
- `kangoo_rewards_render_cart_box()`: echoes rewards box.
- `kangoo_rewards_prepend_block_page_box($content)`: inserts rewards box before block cart/checkout content.
- `kangoo_rewards_generate_customer_username($email)`: creates unique username from email.
- `kangoo_rewards_attach_order_customer($order_id)`: attaches/creates customer for guest order.
- `kangoo_rewards_get_order_user_id($order)`: resolves reward user ID for order.
- `kangoo_rewards_award_order_points($order_id)`: awards order points on completion.
- `kangoo_rewards_redeem_order_points($order_id)`: deducts redeemed points.
- `kangoo_rewards_return_order_points($order_id)`: returns points on failed/cancelled/refunded orders.
- `kangoo_rewards_user_register_bonus($user_id)`: awards website registration bonus if enabled.
- `kangoo_rewards_add_account_endpoint()`: registers My Account rewards endpoint.
- `kangoo_rewards_account_menu_items($items)`: adds Rewards tab.
- `kangoo_rewards_account_endpoint_content()`: renders account rewards page.

### Cart Drawer, Free Shipping, AJAX Cart, Account Drawer

- `kangoo_body_classes($classes)`: adds body classes.
- `kangoo_get_cart_badge_html()`: returns cart count badge.
- `kangoo_free_shipping_threshold()`: returns 14.95.
- `kangoo_get_free_shipping_nudge_html($context)`: renders free delivery progress UI.
- `kangoo_get_mini_cart_container_html()`: wrapper around mini cart.
- `kangoo_get_mini_cart_html()`: returns mini cart HTML.
- `kangoo_get_refreshed_cart_fragments_payload()`: returns Woo fragments and cart hash.
- `kangoo_render_mini_cart_free_shipping_nudge()`: prints drawer nudge.
- `kangoo_mini_cart_quantity_stock_data($quantity_html, $cart_item, $cart_item_key)`: adds data attributes for mini-cart stock max.
- `kangoo_ajax_update_mini_cart_quantity()`: AJAX quantity update.
- `kangoo_ajax_remove_mini_cart_item()`: AJAX remove item.
- `kangoo_ajax_clear_cart()`: AJAX clear cart.
- `kangoo_ajax_get_cart_fragments()`: AJAX fragment refresh.
- `kangoo_ajax_add_to_cart()`: AJAX add-to-cart with stock limit checks.
- `kangoo_account_get_redirect_url()`: account page URL.
- `kangoo_account_get_user_payload($user)`: user payload for account drawer.
- `kangoo_ajax_account_status()`: AJAX status.
- `kangoo_ajax_account_login()`: AJAX login.
- `kangoo_ajax_account_register()`: AJAX registration.
- `kangoo_ajax_account_logout()`: AJAX logout.

### ACF Helpers, Mega Menu, Age Gate, Filters

- `kangoo_acf_option_value($keys, $default)`: reads ACF option values with fallbacks.
- `kangoo_acf_link_url($link)`: extracts URL from ACF link.
- `kangoo_acf_link_target($link)`: extracts target from ACF link.
- `kangoo_normalize_mega_menu_source($source)`: normalizes panel source keys.
- `kangoo_get_mega_menu_settings()`: builds full mega menu settings array.
- `kangoo_get_age_gate_settings()`: returns age gate config.
- `kangoo_is_age_gate_enabled()`: boolean age gate state.
- `kangoo_register_age_gate_acf_fields()`: registers ACF options for age gate.
- `kangoo_sanitize_age_gate_enabled($value)`: sanitizes boolean option.
- `kangoo_register_theme_options()`: registers fallback theme options.
- `kangoo_theme_options_section_callback()`: options page copy.
- `kangoo_age_gate_enabled_field()`: checkbox field markup.
- `kangoo_render_theme_options_page()`: theme options page markup.
- `kangoo_theme_options_menu()`: currently returns early; admin menu disabled.
- `kangoo_acf_add_types_panel_choice($field)`: adds Types mega menu choice.
- `kangoo_acf_add_home_product_sources($field)`: adds summer collection source.
- `kangoo_product_filter_taxonomy($filter)`: maps filter type to product attribute taxonomy.
- `kangoo_product_filter_fallback_options($filter)`: fallback filter options.
- `kangoo_product_filter_options($filter)`: returns live terms or fallback options.
- `kangoo_add_product_filter_query($query, $filter, $value)`: adds tax query for filters.
- `kangoo_add_attribute_landing_rewrites()`: custom flavour/strength rewrites.
- `kangoo_attribute_landing_term_link($termlink, $term, $taxonomy)`: custom term links.
- `kangoo_account_page_assets()`: enqueues account page CSS/JS.
- `kangoo_account_page_body_class($classes)`: account page body class.
- `kangoo_account_menu_items($items)`: renames Woo account menu items.
- `kangoo_filter_product_category_query($query)`: applies archive filters and sorting.

## Function Index: App API Plugin

- `kangoo_app_api_latest_apk_release()`: scans `/app/android/` for newest APK.
- `kangoo_app_api_version_from_apk_filename($file)`: extracts version from APK filename.
- `kangoo_app_api_settings()`: loads release/update settings with detected APK fallback.
- `kangoo_app_api_install_id($value)`: sanitizes install ID.
- `kangoo_app_api_config($request)`: returns update config to app.
- `kangoo_app_api_record_event($event, $install_id, $app_version, $user_id)`: records app stats.
- `kangoo_app_api_event($request)`: REST event endpoint.
- `kangoo_app_api_after_theme_loaded()`: swaps signup bonus handler.
- `kangoo_app_api_award_signup_bonus($user_id)`: awards 50 app signup points.
- `kangoo_app_api_token_hash($token)`: HMAC hash for stored tokens.
- `kangoo_app_api_base64url_encode($value)`: URL-safe base64 encode.
- `kangoo_app_api_base64url_decode($value)`: URL-safe base64 decode.
- `kangoo_app_api_create_token($user_id)`: creates app bearer token.
- `kangoo_app_api_get_bearer_token($request)`: reads Authorization header.
- `kangoo_app_api_authenticate($request)`: validates token and returns user ID.
- `kangoo_app_api_current_user_permission($request)`: REST permission wrapper.
- `kangoo_app_api_user_payload($user_id)`: normalized user data.
- `kangoo_app_api_rewards_payload($user_id)`: rewards data/rules for app.
- `kangoo_app_api_logout($request)`: removes current token.
- `kangoo_app_api_login($request)`: email/username login.
- `kangoo_app_api_register($request)`: creates customer account.
- `kangoo_app_api_me($request)`: returns user and rewards.
- `kangoo_app_api_rewards($request)`: returns rewards only.
- `kangoo_app_api_orders($request)`: returns recent orders.
- `kangoo_app_api_order_reorder_items($order)`: converts order items to checkout payload.
- `kangoo_app_api_reorder($request)`: creates checkout transfer from old order.
- `kangoo_app_api_addresses($request)`: returns billing/shipping.
- `kangoo_app_api_get_address($user_id, $type)`: reads address meta.
- `kangoo_app_api_update_addresses($request)`: updates address meta.
- `kangoo_app_api_checkout($request)`: validates app cart and returns transfer URL.
- `kangoo_app_api_handle_checkout_transfer()`: consumes transfer key and builds Woo cart.
- `kangoo_app_api_mark_order_source($order)`: marks orders as app orders.
- `kangoo_app_api_alerts($request)`: returns alert product IDs.
- `kangoo_app_api_clean_id_list($ids)`: sanitizes ID arrays.
- `kangoo_app_api_update_alerts($request)`: saves alert IDs.
- `kangoo_app_api_save_push_token($request)`: stores push token.
- `kangoo_app_api_admin_menu()`: adds WooCommerce > Kangoo App submenu.
- `kangoo_app_api_admin_page()`: renders settings/stats admin page.
- `kangoo_app_api_register_routes()`: registers REST routes.

## Method Index: Android `MainActivity.java`

The Android app is a single-activity native Java app using programmatic UI.

Main lifecycle/navigation:

- `onCreate()`: bootstraps splash.
- `showSplash()`: splash logo/title.
- `showAppShell()`: app shell, nav, tracking, update check, product fetch.
- `renderNav()`: bottom icon nav.
- `addNavButton()`, `navIcon()`, `cartCount()`: nav helpers.

Screens:

- `showHome()`: search, featured products, summer collection, tools.
- `showShop()`: product list with filters.
- `showFilterDrawer()`: filter overlay/drawer.
- `showFinder()`: native guided finder.
- `showCart()`: native cart.
- `showRewards()`: rewards balance/history.
- `showAccount()`: sign-in/account area.

Search/filter:

- `addSearchBar()`: Amazon-style search bar.
- `populateSearchResults()`: live search results.
- `searchResultRow()`: result row.
- `filterRow()`, `filterChip()`, `isFilterActive()`, `filterSummary()`.
- `productOptions()`, `productOptionsWithAny()`.
- `filteredProducts()`, `matchesPanelFilters()`.
- `startVoiceSearch()`, `onActivityResult()`.

Cart/checkout:

- `addToCart()`: native add.
- `changeCartQuantity()`: quantity changes.
- `removeCartLine()`: remove one line.
- `clearCart()`: empty native cart.
- `cartSubtotal()`: subtotal calculation.
- `cartSummaryCard()`: fixed summary.
- `freeDeliveryCard()`: 14.95 GBP free shipping progress.
- `startCheckout()`: posts to app API and opens Woo checkout transfer.

Account/API:

- `installId()`: persistent anonymous install ID.
- `trackInstallAndOpen()`: tracks first install and open.
- `trackEvent()`: posts analytics events.
- `checkForUpdates()`: calls `/config`.
- `auth()`: login/register.
- `fetchAccount()`: pulls user/rewards/orders.
- `signOut()`: logout.
- `fetchProducts()`: loads Woo Store API products.

Rendering helpers:

- `addHorizontalProducts()`, `addHorizontalProductList()`.
- `productsMatching()`, `featuredProducts()`, `addFeaturedMatches()`, `addBrandSection()`.
- `productCard()`: native product card.
- `pageShell()`, `addLoading()`, `addSectionTitle()`, `actionCard()`.
- `eyebrow()`, `sectionLabel()`, `label()`, `title()`, `body()`, `small()`, `tinyBadge()`, `stat()`.
- `primaryButton()`, `secondaryButton()`, `iconButton()`, `baseButton()`.
- `openUrl()`, `round()`, `gradientPanel()`, `wrapWithMargins()`, `matchWrap()`, `fixedWrap()`, `dp()`, `money()`.

Async/data classes:

- `ApiTask`: authenticated/general REST task.
- `ConfigTask`: update config fetch.
- `SilentApiTask`: background event tracking.
- `ProductTask`: Store API product fetch.
- `ImageTask`: async product image loading.
- `Product`: native product model.
- `CartLine`: native cart line model.

## Function Index: Frontend JavaScript

These are the named frontend functions. Anonymous event callbacks are not listed individually; they are documented by their parent file responsibilities above.

### `assets/js/account-drawer.js`

- `getAjaxUrl()`: returns the localized admin AJAX URL.
- `getAccountUrl()`: returns the My Account URL.
- `getRedirectUrl()`: reads safe redirect parameters.
- `isCheckoutPage()`: detects checkout.
- `getPostAuthRedirect(user)`: chooses where to send users after login/register.
- `isCheckoutAccountLoginLink(href)`: detects checkout account links that should open the drawer.
- `openDrawer(defaultTab)`: opens account drawer.
- `closeDrawer()`: closes account drawer.
- `switchTab(tab)`: switches login/register tabs.
- `clearMessage()`: clears drawer notices.
- `showMessage(text, type)`: displays drawer notices.
- `renderLoggedIn(user)`: renders signed-in drawer state.
- `renderLoggedOut()`: renders signed-out drawer state.
- `setFormLoading($form, loading)`: toggles loading state.
- `ajaxPost(action, nonce, payload)`: posts account AJAX requests.
- `refreshStatus()`: refreshes current account state.

### `assets/js/account-page.js`

- `closeMenu()`: closes mobile account menu.
- `openMenu()`: opens mobile account menu.

### `assets/js/ajax-cart.js`

- `getPriceEl($form)`: locates price element for a product form.
- `ensureDefaultPriceStored($priceEl)`: stores original price HTML/data.
- `parsePriceNumber(input)`: parses a visible money string.
- `formatCurrency(amount)`: formats GBP.
- `buildButtonHtml(label, total)`: builds add button label.
- `syncStrengthPills($form)`: keeps visual strength pills synced with variation selects.
- `updateDisplayedPrice($form, variation)`: updates price for selected variation.
- `getCurrentSingleUnitPrice($form)`: current single product unit price.
- `syncSingleVariationStockNote($form, variation)`: updates low-stock note for variation.
- `syncSingleAddButtonTotal($form)`: updates single add-to-cart total label.
- `enhanceQuantityButtons()`: enhances quantity steppers.
- `showTemporaryButtonState($button, html, delay)`: temporary feedback state.
- `applyCartFragments(fragments)`: applies Woo fragments to DOM.
- `refreshCartDrawerFragments()`: fetches latest drawer fragments.
- `scheduleCartDrawerFragmentRefresh()`: debounces fragment refresh.
- `positionClearCartButton()`: positions clear-cart control.
- `syncClearCartButton()`: shows/hides clear-cart.
- `clearCart()`: AJAX clears cart.
- `isCheckoutPage()`: checkout detector used in multiple closures.
- `getMiniCartMax($wrap)`: reads stock max from mini-cart item.
- `enhanceMiniCartQty()`: turns mini-cart quantities into controls.
- `scheduleEnhanceMiniCartQty()`: debounces mini-cart enhancement.
- `showCardStockMessage($button, message)`: shows limited availability on product cards.
- `openCartDrawer(fragments)`: opens drawer after add-to-cart.
- `resyncSingleProductButton($form)`: returns single product button to idle.
- `showSingleStockMessage(message, $form)`: displays single product limited availability.
- `buildQuickAddButtonHtml(label, total)`: quick-add modal button label.
- `getQuickAddVariations($modal)`: reads variation data from quick-add modal.
- `getSelectedAttributes($modal)`: returns selected quick-add attributes.
- `getQuickAddQuantity($modal)`: reads quick-add quantity.
- `getVariationStockLimit(variation)`: max purchase quantity for variation.
- `getLowStockText(stockLimit)`: low-stock copy.
- `clampQuickAddQuantity($modal, max)`: clamps quantity.
- `getQuickAddPackTiers($modal)`: reads pack tiers from modal.
- `getQuickAddPackTierForQty($modal, qty)`: selects tier for qty.
- `matchesAttributes(variation, selected)`: variation matching helper.
- `isVariationAvailable(variation)`: variation availability helper.
- `findExactVariation($modal)`: exact variation lookup.
- `updateQuickAddPillAvailability($modal)`: disables unavailable pills.
- `syncQuickAddState($modal)`: updates modal price/stock/button.
- `formatCardPrice(amount)`: card price formatter.
- `updateCardAddButton($card)`: updates product card add button total.
- `parseCardPackTiers($card)`: reads card pack tiers.
- `getCardPackTierForQty($card, qty)`: card pack tier lookup.
- `formatSingleSaving(amount)`: saving formatter.
- `updateSingleProductSaving($form)`: single product saving updater.
- `formatPackCurrency(amount)`: pack selector currency formatter.
- `getPackOptions($pricing)`: pack option lookup.
- `getPackTierForQty($pricing, qty)`: single product pack tier lookup.
- `getSingleStockLimit($form)`: stock limit for single product.
- `syncSingleLowStockNote($form)`: low-stock note updater.
- `syncPackOptionAvailability($pricing)`: disables impossible pack options.
- `syncPackPricing($pricing)`: updates selected pack state.
- `formatStickyCurrency(amount)`: sticky bar currency formatter.
- `parseStickyPrice(value)`: sticky bar price parser.
- `getStickyPackTiers($form)`: sticky bar pack tiers.
- `getStickyTierForQty($form, qty)`: sticky selected tier.
- `stickyPackLabel(tier, qty)`: sticky pack label.
- `getStickyUnitPrice($form)`: sticky unit price.
- `syncStickyAddBar($form)`: sticky add-to-cart sync.

### `assets/js/build-a-box.js`

- `selectedCount()`: number of selected products.
- `productById(id)`: product lookup.
- `money(value)`: money formatter.
- `getTotals()`: calculates bundle totals.
- `filterProducts()`: filters candidate products.
- `productCard(product)`: renders product card.
- `renderProducts()`: renders available products.
- `renderSelected()`: renders selected items.
- `renderSummary()`: renders bundle summary.
- `renderAll()`: rerenders full builder.
- `trimToSize()`: clamps selected items to bundle size.
- `addProduct(id)`: adds item.
- `removeProduct(id)`: removes item.
- `setButtonState(button, state)`: feedback state.
- `addLineToCart(line)`: adds one bundle line.
- `applyFragments(fragments)`: applies Woo fragments.
- `addBoxToCart()`: adds selected box to cart.
- `escapeHtml(value)`: HTML escaping.
- `decodeHtml(value)`: HTML decoding.

### `assets/js/main.js`

- `initUrlCoupon()`: initializes URL coupon application.
- `initRewardsForms()`: initializes rewards forms.
- `clamp(value, min, max)`: numeric clamp.
- `sync()`: rewards form sync helper.
- `getRewardsFormValue(form, selector)`: reads reward form values.
- `syncRewardsForm(form)`: updates reward form display.
- `parseRewardsResponse(response)`: extracts AJAX rewards response.
- `getStoreApiNonce()`: reads Store API nonce.
- `callStoreApiCoupon(method, code)`: calls Store API coupon endpoint.
- `isDesktop()`: desktop breakpoint helper.
- `openCartDrawer()`: opens cart drawer.
- `closeCartDrawer()`: closes cart drawer.
- `openMegaDrawer()`: opens mobile mega drawer.
- `closeMegaDrawer()`: closes mobile mega drawer.
- `openDesktopMegaMenu()`: opens desktop mega menu.
- `closeDesktopMegaMenu()`: closes desktop mega menu.
- `setActiveMegaPanel(panel)`: switches mega menu panel.
- `openSearchOverlay()`: opens search overlay.
- `closeSearchOverlay()`: closes search overlay.
- `escapeHtml(value)`: search result escaping.
- `setSearchMessage(message)`: search UI status.
- `renderSearchResults(results)`: renders AJAX search results.
- `runSearch(query)`: executes search.
- `isAgeGateAccepted()`: reads age gate localStorage state.
- `showAgeGate()`: shows gate.
- `hideAgeGate()`: hides gate.
- `blockAgeGate()`: under-18 blocked state.
- `setupCheckoutAgeVerificationRow()`: custom DOB checkout UI.
- `linkCheckoutTermsText()`: links checkout policy text.
- `parseCheckoutMoney(text)`: parses totals.
- `formatCheckoutMoney(amount)`: formats totals.
- `getCheckoutSubtotal()`: reads checkout subtotal.
- `getCartOrCheckoutSubtotal()`: reads current subtotal.
- `getFreeShippingNudgeAnchor(container)`: chooses insertion point.
- `setupFreeShippingNudge()`: renders/updates free shipping nudge.

### `assets/js/pouch-comparison.js`

- `productText(product)`: searchable product text.
- `formatMoney(value, currency)`: money formatter.
- `strengthLevel(product)`: normalized strength bucket.
- `bestFor(product)`: best-use copy.
- `productCard(product)`: renders picker card.
- `visibleProducts()`: filtered product list.
- `renderPicker()`: renders picker.
- `renderSelectedBar()`: renders selected pills.
- `row(label, mapper)`: comparison row.
- `renderTable()`: comparison table.
- `renderAll()`: rerenders comparison UI.
- `toggleProduct(id)`: selects/deselects product.
- `escapeHtml(value)`: HTML escaping.
- `decodeHtml(value)`: HTML decoding.

### `assets/js/pouch-finder.js`

- `getValue(name)`: reads selected answer.
- `updateStep()`: updates active questionnaire step.
- `syncNextButton()`: enables/disables next button.
- `targetStrength(values)`: recommended strength from answers.
- `productStrengthBucket(product)`: product strength bucket.
- `flavourScore(product, flavour)`: flavour match score.
- `strengthScore(product, strength)`: strength match score.
- `useCaseScore(product, useCase)`: use-case match score.
- `buildResultCopy(values, strength)`: result summary text.
- `renderProducts(scored)`: renders matched products.
- `showResult()`: calculates and displays final result.
- `escapeHtml(value)`: HTML escaping.

## Known Maintenance Notes

1. `functions.php` is powerful but very large. Future work should move isolated systems into small includes such as `inc/rewards.php`, `inc/cart.php`, `inc/schema.php`, and `inc/app-download.php` if time allows.
2. The app currently uses a debug APK workflow. Before wider public launch, create a release signing key and build a signed release APK.
3. Because the app is distributed outside Google Play, the landing page must keep explaining Android unknown-source warnings.
4. Push token storage exists, but actual push notification delivery is not implemented yet.
5. Abandoned cart tracking is not implemented. Use a WooCommerce abandoned cart plugin or add a server-side app/cart event table later.
6. WooCommerce draft orders are usually created when checkout/payment is started but not completed. They are not created by normal add-to-cart.
7. Some temporary `error_log()` debug hooks exist around WooCommerce add-to-cart quantity. These should be removed or guarded before high-traffic production use.
8. The app API plugin stores app stats in a WordPress option. That is fine for early use; for high volume, move events to a custom database table.
9. The app's native checkout intentionally hands off to WooCommerce checkout for payment security and to preserve existing payment/shipping/tax rules.
10. Keep reward and coupon rules mirrored between website and app API whenever changing them.

## Common Change Recipes

### Change Free Shipping Threshold

1. Update `kangoo_free_shipping_threshold()` in `functions.php`.
2. Update WooCommerce shipping method minimum amount.
3. Test cart drawer, cart page, checkout page, and Android app free delivery card.

### Change Rewards Conversion

1. Update `kangoo_rewards_points_per_pound_value()` for redemption value.
2. Update app API `kangoo_app_api_rewards_payload()` if app-specific caps change.
3. Test cart redemption, checkout, order completion, refund/cancel return.

### Add A New APK Version

1. Update Android `versionCode` and `versionName`.
2. Build APK.
3. Rename to `kangoo-pouches-vX.Y.Z.apk`.
4. Upload to `/app/android/`.
5. Leave app plugin APK URL blank so auto-detection works.
6. Check `/wp-json/kangoo-app/v1/config?version_code=OLD&version=OLD`.

### Add A Product Filter

1. Add taxonomy/attribute or fallback options.
2. Update `kangoo_product_filter_taxonomy()` and/or `kangoo_product_filter_fallback_options()`.
3. Update shop archive UI and Android filter list if needed.

### Add A New Homepage Section

1. Add an ACF flexible content layout.
2. Add a branch in `front-page.php`.
3. Create a template part in `template-parts/home/`.
4. Add CSS in `assets/css/home.css`.

### Update Mascot Messaging

1. Edit text in `kangoo_helper_mascot_render()`.
2. Edit animation timing in `kangoo-helper-mascot.css` and `kangoo-helper-mascot.js`.
3. Repackage plugin zip with forward slashes.

## Testing Checklist

Website:

- Homepage app CTA points to newest APK.
- Kangoo App page download buttons point to newest APK.
- Product card add-to-cart shows `ADDED [check]`.
- Product card low stock cannot exceed available quantity.
- Product card 10-pack is disabled when stock is below 10.
- Single product add-to-cart recovers from max stock attempts.
- Mini cart updates quantity, subtotal, free shipping nudge, and cart badge.
- Cart page and drawer stay synced.
- Clear cart works.
- Free shipping nudge unlocks at 14.95 GBP.
- URL coupons apply when eligible.
- Kangoo Rewards apply/remove works and does not combine incorrectly with coupons.
- Checkout age verification blocks missing/underage data.
- Account drawer login/register/logout works.

App:

- Splash displays transparent logo.
- Home starts with search bar.
- Search live results display image/title/price/strength.
- Shop filter drawer stays open while selecting filters.
- Cart bottom summary remains visible above phone controls.
- Cart item removal and clear cart work.
- Checkout transfers to Woo checkout with app source metadata.
- Rewards displays balance/history.
- Update notice appears when server version is newer.
