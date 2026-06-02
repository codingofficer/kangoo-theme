<?php
/**
 * Customer processing order email.
 *
 * @package Kangoo
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email);

$kangoo_rendered_order_email = false;

if (function_exists('kangoo_email_render_order_received') && $order instanceof WC_Order) {
    try {
        $kangoo_order_email_body = kangoo_email_render_order_received($order);

        if (trim(wp_strip_all_tags((string) $kangoo_order_email_body)) !== '') {
            echo $kangoo_order_email_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $kangoo_rendered_order_email = true;
        }
    } catch (Throwable $error) {
        error_log('Kangoo order received email failed: ' . $error->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}

if (!$kangoo_rendered_order_email) {
    ?>
    <p>
        <?php
        printf(
            esc_html__('Hi %s,', 'woocommerce'),
            esc_html($order->get_billing_first_name())
        );
        ?>
    </p>
    <p><?php esc_html_e("Just to let you know, we've received your order and it is now being processed.", 'woocommerce'); ?></p>
    <?php
    do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);
    do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);
    do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);
}

if (!$kangoo_rendered_order_email && $additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

do_action('woocommerce_email_footer', $email);
