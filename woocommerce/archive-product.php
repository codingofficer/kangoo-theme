<?php
defined('ABSPATH') || exit;

get_header();

if (function_exists('kangoo_breadcrumbs')) {
    kangoo_breadcrumbs();
}

$term = get_queried_object();
$term_id = $term instanceof WP_Term ? $term->term_id : 0;
$term_taxonomy = $term instanceof WP_Term ? $term->taxonomy : '';
$term_acf_key = $term_id && $term_taxonomy ? $term_taxonomy . '_' . $term_id : '';

$category_intro = $term_acf_key ? get_field('category_intro', $term_acf_key) : '';
$category_page_heading = $term_acf_key ? get_field('category_seo_title', $term_acf_key) : '';
$category_seo_content = $term_acf_key ? get_field('category_seo_content', $term_acf_key) : '';
$category_faq_rows = $term_acf_key ? get_field('category_faq', $term_acf_key) : array();

if (!is_array($category_faq_rows)) {
    $category_faq_rows = array();
}

if (
    $term instanceof WP_Term
    && $term_taxonomy === 'product_cat'
    && function_exists('kangoo_get_brand_authority_profile')
    && !empty(kangoo_get_brand_authority_profile($term->slug))
) {
    $brand_authority_profile = kangoo_get_brand_authority_profile($term->slug);

    if (function_exists('kangoo_get_brand_authority_intro')) {
        $category_intro = kangoo_get_brand_authority_intro($term->slug);
    }

    $category_page_heading = sprintf(__('%s Nicotine Pouches', 'kangoo'), $brand_authority_profile['label']);

    if (function_exists('kangoo_get_brand_authority_content')) {
        $category_seo_content = kangoo_get_brand_authority_content($term->slug);
    }

    if (function_exists('kangoo_get_brand_authority_faq')) {
        $category_faq_rows = kangoo_get_brand_authority_faq($term->slug);
    }
}

$category_faq_schema = array();

foreach ($category_faq_rows as $faq_row) {
    $question = isset($faq_row['question']) ? trim(wp_strip_all_tags($faq_row['question'])) : '';
    $answer = isset($faq_row['answer']) ? trim(wp_strip_all_tags($faq_row['answer'])) : '';

    if ($question === '' || $answer === '') {
        continue;
    }

    $category_faq_schema[] = array(
        '@type' => 'Question',
        'name' => $question,
        'acceptedAnswer' => array(
            '@type' => 'Answer',
            'text' => $answer,
        ),
    );
}

$category_hero_copy = $category_intro;
$product_count = isset($GLOBALS['wp_query']->found_posts)
    ? (int) $GLOBALS['wp_query']->found_posts
    : (int) wp_count_posts('product')->publish;

$archive_eyebrow = __('Shop', 'kangoo');

if ($term_taxonomy === 'product_cat') {
    $archive_eyebrow = __('Category', 'kangoo');
} elseif ($term_taxonomy === 'pa_strength') {
    $archive_eyebrow = __('Strength', 'kangoo');
} elseif ($term_taxonomy === 'pa_flavour') {
    $archive_eyebrow = __('Flavour', 'kangoo');
}

$category_display_heading = $category_page_heading
    ? $category_page_heading
    : woocommerce_page_title(false);

if ($term_taxonomy === 'product_cat') {
    $category_display_heading = preg_replace('/\s+UK$/i', '', $category_display_heading);
}
?>

<main class="category-page">
    <section class="section category-page__hero">
        <div class="container">
            <div class="category-page__hero-inner">
                <header class="category-page__header">
                    <?php if ($term_taxonomy !== 'product_cat') : ?>
                        <span class="eyebrow"><?php echo esc_html($archive_eyebrow); ?></span>
                    <?php endif; ?>
                    <h1>
                        <?php echo esc_html($category_display_heading); ?>
                    </h1>

                    <?php if ($category_hero_copy) : ?>
                        <div class="category-page__copy" data-category-readmore>
                            <p class="category-page__intro">
                                <?php echo esc_html($category_intro); ?>
                            </p>
                        </div>

                        <button
                            type="button"
                            class="category-page__read-more"
                            data-category-readmore-toggle
                            aria-expanded="false"
                        >
                                <?php esc_html_e('Read more', 'kangoo'); ?>
                        </button>
                    <?php endif; ?>
                </header>
            </div>
        </div>
    </section>

    <?php if ($term instanceof WP_Term && $term_taxonomy === 'product_cat' && $term->slug === 'nicotine-pouches') : ?>
        <?php
        $kangoo_tool_pages = array(
            array(
                'label'    => __('Pouch Finder', 'kangoo'),
                'text'     => __('Get matched by strength, flavour and experience.', 'kangoo'),
                'template' => 'page-templates/template-pouch-finder.php',
                'fallback' => home_url('/pouch-finder/'),
            ),
            array(
                'label'    => __('Compare Pouches', 'kangoo'),
                'text'     => __('Compare products side by side before buying.', 'kangoo'),
                'template' => 'page-templates/template-pouch-comparison.php',
                'fallback' => home_url('/compare-pouches/'),
            ),
            array(
                'label'    => __('Pick n Mix Bundle', 'kangoo'),
                'text'     => __('Mix brands, strengths and flavours in one order.', 'kangoo'),
                'template' => 'page-templates/template-build-a-box.php',
                'fallback' => home_url('/pick-n-mix-bundle/'),
            ),
            array(
                'label'    => __('Strength Ladder', 'kangoo'),
                'text'     => __('Understand light, balanced, strong and extra strong.', 'kangoo'),
                'template' => 'page-templates/template-strength-ladder.php',
                'fallback' => home_url('/strength-ladder/'),
            ),
            array(
                'label'    => __('Flavour Explorer', 'kangoo'),
                'text'     => __('Browse mint, berry, citrus, tropical and sweet profiles.', 'kangoo'),
                'template' => 'page-templates/template-flavour-explorer.php',
                'fallback' => home_url('/flavour-explorer/'),
            ),
        );

        foreach ($kangoo_tool_pages as $tool_index => $tool_page) {
            $tool_page_ids = get_posts(array(
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => '_wp_page_template',
                'meta_value'     => $tool_page['template'],
            ));

            $kangoo_tool_pages[$tool_index]['url'] = !empty($tool_page_ids) ? get_permalink($tool_page_ids[0]) : $tool_page['fallback'];
        }

        $quick_links = array(
            array('label' => __('Mint nicotine pouches', 'kangoo'), 'url' => home_url('/mint-nicotine-pouches/')),
            array('label' => __('Berry nicotine pouches', 'kangoo'), 'url' => home_url('/berry-nicotine-pouches/')),
            array('label' => __('Strong nicotine pouches', 'kangoo'), 'url' => home_url('/strong-strength-nicotine-pouches/')),
            array('label' => __('Extra strong nicotine pouches', 'kangoo'), 'url' => home_url('/extra-strong-strength-nicotine-pouches/')),
            array('label' => __('ZYN nicotine pouches', 'kangoo'), 'url' => function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'zyn', '/product-category/zyn/') : home_url('/product-category/zyn/')),
            array('label' => __('VELO nicotine pouches', 'kangoo'), 'url' => function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'velo', '/product-category/velo/') : home_url('/product-category/velo/')),
        );
        ?>

        <section class="category-link-rail" aria-label="<?php esc_attr_e('Popular nicotine pouch pages', 'kangoo'); ?>">
            <div class="container">
                <div class="category-link-rail__scroller">
                    <?php foreach ($quick_links as $quick_link) : ?>
                        <a href="<?php echo esc_url($quick_link['url']); ?>"><?php echo esc_html($quick_link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="section category-page__products">
        <div class="container">
			<?php
			$current_brand    = isset($_GET['filter_brand']) ? sanitize_title(wp_unslash($_GET['filter_brand'])) : '';
			$current_flavour  = isset($_GET['filter_flavour']) ? sanitize_title(wp_unslash($_GET['filter_flavour'])) : '';
			$current_strength = isset($_GET['filter_strength']) ? sanitize_title(wp_unslash($_GET['filter_strength'])) : '';
			$current_orderby  = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : '';
            $is_brand_category_filter_context = $term instanceof WP_Term && $term_taxonomy === 'product_cat' && function_exists('kangoo_is_product_brand_category_slug') && kangoo_is_product_brand_category_slug($term->slug);

            if ($current_brand === '' && $is_brand_category_filter_context) {
                $current_brand = $term->slug;
            }

			$base_url = $term instanceof WP_Term ? get_term_link($term) : get_permalink(wc_get_page_id('shop'));
            if (is_wp_error($base_url) || !$base_url) {
                $base_url = home_url('/');
            }

            $filter_action_url = $base_url;
            $filter_reset_url = $base_url;

            if ($is_brand_category_filter_context) {
                $nicotine_pouches_url = function_exists('kangoo_get_term_url_by_slug')
                    ? kangoo_get_term_url_by_slug('product_cat', 'nicotine-pouches', '/product-category/nicotine-pouches/')
                    : home_url('/product-category/nicotine-pouches/');

                $filter_action_url = $nicotine_pouches_url;
                $filter_reset_url = $nicotine_pouches_url;
            }

			?>

            <div class="category-products-toolbar">
                <button type="button" class="category-products-toolbar__filter" data-category-filter-open>
                    <?php esc_html_e('Filter & Sort', 'kangoo'); ?>
                </button>

                <form class="category-products-toolbar__sort" method="get" action="<?php echo esc_url($base_url); ?>">
                    <?php if ($current_brand !== '' && !$is_brand_category_filter_context) : ?>
                        <input type="hidden" name="filter_brand" value="<?php echo esc_attr($current_brand); ?>">
                    <?php endif; ?>
                    <?php if ($current_flavour !== '') : ?>
                        <input type="hidden" name="filter_flavour" value="<?php echo esc_attr($current_flavour); ?>">
                    <?php endif; ?>
                    <?php if ($current_strength !== '') : ?>
                        <input type="hidden" name="filter_strength" value="<?php echo esc_attr($current_strength); ?>">
                    <?php endif; ?>

                    <label class="screen-reader-text" for="category-mobile-sort">
                        <?php esc_html_e('Sort products', 'kangoo'); ?>
                    </label>
                    <select id="category-mobile-sort" name="orderby" onchange="this.form.submit()">
                        <option value="" <?php selected($current_orderby, ''); ?>><?php esc_html_e('Sort by: Popular', 'kangoo'); ?></option>
                        <option value="date" <?php selected($current_orderby, 'date'); ?>><?php esc_html_e('Sort by: Newest', 'kangoo'); ?></option>
                        <option value="price" <?php selected($current_orderby, 'price'); ?>><?php esc_html_e('Price: Low to high', 'kangoo'); ?></option>
                        <option value="price-desc" <?php selected($current_orderby, 'price-desc'); ?>><?php esc_html_e('Price: High to low', 'kangoo'); ?></option>
                        <option value="title" <?php selected($current_orderby, 'title'); ?>><?php esc_html_e('Sort by: A-Z', 'kangoo'); ?></option>
                    </select>
                </form>
            </div>

            <div class="category-filter-backdrop" data-category-filter-close></div>

			<form class="category-filter" method="get" action="<?php echo esc_url($filter_action_url); ?>" data-category-filter>
				<div class="category-filter__top">
					<div>
						<h2>Filter by</h2>
						<span><?php echo esc_html($product_count); ?> products</span>
					</div>

					<a class="category-filter__reset" href="<?php echo esc_url($filter_reset_url); ?>">
						Reset
					</a>

                    <button type="button" class="category-filter__close" data-category-filter-close aria-label="<?php esc_attr_e('Close filters', 'kangoo'); ?>">
                        &times;
                    </button>
				</div>

				<div class="category-filter__fields">
					<select name="filter_brand">
						<option value="">Brand</option>
						<?php
						$brands = function_exists('kangoo_product_filter_options') ? kangoo_product_filter_options('brand') : array();

						foreach ($brands as $brand_slug => $brand) :
							?>
							<option value="<?php echo esc_attr($brand_slug); ?>" <?php selected($current_brand, $brand_slug); ?>>
								<?php echo esc_html($brand); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select name="filter_flavour">
						<option value="">Flavour</option>
						<?php
						$flavours = function_exists('kangoo_product_filter_options') ? kangoo_product_filter_options('flavour') : array();

						foreach ($flavours as $flavour_slug => $flavour) :
							?>
							<option value="<?php echo esc_attr($flavour_slug); ?>" <?php selected($current_flavour, $flavour_slug); ?>>
								<?php echo esc_html($flavour); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select name="filter_strength">
						<option value="">Strength</option>
						<?php
						$strengths = function_exists('kangoo_product_filter_options') ? kangoo_product_filter_options('strength') : array();

						foreach ($strengths as $strength_slug => $strength) :
							?>
							<option value="<?php echo esc_attr($strength_slug); ?>" <?php selected($current_strength, $strength_slug); ?>>
								<?php echo esc_html($strength); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select name="orderby">
						<option value="">Sort by</option>
						<option value="date" <?php selected($current_orderby, 'date'); ?>>Newest</option>
						<option value="price" <?php selected($current_orderby, 'price'); ?>>Price, low to high</option>
						<option value="price-desc" <?php selected($current_orderby, 'price-desc'); ?>>Price, high to low</option>
						<option value="title" <?php selected($current_orderby, 'title'); ?>>A-Z</option>
					</select>

					<div class="category-filter__actions">
						<button type="submit" class="btn btn--primary">
							Apply
						</button>

						<a class="category-filter__clear" href="<?php echo esc_url($filter_reset_url); ?>">
							<?php esc_html_e('Clear Filters', 'kangoo'); ?>
						</a>
					</div>
				</div>
			</form>
            <?php if (woocommerce_product_loop()) : ?>
                <div class="woo-grid">
                    <?php while (have_posts()) : the_post(); ?>
                        <?php wc_get_template_part('content', 'product'); ?>
                    <?php endwhile; ?>
                </div>

                <?php
                global $wp_query;

                $current_page = max(1, (int) get_query_var('paged'));
                $per_page = (int) $wp_query->get('posts_per_page');
                $total_products = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : $product_count;
                $shown_products = $per_page > 0 ? min($current_page * $per_page, $total_products) : $total_products;
                $progress = $total_products > 0 ? min(100, ($shown_products / $total_products) * 100) : 0;
                $next_page_url = $current_page < (int) $wp_query->max_num_pages ? get_pagenum_link($current_page + 1) : '';
                ?>

                <div class="category-show-more" aria-label="<?php esc_attr_e('Product pagination', 'kangoo'); ?>" data-category-show-more>
                    <p>
                        <?php
                        printf(
                            esc_html__('Showing %1$d of %2$d', 'kangoo'),
                            $shown_products,
                            $total_products
                        );
                        ?>
                    </p>
                    <div class="category-show-more__track" aria-hidden="true">
                        <span style="width: <?php echo esc_attr($progress); ?>%"></span>
                    </div>

                    <?php if ($next_page_url) : ?>
                        <a
                            class="category-show-more__button"
                            href="<?php echo esc_url($next_page_url); ?>"
                            data-category-show-more-button
                        >
                            <?php esc_html_e('Show more', 'kangoo'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="category-page__empty card">
                    <h2>No products found</h2>
                    <p>We are adding products to this category soon.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php get_template_part('template-parts/category/seo-product-modules'); ?>

    <?php if ($term instanceof WP_Term && $term_taxonomy === 'product_cat' && $term->slug === 'nicotine-pouches' && !empty($kangoo_tool_pages)) : ?>
        <section class="category-choice-tools category-choice-tools--after-products" aria-label="<?php esc_attr_e('Nicotine pouch buying tools', 'kangoo'); ?>">
            <div class="container">
                <div class="category-choice-tools__inner">
                    <div class="category-choice-tools__copy">
                        <span class="eyebrow"><?php esc_html_e('Not sure what to choose?', 'kangoo'); ?></span>
                        <h2><?php esc_html_e('Use the Kangoo Pouch Finder for strength and flavour recommendations.', 'kangoo'); ?></h2>
                        <div class="category-choice-tools__pills" aria-hidden="true">
                            <span><?php esc_html_e('Strength', 'kangoo'); ?></span>
                            <span><?php esc_html_e('Flavour', 'kangoo'); ?></span>
                            <span><?php esc_html_e('Experience', 'kangoo'); ?></span>
                        </div>
                    </div>

                    <div class="category-choice-tools__actions">
                        <?php foreach ($kangoo_tool_pages as $tool_index => $tool_page) : ?>
                            <a href="<?php echo esc_url($tool_page['url']); ?>" class="btn <?php echo $tool_index === 0 ? 'btn--primary' : 'btn--secondary'; ?>">
                                <?php echo esc_html($tool_page['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($category_seo_content) : ?>
        <section class="section category-page__seo">
            <div class="container container--narrow">
                <header class="section-header section-header--left">
                    <h2>
                        <?php
                        printf(
                            esc_html__('More about %s', 'kangoo'),
                            esc_html($term instanceof WP_Term ? $term->name : woocommerce_page_title(false))
                        );
                        ?>
                    </h2>
                </header>

                <?php if ($category_seo_content) : ?>
                    <div class="wysiwyg category-page__seo-content">
                        <?php echo wp_kses_post($category_seo_content); ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($category_faq_rows)) : ?>
        <section class="section category-page__faq">
            <div class="container container--narrow">
                <header class="section-header section-header--left">
                    <span class="eyebrow">FAQ</span>
                    <h2>Frequently asked questions</h2>
                </header>

                <div class="faq-list">
                    <?php foreach ($category_faq_rows as $faq_row) : ?>
                        <?php
                        $question = isset($faq_row['question']) ? trim($faq_row['question']) : '';
                        $answer = isset($faq_row['answer']) ? trim($faq_row['answer']) : '';

                        if ($question === '' || $answer === '') {
                            continue;
                        }
                        ?>
                        <details>
                            <summary><?php echo esc_html($question); ?></summary>
                            <div class="wysiwyg">
                                <p><?php echo wp_kses_post($answer); ?></p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php if (!empty($category_faq_schema)) : ?>
    <script type="application/ld+json">
        <?php
        echo wp_json_encode(
            array(
                '@context' => 'https://schema.org',
                '@type'    => 'FAQPage',
                'mainEntity' => $category_faq_schema,
            ),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        ?>
    </script>
<?php endif; ?>

<?php get_footer(); ?>
