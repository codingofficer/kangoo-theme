<?php
/**
 * Feature-flagged custom AJAX checkout preview.
 *
 * The live checkout remains the WooCommerce Blocks checkout unless the
 * `kangoo_custom_ajax_checkout_enabled` option is enabled. Administrators can
 * preview with `?kangoo_checkout=1`.
 */

defined('ABSPATH') || exit;

function kangoo_custom_ajax_checkout_enabled() {
    $enabled = get_option('kangoo_custom_ajax_checkout_enabled', false);

    if (function_exists('get_field') && get_option('options_kangoo_custom_ajax_checkout_enabled', null) !== null) {
        $enabled = get_field('kangoo_custom_ajax_checkout_enabled', 'option');
    }

    return !empty($enabled);
}

function kangoo_custom_ajax_checkout_register_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_kangoo_custom_ajax_checkout',
        'title' => __('Checkout Experience', 'kangoo'),
        'fields' => array(
            array(
                'key' => 'field_kangoo_custom_ajax_checkout_enabled',
                'label' => __('Custom AJAX Checkout', 'kangoo'),
                'name' => 'kangoo_custom_ajax_checkout_enabled',
                'type' => 'true_false',
                'instructions' => __('Enable the feature-flagged 4-step AJAX checkout for shoppers. Leave disabled while testing; admins can preview with /checkout/?kangoo_checkout=1&step=delivery. Fallback remains available at /checkout/?classic_checkout=1.', 'kangoo'),
                'message' => __('Use custom multi-step checkout on cart and checkout pages', 'kangoo'),
                'ui' => 1,
                'default_value' => 0,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'control-panel',
                ),
            ),
        ),
        'position' => 'acf_after_title',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 0,
    ));
}
add_action('acf/init', 'kangoo_custom_ajax_checkout_register_acf_fields');

function kangoo_custom_ajax_checkout_sanitize_enabled($value) {
    return !empty($value) ? 1 : 0;
}

function kangoo_custom_ajax_checkout_register_fallback_setting() {
    register_setting('kangoo_custom_ajax_checkout_options', 'kangoo_custom_ajax_checkout_enabled', array(
        'sanitize_callback' => 'kangoo_custom_ajax_checkout_sanitize_enabled',
        'default'           => 0,
    ));
}
add_action('admin_init', 'kangoo_custom_ajax_checkout_register_fallback_setting');

function kangoo_custom_ajax_checkout_preview_enabled() {
    return isset($_GET['kangoo_checkout'])
        && '1' === (string) wp_unslash($_GET['kangoo_checkout'])
        && is_user_logged_in()
        && current_user_can('manage_woocommerce');
}

function kangoo_custom_ajax_checkout_active() {
    if (isset($_GET['classic_checkout'])) {
        return false;
    }

    if (!function_exists('is_checkout') || !is_checkout() || (function_exists('is_order_received_page') && is_order_received_page())) {
        return false;
    }

    return kangoo_custom_ajax_checkout_enabled() || kangoo_custom_ajax_checkout_preview_enabled();
}

function kangoo_custom_ajax_checkout_requested() {
    if (isset($_GET['classic_checkout'])) {
        return false;
    }

    return kangoo_custom_ajax_checkout_enabled() || kangoo_custom_ajax_checkout_preview_enabled();
}

function kangoo_custom_ajax_checkout_body_class($classes) {
    $is_cart = function_exists('is_cart') && is_cart();
    $is_checkout = function_exists('is_checkout') && is_checkout() && !(function_exists('is_order_received_page') && is_order_received_page());

    if (($is_cart || $is_checkout) && kangoo_custom_ajax_checkout_requested()) {
        $classes[] = 'kangoo-custom-checkout-enabled';
    }

    return $classes;
}
add_filter('body_class', 'kangoo_custom_ajax_checkout_body_class');

function kangoo_custom_ajax_checkout_asset_version($relative_path) {
    $path = get_theme_file_path($relative_path);

    return file_exists($path) ? (string) filemtime($path) : wp_get_theme()->get('Version');
}

function kangoo_custom_ajax_checkout_enqueue_assets() {
    $is_cart = function_exists('is_cart') && is_cart();
    $is_checkout = function_exists('is_checkout') && is_checkout() && !(function_exists('is_order_received_page') && is_order_received_page());
    $feature_requested = kangoo_custom_ajax_checkout_requested();

    if (!$feature_requested || (!$is_cart && !$is_checkout)) {
        return;
    }

    wp_enqueue_style(
        'kangoo-custom-checkout',
        get_theme_file_uri('/assets/css/custom-checkout.css'),
        array(),
        kangoo_custom_ajax_checkout_asset_version('/assets/css/custom-checkout.css')
    );

    wp_enqueue_script(
        'kangoo-custom-checkout',
        get_theme_file_uri('/assets/js/custom-checkout.js'),
        array(),
        kangoo_custom_ajax_checkout_asset_version('/assets/js/custom-checkout.js'),
        true
    );

    wp_localize_script('kangoo-custom-checkout', 'kangooCustomCheckout', array(
        'enabled' => kangoo_custom_ajax_checkout_enabled(),
        'preview' => kangoo_custom_ajax_checkout_preview_enabled(),
        'active' => kangoo_custom_ajax_checkout_active(),
        'restUrl' => untrailingslashit(rest_url('kangoo-checkout/v1')),
        'nonce' => wp_create_nonce('wp_rest'),
        'checkoutUrl' => wc_get_checkout_url(),
        'cartUrl' => wc_get_cart_url(),
        'classicCheckoutUrl' => add_query_arg('classic_checkout', '1', wc_get_checkout_url()),
    ));
}
add_action('wp_enqueue_scripts', 'kangoo_custom_ajax_checkout_enqueue_assets', 45);

function kangoo_custom_ajax_checkout_step_key() {
    $step = isset($_GET['step']) ? sanitize_key(wp_unslash($_GET['step'])) : 'delivery';
    return in_array($step, array('delivery', 'verify', 'payment'), true) ? $step : 'delivery';
}

function kangoo_custom_ajax_checkout_shell($checkout_content = '') {
    $step = kangoo_custom_ajax_checkout_step_key();
    ob_start();
    ?>
    <div class="kangoo-custom-checkout" data-kangoo-custom-checkout data-step="<?php echo esc_attr($step); ?>">
        <div class="kangoo-custom-checkout__card">
            <nav class="kangoo-custom-checkout__steps" aria-label="<?php esc_attr_e('Checkout progress', 'kangoo'); ?>">
                <a class="kangoo-custom-checkout__step is-complete" href="<?php echo esc_url(wc_get_cart_url()); ?>">
                    <span>1</span><strong><?php esc_html_e('Cart', 'kangoo'); ?></strong>
                </a>
                <button type="button" class="kangoo-custom-checkout__step" data-kangoo-step-target="delivery">
                    <span>2</span><strong><?php esc_html_e('Delivery', 'kangoo'); ?></strong>
                </button>
                <button type="button" class="kangoo-custom-checkout__step" data-kangoo-step-target="verify">
                    <span>3</span><strong><?php esc_html_e('Verify', 'kangoo'); ?></strong>
                </button>
                <button type="button" class="kangoo-custom-checkout__step" data-kangoo-step-target="payment">
                    <span>4</span><strong><?php esc_html_e('Payment', 'kangoo'); ?></strong>
                </button>
            </nav>

            <div class="kangoo-custom-checkout__body" aria-live="polite">
                <section class="kangoo-custom-checkout__panel" data-kangoo-panel="delivery">
                    <h1><?php esc_html_e('Delivery details', 'kangoo'); ?></h1>
                    <p><?php esc_html_e('Enter your email and delivery information', 'kangoo'); ?></p>
                    <form class="kangoo-custom-checkout__form" data-kangoo-delivery-form novalidate>
                        <label>
                            <span><?php esc_html_e('Email address', 'kangoo'); ?></span>
                            <input type="email" name="email" autocomplete="email" placeholder="email@domain.com" required>
                        </label>
                        <h2><?php esc_html_e('Shipping address', 'kangoo'); ?></h2>
                        <label>
                            <span><?php esc_html_e('Full name', 'kangoo'); ?></span>
                            <input type="text" name="full_name" autocomplete="name" placeholder="<?php esc_attr_e('Enter your full name', 'kangoo'); ?>" required>
                        </label>
                        <label>
                            <span><?php esc_html_e('Address line 1', 'kangoo'); ?></span>
                            <input type="text" name="address_1" autocomplete="address-line1" placeholder="<?php esc_attr_e('Street address', 'kangoo'); ?>" required>
                        </label>
                        <label>
                            <span><?php esc_html_e('Address line 2 (optional)', 'kangoo'); ?></span>
                            <input type="text" name="address_2" autocomplete="address-line2" placeholder="<?php esc_attr_e('Apartment, suite, etc.', 'kangoo'); ?>">
                        </label>
                        <div class="kangoo-custom-checkout__grid">
                            <label>
                                <span><?php esc_html_e('City', 'kangoo'); ?></span>
                                <input type="text" name="city" autocomplete="address-level2" required>
                            </label>
                            <label>
                                <span><?php esc_html_e('Postcode', 'kangoo'); ?></span>
                                <input type="text" name="postcode" autocomplete="postal-code" required>
                            </label>
                        </div>
                        <label>
                            <span><?php esc_html_e('Country', 'kangoo'); ?></span>
                            <select name="country" autocomplete="country">
                                <option value="GB"><?php esc_html_e('United Kingdom', 'kangoo'); ?></option>
                            </select>
                        </label>
                        <label class="kangoo-custom-checkout__checkbox">
                            <input type="checkbox" name="billing_same" value="1" checked>
                            <span><?php esc_html_e('Use same address for billing', 'kangoo'); ?></span>
                        </label>
                        <div class="kangoo-custom-checkout__billing" data-kangoo-billing-fields hidden>
                            <h2><?php esc_html_e('Billing address', 'kangoo'); ?></h2>
                            <label>
                                <span><?php esc_html_e('Billing address line 1', 'kangoo'); ?></span>
                                <input type="text" name="billing_address_1" autocomplete="billing address-line1">
                            </label>
                            <div class="kangoo-custom-checkout__grid">
                                <label>
                                    <span><?php esc_html_e('Billing city', 'kangoo'); ?></span>
                                    <input type="text" name="billing_city" autocomplete="billing address-level2">
                                </label>
                                <label>
                                    <span><?php esc_html_e('Billing postcode', 'kangoo'); ?></span>
                                    <input type="text" name="billing_postcode" autocomplete="billing postal-code">
                                </label>
                            </div>
                        </div>
                        <h2><?php esc_html_e('Delivery options', 'kangoo'); ?></h2>
                        <div class="kangoo-custom-checkout__delivery-options" data-kangoo-delivery-options></div>
                        <p class="kangoo-custom-checkout__message" data-kangoo-message="delivery"></p>
                        <button type="submit" class="kangoo-custom-checkout__primary">
                            <span><?php esc_html_e('Continue', 'kangoo'); ?></span>
                            <i class="fa fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                        <a class="kangoo-custom-checkout__back" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Back to cart', 'kangoo'); ?></a>
                    </form>
                </section>

                <section class="kangoo-custom-checkout__panel" data-kangoo-panel="verify" hidden>
                    <h1><?php esc_html_e('Verify your age', 'kangoo'); ?></h1>
                    <p><?php esc_html_e('Enter your date of birth to continue.', 'kangoo'); ?></p>
                    <form class="kangoo-custom-checkout__form" data-kangoo-dob-form novalidate>
                        <label>
                            <span><?php esc_html_e('Date of birth', 'kangoo'); ?></span>
                            <input type="text" name="dob" inputmode="numeric" autocomplete="bday" placeholder="DD / MM / YYYY" required>
                            <small><?php esc_html_e('You must be 18 or over to purchase.', 'kangoo'); ?></small>
                        </label>
                        <p class="kangoo-custom-checkout__message" data-kangoo-message="verify"></p>
                        <button type="submit" class="kangoo-custom-checkout__primary" data-kangoo-dob-submit disabled>
                            <span><?php esc_html_e('Continue to payment', 'kangoo'); ?></span>
                            <i class="fa fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="kangoo-custom-checkout__back" data-kangoo-step-target="delivery"><?php esc_html_e('Back to delivery details', 'kangoo'); ?></button>
                    </form>
                </section>

                <section class="kangoo-custom-checkout__panel" data-kangoo-panel="payment" hidden>
                    <h1><?php esc_html_e('Payment', 'kangoo'); ?></h1>
                    <p><?php esc_html_e('Choose your preferred payment method', 'kangoo'); ?></p>
                    <?php if (!is_user_logged_in()) : ?>
                    <div class="kangoo-checkout-guest-notice" data-kangoo-checkout-guest-notice>
                        <span aria-hidden="true">i</span>
                        <div class="kangoo-checkout-guest-notice__copy">
                            <?php esc_html_e('You are currently checking out as a guest. Click', 'kangoo'); ?>
                            <a href="#" data-account-open="login"><?php esc_html_e('here', 'kangoo'); ?></a>
                            <?php esc_html_e('to sign in.', 'kangoo'); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (function_exists('kangoo_rewards_get_cart_box_html')) : ?>
                    <?php echo kangoo_rewards_get_cart_box_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endif; ?>
                    <div class="kangoo-custom-checkout__summary" data-kangoo-order-summary></div>
                    <label class="kangoo-custom-checkout__checkbox">
                        <input type="checkbox" data-kangoo-note-toggle>
                        <span><?php esc_html_e('Add a note to your order', 'kangoo'); ?></span>
                    </label>
                    <textarea data-kangoo-order-note hidden placeholder="<?php esc_attr_e('Order note', 'kangoo'); ?>"></textarea>
                    <div class="kangoo-custom-checkout__native-bridge">
                        <div class="kangoo-custom-checkout__divider"><?php esc_html_e('Secure payment form', 'kangoo'); ?></div>
                        <p><?php esc_html_e('Enter your payment details below to complete your order securely.', 'kangoo'); ?></p>
                        <div class="kangoo-custom-checkout__payment-pause" data-kangoo-payment-pause hidden>
                            <strong><?php esc_html_e('Our payment system is being updated', 'kangoo'); ?></strong>
                            <p><?php esc_html_e('We are switching payment providers so checkout may be temporarily unavailable. Email hello@kangoopouches.co.uk for help, or leave your email below and we will send you a 20% discount when payments are back.', 'kangoo'); ?></p>
                            <form data-kangoo-payment-pause-form novalidate>
                                <label>
                                    <span><?php esc_html_e('Email address', 'kangoo'); ?></span>
                                    <input type="email" name="email" autocomplete="email" placeholder="email@domain.com" required>
                                </label>
                                <button type="submit"><?php esc_html_e('Notify me and send 20% off', 'kangoo'); ?></button>
                                <small data-kangoo-payment-pause-message></small>
                            </form>
                        </div>
                        <div class="kangoo-custom-checkout__woo-bridge" data-kangoo-woo-bridge>
                            <?php echo $checkout_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                    <button type="button" class="kangoo-custom-checkout__back" data-kangoo-step-target="verify"><?php esc_html_e('Back to age verification', 'kangoo'); ?></button>
                </section>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kangoo_custom_ajax_checkout_filter_content($content) {
    if (!kangoo_custom_ajax_checkout_active()) {
        return $content;
    }

    return kangoo_custom_ajax_checkout_shell($content);
}
add_filter('the_content', 'kangoo_custom_ajax_checkout_filter_content', 9);

function kangoo_custom_ajax_checkout_load_cart() {
    if (!function_exists('WC')) {
        return new WP_Error('kangoo_checkout_wc_missing', __('WooCommerce is not available.', 'kangoo'), array('status' => 500));
    }

    if (function_exists('wc_load_cart') && (!WC()->cart || !WC()->session)) {
        wc_load_cart();
    }

    if (!WC()->cart || WC()->cart->is_empty()) {
        return new WP_Error('kangoo_checkout_empty_cart', __('Your cart is empty.', 'kangoo'), array('status' => 409));
    }

    return true;
}

function kangoo_custom_ajax_checkout_money($amount) {
    return html_entity_decode(wp_strip_all_tags(wc_price((float) $amount)), ENT_QUOTES, 'UTF-8');
}

function kangoo_custom_ajax_checkout_cart_items() {
    $items = array();

    foreach (WC()->cart->get_cart() as $key => $cart_item) {
        $product = isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product ? $cart_item['data'] : null;
        if (!$product) {
            continue;
        }

        $items[] = array(
            'key' => $key,
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'quantity' => (int) $cart_item['quantity'],
            'price' => kangoo_custom_ajax_checkout_money((float) $product->get_price()),
            'lineTotal' => kangoo_custom_ajax_checkout_money((float) $cart_item['line_total']),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail'),
        );
    }

    return $items;
}

function kangoo_custom_ajax_checkout_shipping_rates() {
    WC()->cart->calculate_shipping();
    WC()->cart->calculate_totals();

    $chosen = WC()->session ? (array) WC()->session->get('chosen_shipping_methods', array()) : array();
    $packages = WC()->shipping()->get_packages();
    $rates = array();

    foreach ($packages as $package_index => $package) {
        if (empty($package['rates'])) {
            continue;
        }

        foreach ($package['rates'] as $rate_id => $rate) {
            if (!$rate instanceof WC_Shipping_Rate) {
                continue;
            }

            $rates[] = array(
                'id' => $rate_id,
                'package' => $package_index,
                'label' => $rate->get_label(),
                'price' => kangoo_custom_ajax_checkout_money((float) $rate->get_cost()),
                'selected' => isset($chosen[$package_index]) ? $chosen[$package_index] === $rate_id : !$rates,
            );
        }
    }

    return $rates;
}

function kangoo_custom_ajax_checkout_state_response() {
    WC()->cart->calculate_totals();
    $customer = WC()->customer;
    $dob = function_exists('kangoo_get_saved_checkout_dob') ? kangoo_get_saved_checkout_dob() : '';
    $age = $dob && function_exists('kangoo_calculate_age_from_date') ? kangoo_calculate_age_from_date($dob) : null;

    return array(
        'cartHash' => WC()->cart->get_cart_hash(),
        'items' => kangoo_custom_ajax_checkout_cart_items(),
        'totals' => array(
            'subtotal' => kangoo_custom_ajax_checkout_money((float) WC()->cart->get_subtotal()),
            'shipping' => kangoo_custom_ajax_checkout_money((float) WC()->cart->get_shipping_total()),
            'total' => html_entity_decode(wp_strip_all_tags(WC()->cart->get_total()), ENT_QUOTES, 'UTF-8'),
        ),
        'coupons' => WC()->cart->get_applied_coupons(),
        'shippingRates' => kangoo_custom_ajax_checkout_shipping_rates(),
        'customer' => array(
            'email' => function_exists('kangoo_get_saved_checkout_email') ? kangoo_get_saved_checkout_email() : ($customer ? $customer->get_billing_email() : ''),
            'shipping' => $customer ? array(
                'first_name' => $customer->get_shipping_first_name(),
                'last_name' => $customer->get_shipping_last_name(),
                'address_1' => $customer->get_shipping_address_1(),
                'address_2' => $customer->get_shipping_address_2(),
                'city' => $customer->get_shipping_city(),
                'postcode' => $customer->get_shipping_postcode(),
                'country' => $customer->get_shipping_country() ?: 'GB',
            ) : array(),
        ),
        'dob' => array(
            'value' => $dob,
            'valid' => $age !== null && $age >= 18,
        ),
        'verification' => array(
            'enabled' => !empty($GLOBALS['kangooAgeVerification']) ? false : false,
        ),
    );
}

function kangoo_custom_ajax_checkout_register_routes() {
    register_rest_route('kangoo-checkout/v1', '/state', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'kangoo_custom_ajax_checkout_rest_state',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('kangoo-checkout/v1', '/delivery', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'kangoo_custom_ajax_checkout_rest_delivery',
        'permission_callback' => 'kangoo_custom_ajax_checkout_rest_permission',
    ));
    register_rest_route('kangoo-checkout/v1', '/dob', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'kangoo_custom_ajax_checkout_rest_dob',
        'permission_callback' => 'kangoo_custom_ajax_checkout_rest_permission',
    ));
    register_rest_route('kangoo-checkout/v1', '/note', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'kangoo_custom_ajax_checkout_rest_note',
        'permission_callback' => 'kangoo_custom_ajax_checkout_rest_permission',
    ));
    register_rest_route('kangoo-checkout/v1', '/verification/session', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'kangoo_custom_ajax_checkout_rest_verification_session',
        'permission_callback' => 'kangoo_custom_ajax_checkout_rest_permission',
    ));
    register_rest_route('kangoo-checkout/v1', '/verification/check', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'kangoo_custom_ajax_checkout_rest_verification_check',
        'permission_callback' => 'kangoo_custom_ajax_checkout_rest_permission',
    ));
    register_rest_route('kangoo-checkout/v1', '/place-order', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'kangoo_custom_ajax_checkout_rest_place_order',
        'permission_callback' => 'kangoo_custom_ajax_checkout_rest_permission',
    ));
    register_rest_route('kangoo-checkout/v1', '/payment-waitlist', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'kangoo_custom_ajax_checkout_rest_payment_waitlist',
        'permission_callback' => 'kangoo_custom_ajax_checkout_rest_permission',
    ));
}
add_action('rest_api_init', 'kangoo_custom_ajax_checkout_register_routes');

function kangoo_custom_ajax_checkout_rest_permission($request) {
    return wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')
        ? true
        : new WP_Error('kangoo_checkout_nonce', __('Your checkout session expired. Refresh and try again.', 'kangoo'), array('status' => 403));
}

function kangoo_custom_ajax_checkout_rest_state() {
    $loaded = kangoo_custom_ajax_checkout_load_cart();
    if (is_wp_error($loaded)) {
        return $loaded;
    }

    return rest_ensure_response(kangoo_custom_ajax_checkout_state_response());
}

function kangoo_custom_ajax_checkout_split_name($full_name) {
    $parts = preg_split('/\s+/', trim((string) $full_name));
    $first = $parts ? array_shift($parts) : '';

    return array($first, trim(implode(' ', $parts)));
}

function kangoo_custom_ajax_checkout_rest_delivery($request) {
    $loaded = kangoo_custom_ajax_checkout_load_cart();
    if (is_wp_error($loaded)) {
        return $loaded;
    }

    $email = sanitize_email((string) $request->get_param('email'));
    $full_name = sanitize_text_field((string) $request->get_param('full_name'));
    $address_1 = sanitize_text_field((string) $request->get_param('address_1'));
    $city = sanitize_text_field((string) $request->get_param('city'));
    $postcode = sanitize_text_field((string) $request->get_param('postcode'));
    $country = sanitize_text_field((string) $request->get_param('country')) ?: 'GB';
    $shipping_rate = sanitize_text_field((string) $request->get_param('shipping_rate'));

    if (!is_email($email)) {
        return new WP_Error('kangoo_checkout_email_invalid', __('Enter a valid email address.', 'kangoo'), array('status' => 400, 'field' => 'email'));
    }

    if (!$full_name) {
        return new WP_Error('kangoo_checkout_name_missing', __('Full name is missing.', 'kangoo'), array('status' => 400, 'field' => 'full_name'));
    }

    if (!$address_1) {
        return new WP_Error('kangoo_checkout_address_missing', __('Address line 1 is missing.', 'kangoo'), array('status' => 400, 'field' => 'address_1'));
    }

    if (!$city) {
        return new WP_Error('kangoo_checkout_city_missing', __('City is missing.', 'kangoo'), array('status' => 400, 'field' => 'city'));
    }

    if (!$postcode) {
        return new WP_Error('kangoo_checkout_postcode_missing', __('Postcode is missing.', 'kangoo'), array('status' => 400, 'field' => 'postcode'));
    }

    if (!$shipping_rate) {
        return new WP_Error('kangoo_checkout_shipping_missing', __('Choose a delivery option.', 'kangoo'), array('status' => 400, 'field' => 'shipping_rate'));
    }

    list($first_name, $last_name) = kangoo_custom_ajax_checkout_split_name($full_name);
    $address_2 = sanitize_text_field((string) $request->get_param('address_2'));
    $billing_same = wc_string_to_bool($request->get_param('billing_same'));
    $customer = WC()->customer;

    $customer->set_billing_email($email);
    $customer->set_shipping_first_name($first_name);
    $customer->set_shipping_last_name($last_name ?: $first_name);
    $customer->set_shipping_address_1($address_1);
    $customer->set_shipping_address_2($address_2);
    $customer->set_shipping_city($city);
    $customer->set_shipping_postcode($postcode);
    $customer->set_shipping_country($country);

    if ($billing_same) {
        $customer->set_billing_first_name($customer->get_shipping_first_name());
        $customer->set_billing_last_name($customer->get_shipping_last_name());
        $customer->set_billing_address_1($customer->get_shipping_address_1());
        $customer->set_billing_address_2($customer->get_shipping_address_2());
        $customer->set_billing_city($customer->get_shipping_city());
        $customer->set_billing_postcode($customer->get_shipping_postcode());
        $customer->set_billing_country($customer->get_shipping_country());
    }

    $customer->save();

    if (function_exists('wc_setcookie')) {
        wc_setcookie('kangoo_checkout_email', $email, time() + (14 * DAY_IN_SECONDS), is_ssl(), false);
    }

    WC()->cart->calculate_shipping();
    WC()->cart->calculate_totals();

    $available_rate_ids = array();
    foreach (WC()->shipping()->get_packages() as $package) {
        foreach ((array) $package['rates'] as $rate_id => $rate) {
            $available_rate_ids[] = (string) $rate_id;
        }
    }

    if (!in_array($shipping_rate, $available_rate_ids, true)) {
        return new WP_Error('kangoo_checkout_shipping_invalid', __('Choose an available delivery option.', 'kangoo'), array('status' => 400));
    }

    if (WC()->session) {
        WC()->session->set('kangoo_checkout_email', $email);
        WC()->session->set('chosen_shipping_methods', array($shipping_rate));
    }

    return rest_ensure_response(kangoo_custom_ajax_checkout_state_response());
}

function kangoo_custom_ajax_checkout_rest_dob($request) {
    $loaded = kangoo_custom_ajax_checkout_load_cart();
    if (is_wp_error($loaded)) {
        return $loaded;
    }

    $raw = sanitize_text_field((string) $request->get_param('dob'));
    $digits = preg_replace('/\D+/', '', $raw);
    if (!preg_match('/^(\d{2})(\d{2})(\d{4})$/', $digits, $matches)) {
        return new WP_Error('kangoo_checkout_dob_invalid', __('Enter your date of birth as DD / MM / YYYY.', 'kangoo'), array('status' => 400));
    }

    $dob = kangoo_normalize_dob_parts($matches[1], $matches[2], $matches[3]);
    $age = $dob && function_exists('kangoo_calculate_age_from_date') ? kangoo_calculate_age_from_date($dob) : null;
    if (!$dob || $age === null || $age < 18) {
        return new WP_Error('kangoo_checkout_dob_underage', __('You must be 18 or over to place an order.', 'kangoo'), array('status' => 400));
    }

    if (WC()->session) {
        WC()->session->set('kangoo_checkout_dob', $dob);
    }
    if (function_exists('wc_setcookie')) {
        wc_setcookie('kangoo_checkout_dob', $dob, time() + (14 * DAY_IN_SECONDS), is_ssl(), false);
    }
    if (is_user_logged_in()) {
        update_user_meta(get_current_user_id(), 'kangoo_date_of_birth', $dob);
    }

    return rest_ensure_response(kangoo_custom_ajax_checkout_state_response());
}

function kangoo_custom_ajax_checkout_rest_note($request) {
    $loaded = kangoo_custom_ajax_checkout_load_cart();
    if (is_wp_error($loaded)) {
        return $loaded;
    }

    $note = sanitize_textarea_field((string) $request->get_param('note'));
    if (WC()->session) {
        WC()->session->set('kangoo_checkout_order_note', $note);
    }

    return rest_ensure_response(array('note' => $note));
}

function kangoo_custom_ajax_checkout_rest_verification_session($request) {
    if (!class_exists('Kangoo_Age_Verification')) {
        return new WP_Error('kangoo_checkout_verification_unavailable', __('Photo-ID verification is not available.', 'kangoo'), array('status' => 409));
    }

    return Kangoo_Age_Verification::instance()->rest_create_session($request);
}

function kangoo_custom_ajax_checkout_rest_verification_check($request) {
    if (!class_exists('Kangoo_Age_Verification')) {
        return new WP_Error('kangoo_checkout_verification_unavailable', __('Photo-ID verification is not available.', 'kangoo'), array('status' => 409));
    }

    return Kangoo_Age_Verification::instance()->rest_check_session($request);
}

function kangoo_custom_ajax_checkout_rest_place_order($request) {
    $loaded = kangoo_custom_ajax_checkout_load_cart();
    if (is_wp_error($loaded)) {
        return $loaded;
    }

    if (!wc_string_to_bool($request->get_param('terms'))) {
        return new WP_Error('kangoo_checkout_terms_required', __('Accept the terms before placing your order.', 'kangoo'), array('status' => 400));
    }

    $dob = function_exists('kangoo_get_saved_checkout_dob') ? kangoo_get_saved_checkout_dob() : '';
    $age = $dob && function_exists('kangoo_calculate_age_from_date') ? kangoo_calculate_age_from_date($dob) : null;
    if (!$dob || $age === null || $age < 18) {
        return new WP_Error('kangoo_checkout_age_required', __('Enter a valid date of birth before payment.', 'kangoo'), array('status' => 400));
    }

    return rest_ensure_response(array(
        'validated' => true,
        'message' => __('Checkout details validated. Complete payment in the secure WooPayments form.', 'kangoo'),
        'classicCheckoutUrl' => add_query_arg('classic_checkout', '1', wc_get_checkout_url()),
    ));
}

function kangoo_custom_ajax_checkout_rest_payment_waitlist($request) {
    $email = sanitize_email((string) $request->get_param('email'));

    if (!is_email($email)) {
        return new WP_Error('kangoo_checkout_waitlist_email', __('Enter a valid email address.', 'kangoo'), array('status' => 400));
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) && preg_match('/^[0-9a-fA-F:.]+$/', (string) $_SERVER['REMOTE_ADDR'])
        ? (string) $_SERVER['REMOTE_ADDR']
        : '';
    $rate_key = 'kangoo_payment_waitlist_' . md5(strtolower($email) . '|' . $ip);

    if (get_transient($rate_key)) {
        return rest_ensure_response(array(
            'saved' => true,
            'message' => __('You are already on the list. We will email you when payments are back.', 'kangoo'),
        ));
    }

    set_transient($rate_key, 1, HOUR_IN_SECONDS);

    $post_id = wp_insert_post(array(
        'post_type'    => 'kangoo_enquiry',
        'post_status'  => 'publish',
        'post_title'   => sprintf('[Payment update signup] %s', $email),
        'post_content' => __('Customer asked to be notified when payments are back and receive the 20% discount.', 'kangoo'),
    ), true);

    if (is_wp_error($post_id)) {
        return new WP_Error('kangoo_checkout_waitlist_save', __('We could not save your email. Please email hello@kangoopouches.co.uk.', 'kangoo'), array('status' => 500));
    }

    update_post_meta($post_id, '_kangoo_contact_name', '');
    update_post_meta($post_id, '_kangoo_contact_email', $email);
    update_post_meta($post_id, '_kangoo_contact_topic', 'payment_update');
    update_post_meta($post_id, '_kangoo_contact_topic_label', __('Payment update signup', 'kangoo'));
    update_post_meta($post_id, '_kangoo_contact_status', 'new');
    update_post_meta($post_id, '_kangoo_contact_ip', $ip);
    update_post_meta($post_id, '_kangoo_contact_user_agent', substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255));

    $recipient = function_exists('kangoo_contact_general_email') ? kangoo_contact_general_email() : get_option('admin_email');
    if (is_email($recipient)) {
        wp_mail(
            $recipient,
            sprintf('[Kangoo Pouches] Payment update signup - %s', $email),
            sprintf("A customer joined the payment update list.\n\nEmail: %s\nAdmin record: %s", $email, get_edit_post_link($post_id, 'raw')),
            array('Content-Type: text/plain; charset=UTF-8')
        );
    }

    return rest_ensure_response(array(
        'saved' => true,
        'message' => __('Thanks. We will email you when payments are back with your 20% discount.', 'kangoo'),
    ));
}
