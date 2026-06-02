<?php
defined('ABSPATH') || exit;

function kangoo_shipping_statuses() {
    return array(
        'dispatched' => __('Dispatched', 'kangoo'),
        'shipped'    => __('Shipped', 'kangoo'),
        'delayed'    => __('Delayed', 'kangoo'),
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

function kangoo_shipping_bulk_order_actions($actions) {
    $actions['mark_dispatched'] = __('Change status to dispatched', 'kangoo');
    $actions['mark_delayed'] = __('Change status to delayed', 'kangoo');

    return $actions;
}
add_filter('bulk_actions-edit-shop_order', 'kangoo_shipping_bulk_order_actions');
add_filter('bulk_actions-woocommerce_page_wc-orders', 'kangoo_shipping_bulk_order_actions');

function kangoo_shipping_paid_order_statuses($statuses) {
    return array_values(array_unique(array_merge((array) $statuses, array('dispatched', 'shipped', 'delayed'))));
}
add_filter('woocommerce_order_is_paid_statuses', 'kangoo_shipping_paid_order_statuses');

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

    if (is_scalar($value) && trim((string) $value) !== '') {
        return (string) $value;
    }

    if ($field === 'tracking_number') {
        return kangoo_shipping_get_tracking_number($order, $default);
    }

    if ($field === 'tracking_url') {
        return kangoo_shipping_get_tracking_url_from_meta($order, $default);
    }

    if ($field === 'service') {
        return kangoo_shipping_get_service_from_tracking_meta($order, $default);
    }

    return $default;
}

function kangoo_shipping_get_first_meta_value($order, $keys) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    foreach ((array) $keys as $key) {
        $value = $order->get_meta($key);

        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }
    }

    return '';
}

function kangoo_shipping_get_shipment_tracking_items($order) {
    if (!$order instanceof WC_Order) {
        return array();
    }

    $items = $order->get_meta('_wc_shipment_tracking_items');

    if (is_string($items) && $items !== '') {
        $decoded = json_decode($items, true);
        $items = is_array($decoded) ? $decoded : maybe_unserialize($items);
    }

    if (!is_array($items)) {
        return array();
    }

    if (isset($items['tracking_number']) || isset($items['tracking_id'])) {
        return array($items);
    }

    return $items;
}

function kangoo_shipping_get_tracking_number($order, $default = '') {
    $tracking_number = kangoo_shipping_get_first_meta_value($order, array(
        '_kangoo_tracking_number',
        '_tracking_number',
        'tracking_number',
        '_royal_mail_tracking_number',
        'royal_mail_tracking_number',
        '_royalmail_tracking_number',
        'royalmail_tracking_number',
    ));

    if ($tracking_number !== '') {
        return $tracking_number;
    }

    foreach (kangoo_shipping_get_shipment_tracking_items($order) as $item) {
        if (!is_array($item)) {
            continue;
        }

        foreach (array('tracking_number', 'tracking_id') as $key) {
            if (!empty($item[$key]) && is_scalar($item[$key])) {
                return trim((string) $item[$key]);
            }
        }
    }

    return $default;
}

function kangoo_shipping_get_tracking_url_from_meta($order, $default = '') {
    $tracking_url = kangoo_shipping_get_first_meta_value($order, array(
        '_kangoo_tracking_url',
        '_tracking_url',
        'tracking_url',
        '_royal_mail_tracking_url',
        'royal_mail_tracking_url',
        '_royalmail_tracking_url',
        'royalmail_tracking_url',
    ));

    if ($tracking_url !== '') {
        return kangoo_shipping_normalize_tracking_url($tracking_url);
    }

    foreach (kangoo_shipping_get_shipment_tracking_items($order) as $item) {
        if (!is_array($item)) {
            continue;
        }

        foreach (array('tracking_url', 'custom_tracking_link', 'formatted_tracking_link') as $key) {
            if (!empty($item[$key]) && is_scalar($item[$key])) {
                return kangoo_shipping_normalize_tracking_url((string) $item[$key]);
            }
        }
    }

    return $default;
}

function kangoo_shipping_get_service_from_tracking_meta($order, $default = '') {
    foreach (kangoo_shipping_get_shipment_tracking_items($order) as $item) {
        if (!is_array($item)) {
            continue;
        }

        foreach (array('shipping_provider', 'tracking_provider', 'custom_tracking_provider', 'carrier_name', 'provider') as $key) {
            if (!empty($item[$key]) && is_scalar($item[$key])) {
                return trim((string) $item[$key]);
            }
        }
    }

    return $default;
}

function kangoo_shipping_default_tracking_url($tracking_number) {
    $tracking_number = trim((string) $tracking_number);

    if ($tracking_number === '') {
        return '';
    }

    return 'https://www.royalmail.com/track-your-item#/tracking-results/' . rawurlencode($tracking_number);
}

function kangoo_shipping_get_tracking_url($order) {
    $custom_url = kangoo_shipping_get_tracking_url_from_meta($order);

    if ($custom_url) {
        return esc_url_raw($custom_url);
    }

    return kangoo_shipping_default_tracking_url(kangoo_shipping_get_tracking_number($order));
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
        'processing'  => __('Processing', 'kangoo'),
        'dispatched' => __('Dispatched', 'kangoo'),
        'delayed'    => __('Delayed', 'kangoo'),
        'completed'  => __('Completed', 'kangoo'),
    );

    if ($order->get_status() === 'shipped') {
        $status_options['shipped'] = __('Shipped', 'kangoo');
    }

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
        <p class="description"><?php esc_html_e('Changing the status to Dispatched or Delayed sends the customer an email.', 'kangoo'); ?></p>
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
        'type'    => 'shop_order',
        'status'  => array('processing', 'delayed'),
        'limit'   => -1,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'objects',
    )) : array();
    ?>
    <div class="wrap kangoo-shipping-admin">
        <h1><?php esc_html_e('Kangoo Shipping', 'kangoo'); ?></h1>
        <p><?php esc_html_e('Update dispatched, delayed and tracking details for live processing orders from one screen.', 'kangoo'); ?></p>

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
                                    <?php foreach (array('processing' => __('Processing', 'kangoo'), 'dispatched' => __('Dispatched', 'kangoo'), 'delayed' => __('Delayed', 'kangoo')) as $status => $label) : ?>
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
                <div class="notice notice-info"><p><?php esc_html_e('No processing or delayed orders need shipping updates right now.', 'kangoo'); ?></p></div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function kangoo_shipping_email_body($order, $type) {
    if (function_exists('kangoo_email_render_shipping_update')) {
        return kangoo_email_render_shipping_update($order, $type);
    }

    return '';
}

function kangoo_shipping_register_email_classes($emails) {
    if (!class_exists('WC_Email')) {
        return $emails;
    }

    if (!class_exists('WC_Email_Kangoo_Order_Status')) {
        abstract class WC_Email_Kangoo_Order_Status extends WC_Email {
            public $kangoo_stage = '';
            public $kangoo_order_statuses = array();

            public function __construct() {
                $this->customer_email = true;

                foreach ((array) $this->kangoo_order_statuses as $status) {
                    add_action('woocommerce_order_status_' . $status, array($this, 'trigger'), 10, 2);
                }

                parent::__construct();
            }

            public function trigger($order_id, $order = false) {
                $this->setup_locale();

                $order = $order instanceof WC_Order ? $order : wc_get_order($order_id);

                if (!$order instanceof WC_Order) {
                    $this->restore_locale();
                    return;
                }

                $this->object = $order;
                $this->recipient = $order->get_billing_email();
                $this->placeholders['{order_number}'] = $order->get_order_number();

                if ($this->is_enabled() && $this->get_recipient()) {
                    $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
                }

                $this->restore_locale();
            }

            public function get_content_html() {
                if (!$this->object instanceof WC_Order) {
                    return '';
                }

                if (!empty($this->template_html) && function_exists('wc_get_template_html')) {
                    return wc_get_template_html(
                        $this->template_html,
                        array(
                            'order'              => $this->object,
                            'email'              => $this,
                            'sent_to_admin'      => false,
                            'plain_text'         => false,
                            'additional_content' => '',
                        ),
                        '',
                        $this->template_base
                    );
                }

                if ($this->kangoo_stage === 'delayed' && function_exists('kangoo_email_render_order_delayed')) {
                    return kangoo_email_render_order_delayed($this->object);
                }

                if (function_exists('kangoo_email_render_order_dispatched')) {
                    return kangoo_email_render_order_dispatched($this->object);
                }

                return '';
            }

            public function get_content_plain() {
                return wp_strip_all_tags($this->get_content_html());
            }

            public function init_form_fields() {
                $placeholder_text = sprintf(
                    /* translators: %s: available placeholder list */
                    __('Available placeholders: %s', 'woocommerce'),
                    '<code>{order_number}</code>'
                );

                $this->form_fields = array(
                    'enabled' => array(
                        'title'   => __('Enable/Disable', 'woocommerce'),
                        'type'    => 'checkbox',
                        'label'   => __('Enable this email notification', 'woocommerce'),
                        'default' => 'yes',
                    ),
                    'subject' => array(
                        'title'       => __('Subject', 'woocommerce'),
                        'type'        => 'text',
                        'description' => $placeholder_text,
                        'placeholder' => $this->get_default_subject(),
                        'default'     => '',
                    ),
                    'heading' => array(
                        'title'       => __('Email heading', 'woocommerce'),
                        'type'        => 'text',
                        'description' => $placeholder_text,
                        'placeholder' => $this->get_default_heading(),
                        'default'     => '',
                    ),
                );
            }
        }
    }

    if (!class_exists('WC_Email_Kangoo_Dispatched_Order')) {
        class WC_Email_Kangoo_Dispatched_Order extends WC_Email_Kangoo_Order_Status {
            public function __construct() {
                $this->id = 'kangoo_dispatched_order';
                $this->title = __('Kangoo order dispatched', 'kangoo');
                $this->description = __('Sent to customers when an order is marked Dispatched or legacy Shipped.', 'kangoo');
                $this->kangoo_stage = 'dispatched';
                $this->kangoo_order_statuses = array('dispatched', 'shipped');
                $this->template_html = 'emails/customer-dispatched-order.php';
                $this->template_base = trailingslashit(get_template_directory()) . 'woocommerce/';

                parent::__construct();
            }

            public function get_default_subject() {
                return __('Your Kangoo Pouches order #{order_number} is on its way', 'kangoo');
            }

            public function get_default_heading() {
                return __('Your order is on its way', 'kangoo');
            }
        }
    }

    if (!class_exists('WC_Email_Kangoo_Delayed_Order')) {
        class WC_Email_Kangoo_Delayed_Order extends WC_Email_Kangoo_Order_Status {
            public function __construct() {
                $this->id = 'kangoo_delayed_order';
                $this->title = __('Kangoo order delayed', 'kangoo');
                $this->description = __('Sent to customers when an order is manually marked Delayed.', 'kangoo');
                $this->kangoo_stage = 'delayed';
                $this->kangoo_order_statuses = array('delayed');
                $this->template_html = 'emails/customer-delayed-order.php';
                $this->template_base = trailingslashit(get_template_directory()) . 'woocommerce/';

                parent::__construct();
            }

            public function get_default_subject() {
                return __('Update on your Kangoo Pouches order #{order_number}', 'kangoo');
            }

            public function get_default_heading() {
                return __('Your order has been delayed', 'kangoo');
            }
        }
    }

    $emails['WC_Email_Kangoo_Dispatched_Order'] = new WC_Email_Kangoo_Dispatched_Order();
    $emails['WC_Email_Kangoo_Delayed_Order'] = new WC_Email_Kangoo_Delayed_Order();

    return $emails;
}
add_filter('woocommerce_email_classes', 'kangoo_shipping_register_email_classes');

function kangoo_shipping_send_status_email($order_id, $order = null) {
    return;
}

function kangoo_shipping_customer_order_tracking($order) {
    if (!$order instanceof WC_Order) {
        return;
    }

    $tracking_number = kangoo_shipping_get_order_meta($order, 'tracking_number');
    $tracking_url = kangoo_shipping_get_tracking_url($order);
    $service = kangoo_shipping_get_order_meta($order, 'service', $order->get_shipping_method());
    $status = $order->get_status();

    if (!$tracking_number && !in_array($status, array('dispatched', 'shipped', 'delayed'), true)) {
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
