<?php
/**
 * Customer delayed order email.
 *
 * @package Kangoo
 */

defined('ABSPATH') || exit;

if (!isset($order) || !($order instanceof WC_Order)) {
    return;
}

$additional_content = isset($additional_content) ? (string) $additional_content : '';

if (function_exists('kangoo_email_render_order_delayed')) {
    echo kangoo_email_render_order_delayed($order, $additional_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return;
}
