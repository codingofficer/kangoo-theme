<?php

defined('ABSPATH') || exit;

final class Kangoo_Age_Verification {
    private const OPTION = 'kangoo_age_verification_settings';
    private const COOKIE = 'kangoo_av_token';
    private const CRON_HOOK = 'kangoo_av_redact_session';
    private const TOKEN_TTL = 43200;

    private static $instance;
    private $settings = array();

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = $wpdb->prefix . 'kangoo_age_verifications';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            token_hash char(64) NOT NULL,
            provider varchar(32) NOT NULL DEFAULT 'stripe',
            provider_session_id varchar(255) NOT NULL,
            environment varchar(16) NOT NULL DEFAULT 'test',
            status varchar(32) NOT NULL DEFAULT 'pending',
            age_over_18 tinyint(1) NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            order_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            verified_at datetime NULL,
            redacted_at datetime NULL,
            last_error text NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY provider_session (provider_session_id),
            KEY token_hash (token_hash),
            KEY status (status)
        ) {$charset};";

        dbDelta($sql);

        if (!get_option(self::OPTION)) {
            add_option(self::OPTION, self::defaults(), '', false);
        }
    }

    private function __construct() {
        $this->settings = wp_parse_args((array) get_option(self::OPTION, array()), self::defaults());

        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'register_settings_page'));
        add_action('admin_notices', array($this, 'admin_notice'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_assets'), 40);
        add_filter('render_block_woocommerce/checkout-payment-block', array($this, 'prepend_block_checkout_ui'), 8, 2);
        add_action('woocommerce_review_order_before_payment', array($this, 'render_classic_checkout_ui'), 4);
        add_filter('woocommerce_available_payment_gateways', array($this, 'gate_payment_methods'), 9999);
        add_action('woocommerce_checkout_process', array($this, 'validate_classic_checkout'), 9999);
        add_filter('rest_pre_dispatch', array($this, 'validate_store_api_checkout'), 10, 3);
        add_action('woocommerce_checkout_create_order', array($this, 'attach_verification_to_order'), 999, 2);
        add_action('woocommerce_store_api_checkout_order_processed', array($this, 'attach_store_api_verification_to_order'), 999);
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'render_order_verification_meta'), 30);
        add_action(self::CRON_HOOK, array($this, 'redact_stripe_session'));
        add_action('after_setup_theme', array($this, 'retire_legacy_dob_gate'), 999);
    }

    private static function defaults() {
        return array(
            'enabled' => 'no',
            'environment' => 'test',
            'test_admin_only' => 'yes',
            'test_publishable_key' => '',
            'test_secret_key' => '',
            'test_webhook_secret' => '',
            'live_publishable_key' => '',
            'live_secret_key' => '',
            'live_webhook_secret' => '',
        );
    }

    public function register_settings() {
        register_setting('kangoo_age_verification', self::OPTION, array($this, 'sanitize_settings'));
    }

    public function sanitize_settings($input) {
        $current = wp_parse_args((array) get_option(self::OPTION, array()), self::defaults());
        $clean = self::defaults();
        $clean['enabled'] = !empty($input['enabled']) ? 'yes' : 'no';
        $clean['environment'] = isset($input['environment']) && 'live' === $input['environment'] ? 'live' : 'test';
        $clean['test_admin_only'] = !empty($input['test_admin_only']) ? 'yes' : 'no';

        foreach (array('test_publishable_key', 'test_secret_key', 'test_webhook_secret', 'live_publishable_key', 'live_secret_key', 'live_webhook_secret') as $field) {
            $value = isset($input[$field]) ? trim((string) $input[$field]) : '';
            $clean[$field] = '' !== $value ? sanitize_text_field($value) : $current[$field];
        }

        $this->settings = $clean;

        if ('yes' === $clean['enabled'] && !$this->is_configured($clean)) {
            $clean['enabled'] = 'no';
            add_settings_error(self::OPTION, 'kangoo_av_missing_keys', __('Age verification was not enabled because the selected environment is missing a publishable key, restricted secret key, or webhook secret.', 'kangoo-age-verification'), 'error');
        }

        return $clean;
    }

    public function register_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Age Verification', 'kangoo-age-verification'),
            __('Age Verification', 'kangoo-age-verification'),
            'manage_woocommerce',
            'kangoo-age-verification',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = wp_parse_args((array) get_option(self::OPTION, array()), self::defaults());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Kangoo Age Verification', 'kangoo-age-verification'); ?></h1>
            <p><?php esc_html_e('Requires a separate Stripe Identity account. WooPayments Connect keys cannot be used.', 'kangoo-age-verification'); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields('kangoo_age_verification'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable enforcement', 'kangoo-age-verification'); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1" <?php checked($settings['enabled'], 'yes'); ?>> <?php esc_html_e('Require verified photo ID and matching selfie before payment', 'kangoo-age-verification'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kangoo-av-environment"><?php esc_html_e('Environment', 'kangoo-age-verification'); ?></label></th>
                        <td><select id="kangoo-av-environment" name="<?php echo esc_attr(self::OPTION); ?>[environment]"><option value="test" <?php selected($settings['environment'], 'test'); ?>>Test</option><option value="live" <?php selected($settings['environment'], 'live'); ?>>Live</option></select></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Test visibility', 'kangoo-age-verification'); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[test_admin_only]" value="1" <?php checked($settings['test_admin_only'], 'yes'); ?>> <?php esc_html_e('In test mode, enforce only for logged-in administrators', 'kangoo-age-verification'); ?></label></td>
                    </tr>
                    <?php $this->render_key_fields('test', $settings); ?>
                    <?php $this->render_key_fields('live', $settings); ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Webhook URL', 'kangoo-age-verification'); ?></th>
                        <td><code><?php echo esc_html(rest_url('kangoo-age-verification/v1/webhook/stripe')); ?></code><p class="description"><?php esc_html_e('Listen for identity.verification_session.verified, requires_input and canceled events.', 'kangoo-age-verification'); ?></p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function render_key_fields($environment, $settings) {
        $label = ucfirst($environment);
        $prefix = self::OPTION . '[' . $environment;
        ?>
        <tr><th scope="row"><?php echo esc_html($label . ' publishable key'); ?></th><td><input class="regular-text" type="text" autocomplete="off" name="<?php echo esc_attr($prefix . '_publishable_key]'); ?>" value="<?php echo esc_attr($settings[$environment . '_publishable_key']); ?>" placeholder="pk_<?php echo esc_attr($environment); ?>_..."></td></tr>
        <tr><th scope="row"><?php echo esc_html($label . ' restricted secret key'); ?></th><td><input class="regular-text" type="password" autocomplete="new-password" name="<?php echo esc_attr($prefix . '_secret_key]'); ?>" value="" placeholder="<?php echo $settings[$environment . '_secret_key'] ? esc_attr__('Configured - leave blank to keep', 'kangoo-age-verification') : esc_attr('rk_' . $environment . '_...'); ?>"><p class="description"><?php esc_html_e('Grant Identity Verification Sessions write/read and access to sensitive verification results.', 'kangoo-age-verification'); ?></p></td></tr>
        <tr><th scope="row"><?php echo esc_html($label . ' webhook secret'); ?></th><td><input class="regular-text" type="password" autocomplete="new-password" name="<?php echo esc_attr($prefix . '_webhook_secret]'); ?>" value="" placeholder="<?php echo $settings[$environment . '_webhook_secret'] ? esc_attr__('Configured - leave blank to keep', 'kangoo-age-verification') : 'whsec_...'; ?>"></td></tr>
        <?php
    }

    public function admin_notice() {
        if (!current_user_can('manage_woocommerce') || 'yes' === $this->settings['enabled']) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || false === strpos((string) $screen->id, 'woocommerce')) {
            return;
        }

        printf('<div class="notice notice-warning"><p>%s</p></div>', wp_kses_post(sprintf(__('Kangoo photo-ID verification is installed but not enforcing checkout. <a href="%s">Configure Stripe Identity</a>.', 'kangoo-age-verification'), esc_url(admin_url('admin.php?page=kangoo-age-verification')))));
    }

    public function register_rest_routes() {
        register_rest_route('kangoo-age-verification/v1', '/status', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'rest_status'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route('kangoo-age-verification/v1', '/session', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'rest_create_session'),
            'permission_callback' => array($this, 'verify_rest_nonce'),
        ));
        register_rest_route('kangoo-age-verification/v1', '/check', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'rest_check_session'),
            'permission_callback' => array($this, 'verify_rest_nonce'),
        ));
        register_rest_route('kangoo-age-verification/v1', '/webhook/stripe', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'rest_stripe_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    public function verify_rest_nonce($request) {
        return wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest') ? true : new WP_Error('kangoo_av_nonce', __('Your checkout session expired. Refresh the page and try again.', 'kangoo-age-verification'), array('status' => 403));
    }

    public function rest_status() {
        return rest_ensure_response(array(
            'enabled' => $this->should_enforce(),
            'verified' => $this->is_current_customer_verified(),
        ));
    }

    public function rest_create_session($request) {
        if (!$this->should_enforce()) {
            return new WP_Error('kangoo_av_disabled', __('Photo-ID verification is not enabled.', 'kangoo-age-verification'), array('status' => 409));
        }

        if ($this->is_current_customer_verified()) {
            return rest_ensure_response(array('verified' => true));
        }

        $token = $this->get_or_create_token();
        $existing = $this->find_current_attempt($token);
        if ($existing && in_array($existing->status, array('pending', 'requires_input'), true)) {
            $this->update_attempt($existing->provider_session_id, 'superseded', false, 'replaced_by_fresh_session');
        }

        $email = sanitize_email((string) $request->get_param('email'));
        $body = array(
            'type' => 'document',
            'options[document][require_matching_selfie]' => 'true',
            'options[document][require_live_capture]' => 'true',
            'options[document][allowed_types][0]' => 'driving_license',
            'options[document][allowed_types][1]' => 'passport',
            'options[document][allowed_types][2]' => 'id_card',
            'metadata[site]' => 'kangoopouches.co.uk',
            'metadata[checkout_token]' => substr(hash_hmac('sha256', $token, wp_salt('auth')), 0, 32),
        );

        if ($email) {
            $body['provided_details[email]'] = $email;
        }

        $session = $this->stripe_request('POST', '/v1/identity/verification_sessions', $body);
        if (is_wp_error($session)) {
            return $session;
        }

        $this->insert_attempt($token, $session);

        return rest_ensure_response(array(
            'clientSecret' => isset($session['client_secret']) ? $session['client_secret'] : '',
            'verified' => false,
        ));
    }

    public function rest_check_session() {
        $token = $this->get_token();
        $attempt = $token ? $this->find_current_attempt($token) : null;
        if (!$attempt) {
            return new WP_Error('kangoo_av_missing_attempt', __('No age-verification attempt was found.', 'kangoo-age-verification'), array('status' => 404));
        }

        $result = $this->refresh_attempt($attempt);
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(array(
            'verified' => 'verified' === $result['status'] && !empty($result['age_over_18']),
            'status' => $result['status'],
            'message' => $result['message'],
        ));
    }

    public function rest_stripe_webhook($request) {
        $payload = $request->get_body();
        $signature = (string) $request->get_header('Stripe-Signature');
        if (!$this->verify_stripe_signature($payload, $signature)) {
            return new WP_Error('kangoo_av_bad_signature', __('Invalid webhook signature.', 'kangoo-age-verification'), array('status' => 400));
        }

        $event = json_decode($payload, true);
        $session_id = isset($event['data']['object']['id']) ? sanitize_text_field($event['data']['object']['id']) : '';
        if (!$session_id) {
            return rest_ensure_response(array('received' => true));
        }

        $attempt = $this->find_attempt_by_session($session_id);
        if ($attempt) {
            $this->refresh_attempt($attempt);
        }

        return rest_ensure_response(array('received' => true));
    }

    private function refresh_attempt($attempt) {
        $session = $this->stripe_request('GET', '/v1/identity/verification_sessions/' . rawurlencode($attempt->provider_session_id), array('expand[]' => 'verified_outputs'));
        if (is_wp_error($session)) {
            return $session;
        }

        $status = isset($session['status']) ? sanitize_key($session['status']) : 'pending';
        $age_over_18 = false;
        $message = __('Verification is still processing.', 'kangoo-age-verification');
        $error = '';

        if ('verified' === $status) {
            $dob = isset($session['verified_outputs']['dob']) ? $session['verified_outputs']['dob'] : array();
            $age_over_18 = $this->dob_is_over_18($dob);
            $status = $age_over_18 ? 'verified' : 'denied';
            $message = $age_over_18 ? __('Your age has been verified.', 'kangoo-age-verification') : __('We could not confirm that you are 18 or over.', 'kangoo-age-verification');
        } elseif ('requires_input' === $status) {
            $message = __('Stripe needs another photo or a different identity document. Please try again.', 'kangoo-age-verification');
            $error = isset($session['last_error']['reason']) ? sanitize_text_field($session['last_error']['reason']) : 'requires_input';
        } elseif ('canceled' === $status) {
            $message = __('Verification was cancelled. Please start again to continue.', 'kangoo-age-verification');
        }

        $this->update_attempt($attempt->provider_session_id, $status, $age_over_18, $error);

        if ($age_over_18 && !wp_next_scheduled(self::CRON_HOOK, array($attempt->provider_session_id))) {
            wp_schedule_single_event(time() + DAY_IN_SECONDS, self::CRON_HOOK, array($attempt->provider_session_id));
        }

        return array('status' => $status, 'age_over_18' => $age_over_18, 'message' => $message);
    }

    public function redact_stripe_session($session_id) {
        $attempt = $this->find_attempt_by_session($session_id);
        if (!$attempt || $attempt->redacted_at) {
            return;
        }

        $result = $this->stripe_request('POST', '/v1/identity/verification_sessions/' . rawurlencode($session_id) . '/redact');
        if (is_wp_error($result)) {
            wp_schedule_single_event(time() + HOUR_IN_SECONDS, self::CRON_HOOK, array($session_id));
            return;
        }

        global $wpdb;
        $wpdb->update($this->table(), array('redacted_at' => current_time('mysql', true)), array('provider_session_id' => $session_id), array('%s'), array('%s'));
    }

    private function stripe_request($method, $path, $body = array()) {
        $key = $this->secret_key();
        if (!$key) {
            return new WP_Error('kangoo_av_not_configured', __('Stripe Identity is not configured.', 'kangoo-age-verification'), array('status' => 503));
        }

        $url = 'https://api.stripe.com' . $path;
        if ('GET' === $method && $body) {
            $url = add_query_arg($body, $url);
            $body = null;
        }

        $response = wp_remote_request($url, array(
            'method' => $method,
            'timeout' => 25,
            'headers' => array('Authorization' => 'Bearer ' . $key),
            'body' => $body,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('kangoo_av_provider_error', __('The identity provider could not be reached. Please try again.', 'kangoo-age-verification'), array('status' => 502));
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) {
            $message = isset($data['error']['message']) ? $data['error']['message'] : __('Stripe Identity returned an error.', 'kangoo-age-verification');
            return new WP_Error('kangoo_av_stripe_error', sanitize_text_field($message), array('status' => 502));
        }

        return is_array($data) ? $data : array();
    }

    private function verify_stripe_signature($payload, $header) {
        $secret = $this->webhook_secret();
        if (!$secret || !$header) {
            return false;
        }

        $timestamp = 0;
        $signatures = array();
        foreach (explode(',', $header) as $part) {
            $bits = array_map('trim', explode('=', $part, 2));
            if (2 !== count($bits)) {
                continue;
            }
            if ('t' === $bits[0]) {
                $timestamp = (int) $bits[1];
            } elseif ('v1' === $bits[0]) {
                $signatures[] = $bits[1];
            }
        }

        if (!$timestamp || abs(time() - $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function enqueue_checkout_assets() {
        $is_cart = function_exists('is_cart') && is_cart();
        $is_checkout = $this->is_checkout_context();

        if (!$this->should_enforce() || (!$is_cart && !$is_checkout)) {
            return;
        }

        $frontend_config = array(
            'enabled' => true,
            'replacesDobGate' => true,
            'verified' => $this->is_current_customer_verified(),
            'publishableKey' => $this->publishable_key(),
            'restUrl' => untrailingslashit(rest_url('kangoo-age-verification/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        );

        if (wp_script_is('kangoo-main', 'enqueued')) {
            wp_add_inline_script('kangoo-main', 'window.kangooAgeVerification = ' . wp_json_encode($frontend_config) . ';', 'before');
        }

        if (!$is_checkout) {
            return;
        }

        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
        wp_enqueue_script('kangoo-age-verification', KANGOO_AV_URL . 'assets/checkout.js', array('stripe-js'), KANGOO_AV_VERSION, true);
        wp_enqueue_style('kangoo-age-verification', KANGOO_AV_URL . 'assets/checkout.css', array(), KANGOO_AV_VERSION);
        wp_localize_script('kangoo-age-verification', 'kangooAgeVerification', $frontend_config);
    }

    public function prepend_block_checkout_ui($block_content) {
        if (!$this->should_enforce()) {
            return $block_content;
        }

        return $this->checkout_ui() . $block_content;
    }

    public function render_classic_checkout_ui() {
        if ($this->should_enforce()) {
            echo $this->checkout_ui(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    private function checkout_ui() {
        $verified = $this->is_current_customer_verified();
        ob_start();
        ?>
        <section class="kangoo-av<?php echo $verified ? ' is-verified' : ''; ?>" data-kangoo-av>
            <div class="kangoo-av__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2.8 19 5v5.7c0 4.7-2.9 8.7-7 10.5-4.1-1.8-7-5.8-7-10.5V5l7-2.2Z"/><path d="m8.7 12 2.1 2.1 4.5-4.6"/></svg></div>
            <div class="kangoo-av__content">
                <span class="kangoo-av__eyebrow"><?php esc_html_e('Required before payment', 'kangoo-age-verification'); ?></span>
                <h3><?php echo $verified ? esc_html__('Age verified', 'kangoo-age-verification') : esc_html__('Verify you are 18 or over', 'kangoo-age-verification'); ?></h3>
                <p><?php echo $verified ? esc_html__('Photo ID and selfie verification completed. Payment options are now available.', 'kangoo-age-verification') : esc_html__('Scan a valid photo ID and take a quick matching selfie. Usually completed in under a minute.', 'kangoo-age-verification'); ?></p>
                <p class="kangoo-av__privacy"><?php esc_html_e('Stripe handles the images securely. Kangoo stores only the verification result and schedules the identity session for deletion.', 'kangoo-age-verification'); ?></p>
                <button type="button" class="kangoo-av__button" data-kangoo-av-start <?php disabled($verified); ?>><?php echo $verified ? esc_html__('Verified', 'kangoo-age-verification') : esc_html__('Verify with photo ID', 'kangoo-age-verification'); ?></button>
                <p class="kangoo-av__message" data-kangoo-av-message aria-live="polite"></p>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public function gate_payment_methods($gateways) {
        if ($this->should_enforce() && $this->is_checkout_context() && !$this->is_current_customer_verified()) {
            return array();
        }

        return $gateways;
    }

    public function validate_classic_checkout() {
        if ($this->should_enforce() && !$this->is_current_customer_verified()) {
            wc_add_notice(__('Complete photo-ID age verification before placing your order.', 'kangoo-age-verification'), 'error');
        }
    }

    public function validate_store_api_checkout($response, $handler, $request) {
        if (!$this->should_enforce() || 'POST' !== $request->get_method()) {
            return $response;
        }

        $route = $request->get_route();
        if (preg_match('#^/wc/store(?:/v\d+)?/checkout(?:/|$)#', $route) && !$this->is_current_customer_verified()) {
            return new WP_Error('kangoo_age_verification_required', __('Complete photo-ID age verification before payment.', 'kangoo-age-verification'), array('status' => 403));
        }

        return $response;
    }

    public function attach_verification_to_order($order) {
        $this->save_order_meta($order);
    }

    public function attach_store_api_verification_to_order($order) {
        $this->save_order_meta($order);
        if ($order instanceof WC_Order) {
            $order->save();
        }
    }

    private function save_order_meta($order) {
        if (!$order instanceof WC_Order) {
            return;
        }

        $attempt = $this->current_verified_attempt();
        if (!$attempt) {
            return;
        }

        $order->update_meta_data('_kangoo_age_verification_provider', 'Stripe Identity');
        $order->update_meta_data('_kangoo_age_verification_session_id', $attempt->provider_session_id);
        $order->update_meta_data('_kangoo_age_verification_method', 'photo_id_and_matching_selfie');
        $order->update_meta_data('_kangoo_age_verification_over_18', 'yes');
        $order->update_meta_data('_kangoo_age_verification_verified_at', $attempt->verified_at);

        global $wpdb;
        $wpdb->update($this->table(), array('order_id' => $order->get_id()), array('id' => $attempt->id), array('%d'), array('%d'));
    }

    public function render_order_verification_meta($order) {
        if (!$order instanceof WC_Order || 'yes' !== $order->get_meta('_kangoo_age_verification_over_18')) {
            return;
        }

        echo '<div class="kangoo-admin-age-verification"><h3>' . esc_html__('Photo-ID age verification', 'kangoo-age-verification') . '</h3>';
        echo '<p><strong>' . esc_html__('Status:', 'kangoo-age-verification') . '</strong> ' . esc_html__('18+ verified', 'kangoo-age-verification') . '</p>';
        echo '<p><strong>' . esc_html__('Method:', 'kangoo-age-verification') . '</strong> ' . esc_html__('Government photo ID and matching selfie', 'kangoo-age-verification') . '</p>';
        echo '<p><strong>' . esc_html__('Provider:', 'kangoo-age-verification') . '</strong> ' . esc_html($order->get_meta('_kangoo_age_verification_provider')) . '</p></div>';
    }

    public function retire_legacy_dob_gate() {
        if (!$this->should_enforce()) {
            return;
        }

        remove_action('woocommerce_review_order_before_submit', 'kangoo_checkout_age_verification_html', 8);
        remove_action('woocommerce_checkout_process', 'kangoo_validate_checkout_age_verification');
        remove_action('woocommerce_init', 'kangoo_register_checkout_block_age_verification_fields');
        remove_action('woocommerce_blocks_validate_location_other_fields', 'kangoo_validate_checkout_block_age_fields', 10);
        remove_action('woocommerce_blocks_validate_location_contact_fields', 'kangoo_validate_checkout_block_age_fields', 10);
        remove_action('template_redirect', 'kangoo_guard_checkout_identity', 9);
        remove_action('woocommerce_cart_collaterals', 'kangoo_render_cart_email_capture', 6);
        remove_action('woocommerce_edit_account_form', 'kangoo_render_account_dob_field', 12);
        remove_action('woocommerce_save_account_details', 'kangoo_save_account_dob_field', 12);
    }

    private function should_enforce() {
        if ('yes' !== $this->settings['enabled'] || !$this->is_configured($this->settings)) {
            return false;
        }

        if ('test' === $this->settings['environment'] && 'yes' === $this->settings['test_admin_only']) {
            return is_user_logged_in() && current_user_can('manage_woocommerce');
        }

        return true;
    }

    private function is_configured($settings) {
        $env = isset($settings['environment']) && 'live' === $settings['environment'] ? 'live' : 'test';
        $publishable_key = defined('KANGOO_AV_STRIPE_PUBLISHABLE_KEY') ? KANGOO_AV_STRIPE_PUBLISHABLE_KEY : $settings[$env . '_publishable_key'];
        $secret_key = defined('KANGOO_AV_STRIPE_SECRET_KEY') ? KANGOO_AV_STRIPE_SECRET_KEY : $settings[$env . '_secret_key'];
        $webhook_secret = defined('KANGOO_AV_STRIPE_WEBHOOK_SECRET') ? KANGOO_AV_STRIPE_WEBHOOK_SECRET : $settings[$env . '_webhook_secret'];

        return !empty($publishable_key) && !empty($secret_key) && !empty($webhook_secret);
    }

    private function is_checkout_context() {
        return function_exists('is_checkout') && is_checkout() && !(function_exists('is_order_received_page') && is_order_received_page());
    }

    private function publishable_key() {
        if (defined('KANGOO_AV_STRIPE_PUBLISHABLE_KEY')) {
            return KANGOO_AV_STRIPE_PUBLISHABLE_KEY;
        }
        return $this->settings[$this->settings['environment'] . '_publishable_key'];
    }

    private function secret_key() {
        if (defined('KANGOO_AV_STRIPE_SECRET_KEY')) {
            return KANGOO_AV_STRIPE_SECRET_KEY;
        }
        return $this->settings[$this->settings['environment'] . '_secret_key'];
    }

    private function webhook_secret() {
        if (defined('KANGOO_AV_STRIPE_WEBHOOK_SECRET')) {
            return KANGOO_AV_STRIPE_WEBHOOK_SECRET;
        }
        return $this->settings[$this->settings['environment'] . '_webhook_secret'];
    }

    private function get_token() {
        return isset($_COOKIE[self::COOKIE]) ? sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE])) : '';
    }

    private function get_or_create_token() {
        $token = $this->get_token();
        if ($token && preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $token;
        }

        $token = bin2hex(random_bytes(32));
        setcookie(self::COOKIE, $token, array(
            'expires' => time() + self::TOKEN_TTL,
            'path' => COOKIEPATH ? COOKIEPATH : '/',
            'domain' => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ));
        $_COOKIE[self::COOKIE] = $token;
        return $token;
    }

    private function token_hash($token) {
        return hash_hmac('sha256', $token, wp_salt('secure_auth'));
    }

    private function table() {
        global $wpdb;
        return $wpdb->prefix . 'kangoo_age_verifications';
    }

    private function find_current_attempt($token) {
        global $wpdb;
        $table = $this->table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE token_hash = %s ORDER BY id DESC LIMIT 1", $this->token_hash($token)));
    }

    private function find_attempt_by_session($session_id) {
        global $wpdb;
        $table = $this->table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE provider_session_id = %s LIMIT 1", $session_id));
    }

    private function current_verified_attempt() {
        $token = $this->get_token();
        if (!$token) {
            return null;
        }
        $attempt = $this->find_current_attempt($token);
        return $attempt && 'verified' === $attempt->status && (int) $attempt->age_over_18 === 1 ? $attempt : null;
    }

    private function is_current_customer_verified() {
        return (bool) $this->current_verified_attempt();
    }

    private function insert_attempt($token, $session) {
        global $wpdb;
        $wpdb->insert($this->table(), array(
            'token_hash' => $this->token_hash($token),
            'provider' => 'stripe',
            'provider_session_id' => sanitize_text_field($session['id']),
            'environment' => $this->settings['environment'],
            'status' => 'pending',
            'age_over_18' => 0,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql', true),
        ), array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s'));
    }

    private function update_attempt($session_id, $status, $age_over_18, $error = '') {
        global $wpdb;
        $data = array(
            'status' => $status,
            'age_over_18' => $age_over_18 ? 1 : 0,
            'last_error' => $error,
        );
        $format = array('%s', '%d', '%s');
        if ($age_over_18) {
            $data['verified_at'] = current_time('mysql', true);
            $format[] = '%s';
        }
        $wpdb->update($this->table(), $data, array('provider_session_id' => $session_id), $format, array('%s'));
    }

    private function dob_is_over_18($dob) {
        if (!is_array($dob) || empty($dob['year']) || empty($dob['month']) || empty($dob['day'])) {
            return false;
        }
        try {
            $birth = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $dob['year'], $dob['month'], $dob['day']));
            return $birth->diff(new DateTimeImmutable('today'))->y >= 18;
        } catch (Exception $exception) {
            return false;
        }
    }
}
