<?php
defined('ABSPATH') || exit;

get_header();

if (function_exists('kangoo_seo_render_human_sitemap_content')) {
    echo kangoo_seo_render_human_sitemap_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
    ?>
    <main class="section">
        <div class="container container--narrow">
            <header class="section-header section-header--left">
                <h1><?php esc_html_e('Sitemap', 'kangoo'); ?></h1>
            </header>
            <p><?php esc_html_e('The Kangoo Pouches sitemap is temporarily unavailable.', 'kangoo'); ?></p>
        </div>
    </main>
    <?php
}

get_footer();
