<?php
/**
 * Customer completed order email.
 *
 * Override path:
 * your-child-theme/woocommerce/emails/customer-completed-order.php
 *
 * @package Kangoo
 */

defined('ABSPATH') || exit;

if (!isset($order) || !($order instanceof WC_Order)) {
    return;
}

$order_number = $order->get_order_number();
$first_name   = $order->get_billing_first_name() ?: __('there', 'woocommerce');
$view_url     = $order->get_view_order_url();
$currency     = $order->get_currency();

$logo_url = apply_filters(
    'kangoo_email_logo_url',
    get_theme_file_uri('/assets/images/kangoo-logo-black.png')
);

$box_url = apply_filters(
    'kangoo_email_shipped_box_url',
    get_theme_file_uri('/assets/images/email/kp-order-box-img.png')
);

$tracking_number = function_exists('kangoo_email_tracking_number_for_order') ? kangoo_email_tracking_number_for_order($order) : '';
if (!$tracking_number && function_exists('kangoo_shipping_get_order_meta')) {
    $tracking_number = kangoo_shipping_get_order_meta($order, 'tracking_number');
}
$tracking_number = apply_filters('kangoo_email_shipped_tracking_number', trim((string) $tracking_number), $order);

$tracking_url = function_exists('kangoo_email_tracking_url_for_order') ? kangoo_email_tracking_url_for_order($order) : '';
if (!$tracking_url && $tracking_number) {
    $tracking_url = 'https://www.royalmail.com/track-your-item#/tracking-results/' . rawurlencode($tracking_number);
}
$tracking_url = apply_filters('kangoo_email_shipped_tracking_url', $tracking_url, $order);

$shipping_service = function_exists('kangoo_email_shipping_service_for_order') ? kangoo_email_shipping_service_for_order($order) : $order->get_shipping_method();
$shipping_service = $shipping_service ?: __('Royal Mail Tracked 48', 'kangoo');

$primary_url = $tracking_url ?: $view_url;

$address_lines = array_filter(array(
    trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()) ?: trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
    $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
    $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
    $order->get_shipping_city() ?: $order->get_billing_city(),
    $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
));

$shipping_total = (float) $order->get_shipping_total() > 0
    ? wc_price($order->get_shipping_total(), array('currency' => $currency))
    : __('Free', 'kangoo');

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo esc_html(sprintf(__('Order #%s shipped', 'kangoo'), $order_number)); ?></title>
    <style>
        @media only screen and (max-width: 620px) {
            .kg-wrap { width: 100% !important; max-width: 100% !important; border-radius: 14px !important; }
            .kg-body-pad { padding: 12px 8px !important; }
            .kg-header { padding: 18px 16px 16px !important; }
            .kg-inner { padding: 28px 26px 22px !important; }
            .kg-logo { width: 285px !important; max-width: 90% !important; }
            .kg-hero-copy { width: 58% !important; padding: 0 12px 0 0 !important; vertical-align: top !important; }
            .kg-hero-title { margin-bottom: 20px !important; font-size: 34px !important; line-height: 1.12 !important; }
            .kg-hero-text { font-size: 17px !important; line-height: 1.45 !important; }
            .kg-hero-image { display: table-cell !important; width: 42% !important; padding: 8px 0 0 !important; vertical-align: middle !important; }
            .kg-hero-box { width: 172px !important; max-width: 100% !important; }
            .kg-primary-action { margin: 18px 0 22px !important; }
            .kg-btn { display: block !important; width: 100% !important; box-sizing: border-box !important; text-align: center !important; white-space: nowrap !important; }
            .kg-card { padding: 18px !important; }
            .kg-track-copy { padding: 0 !important; vertical-align: middle !important; }
            .kg-track-action { padding-top: 18px !important; text-align: center !important; }
            .kg-address-icon, .kg-track-icon, .kg-help-icon { width: 62px !important; padding-right: 14px !important; }
            .kg-summary-product-image { width: 78px !important; padding-right: 14px !important; }
            .kg-summary-product-copy { padding-left: 0 !important; }
            .kg-total-label { font-size: 22px !important; }
            .kg-total-value { font-size: 24px !important; white-space: nowrap !important; }
            .kg-progress-label { font-size: 13px !important; }
            .kg-progress-circle { width: 36px !important; height: 36px !important; line-height: 36px !important; }
        }
        @media only screen and (max-width: 380px) {
            .kg-inner { padding: 24px 18px 20px !important; }
            .kg-logo { width: 255px !important; }
            .kg-hero-title { font-size: 30px !important; }
            .kg-hero-text { font-size: 15px !important; }
            .kg-hero-box { width: 142px !important; }
            .kg-card { padding: 16px !important; }
            .kg-progress-label { font-size: 12px !important; }
            .kg-total-value { font-size: 21px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f5f6f8; font-family:Arial, Helvetica, sans-serif; color:#0b0f14;">
    <table role="presentation" class="kg-body-pad" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f5f6f8; margin:0; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" class="kg-wrap" width="680" cellspacing="0" cellpadding="0" border="0" style="width:680px; max-width:680px; background:#ffffff; border:1px solid #dfe3e8; border-radius:16px; overflow:hidden; box-shadow:0 18px 48px rgba(15,23,42,0.12);">
                    <tr>
                        <td class="kg-header" align="center" style="padding:30px 24px 24px; border-bottom:4px solid #ff5a00;">
                            <img class="kg-logo" src="<?php echo esc_url($logo_url); ?>" width="360" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" style="display:block; width:360px; max-width:82%; height:auto; border:0;" />
                        </td>
                    </tr>

                    <tr>
                        <td class="kg-inner" style="padding:40px 52px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="kg-stack-cell kg-hero-copy" width="56%" style="width:56%; padding:0 24px 20px 0; vertical-align:middle;">
                                        <h1 class="kg-hero-title" style="margin:0 0 22px; font-size:44px; line-height:1.08; font-weight:900; color:#020407;">
                                            <?php esc_html_e('Your order is', 'kangoo'); ?><br />
                                            <span style="color:#ff5a00;"><?php esc_html_e('on its way!', 'kangoo'); ?></span>
                                        </h1>
                                        <p class="kg-hero-text" style="margin:0 0 12px; font-size:18px; line-height:1.45; color:#0b0f14;">
                                            <?php
                                            printf(
                                                wp_kses_post(__('Great news, <strong>%s</strong>.', 'kangoo')),
                                                esc_html($first_name)
                                            );
                                            ?>
                                        </p>
                                        <p class="kg-hero-text" style="margin:0; font-size:17px; line-height:1.5; color:#0b0f14;">
                                            <?php esc_html_e('Your order', 'kangoo'); ?>
                                            <strong style="color:#ff5a00;">#<?php echo esc_html($order_number); ?></strong>
                                            <?php esc_html_e('has now been packed and handed over to Royal Mail.', 'kangoo'); ?>
                                        </p>
                                    </td>
                                    <td class="kg-stack-cell kg-hero-image" width="44%" align="right" style="width:44%; padding:0 0 20px; vertical-align:middle;">
                                        <img class="kg-hero-box" src="<?php echo esc_url($box_url); ?>" width="270" alt="" style="display:block; width:270px; max-width:100%; height:auto; border:0;" />
                                    </td>
                                </tr>
                            </table>

                            <?php if ($primary_url) : ?>
                                <p class="kg-primary-action" style="margin:0 0 24px;">
                                    <a class="kg-btn" href="<?php echo esc_url($primary_url); ?>" style="display:inline-block; min-width:292px; padding:18px 22px; border-radius:8px; background:#ff5a00; color:#ffffff; font-size:19px; line-height:1; font-weight:900; text-decoration:none; text-transform:uppercase; text-align:center;">
                                        <span style="font-size:24px; line-height:0; vertical-align:-3px;">&#9638;</span>
                                        <span style="display:inline-block; padding-left:12px;"><?php esc_html_e('Track my order', 'kangoo'); ?></span>
                                    </a>
                                </p>
                            <?php endif; ?>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 16px; border:1px solid #e0e3e8; border-radius:14px; background:#ffffff;">
                                <tr>
                                    <?php
                                    $steps = array(
                                        array(__('Order<br />Received', 'kangoo'), true, false),
                                        array(__('Payment<br />Confirmed', 'kangoo'), true, false),
                                        array(__('Dispatched', 'kangoo'), true, true),
                                        array(__('Delivered', 'kangoo'), false, false),
                                    );
                                    foreach ($steps as $index => $step) :
                                        $done = (bool) $step[1];
                                        $active = (bool) $step[2];
                                        ?>
                                        <td width="25%" align="center" style="padding:22px 8px 20px; vertical-align:top;">
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td style="height:42px; vertical-align:middle;">
                                                        <?php
                                                        $left_line = $index === 0 ? 'transparent' : ($index <= 2 ? '#ff5a00' : '#cfd3d8');
                                                        $right_line = $index < 2 ? '#ff5a00' : ($index === 2 ? '#cfd3d8' : 'transparent');
                                                        ?>
                                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                            <tr>
                                                                <td width="50%" style="border-top:4px solid <?php echo esc_attr($left_line); ?>; font-size:0; line-height:0;">&nbsp;</td>
                                                                <td width="44" align="center" style="width:44px; text-align:center;">
                                                                    <span class="kg-progress-circle" style="display:inline-block; width:40px; height:40px; border-radius:50%; border:3px solid <?php echo $done ? '#ff5a00' : '#cfd3d8'; ?>; background:<?php echo $done ? '#ff5a00' : '#ffffff'; ?>; color:#ffffff; font-size:21px; line-height:40px; font-weight:900; text-align:center;"><?php echo $done ? '&#10003;' : '&nbsp;'; ?></span>
                                                                </td>
                                                                <td width="50%" style="border-top:4px solid <?php echo esc_attr($right_line); ?>; font-size:0; line-height:0;">&nbsp;</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="kg-progress-label" style="padding-top:12px; color:<?php echo $active ? '#ff5a00' : ($done ? '#111827' : '#69707a'); ?>; font-size:15px; line-height:1.2; font-weight:<?php echo $active ? '800' : '700'; ?>; text-align:center;">
                                                        <?php echo wp_kses_post($step[0]); ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:0; border:1px solid #e0e3e8; border-radius:14px; background:#ffffff;">
                                <tr>
                                    <td class="kg-card" style="padding:24px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td class="kg-track-icon" width="84" style="width:84px; padding-right:20px; vertical-align:middle;">
                                                    <span style="display:inline-block; width:64px; height:64px; border-radius:50%; background:#fff1e7; color:#ff5a00; font-size:33px; line-height:64px; text-align:center;">&#128205;&#65038;</span>
                                                </td>
                                                <td class="kg-track-copy" style="vertical-align:middle;">
                                                    <strong style="display:block; margin-bottom:8px; color:#0b0f14; font-size:19px; line-height:1.25;"><?php esc_html_e('Tracking Number', 'kangoo'); ?></strong>
                                                    <?php if ($tracking_number) : ?>
                                                        <span style="display:inline-block; color:#ff5a00; font-size:25px; line-height:1.15; font-weight:900;"><?php echo esc_html($tracking_number); ?></span>
                                                        <span style="display:inline-block; margin-left:10px; color:#606872; font-size:18px; vertical-align:2px;">&#9633;</span>
                                                    <?php else : ?>
                                                        <span style="display:inline-block; color:#ff5a00; font-size:22px; line-height:1.15; font-weight:900;"><?php esc_html_e('Tracking pending', 'kangoo'); ?></span>
                                                    <?php endif; ?>
                                                    <span style="display:block; margin-top:8px; color:#59616b; font-size:18px; line-height:1.35;"><?php echo esc_html($shipping_service); ?></span>
                                                </td>
                                            </tr>
                                            <?php if ($primary_url) : ?>
                                                <tr>
                                                    <td class="kg-track-action" colspan="2" align="center" style="padding-top:20px; text-align:center;">
                                                        <a class="kg-btn" href="<?php echo esc_url($primary_url); ?>" style="display:block; padding:15px 20px; border:1px solid #ff5a00; border-radius:7px; color:#ff5a00; background:#ffffff; font-size:16px; line-height:1; font-weight:900; text-decoration:none; text-transform:uppercase; text-align:center; white-space:nowrap;">
                                                            <?php esc_html_e('Track parcel', 'kangoo'); ?> <span style="font-size:16px;">&#8599;</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px; border:1px solid #e0e3e8; border-radius:14px; background:#ffffff;">
                                <tr>
                                    <td class="kg-card" style="padding:24px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td class="kg-address-icon" width="84" style="width:84px; padding-right:20px; vertical-align:top;">
                                                    <span style="display:inline-block; width:64px; height:64px; border-radius:50%; background:#fff1e7; color:#ff5a00; font-size:33px; line-height:64px; text-align:center;">&#128205;&#65038;</span>
                                                </td>
                                                <td style="vertical-align:top;">
                                                    <strong style="display:block; margin-bottom:10px; color:#0b0f14; font-size:19px; line-height:1.25;"><?php esc_html_e('Delivery Address', 'kangoo'); ?></strong>
                                                    <span style="display:block; color:#0b0f14; font-size:17px; line-height:1.45;"><?php echo wp_kses_post(implode('<br />', array_map('esc_html', $address_lines))); ?></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px; border:1px solid #e0e3e8; border-radius:14px; background:#ffffff;">
                                <tr>
                                    <td class="kg-card" style="padding:24px;">
                                        <strong style="display:block; margin-bottom:14px; color:#0b0f14; font-size:20px; line-height:1.25;"><?php esc_html_e('Order Summary', 'kangoo'); ?></strong>
                                        <?php foreach ($order->get_items() as $item_id => $item) : ?>
                                            <?php
                                            if (!$item instanceof WC_Order_Item_Product) {
                                                continue;
                                            }

                                            $product = $item->get_product();
                                            $image_url = '';
                                            if ($product) {
                                                $image_id = (int) $product->get_image_id();
                                                if (!$image_id && $product->is_type('variation')) {
                                                    $parent_product = wc_get_product($product->get_parent_id());
                                                    $image_id = $parent_product ? (int) $parent_product->get_image_id() : 0;
                                                }
                                                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : wc_placeholder_img_src('woocommerce_thumbnail');
                                            }
                                            ?>
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td class="kg-summary-product-image" width="104" style="width:104px; padding:0 22px 18px 0; vertical-align:top;">
                                                        <?php if ($image_url) : ?>
                                                            <img src="<?php echo esc_url($image_url); ?>" width="88" alt="<?php echo esc_attr($item->get_name()); ?>" style="display:block; width:88px; max-width:88px; height:auto; max-height:96px; border:0; border-radius:10px; background:#ffffff;" />
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="kg-summary-product-copy" style="padding:8px 0 18px; vertical-align:top;">
                                                        <strong style="display:block; margin-bottom:10px; color:#0b0f14; font-size:18px; line-height:1.3;"><?php echo esc_html($item->get_name()); ?></strong>
                                                        <span style="display:block; margin-bottom:12px; color:#59616b; font-size:16px;"><?php echo esc_html(sprintf(__('Qty: %d', 'kangoo'), $item->get_quantity())); ?></span>
                                                        <strong style="display:block; color:#0b0f14; font-size:17px;"><?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?></strong>
                                                    </td>
                                                </tr>
                                            </table>
                                        <?php endforeach; ?>

                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-top:1px solid #dfe3e8;">
                                            <tr>
                                                <td style="padding:18px 0 8px; color:#0b0f14; font-size:17px;"><?php esc_html_e('Subtotal', 'kangoo'); ?></td>
                                                <td align="right" style="padding:18px 0 8px; color:#0b0f14; font-size:17px;"><?php echo wp_kses_post(wc_price($order->get_subtotal(), array('currency' => $currency))); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0 18px; color:#0b0f14; font-size:17px;"><?php echo esc_html(sprintf(__('Shipping (%s)', 'kangoo'), $shipping_service)); ?></td>
                                                <td align="right" style="padding:8px 0 18px; color:#0b0f14; font-size:17px;"><?php echo wp_kses_post($shipping_total); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="kg-total-label" style="padding:20px 0 0; border-top:1px solid #dfe3e8; color:#0b0f14; font-size:22px; line-height:1.2; font-weight:900;"><?php esc_html_e('Total', 'kangoo'); ?></td>
                                                <td class="kg-total-value" align="right" style="padding:20px 0 0; border-top:1px solid #dfe3e8; color:#ff5a00; font-size:25px; line-height:1.2; font-weight:900;"><?php echo wp_kses_post($order->get_formatted_order_total()); ?> <?php echo esc_html($currency); ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:18px; border:1px solid #e0e3e8; border-radius:14px; background:#ffffff;">
                                <tr>
                                    <td class="kg-card" style="padding:24px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td class="kg-help-icon" width="88" style="width:88px; padding-right:22px; vertical-align:middle;">
                                                    <span style="display:inline-block; width:66px; height:66px; border-radius:50%; background:#fff1e7; color:#ff5a00; font-size:32px; line-height:66px; text-align:center;">&#127911;&#65038;</span>
                                                </td>
                                                <td style="vertical-align:middle;">
                                                    <strong style="display:block; margin-bottom:10px; color:#0b0f14; font-size:20px; line-height:1.25;"><?php esc_html_e("Need help? We're here for you.", 'kangoo'); ?></strong>
                                                    <a href="mailto:hello@kangoopouches.co.uk" style="display:block; margin-bottom:8px; color:#ff5a00; font-size:18px; line-height:1.3; text-decoration:none;">hello@kangoopouches.co.uk</a>
                                                    <a href="https://kangoopouches.co.uk" style="display:block; color:#59616b; font-size:18px; line-height:1.3; text-decoration:none;">kangoopouches.co.uk</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <?php if (!empty($additional_content)) : ?>
                                <div style="margin-top:22px; color:#59616b; font-size:15px; line-height:1.5;">
                                    <?php echo wp_kses_post(wpautop(wptexturize($additional_content))); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
