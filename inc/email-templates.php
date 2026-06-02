<?php
defined('ABSPATH') || exit;

function kangoo_email_theme_logo_url() {
    return esc_url(apply_filters(
        'kangoo_email_logo_url',
        'https://kangoopouches.co.uk/wp-content/themes/kangoo-theme/assets/images/kangoo-logo-black.png'
    ));
}

function kangoo_email_asset_url($filename) {
    return esc_url(get_theme_file_uri('/assets/images/email/' . ltrim((string) $filename, '/')));
}

function kangoo_email_customer_first_name($order) {
    if (!$order instanceof WC_Order) {
        return __('there', 'kangoo');
    }

    $name = trim((string) $order->get_billing_first_name());

    return $name !== '' ? $name : __('there', 'kangoo');
}

function kangoo_email_order_date($order) {
    if (!$order instanceof WC_Order || !$order->get_date_created()) {
        return '';
    }

    return wc_format_datetime($order->get_date_created(), 'j F Y');
}

function kangoo_email_shop_url() {
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';

    return $shop_url ? $shop_url : home_url('/shop/');
}

function kangoo_email_rewards_url() {
    return function_exists('wc_get_account_endpoint_url')
        ? wc_get_account_endpoint_url('kangoo-rewards')
        : home_url('/my-account/kangoo-rewards/');
}

function kangoo_email_discount_code($order) {
    return (string) apply_filters('kangoo_email_next_order_code', 'THANKYOU10', $order);
}

function kangoo_email_tracking_number_for_order($order) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    if (function_exists('kangoo_shipping_get_tracking_number')) {
        return kangoo_shipping_get_tracking_number($order);
    }

    $keys = array('_kangoo_tracking_number', '_tracking_number', 'tracking_number');

    foreach ($keys as $key) {
        $value = trim((string) $order->get_meta($key));

        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function kangoo_email_tracking_url_for_order($order) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    if (function_exists('kangoo_shipping_get_tracking_url')) {
        return kangoo_shipping_get_tracking_url($order);
    }

    $tracking_number = kangoo_email_tracking_number_for_order($order);

    if ($tracking_number === '') {
        return '';
    }

    return 'https://www.royalmail.com/track-your-item#/tracking-results/' . rawurlencode($tracking_number);
}

function kangoo_email_shipping_service_for_order($order) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    if (function_exists('kangoo_shipping_get_order_meta')) {
        $service = kangoo_shipping_get_order_meta($order, 'service', '');

        if ($service !== '') {
            return $service;
        }
    }

    return $order->get_shipping_method();
}

function kangoo_email_estimated_delivery_for_order($order) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    $keys = array(
        '_estimated_delivery_date',
        'estimated_delivery_date',
        '_delivery_estimate',
        'delivery_estimate',
        '_kangoo_estimated_delivery_date',
        '_kangoo_delivery_estimate',
    );

    foreach ($keys as $key) {
        $value = trim((string) $order->get_meta($key));

        if ($value === '') {
            continue;
        }

        $timestamp = strtotime($value);

        if ($timestamp) {
            return date_i18n('l', $timestamp) . '<br>' . date_i18n('j F Y', $timestamp);
        }

        return esc_html($value);
    }

    return '';
}

function kangoo_email_delivery_address($order) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    $address = $order->get_formatted_shipping_address();

    if (!$address) {
        $address = $order->get_formatted_billing_address();
    }

    return $address ? wp_kses_post($address) : esc_html__('Address unavailable', 'kangoo');
}

function kangoo_email_rewards_points_for_order($order) {
    if (!$order instanceof WC_Order) {
        return 0;
    }

    $meta_keys = array(
        '_kangoo_rewards_points_awarded',
        '_kangoo_rewards_points_earned',
        '_rewards_points_earned',
        'reward_points',
    );

    foreach ($meta_keys as $key) {
        $points = (int) $order->get_meta($key);

        if ($points > 0) {
            return $points;
        }
    }

    $eligible_total = max(0, (float) $order->get_subtotal() - (float) $order->get_discount_total());
    $points_per_pound = function_exists('kangoo_rewards_points_per_pound') ? (float) kangoo_rewards_points_per_pound() : 10;
    $points = (int) floor($eligible_total * max(0, $points_per_pound));

    return (int) apply_filters('kangoo_email_reward_points', $points, $order);
}

function kangoo_email_rewards_balance_for_order($order, $fallback_points = 0) {
    if (!$order instanceof WC_Order) {
        return (int) $fallback_points;
    }

    $user_id = function_exists('kangoo_rewards_get_order_user_id')
        ? (int) kangoo_rewards_get_order_user_id($order)
        : (int) $order->get_user_id();

    if ($user_id && function_exists('kangoo_rewards_get_balance')) {
        $balance = (int) kangoo_rewards_get_balance($user_id);

        if ($balance > 0) {
            return $balance;
        }
    }

    return (int) $fallback_points;
}

function kangoo_email_icon_circle($filename, $size = 58, $icon_size = 30, $background = '#fff1e7') {
    return sprintf(
        '<span style="display:inline-block;width:%1$dpx;height:%1$dpx;border-radius:999px;background:%3$s;text-align:center;"><span style="display:table;width:%1$dpx;height:%1$dpx;"><span style="display:table-cell;width:%1$dpx;height:%1$dpx;text-align:center;vertical-align:middle;"><img src="%4$s" width="%2$d" alt="" style="display:inline-block;width:%2$dpx;height:%2$dpx;border:0;margin:0 auto;vertical-align:middle;"></span></span></span>',
        (int) $size,
        (int) $icon_size,
        esc_attr($background),
        kangoo_email_asset_url($filename)
    );
}

function kangoo_email_button($url, $label, $background = '#ff5a00', $colour = '#ffffff', $border = '#ff5a00', $icon = '') {
    if (!$url) {
        return '';
    }

    $icon_html = '';

    if ($icon !== '') {
        $icon_html = '<img src="' . kangoo_email_asset_url($icon) . '" width="18" alt="" style="display:inline-block;width:18px;height:18px;margin-left:8px;border:0;vertical-align:-3px;">';
    }

    return sprintf(
        '<a class="kg-btn" href="%1$s" style="box-sizing:border-box;display:inline-block;min-width:150px;max-width:100%%;padding:15px 22px;border:1px solid %5$s;border-radius:8px;background:%3$s;color:%4$s !important;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:900;line-height:1;text-align:center;text-decoration:none;text-transform:uppercase;white-space:nowrap;">%2$s%6$s</a>',
        esc_url($url),
        esc_html($label),
        esc_attr($background),
        esc_attr($colour),
        esc_attr($border),
        $icon_html
    );
}

function kangoo_email_order_item_rows($order) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    ob_start();

    foreach ($order->get_items() as $item) {
        if (!$item instanceof WC_Order_Item_Product) {
            continue;
        }

        $product = $item->get_product();
        $image_url = '';
        $image_alt = $item->get_name();

        if ($product) {
            $image_id = (int) $product->get_image_id();

            if (!$image_id && $product->is_type('variation')) {
                $parent_product = wc_get_product($product->get_parent_id());
                $image_id = $parent_product ? (int) $parent_product->get_image_id() : 0;
            }

            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : wc_placeholder_img_src('woocommerce_thumbnail');
            $image_alt = $image_id ? (string) get_post_meta($image_id, '_wp_attachment_image_alt', true) : $image_alt;
            $image_alt = $image_alt !== '' ? $image_alt : $item->get_name();
        }
        ?>
        <tr>
            <td class="kg-product-image" width="104" style="width:104px;padding:12px 20px 14px 0;vertical-align:top;">
                <?php if ($image_url) : ?>
                    <img class="kg-product-img" src="<?php echo esc_url($image_url); ?>" width="92" alt="<?php echo esc_attr($image_alt); ?>" style="display:block;width:92px;max-width:92px;height:auto;border:0;border-radius:8px;background:#ffffff;">
                <?php endif; ?>
            </td>
            <td style="padding:12px 0 14px;vertical-align:top;">
                <strong class="kg-product-name" style="display:block;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:1.3;font-weight:900;"><?php echo esc_html($item->get_name()); ?></strong>
                <span style="display:block;margin-top:10px;color:#5b616b;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.25;"><?php echo esc_html(sprintf(__('Qty: %d', 'kangoo'), $item->get_quantity())); ?></span>
                <strong style="display:block;margin-top:12px;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.25;"><?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?></strong>
            </td>
        </tr>
        <?php
    }

    return ob_get_clean();
}

function kangoo_email_totals_rows($order) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    $currency = $order->get_currency();
    $shipping_label = $order->get_shipping_method();
    $shipping_total = (float) $order->get_shipping_total() > 0
        ? wc_price($order->get_shipping_total(), array('currency' => $currency))
        : __('Free', 'kangoo');

    ob_start();
    ?>
    <tr>
        <td class="kg-total-label" style="padding:13px 0 6px;border-top:1px solid #e5e7eb;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.3;white-space:nowrap;"><?php esc_html_e('Subtotal', 'kangoo'); ?></td>
        <td class="kg-total-value" width="92" align="right" style="width:92px;padding:13px 0 6px;border-top:1px solid #e5e7eb;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.3;text-align:right;white-space:nowrap;"><?php echo wp_kses_post(wc_price($order->get_subtotal(), array('currency' => $currency))); ?></td>
    </tr>
    <tr>
        <td class="kg-total-label" style="padding:6px 0 13px;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.3;white-space:nowrap;"><?php echo esc_html($shipping_label ? sprintf(__('Shipping (%s)', 'kangoo'), $shipping_label) : __('Shipping', 'kangoo')); ?></td>
        <td class="kg-total-value" width="92" align="right" style="width:92px;padding:6px 0 13px;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.3;text-align:right;white-space:nowrap;"><?php echo wp_kses_post($shipping_total); ?></td>
    </tr>
    <tr>
        <td style="padding:18px 0 0;border-top:1px solid #e5e7eb;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:24px;font-weight:900;line-height:1.2;"><?php esc_html_e('Total', 'kangoo'); ?></td>
        <td width="170" align="right" style="width:170px;padding:18px 0 0;border-top:1px solid #e5e7eb;color:#ff5a00;font-family:Arial,Helvetica,sans-serif;font-size:24px;font-weight:900;line-height:1.2;text-align:right;white-space:nowrap;"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
    </tr>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_order_summary_card($order) {
    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border:1px solid #e0e3e8;border-radius:14px;background:#ffffff;">
        <tr>
            <td class="kg-card-pad" style="padding:24px;">
                <strong style="display:block;margin:0 0 2px;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:20px;line-height:1.25;font-weight:900;"><?php esc_html_e('Order Summary', 'kangoo'); ?></strong>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <?php echo kangoo_email_order_item_rows($order); ?>
                    <?php echo kangoo_email_totals_rows($order); ?>
                </table>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_progress($stage) {
    $steps = array(
        'received' => array(
            'label' => __('Order', 'kangoo') . '<br>' . __('Received', 'kangoo'),
            'icon'  => 'order-received.png',
        ),
        'middle' => array(
            'label' => $stage === 'delayed' ? __('Delayed', 'kangoo') : __('Dispatched', 'kangoo'),
            'icon'  => 'order-dispatched.png',
        ),
        'completed' => array(
            'label' => __('Completed', 'kangoo'),
            'icon'  => 'order-completed.png',
        ),
    );

    $state = array(
        'received' => array(true, false, false),
        'dispatched' => array(true, true, false),
        'delayed' => array(true, true, false),
        'completed' => array(true, true, true),
    );

    $current = isset($state[$stage]) ? $state[$stage] : $state['received'];
    $active_index = array('received' => 0, 'dispatched' => 1, 'delayed' => 1, 'completed' => 2);
    $active_index = isset($active_index[$stage]) ? $active_index[$stage] : 0;
    $keys = array_keys($steps);

    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:18px 0 0;border:1px solid #e0e3e8;border-radius:14px;background:#ffffff;">
        <tr>
            <td class="kg-progress-pad" style="padding:20px 20px 18px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <?php foreach ($keys as $index => $key) : ?>
                            <?php
                            $done = !empty($current[$index]);
                            $active = $index === $active_index;
                            $circle = $done ? '#ff5a00' : '#ffffff';
                            $circle_border = $done ? '#ff5a00' : '#cfd3d8';
                            $left_line = $index === 0 ? '#ffffff' : (!empty($current[$index - 1]) && $done ? '#ff5a00' : '#d7d9de');
                            $right_line = $index === count($keys) - 1 ? '#ffffff' : (!empty($current[$index + 1]) ? '#ff5a00' : '#d7d9de');
                            $mark = $stage === 'delayed' && $index === 1 ? '!' : '&#10003;';
                            ?>
                            <td width="33.33%" align="center" style="width:33.33%;vertical-align:top;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td width="50%" style="vertical-align:middle;"><div style="height:0;border-top:4px solid <?php echo esc_attr($left_line); ?>;font-size:0;line-height:0;">&nbsp;</div></td>
                                        <td width="44" align="center" style="width:44px;vertical-align:middle;text-align:center;">
                                            <span class="kg-progress-circle" style="display:inline-block;width:38px;height:38px;border:3px solid <?php echo esc_attr($circle_border); ?>;border-radius:999px;background:<?php echo esc_attr($circle); ?>;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:21px;font-weight:900;line-height:38px;text-align:center;"><?php echo $done ? wp_kses_post($mark) : '&nbsp;'; ?></span>
                                        </td>
                                        <td width="50%" style="vertical-align:middle;"><div style="height:0;border-top:4px solid <?php echo esc_attr($right_line); ?>;font-size:0;line-height:0;">&nbsp;</div></td>
                                    </tr>
                                </table>
                                <div style="margin:8px 0 4px;text-align:center;">
                                    <img src="<?php echo kangoo_email_asset_url($steps[$key]['icon']); ?>" width="22" alt="" style="display:block;width:22px;height:22px;border:0;margin:0 auto;">
                                </div>
                                <div class="kg-progress-label" style="color:<?php echo esc_attr($active ? '#ff5a00' : ($done ? '#111827' : '#6f7680')); ?>;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:<?php echo esc_attr($active ? '900' : '800'); ?>;line-height:1.15;text-align:center;"><?php echo wp_kses_post($steps[$key]['label']); ?></div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_order_strip($order, $button_label = null) {
    $button_label = $button_label ?: __('View order', 'kangoo');

    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:14px 0 0;border-radius:14px;background:#fff7ef;">
        <tr>
            <td width="72" class="kg-strip-icon" style="width:72px;padding:16px 0 16px 20px;vertical-align:middle;">
                <?php echo kangoo_email_icon_circle('order.png', 50, 28); ?>
            </td>
            <td class="kg-strip-copy" style="padding:16px 14px;vertical-align:middle;">
                <strong style="display:block;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:1.25;font-weight:900;"><?php echo esc_html(sprintf(__('Order #%s', 'kangoo'), $order->get_order_number())); ?></strong>
                <span style="display:block;margin-top:4px;color:#5b616b;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.3;"><?php echo esc_html(kangoo_email_order_date($order)); ?></span>
            </td>
            <td width="184" class="kg-strip-action" align="right" style="width:184px;padding:16px 20px 16px 0;text-align:right;vertical-align:middle;">
                <?php echo kangoo_email_button($order->get_view_order_url(), $button_label, '#ffffff', '#ff5a00', '#ff5a00'); ?>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_support_block() {
    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border:1px solid #e0e3e8;border-radius:14px;background:#ffffff;">
        <tr>
            <td class="kg-card-pad" style="padding:22px 24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td width="74" class="kg-support-icon" style="width:74px;padding-right:18px;vertical-align:middle;">
                            <?php echo kangoo_email_icon_circle('customer-service.png', 62, 34); ?>
                        </td>
                        <td style="vertical-align:middle;">
                            <strong style="display:block;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:19px;line-height:1.25;font-weight:900;"><?php esc_html_e("Need help? We're here for you.", 'kangoo'); ?></strong>
                            <a href="mailto:hello@kangoopouches.co.uk" style="display:block;margin-top:6px;color:#ff5a00 !important;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:1.3;text-decoration:none;">hello@kangoopouches.co.uk</a>
                            <span style="display:block;margin-top:5px;color:#5b616b;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:1.3;">kangoopouches.co.uk</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_trust_row() {
    $items = array(
        array('verification-shield.png', __('100% Authentic Products', 'kangoo')),
        array('order-dispatched.png', __('Fast & Discreet Delivery', 'kangoo')),
        array('order.png', __('Secure Payments', 'kangoo')),
    );

    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border-radius:12px;background:#f7f7f7;">
        <tr>
            <?php foreach ($items as $index => $item) : ?>
                <td class="kg-trust-cell" width="33.33%" style="width:33.33%;padding:18px 16px;border-left:<?php echo $index === 0 ? '0' : '1px solid #e2e5e9'; ?>;vertical-align:middle;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <td width="36" style="width:36px;padding-right:10px;vertical-align:middle;">
                                <img src="<?php echo kangoo_email_asset_url($item[0]); ?>" width="28" alt="" style="display:block;width:28px;height:28px;border:0;">
                            </td>
                            <td style="color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;line-height:1.2;vertical-align:middle;"><?php echo esc_html($item[1]); ?></td>
                        </tr>
                    </table>
                </td>
            <?php endforeach; ?>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_tracking_card($order) {
    $tracking_number = kangoo_email_tracking_number_for_order($order);
    $tracking_url = kangoo_email_tracking_url_for_order($order);
    $shipping_service = kangoo_email_shipping_service_for_order($order);
    $shipping_service = $shipping_service !== '' ? $shipping_service : __('Royal Mail Tracked 48', 'kangoo');
    $action_url = $tracking_url ?: $order->get_view_order_url();

    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border:1px solid #e0e3e8;border-radius:14px;background:#ffffff;">
        <tr>
            <td class="kg-card-pad" style="padding:24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td width="86" class="kg-card-icon" style="width:86px;padding-right:20px;vertical-align:middle;">
                            <?php echo kangoo_email_icon_circle('location.png', 66, 36); ?>
                        </td>
                        <td style="vertical-align:middle;">
                            <strong style="display:block;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:20px;line-height:1.25;font-weight:900;"><?php esc_html_e('Tracking Number', 'kangoo'); ?></strong>
                            <?php if ($tracking_number !== '') : ?>
                                <span class="kg-tracking-number" style="display:inline-block;margin-top:8px;color:#ff5a00;font-family:Arial,Helvetica,sans-serif;font-size:24px;line-height:1.15;font-weight:900;"><?php echo esc_html($tracking_number); ?></span>
                            <?php else : ?>
                                <span class="kg-tracking-number" style="display:inline-block;margin-top:8px;color:#ff5a00;font-family:Arial,Helvetica,sans-serif;font-size:22px;line-height:1.15;font-weight:900;"><?php esc_html_e('Tracking pending', 'kangoo'); ?></span>
                            <?php endif; ?>
                            <span style="display:block;margin-top:8px;color:#5b616b;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:1.3;"><?php echo esc_html($shipping_service); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top:20px;text-align:center;">
                            <?php echo kangoo_email_button($action_url, __('Track parcel', 'kangoo'), '#ffffff', '#ff5a00', '#ff5a00', 'maximize.png'); ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_delivery_address_card($order) {
    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border:1px solid #e0e3e8;border-radius:14px;background:#ffffff;">
        <tr>
            <td class="kg-card-pad" style="padding:24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td width="86" class="kg-card-icon" style="width:86px;padding-right:20px;vertical-align:top;">
                            <?php echo kangoo_email_icon_circle('location.png', 66, 36); ?>
                        </td>
                        <td style="vertical-align:top;">
                            <strong style="display:block;margin-bottom:10px;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:20px;line-height:1.25;font-weight:900;"><?php esc_html_e('Delivery Address', 'kangoo'); ?></strong>
                            <div style="color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.45;"><?php echo kangoo_email_delivery_address($order); ?></div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_estimated_delivery_card($order, $compact = false) {
    $estimate = kangoo_email_estimated_delivery_for_order($order);

    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td width="70" style="width:70px;padding-right:18px;vertical-align:middle;">
                <?php echo kangoo_email_icon_circle('order-dispatched.png', 58, 30); ?>
            </td>
            <td style="vertical-align:middle;">
                <strong style="display:block;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:1.25;font-weight:900;"><?php esc_html_e('New estimated delivery', 'kangoo'); ?></strong>
                <?php if ($estimate !== '') : ?>
                    <strong style="display:block;margin-top:10px;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:19px;line-height:1.3;font-weight:900;"><?php echo wp_kses_post($estimate); ?></strong>
                    <span style="display:block;margin-top:6px;color:#5b616b;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.3;"><?php esc_html_e('Before 8:00pm', 'kangoo'); ?></span>
                <?php else : ?>
                    <span style="display:block;margin-top:10px;color:#5b616b;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.4;"><?php echo $compact ? esc_html__('Tracking information may take a few hours to appear after dispatch.', 'kangoo') : esc_html__("We'll update you as soon as we have a confirmed delivery estimate.", 'kangoo'); ?></span>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_dispatched_delivery_card($order) {
    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border:1px solid #e0e3e8;border-radius:14px;background:#ffffff;">
        <tr>
            <td class="kg-card-pad" style="padding:24px;">
                <?php echo kangoo_email_render_estimated_delivery_card($order, true); ?>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_delay_info_card($order) {
    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border-radius:14px;background:#fff7ef;">
        <tr>
            <td class="kg-card-pad" style="padding:24px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr class="kg-stack-row">
                        <td class="kg-stack-cell" width="58%" style="width:58%;padding-right:22px;border-right:1px solid #eadfd7;vertical-align:top;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="76" style="width:76px;padding-right:18px;vertical-align:top;">
                                        <span style="display:inline-block;width:62px;height:62px;border:3px solid #ff5a00;border-radius:999px;color:#ff5a00;font-family:Arial,Helvetica,sans-serif;font-size:34px;font-weight:400;line-height:62px;text-align:center;">!</span>
                                    </td>
                                    <td style="vertical-align:top;">
                                        <strong style="display:block;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:1.25;font-weight:900;"><?php esc_html_e("What's happening?", 'kangoo'); ?></strong>
                                        <span style="display:block;margin-top:6px;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.35;"><?php esc_html_e("There has been an unexpected delay with your order. We're working hard to get it back on track. Thank you for your patience.", 'kangoo'); ?></span>
                                        <span style="display:block;margin-top:14px;padding:9px 12px;border-radius:8px;background:#ffe4d5;color:#3d2720;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.3;"><?php esc_html_e("If anything changes, we'll let you know straight away.", 'kangoo'); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="kg-stack-cell kg-estimate-cell" width="42%" style="width:42%;padding-left:22px;vertical-align:top;">
                            <?php echo kangoo_email_render_estimated_delivery_card($order); ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_pending_rewards_card($order) {
    $points = kangoo_email_rewards_points_for_order($order);

    if ($points <= 0) {
        return '';
    }

    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border-radius:14px;background:#fff7ef;">
        <tr>
            <td width="72" class="kg-card-icon" style="width:72px;padding:18px 0 10px 22px;vertical-align:middle;">
                <?php echo kangoo_email_icon_circle('giftbox.png', 54, 30, '#ffffff'); ?>
            </td>
            <td style="padding:18px 22px 10px 16px;vertical-align:middle;">
                <strong style="display:block;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.25;font-weight:900;"><?php echo esc_html(sprintf(__("You'll earn %d points from this order.", 'kangoo'), $points)); ?></strong>
                <span style="display:block;margin-top:5px;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.3;"><?php esc_html_e('Points will be added when your order is completed.', 'kangoo'); ?></span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding:6px 22px 20px;text-align:center;">
                <?php echo kangoo_email_button(kangoo_email_rewards_url(), __('View rewards', 'kangoo'), '#ffffff', '#ff5a00', '#ff5a00'); ?>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_earned_rewards_card($order) {
    $points = kangoo_email_rewards_points_for_order($order);
    $balance = kangoo_email_rewards_balance_for_order($order, $points);

    if ($points <= 0 && $balance <= 0) {
        return '';
    }

    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border-radius:14px;background:#ff5a00;">
        <tr>
            <td width="78" class="kg-card-icon" style="width:78px;padding:20px 0 8px 24px;vertical-align:middle;">
                <?php echo kangoo_email_icon_circle('giftbox.png', 56, 32, '#ffffff'); ?>
            </td>
            <td style="padding:20px 24px 8px 16px;vertical-align:middle;">
                <strong style="display:block;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:1.2;font-weight:900;"><?php esc_html_e('Kangoo Rewards', 'kangoo'); ?></strong>
                <span style="display:block;margin-top:6px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.3;font-weight:800;"><?php echo esc_html(sprintf(__('You earned %d points. Balance: %d points.', 'kangoo'), $points, $balance)); ?></span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding:8px 24px 22px;text-align:center;">
                <?php echo kangoo_email_button(kangoo_email_rewards_url(), __('View rewards', 'kangoo'), '#ffffff', '#ff5a00', '#ffffff'); ?>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_offer_card($order) {
    $discount_code = kangoo_email_discount_code($order);

    if ($discount_code === '') {
        return '';
    }

    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border-radius:14px;background:#fff7ef;">
        <tr>
            <td width="78" class="kg-card-icon" style="width:78px;padding:20px 0 8px 24px;vertical-align:middle;">
                <?php echo kangoo_email_icon_circle('order-completed.png', 56, 30, '#ffffff'); ?>
            </td>
            <td style="padding:20px 24px 8px 16px;vertical-align:middle;">
                <strong style="display:block;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:1.2;font-weight:900;"><?php esc_html_e('Thank you for your order!', 'kangoo'); ?></strong>
                <span style="display:block;margin-top:7px;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.35;"><?php esc_html_e('Use code', 'kangoo'); ?> <span style="display:inline-block;padding:3px 7px;border-radius:5px;background:#ff5a00;color:#ffffff;font-weight:900;white-space:nowrap;"><?php echo esc_html($discount_code); ?></span> <?php esc_html_e('for 10% off your next order.', 'kangoo'); ?></span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding:8px 24px 22px;text-align:center;">
                <?php echo kangoo_email_button(kangoo_email_shop_url(), __('Shop again', 'kangoo')); ?>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_keep_updated_card() {
    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;border-radius:14px;background:#fff7ef;">
        <tr>
            <td width="86" class="kg-card-icon" style="width:86px;padding:20px 0 20px 24px;vertical-align:middle;">
                <?php echo kangoo_email_icon_circle('verification-shield.png', 58, 32, '#ffffff'); ?>
            </td>
            <td style="padding:20px 24px 20px 18px;vertical-align:middle;">
                <strong style="display:block;color:#06080d;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:1.25;font-weight:900;"><?php esc_html_e("We'll keep you updated!", 'kangoo'); ?></strong>
                <span style="display:block;margin-top:4px;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.35;"><?php esc_html_e("You'll receive another email when your order has been delivered.", 'kangoo'); ?></span>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_document($order, $stage, $additional_content = '') {
    if (!$order instanceof WC_Order) {
        return '';
    }

    $first_name = kangoo_email_customer_first_name($order);
    $order_number = $order->get_order_number();
    $view_order_url = $order->get_view_order_url();
    $tracking_url = kangoo_email_tracking_url_for_order($order);

    $variants = array(
        'received' => array(
            'title' => sprintf(__('Thanks, %s!', 'kangoo'), $first_name),
            'title_orange' => __("We've received your order!", 'kangoo'),
            'intro' => sprintf(__('Great news, your order #%s has been received and we are getting it ready.', 'kangoo'), $order_number),
            'body' => __("We'll let you know as soon as it's on the way to you.", 'kangoo'),
            'image' => 'order-received-right-img.png',
            'cta' => __('View order', 'kangoo'),
            'cta_url' => $view_order_url,
            'cta_icon' => 'order.png',
        ),
        'dispatched' => array(
            'title' => __('Your order is', 'kangoo'),
            'title_orange' => __('on its way!', 'kangoo'),
            'intro' => sprintf(__('Great news, %s.', 'kangoo'), $first_name),
            'body' => sprintf(__('Your order #%s has been packed and is now on its way to you.', 'kangoo'), $order_number),
            'image' => 'order-dispatched-right-img.png',
            'cta' => __('Track my order', 'kangoo'),
            'cta_url' => $tracking_url ?: $view_order_url,
            'cta_icon' => 'order-dispatched.png',
        ),
        'delayed' => array(
            'title' => __('Your order is', 'kangoo'),
            'title_orange' => __('delayed', 'kangoo'),
            'intro' => sprintf(__('Hi %s,', 'kangoo'), $first_name),
            'body' => __("We're really sorry, but there's been a delay with your order.<br><br>We're working hard to get it to you as soon as possible and will keep you updated.", 'kangoo'),
            'image' => 'kp-delay.png',
            'cta' => __('View order', 'kangoo'),
            'cta_url' => $view_order_url,
            'cta_icon' => 'order.png',
        ),
        'completed' => array(
            'title' => __('Your order is', 'kangoo'),
            'title_orange' => __('complete!', 'kangoo'),
            'intro' => __('Thanks for choosing Kangoo Pouches.', 'kangoo'),
            'body' => __("We hope you're enjoying your order.<br>Your order has now been completed and we're grateful for your support.", 'kangoo'),
            'image' => 'order-completed-right-img.png',
            'cta' => __('Shop again', 'kangoo'),
            'cta_url' => kangoo_email_shop_url(),
            'cta_icon' => 'order.png',
        ),
    );

    $variant = isset($variants[$stage]) ? $variants[$stage] : $variants['received'];

    ob_start();
    ?>
    <!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html(sprintf(__('Order #%s', 'kangoo'), $order_number)); ?></title>
        <style>
            @media only screen and (max-width: 620px) {
                .kg-body { padding: 10px 6px !important; }
                .kg-wrap { width: 100% !important; max-width: 100% !important; border-radius: 14px !important; }
                .kg-header { padding: 18px 14px 14px !important; }
                .kg-logo { width: 300px !important; max-width: 88% !important; }
                .kg-inner { padding: 24px 20px 20px !important; }
                .kg-hero-copy { width: 54% !important; padding-right: 10px !important; vertical-align: top !important; }
                .kg-hero-image { width: 46% !important; padding-left: 4px !important; vertical-align: middle !important; }
                .kg-hero-img { width: 190px !important; max-width: 100% !important; }
                .kg-hero-title { font-size: 34px !important; line-height: 1.06 !important; }
                .kg-hero-title--received { margin-bottom: 8px !important; font-size: 32px !important; line-height: 1.08 !important; white-space: nowrap !important; }
                .kg-hero-orange--received { font-size: 28px !important; line-height: 1.06 !important; }
                .kg-hero-text { font-size: 17px !important; line-height: 1.45 !important; }
                .kg-primary-action .kg-btn { display: block !important; width: 100% !important; min-width: 0 !important; padding-left: 12px !important; padding-right: 12px !important; }
                .kg-progress-pad { padding: 18px 10px 16px !important; }
                .kg-progress-circle { width: 34px !important; height: 34px !important; line-height: 34px !important; }
                .kg-progress-label { font-size: 12px !important; }
                .kg-card-pad { padding: 18px !important; }
                .kg-product-image { width: 82px !important; padding-right: 14px !important; }
                .kg-product-img { width: 72px !important; max-width: 72px !important; }
                .kg-product-name { font-size: 16px !important; }
                .kg-total-label, .kg-total-value { font-size: 13px !important; }
                .kg-strip-icon, .kg-card-icon, .kg-support-icon { width: 68px !important; padding-left: 18px !important; padding-right: 12px !important; }
                .kg-strip-copy { padding-left: 10px !important; padding-right: 8px !important; }
                .kg-strip-action { display: block !important; width: auto !important; padding: 0 18px 18px !important; text-align: center !important; }
                .kg-strip-action .kg-btn { display: block !important; width: 100% !important; min-width: 0 !important; }
                .kg-stack-cell { display: block !important; width: 100% !important; box-sizing: border-box !important; padding-left: 0 !important; padding-right: 0 !important; border-right: 0 !important; }
                .kg-estimate-cell { padding-top: 18px !important; }
                .kg-trust-cell { display: block !important; width: 100% !important; box-sizing: border-box !important; border-left: 0 !important; border-top: 1px solid #e2e5e9 !important; }
                .kg-trust-cell:first-child { border-top: 0 !important; }
                .kg-tracking-number { font-size: 20px !important; word-break: break-word !important; }
                .kg-btn { white-space: nowrap !important; }
            }
            @media only screen and (max-width: 390px) {
                .kg-inner { padding: 22px 16px 18px !important; }
                .kg-hero-title { font-size: 29px !important; }
                .kg-hero-title--received { font-size: 28px !important; }
                .kg-hero-orange--received { font-size: 24px !important; }
                .kg-hero-text { font-size: 15px !important; }
                .kg-hero-img { width: 150px !important; }
                .kg-logo { width: 260px !important; }
                .kg-card-pad { padding: 16px !important; }
            }
        </style>
    </head>
    <body style="margin:0;padding:0;background:#f4f6f8;color:#06080d;font-family:Arial,Helvetica,sans-serif;">
        <table role="presentation" class="kg-body" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0;padding:22px 10px;background:#f4f6f8;">
            <tr>
                <td align="center">
                    <table role="presentation" class="kg-wrap" width="680" cellspacing="0" cellpadding="0" border="0" style="width:680px;max-width:680px;border:1px solid #dfe3e8;border-radius:16px;background:#ffffff;box-shadow:0 18px 50px rgba(15,23,42,0.08);overflow:hidden;">
                        <tr>
                            <td class="kg-header" align="center" style="padding:25px 24px 20px;border-bottom:4px solid #ff5a00;">
                                <img class="kg-logo" src="<?php echo kangoo_email_theme_logo_url(); ?>" width="360" alt="Kangoo Pouches" style="display:block;width:360px;max-width:82%;height:auto;border:0;">
                            </td>
                        </tr>
                        <tr>
                            <td class="kg-inner" style="padding:38px 52px 30px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td class="kg-hero-copy" width="54%" style="width:54%;padding:0 20px 18px 0;vertical-align:middle;">
                                            <?php if ($stage === 'received') : ?>
                                                <h1 class="kg-hero-title--received" style="margin:0 0 10px;color:#020407;font-family:Arial,Helvetica,sans-serif;font-size:39px;font-weight:900;line-height:1.08;letter-spacing:0;white-space:nowrap;">
                                                    <?php echo esc_html($variant['title']); ?>
                                                </h1>
                                                <div class="kg-hero-orange--received" style="margin:0 0 18px;color:#ff5a00;font-family:Arial,Helvetica,sans-serif;font-size:34px;font-weight:900;line-height:1.06;letter-spacing:0;">
                                                    <?php echo esc_html($variant['title_orange']); ?>
                                                </div>
                                            <?php else : ?>
                                                <h1 class="kg-hero-title" style="margin:0 0 22px;color:#020407;font-family:Arial,Helvetica,sans-serif;font-size:38px;font-weight:900;line-height:1.06;letter-spacing:0;">
                                                    <span style="white-space:nowrap;"><?php echo esc_html($variant['title']); ?></span><br>
                                                    <span style="color:#ff5a00;"><?php echo esc_html($variant['title_orange']); ?></span>
                                                </h1>
                                            <?php endif; ?>
                                            <p class="kg-hero-text" style="margin:0 0 12px;color:#0b0f14;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:1.45;font-weight:700;"><?php echo wp_kses_post($variant['intro']); ?></p>
                                            <p class="kg-hero-text" style="margin:0;color:#0b0f14;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:1.48;"><?php echo wp_kses_post($variant['body']); ?></p>
                                        </td>
                                        <td class="kg-hero-image" width="46%" align="right" style="width:46%;padding:0 0 18px 4px;vertical-align:middle;text-align:right;">
                                            <img class="kg-hero-img" src="<?php echo kangoo_email_asset_url($variant['image']); ?>" width="265" alt="" style="display:block;width:265px;max-width:100%;height:auto;border:0;">
                                        </td>
                                    </tr>
                                </table>

                                <p class="kg-primary-action" style="margin:0 0 22px;">
                                    <a class="kg-btn" href="<?php echo esc_url($variant['cta_url']); ?>" style="box-sizing:border-box;display:inline-block;min-width:292px;max-width:100%;padding:18px 22px;border-radius:8px;background:#ff5a00;color:#ffffff !important;font-family:Arial,Helvetica,sans-serif;font-size:19px;font-weight:900;line-height:1;text-align:center;text-decoration:none;text-transform:uppercase;white-space:nowrap;">
                                        <img src="<?php echo kangoo_email_asset_url($variant['cta_icon']); ?>" width="26" alt="" style="display:inline-block;width:26px;height:26px;margin-right:12px;border:0;vertical-align:-7px;">
                                        <?php echo esc_html($variant['cta']); ?>
                                    </a>
                                </p>

                                <?php echo kangoo_email_render_progress($stage); ?>

                                <?php
                                if ($stage === 'received') {
                                    echo kangoo_email_render_order_strip($order);
                                    echo kangoo_email_render_order_summary_card($order);
                                    echo kangoo_email_render_pending_rewards_card($order);
                                    echo kangoo_email_render_support_block();
                                    echo kangoo_email_render_trust_row();
                                } elseif ($stage === 'dispatched') {
                                    echo kangoo_email_render_order_strip($order);
                                    echo kangoo_email_render_tracking_card($order);
                                    echo kangoo_email_render_dispatched_delivery_card($order);
                                    echo kangoo_email_render_delivery_address_card($order);
                                    echo kangoo_email_render_order_summary_card($order);
                                    echo kangoo_email_render_keep_updated_card();
                                    echo kangoo_email_render_support_block();
                                } elseif ($stage === 'delayed') {
                                    echo kangoo_email_render_delay_info_card($order);
                                    echo kangoo_email_render_order_strip($order);
                                    echo kangoo_email_render_order_summary_card($order);
                                    echo kangoo_email_render_support_block();
                                    echo kangoo_email_render_trust_row();
                                } else {
                                    echo kangoo_email_render_order_strip($order);
                                    echo kangoo_email_render_order_summary_card($order);
                                    echo kangoo_email_render_earned_rewards_card($order);
                                    echo kangoo_email_render_offer_card($order);
                                    echo kangoo_email_render_support_block();
                                }
                                ?>

                                <?php if (trim((string) $additional_content) !== '') : ?>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 0;">
                                        <tr>
                                            <td style="color:#4b5563;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;">
                                                <?php echo wp_kses_post(wpautop($additional_content)); ?>
                                            </td>
                                        </tr>
                                    </table>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_order_received($order, $additional_content = '') {
    return kangoo_email_render_document($order, 'received', $additional_content);
}

function kangoo_email_render_order_dispatched($order, $additional_content = '') {
    return kangoo_email_render_document($order, 'dispatched', $additional_content);
}

function kangoo_email_render_order_delayed($order, $additional_content = '') {
    return kangoo_email_render_document($order, 'delayed', $additional_content);
}

function kangoo_email_render_order_completed($order, $additional_content = '') {
    return kangoo_email_render_document($order, 'completed', $additional_content);
}

function kangoo_email_render_shipping_update($order, $type) {
    if ($type === 'delayed') {
        return kangoo_email_render_order_delayed($order);
    }

    return kangoo_email_render_order_dispatched($order);
}
