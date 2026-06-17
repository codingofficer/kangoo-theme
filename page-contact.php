<?php
/**
 * Contact page.
 */

get_header();

$faq_items = array(
    array(
        'question' => __('Where is my order?', 'kangoo'),
        'answer'   => __('Royal Mail tracking details are emailed once your order ships. Check your inbox and spam folder, then contact us with your order number if you need help.', 'kangoo'),
    ),
    array(
        'question' => __('How long does delivery take?', 'kangoo'),
        'answer'   => __('Tracked 24 is usually 1-2 working days and Tracked 48 is usually 2-3 working days after dispatch. Busy periods can take longer.', 'kangoo'),
    ),
    array(
        'question' => __('Can I return nicotine pouches?', 'kangoo'),
        'answer'   => __('For hygiene and age-restricted product reasons, opened nicotine pouch products cannot usually be returned. See our returns policy for the full rules.', 'kangoo'),
    ),
    array(
        'question' => __('Do you offer wholesale?', 'kangoo'),
        'answer'   => __('For trade, wholesale or partnership enquiries, choose General enquiry in the form or email hello@kangoopouches.co.uk.', 'kangoo'),
    ),
);
?>

<main id="site-main" class="kangoo-contact-page">
    <section class="kangoo-contact-hero">
        <div class="container">
            <div class="kangoo-contact-hero__content">
                <span class="eyebrow"><?php esc_html_e('Kangoo Pouches', 'kangoo'); ?></span>
                <h1><?php esc_html_e('Need help?', 'kangoo'); ?> <span><?php esc_html_e("We're here.", 'kangoo'); ?></span></h1>
                <p><?php esc_html_e('Whether it is an order question, delivery update, product recommendation or general enquiry, our team is happy to help.', 'kangoo'); ?></p>

                <div class="kangoo-contact-hero__features" aria-label="<?php esc_attr_e('Support highlights', 'kangoo'); ?>">
                    <div>
                        <span aria-hidden="true"><?php echo kangoo_contact_icon('bolt'); ?></span>
                        <strong><?php esc_html_e('Fast response times', 'kangoo'); ?></strong>
                    </div>
                    <div>
                        <span aria-hidden="true"><?php echo kangoo_contact_icon('box'); ?></span>
                        <strong><?php esc_html_e('Order support', 'kangoo'); ?></strong>
                    </div>
                    <div>
                        <span aria-hidden="true"><?php echo kangoo_contact_icon('truck'); ?></span>
                        <strong><?php esc_html_e('Delivery questions', 'kangoo'); ?></strong>
                    </div>
                    <div>
                        <span aria-hidden="true"><?php echo kangoo_contact_icon('star'); ?></span>
                        <strong><?php esc_html_e('Product recommendations', 'kangoo'); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="kangoo-contact-page__main">
        <div class="container">
            <?php echo do_shortcode('[kangoo_contact_form]'); ?>
        </div>
    </section>

    <section class="kangoo-contact-faq">
        <div class="container">
            <div class="kangoo-contact-faq__heading">
                <span><?php esc_html_e('FAQ', 'kangoo'); ?></span>
                <h2><?php esc_html_e('Frequently asked questions', 'kangoo'); ?></h2>
            </div>

            <div class="kangoo-contact-faq__list">
                <?php foreach ($faq_items as $item) : ?>
                    <details class="kangoo-contact-faq__item">
                        <summary>
                            <span aria-hidden="true"><?php echo kangoo_contact_icon('box'); ?></span>
                            <strong><?php echo esc_html($item['question']); ?></strong>
                        </summary>
                        <p><?php echo esc_html($item['answer']); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
