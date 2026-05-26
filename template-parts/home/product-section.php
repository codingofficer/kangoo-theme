<section class="section featured-products">
    <div class="container">

        <header class="section-header">
            <span class="eyebrow"><?php the_sub_field('eyebrow'); ?></span>
            <h2><?php the_sub_field('heading'); ?></h2>
            <p><?php the_sub_field('subheading'); ?></p>
        </header>

        <div class="grid grid--products">
            <?php 
            $products = get_sub_field('products');
            if ($products):
                foreach ($products as $post):
                    setup_postdata($post);
                    wc_get_template_part('content', 'product');
                endforeach;
                wp_reset_postdata();
            endif;
            ?>
        </div>

    </div>
</section>