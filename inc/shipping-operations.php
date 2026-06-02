<?php
defined('ABSPATH') || exit;

function kangoo_shipping_statuses() {
    return array(
        'packed'  => __('Packed', 'kangoo'),
        'shipped' => __('Shipped', 'kangoo'),
        'delayed' => __('Delayed', 'kangoo'),
    );
}

function kangoo_register_shipping_order_statuses() {
    foreach (kangoo_shipping_statuses() as $status => $label) {
        register_post_status('wc-' . $status, array(
            'label'                     => $label,
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'kangoo'),
        ));
    }
}
add_action('init', 'kangoo_register_shipping_order_statuses');

function kangoo_add_shipping_order_statuses($statuses) {
    $new_statuses = array();

    foreach ($statuses as $status => $label) {
        $new_statuses[$status] = $label;

        if ($status === 'wc-processing') {
            foreach (kangoo_shipping_statuses() as $shipping_status => $shipping_label) {
                $new_statuses['wc-' . $shipping_status] = $shipping_label;
            }
        }
    }

    return $new_statuses;
}
add_filter('wc_order_statuses', 'kangoo_add_shipping_order_statuses');

function kangoo_shipping_meta_keys() {
    return array(
        'carrier'         => '_kangoo_shipping_carrier',
        'service'         => '_kangoo_shipping_service',
        'tracking_number' => '_kangoo_tracking_number',
        'tracking_url'    => '_kangoo_tracking_url',
        'note'            => '_kangoo_shipping_note',
    );
}

function kangoo_shipping_get_order_meta($order, $field, $default = '') {
    $keys = kangoo_shipping_meta_keys();

    if (!$order instanceof WC_Order || !isset($keys[$field])) {
        return $default;
    }

    $value = $order->get_meta($keys[$field]);

    return $value !== '' ? $value : $default;
}

function kangoo_shipping_default_tracking_url($tracking_number) {
    $tracking_number = trim((string) $tracking_number);

    if ($tracking_number === '') {
        return '';
    }

    return 'https://www.royalmail.com/track-your-item#/tracking-results/' . rawurlencode($tracking_number);
}

function kangoo_shipping_get_tracking_url($order) {
    $custom_url = kangoo_shipping_get_order_meta($order, 'tracking_url');

    if ($custom_url) {
        return esc_url_raw($custom_url);
    }

    return kangoo_shipping_default_tracking_url(kangoo_shipping_get_order_meta($order, 'tracking_number'));
}

function kangoo_shipping_normalize_tracking_url($url) {
    $url = trim((string) $url);

    if ($url === '') {
        return '';
    }

    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    return esc_url_raw($url);
}

function kangoo_shipping_display_tracking_url($url) {
    $url = trim((string) $url);

    return preg_replace('#^https?://#i', '', $url);
}

function kangoo_shipping_save_order_fields($order) {
    static $processed = array();

    if (!$order instanceof WC_Order || isset($processed[$order->get_id()])) {
        return;
    }

    if (
        empty($_POST['kangoo_shipping_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kangoo_shipping_nonce'])), 'kangoo_shipping_fields')
    ) {
        return;
    }

    $processed[$order->get_id()] = true;
    $keys = kangoo_shipping_meta_keys();

    foreach ($keys as $field => $meta_key) {
        $value = isset($_POST['kangoo_shipping_' . $field]) ? sanitize_textarea_field(wp_unslash($_POST['kangoo_shipping_' . $field])) : '';

        if ($field === 'tracking_url') {
            $value = kangoo_shipping_normalize_tracking_url($value);
        }

        $order->update_meta_data($meta_key, $value);
    }

    $new_status = isset($_POST['kangoo_shipping_status']) ? sanitize_key(wp_unslash($_POST['kangoo_shipping_status'])) : '';
    $allowed_statuses = array_merge(array('processing', 'completed'), array_keys(kangoo_shipping_statuses()));

    if ($new_status && in_array($new_status, $allowed_statuses, true) && $new_status !== $order->get_status()) {
        $order->update_status($new_status, __('Shipping status updated from Kangoo shipping fields.', 'kangoo'), true);
    }
}

function kangoo_shipping_save_order_fields_from_post($order_id) {
    $order = wc_get_order($order_id);

    if ($order) {
        kangoo_shipping_save_order_fields($order);
        $order->save();
    }
}
add_action('woocommerce_process_shop_order_meta', 'kangoo_shipping_save_order_fields_from_post', 30);
add_action('woocommerce_admin_process_shop_order_object', 'kangoo_shipping_save_order_fields', 30);

function kangoo_shipping_order_meta_box() {
    $screens = array('shop_order', 'woocommerce_page_wc-orders');

    foreach ($screens as $screen) {
        add_meta_box(
            'kangoo-shipping-details',
            __('Kangoo Shipping', 'kangoo'),
            'kangoo_render_shipping_order_meta_box',
            $screen,
            'side',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'kangoo_shipping_order_meta_box');

function kangoo_render_shipping_order_meta_box($post_or_order) {
    $order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order($post_or_order->ID);

    if (!$order) {
        return;
    }

    $status_options = array(
        'processing' => __('Processing', 'kangoo'),
        'packed'     => __('Packed', 'kangoo'),
        'shipped'    => __('Shipped', 'kangoo'),
        'delayed'    => __('Delayed', 'kangoo'),
        'completed'  => __('Completed', 'kangoo'),
    );

    wp_nonce_field('kangoo_shipping_fields', 'kangoo_shipping_nonce');
    ?>
    <div class="kangoo-shipping-fields">
        <p>
            <label for="kangoo_shipping_status"><strong><?php esc_html_e('Shipping status', 'kangoo'); ?></strong></label>
            <select id="kangoo_shipping_status" name="kangoo_shipping_status" style="width:100%;">
                <?php foreach ($status_options as $status => $label) : ?>
                    <option value="<?php echo esc_attr($status); ?>" <?php selected($order->get_status(), $status); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="kangoo_shipping_carrier"><?php esc_html_e('Carrier', 'kangoo'); ?></label>
            <input id="kangoo_shipping_carrier" name="kangoo_shipping_carrier" type="text" value="<?php echo esc_attr(kangoo_shipping_get_order_meta($order, 'carrier', 'Royal Mail')); ?>" style="width:100%;">
        </p>
        <p>
            <label for="kangoo_shipping_service"><?php esc_html_e('Service', 'kangoo'); ?></label>
            <input id="kangoo_shipping_service" name="kangoo_shipping_service" type="text" value="<?php echo esc_attr(kangoo_shipping_get_order_meta($order, 'service', $order->get_shipping_method())); ?>" style="width:100%;">
        </p>
        <p>
            <label for="kangoo_shipping_tracking_number"><?php esc_html_e('Tracking number', 'kangoo'); ?></label>
            <input id="kangoo_shipping_tracking_number" name="kangoo_shipping_tracking_number" type="text" value="<?php echo esc_attr(kangoo_shipping_get_order_meta($order, 'tracking_number')); ?>" style="width:100%;">
        </p>
        <p>
            <label for="kangoo_shipping_tracking_url"><?php esc_html_e('Tracking URL', 'kangoo'); ?></label>
            <input id="kangoo_shipping_tracking_url" name="kangoo_shipping_tracking_url" type="text" inputmode="url" value="<?php echo esc_attr(kangoo_shipping_display_tracking_url(kangoo_shipping_get_order_meta($order, 'tracking_url'))); ?>" placeholder="<?php esc_attr_e('test.com/tracking', 'kangoo'); ?>" style="width:100%;">
        </p>
        <p>
            <label for="kangoo_shipping_note"><?php esc_html_e('Customer note', 'kangoo'); ?></label>
            <textarea id="kangoo_shipping_note" name="kangoo_shipping_note" rows="3" style="width:100%;"><?php echo esc_textarea(kangoo_shipping_get_order_meta($order, 'note')); ?></textarea>
        </p>
        <p class="description"><?php esc_html_e('Changing the status to Shipped or Delayed sends the customer an email.', 'kangoo'); ?></p>
    </div>
    <?php
}

function kangoo_shipping_admin_menu() {
    add_submenu_page(
        'woocommerce',
        __('Kangoo Shipping', 'kangoo'),
        __('Kangoo Shipping', 'kangoo'),
        'manage_woocommerce',
        'kangoo-shipping',
        'kangoo_render_shipping_admin_page'
    );
}
add_action('admin_menu', 'kangoo_shipping_admin_menu');

function kangoo_shipping_handle_admin_update() {
    if (
        empty($_POST['kangoo_shipping_dashboard_nonce']) ||
        !current_user_can('manage_woocommerce') ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kangoo_shipping_dashboard_nonce'])), 'kangoo_shipping_dashboard')
    ) {
        return;
    }

    $order_id = isset($_POST['kangoo_shipping_order_id']) ? absint($_POST['kangoo_shipping_order_id']) : 0;
    $order = $order_id ? wc_get_order($order_id) : false;

    if (!$order) {
        return;
    }

    $_POST['kangoo_shipping_nonce'] = wp_create_nonce('kangoo_shipping_fields');
    kangoo_shipping_save_order_fields($order);
    $order->save();

    wp_safe_redirect(add_query_arg('kangoo_shipping_updated', $order_id, admin_url('admin.php?page=kangoo-shipping')));
    exit;
}
add_action('admin_init', 'kangoo_shipping_handle_admin_update');

function kangoo_render_shipping_admin_page() {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $orders = function_exists('wc_get_orders') ? wc_get_orders(array(
        'status'  => array('processing', 'packed', 'delayed'),
        'limit'   => 50,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'objects',
    )) : array();
    ?>
    <div class="wrap kangoo-shipping-admin">
        <h1><?php esc_html_e('Kangoo Shipping', 'kangoo'); ?></h1>
        <p><?php esc_html_e('Update packed, shipped, delayed and tracking details for live orders from one screen.', 'kangoo'); ?></p>

        <?php if (!empty($_GET['kangoo_shipping_updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf(__('Order #%d updated.', 'kangoo'), absint($_GET['kangoo_shipping_updated']))); ?></p></div>
        <?php endif; ?>

        <style>
            .kangoo-shipping-grid{display:grid;gap:16px;max-width:1280px}
            .kangoo-shipping-card{display:grid;grid-template-columns:minmax(180px,.7fr) minmax(0,1.3fr);gap:16px;padding:16px;border:1px solid #dcdcde;border-radius:12px;background:#fff}
            .kangoo-shipping-card h2{margin:0 0 8px;font-size:18px}
            .kangoo-shipping-card p{margin:4px 0}
            .kangoo-shipping-form{display:grid;grid-template-columns:repeat(5,minmax(120px,1fr)) auto;gap:10px;align-items:end}
            .kangoo-shipping-form label{display:grid;gap:6px;font-weight:600}
            .kangoo-shipping-form input,.kangoo-shipping-form select{width:100%;height:38px;min-height:38px;box-sizing:border-box;margin:0;padding:0 12px;border:1px solid #8c8f94;border-radius:0;background:#fff;font-size:14px;line-height:38px}
            .kangoo-shipping-form textarea{width:100%;min-height:56px;box-sizing:border-box;margin:0;padding:8px 12px;border:1px solid #8c8f94;background:#fff;font-size:14px}
            .kangoo-shipping-form__note{grid-column:1 / -2}
            @media(max-width:1100px){.kangoo-shipping-card,.kangoo-shipping-form{grid-template-columns:1fr}}
        </style>

        <div class="kangoo-shipping-grid">
            <?php if (!empty($orders)) : ?>
                <?php foreach ($orders as $order) : ?>
                    <section class="kangoo-shipping-card">
                        <div>
                            <h2>
                                <a href="<?php echo esc_url($order->get_edit_order_url()); ?>">
                                    <?php echo esc_html(sprintf(__('Order #%s', 'kangoo'), $order->get_order_number())); ?>
                                </a>
                            </h2>
                            <p><strong><?php esc_html_e('Customer:', 'kangoo'); ?></strong> <?php echo esc_html($order->get_formatted_billing_full_name()); ?></p>
                            <p><strong><?php esc_html_e('Total:', 'kangoo'); ?></strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?></p>
                            <p><strong><?php esc_html_e('Status:', 'kangoo'); ?></strong> <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></p>
                            <p><strong><?php esc_html_e('Method:', 'kangoo'); ?></strong> <?php echo esc_html($order->get_shipping_method()); ?></p>
                        </div>
                        <form class="kangoo-shipping-form" method="post">
                            <?php wp_nonce_field('kangoo_shipping_dashboard', 'kangoo_shipping_dashboard_nonce'); ?>
                            <input type="hidden" name="kangoo_shipping_order_id" value="<?php echo esc_attr($order->get_id()); ?>">
                            <label>
                                <?php esc_html_e('Status', 'kangoo'); ?>
                                <select name="kangoo_shipping_status">
                                    <?php foreach (array('processing' => __('Processing', 'kangoo'), 'packed' => __('Packed', 'kangoo'), 'shipped' => __('Shipped', 'kangoo'), 'delayed' => __('Delayed', 'kangoo')) as $status => $label) : ?>
                                        <option value="<?php echo esc_attr($status); ?>" <?php selected($order->get_status(), $status); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <?php esc_html_e('Carrier', 'kangoo'); ?>
                                <input type="text" name="kangoo_shipping_carrier" value="<?php echo esc_attr(kangoo_shipping_get_order_meta($order, 'carrier', 'Royal Mail')); ?>">
                            </label>
                            <label>
                                <?php esc_html_e('Service', 'kangoo'); ?>
                                <input type="text" name="kangoo_shipping_service" value="<?php echo esc_attr(kangoo_shipping_get_order_meta($order, 'service', $order->get_shipping_method())); ?>">
                            </label>
                            <label>
                                <?php esc_html_e('Tracking', 'kangoo'); ?>
                                <input type="text" name="kangoo_shipping_tracking_number" value="<?php echo esc_attr(kangoo_shipping_get_order_meta($order, 'tracking_number')); ?>">
                            </label>
                            <label>
                                <?php esc_html_e('Tracking URL', 'kangoo'); ?>
                                <input type="text" inputmode="url" name="kangoo_shipping_tracking_url" value="<?php echo esc_attr(kangoo_shipping_display_tracking_url(kangoo_shipping_get_order_meta($order, 'tracking_url'))); ?>" placeholder="<?php esc_attr_e('test.com/tracking', 'kangoo'); ?>">
                            </label>
                            <label class="kangoo-shipping-form__note">
                                <?php esc_html_e('Customer note', 'kangoo'); ?>
                                <textarea name="kangoo_shipping_note" rows="2"><?php echo esc_textarea(kangoo_shipping_get_order_meta($order, 'note')); ?></textarea>
                            </label>
                            <button class="button button-primary" type="submit"><?php esc_html_e('Update', 'kangoo'); ?></button>
                        </form>
                    </section>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="notice notice-info"><p><?php esc_html_e('No processing, packed or delayed orders need shipping updates right now.', 'kangoo'); ?></p></div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function kangoo_shipping_email_body($order, $type) {
    $tracking_number = kangoo_shipping_get_order_meta($order, 'tracking_number');
    $tracking_url = kangoo_shipping_get_tracking_url($order);
    $service = kangoo_shipping_get_order_meta($order, 'service', $order->get_shipping_method());
    $note = kangoo_shipping_get_order_meta($order, 'note');
    $is_delayed = $type === 'delayed';
    $heading = $is_delayed
        ? sprintf(__('Your Kangoo order #%s has a delivery update', 'kangoo'), $order->get_order_number())
        : sprintf(__('Your Kangoo order #%s is now on its way', 'kangoo'), $order->get_order_number());

    ob_start();
    ?>
    <div style="font-family:Arial,sans-serif;color:#101216;">
        <h1 style="margin:0 0 18px;text-align:center;font-size:28px;line-height:1.15;"><?php echo esc_html($heading); ?></h1>
        <p style="text-align:center;font-size:15px;line-height:1.5;">
            <?php if ($is_delayed) : ?>
                <?php esc_html_e('Your order is still being handled, but there has been a delivery update. We will keep things moving and update your tracking as soon as it changes.', 'kangoo'); ?>
            <?php else : ?>
                <?php esc_html_e('Your order has been packed and is now on its way to you. Tracking details may take a little while to appear after dispatch.', 'kangoo'); ?>
            <?php endif; ?>
        </p>
        <div style="margin:24px 0;padding:18px;background:#f6f7f2;text-align:center;">
            <?php if ($tracking_url) : ?>
                <p style="margin:0 0 16px;"><a href="<?php echo esc_url($tracking_url); ?>" style="display:inline-block;padding:13px 24px;background:#ff6f00;color:#fff;text-decoration:none;font-weight:700;border-radius:6px;"><?php esc_html_e('Track order', 'kangoo'); ?></a></p>
            <?php endif; ?>
            <?php if ($tracking_number) : ?>
                <p style="margin:0;font-size:18px;"><strong><?php esc_html_e('Tracking number:', 'kangoo'); ?></strong><br><span style="color:#ff6f00;font-weight:700;"><?php echo esc_html($tracking_number); ?></span></p>
            <?php endif; ?>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:20px;padding:18px;background:#f6f7f2;">
            <div>
                <p style="margin:0 0 4px;font-weight:700;"><?php esc_html_e('Order number:', 'kangoo'); ?></p>
                <p style="margin:0;color:#ff6f00;"><?php echo esc_html($order->get_order_number()); ?></p>
            </div>
            <div>
                <p style="margin:0 0 4px;font-weight:700;"><?php esc_html_e('Shipping method:', 'kangoo'); ?></p>
                <p style="margin:0;color:#ff6f00;"><?php echo esc_html($service); ?></p>
            </div>
            <div>
                <p style="margin:0 0 4px;font-weight:700;"><?php esc_html_e('Delivery address:', 'kangoo'); ?></p>
                <p style="margin:0;"><?php echo wp_kses_post($order->get_formatted_shipping_address() ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address()); ?></p>
            </div>
            <div>
                <p style="margin:0 0 4px;font-weight:700;"><?php esc_html_e('Order date:', 'kangoo'); ?></p>
                <p style="margin:0;color:#ff6f00;"><?php echo esc_html(wc_format_datetime($order->get_date_created(), 'Y-m-d')); ?></p>
            </div>
        </div>
        <?php if ($note) : ?>
            <p style="margin-top:18px;"><?php echo esc_html($note); ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function kangoo_shipping_send_status_email($order_id, $order = null) {
    $order = $order instanceof WC_Order ? $order : wc_get_order($order_id);

    if (!$order || !$order->get_billing_email() || !function_exists('WC')) {
        return;
    }

    $status = $order->get_status();
    $is_delayed = $status === 'delayed';
    $heading = $is_delayed
        ? sprintf(__('Your Kangoo order #%s has a delivery update', 'kangoo'), $order->get_order_number())
        : sprintf(__('Your Kangoo order #%s is now on its way', 'kangoo'), $order->get_order_number());
    $subject = $heading;
    $mailer = WC()->mailer();
    $body = kangoo_shipping_email_body($order, $is_delayed ? 'delayed' : 'shipped');
    $message = $mailer->wrap_message($heading, $body);

    $mailer->send($order->get_billing_email(), $subject, $message, "Content-Type: text/html\r\n");
}
add_action('woocommerce_order_status_shipped', 'kangoo_shipping_send_status_email', 10, 2);
add_action('woocommerce_order_status_delayed', 'kangoo_shipping_send_status_email', 10, 2);

function kangoo_shipping_customer_order_tracking($order) {
    if (!$order instanceof WC_Order) {
        return;
    }

    $tracking_number = kangoo_shipping_get_order_meta($order, 'tracking_number');
    $tracking_url = kangoo_shipping_get_tracking_url($order);
    $service = kangoo_shipping_get_order_meta($order, 'service', $order->get_shipping_method());
    $status = $order->get_status();

    if (!$tracking_number && !in_array($status, array('packed', 'shipped', 'delayed'), true)) {
        return;
    }
    ?>
    <section class="kangoo-order-tracking" style="margin:1.25rem 0;padding:1rem;border:1px solid rgba(77,163,255,.22);border-radius:14px;background:rgba(77,163,255,.06);">
        <h2 style="margin-top:0;"><?php esc_html_e('Delivery tracking', 'kangoo'); ?></h2>
        <p><strong><?php esc_html_e('Status:', 'kangoo'); ?></strong> <?php echo esc_html(wc_get_order_status_name($status)); ?></p>
        <?php if ($service) : ?>
            <p><strong><?php esc_html_e('Shipping method:', 'kangoo'); ?></strong> <?php echo esc_html($service); ?></p>
        <?php endif; ?>
        <?php if ($tracking_number) : ?>
            <p><strong><?php esc_html_e('Tracking number:', 'kangoo'); ?></strong> <?php echo esc_html($tracking_number); ?></p>
        <?php endif; ?>
        <?php if ($tracking_url && $status !== 'completed') : ?>
            <p><a class="button" href="<?php echo esc_url($tracking_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Track order', 'kangoo'); ?></a></p>
        <?php endif; ?>
    </section>
    <?php
}
add_action('woocommerce_order_details_after_order_table', 'kangoo_shipping_customer_order_tracking', 12);
