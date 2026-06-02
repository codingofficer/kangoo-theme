<?php
defined('ABSPATH') || exit;

function kangoo_email_theme_logo_url() {
    return esc_url(get_theme_file_uri('/assets/images/kangoo-logo-black.png'));
}

function kangoo_email_asset_url($filename) {
    return esc_url(get_theme_file_uri('/assets/images/email/' . ltrim((string) $filename, '/')));
}

function kangoo_email_button($url, $label, $background = '#ff6b00', $colour = '#ffffff') {
    if (!$url) {
        return '';
    }

    return sprintf(
        '<a href="%1$s" style="box-sizing:border-box;display:inline-block;min-width:150px;max-width:100%;padding:15px 22px;border-radius:8px;background:%3$s;color:%4$s !important;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:800;line-height:1;text-align:center;text-decoration:none;text-transform:uppercase;">%2$s</a>',
        esc_url($url),
        esc_html($label),
        esc_attr($background),
        esc_attr($colour)
    );
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

function kangoo_email_tracking_url_for_order($order) {
    if (function_exists('kangoo_shipping_get_tracking_url')) {
        return kangoo_shipping_get_tracking_url($order);
    }

    return '';
}

function kangoo_email_tracking_number_for_order($order) {
    if (function_exists('kangoo_shipping_get_order_meta')) {
        return kangoo_shipping_get_order_meta($order, 'tracking_number');
    }

    return '';
}

function kangoo_email_shipping_service_for_order($order) {
    if (function_exists('kangoo_shipping_get_order_meta')) {
        return kangoo_shipping_get_order_meta($order, 'service', $order->get_shipping_method());
    }

    return $order instanceof WC_Order ? $order->get_shipping_method() : '';
}

function kangoo_email_delivery_address($order) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    $address = $order->get_formatted_shipping_address();

    if (!$address) {
        $address = $order->get_formatted_billing_address();
    }

    return $address ? wp_kses_post($address) : '';
}

function kangoo_email_order_items_rows($order) {
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

        if ($product && $product->get_image_id()) {
            $image_url = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
        }
        ?>
        <tr>
            <td style="padding:14px 0;border-bottom:1px solid #edf0f3;" width="82">
                <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" width="68" height="68" alt="" style="display:block;width:68px;height:68px;object-fit:contain;border:1px solid #e5e7eb;border-radius:12px;background:#ffffff;">
                <?php endif; ?>
            </td>
            <td style="padding:14px 12px;border-bottom:1px solid #edf0f3;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.35;">
                <strong><?php echo esc_html($item->get_name()); ?></strong><br>
                <span style="color:#4b5563;"><?php echo esc_html(sprintf(__('Qty: %d', 'kangoo'), $item->get_quantity())); ?></span>
            </td>
            <td style="padding:14px 0;border-bottom:1px solid #edf0f3;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:800;line-height:1.35;text-align:right;" width="90">
                <?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?>
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

    $shipping = (float) $order->get_shipping_total() > 0 ? wc_price($order->get_shipping_total()) : __('Free', 'kangoo');

    ob_start();
    ?>
    <tr>
        <td style="padding:8px 0;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;"><?php esc_html_e('Subtotal', 'kangoo'); ?></td>
        <td style="padding:8px 0;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;text-align:right;"><?php echo wp_kses_post(wc_price($order->get_subtotal())); ?></td>
    </tr>
    <tr>
        <td style="padding:8px 0;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;"><?php echo esc_html($order->get_shipping_method() ? sprintf(__('Shipping (%s)', 'kangoo'), $order->get_shipping_method()) : __('Shipping', 'kangoo')); ?></td>
        <td style="padding:8px 0;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;text-align:right;"><?php echo wp_kses_post($shipping); ?></td>
    </tr>
    <tr>
        <td style="padding:14px 0 0;border-top:1px solid #e5e7eb;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:800;"><?php esc_html_e('Total', 'kangoo'); ?></td>
        <td style="padding:14px 0 0;border-top:1px solid #e5e7eb;color:#ff5a00;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:900;text-align:right;"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
    </tr>
    <?php

    return ob_get_clean();
}

function kangoo_email_progress($steps) {
    if (empty($steps) || !is_array($steps)) {
        return '';
    }

    $count = count($steps);
    $cell_width = $count > 0 ? floor(100 / $count) : 100;

    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:24px 0;border:1px solid #e5e7eb;border-radius:14px;background:#ffffff;">
        <tr>
            <?php foreach ($steps as $step) : ?>
                <?php
                $active = !empty($step['active']);
                $done = !empty($step['done']);
                $colour = $active || $done ? '#ff6b00' : '#d1d5db';
                $label = isset($step['label']) ? $step['label'] : '';
                $icon = $done ? '&#10003;' : (!empty($step['alert']) ? '!' : '');
                ?>
                <td width="<?php echo esc_attr((string) $cell_width); ?>%" style="padding:18px 8px;text-align:center;vertical-align:top;">
                    <span style="display:inline-block;width:28px;height:28px;border:3px solid <?php echo esc_attr($colour); ?>;border-radius:999px;background:<?php echo esc_attr($active || $done ? $colour : '#ffffff'); ?>;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:900;line-height:28px;text-align:center;"><?php echo wp_kses_post($icon); ?></span>
                    <span style="display:block;margin-top:8px;color:<?php echo esc_attr($active ? '#ff5a00' : '#111827'); ?>;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:<?php echo esc_attr($active ? '800' : '600'); ?>;line-height:1.25;"><?php echo esc_html($label); ?></span>
                </td>
            <?php endforeach; ?>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_card_start($background = '#ffffff', $border = '#e5e7eb') {
    return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:18px 0;border:1px solid ' . esc_attr($border) . ';border-radius:14px;background:' . esc_attr($background) . ';"><tr><td style="padding:20px;">';
}

function kangoo_email_card_end() {
    return '</td></tr></table>';
}

function kangoo_email_rewards_points_for_order($order) {
    if (!$order instanceof WC_Order || !function_exists('kangoo_rewards_points_per_pound')) {
        return 0;
    }

    return (int) floor(max(0, (float) $order->get_subtotal() - (float) $order->get_discount_total()) * kangoo_rewards_points_per_pound());
}

function kangoo_email_common_support() {
    ob_start();
    ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:22px 0 0;border-top:1px solid #e5e7eb;">
        <tr>
            <td style="padding:20px 0 0;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="62" style="vertical-align:top;">
                            <span style="display:inline-block;width:48px;height:48px;border-radius:999px;background:#fff1e7;color:#ff5a00;font-family:Arial,Helvetica,sans-serif;font-size:24px;font-weight:900;line-height:48px;text-align:center;">&#9742;</span>
                        </td>
                        <td style="color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;">
                            <strong style="font-size:17px;"><?php esc_html_e("Need help? We're here for you.", 'kangoo'); ?></strong><br>
                            <a href="mailto:hello@kangoopouches.co.uk" style="color:#ff5a00;font-weight:800;text-decoration:none;">hello@kangoopouches.co.uk</a><br>
                            <span style="color:#4b5563;">kangoopouches.co.uk</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_order_received($order) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    $view_order_url = $order->get_view_order_url();
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
    $rewards_url = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('kangoo-rewards') : home_url('/my-account/kangoo-rewards/');
    $points = kangoo_email_rewards_points_for_order($order);
    $user_id = function_exists('kangoo_rewards_get_order_user_id') ? (int) kangoo_rewards_get_order_user_id($order) : (int) $order->get_user_id();
    $balance = $user_id && function_exists('kangoo_rewards_get_balance') ? (int) kangoo_rewards_get_balance($user_id) : 0;
    $balance = $balance > 0 ? $balance : $points;
    $address = kangoo_email_delivery_address($order);
    $hero_image = kangoo_email_asset_url('kp-order-box-img.png');

    ob_start();
    ?>
    <div class="kangoo-email kangoo-email--received" style="color:#111827;font-family:Arial,Helvetica,sans-serif;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="58%" style="padding:8px 18px 18px 0;vertical-align:middle;">
                    <h2 style="margin:0;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:36px;font-weight:900;line-height:1.08;">
                        <?php esc_html_e('Good things are', 'kangoo'); ?><br>
                        <?php esc_html_e('heading', 'kangoo'); ?> <span style="color:#ff5a00;"><?php esc_html_e('your way!', 'kangoo'); ?></span>
                    </h2>
                    <div style="margin:18px 0 0;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:1.45;">
                        <?php echo esc_html(sprintf(__('Hi %s,', 'kangoo'), kangoo_email_customer_first_name($order))); ?><br>
                        <?php esc_html_e("We've packed your order and it's now being prepared for dispatch.", 'kangoo'); ?>
                    </div>
                </td>
                <td width="42%" style="padding:8px 0 18px;text-align:right;vertical-align:middle;">
                    <img src="<?php echo esc_url($hero_image); ?>" width="240" alt="" style="display:inline-block;max-width:240px;width:100%;height:auto;">
                </td>
            </tr>
        </table>

        <?php
        echo kangoo_email_progress(array(
            array('label' => __('Order received', 'kangoo'), 'done' => true),
            array('label' => __('Payment confirmed', 'kangoo'), 'done' => true),
            array('label' => __('Preparing dispatch', 'kangoo'), 'active' => true),
            array('label' => __('On the way', 'kangoo')),
        ));
        ?>

        <?php echo kangoo_email_card_start('#fff7ed', '#fde4d2'); ?>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="68" style="vertical-align:middle;">
                        <span style="display:inline-block;width:52px;height:52px;border-radius:999px;background:#ff6b00;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:24px;font-weight:900;line-height:52px;text-align:center;">&#128203;</span>
                    </td>
                    <td style="color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:1.35;vertical-align:middle;">
                        <strong style="font-size:20px;"><?php echo esc_html(sprintf(__('Order #%s', 'kangoo'), $order->get_order_number())); ?></strong><br>
                        <span style="color:#4b5563;"><?php echo esc_html(kangoo_email_order_date($order)); ?></span>
                    </td>
                    <td width="160" style="text-align:right;vertical-align:middle;"><?php echo kangoo_email_button($view_order_url, __('View order', 'kangoo')); ?></td>
                </tr>
            </table>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:18px;border:1px solid #e5e7eb;border-radius:12px;background:#ffffff;">
                <?php echo kangoo_email_order_items_rows($order); ?>
            </table>
        <?php echo kangoo_email_card_end(); ?>

        <?php echo kangoo_email_card_start(); ?>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <?php echo kangoo_email_totals_rows($order); ?>
            </table>
        <?php echo kangoo_email_card_end(); ?>

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="50%" style="padding-right:8px;vertical-align:top;">
                    <?php echo kangoo_email_card_start('#f0fdf4', '#dcfce7'); ?>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td width="58" style="vertical-align:top;">
                                    <span style="display:inline-block;width:46px;height:46px;border-radius:999px;color:#16a34a;font-family:Arial,Helvetica,sans-serif;font-size:34px;font-weight:900;line-height:46px;text-align:center;">&#10003;</span>
                                </td>
                                <td style="vertical-align:top;">
                                    <strong style="display:block;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:1.25;"><?php esc_html_e('Age verification complete', 'kangoo'); ?></strong>
                                    <span style="display:inline-block;margin-top:14px;padding:6px 11px;border-radius:999px;background:#dcfce7;color:#15803d;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:800;">&#10003; <?php esc_html_e('Verified customer', 'kangoo'); ?></span>
                                </td>
                            </tr>
                        </table>
                    <?php echo kangoo_email_card_end(); ?>
                </td>
                <td width="50%" style="padding-left:8px;vertical-align:top;">
                    <?php echo kangoo_email_card_start(); ?>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td width="58" style="vertical-align:top;">
                                    <span style="display:inline-block;width:46px;height:46px;border-radius:999px;color:#ff5a00;font-family:Arial,Helvetica,sans-serif;font-size:34px;font-weight:900;line-height:46px;text-align:center;">&#9679;</span>
                                </td>
                                <td style="vertical-align:top;">
                                    <strong style="display:block;margin-bottom:8px;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:18px;"><?php esc_html_e('Delivery address', 'kangoo'); ?></strong>
                                    <div style="color:#374151;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.45;"><?php echo wp_kses_post($address); ?></div>
                                </td>
                            </tr>
                        </table>
                    <?php echo kangoo_email_card_end(); ?>
                </td>
            </tr>
        </table>

        <?php if ($points > 0) : ?>
            <?php echo kangoo_email_card_start('#ff6b00', '#ff6b00'); ?>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="70" style="vertical-align:middle;">
                            <span style="display:inline-block;width:58px;height:58px;border-radius:999px;background:#ffffff;color:#ff6b00;font-family:Arial,Helvetica,sans-serif;font-size:28px;font-weight:900;line-height:58px;text-align:center;">&#127873;</span>
                        </td>
                        <td style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.4;">
                            <strong style="font-size:20px;"><?php esc_html_e('Kangoo Rewards', 'kangoo'); ?></strong><br>
                            <strong><?php echo esc_html(sprintf(__('You earned %d points!', 'kangoo'), $points)); ?></strong><br>
                            <?php echo esc_html(sprintf(__('Current balance: %d points', 'kangoo'), $balance)); ?>
                        </td>
                        <td width="160" style="text-align:right;vertical-align:middle;"><?php echo kangoo_email_button($rewards_url, __('View rewards', 'kangoo'), '#ffffff', '#ff5a00'); ?></td>
                    </tr>
                </table>
            <?php echo kangoo_email_card_end(); ?>
        <?php endif; ?>

        <?php echo kangoo_email_card_start('#fff7ed', '#fed7aa'); ?>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="68" style="vertical-align:middle;">
                        <span style="display:inline-block;width:58px;height:58px;border-radius:999px;background:#ffffff;color:#ff5a00;font-family:Arial,Helvetica,sans-serif;font-size:28px;font-weight:900;line-height:58px;text-align:center;">&#127991;</span>
                    </td>
                    <td style="color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:1.45;">
                        <strong style="font-size:18px;"><?php esc_html_e('Thanks for your order!', 'kangoo'); ?></strong><br>
                        <?php esc_html_e('Use code', 'kangoo'); ?> <span style="display:inline-block;padding:2px 6px;border-radius:5px;background:#ff6b00;color:#ffffff;font-weight:900;">THANKYOU10</span> <?php esc_html_e('for 10% off your next order.', 'kangoo'); ?>
                    </td>
                    <td width="150" style="text-align:right;vertical-align:middle;"><?php echo kangoo_email_button($shop_url, __('Shop again', 'kangoo')); ?></td>
                </tr>
            </table>
        <?php echo kangoo_email_card_end(); ?>

        <?php echo kangoo_email_common_support(); ?>
    </div>
    <?php

    return ob_get_clean();
}

function kangoo_email_render_shipping_update($order, $type) {
    if (!$order instanceof WC_Order) {
        return '';
    }

    $is_delayed = $type === 'delayed';
    $tracking_url = kangoo_email_tracking_url_for_order($order);
    $tracking_number = kangoo_email_tracking_number_for_order($order);
    $service = kangoo_email_shipping_service_for_order($order);
    $address = kangoo_email_delivery_address($order);
    $image = $is_delayed ? 'kp-delay.png' : 'kp-order-box-img.png';
    $heading = $is_delayed ? __('Your order is experiencing a delay', 'kangoo') : __('Your order is on its way!', 'kangoo');
    $intro = $is_delayed
        ? __('Your order is still moving through the delivery network, but Royal Mail has reported a delay.', 'kangoo')
        : sprintf(__('Great news! Order #%s has been packed and handed to Royal Mail.', 'kangoo'), $order->get_order_number());

    ob_start();
    ?>
    <div class="kangoo-email kangoo-email--shipping" style="color:#111827;font-family:Arial,Helvetica,sans-serif;">
        <?php if ($is_delayed) : ?>
            <?php echo kangoo_email_card_start('#fff7ed', '#ff6b00'); ?>
                <strong style="display:block;color:#ff5a00;font-size:18px;text-transform:uppercase;"><?php esc_html_e('Delivery update', 'kangoo'); ?></strong>
                <span style="display:block;margin-top:6px;color:#111827;font-size:15px;"><?php esc_html_e("We're keeping an eye on your order.", 'kangoo'); ?></span>
            <?php echo kangoo_email_card_end(); ?>
        <?php endif; ?>

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td style="padding:8px 0 18px;vertical-align:middle;">
                    <h2 style="margin:0;color:#111827;font-size:34px;font-weight:900;line-height:1.08;"><?php echo esc_html($heading); ?></h2>
                    <p style="margin:14px 0 0;color:#111827;font-size:16px;line-height:1.55;">
                        <?php echo esc_html(sprintf(__('Hi %s,', 'kangoo'), kangoo_email_customer_first_name($order))); ?><br>
                        <?php echo esc_html($intro); ?>
                    </p>
                </td>
                <td width="190" style="padding:8px 0 18px;text-align:right;vertical-align:middle;">
                    <img src="<?php echo kangoo_email_asset_url($image); ?>" width="170" alt="" style="display:inline-block;max-width:170px;width:100%;height:auto;">
                </td>
            </tr>
        </table>

        <?php if ($tracking_url) : ?>
            <p style="margin:0 0 20px;text-align:center;"><?php echo kangoo_email_button($tracking_url, __('Track my order', 'kangoo')); ?></p>
        <?php endif; ?>

        <?php
        echo kangoo_email_progress($is_delayed ? array(
            array('label' => __('Order received', 'kangoo'), 'done' => true),
            array('label' => __('Processing', 'kangoo'), 'done' => true),
            array('label' => __('Dispatched', 'kangoo'), 'done' => true),
            array('label' => __('Delayed in transit', 'kangoo'), 'active' => true, 'alert' => true),
            array('label' => __('Delivered', 'kangoo')),
        ) : array(
            array('label' => __('Order received', 'kangoo'), 'done' => true),
            array('label' => __('Processing complete', 'kangoo'), 'done' => true),
            array('label' => __('Dispatched', 'kangoo'), 'active' => true),
            array('label' => __('Delivered', 'kangoo')),
        ));
        ?>

        <?php if ($tracking_number || $service) : ?>
            <?php echo kangoo_email_card_start($is_delayed ? '#fff7ed' : '#ffffff', $is_delayed ? '#fed7aa' : '#e5e7eb'); ?>
                <strong style="display:block;margin-bottom:8px;color:#111827;font-size:17px;"><?php esc_html_e('Tracking number', 'kangoo'); ?></strong>
                <?php if ($tracking_number) : ?>
                    <div style="color:#ff5a00;font-size:22px;font-weight:900;line-height:1.2;"><?php echo esc_html($tracking_number); ?></div>
                <?php endif; ?>
                <?php if ($service) : ?>
                    <div style="margin-top:6px;color:#4b5563;font-size:15px;"><?php echo esc_html($service); ?></div>
                <?php endif; ?>
            <?php echo kangoo_email_card_end(); ?>
        <?php endif; ?>

        <?php if ($address) : ?>
            <?php echo kangoo_email_card_start(); ?>
                <strong style="display:block;margin-bottom:8px;color:#111827;font-size:17px;"><?php esc_html_e('Delivery address', 'kangoo'); ?></strong>
                <div style="color:#374151;font-size:15px;line-height:1.45;"><?php echo wp_kses_post($address); ?></div>
            <?php echo kangoo_email_card_end(); ?>
        <?php endif; ?>

        <?php if ($is_delayed) : ?>
            <?php echo kangoo_email_card_start('#fffbeb', '#fcd34d'); ?>
                <strong style="display:block;color:#111827;font-size:18px;"><?php esc_html_e("Don't worry", 'kangoo'); ?></strong>
                <p style="margin:10px 0 0;color:#374151;font-size:15px;line-height:1.5;"><?php esc_html_e('Most delayed parcels arrive within 1-2 additional working days. If tracking has not updated after 5 working days, reply to this email and we will investigate.', 'kangoo'); ?></p>
            <?php echo kangoo_email_card_end(); ?>
        <?php else : ?>
            <?php echo kangoo_email_card_start(); ?>
                <strong style="display:block;margin-bottom:12px;color:#111827;font-size:18px;"><?php esc_html_e('Order summary', 'kangoo'); ?></strong>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                    <?php echo kangoo_email_order_items_rows($order); ?>
                    <?php echo kangoo_email_totals_rows($order); ?>
                </table>
            <?php echo kangoo_email_card_end(); ?>
        <?php endif; ?>

        <?php echo kangoo_email_common_support(); ?>
    </div>
    <?php

    return ob_get_clean();
}
