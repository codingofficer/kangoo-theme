<?php
/* FILE: header.php */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$kangoo_age_gate_mode = isset($_GET['age_gate']) ? sanitize_key(wp_unslash($_GET['age_gate'])) : '';
$kangoo_age_gate_preview = in_array($kangoo_age_gate_mode, array('preview', 'reset'), true);
$kangoo_age_gate_settings = function_exists('kangoo_get_age_gate_settings') ? kangoo_get_age_gate_settings() : array();
?>
<?php if (function_exists('kangoo_is_age_gate_enabled') && (kangoo_is_age_gate_enabled() || $kangoo_age_gate_preview)) : ?>
    <div class="kangoo-age-gate" id="kangoo-age-gate" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="kangoo-age-gate-title" aria-describedby="kangoo-age-gate-description">
        <div class="kangoo-age-gate__ambient" aria-hidden="true"></div>
        <div class="kangoo-age-gate__panel">
            <div class="kangoo-age-gate__brand">
                <span class="kangoo-age-gate__brand-mark" aria-hidden="true">18+</span>
                <img
                    class="kangoo-age-gate__logo"
                    src="<?php echo esc_url('https://kangoopouches.co.uk/wp-content/uploads/2026/04/cropped-cropped-kangoo-pouches-logo-orange-white.png'); ?>"
                    alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                    loading="eager"
                    decoding="async"
                >
            </div>

            <div class="kangoo-age-gate__screen" data-age-gate-confirm-screen>
                <span class="kangoo-age-gate__eyebrow"><?php esc_html_e('Age check', 'kangoo'); ?></span>
                <h2 class="kangoo-age-gate__title" id="kangoo-age-gate-title"><?php echo esc_html($kangoo_age_gate_settings['title'] ?? __('Confirm your age', 'kangoo')); ?></h2>
                <p class="kangoo-age-gate__copy" id="kangoo-age-gate-description">
                    <?php echo esc_html($kangoo_age_gate_settings['message'] ?? __('Please confirm you are 18 or over to browse Kangoo Pouches.', 'kangoo')); ?>
                </p>

                <div class="kangoo-age-gate__actions">
                    <button type="button" class="kangoo-age-gate__button kangoo-age-gate__button--primary" data-age-gate-accept><?php esc_html_e('I am 18 or over', 'kangoo'); ?></button>
                    <button type="button" class="kangoo-age-gate__button kangoo-age-gate__button--secondary" data-age-gate-reject><?php esc_html_e('I am under 18', 'kangoo'); ?></button>
                </div>

                <p class="kangoo-age-gate__smallprint">
                    <?php echo esc_html($kangoo_age_gate_settings['smallprint'] ?? __('Nicotine products are not for anyone under 18.', 'kangoo')); ?>
                </p>
            </div>

            <div class="kangoo-age-gate__screen kangoo-age-gate__screen--blocked" data-age-gate-blocked-screen hidden>
                <span class="kangoo-age-gate__eyebrow"><?php esc_html_e('Age check', 'kangoo'); ?></span>
                <h2 class="kangoo-age-gate__title"><?php esc_html_e('Thanks for checking.', 'kangoo'); ?></h2>
                <p class="kangoo-age-gate__copy">
                    <?php esc_html_e('This store is only available to visitors aged 18 or over.', 'kangoo'); ?>
                </p>
                <button type="button" class="kangoo-age-gate__button kangoo-age-gate__button--primary kangoo-age-gate__leave" data-age-gate-leave><?php esc_html_e('Leave site', 'kangoo'); ?></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$kangoo_mega_menu = function_exists('kangoo_get_mega_menu_settings')
    ? kangoo_get_mega_menu_settings()
    : array();

$kangoo_mega_enabled = !empty($kangoo_mega_menu['enabled']);

$kangoo_trigger_label   = isset($kangoo_mega_menu['trigger_label']) ? (string) $kangoo_mega_menu['trigger_label'] : '';
$kangoo_mobile_title    = isset($kangoo_mega_menu['mobile_drawer_title']) && $kangoo_mega_menu['mobile_drawer_title'] !== ''
    ? (string) $kangoo_mega_menu['mobile_drawer_title']
    : __('Browse', 'kangoo');
$kangoo_99p_pouches_url = home_url('/product-category/99p-pouches/');

if (function_exists('get_term_link')) {
    $kangoo_99p_term_link = get_term_link('99p-pouches', 'product_cat');

    if (!is_wp_error($kangoo_99p_term_link)) {
        $kangoo_99p_pouches_url = $kangoo_99p_term_link;
    }
}

$kangoo_top_links       = !empty($kangoo_mega_menu['top_links']) && is_array($kangoo_mega_menu['top_links']) ? $kangoo_mega_menu['top_links'] : array();
$kangoo_brand_cards     = !empty($kangoo_mega_menu['brand_cards']) && is_array($kangoo_mega_menu['brand_cards']) ? $kangoo_mega_menu['brand_cards'] : array();
$kangoo_strength_cards  = !empty($kangoo_mega_menu['strength_cards']) && is_array($kangoo_mega_menu['strength_cards']) ? $kangoo_mega_menu['strength_cards'] : array();
$kangoo_type_cards      = !empty($kangoo_mega_menu['type_cards']) && is_array($kangoo_mega_menu['type_cards']) ? $kangoo_mega_menu['type_cards'] : array();
$kangoo_flavour_cards   = !empty($kangoo_mega_menu['flavour_cards']) && is_array($kangoo_mega_menu['flavour_cards']) ? $kangoo_mega_menu['flavour_cards'] : array();
$kangoo_mobile_sections = !empty($kangoo_mega_menu['mobile_sections']) && is_array($kangoo_mega_menu['mobile_sections']) ? $kangoo_mega_menu['mobile_sections'] : array();
$kangoo_theme_menu_toggle_html = function_exists('kangoo_get_theme_menu_toggle_html') ? kangoo_get_theme_menu_toggle_html() : '';

$kangoo_normalize_panel_key = static function ($panel_key) {
    $value = strtolower(trim((string) $panel_key));

    if (in_array($value, array('brand', 'brands'), true)) {
        return 'brands';
    }

    if (in_array($value, array('strength', 'strengths'), true)) {
        return 'strengths';
    }

    if (in_array($value, array('type', 'types'), true)) {
        return 'types';
    }

    if (in_array($value, array('flavour', 'flavours', 'flavor', 'flavors'), true)) {
        return 'flavours';
    }

    return $value;
};

$kangoo_sidebar_links = array();
if (!empty($kangoo_mega_menu['desktop_sidebar_links']) && is_array($kangoo_mega_menu['desktop_sidebar_links'])) {
    foreach ($kangoo_mega_menu['desktop_sidebar_links'] as $item) {
        if (!is_array($item)) {
            continue;
        }

        $label  = trim((string) ($item['label'] ?? ''));
        $type   = strtolower(trim((string) ($item['type'] ?? 'panel')));
        $panel  = $kangoo_normalize_panel_key($item['panel_key'] ?? $item['panel'] ?? '');
        $link   = isset($item['link']) ? $item['link'] : (isset($item['url']) ? $item['url'] : array());

        if ($label === '') {
            continue;
        }

        if ($label === '') {
            continue;
        }

        $kangoo_sidebar_links[] = array(
            'label'     => $label,
            'type'      => $type,
            'panel_key' => $panel,
            'link'      => $link,
        );
    }
}

$has_valid_sidebar_links = false;
foreach ($kangoo_sidebar_links as $item) {
    if ($item['type'] === 'panel' && $item['panel_key'] !== '') {
        $has_valid_sidebar_links = true;
        break;
    }

    if ($item['type'] === 'link' && function_exists('kangoo_acf_link_url') && kangoo_acf_link_url($item['link']) !== '') {
        $has_valid_sidebar_links = true;
        break;
    }
}

if (!$has_valid_sidebar_links) {
    $kangoo_sidebar_links = array(
        array('label' => __('Brands', 'kangoo'), 'type' => 'panel', 'panel_key' => 'brands', 'link' => array()),
        array('label' => __('Strengths', 'kangoo'), 'type' => 'panel', 'panel_key' => 'strengths', 'link' => array()),
        array('label' => __('Flavours', 'kangoo'), 'type' => 'panel', 'panel_key' => 'flavours', 'link' => array()),
        array('label' => __('Types', 'kangoo'), 'type' => 'panel', 'panel_key' => 'types', 'link' => array()),
    );
}

$panel_order = array('brands', 'strengths', 'flavours', 'types');
$panel_links = array();
$non_panel_links = array();

foreach ($kangoo_sidebar_links as $item) {
    if (isset($item['type']) && $item['type'] === 'panel' && isset($item['panel_key']) && in_array($item['panel_key'], $panel_order, true)) {
        $panel_links[] = $item;
        continue;
    }

    $non_panel_links[] = $item;
}

usort($panel_links, static function ($a, $b) use ($panel_order) {
    return array_search($a['panel_key'], $panel_order, true) <=> array_search($b['panel_key'], $panel_order, true);
});

$kangoo_sidebar_links = array_merge($panel_links, $non_panel_links);

$kangoo_available_panels = array('brands', 'strengths', 'types', 'flavours');
$kangoo_default_panel    = 'brands';

foreach ($kangoo_sidebar_links as $kangoo_item) {
    if (
        is_array($kangoo_item) &&
        isset($kangoo_item['type'], $kangoo_item['panel_key']) &&
        $kangoo_item['type'] === 'panel' &&
        $kangoo_item['panel_key'] !== ''
    ) {
        $candidate = $kangoo_normalize_panel_key($kangoo_item['panel_key']);

        if (in_array($candidate, $kangoo_available_panels, true)) {
            $kangoo_default_panel = $candidate;
            break;
        }
    }
}

if (current_user_can('manage_options')) {
    echo "\n<!-- mega-enabled: " . ($kangoo_mega_enabled ? 'yes' : 'no') . " -->\n";
}
?>
	
<?php if (current_user_can('manage_options')) : ?>
    <!-- mega-enabled: <?php echo !empty($kangoo_mega_menu['enabled']) ? 'yes' : 'no'; ?> -->
    <!-- mega-desktop-links-count: <?php echo isset($kangoo_mega_menu['desktop_sidebar_links']) && is_array($kangoo_mega_menu['desktop_sidebar_links']) ? count($kangoo_mega_menu['desktop_sidebar_links']) : 0; ?> -->
    <!-- mega-brand-cards-count: <?php echo isset($kangoo_mega_menu['brand_cards']) && is_array($kangoo_mega_menu['brand_cards']) ? count($kangoo_mega_menu['brand_cards']) : 0; ?> -->
    <!-- mega-strength-cards-count: <?php echo isset($kangoo_mega_menu['strength_cards']) && is_array($kangoo_mega_menu['strength_cards']) ? count($kangoo_mega_menu['strength_cards']) : 0; ?> -->
    <!-- mega-flavour-cards-count: <?php echo isset($kangoo_mega_menu['flavour_cards']) && is_array($kangoo_mega_menu['flavour_cards']) ? count($kangoo_mega_menu['flavour_cards']) : 0; ?> -->
<?php endif; ?>

<header class="site-header">
    <div class="site-header__inner">
        <div class="container">
            <div class="site-logo">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <?php bloginfo('name'); ?>
                    </a>
                <?php endif; ?>
            </div>

            <nav class="site-nav" aria-label="<?php esc_attr_e('Primary Menu', 'kangoo'); ?>">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'site-nav__menu',
                    'fallback_cb'    => false,
                ));
                ?>
            </nav>

            <div class="site-header__actions">
                <button
                    type="button"
                    class="site-header__search"
                    data-search-open
                    aria-controls="kangoo-search-overlay"
                    aria-expanded="false"
                    aria-label="<?php esc_attr_e('Open search', 'kangoo'); ?>"
                >
                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                        <path d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>

                <button
                    type="button"
                    class="site-header__account"
                    data-account-open="login"
                    aria-label="<?php esc_attr_e('Open account panel', 'kangoo'); ?>"
                >
                    <span class="site-header__account-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm0 2.25c-4.14 0-7.5 2.59-7.5 5.78V21h15v-.97c0-3.19-3.36-5.78-7.5-5.78Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="site-header__account-text"><?php esc_html_e('Account', 'kangoo'); ?></span>
                </button>

                <?php if ($kangoo_mega_enabled) : ?>
                    <button
                        type="button"
                        class="site-header__menu-toggle"
                        id="header-menu-toggle"
                        data-mega-menu-open
                        aria-controls="kangoo-mega-menu-drawer"
                        aria-expanded="false"
                        aria-label="<?php esc_attr_e('Open menu', 'kangoo'); ?>"
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                <?php endif; ?>

                <?php if (function_exists('WC') && function_exists('wc_get_cart_url')) : ?>
                    <button
                        type="button"
                        class="site-header__cart"
                        id="header-cart-trigger"
                        aria-label="<?php esc_attr_e('Open cart', 'kangoo'); ?>"
                    >
                        <span class="cart-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="M6 6h15l-1.5 8.5a2 2 0 0 1-2 1.5H9a2 2 0 0 1-2-1.3L4.3 4H2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="9" cy="20" r="1.75" fill="currentColor"/>
                                <circle cx="18" cy="20" r="1.75" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="cart-badge">
                            <?php echo function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0; ?>
                        </span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="kangoo-search" id="kangoo-search-overlay" aria-hidden="true">
        <div class="kangoo-search__backdrop" data-search-close></div>
        <div class="kangoo-search__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Search Kangoo Pouches', 'kangoo'); ?>">
            <div class="kangoo-search__header">
                <div>
                    <span class="kangoo-search__eyebrow"><?php esc_html_e('Search', 'kangoo'); ?></span>
                    <h2><?php esc_html_e('Find products fast', 'kangoo'); ?></h2>
                </div>

                <button type="button" class="kangoo-search__close" data-search-close aria-label="<?php esc_attr_e('Close search', 'kangoo'); ?>">
                    &times;
                </button>
            </div>

            <form class="kangoo-search__form" role="search" action="<?php echo esc_url(home_url('/')); ?>" method="get" data-search-form>
                <label class="screen-reader-text" for="kangoo-search-input"><?php esc_html_e('Search products and guides', 'kangoo'); ?></label>
                <input id="kangoo-search-input" type="search" name="s" autocomplete="off" placeholder="<?php esc_attr_e('Search VELO, ZYN, mint, strong...', 'kangoo'); ?>" data-search-input>
                <button type="submit"><?php esc_html_e('Search', 'kangoo'); ?></button>
            </form>

            <div class="kangoo-search__popular" data-search-popular>
                <span><?php esc_html_e('Popular', 'kangoo'); ?></span>
                <button type="button" data-search-suggestion="VELO">VELO</button>
                <button type="button" data-search-suggestion="ZYN">ZYN</button>
                <button type="button" data-search-suggestion="Mint">Mint</button>
                <button type="button" data-search-suggestion="Strong">Strong</button>
                <button type="button" data-search-suggestion="Berry">Berry</button>
            </div>

            <div class="kangoo-search__results" data-search-results>
                <p class="kangoo-search__empty"><?php esc_html_e('Start typing to search products and guides.', 'kangoo'); ?></p>
            </div>
        </div>
    </div>

    <?php if ($kangoo_mega_enabled) : ?>
        <div
            class="kangoo-mega-menu"
            id="kangoo-mega-menu-desktop"
            data-trigger-label="<?php echo esc_attr(strtolower(trim($kangoo_trigger_label))); ?>"
        >
            <div class="container">
                <div class="kangoo-mega-menu__grid">
                    <aside class="kangoo-mega-menu__sidebar">
                        <?php foreach ($kangoo_sidebar_links as $item) :
                            $label  = isset($item['label']) ? (string) $item['label'] : '';
                            $type   = isset($item['type']) ? strtolower(trim((string) $item['type'])) : 'link';
                            $panel  = isset($item['panel_key']) ? strtolower(trim((string) $item['panel_key'])) : '';
                            $link   = isset($item['link']) ? $item['link'] : array();
                            $url    = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($link) : '';
                            $target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($link) : '_self';

                            if ($label === '') {
                                continue;
                            }
                            ?>
                            <?php if ($type === 'panel' && $panel !== '') : ?>
                                <button
                                    type="button"
                                    class="kangoo-mega-menu__sidebar-link<?php echo $panel === $kangoo_default_panel ? ' is-active' : ''; ?>"
                                    data-mega-panel-trigger="<?php echo esc_attr($kangoo_normalize_panel_key($panel)); ?>"
                                >
                                    <?php echo esc_html($label); ?>
                                </button>
                            <?php elseif ($url !== '') : ?>
                                <a
                                    href="<?php echo esc_url($url); ?>"
                                    target="<?php echo esc_attr($target); ?>"
                                    class="kangoo-mega-menu__sidebar-link"
                                >
                                    <?php echo esc_html($label); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <a
                            href="<?php echo esc_url($kangoo_99p_pouches_url); ?>"
                            class="kangoo-mega-menu__sidebar-link kangoo-mega-menu__sidebar-link--99p"
                        >
                            <?php esc_html_e('99p Pouches', 'kangoo'); ?>
                        </a>
                        <a
                            href="<?php echo esc_url(home_url('/kangoo-app/')); ?>"
                            class="kangoo-mega-menu__sidebar-link kangoo-mega-menu__sidebar-link--app"
                        >
                            <?php esc_html_e('Kangoo App', 'kangoo'); ?>
                        </a>
                        <?php if ($kangoo_theme_menu_toggle_html !== '') : ?>
                            <?php echo $kangoo_theme_menu_toggle_html; ?>
                        <?php endif; ?>
                    </aside>

                    <div class="kangoo-mega-menu__panels">
                        <section
                            class="kangoo-mega-menu__panel<?php echo $kangoo_default_panel === 'brands' ? ' is-active' : ''; ?>"
                            data-mega-panel="brands"
                        >
                            <?php if (!empty($kangoo_mega_menu['brands_panel_title'])) : ?>
                                <h3><?php echo esc_html($kangoo_mega_menu['brands_panel_title']); ?></h3>
                            <?php endif; ?>

                            <div class="kangoo-mega-menu__brand-grid">
                                <?php foreach ($kangoo_brand_cards as $card) :
                                    $label    = isset($card['label']) ? (string) $card['label'] : '';
                                    $link     = isset($card['link']) ? $card['link'] : array();
                                    $url      = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($link) : '';
                                    $target   = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($link) : '_self';
                                    $image    = isset($card['image']) && is_array($card['image']) ? $card['image'] : array();
                                    $featured = !empty($card['featured']);
                                    $badge    = isset($card['badge_text']) ? (string) $card['badge_text'] : '';

                                    if ($label === '' || $url === '') {
                                        continue;
                                    }
                                    ?>
                                    <a
                                        href="<?php echo esc_url($url); ?>"
                                        target="<?php echo esc_attr($target); ?>"
                                        class="kangoo-mega-menu__brand-card<?php echo $featured ? ' is-featured' : ''; ?>"
                                    >
                                        <?php if ($featured && $badge !== '') : ?>
                                            <span class="kangoo-mega-menu__brand-badge"><?php echo esc_html($badge); ?></span>
                                        <?php endif; ?>

                                        <?php if (!empty($image['url'])) : ?>
                                            <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($label); ?>">
                                        <?php endif; ?>

                                        <span><?php echo esc_html($label); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <?php
                            $brands_view_all_link   = isset($kangoo_mega_menu['brands_view_all_link']) ? $kangoo_mega_menu['brands_view_all_link'] : array();
                            $brands_view_all_url    = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($brands_view_all_link) : '';
                            $brands_view_all_target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($brands_view_all_link) : '_self';
                            $brands_view_all_label  = isset($kangoo_mega_menu['brands_view_all_label']) ? (string) $kangoo_mega_menu['brands_view_all_label'] : '';
                            ?>

                            <?php if ($brands_view_all_url !== '' && $brands_view_all_label !== '') : ?>
                                <a
                                    href="<?php echo esc_url($brands_view_all_url); ?>"
                                    target="<?php echo esc_attr($brands_view_all_target); ?>"
                                    class="kangoo-mega-menu__view-all"
                                >
                                    <?php echo esc_html($brands_view_all_label); ?>
                                </a>
                            <?php endif; ?>
                        </section>

                        <section
                            class="kangoo-mega-menu__panel<?php echo $kangoo_default_panel === 'strengths' ? ' is-active' : ''; ?>"
                            data-mega-panel="strengths"
                        >
                            <?php if (!empty($kangoo_mega_menu['strengths_panel_title'])) : ?>
                                <h3><?php echo esc_html($kangoo_mega_menu['strengths_panel_title']); ?></h3>
                            <?php endif; ?>

                            <div class="kangoo-mega-menu__strength-grid">
                                <?php foreach ($kangoo_strength_cards as $card) :
                                    $label  = isset($card['label']) ? (string) $card['label'] : '';
                                    $desc   = isset($card['description']) ? (string) $card['description'] : '';
                                    $mg     = isset($card['mg_range']) ? (string) $card['mg_range'] : '';
                                    $link   = isset($card['link']) ? $card['link'] : array();
                                    $url    = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($link) : '';
                                    $target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($link) : '_self';
                                    $dots   = isset($card['dots_on']) ? max(0, min(4, (int) $card['dots_on'])) : 0;
                                    $color  = isset($card['dot_color']) ? (string) $card['dot_color'] : '#4da3ff';

                                    if ($label === '' || $url === '') {
                                        continue;
                                    }
                                    ?>
                                    <a
                                        href="<?php echo esc_url($url); ?>"
                                        target="<?php echo esc_attr($target); ?>"
                                        class="kangoo-mega-menu__strength-card"
                                    >
                                        <strong><?php echo esc_html($label); ?></strong>
                                        <?php if ($desc !== '') : ?><span><?php echo esc_html($desc); ?></span><?php endif; ?>
                                        <?php if ($mg !== '') : ?><span><?php echo esc_html($mg); ?></span><?php endif; ?>

                                        <span class="kangoo-mega-menu__dots">
                                            <?php for ($i = 1; $i <= 4; $i++) : ?>
                                                <span<?php echo $i <= $dots ? ' style="background:' . esc_attr($color) . ';border-color:' . esc_attr($color) . ';"' : ''; ?>></span>
                                            <?php endfor; ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section
                            class="kangoo-mega-menu__panel<?php echo $kangoo_default_panel === 'types' ? ' is-active' : ''; ?>"
                            data-mega-panel="types"
                        >
                            <?php if (!empty($kangoo_mega_menu['types_panel_title'])) : ?>
                                <h3><?php echo esc_html($kangoo_mega_menu['types_panel_title']); ?></h3>
                            <?php endif; ?>

                            <div class="kangoo-mega-menu__type-grid">
                                <?php foreach ($kangoo_type_cards as $card) :
                                    $label  = isset($card['label']) ? (string) $card['label'] : '';
                                    $link   = isset($card['link']) ? $card['link'] : array();
                                    $url    = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($link) : '';
                                    $target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($link) : '_self';
                                    $bg     = isset($card['background_color']) ? (string) $card['background_color'] : '#1b1d23';
                                    $text   = isset($card['text_color']) ? (string) $card['text_color'] : '#ffffff';
                                    $icon   = isset($card['icon']) && is_array($card['icon']) ? $card['icon'] : array();

                                    if ($label === '' || $url === '') {
                                        continue;
                                    }
                                    ?>
                                    <a
                                        href="<?php echo esc_url($url); ?>"
                                        target="<?php echo esc_attr($target); ?>"
                                        class="kangoo-mega-menu__type-card"
                                        style="background:<?php echo esc_attr($bg); ?>;color:<?php echo esc_attr($text); ?>;"
                                    >
                                        <?php if (!empty($icon['url'])) : ?>
                                            <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($label); ?>">
                                        <?php endif; ?>

                                        <span><?php echo esc_html($label); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section
                            class="kangoo-mega-menu__panel<?php echo $kangoo_default_panel === 'flavours' ? ' is-active' : ''; ?>"
                            data-mega-panel="flavours"
                        >
                            <?php if (!empty($kangoo_mega_menu['flavours_panel_title'])) : ?>
                                <h3><?php echo esc_html($kangoo_mega_menu['flavours_panel_title']); ?></h3>
                            <?php endif; ?>

                            <div class="kangoo-mega-menu__flavour-grid">
                                <?php foreach ($kangoo_flavour_cards as $card) :
                                    $label  = isset($card['label']) ? (string) $card['label'] : '';
                                    $link   = isset($card['link']) ? $card['link'] : array();
                                    $url    = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($link) : '';
                                    $target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($link) : '_self';
                                    $bg     = isset($card['background_color']) ? (string) $card['background_color'] : '#1b1d23';
                                    $text   = isset($card['text_color']) ? (string) $card['text_color'] : '#ffffff';
                                    $icon   = isset($card['icon']) && is_array($card['icon']) ? $card['icon'] : array();

                                    if ($label === '' || $url === '') {
                                        continue;
                                    }
                                    ?>
                                    <a
                                        href="<?php echo esc_url($url); ?>"
                                        target="<?php echo esc_attr($target); ?>"
                                        class="kangoo-mega-menu__flavour-card"
                                        style="background:<?php echo esc_attr($bg); ?>;color:<?php echo esc_attr($text); ?>;"
                                    >
                                        <?php if (!empty($icon['url'])) : ?>
                                            <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($label); ?>">
                                        <?php endif; ?>

                                        <span><?php echo esc_html($label); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>

        <div class="kangoo-mega-drawer" id="kangoo-mega-menu-drawer" aria-hidden="true">
            <div class="kangoo-mega-drawer__overlay" data-mega-menu-close></div>

            <div class="kangoo-mega-drawer__panel">
                <div class="kangoo-mega-drawer__header">
                    <strong><?php echo esc_html($kangoo_mobile_title); ?></strong>
                    <button
                        type="button"
                        class="kangoo-mega-drawer__close"
                        data-mega-menu-close
                        aria-label="<?php esc_attr_e('Close menu', 'kangoo'); ?>"
                    >×</button>
                </div>

                <?php if (!empty($kangoo_top_links)) : ?>
                    <div class="kangoo-mega-drawer__top-links">
                        <?php foreach ($kangoo_top_links as $item) :
                            $label  = isset($item['label']) ? (string) $item['label'] : '';
                            $link   = isset($item['link']) ? $item['link'] : array();
                            $url    = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($link) : '';
                            $target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($link) : '_self';

                            if ($label === '' || $url === '') {
                                continue;
                            }
                            ?>
                            <a href="<?php echo esc_url($url); ?>" target="<?php echo esc_attr($target); ?>">
                                <?php echo esc_html($label); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="kangoo-mega-drawer__sections">
                    <?php foreach ($kangoo_mobile_sections as $section) :
                        $label  = isset($section['label']) ? (string) $section['label'] : '';
                        $source = isset($section['source']) ? $section['source'] : '';
                        $source = function_exists('kangoo_normalize_mega_menu_source')
                            ? kangoo_normalize_mega_menu_source($source)
                            : sanitize_title((string) $source);

                        $label_source = $label !== '' && function_exists('kangoo_normalize_mega_menu_source')
                            ? kangoo_normalize_mega_menu_source($label)
                            : '';

                        if (
                            $label_source !== ''
                            && !in_array($source, array('brands', 'strengths', 'types', 'flavours'), true)
                        ) {
                            $source = $label_source;
                        }

                        if ($source === '' && $label !== '') {
                            $source = function_exists('kangoo_normalize_mega_menu_source')
                                ? kangoo_normalize_mega_menu_source($label)
                                : sanitize_title($label);
                        }

                        $open   = !empty($section['open_by_default']);

                        if ($label === '' || $source === '') {
                            continue;
                        }
                        ?>
                        <details class="kangoo-mega-drawer__section"<?php echo $open ? ' open' : ''; ?>>
                            <summary><?php echo esc_html($label); ?></summary>

                            <div class="kangoo-mega-drawer__section-body">
                                <?php if ($source === 'brands') : ?>
                                    <div class="kangoo-mega-drawer__brand-list">
                                        <?php foreach ($kangoo_brand_cards as $card) :
                                            $card_label  = isset($card['label']) ? (string) $card['label'] : '';
                                            $card_link   = isset($card['link']) ? $card['link'] : array();
                                            $card_url    = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($card_link) : '';
                                            $card_target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($card_link) : '_self';

                                            if ($card_label === '' || $card_url === '') {
                                                continue;
                                            }
                                            ?>
											<a href="<?php echo esc_url($card_url); ?>" target="<?php echo esc_attr($card_target); ?>">
												<?php if (!empty($card['image']['url'])) : ?>
													<img src="<?php echo esc_url($card['image']['url']); ?>" alt="<?php echo esc_attr($card_label); ?>">
												<?php endif; ?>

												<span><?php echo esc_html($card_label); ?></span>
											</a>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($source === 'strengths') : ?>
                                    <div class="kangoo-mega-drawer__strength-list">
                                        <?php foreach ($kangoo_strength_cards as $card) :
                                            $card_label  = isset($card['label']) ? (string) $card['label'] : '';
                                            $card_desc   = isset($card['description']) ? (string) $card['description'] : '';
                                            $card_mg     = isset($card['mg_range']) ? (string) $card['mg_range'] : '';
                                            $card_dots   = isset($card['dots_on']) ? max(0, min(4, (int) $card['dots_on'])) : 0;
                                            $card_color  = isset($card['dot_color']) ? (string) $card['dot_color'] : '#4da3ff';
                                            $card_link   = isset($card['link']) ? $card['link'] : array();
                                            $card_url    = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($card_link) : '';
                                            $card_target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($card_link) : '_self';

                                            if ($card_label === '' || $card_url === '') {
                                                continue;
                                            }
                                            ?>
                                            <a href="<?php echo esc_url($card_url); ?>" target="<?php echo esc_attr($card_target); ?>" class="kangoo-mega-drawer__strength-item">
                                                <div class="kangoo-mega-drawer__strength-header">
                                                    <span class="kangoo-mega-drawer__strength-badge" style="background: <?php echo esc_attr($card_color); ?>;"></span>
                                                    <strong><?php echo esc_html($card_label); ?></strong>
                                                </div>
                                                <?php if ($card_desc !== '') : ?><p class="kangoo-mega-drawer__strength-copy"><?php echo esc_html($card_desc); ?></p><?php endif; ?>
                                                <?php if ($card_mg !== '') : ?><span class="kangoo-mega-drawer__strength-mg"><?php echo esc_html($card_mg); ?></span><?php endif; ?>

                                                <span class="kangoo-mega-drawer__dots">
                                                    <?php for ($i = 1; $i <= 4; $i++) : ?>
                                                        <span<?php echo $i <= $card_dots ? ' style="background:' . esc_attr($card_color) . ';border-color:' . esc_attr($card_color) . ';"' : ''; ?>></span>
                                                    <?php endfor; ?>
                                                </span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($source === 'types') : ?>
                                    <div class="kangoo-mega-drawer__type-list">
                                        <?php foreach ($kangoo_type_cards as $card) :
                                            $card_label  = isset($card['label']) ? (string) $card['label'] : '';
                                            $card_link   = isset($card['link']) ? $card['link'] : array();
                                            $card_url    = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($card_link) : '';
                                            $card_target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($card_link) : '_self';
                                            $card_bg     = isset($card['background_color']) ? (string) $card['background_color'] : '#1b1d23';
                                            $card_text   = isset($card['text_color']) ? (string) $card['text_color'] : '#ffffff';

                                            if ($card_label === '' || $card_url === '') {
                                                continue;
                                            }
                                            ?>
                                            <a
                                                href="<?php echo esc_url($card_url); ?>"
                                                target="<?php echo esc_attr($card_target); ?>"
                                                class="kangoo-mega-drawer__type-item"
                                                style="background:<?php echo esc_attr($card_bg); ?>;color:<?php echo esc_attr($card_text); ?>;"
                                            >
                                                <?php echo esc_html($card_label); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($source === 'flavours') : ?>
                                    <div class="kangoo-mega-drawer__flavour-list">
                                        <?php foreach ($kangoo_flavour_cards as $card) :
                                            $card_label  = isset($card['label']) ? (string) $card['label'] : '';
                                            $card_link   = isset($card['link']) ? $card['link'] : array();
                                            $card_url    = function_exists('kangoo_acf_link_url') ? kangoo_acf_link_url($card_link) : '';
                                            $card_target = function_exists('kangoo_acf_link_target') ? kangoo_acf_link_target($card_link) : '_self';
                                            $card_bg     = isset($card['background_color']) ? (string) $card['background_color'] : '#1b1d23';
                                            $card_text   = isset($card['text_color']) ? (string) $card['text_color'] : '#ffffff';
                                            $card_icon   = isset($card['icon']) && is_array($card['icon']) ? $card['icon'] : array();

                                            if ($card_label === '' || $card_url === '') {
                                                continue;
                                            }
                                            ?>
                                            <a
                                                href="<?php echo esc_url($card_url); ?>"
                                                target="<?php echo esc_attr($card_target); ?>"
                                                style="background:<?php echo esc_attr($card_bg); ?>;color:<?php echo esc_attr($card_text); ?>;"
                                            >
                                                <?php if (!empty($card_icon['url'])) : ?>
                                                    <img src="<?php echo esc_url($card_icon['url']); ?>" alt="<?php echo esc_attr($card_label); ?>">
                                                <?php endif; ?>

                                                <span><?php echo esc_html($card_label); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                    <a
                        href="<?php echo esc_url($kangoo_99p_pouches_url); ?>"
                        class="kangoo-mega-drawer__direct-link"
                    >
                        <?php esc_html_e('99p Pouches', 'kangoo'); ?>
                    </a>
                    <a
                        href="<?php echo esc_url(home_url('/kangoo-app/')); ?>"
                        class="kangoo-mega-drawer__direct-link"
                    >
                        <?php esc_html_e('Kangoo App', 'kangoo'); ?>
                    </a>
                    <?php if ($kangoo_theme_menu_toggle_html !== '') : ?>
                        <?php echo $kangoo_theme_menu_toggle_html; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</header>

<?php
$kangoo_cart_drawer_classes = array('cart-drawer');

if (function_exists('is_checkout') && is_checkout() && (!function_exists('is_order_received_page') || !is_order_received_page())) {
    $kangoo_cart_drawer_classes[] = 'cart-drawer--checkout';
}

if (function_exists('is_cart') && is_cart()) {
    $kangoo_cart_drawer_classes[] = 'cart-drawer--cart-page';
}
?>

<div id="cart-drawer" class="<?php echo esc_attr(implode(' ', $kangoo_cart_drawer_classes)); ?>">
    <div class="cart-drawer__overlay"></div>

    <div class="cart-drawer__panel">
        <div class="cart-drawer__header">
            <h3><?php esc_html_e('Your cart', 'kangoo'); ?></h3>
            <button class="cart-drawer__close" type="button" aria-label="<?php esc_attr_e('Close cart', 'kangoo'); ?>">×</button>
        </div>

        <div class="cart-drawer__content">
            <?php woocommerce_mini_cart(); ?>
        </div>

        <?php
        if (function_exists('kangoo_get_cart_drawer_footer_html')) {
            echo kangoo_get_cart_drawer_footer_html();
        }
        ?>
    </div>
</div>

<?php if (function_exists('kangoo_get_mobile_cart_sticky_html')) : ?>
    <?php echo kangoo_get_mobile_cart_sticky_html(); ?>
<?php endif; ?>

<div class="cart-confirm" id="cart-clear-confirm" aria-hidden="true">
    <div class="cart-confirm__overlay" data-cart-clear-cancel></div>
    <div class="cart-confirm__dialog" role="dialog" aria-modal="true" aria-labelledby="cart-clear-confirm-title">
        <h3 id="cart-clear-confirm-title"><?php esc_html_e('Clear cart?', 'kangoo'); ?></h3>
        <p><?php esc_html_e('This will remove every item from your cart.', 'kangoo'); ?></p>
        <div class="cart-confirm__actions">
            <button type="button" class="btn btn--ghost" data-cart-clear-cancel>
                <?php esc_html_e('Cancel', 'kangoo'); ?>
            </button>
            <button type="button" class="btn btn--primary" data-cart-clear-confirm>
                <?php esc_html_e('Clear cart', 'kangoo'); ?>
            </button>
        </div>
    </div>
</div>
