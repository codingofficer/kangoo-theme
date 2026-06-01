<?php
if (!defined('ABSPATH')) {
    exit;
}

function kangoo_event_theme_presets() {
    return array(
        'none'               => __('None', 'kangoo'),
        'football_world_cup' => __('Football / World Cup', 'kangoo'),
        'christmas'          => __('Christmas', 'kangoo'),
        'easter'             => __('Easter', 'kangoo'),
        'winter'             => __('Winter', 'kangoo'),
        'spring'             => __('Spring', 'kangoo'),
        'autumn'             => __('Autumn', 'kangoo'),
        'summer'             => __('Summer', 'kangoo'),
    );
}

function kangoo_normalize_event_theme($theme) {
    $theme = sanitize_key(str_replace('-', '_', (string) $theme));
    $presets = kangoo_event_theme_presets();

    return array_key_exists($theme, $presets) ? $theme : 'none';
}

function kangoo_get_active_event_theme() {
    $theme = '';

    if (function_exists('get_field')) {
        $acf_theme = get_field('kangoo_active_event_theme', 'option');

        if (is_string($acf_theme) && trim($acf_theme) !== '') {
            $theme = $acf_theme;
        }
    }

    if ($theme === '') {
        $theme = get_option('kangoo_active_event_theme', 'none');
    }

    return kangoo_normalize_event_theme($theme);
}

function kangoo_is_event_theme_active() {
    return kangoo_get_active_event_theme() !== 'none';
}

function kangoo_render_product_card_event_decoration() {
    $theme = kangoo_get_active_event_theme();

    if ($theme === 'none') {
        return;
    }

    printf(
        '<span class="kangoo-event-decor kangoo-event-decor--%s" aria-hidden="true"></span>',
        esc_attr(sanitize_html_class($theme))
    );
}

function kangoo_event_theme_body_class($classes) {
    $theme = kangoo_get_active_event_theme();

    if ($theme !== 'none') {
        $classes[] = 'kangoo-event-theme';
        $classes[] = 'kangoo-event-theme--' . sanitize_html_class($theme);
    }

    return $classes;
}
add_filter('body_class', 'kangoo_event_theme_body_class');

function kangoo_register_event_theme_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_kangoo_event_themes',
        'title' => __('Event Theme', 'kangoo'),
        'fields' => array(
            array(
                'key' => 'field_kangoo_active_event_theme',
                'label' => __('Active Event Theme', 'kangoo'),
                'name' => 'kangoo_active_event_theme',
                'type' => 'select',
                'instructions' => __('Choose one subtle product-card decoration theme. Select None to disable event styling.', 'kangoo'),
                'choices' => kangoo_event_theme_presets(),
                'default_value' => 'none',
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
add_action('acf/init', 'kangoo_register_event_theme_acf_fields');

function kangoo_sanitize_event_theme($theme) {
    return kangoo_normalize_event_theme($theme);
}

function kangoo_register_event_theme_fallback_settings() {
    register_setting('kangoo_event_theme_options', 'kangoo_active_event_theme', array(
        'sanitize_callback' => 'kangoo_sanitize_event_theme',
        'default'           => 'none',
    ));

    add_settings_section(
        'kangoo_event_theme_options_section',
        __('Seasonal Product Card Theme', 'kangoo'),
        'kangoo_event_theme_options_section_callback',
        'kangoo-event-themes'
    );

    add_settings_field(
        'kangoo_active_event_theme',
        __('Active Event Theme', 'kangoo'),
        'kangoo_active_event_theme_field',
        'kangoo-event-themes',
        'kangoo_event_theme_options_section'
    );
}
add_action('admin_init', 'kangoo_register_event_theme_fallback_settings');

function kangoo_event_theme_options_section_callback() {
    echo '<p>' . esc_html__('Choose one subtle event decoration for product cards. Select None to disable seasonal styling.', 'kangoo') . '</p>';
}

function kangoo_active_event_theme_field() {
    $active = kangoo_get_active_event_theme();
    ?>
    <select id="kangoo_active_event_theme" name="kangoo_active_event_theme">
        <?php foreach (kangoo_event_theme_presets() as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($active, $value); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

function kangoo_should_show_event_theme_fallback_page() {
    return !function_exists('get_field') && !function_exists('acf_add_local_field_group');
}

function kangoo_event_theme_fallback_menu() {
    if (!kangoo_should_show_event_theme_fallback_page()) {
        return;
    }

    add_theme_page(
        __('Event Themes', 'kangoo'),
        __('Event Themes', 'kangoo'),
        'manage_options',
        'kangoo-event-themes',
        'kangoo_render_event_theme_fallback_page'
    );
}
add_action('admin_menu', 'kangoo_event_theme_fallback_menu');

function kangoo_render_event_theme_fallback_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Event Themes', 'kangoo'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('kangoo_event_theme_options');
            do_settings_sections('kangoo-event-themes');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
