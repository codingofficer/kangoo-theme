<?php
if (!defined('ABSPATH')) {
    exit;
}

function kangoo_theme_appearance_presets() {
    return array(
        'dark'        => __('Dark', 'kangoo'),
        'light-first' => __('Light-first retail', 'kangoo'),
    );
}

function kangoo_normalize_theme_appearance($appearance) {
    if (is_array($appearance)) {
        if (isset($appearance['value'])) {
            $appearance = $appearance['value'];
        } else {
            $appearance = reset($appearance);
        }
    }

    $appearance = sanitize_key(str_replace('_', '-', (string) $appearance));

    if ($appearance === 'light') {
        $appearance = 'light-first';
    }

    return array_key_exists($appearance, kangoo_theme_appearance_presets()) ? $appearance : 'dark';
}

function kangoo_get_saved_theme_appearance() {
    $appearance = '';

    if (function_exists('get_field')) {
        $acf_appearance = get_field('kangoo_theme_appearance', 'option');

        if ($acf_appearance !== null && $acf_appearance !== false && $acf_appearance !== '') {
            $appearance = $acf_appearance;
        }
    }

    if ($appearance === '') {
        $appearance = get_option('kangoo_theme_appearance', 'dark');
    }

    return kangoo_normalize_theme_appearance($appearance);
}

function kangoo_get_theme_preview_appearance() {
    if (!current_user_can('manage_options') || !isset($_GET['kangoo_theme_preview'])) {
        return '';
    }

    return kangoo_normalize_theme_appearance(wp_unslash($_GET['kangoo_theme_preview']));
}

function kangoo_get_active_theme_appearance() {
    $preview = kangoo_get_theme_preview_appearance();

    if ($preview !== '') {
        return $preview;
    }

    return kangoo_get_saved_theme_appearance();
}

function kangoo_is_light_theme_active() {
    return kangoo_get_active_theme_appearance() === 'light-first';
}

function kangoo_theme_appearance_body_class($classes) {
    $appearance = kangoo_get_active_theme_appearance();

    $classes[] = 'kangoo-theme--' . sanitize_html_class($appearance === 'light-first' ? 'light' : 'dark');

    if (kangoo_get_theme_preview_appearance() !== '') {
        $classes[] = 'kangoo-theme-preview';
    }

    return $classes;
}
add_filter('body_class', 'kangoo_theme_appearance_body_class');

function kangoo_light_theme_logo_url() {
    return get_template_directory_uri() . '/assets/images/kangoo-logo-black.png';
}

function kangoo_light_theme_logo_path() {
    return get_template_directory() . '/assets/images/kangoo-logo-black.png';
}

function kangoo_filter_custom_logo_for_light_theme($html, $blog_id) {
    if (!kangoo_is_light_theme_active()) {
        return $html;
    }

    if (!file_exists(kangoo_light_theme_logo_path())) {
        return $html;
    }

    $home_url = esc_url(home_url('/'));
    $site_name = esc_attr(get_bloginfo('name'));
    $logo_url = esc_url(kangoo_light_theme_logo_url());
    $current_attr = is_front_page() ? ' aria-current="page"' : '';

    return sprintf(
        '<a href="%1$s" class="custom-logo-link" rel="home"%4$s><img width="402" height="117" src="%2$s" class="custom-logo" alt="%3$s" decoding="async" fetchpriority="high" /></a>',
        $home_url,
        $logo_url,
        $site_name,
        $current_attr
    );
}
add_filter('get_custom_logo', 'kangoo_filter_custom_logo_for_light_theme', 10, 2);

function kangoo_register_theme_appearance_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_kangoo_theme_appearance',
        'title' => __('Theme Appearance', 'kangoo'),
        'fields' => array(
            array(
                'key' => 'field_kangoo_theme_appearance',
                'label' => __('Theme Appearance', 'kangoo'),
                'name' => 'kangoo_theme_appearance',
                'type' => 'select',
                'instructions' => __('Choose the public storefront appearance. Dark is the safe default; use ?kangoo_theme_preview=light while logged in as an admin to preview light mode without changing visitors.', 'kangoo'),
                'choices' => kangoo_theme_appearance_presets(),
                'default_value' => 'dark',
                'return_format' => 'value',
                'ui' => 1,
                'allow_null' => 0,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'control-panel',
                ),
            ),
        ),
        'position' => 'acf_after_title',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 0,
    ));
}
add_action('acf/init', 'kangoo_register_theme_appearance_acf_fields');

function kangoo_sanitize_theme_appearance($appearance) {
    return kangoo_normalize_theme_appearance($appearance);
}

function kangoo_register_theme_appearance_fallback_settings() {
    register_setting('kangoo_theme_appearance_options', 'kangoo_theme_appearance', array(
        'sanitize_callback' => 'kangoo_sanitize_theme_appearance',
        'default'           => 'dark',
    ));

    add_settings_section(
        'kangoo_theme_appearance_section',
        __('Storefront Appearance', 'kangoo'),
        'kangoo_theme_appearance_section_callback',
        'kangoo-theme-appearance'
    );

    add_settings_field(
        'kangoo_theme_appearance',
        __('Theme Appearance', 'kangoo'),
        'kangoo_theme_appearance_field',
        'kangoo-theme-appearance',
        'kangoo_theme_appearance_section'
    );
}
add_action('admin_init', 'kangoo_register_theme_appearance_fallback_settings');

function kangoo_theme_appearance_section_callback() {
    echo '<p>' . esc_html__('Dark remains the rollback-safe default. Light-first can be previewed by logged-in admins with ?kangoo_theme_preview=light before changing the saved setting.', 'kangoo') . '</p>';
}

function kangoo_theme_appearance_field() {
    $active = kangoo_get_saved_theme_appearance();
    ?>
    <select id="kangoo_theme_appearance" name="kangoo_theme_appearance">
        <?php foreach (kangoo_theme_appearance_presets() as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($active, $value); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

function kangoo_should_show_theme_appearance_fallback_page() {
    return !function_exists('get_field') && !function_exists('acf_add_local_field_group');
}

function kangoo_theme_appearance_fallback_menu() {
    if (!kangoo_should_show_theme_appearance_fallback_page()) {
        return;
    }

    add_theme_page(
        __('Theme Appearance', 'kangoo'),
        __('Theme Appearance', 'kangoo'),
        'manage_options',
        'kangoo-theme-appearance',
        'kangoo_render_theme_appearance_fallback_page'
    );
}
add_action('admin_menu', 'kangoo_theme_appearance_fallback_menu');

function kangoo_render_theme_appearance_fallback_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Theme Appearance', 'kangoo'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('kangoo_theme_appearance_options');
            do_settings_sections('kangoo-theme-appearance');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
