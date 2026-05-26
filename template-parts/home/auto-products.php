<?php
$eyebrow       = get_sub_field('eyebrow');
$heading       = get_sub_field('heading');
$subheading    = get_sub_field('subheading');
$source        = get_sub_field('source');
$limit         = (int) get_sub_field('limit');
$limit         = $limit > 0 ? $limit : 6;
$show_view_all = (bool) get_sub_field('show_view_all');
$view_all_text = get_sub_field('view_all_text') ?: __('View all', 'kangoo');
$view_all_link = get_sub_field('view_all_link');

$args = array(
    'post_type'      => 'product',
    'posts_per_page' => 50,
    'post_status'    => 'publish',
);

$tax_query = array();

switch ($source) {
    case 'latest':
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        break;

	case 'best_selling':
        $args['meta_key'] = 'total_sales';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
		break;

    case 'top_rated':
        $args['meta_key'] = '_wc_average_rating';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;

    case 'featured':
        $tax_query[] = array(
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => array('featured'),
        );
        break;

    case 'strong':
        $tax_query[] = array(
            'taxonomy' => 'pa_strength',
            'field'    => 'slug',
            'terms'    => array('strong'),
        );
        break;

    case 'summer_collection':
        $tax_query[] = array(
            'relation' => 'OR',
            array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => array('summer-collection'),
            ),
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array('summer-collection'),
            ),
        );

        $args['orderby'] = 'menu_order date';
        $args['order'] = 'DESC';
        break;

    case 'pouches_99p':
    case '99p_pouches':
        $tax_query[] = array(
            'relation' => 'OR',
            array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => array('99p', '99p-collection'),
            ),
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array('99p-pouches'),
            ),
        );

        $args['orderby'] = 'menu_order date';
        $args['order'] = 'DESC';
        break;
}

if (!empty($tax_query)) {
    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }

    $args['tax_query'] = $tax_query;
}

$query = new WP_Query($args);

if ($query->have_posts()) {
    usort($query->posts, function ($a, $b) {
        $product_a = function_exists('wc_get_product') ? wc_get_product($a->ID) : null;
        $product_b = function_exists('wc_get_product') ? wc_get_product($b->ID) : null;
        $stock_a = $product_a && $product_a->is_in_stock() ? 0 : 1;
        $stock_b = $product_b && $product_b->is_in_stock() ? 0 : 1;

        if ($stock_a !== $stock_b) {
            return $stock_a <=> $stock_b;
        }

        $priority = array(
            'limited_edition'  => 1,
            'sale'             => 2,
            'best_seller'      => 3,
            'new'              => 4,
            'none'             => 9,
            ''                 => 9,
        );

        $badge_a = function_exists('kangoo_get_product_badge_key') ? kangoo_get_product_badge_key($a->ID) : '';
        $badge_b = function_exists('kangoo_get_product_badge_key') ? kangoo_get_product_badge_key($b->ID) : '';

        $pa = isset($priority[$badge_a]) ? $priority[$badge_a] : 9;
        $pb = isset($priority[$badge_b]) ? $priority[$badge_b] : 9;

        if ($pa === $pb) {
            return strtotime($b->post_date) <=> strtotime($a->post_date);
        }

        return $pa <=> $pb;
    });

    $query->posts = array_slice($query->posts, 0, $limit);
    $query->post_count = count($query->posts);
}
?>

<section class="section home-auto-products home-auto-products--<?php echo esc_attr(sanitize_html_class($source ?: 'default')); ?>">
    <div class="container">
        <?php if ($eyebrow || $heading || $subheading) : ?>
            <header class="section-header">
                <?php if ($eyebrow) : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>

                <?php if ($heading) : ?>
                    <h2><?php echo esc_html($heading); ?></h2>
                <?php endif; ?>

                <?php if ($subheading) : ?>
                    <p><?php echo esc_html($subheading); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php if ($query->have_posts()) : ?>
            <div class="grid grid--products">
                <?php
                while ($query->have_posts()) :
                    $query->the_post();
                    wc_get_template_part('content', 'product');
                endwhile;
                wp_reset_postdata();
                ?>
            </div>

            <?php if ($show_view_all && $view_all_link) : ?>
                <div class="section-actions">
                    <a class="btn btn--ghost" href="<?php echo esc_url($view_all_link); ?>">
                        <?php echo esc_html($view_all_text); ?>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
