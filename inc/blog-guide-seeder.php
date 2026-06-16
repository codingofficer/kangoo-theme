<?php
defined('ABSPATH') || exit;

function kangoo_blog_guide_seeder_data_path() {
    return get_template_directory() . '/guides/blog-guides/kangoo-blog-seed-data.json';
}

function kangoo_blog_guide_seeder_brand_authority_data_path() {
    return get_template_directory() . '/guides/blog-guides/kangoo-brand-authority-seed-data.json';
}

function kangoo_blog_guide_seeder_banner_map_path() {
    return get_template_directory() . '/generated-blog-banners/banner-map.csv';
}

function kangoo_blog_guide_seeder_load_json_file($path, $error_code) {
    if (!file_exists($path) || !is_readable($path)) {
        return new WP_Error($error_code . '_missing', __('Seed data file is missing.', 'kangoo'));
    }

    $json = file_get_contents($path);
    $guides = json_decode($json, true);

    if (!is_array($guides)) {
        return new WP_Error($error_code . '_invalid', __('Seed data file is not valid JSON.', 'kangoo'));
    }

    return $guides;
}

function kangoo_blog_guide_seeder_load_guides() {
    $path = kangoo_blog_guide_seeder_data_path();

    $guides = kangoo_blog_guide_seeder_load_json_file($path, 'kangoo_blog_seed');

    if (is_wp_error($guides)) {
        return $guides;
    }

    $brand_path = kangoo_blog_guide_seeder_brand_authority_data_path();

    if (!file_exists($brand_path)) {
        return $guides;
    }

    $brand_guides = kangoo_blog_guide_seeder_load_json_file($brand_path, 'kangoo_brand_authority_seed');

    if (is_wp_error($brand_guides)) {
        return $brand_guides;
    }

    $merged = array();
    $slug_positions = array();

    foreach ($guides as $guide) {
        $slug = isset($guide['slug']) ? sanitize_title($guide['slug']) : '';
        $slug_positions[$slug] = count($merged);
        $merged[] = $guide;
    }

    foreach ($brand_guides as $guide) {
        $slug = isset($guide['slug']) ? sanitize_title($guide['slug']) : '';

        if ($slug && isset($slug_positions[$slug])) {
            $merged[$slug_positions[$slug]] = array_merge($merged[$slug_positions[$slug]], $guide);
            continue;
        }

        $merged[] = $guide;
    }

    return $merged;
}

function kangoo_blog_guide_seeder_load_banner_map() {
    $path = kangoo_blog_guide_seeder_banner_map_path();

    if (!file_exists($path) || !is_readable($path)) {
        return new WP_Error('kangoo_blog_banner_map_missing', __('Banner map file is missing.', 'kangoo'));
    }

    $handle = fopen($path, 'r');

    if (!$handle) {
        return new WP_Error('kangoo_blog_banner_map_unreadable', __('Banner map file could not be opened.', 'kangoo'));
    }

    $headers = fgetcsv($handle);
    $rows = array();

    if (!is_array($headers)) {
        fclose($handle);
        return new WP_Error('kangoo_blog_banner_map_invalid', __('Banner map file has no header row.', 'kangoo'));
    }

    while (($data = fgetcsv($handle)) !== false) {
        $row = array();

        foreach ($headers as $index => $header) {
            $row[$header] = isset($data[$index]) ? $data[$index] : '';
        }

        if (!empty($row['slug']) && !empty($row['banner_filename'])) {
            $rows[] = $row;
        }
    }

    fclose($handle);

    return $rows;
}

function kangoo_blog_guide_seeder_update_term_field($term_id, $term_key, $field_name, $value) {
    if (function_exists('update_field')) {
        update_field($field_name, $value, $term_key);
        return;
    }

    update_term_meta($term_id, $field_name, $value);
}

function kangoo_blog_guide_seeder_sync_brand_category_seo() {
    if (
        !taxonomy_exists('product_cat')
        || !function_exists('kangoo_get_brand_authority_profiles')
        || !function_exists('kangoo_get_brand_authority_intro')
        || !function_exists('kangoo_get_brand_authority_content')
        || !function_exists('kangoo_get_brand_authority_faq')
    ) {
        return new WP_Error('kangoo_brand_category_sync_unavailable', __('Brand authority category data is unavailable.', 'kangoo'));
    }

    $updated = 0;
    $missing = array();

    foreach (kangoo_get_brand_authority_profiles() as $slug => $profile) {
        $term = get_term_by('slug', $slug, 'product_cat');

        if (!$term instanceof WP_Term) {
            $missing[] = $profile['label'];
            continue;
        }

        $term_key = 'product_cat_' . $term->term_id;

        kangoo_blog_guide_seeder_update_term_field($term->term_id, $term_key, 'category_seo_title', sprintf(__('%s Nicotine Pouches', 'kangoo'), $profile['label']));
        kangoo_blog_guide_seeder_update_term_field($term->term_id, $term_key, 'category_intro', kangoo_get_brand_authority_intro($slug));
        kangoo_blog_guide_seeder_update_term_field($term->term_id, $term_key, 'category_seo_content', kangoo_get_brand_authority_content($slug));
        kangoo_blog_guide_seeder_update_term_field($term->term_id, $term_key, 'category_faq', kangoo_get_brand_authority_faq($slug));

        $updated++;
    }

    return array(
        'updated' => $updated,
        'missing' => $missing,
    );
}

function kangoo_blog_guide_seeder_get_blog_post_by_slug($slug) {
    $posts = get_posts(array(
        'name'           => sanitize_title($slug),
        'post_type'      => 'kangoo_blog',
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ));

    return $posts ? (int) $posts[0] : 0;
}

function kangoo_blog_guide_seeder_admin_menu() {
    add_management_page(
        __('Kangoo Blog Seeder', 'kangoo'),
        __('Kangoo Blog Seeder', 'kangoo'),
        'manage_options',
        'kangoo-blog-guide-seeder',
        'kangoo_blog_guide_seeder_admin_page'
    );
}
add_action('admin_menu', 'kangoo_blog_guide_seeder_admin_menu');

function kangoo_blog_guide_seeder_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'kangoo'));
    }

    $guides = kangoo_blog_guide_seeder_load_guides();
    $is_error = is_wp_error($guides);
    $guide_count = $is_error ? 0 : count($guides);
    $existing_count = 0;
    $banner_rows = kangoo_blog_guide_seeder_load_banner_map();
    $banner_map_error = is_wp_error($banner_rows);
    $banner_count = $banner_map_error ? 0 : count($banner_rows);

    if (!$is_error) {
        foreach ($guides as $guide) {
            $slug = isset($guide['slug']) ? sanitize_title($guide['slug']) : '';

            if ($slug && kangoo_blog_guide_seeder_get_blog_post_by_slug($slug)) {
                $existing_count++;
            }
        }
    }

    $default_start = wp_date('Y-m-d', strtotime('+1 day', current_time('timestamp')));
    $last_result = get_transient('kangoo_blog_guide_seeder_result');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Kangoo Blog Seeder', 'kangoo'); ?></h1>
        <p><?php esc_html_e('Create the generated SEO blog guides as Kangoo Blog articles, with ACF article SEO fields and Yoast SEO meta filled automatically.', 'kangoo'); ?></p>

        <?php if ($last_result) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($last_result); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($is_error) : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($guides->get_error_message()); ?></p>
                <p><code><?php echo esc_html(kangoo_blog_guide_seeder_data_path()); ?></code></p>
            </div>
        <?php else : ?>
            <div class="card" style="max-width: 760px;">
                <h2><?php esc_html_e('Ready to import', 'kangoo'); ?></h2>
                <p>
                    <?php
                    printf(
                        esc_html__('%1$d guides found. %2$d matching slugs already exist in Blog.', 'kangoo'),
                        (int) $guide_count,
                        (int) $existing_count
                    );
                    ?>
                </p>
                <p class="description">
                    <?php esc_html_e('Base seed:', 'kangoo'); ?>
                    <code><?php echo esc_html(kangoo_blog_guide_seeder_data_path()); ?></code>
                </p>
                <p class="description">
                    <?php esc_html_e('Brand authority supplement:', 'kangoo'); ?>
                    <code><?php echo esc_html(kangoo_blog_guide_seeder_brand_authority_data_path()); ?></code>
                </p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('kangoo_blog_guide_seeder', 'kangoo_blog_guide_seeder_nonce'); ?>
                    <input type="hidden" name="action" value="kangoo_seed_blog_guides">

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="kangoo_seed_mode"><?php esc_html_e('Import mode', 'kangoo'); ?></label>
                            </th>
                            <td>
                                <select id="kangoo_seed_mode" name="seed_mode">
                                    <option value="schedule"><?php esc_html_e('Schedule one post per day', 'kangoo'); ?></option>
                                    <option value="draft"><?php esc_html_e('Create as drafts', 'kangoo'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="kangoo_seed_start_date"><?php esc_html_e('Schedule start date', 'kangoo'); ?></label>
                            </th>
                            <td>
                                <input id="kangoo_seed_start_date" type="date" name="start_date" value="<?php echo esc_attr($default_start); ?>">
                                <p class="description"><?php esc_html_e('Used only for scheduled posts without a fixed seed date. Guides with a scheduled_at value use that exact date instead.', 'kangoo'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="kangoo_seed_publish_time"><?php esc_html_e('Publish time', 'kangoo'); ?></label>
                            </th>
                            <td>
                                <input id="kangoo_seed_publish_time" type="time" name="publish_time" value="09:00">
                                <p class="description"><?php esc_html_e('Uses the WordPress site timezone.', 'kangoo'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Existing posts', 'kangoo'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="overwrite_existing" value="1">
                                    <?php esc_html_e('Update existing posts with matching slugs', 'kangoo'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Leave unchecked to skip existing articles. If checked, existing posts keep their current status and publish/scheduled dates while content and SEO fields are refreshed.', 'kangoo'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(__('Run blog seeder', 'kangoo')); ?>
                </form>
            </div>

            <div class="card" style="max-width: 760px;">
                <h2><?php esc_html_e('Brand category SEO', 'kangoo'); ?></h2>
                <p><?php esc_html_e('Overwrite the WooCommerce brand category ACF fields with the latest Kangoo brand authority content. This updates the visible category heading, hero intro, lower-page SEO copy and FAQ rows for ZYN, VELO, PABLO, KILLA, Nordic Spirit, Übbs, FUMi and XQS.', 'kangoo'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('kangoo_sync_brand_category_seo', 'kangoo_sync_brand_category_seo_nonce'); ?>
                    <input type="hidden" name="action" value="kangoo_sync_brand_category_seo">
                    <?php submit_button(__('Sync brand category SEO', 'kangoo'), 'secondary'); ?>
                </form>
            </div>

            <div class="card" style="max-width: 760px;">
                <h2><?php esc_html_e('Blog banners', 'kangoo'); ?></h2>

                <?php if ($banner_map_error) : ?>
                    <p><?php echo esc_html($banner_rows->get_error_message()); ?></p>
                    <p><code><?php echo esc_html(kangoo_blog_guide_seeder_banner_map_path()); ?></code></p>
                <?php else : ?>
                    <p>
                        <?php
                        printf(
                            esc_html__('%d banner rows found. Import matching PNGs into Media Library, set featured images, and write attachment alt text.', 'kangoo'),
                            (int) $banner_count
                        );
                        ?>
                    </p>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('kangoo_blog_banner_import', 'kangoo_blog_banner_import_nonce'); ?>
                        <input type="hidden" name="action" value="kangoo_assign_blog_banners">

                        <p>
                            <label>
                                <input type="checkbox" name="overwrite_featured_image" value="1">
                                <?php esc_html_e('Replace existing featured images', 'kangoo'); ?>
                            </label>
                        </p>

                        <p class="description"><?php esc_html_e('Leave unchecked to only fill posts without a featured image. Existing imported banner attachments are reused when possible.', 'kangoo'); ?></p>

                        <?php submit_button(__('Import and assign blog banners', 'kangoo')); ?>
                    </form>
                <?php endif; ?>
            </div>

            <h2><?php esc_html_e('Guides in this batch', 'kangoo'); ?></h2>
            <table class="widefat striped" style="max-width: 1100px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Order', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Title', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Focus keyphrase', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Topic', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Scheduled at', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Status', 'kangoo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guides as $guide) : ?>
                        <?php
                        $slug = isset($guide['slug']) ? sanitize_title($guide['slug']) : '';
                        $existing_id = $slug ? kangoo_blog_guide_seeder_get_blog_post_by_slug($slug) : 0;
                        $existing = $existing_id ? get_post($existing_id) : null;
                        ?>
                        <tr>
                            <td><?php echo esc_html(isset($guide['order']) ? (int) $guide['order'] : ''); ?></td>
                        <td><?php echo esc_html(isset($guide['title']) ? $guide['title'] : ''); ?></td>
                        <td><?php echo esc_html(isset($guide['focus_keyphrase']) ? $guide['focus_keyphrase'] : ''); ?></td>
                        <td><?php echo esc_html(isset($guide['topic']) ? $guide['topic'] : ''); ?></td>
                        <td><?php echo esc_html(isset($guide['scheduled_at']) ? $guide['scheduled_at'] : __('Seeder start date', 'kangoo')); ?></td>
                        <td>
                                <?php
                                echo $existing
                                    ? esc_html(sprintf(__('Exists: %s', 'kangoo'), get_post_status($existing)))
                                    : esc_html__('Ready', 'kangoo');
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

function kangoo_blog_guide_seeder_update_meta($post_id, $field_name, $field_key, $value) {
    if (function_exists('update_field')) {
        update_field($field_key, $value, $post_id);
    } else {
        update_post_meta($post_id, $field_name, $value);
    }

    update_post_meta($post_id, '_' . $field_name, $field_key);
}

function kangoo_blog_guide_seeder_assign_topic($post_id, $topic_name) {
    $topic_name = sanitize_text_field($topic_name);

    if ($topic_name === '') {
        return;
    }

    $term = term_exists($topic_name, 'blog_topic');

    if (!$term) {
        $term = wp_insert_term($topic_name, 'blog_topic');
    }

    if (is_wp_error($term)) {
        return;
    }

    $term_id = is_array($term) ? (int) $term['term_id'] : (int) $term;
    wp_set_object_terms($post_id, array($term_id), 'blog_topic', false);
}

function kangoo_blog_guide_seeder_banner_alt_text($row, $post_id) {
    $title = '';

    foreach (array('seo_title', 'post_title', 'title') as $key) {
        if (!empty($row[$key])) {
            $title = $row[$key];
            break;
        }
    }

    if ($title === '') {
        $title = get_the_title($post_id);
    }

    $title = preg_replace('/\s+\|\s*Kangoo(?:\s+Pouches)?\s*$/i', '', wp_strip_all_tags($title));
    $title = trim(preg_replace('/\s+/', ' ', $title));

    return sprintf(
        __('Kangoo Pouches blog banner for %s', 'kangoo'),
        $title ? $title : get_the_title($post_id)
    );
}

function kangoo_blog_guide_seeder_find_banner_attachment($slug) {
    $attachments = get_posts(array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_kangoo_blog_banner_slug',
        'meta_value'     => sanitize_title($slug),
    ));

    return $attachments ? (int) $attachments[0] : 0;
}

function kangoo_blog_guide_seeder_media_includes() {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
}

function kangoo_blog_guide_seeder_import_banner_attachment($row, $post_id) {
    $slug = isset($row['slug']) ? sanitize_title($row['slug']) : '';
    $filename = isset($row['banner_filename']) ? sanitize_file_name(basename($row['banner_filename'])) : '';

    if ($slug === '' || $filename === '') {
        return new WP_Error('kangoo_blog_banner_row_invalid', __('Banner row is missing a slug or filename.', 'kangoo'));
    }

    $existing_attachment_id = kangoo_blog_guide_seeder_find_banner_attachment($slug);
    $alt_text = kangoo_blog_guide_seeder_banner_alt_text($row, $post_id);

    if ($existing_attachment_id) {
        update_post_meta($existing_attachment_id, '_wp_attachment_image_alt', $alt_text);
        return $existing_attachment_id;
    }

    $source_path = get_template_directory() . '/generated-blog-banners/' . $filename;

    if (!file_exists($source_path) || !is_readable($source_path)) {
        return new WP_Error('kangoo_blog_banner_missing_file', sprintf(__('Banner file is missing: %s', 'kangoo'), $filename));
    }

    kangoo_blog_guide_seeder_media_includes();

    $temporary_file = wp_tempnam($filename);

    if (!$temporary_file || !copy($source_path, $temporary_file)) {
        return new WP_Error('kangoo_blog_banner_temp_failed', sprintf(__('Could not prepare banner file: %s', 'kangoo'), $filename));
    }

    $file_type = wp_check_filetype($filename);
    $file_array = array(
        'name'     => $filename,
        'type'     => !empty($file_type['type']) ? $file_type['type'] : 'image/png',
        'tmp_name' => $temporary_file,
        'error'    => 0,
        'size'     => filesize($source_path),
    );

    $attachment_id = media_handle_sideload($file_array, $post_id, $alt_text);

    if (is_wp_error($attachment_id)) {
        @unlink($temporary_file);
        return $attachment_id;
    }

    wp_update_post(array(
        'ID'           => $attachment_id,
        'post_title'   => $alt_text,
        'post_excerpt' => $alt_text,
    ));

    update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
    update_post_meta($attachment_id, '_kangoo_blog_banner_slug', $slug);
    update_post_meta($attachment_id, '_kangoo_blog_banner_filename', $filename);

    return (int) $attachment_id;
}

function kangoo_blog_guide_seeder_post_dates($mode, $index, $start_date, $publish_time, $scheduled_at = '') {
    if ($mode !== 'schedule') {
        return array(
            'post_date'     => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', true),
        );
    }

    $timezone = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');
    $start_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) ? $start_date : wp_date('Y-m-d', strtotime('+1 day', current_time('timestamp')));
    $publish_time = preg_match('/^\d{2}:\d{2}$/', $publish_time) ? $publish_time : '09:00';
    $has_fixed_schedule = $scheduled_at && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $scheduled_at);

    try {
        if ($has_fixed_schedule) {
            $date = new DateTimeImmutable($scheduled_at, $timezone);
        } else {
            $date = new DateTimeImmutable($start_date . ' ' . $publish_time . ':00', $timezone);
        }
    } catch (Exception $exception) {
        $date = new DateTimeImmutable(wp_date('Y-m-d', strtotime('+1 day', current_time('timestamp'))) . ' 09:00:00', $timezone);
    }

    if (!$has_fixed_schedule && $index > 0) {
        $date = $date->modify('+' . absint($index) . ' days');
    }

    $post_date = $date->format('Y-m-d H:i:s');

    return array(
        'post_date'     => $post_date,
        'post_date_gmt' => get_gmt_from_date($post_date),
    );
}

function kangoo_blog_guide_seeder_handle_import() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to run the blog seeder.', 'kangoo'));
    }

    check_admin_referer('kangoo_blog_guide_seeder', 'kangoo_blog_guide_seeder_nonce');

    $guides = kangoo_blog_guide_seeder_load_guides();

    if (is_wp_error($guides)) {
        wp_die(esc_html($guides->get_error_message()));
    }

    $mode = isset($_POST['seed_mode']) && $_POST['seed_mode'] === 'draft' ? 'draft' : 'schedule';
    $overwrite_existing = !empty($_POST['overwrite_existing']);
    $start_date = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : '';
    $publish_time = isset($_POST['publish_time']) ? sanitize_text_field(wp_unslash($_POST['publish_time'])) : '09:00';
    $created = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($guides as $offset => $guide) {
        $slug = isset($guide['slug']) ? sanitize_title($guide['slug']) : '';
        $title = isset($guide['title']) ? sanitize_text_field($guide['title']) : '';

        if ($slug === '' || $title === '') {
            $skipped++;
            continue;
        }

        $existing_id = kangoo_blog_guide_seeder_get_blog_post_by_slug($slug);
        $existing = $existing_id ? get_post($existing_id) : null;

        if ($existing && !$overwrite_existing) {
            $skipped++;
            continue;
        }

        $scheduled_at = isset($guide['scheduled_at']) ? sanitize_text_field($guide['scheduled_at']) : '';
        $dates = kangoo_blog_guide_seeder_post_dates($mode, $offset, $start_date, $publish_time, $scheduled_at);
        $post_status = $mode === 'schedule' ? 'future' : 'draft';

        if ($existing) {
            $post_status = $existing->post_status;
            $dates = array(
                'post_date'     => $existing->post_date,
                'post_date_gmt' => $existing->post_date_gmt,
            );
        }

        $post_data = array(
            'post_type'    => 'kangoo_blog',
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => isset($guide['content_html']) ? wp_kses_post($guide['content_html']) : '',
            'post_excerpt' => isset($guide['standfirst']) ? wp_strip_all_tags($guide['standfirst']) : '',
            'post_status'  => $post_status,
            'post_author'  => get_current_user_id(),
            'post_date'    => $dates['post_date'],
            'post_date_gmt'=> $dates['post_date_gmt'],
        );

        if ($existing) {
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post($post_data, true);
            $updated++;
        } else {
            $post_id = wp_insert_post($post_data, true);
            $created++;
        }

        if (is_wp_error($post_id)) {
            $skipped++;
            continue;
        }

        $seo_title = isset($guide['seo_title']) ? sanitize_text_field($guide['seo_title']) : $title;
        $meta_description = isset($guide['meta_description']) ? sanitize_textarea_field($guide['meta_description']) : '';
        $standfirst = isset($guide['standfirst']) ? sanitize_textarea_field($guide['standfirst']) : $meta_description;
        $focus_keyphrase = isset($guide['focus_keyphrase']) ? sanitize_text_field($guide['focus_keyphrase']) : '';
        $eyebrow = isset($guide['eyebrow']) ? sanitize_text_field($guide['eyebrow']) : __('Guide', 'kangoo');
        $read_time = isset($guide['read_time']) ? max(1, absint($guide['read_time'])) : 5;

        kangoo_blog_guide_seeder_update_meta($post_id, 'blog_eyebrow', 'field_kangoo_blog_eyebrow', $eyebrow);
        kangoo_blog_guide_seeder_update_meta($post_id, 'blog_standfirst', 'field_kangoo_blog_standfirst', $standfirst);
        kangoo_blog_guide_seeder_update_meta($post_id, 'blog_read_time', 'field_kangoo_blog_read_time', $read_time);
        kangoo_blog_guide_seeder_update_meta($post_id, 'blog_seo_title', 'field_kangoo_blog_seo_title', $seo_title);
        kangoo_blog_guide_seeder_update_meta($post_id, 'blog_meta_description', 'field_kangoo_blog_meta_description', $meta_description);

        if (isset($guide['primary_brand'])) {
            update_post_meta($post_id, '_kangoo_primary_brand', sanitize_text_field($guide['primary_brand']));
        }

        if (isset($guide['category_url'])) {
            update_post_meta($post_id, '_kangoo_category_url', esc_url_raw($guide['category_url']));
        }

        if (isset($guide['query_targets']) && is_array($guide['query_targets'])) {
            update_post_meta($post_id, '_kangoo_query_targets', array_map('sanitize_text_field', $guide['query_targets']));
        }

        if (isset($guide['source_urls']) && is_array($guide['source_urls'])) {
            update_post_meta($post_id, '_kangoo_source_urls', array_map('esc_url_raw', $guide['source_urls']));
        }

        update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus_keyphrase);
        update_post_meta($post_id, '_yoast_wpseo_title', $seo_title);
        update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);

        kangoo_blog_guide_seeder_assign_topic($post_id, isset($guide['topic']) ? $guide['topic'] : __('Guide', 'kangoo'));
    }

    $category_sync = kangoo_blog_guide_seeder_sync_brand_category_seo();
    $category_sync_message = '';

    if (!is_wp_error($category_sync)) {
        $category_sync_message = ' ' . sprintf(
            __('Brand categories updated: %d.', 'kangoo'),
            isset($category_sync['updated']) ? (int) $category_sync['updated'] : 0
        );

        if (!empty($category_sync['missing'])) {
            $category_sync_message .= ' ' . sprintf(
                __('Missing category slugs: %s.', 'kangoo'),
                implode(', ', array_map('sanitize_text_field', $category_sync['missing']))
            );
        }
    }

    set_transient(
        'kangoo_blog_guide_seeder_result',
        sprintf(
            __('Blog seeder finished. Created: %1$d. Updated: %2$d. Skipped: %3$d.', 'kangoo'),
            $created,
            $updated,
            $skipped
        ) . $category_sync_message,
        60
    );

    wp_safe_redirect(admin_url('tools.php?page=kangoo-blog-guide-seeder'));
    exit;
}
add_action('admin_post_kangoo_seed_blog_guides', 'kangoo_blog_guide_seeder_handle_import');

function kangoo_blog_guide_seeder_handle_brand_category_sync() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to sync brand category SEO.', 'kangoo'));
    }

    check_admin_referer('kangoo_sync_brand_category_seo', 'kangoo_sync_brand_category_seo_nonce');

    $result = kangoo_blog_guide_seeder_sync_brand_category_seo();

    if (is_wp_error($result)) {
        wp_die(esc_html($result->get_error_message()));
    }

    $message = sprintf(
        __('Brand category SEO sync finished. Updated: %d.', 'kangoo'),
        isset($result['updated']) ? (int) $result['updated'] : 0
    );

    if (!empty($result['missing'])) {
        $message .= ' ' . sprintf(
            __('Missing category slugs: %s.', 'kangoo'),
            implode(', ', array_map('sanitize_text_field', $result['missing']))
        );
    }

    set_transient('kangoo_blog_guide_seeder_result', $message, 60);

    wp_safe_redirect(admin_url('tools.php?page=kangoo-blog-guide-seeder'));
    exit;
}
add_action('admin_post_kangoo_sync_brand_category_seo', 'kangoo_blog_guide_seeder_handle_brand_category_sync');

function kangoo_blog_guide_seeder_handle_banner_import() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to import blog banners.', 'kangoo'));
    }

    check_admin_referer('kangoo_blog_banner_import', 'kangoo_blog_banner_import_nonce');

    $rows = kangoo_blog_guide_seeder_load_banner_map();

    if (is_wp_error($rows)) {
        wp_die(esc_html($rows->get_error_message()));
    }

    $overwrite_featured_image = !empty($_POST['overwrite_featured_image']);
    $matched = 0;
    $imported = 0;
    $reused = 0;
    $assigned = 0;
    $skipped_with_image = 0;
    $missing_posts = 0;
    $errors = 0;

    foreach ($rows as $row) {
        $slug = isset($row['slug']) ? sanitize_title($row['slug']) : '';
        $post_id = $slug ? kangoo_blog_guide_seeder_get_blog_post_by_slug($slug) : 0;

        if (!$post_id) {
            $missing_posts++;
            continue;
        }

        $matched++;

        if (!$overwrite_featured_image && has_post_thumbnail($post_id)) {
            $skipped_with_image++;
            continue;
        }

        $existing_attachment_id = kangoo_blog_guide_seeder_find_banner_attachment($slug);
        $attachment_id = kangoo_blog_guide_seeder_import_banner_attachment($row, $post_id);

        if (is_wp_error($attachment_id)) {
            $errors++;
            continue;
        }

        if ($existing_attachment_id) {
            $reused++;
        } else {
            $imported++;
        }

        if (set_post_thumbnail($post_id, $attachment_id)) {
            $assigned++;
        }
    }

    set_transient(
        'kangoo_blog_guide_seeder_result',
        sprintf(
            __('Blog banner import finished. Rows: %1$d. Matched posts: %2$d. Imported: %3$d. Reused: %4$d. Assigned: %5$d. Already had images: %6$d. Missing posts: %7$d. Errors: %8$d.', 'kangoo'),
            count($rows),
            $matched,
            $imported,
            $reused,
            $assigned,
            $skipped_with_image,
            $missing_posts,
            $errors
        ),
        60
    );

    wp_safe_redirect(admin_url('tools.php?page=kangoo-blog-guide-seeder'));
    exit;
}
add_action('admin_post_kangoo_assign_blog_banners', 'kangoo_blog_guide_seeder_handle_banner_import');
