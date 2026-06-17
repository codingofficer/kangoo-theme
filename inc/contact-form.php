<?php
/**
 * Kangoo contact form and enquiry tracking.
 *
 * Provides a lightweight first-party alternative to a form plugin. Messages are
 * emailed through WordPress/SMTP and stored as private admin enquiries.
 */

if (!defined('ABSPATH')) {
    exit;
}

function kangoo_contact_default_email() {
    $email = get_option('admin_email');

    return is_email($email) ? $email : 'hello@kangoopouches.co.uk';
}

function kangoo_contact_option_email($field_name, $default = '') {
    $email = '';

    if (function_exists('get_field')) {
        $acf_value = get_field($field_name, 'option');

        if (is_string($acf_value)) {
            $email = trim($acf_value);
        }
    }

    if (!$email) {
        $option_value = get_option($field_name, '');

        if (is_string($option_value)) {
            $email = trim($option_value);
        }
    }

    if (!$email) {
        $email = $default;
    }

    return is_email($email) ? $email : kangoo_contact_default_email();
}

function kangoo_contact_general_email() {
    return kangoo_contact_option_email('kangoo_contact_general_email', kangoo_contact_default_email());
}

function kangoo_contact_support_email() {
    return kangoo_contact_option_email('kangoo_contact_support_email', kangoo_contact_general_email());
}

function kangoo_contact_topic_options() {
    return array(
        'order'    => __('Order support', 'kangoo'),
        'delivery' => __('Delivery question', 'kangoo'),
        'returns'  => __('Returns or refund', 'kangoo'),
        'product'  => __('Product question', 'kangoo'),
        'account'  => __('Account or rewards', 'kangoo'),
        'general'  => __('General enquiry', 'kangoo'),
    );
}

function kangoo_contact_topic_recipient($topic) {
    $support_topics = array('order', 'delivery', 'returns', 'account');

    return in_array($topic, $support_topics, true) ? kangoo_contact_support_email() : kangoo_contact_general_email();
}

function kangoo_contact_register_post_type() {
    register_post_type('kangoo_enquiry', array(
        'labels' => array(
            'name'               => __('Enquiries', 'kangoo'),
            'singular_name'      => __('Enquiry', 'kangoo'),
            'menu_name'          => __('Enquiries', 'kangoo'),
            'add_new_item'       => __('Add enquiry', 'kangoo'),
            'edit_item'          => __('View enquiry', 'kangoo'),
            'new_item'           => __('New enquiry', 'kangoo'),
            'view_item'          => __('View enquiry', 'kangoo'),
            'search_items'       => __('Search enquiries', 'kangoo'),
            'not_found'          => __('No enquiries found.', 'kangoo'),
            'not_found_in_trash' => __('No enquiries found in Trash.', 'kangoo'),
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => 'control-panel',
        'show_in_admin_bar'   => false,
        'exclude_from_search' => true,
        'supports'            => array('title', 'editor'),
        'capability_type'     => 'post',
        'menu_icon'           => 'dashicons-email-alt2',
    ));
}
add_action('init', 'kangoo_contact_register_post_type');

function kangoo_contact_register_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_kangoo_contact_settings',
        'title' => __('Contact settings', 'kangoo'),
        'fields' => array(
            array(
                'key' => 'field_kangoo_contact_general_email',
                'label' => __('General enquiries email', 'kangoo'),
                'name' => 'kangoo_contact_general_email',
                'type' => 'email',
                'default_value' => 'hello@kangoopouches.co.uk',
                'instructions' => __('Used for general, product, partnership and trade enquiries.', 'kangoo'),
            ),
            array(
                'key' => 'field_kangoo_contact_support_email',
                'label' => __('Support email', 'kangoo'),
                'name' => 'kangoo_contact_support_email',
                'type' => 'email',
                'default_value' => 'hello@kangoopouches.co.uk',
                'instructions' => __('Used for order, delivery, returns, account and rewards enquiries.', 'kangoo'),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'contact-settings',
                ),
            ),
        ),
        'position' => 'acf_after_title',
        'style' => 'default',
        'label_placement' => 'top',
        'active' => true,
        'show_in_rest' => 0,
    ));
}
add_action('acf/init', 'kangoo_contact_register_acf_fields');

function kangoo_contact_flash_get() {
    $token = isset($_GET['kangoo_contact']) ? sanitize_key(wp_unslash($_GET['kangoo_contact'])) : '';

    if (!$token) {
        return array();
    }

    $flash = get_transient('kangoo_contact_flash_' . $token);
    delete_transient('kangoo_contact_flash_' . $token);

    return is_array($flash) ? $flash : array();
}

function kangoo_contact_flash_redirect($data) {
    $redirect = isset($_POST['_wp_http_referer'])
        ? wp_unslash($_POST['_wp_http_referer'])
        : home_url('/contact/');

    $redirect = wp_validate_redirect($redirect, home_url('/contact/'));
    $token = wp_generate_password(12, false, false);
    set_transient('kangoo_contact_flash_' . $token, $data, 10 * MINUTE_IN_SECONDS);

    wp_safe_redirect(add_query_arg('kangoo_contact', $token, $redirect));
    exit;
}

function kangoo_contact_icon($name) {
    $icons = array(
        'mail' => '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M4.75 6.75h14.5v10.5H4.75z"/><path d="m5.25 7.25 6.75 5.5 6.75-5.5"/></svg>',
        'support' => '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M5.5 11.5a6.5 6.5 0 0 1 13 0v4.25a2 2 0 0 1-2 2H14"/><path d="M8.5 12.25h-2a1.5 1.5 0 0 0-1.5 1.5v1a1.5 1.5 0 0 0 1.5 1.5h2z"/><path d="M15.5 12.25h2a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-2z"/><path d="M10.75 17.75h3.25"/></svg>',
        'chat' => '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M5.5 6.5h13v8.25h-7.25L7.5 18.25v-3.5h-2z"/><path d="M8.75 9.5h6.5M8.75 12h4.5"/></svg>',
        'truck' => '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M4.75 7.5h9.5v8h-9.5z"/><path d="M14.25 10h3.25l1.75 2.25v3.25h-5z"/><path d="M7.25 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM16.75 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>',
        'bolt' => '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m13.25 3.75-6 9h4l-1 7.5 6.5-10h-4z"/></svg>',
        'box' => '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m12 4.75 7 3.5v7.5l-7 3.5-7-3.5v-7.5z"/><path d="m5.5 8.5 6.5 3.25 6.5-3.25M12 11.75v7"/></svg>',
        'star' => '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m12 4.75 1.9 4.25 4.6.5-3.45 3.1.95 4.55L12 14.8l-4 2.35.95-4.55L5.5 9.5l4.6-.5z"/></svg>',
    );

    return $icons[$name] ?? $icons['mail'];
}

function kangoo_contact_form_shortcode() {
    $flash = kangoo_contact_flash_get();
    $old = isset($flash['old']) && is_array($flash['old']) ? $flash['old'] : array();
    $errors = isset($flash['errors']) && is_array($flash['errors']) ? $flash['errors'] : array();
    $sent = !empty($flash['sent']);
    $user = wp_get_current_user();

    if (empty($old['name']) && $user && $user->exists()) {
        $old['name'] = $user->display_name;
    }

    if (empty($old['email']) && $user && $user->exists()) {
        $old['email'] = $user->user_email;
    }

    $topics = kangoo_contact_topic_options();

    ob_start();
    ?>
    <div class="kangoo-contact">
        <?php if ($sent) : ?>
            <div class="kangoo-contact__notice kangoo-contact__notice--success" role="status">
                <strong><?php esc_html_e('Message sent', 'kangoo'); ?></strong>
                <span><?php esc_html_e('Thanks. We have received your enquiry and will reply as soon as possible.', 'kangoo'); ?></span>
            </div>
        <?php elseif (!empty($errors)) : ?>
            <div class="kangoo-contact__notice kangoo-contact__notice--error" role="alert">
                <strong><?php esc_html_e('Please check the form', 'kangoo'); ?></strong>
                <span><?php echo esc_html(reset($errors)); ?></span>
            </div>
        <?php endif; ?>

        <div class="kangoo-contact__grid">
            <form class="kangoo-contact__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('kangoo_contact_submit', 'kangoo_contact_nonce'); ?>
                <input type="hidden" name="action" value="kangoo_contact_submit">
                <input class="kangoo-contact__trap" type="text" name="kangoo_company" value="" tabindex="-1" autocomplete="off">

                <div class="kangoo-contact__form-heading">
                    <span aria-hidden="true"><?php echo kangoo_contact_icon('mail'); ?></span>
                    <div>
                        <h2><?php esc_html_e('Send us a message', 'kangoo'); ?></h2>
                        <p><?php esc_html_e('Fill out the form below and we will get back to you as soon as possible.', 'kangoo'); ?></p>
                    </div>
                </div>

                <div class="kangoo-contact__field kangoo-contact__field--half">
                    <label for="kangoo_contact_name"><?php esc_html_e('Name', 'kangoo'); ?></label>
                    <input id="kangoo_contact_name" type="text" name="kangoo_contact_name" value="<?php echo esc_attr($old['name'] ?? ''); ?>" autocomplete="name" required>
                    <?php kangoo_contact_field_error($errors, 'name'); ?>
                </div>

                <div class="kangoo-contact__field kangoo-contact__field--half">
                    <label for="kangoo_contact_email"><?php esc_html_e('Email address', 'kangoo'); ?></label>
                    <input id="kangoo_contact_email" type="email" name="kangoo_contact_email" value="<?php echo esc_attr($old['email'] ?? ''); ?>" autocomplete="email" required>
                    <?php kangoo_contact_field_error($errors, 'email'); ?>
                </div>

                <div class="kangoo-contact__field kangoo-contact__field--half">
                    <label for="kangoo_contact_topic"><?php esc_html_e('What do you need help with?', 'kangoo'); ?></label>
                    <select id="kangoo_contact_topic" name="kangoo_contact_topic" required>
                        <?php foreach ($topics as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $old['topic'] ?? 'order'); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php kangoo_contact_field_error($errors, 'topic'); ?>
                </div>

                <div class="kangoo-contact__field kangoo-contact__field--half">
                    <label for="kangoo_contact_order"><?php esc_html_e('Order number optional', 'kangoo'); ?></label>
                    <input id="kangoo_contact_order" type="text" name="kangoo_contact_order" value="<?php echo esc_attr($old['order_number'] ?? ''); ?>" inputmode="numeric" autocomplete="off">
                </div>

                <div class="kangoo-contact__field">
                    <label for="kangoo_contact_subject"><?php esc_html_e('Subject', 'kangoo'); ?></label>
                    <input id="kangoo_contact_subject" type="text" name="kangoo_contact_subject" value="<?php echo esc_attr($old['subject'] ?? ''); ?>" required>
                    <?php kangoo_contact_field_error($errors, 'subject'); ?>
                </div>

                <div class="kangoo-contact__field">
                    <label for="kangoo_contact_message"><?php esc_html_e('Message', 'kangoo'); ?></label>
                    <textarea id="kangoo_contact_message" name="kangoo_contact_message" rows="7" required><?php echo esc_textarea($old['message'] ?? ''); ?></textarea>
                    <?php kangoo_contact_field_error($errors, 'message'); ?>
                </div>

                <label class="kangoo-contact__consent">
                    <input type="checkbox" name="kangoo_contact_privacy" value="1" <?php checked('1', $old['privacy'] ?? ''); ?> required>
                    <span><?php echo wp_kses_post(sprintf(__('I agree Kangoo Pouches can use these details to respond to my enquiry. See the <a href="%s">Privacy Policy</a>.', 'kangoo'), esc_url(home_url('/privacy-policy/')))); ?></span>
                </label>
                <?php kangoo_contact_field_error($errors, 'privacy'); ?>

                <button class="kangoo-contact__submit" type="submit"><?php esc_html_e('Send message', 'kangoo'); ?></button>
            </form>

            <aside class="kangoo-contact__routes" aria-label="<?php esc_attr_e('Contact routes', 'kangoo'); ?>">
                <div class="kangoo-contact__route">
                    <span aria-hidden="true"><?php echo kangoo_contact_icon('support'); ?></span>
                    <div>
                        <strong><?php esc_html_e('Customer support', 'kangoo'); ?></strong>
                        <a href="mailto:<?php echo esc_attr(kangoo_contact_support_email()); ?>"><?php echo esc_html(kangoo_contact_support_email()); ?></a>
                        <small><?php esc_html_e('For existing orders, delivery issues, returns and refunds.', 'kangoo'); ?></small>
                    </div>
                </div>
                <div class="kangoo-contact__route">
                    <span aria-hidden="true"><?php echo kangoo_contact_icon('chat'); ?></span>
                    <div>
                        <strong><?php esc_html_e('General enquiries', 'kangoo'); ?></strong>
                        <a href="mailto:<?php echo esc_attr(kangoo_contact_general_email()); ?>"><?php echo esc_html(kangoo_contact_general_email()); ?></a>
                        <small><?php esc_html_e('For partnerships, wholesale, press and general questions.', 'kangoo'); ?></small>
                    </div>
                </div>
                <div class="kangoo-contact__route">
                    <span aria-hidden="true"><?php echo kangoo_contact_icon('truck'); ?></span>
                    <div>
                        <strong><?php esc_html_e('Order tracking', 'kangoo'); ?></strong>
                        <small><?php esc_html_e('Tracking is sent by email once your order has been dispatched.', 'kangoo'); ?></small>
                        <a class="kangoo-contact__route-button" href="<?php echo esc_url(home_url('/my-account/orders/')); ?>"><?php esc_html_e('Track order', 'kangoo'); ?></a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('kangoo_contact_form', 'kangoo_contact_form_shortcode');

function kangoo_contact_field_error($errors, $field) {
    if (empty($errors[$field])) {
        return;
    }

    echo '<span class="kangoo-contact__error">' . esc_html($errors[$field]) . '</span>';
}

function kangoo_contact_append_form_to_contact_page($content) {
    if (is_admin() || !is_page('contact') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    if (has_shortcode($content, 'kangoo_contact_form')) {
        return $content;
    }

    return $content . "\n\n" . do_shortcode('[kangoo_contact_form]');
}
add_filter('the_content', 'kangoo_contact_append_form_to_contact_page', 20);

function kangoo_contact_handle_submit() {
    if (
        empty($_POST['kangoo_contact_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kangoo_contact_nonce'])), 'kangoo_contact_submit')
    ) {
        kangoo_contact_flash_redirect(array(
            'errors' => array('form' => __('The form expired. Please try again.', 'kangoo')),
        ));
    }

    $old = array(
        'name'         => sanitize_text_field(wp_unslash($_POST['kangoo_contact_name'] ?? '')),
        'email'        => sanitize_email(wp_unslash($_POST['kangoo_contact_email'] ?? '')),
        'topic'        => sanitize_key(wp_unslash($_POST['kangoo_contact_topic'] ?? '')),
        'order_number' => sanitize_text_field(wp_unslash($_POST['kangoo_contact_order'] ?? '')),
        'subject'      => sanitize_text_field(wp_unslash($_POST['kangoo_contact_subject'] ?? '')),
        'message'      => sanitize_textarea_field(wp_unslash($_POST['kangoo_contact_message'] ?? '')),
        'privacy'      => !empty($_POST['kangoo_contact_privacy']) ? '1' : '',
    );

    $errors = array();
    $topics = kangoo_contact_topic_options();

    if (!empty($_POST['kangoo_company'])) {
        kangoo_contact_flash_redirect(array('sent' => true));
    }

    if (strlen($old['name']) < 2) {
        $errors['name'] = __('Enter your name.', 'kangoo');
    }

    if (!is_email($old['email'])) {
        $errors['email'] = __('Enter a valid email address.', 'kangoo');
    }

    if (!isset($topics[$old['topic']])) {
        $errors['topic'] = __('Choose what you need help with.', 'kangoo');
    }

    if (strlen($old['subject']) < 3) {
        $errors['subject'] = __('Enter a subject.', 'kangoo');
    }

    if (strlen($old['message']) < 15) {
        $errors['message'] = __('Enter a little more detail so we can help.', 'kangoo');
    }

    if ($old['privacy'] !== '1') {
        $errors['privacy'] = __('Confirm you agree to us using these details to respond.', 'kangoo');
    }

    $ip_hash = md5(kangoo_contact_request_ip());
    $rate_key = 'kangoo_contact_rate_' . $ip_hash;

    if (get_transient($rate_key)) {
        $errors['form'] = __('Please wait a moment before sending another message.', 'kangoo');
    }

    if ($errors) {
        kangoo_contact_flash_redirect(array(
            'errors' => $errors,
            'old'    => $old,
        ));
    }

    set_transient($rate_key, 1, MINUTE_IN_SECONDS);

    $topic_label = $topics[$old['topic']];
    $post_id = wp_insert_post(array(
        'post_type'    => 'kangoo_enquiry',
        'post_status'  => 'publish',
        'post_title'   => sprintf('[%s] %s', $topic_label, $old['subject']),
        'post_content' => $old['message'],
    ), true);

    if (is_wp_error($post_id)) {
        kangoo_contact_flash_redirect(array(
            'errors' => array('form' => __('We could not save your message. Please email us directly.', 'kangoo')),
            'old'    => $old,
        ));
    }

    update_post_meta($post_id, '_kangoo_contact_name', $old['name']);
    update_post_meta($post_id, '_kangoo_contact_email', $old['email']);
    update_post_meta($post_id, '_kangoo_contact_topic', $old['topic']);
    update_post_meta($post_id, '_kangoo_contact_topic_label', $topic_label);
    update_post_meta($post_id, '_kangoo_contact_order_number', $old['order_number']);
    update_post_meta($post_id, '_kangoo_contact_status', 'new');
    update_post_meta($post_id, '_kangoo_contact_ip', kangoo_contact_request_ip());
    update_post_meta($post_id, '_kangoo_contact_user_agent', substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255));

    kangoo_contact_send_email($post_id, $old, $topic_label);

    kangoo_contact_flash_redirect(array('sent' => true));
}
add_action('admin_post_nopriv_kangoo_contact_submit', 'kangoo_contact_handle_submit');
add_action('admin_post_kangoo_contact_submit', 'kangoo_contact_handle_submit');

function kangoo_contact_request_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    return preg_match('/^[0-9a-fA-F:.]+$/', $ip) ? $ip : '';
}

function kangoo_contact_send_email($post_id, $data, $topic_label) {
    $recipient = kangoo_contact_topic_recipient($data['topic']);
    $subject = sprintf('[Kangoo Pouches] %s - %s', $topic_label, $data['subject']);
    $order_line = $data['order_number'] ? "\nOrder number: " . $data['order_number'] : '';
    $body = sprintf(
        "New contact enquiry received.\n\nName: %s\nEmail: %s\nTopic: %s%s\n\nSubject: %s\n\nMessage:\n%s\n\nAdmin record: %s",
        $data['name'],
        $data['email'],
        $topic_label,
        $order_line,
        $data['subject'],
        $data['message'],
        get_edit_post_link($post_id, 'raw')
    );
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: Kangoo Pouches <' . kangoo_contact_general_email() . '>',
        'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>',
    );

    wp_mail($recipient, $subject, $body, $headers);
}

function kangoo_contact_add_meta_box() {
    add_meta_box(
        'kangoo_contact_details',
        __('Enquiry details', 'kangoo'),
        'kangoo_contact_render_meta_box',
        'kangoo_enquiry',
        'side',
        'high'
    );
}
add_action('add_meta_boxes_kangoo_enquiry', 'kangoo_contact_add_meta_box');

function kangoo_contact_render_meta_box($post) {
    wp_nonce_field('kangoo_contact_save_meta', 'kangoo_contact_meta_nonce');

    $status = get_post_meta($post->ID, '_kangoo_contact_status', true) ?: 'new';
    $fields = array(
        __('Name', 'kangoo') => get_post_meta($post->ID, '_kangoo_contact_name', true),
        __('Email', 'kangoo') => get_post_meta($post->ID, '_kangoo_contact_email', true),
        __('Topic', 'kangoo') => get_post_meta($post->ID, '_kangoo_contact_topic_label', true),
        __('Order', 'kangoo') => get_post_meta($post->ID, '_kangoo_contact_order_number', true),
        __('IP', 'kangoo') => get_post_meta($post->ID, '_kangoo_contact_ip', true),
    );
    ?>
    <div class="kangoo-contact-admin">
        <?php foreach ($fields as $label => $value) : ?>
            <?php if ($value === '') { continue; } ?>
            <p><strong><?php echo esc_html($label); ?>:</strong><br><?php echo esc_html($value); ?></p>
        <?php endforeach; ?>
        <p>
            <label for="kangoo_contact_status"><strong><?php esc_html_e('Status', 'kangoo'); ?></strong></label>
            <select id="kangoo_contact_status" name="kangoo_contact_status" style="width:100%;margin-top:4px;">
                <?php foreach (kangoo_contact_status_options() as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $status); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
    </div>
    <?php
}

function kangoo_contact_status_options() {
    return array(
        'new'         => __('New', 'kangoo'),
        'in_progress' => __('In progress', 'kangoo'),
        'replied'     => __('Replied', 'kangoo'),
        'closed'      => __('Closed', 'kangoo'),
    );
}

function kangoo_contact_save_meta($post_id) {
    if (
        empty($_POST['kangoo_contact_meta_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kangoo_contact_meta_nonce'])), 'kangoo_contact_save_meta')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || !current_user_can('edit_post', $post_id)
    ) {
        return;
    }

    $status = sanitize_key(wp_unslash($_POST['kangoo_contact_status'] ?? 'new'));

    if (!isset(kangoo_contact_status_options()[$status])) {
        $status = 'new';
    }

    update_post_meta($post_id, '_kangoo_contact_status', $status);
}
add_action('save_post_kangoo_enquiry', 'kangoo_contact_save_meta');

function kangoo_contact_columns($columns) {
    return array(
        'cb' => $columns['cb'],
        'title' => __('Subject', 'kangoo'),
        'kangoo_contact_customer' => __('Customer', 'kangoo'),
        'kangoo_contact_topic' => __('Topic', 'kangoo'),
        'kangoo_contact_order' => __('Order', 'kangoo'),
        'kangoo_contact_status' => __('Status', 'kangoo'),
        'date' => $columns['date'],
    );
}
add_filter('manage_kangoo_enquiry_posts_columns', 'kangoo_contact_columns');

function kangoo_contact_column_content($column, $post_id) {
    if ($column === 'kangoo_contact_customer') {
        $name = get_post_meta($post_id, '_kangoo_contact_name', true);
        $email = get_post_meta($post_id, '_kangoo_contact_email', true);
        echo esc_html($name);
        if ($email) {
            echo '<br><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
    } elseif ($column === 'kangoo_contact_topic') {
        echo esc_html(get_post_meta($post_id, '_kangoo_contact_topic_label', true));
    } elseif ($column === 'kangoo_contact_order') {
        echo esc_html(get_post_meta($post_id, '_kangoo_contact_order_number', true));
    } elseif ($column === 'kangoo_contact_status') {
        $status = get_post_meta($post_id, '_kangoo_contact_status', true) ?: 'new';
        $labels = kangoo_contact_status_options();
        echo esc_html($labels[$status] ?? $labels['new']);
    }
}
add_action('manage_kangoo_enquiry_posts_custom_column', 'kangoo_contact_column_content', 10, 2);

function kangoo_contact_template_include($template) {
    if (!is_page('contact')) {
        return $template;
    }

    $contact_template = get_theme_file_path('page-contact.php');

    return file_exists($contact_template) ? $contact_template : $template;
}
add_filter('template_include', 'kangoo_contact_template_include', 30);
