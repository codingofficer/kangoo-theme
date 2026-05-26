<?php
$finder_url = home_url('/pouch-finder/');
$finder_page = get_posts(array(
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-templates/template-pouch-finder.php',
));

if (!empty($finder_page)) {
    $finder_url = get_permalink($finder_page[0]);
}

$compare_url = home_url('/compare-pouches/');
$compare_page = get_posts(array(
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-templates/template-pouch-comparison.php',
));

if (!empty($compare_page)) {
    $compare_url = get_permalink($compare_page[0]);
}

$box_url = home_url('/pick-n-mix-bundle/');
$box_page = get_posts(array(
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-templates/template-build-a-box.php',
));

if (!empty($box_page)) {
    $box_url = get_permalink($box_page[0]);
}

$strength_ladder_url = home_url('/strength-ladder/');
$strength_ladder_page = get_posts(array(
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-templates/template-strength-ladder.php',
));

if (!empty($strength_ladder_page)) {
    $strength_ladder_url = get_permalink($strength_ladder_page[0]);
}

$flavour_explorer_url = home_url('/flavour-explorer/');
$flavour_explorer_page = get_posts(array(
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-templates/template-flavour-explorer.php',
));

if (!empty($flavour_explorer_page)) {
    $flavour_explorer_url = get_permalink($flavour_explorer_page[0]);
}
?>

<section class="home-finder-cta" aria-label="<?php esc_attr_e('Pouch finder', 'kangoo'); ?>">
    <div class="container">
        <div class="home-finder-cta__inner">
            <div class="home-finder-cta__content">
                <div class="home-finder-cta__copy">
                    <span><?php esc_html_e('Not sure what to choose?', 'kangoo'); ?></span>
                    <strong><?php esc_html_e('Use the Kangoo Pouch Finder for strength and flavour recommendations.', 'kangoo'); ?></strong>
                </div>

                <ul class="home-finder-cta__points" aria-label="<?php esc_attr_e('Finder checks', 'kangoo'); ?>">
                    <li><?php esc_html_e('Strength', 'kangoo'); ?></li>
                    <li><?php esc_html_e('Flavour', 'kangoo'); ?></li>
                    <li><?php esc_html_e('Experience', 'kangoo'); ?></li>
                </ul>
            </div>

            <div class="home-finder-cta__actions">
                <a class="btn btn--primary home-finder-cta__button" href="<?php echo esc_url($finder_url); ?>">
                    <?php esc_html_e('Find my pouch', 'kangoo'); ?>
                </a>
                <a class="btn btn--secondary home-finder-cta__button" href="<?php echo esc_url($compare_url); ?>">
                    <?php esc_html_e('Compare pouches', 'kangoo'); ?>
                </a>
                <a class="btn btn--secondary home-finder-cta__button" href="<?php echo esc_url($box_url); ?>">
                    <?php esc_html_e('Pick n Mix Bundle', 'kangoo'); ?>
                </a>
                <a class="btn btn--secondary home-finder-cta__button" href="<?php echo esc_url($strength_ladder_url); ?>">
                    <?php esc_html_e('Strength Ladder', 'kangoo'); ?>
                </a>
                <a class="btn btn--secondary home-finder-cta__button" href="<?php echo esc_url($flavour_explorer_url); ?>">
                    <?php esc_html_e('Flavour Explorer', 'kangoo'); ?>
                </a>
            </div>
        </div>
    </div>
</section>
