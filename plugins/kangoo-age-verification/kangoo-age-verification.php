<?php
/**
 * Plugin Name: Kangoo Age Verification
 * Description: Mandatory photo-ID and selfie age verification before WooCommerce payment.
 * Version: 0.1.2
 * Author: Kangoo Pouches
 * Requires Plugins: woocommerce
 * Requires PHP: 8.0
 * Text Domain: kangoo-age-verification
 */

defined('ABSPATH') || exit;

define('KANGOO_AV_VERSION', '0.1.2');
define('KANGOO_AV_FILE', __FILE__);
define('KANGOO_AV_DIR', plugin_dir_path(__FILE__));
define('KANGOO_AV_URL', plugin_dir_url(__FILE__));

require_once KANGOO_AV_DIR . 'includes/class-kangoo-age-verification.php';

register_activation_hook(__FILE__, array('Kangoo_Age_Verification', 'activate'));
add_action('plugins_loaded', array('Kangoo_Age_Verification', 'instance'));
