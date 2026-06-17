<?php

defined('ABSPATH') || exit;

if (!defined('WP_CLI') || !WP_CLI) {
    exit('Run this file with wp eval-file.');
}

function kangoo_audit_text($html) {
    return trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $html)));
}

function kangoo_audit_tag($html, $tag) {
    if (preg_match('/<' . preg_quote($tag, '/') . '\b[^>]*>(.*?)<\/' . preg_quote($tag, '/') . '>/is', $html, $match)) {
        return kangoo_audit_text($match[1]);
    }
    return '';
}

function kangoo_audit_meta($html, $name, $property = false) {
    $attribute = $property ? 'property' : 'name';
    if (preg_match('/<meta\b[^>]*' . $attribute . '=["\']' . preg_quote($name, '/') . '["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $match)) {
        return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
    }
    if (preg_match('/<meta\b[^>]*content=["\']([^"\']*)["\'][^>]*' . $attribute . '=["\']' . preg_quote($name, '/') . '["\'][^>]*>/i', $html, $match)) {
        return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
    }
    return '';
}

function kangoo_audit_link($html, $rel) {
    if (preg_match('/<link\b[^>]*rel=["\']' . preg_quote($rel, '/') . '["\'][^>]*href=["\']([^"\']*)["\'][^>]*>/i', $html, $match)) {
        return $match[1];
    }
    if (preg_match('/<link\b[^>]*href=["\']([^"\']*)["\'][^>]*rel=["\']' . preg_quote($rel, '/') . '["\'][^>]*>/i', $html, $match)) {
        return $match[1];
    }
    return '';
}

function kangoo_audit_urls() {
    $urls = array(home_url('/'));

    foreach (array('product_cat', 'pa_flavour', 'pa_strength') as $taxonomy) {
        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $url = get_term_link($term);
                if (!is_wp_error($url)) {
                    $urls[] = $url;
                }
            }
        }
    }

    $posts = get_posts(array(
        'post_type' => array('page', 'kangoo_blog', 'product'),
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ));
    foreach ($posts as $post_id) {
        $urls[] = get_permalink($post_id);
    }

    return array_values(array_unique(array_filter($urls)));
}

$upload = wp_upload_dir();
$dir = trailingslashit($upload['basedir']) . 'kangoo-seo-audits';
wp_mkdir_p($dir);
$stamp = gmdate('Ymd-His');
$csv_path = trailingslashit($dir) . 'seo-audit-' . $stamp . '.csv';
$json_path = trailingslashit($dir) . 'seo-audit-' . $stamp . '.json';
$urls = kangoo_audit_urls();
$rows = array();
$internal_links = array();
$focus_map = array();

foreach ($urls as $index => $url) {
    $response = wp_remote_get($url, array('timeout' => 20, 'redirection' => 3, 'user-agent' => 'Kangoo Pouches SEO Audit/1.0'));
    $status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
    $html = is_wp_error($response) ? '' : wp_remote_retrieve_body($response);
    $title = kangoo_audit_tag($html, 'title');
    $h1 = kangoo_audit_tag($html, 'h1');
    $description = kangoo_audit_meta($html, 'description');
    $robots = kangoo_audit_meta($html, 'robots');
    $canonical = kangoo_audit_link($html, 'canonical');
    $main = kangoo_audit_tag($html, 'main');
    $words = str_word_count(kangoo_audit_text($main ?: $html));
    $focus = '';
    $object_id = url_to_postid($url);

    if ($object_id) {
        $focus = (string) get_post_meta($object_id, '_yoast_wpseo_focuskw', true);
    }

    if ($focus !== '') {
        $key = strtolower(trim($focus));
        $focus_map[$key] = isset($focus_map[$key]) ? $focus_map[$key] + 1 : 1;
    }

    if (preg_match_all('/<a\b[^>]*href=["\']([^"\'#]+)[^"\']*["\']/i', $html, $matches)) {
        foreach ($matches[1] as $link) {
            if (strpos($link, home_url('/')) === 0) {
                $internal_links[$link] = isset($internal_links[$link]) ? $internal_links[$link] + 1 : 1;
            }
        }
    }

    $rows[] = array(
        'url' => $url,
        'status' => $status,
        'title' => $title,
        'title_length' => mb_strlen($title),
        'h1' => $h1,
        'description' => $description,
        'description_length' => mb_strlen($description),
        'canonical' => $canonical,
        'robots' => $robots,
        'word_count' => $words,
        'focus_keyword' => $focus,
        'inbound_links' => 0,
        'orphan_signal' => '',
        'keyword_conflict' => '',
        'issues' => '',
    );
    WP_CLI::log(sprintf('[%d/%d] %s', $index + 1, count($urls), $url));
}

$known = array_fill_keys(array_map(static function ($url) {
    return untrailingslashit($url);
}, $urls), true);
$broken = array();

foreach (array_keys($internal_links) as $link) {
    $clean = untrailingslashit(strtok($link, '?'));
    if (isset($known[$clean])) {
        continue;
    }
    $response = wp_remote_head($link, array('timeout' => 12, 'redirection' => 3));
    $status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
    if ($status === 0 || $status >= 400) {
        $broken[$link] = $status;
    }
}

foreach ($rows as &$row) {
    $row['inbound_links'] = isset($internal_links[$row['url']]) ? $internal_links[$row['url']] : 0;
    $row['orphan_signal'] = $row['url'] !== home_url('/') && $row['inbound_links'] === 0 ? 'Review' : '';
    $focus_key = strtolower(trim($row['focus_keyword']));
    $row['keyword_conflict'] = $focus_key !== '' && isset($focus_map[$focus_key]) && $focus_map[$focus_key] > 1 ? $focus_map[$focus_key] . ' pages' : '';
    $issues = array();
    if ($row['status'] !== 200) {
        $issues[] = 'HTTP ' . $row['status'];
    }
    if ($row['title'] === '') {
        $issues[] = 'Missing title';
    }
    if ($row['h1'] === '') {
        $issues[] = 'Missing H1';
    }
    if ($row['description'] === '') {
        $issues[] = 'Missing description';
    }
    if ($row['canonical'] === '') {
        $issues[] = 'Missing canonical';
    }
    if ($row['title_length'] > 65) {
        $issues[] = 'Long title';
    }
    if ($row['description_length'] > 165) {
        $issues[] = 'Long description';
    }
    $row['issues'] = implode('; ', $issues);
}
unset($row);

$handle = fopen($csv_path, 'w');
fputcsv($handle, array_keys($rows[0]));
foreach ($rows as $row) {
    fputcsv($handle, $row);
}
fclose($handle);
file_put_contents($json_path, wp_json_encode(array('rows' => $rows, 'broken_links' => $broken), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

WP_CLI::success('SEO audit written to ' . $csv_path . ' and ' . $json_path . '. Broken links: ' . count($broken));
