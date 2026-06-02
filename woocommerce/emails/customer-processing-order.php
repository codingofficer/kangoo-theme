<?php
/**
 * Customer processing order email.
 *
 * @package Kangoo
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email);

if (function_exists('kangoo_email_render_order_received')) {
    echo kangoo_email_render_order_received($order); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
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

if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

do_action('woocommerce_email_footer', $email);
