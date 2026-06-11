<?php

defined('ABSPATH') || exit;

$backup_dir = WP_CONTENT_DIR . '/uploads/kangoo-seo-backups';

if (!is_dir($backup_dir)) {
    wp_mkdir_p($backup_dir);
}

$term_ids = get_terms(array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'fields'     => 'ids',
));

$backup = array(
    'created_at'          => gmdate('c'),
    'terms'               => array(),
    'wpseo_taxonomy_meta' => get_option('wpseo_taxonomy_meta', array()),
);

foreach ($term_ids as $term_id) {
    $term = get_term($term_id, 'product_cat');

    if (!$term || is_wp_error($term)) {
        continue;
    }

    $backup['terms'][$term_id] = array(
        'name'                 => $term->name,
        'slug'                 => $term->slug,
        'description'          => $term->description,
        'category_intro'       => get_term_meta($term_id, 'category_intro', true),
        'category_seo_title'   => get_term_meta($term_id, 'category_seo_title', true),
        'category_seo_content' => get_term_meta($term_id, 'category_seo_content', true),
        'category_faq'         => get_term_meta($term_id, 'category_faq', true),
    );
}

$backup_file = trailingslashit($backup_dir) . 'category-content-before-79p-sync-' . gmdate('Ymd-His') . '.json';
file_put_contents($backup_file, wp_json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

foreach ($term_ids as $term_id) {
    $term = get_term($term_id, 'product_cat');

    if (!$term || is_wp_error($term)) {
        continue;
    }

    $description = str_replace('99p', '79p', $term->description);

    if ($term->slug === 'velo') {
        $description = str_replace(
            'Regular VELO single tins are £3.99 where available, while selected 79p trial pouches are separate and limited.',
            'Current VELO prices and pack offers are shown live, while selected 79p trial pouches are separate and limited.',
            $description
        );
    }

    if ($description !== $term->description) {
        wp_update_term($term_id, 'product_cat', array('description' => $description));
    }

    foreach (array('category_intro', 'category_seo_title', 'category_seo_content') as $meta_key) {
        $value = get_term_meta($term_id, $meta_key, true);
        $updated_value = str_replace('99p', '79p', $value);

        if ($term->slug === 'velo' && $meta_key === 'category_seo_content') {
            $updated_value = str_replace(
                'Regular VELO single tins are £3.99 where available. Selected VELO trial pouches may also appear in the 79p pouch range while stock lasts.',
                'Current VELO prices and pack offers are shown live on each product. Selected VELO trial pouches may also appear in the 79p pouch range while stock lasts.',
                $updated_value
            );
        }

        if ($updated_value !== $value) {
            update_term_meta($term_id, $meta_key, $updated_value);
        }
    }

    $faq_count = (int) get_term_meta($term_id, 'category_faq', true);

    for ($index = 0; $index < $faq_count; $index++) {
        foreach (array('question', 'answer') as $part) {
            $meta_key = 'category_faq_' . $index . '_' . $part;
            $value = get_term_meta($term_id, $meta_key, true);
            $updated_value = str_replace('99p', '79p', $value);

            if ($term->slug === 'velo' && $part === 'answer') {
                $updated_value = str_replace(
                    'Regular VELO single tins are £3.99 where available.',
                    'Current VELO prices and pack offers are shown live on each product.',
                    $updated_value
                );
            }

            if ($updated_value !== $value) {
                update_term_meta($term_id, $meta_key, $updated_value);
            }
        }
    }
}

$trial_term = get_term_by('slug', '99p-pouches', 'product_cat');

if ($trial_term instanceof WP_Term) {
    wp_update_term($trial_term->term_id, 'product_cat', array('name' => '79p Pouches'));
    update_term_meta($trial_term->term_id, 'category_intro', 'Looking for 99p nicotine pouches? Kangoo trial pouches now start from just 79p. Explore selected trials from ZYN, VELO, FUMi and XQS while limited stock is available.');
    update_term_meta($trial_term->term_id, 'category_seo_title', '79p Nicotine Pouches UK');
}

$yoast_meta = get_option('wpseo_taxonomy_meta', array());

if (!empty($yoast_meta['product_cat']) && is_array($yoast_meta['product_cat'])) {
    foreach ($yoast_meta['product_cat'] as $term_id => $values) {
        foreach (array('wpseo_title', 'wpseo_desc', 'wpseo_focuskw') as $key) {
            if (isset($values[$key])) {
                $yoast_meta['product_cat'][$term_id][$key] = str_replace('99p', '79p', $values[$key]);
            }
        }
    }

    if ($trial_term instanceof WP_Term) {
        $yoast_meta['product_cat'][$trial_term->term_id]['wpseo_title'] = '79p Nicotine Pouches UK | Cheap Trial Pouches | Kangoo';
        $yoast_meta['product_cat'][$trial_term->term_id]['wpseo_desc'] = 'Searching for 99p nicotine pouches? Pick and mix selected ZYN, VELO, FUMi and XQS trial pouches from just 79p each while limited stock is available.';
        $yoast_meta['product_cat'][$trial_term->term_id]['wpseo_focuskw'] = '79p nicotine pouches';
    }

    $velo_term = get_term_by('slug', 'velo', 'product_cat');

    if ($velo_term instanceof WP_Term) {
        $yoast_meta['product_cat'][$velo_term->term_id]['wpseo_desc'] = 'Shop VELO mint, berry, citrus and tropical nicotine pouches. See live prices and pack offers, plus selected 79p trials while available. 18+ only.';
    }

    update_option('wpseo_taxonomy_meta', $yoast_meta, false);
}

WP_CLI::success('Category content model synchronized. Backup: ' . $backup_file);
