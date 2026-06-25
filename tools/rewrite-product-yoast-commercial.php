<?php
/**
 * Rewrite product Yoast SEO titles and descriptions with commercial hooks.
 *
 * Usage:
 *   wp eval-file tools/rewrite-product-yoast-commercial.php output=/tmp/product-seo.csv
 *   wp eval-file tools/rewrite-product-yoast-commercial.php apply output=/tmp/product-seo.csv
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "This script must be run through WP-CLI.\n");
    exit(1);
}

$cli_args = isset($GLOBALS['argv']) && is_array($GLOBALS['argv']) ? $GLOBALS['argv'] : array();
$apply = in_array('--apply', $cli_args, true) || in_array('apply', $cli_args, true);
$output = '';

foreach ($cli_args as $arg) {
    if (strpos($arg, '--output=') === 0) {
        $output = substr($arg, 9);
        break;
    }

    if (strpos($arg, 'output=') === 0) {
        $output = substr($arg, 7);
        break;
    }
}

if ($output === '') {
    $output = trailingslashit(sys_get_temp_dir()) . 'kangoo-product-yoast-commercial.csv';
}

if (!function_exists('wc_get_products')) {
    fwrite(STDERR, "WooCommerce is not available.\n");
    exit(1);
}

function kangoo_cli_clean_text($value) {
    $value = html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/', ' ', $value);
    return trim((string) $value);
}

function kangoo_cli_get_product_brand($product) {
    if (function_exists('kangoo_get_product_brand_label')) {
        $brand = kangoo_cli_clean_text(kangoo_get_product_brand_label($product));

        if ($brand !== '') {
            return $brand;
        }
    }

    $terms = get_the_terms($product->get_id(), 'product_cat');

    if (!is_array($terms)) {
        return '';
    }

    $known = array('ZYN', 'VELO', 'PABLO', 'KILLA', 'Nordic Spirit', 'Ubbs', 'FUMi', 'XQS');

    foreach ($terms as $term) {
        foreach ($known as $label) {
            if (strtolower($term->slug) === strtolower(sanitize_title($label))) {
                return $label;
            }
        }
    }

    return '';
}

function kangoo_cli_get_product_strength($product) {
    if (function_exists('kangoo_get_product_strength_details')) {
        $details = kangoo_get_product_strength_details($product);
        $label = isset($details['label']) ? kangoo_cli_clean_text($details['label']) : '';

        if ($label !== '') {
            return kangoo_cli_normalize_strength($label);
        }
    }

    $name = $product->get_name();

    if (preg_match('/\b\d+(?:\.\d+)?\s*mg\b/i', $name, $match)) {
        return kangoo_cli_normalize_strength($match[0]);
    }

    return '';
}

function kangoo_cli_normalize_strength($value) {
    $value = kangoo_cli_clean_text($value);
    $value = preg_replace('/\s+/', '', $value);
    $value = preg_replace('/mg$/i', 'mg', $value);
    return (string) $value;
}

function kangoo_cli_get_product_descriptor($product, $brand, $strength) {
    $descriptor = kangoo_cli_clean_text($product->get_name());
    $replacements = array(
        '/\bnicotine\s+pouches\b/i',
        '/\bpouches\b/i',
        '/\bonline\b/i',
        '/\buk\b/i',
        '/\b\d+\s*pouches\b/i',
        '/\b10\s*pack\b/i',
    );

    if ($brand !== '') {
        $descriptor = preg_replace('/\b' . preg_quote($brand, '/') . '\b/i', '', $descriptor);
    }

    if ($strength !== '') {
        $strength_pattern = preg_quote($strength, '/');
        $strength_pattern = str_replace('\ ', '\s*', $strength_pattern);
        $descriptor = preg_replace('/\b' . $strength_pattern . '\b/i', '', $descriptor);
    }

    $descriptor = preg_replace('/\b\d+(?:\.\d+)?\s*mg\b/i', '', $descriptor);

    foreach ($replacements as $pattern) {
        $descriptor = preg_replace($pattern, '', $descriptor);
    }

    $descriptor = preg_replace('/\s+/', ' ', $descriptor);
    $descriptor = trim((string) $descriptor, " \t\n\r\0\x0B-|,");

    return $descriptor !== '' ? $descriptor : kangoo_cli_clean_text($product->get_name());
}

function kangoo_cli_is_trial_product($product) {
    if (function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product->get_id())) {
        return true;
    }

    $terms = get_the_terms($product->get_id(), 'product_cat');

    if (!is_array($terms)) {
        return false;
    }

    foreach ($terms as $term) {
        if ($term->slug === '99p-pouches') {
            return true;
        }
    }

    return false;
}

function kangoo_cli_compact_title($brand, $descriptor, $strength, $hook) {
    $parts = array_filter(array($brand, $descriptor, $strength));
    $variants = array(
        trim(implode(' ', $parts)) . ' Nicotine Pouches | ' . $hook,
        trim(implode(' ', $parts)) . ' Pouches | ' . $hook,
        trim(implode(' ', array_filter(array($brand, $descriptor)))) . ' Nicotine Pouches | ' . $hook,
        trim(implode(' ', array_filter(array($brand, $descriptor)))) . ' Pouches | ' . $hook,
    );

    foreach ($variants as $variant) {
        $variant = kangoo_cli_clean_text($variant);

        if (mb_strlen($variant) <= 65) {
            return $variant;
        }
    }

    return mb_substr(kangoo_cli_clean_text(end($variants)), 0, 65);
}

function kangoo_cli_compact_description($product, $brand, $descriptor, $strength, $price, $is_trial) {
    $product_name = kangoo_cli_clean_text($product->get_name());
    $currency = html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8');
    $price_text = $price !== '' ? $currency . number_format((float) $price, 2) : '';

    if ($is_trial) {
        $desc = sprintf(
            'Try %s from 79p. Tobacco-free nicotine pouches with price checked deals, next day delivery and free delivery available.',
            $product_name
        );
    } else {
        $desc = sprintf(
            'Shop %s at Kangoo Pouches. Price checked at %s, tobacco-free nicotine pouches with next day delivery and free delivery available.',
            $product_name,
            $price_text
        );
    }

    $desc = kangoo_cli_clean_text($desc);

    if (mb_strlen($desc) <= 158) {
        return $desc;
    }

    $short_name = kangoo_cli_clean_text(trim(implode(' ', array_filter(array($brand, $descriptor, $strength)))));

    if ($is_trial) {
        $desc = sprintf(
            'Try %s from 79p. Tobacco-free pouches with price checked deals, next day delivery and free delivery available.',
            $short_name
        );
    } else {
        $desc = sprintf(
            'Shop %s at Kangoo Pouches. Price checked at %s with next day delivery and free delivery available. Tobacco-free pouches.',
            $short_name,
            $price_text
        );
    }

    return kangoo_cli_clean_text($desc);
}

function kangoo_cli_guardrail_warnings($title, $description) {
    $combined = strtolower($title . ' ' . $description);
    $warnings = array();
    $blocked = array('out of stock', '18+', '18 only', 'adult user', 'adult-only', 'cheapest', 'snus', 'quit smoking', 'stop smoking', 'safe ');

    foreach ($blocked as $needle) {
        if (strpos($combined, $needle) !== false) {
            $warnings[] = $needle;
        }
    }

    return $warnings;
}

$products = wc_get_products(array(
    'status' => 'publish',
    'limit' => -1,
    'orderby' => 'ID',
    'order' => 'ASC',
    'return' => 'objects',
));

$rows = array();
$blocked_rows = array();

foreach ($products as $product) {
    if (!$product instanceof WC_Product) {
        continue;
    }

    $brand = kangoo_cli_get_product_brand($product);
    $strength = kangoo_cli_get_product_strength($product);
    $descriptor = kangoo_cli_get_product_descriptor($product, $brand, $strength);
    $is_trial = kangoo_cli_is_trial_product($product);
    $price = $product->get_price();
    $hook = $is_trial ? 'Now 79p' : 'Price Checked';
    $title = kangoo_cli_compact_title($brand, $descriptor, $strength, $hook);
    $description = kangoo_cli_compact_description($product, $brand, $descriptor, $strength, $price, $is_trial);
    $warnings = kangoo_cli_guardrail_warnings($title, $description);

    if (!empty($warnings)) {
        $blocked_rows[] = $product->get_id() . ':' . implode('|', $warnings);
    }

    $rows[] = array(
        'id' => $product->get_id(),
        'url' => get_permalink($product->get_id()),
        'product_name' => kangoo_cli_clean_text($product->get_name()),
        'brand' => $brand,
        'descriptor' => $descriptor,
        'strength' => $strength,
        'price' => $price,
        'is_trial' => $is_trial ? 'yes' : 'no',
        'current_title' => get_post_meta($product->get_id(), '_yoast_wpseo_title', true),
        'proposed_title' => $title,
        'title_length' => mb_strlen($title),
        'current_description' => get_post_meta($product->get_id(), '_yoast_wpseo_metadesc', true),
        'proposed_description' => $description,
        'description_length' => mb_strlen($description),
        'warnings' => implode('|', $warnings),
    );
}

$handle = fopen($output, 'w');

if (!$handle) {
    fwrite(STDERR, "Could not write output CSV: {$output}\n");
    exit(1);
}

if (!empty($rows)) {
    fputcsv($handle, array_keys($rows[0]));

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
}

fclose($handle);

if (!empty($blocked_rows)) {
    fwrite(STDERR, "Guardrail warnings found: " . implode(', ', $blocked_rows) . "\n");
    fwrite(STDERR, "No updates applied. Review {$output}.\n");
    exit(1);
}

if ($apply) {
    foreach ($rows as $row) {
        update_post_meta((int) $row['id'], '_yoast_wpseo_title', $row['proposed_title']);
        update_post_meta((int) $row['id'], '_yoast_wpseo_metadesc', $row['proposed_description']);
    }
}

$mode = $apply ? 'Applied' : 'Dry run';
echo sprintf("%s product Yoast SEO rewrite for %d products. CSV: %s\n", $mode, count($rows), $output);
