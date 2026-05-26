<section class="section">
    <div class="container">

        <header class="section-header">
            <span class="eyebrow"><?php the_sub_field('eyebrow'); ?></span>
            <h2><?php the_sub_field('heading'); ?></h2>
            <p><?php the_sub_field('subheading'); ?></p>
        </header>

        <div class="grid grid--products">

            <?php
            $source = get_sub_field('source');
            $limit  = get_sub_field('limit') ?: 6;

            $args = [
                'post_type' => 'product',
                'posts_per_page' => $limit,
            ];

            // SWITCH LOGIC
            switch ($source) {

                case 'latest':
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                    break;

                case 'best_selling':
                    $args['meta_key'] = 'total_sales';
                    $args['orderby'] = 'meta_value_num';
                    break;

                case 'top_rated':
                    $args['meta_key'] = '_wc_average_rating';
                    $args['orderby'] = 'meta_value_num';
                    break;

                case 'featured':
                    $args['tax_query'][] = [
                        'taxonomy' => 'product_visibility',
                        'field' => 'name',
                        'terms' => 'featured',
                    ];
                    break;

                case 'category':
                    $category = get_sub_field('category');
                    if ($category) {
                        $args['tax_query'][] = [
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => $category->term_id,
                        ];
                    }
                    break;

                case 'strong':
                    // assumes attribute pa_strength = strong
                    $args['tax_query'][] = [
                        'taxonomy' => 'pa_strength',
                        'field' => 'slug',
                        'terms' => 'strong',
                    ];
                    break;
            }

            $query = new WP_Query($args);

            if ($query->have_posts()):
                while ($query->have_posts()): $query->the_post();
                    wc_get_template_part('content', 'product');
                endwhile;
                wp_reset_postdata();
            endif;
            ?>

        </div>

    </div>
</section>