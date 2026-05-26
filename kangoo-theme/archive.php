<?php get_header(); ?>

<main class="section">
    <div class="container">
        <header class="section-header section-header--left">
            <h1><?php the_archive_title(); ?></h1>
            <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="grid grid--3">
                <?php while (have_posts()) : the_post(); ?>
                    <article class="card">
                        <h2 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <div class="wysiwyg"><?php the_excerpt(); ?></div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p>No posts found.</p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
