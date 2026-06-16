<?php

defined('ABSPATH') || exit;

final class Kangoo_VerifyMyAge {
    private const OPTION = 'kangoo_verifymyage_settings';
    private const REST_NAMESPACE = 'kangoo-age-verification/v1';

    private static $instance;
    private $settings = array();

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->settings = wp_parse_args((array) get_option(self::OPTION, array()), self::defaults());

        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'register_settings_page'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('woocommerce_payment_complete', array($this, 'maybe_submit_order'));
        add_action('woocommerce_order_status_processing', array($this, 'maybe_submit_order'));
        add_action('woocommerce_order_status_completed', array($this, 'maybe_submit_order'));
        add_filter('woocommerce_order_actions', array($this, 'register_order_actions'));
        add_action('woocommerce_order_action_kangoo_vma_submit_order', array($this, 'admin_submit_order'));
        add_action('woocommerce_order_action_kangoo_vma_refresh_status', array($this, 'admin_refresh_order_status'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'render_order_meta'), 35);
    }

    private static function defaults() {
        return array(
            'enabled' => 'no',
            'environment' => 'sandbox',
            'test_admin_only' => 'yes',
            'hold_pending_orders' => 'no',
            'notify_email' => 'yes',
            'notify_sms' => 'no',
            'sandbox_api_id' => '',
            'sandbox_api_secret' => '',
            'production_api_id' => '',
            'production_api_secret' => '',
        );
    }

    public function register_settings() {
        register_setting('kangoo_verifymyage', self::OPTION, array($this, 'sanitize_settings'));
    }

    public function sanitize_settings($input) {
        $current = wp_parse_args((array) get_option(self::OPTION, array()), self::defaults());
        $clean = self::defaults();
        $clean['enabled'] = !empty($input['enabled']) ? 'yes' : 'no';
        $clean['environment'] = isset($input['environment']) && 'production' === $input['environment'] ? 'production' : 'sandbox';
        $clean['test_admin_only'] = !empty($input['test_admin_only']) ? 'yes' : 'no';
        $clean['hold_pending_orders'] = !empty($input['hold_pending_orders']) ? 'yes' : 'no';
        $clean['notify_email'] = !empty($input['notify_email']) ? 'yes' : 'no';
        $clean['notify_sms'] = !empty($input['notify_sms']) ? 'yes' : 'no';

        foreach (array('sandbox_api_id', 'sandbox_api_secret', 'production_api_id', 'production_api_secret') as $field) {
            $value = isset($input[$field]) ? trim((string) $input[$field]) : '';
            $clean[$field] = '' !== $value ? sanitize_text_field($value) : $current[$field];
        }

        if ('yes' === $clean['enabled'] && !$this->is_configured($clean)) {
            $clean['enabled'] = 'no';
            add_settings_error(self::OPTION, 'kangoo_vma_missing_keys', __('VerifyMyAge was not enabled because the selected environment is missing an API ID or API secret.', 'kangoo-age-verification'), 'error');
        }

        $this->settings = $clean;
        return $clean;
    }

    public function register_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('VerifyMyAge', 'kangoo-age-verification'),
            __('VerifyMyAge', 'kangoo-age-verification'),
            'manage_woocommerce',
            'kangoo-verifymyage',
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
            <h1><?php esc_html_e('Kangoo VerifyMyAge', 'kangoo-age-verification'); ?></h1>
            <p><?php esc_html_e('Post-checkout age verification using VerifyMyAge Stores & Custom API. Keep disabled until sandbox credentials are tested.', 'kangoo-age-verification'); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields('kangoo_verifymyage'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable automatic checks', 'kangoo-age-verification'); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1" <?php checked($settings['enabled'], 'yes'); ?>> <?php esc_html_e('Submit paid/processing orders to VerifyMyAge', 'kangoo-age-verification'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kangoo-vma-environment"><?php esc_html_e('Environment', 'kangoo-age-verification'); ?></label></th>
                        <td><select id="kangoo-vma-environment" name="<?php echo esc_attr(self::OPTION); ?>[environment]"><option value="sandbox" <?php selected($settings['environment'], 'sandbox'); ?>>Sandbox</option><option value="production" <?php selected($settings['environment'], 'production'); ?>>Production</option></select></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Sandbox safety', 'kangoo-age-verification'); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[test_admin_only]" value="1" <?php checked($settings['test_admin_only'], 'yes'); ?>> <?php esc_html_e('In sandbox mode, only auto-submit orders placed by administrators', 'kangoo-age-verification'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Order handling', 'kangoo-age-verification'); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[hold_pending_orders]" value="1" <?php checked($settings['hold_pending_orders'], 'yes'); ?>> <?php esc_html_e('Move Pending/Failed/Expired verification orders to On hold for manual review', 'kangoo-age-verification'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Customer notifications', 'kangoo-age-verification'); ?></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[notify_email]" value="1" <?php checked($settings['notify_email'], 'yes'); ?>> <?php esc_html_e('Allow VerifyMyAge email notifications', 'kangoo-age-verification'); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[notify_sms]" value="1" <?php checked($settings['notify_sms'], 'yes'); ?>> <?php esc_html_e('Allow VerifyMyAge SMS notifications when a phone number is available', 'kangoo-age-verification'); ?></label>
                        </td>
                    </tr>
                    <?php $this->render_key_fields('sandbox', $settings); ?>
                    <?php $this->render_key_fields('production', $settings); ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Webhook URL', 'kangoo-age-verification'); ?></th>
                        <td><code><?php echo esc_html(rest_url(self::REST_NAMESPACE . '/webhook/verifymyage')); ?></code><p class="description"><?php esc_html_e('Use this as callback.url. VerifyMyAge signs webhook requests with the same HMAC Authorization header.', 'kangoo-age-verification'); ?></p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function render_key_fields($environment, $settings) {
        $label = 'sandbox' === $environment ? __('Sandbox', 'kangoo-age-verification') : __('Production', 'kangoo-age-verification');
        $prefix = self::OPTION . '[' . $environment;
        ?>
        <tr><th scope="row"><?php echo esc_html($label . ' API ID'); ?></th><td><input class="regular-text" type="text" autocomplete="off" name="<?php echo esc_attr($prefix . '_api_id]'); ?>" value="<?php echo esc_attr($settings[$environment . '_api_id']); ?>"></td></tr>
        <tr><th scope="row"><?php echo esc_html($label . ' API secret'); ?></th><td><input class="regular-text" type="password" autocomplete="new-password" name="<?php echo esc_attr($prefix . '_api_secret]'); ?>" value="" placeholder="<?php echo $settings[$environment . '_api_secret'] ? esc_attr__('Configured - leave blank to keep', 'kangoo-age-verification') : ''; ?>"></td></tr>
        <?php
    }

    public function register_rest_routes() {
        register_rest_route(self::REST_NAMESPACE, '/webhook/verifymyage', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'rest_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    public function register_order_actions($actions) {
        $actions['kangoo_vma_submit_order'] = __('Submit to VerifyMyAge', 'kangoo-age-verification');
        $actions['kangoo_vma_refresh_status'] = __('Refresh VerifyMyAge status', 'kangoo-age-verification');
        return $actions;
    }

    public function admin_submit_order($order) {
        if ($order instanceof WC_Order) {
            $this->submit_order($order, true);
        }
    }

    public function admin_refresh_order_status($order) {
        if ($order instanceof WC_Order) {
            $this->refresh_order_status($order);
        }
    }

    public function maybe_submit_order($order_id) {
        if (!$this->should_auto_submit()) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order || $order->get_meta('_kangoo_vma_provider_id')) {
            return;
        }

        if ('sandbox' === $this->settings['environment'] && 'yes' === $this->settings['test_admin_only']) {
            $user_id = (int) $order->get_user_id();
            if (!$user_id || !user_can($user_id, 'manage_woocommerce')) {
                return;
            }
        }

        $this->submit_order($order, false);
    }

    private function submit_order(WC_Order $order, $manual) {
        if (!$this->is_configured($this->settings)) {
            $order->add_order_note(__('VerifyMyAge not submitted: API credentials are not configured.', 'kangoo-age-verification'));
            return false;
        }

        $payload = $this->build_order_payload($order);
        $response = $this->api_request('POST', '/orders', $payload, (string) $order->get_id());
        if (is_wp_error($response)) {
            $order->update_meta_data('_kangoo_vma_last_error', $response->get_error_message());
            $order->save();
            $order->add_order_note(sprintf(__('VerifyMyAge submission failed: %s', 'kangoo-age-verification'), $response->get_error_message()));
            return false;
        }

        $this->store_vma_response($order, $response, $manual ? 'manual_submit' : 'auto_submit');
        return true;
    }

    private function build_order_payload(WC_Order $order) {
        $customer_id = $order->get_user_id() ? 'user-' . $order->get_user_id() : 'guest-' . $order->get_id();
        $products = array();

        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();
            $image = '';
            if ($product instanceof WC_Product) {
                $image_id = $product->get_image_id();
                $image = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : '';
            }

            $products[] = array(
                'id' => (string) $item->get_product_id(),
                'title' => $item->get_name(),
                'image' => $image ? $image : null,
            );
        }

        return array(
            'order' => array(
                'id' => (string) $order->get_id(),
                'customer' => array(
                    'id' => $customer_id,
                    'first_name' => $order->get_billing_first_name(),
                    'last_name' => $order->get_billing_last_name(),
                    'email' => $order->get_billing_email(),
                    'phone' => $order->get_billing_phone(),
                    'postcode' => $order->get_billing_postcode() ? $order->get_billing_postcode() : $order->get_shipping_postcode(),
                    'address1' => $order->get_billing_address_1() ? $order->get_billing_address_1() : $order->get_shipping_address_1(),
                    'address2' => $order->get_billing_address_2() ? $order->get_billing_address_2() : $order->get_shipping_address_2(),
                    'city' => $order->get_billing_city() ? $order->get_billing_city() : $order->get_shipping_city(),
                    'country' => $this->normalize_country($order->get_billing_country() ? $order->get_billing_country() : $order->get_shipping_country()),
                ),
                'products' => $products,
            ),
            'callback' => array(
                'url' => rest_url(self::REST_NAMESPACE . '/webhook/verifymyage'),
            ),
            'notifications' => array(
                'email' => 'yes' === $this->settings['notify_email'],
                'sms' => 'yes' === $this->settings['notify_sms'],
            ),
        );
    }

    private function api_request($method, $path, $payload, $hmac_input) {
        $body = null;
        $url = $this->api_base_url() . $path;

        if ('GET' !== $method) {
            $body = wp_json_encode($payload);
        }

        $response = wp_remote_request($url, array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => $this->authorization_header((string) $hmac_input),
            ),
            'body' => $body,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('kangoo_vma_http_error', __('VerifyMyAge could not be reached.', 'kangoo-age-verification'));
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);

        if ($code < 200 || $code >= 300) {
            $message = isset($data['errors']) ? implode(', ', (array) $data['errors']) : wp_remote_retrieve_response_message($response);
            return new WP_Error('kangoo_vma_api_error', sanitize_text_field($message ? $message : __('VerifyMyAge returned an error.', 'kangoo-age-verification')));
        }

        return is_array($data) ? $data : array();
    }

    private function refresh_order_status(WC_Order $order) {
        $response = $this->api_request('GET', '/orders/' . rawurlencode((string) $order->get_id()), null, (string) $order->get_id());
        if (is_wp_error($response)) {
            $order->add_order_note(sprintf(__('VerifyMyAge status refresh failed: %s', 'kangoo-age-verification'), $response->get_error_message()));
            return false;
        }

        $this->store_vma_response($order, $response, 'manual_refresh');
        return true;
    }

    public function rest_webhook($request) {
        $payload = json_decode($request->get_body(), true);
        $order_id = isset($payload['order']) ? absint($payload['order']) : 0;
        if (!$order_id || !$this->verify_authorization_header((string) $request->get_header('Authorization'), (string) $order_id)) {
            return new WP_Error('kangoo_vma_bad_signature', __('Invalid VerifyMyAge webhook signature.', 'kangoo-age-verification'), array('status' => 400));
        }

        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return new WP_Error('kangoo_vma_order_missing', __('Order not found.', 'kangoo-age-verification'), array('status' => 404));
        }

        $this->store_vma_response($order, is_array($payload) ? $payload : array(), 'webhook');
        return rest_ensure_response(array('received' => true));
    }

    private function store_vma_response(WC_Order $order, $response, $source) {
        $status = isset($response['status']) ? sanitize_text_field($response['status']) : 'Pending';
        $provider_id = isset($response['id']) ? sanitize_text_field($response['id']) : '';
        $url = isset($response['url']) ? esc_url_raw($response['url']) : '';

        if ($provider_id) {
            $order->update_meta_data('_kangoo_vma_provider_id', $provider_id);
        }
        $order->update_meta_data('_kangoo_vma_order_id', (string) $order->get_id());
        $order->update_meta_data('_kangoo_vma_status', $status);
        $order->update_meta_data('_kangoo_vma_url', $url);
        $order->update_meta_data('_kangoo_vma_environment', $this->settings['environment']);
        $order->update_meta_data('_kangoo_vma_last_source', $source);
        $order->update_meta_data('_kangoo_vma_updated_at', current_time('mysql', true));
        if (!$order->get_meta('_kangoo_vma_submitted_at')) {
            $order->update_meta_data('_kangoo_vma_submitted_at', current_time('mysql', true));
        }

        if ('approved' === strtolower($status)) {
            $order->update_meta_data('_kangoo_age_verification_provider', 'VerifyMyAge');
            $order->update_meta_data('_kangoo_age_verification_session_id', $provider_id);
            $order->update_meta_data('_kangoo_age_verification_method', 'post_checkout_stealth_or_fallback');
            $order->update_meta_data('_kangoo_age_verification_over_18', 'yes');
            $order->update_meta_data('_kangoo_age_verification_verified_at', current_time('mysql', true));
        }

        $order->save();
        $order->add_order_note(sprintf(__('VerifyMyAge status: %1$s (%2$s).', 'kangoo-age-verification'), $status, $source));

        if ($this->should_hold_for_status($status) && !$order->has_status('on-hold')) {
            $order->update_status('on-hold', __('Held for VerifyMyAge review.', 'kangoo-age-verification'));
        }
    }

    public function render_order_meta($order) {
        if (!$order instanceof WC_Order || !$order->get_meta('_kangoo_vma_status')) {
            return;
        }

        echo '<div class="kangoo-admin-age-verification"><h3>' . esc_html__('VerifyMyAge', 'kangoo-age-verification') . '</h3>';
        echo '<p><strong>' . esc_html__('Status:', 'kangoo-age-verification') . '</strong> ' . esc_html($order->get_meta('_kangoo_vma_status')) . '</p>';
        echo '<p><strong>' . esc_html__('Environment:', 'kangoo-age-verification') . '</strong> ' . esc_html($order->get_meta('_kangoo_vma_environment')) . '</p>';
        if ($order->get_meta('_kangoo_vma_provider_id')) {
            echo '<p><strong>' . esc_html__('VerifyMyAge ID:', 'kangoo-age-verification') . '</strong> ' . esc_html($order->get_meta('_kangoo_vma_provider_id')) . '</p>';
        }
        if ($order->get_meta('_kangoo_vma_url')) {
            echo '<p><a href="' . esc_url($order->get_meta('_kangoo_vma_url')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open customer verification link', 'kangoo-age-verification') . '</a></p>';
        }
        echo '</div>';
    }

    private function authorization_header($input) {
        $timestamp = time();
        $hmac = hash_hmac('sha256', $this->api_id() . $timestamp . $input, $this->api_secret());
        return 'hmac ' . $this->api_id() . ':' . $timestamp . ':' . $hmac;
    }

    private function verify_authorization_header($header, $input) {
        if (!$this->is_configured($this->settings)) {
            return false;
        }

        if (!$header || !preg_match('/^hmac\s+([^:]+):(\d+):([a-f0-9]+)$/i', trim($header), $matches)) {
            return false;
        }

        if (!hash_equals($this->api_id(), $matches[1])) {
            return false;
        }

        $timestamp = (int) $matches[2];
        if (!$timestamp || abs(time() - $timestamp) > 600) {
            return false;
        }

        $expected = hash_hmac('sha256', $this->api_id() . $timestamp . $input, $this->api_secret());
        return hash_equals($expected, strtolower($matches[3]));
    }

    private function should_auto_submit() {
        return 'yes' === $this->settings['enabled'] && $this->is_configured($this->settings);
    }

    private function should_hold_for_status($status) {
        return 'yes' === $this->settings['hold_pending_orders'] && in_array(strtolower((string) $status), array('pending', 'failed', 'expired'), true);
    }

    private function is_configured($settings) {
        $environment = isset($settings['environment']) && 'production' === $settings['environment'] ? 'production' : 'sandbox';
        return !empty($settings[$environment . '_api_id']) && !empty($settings[$environment . '_api_secret']);
    }

    private function api_base_url() {
        return 'production' === $this->settings['environment'] ? 'https://api.verifymyage.co.uk' : 'https://api-stg.verifymyage.co.uk';
    }

    private function api_id() {
        return $this->settings[$this->settings['environment'] . '_api_id'];
    }

    private function api_secret() {
        return $this->settings[$this->settings['environment'] . '_api_secret'];
    }

    private function normalize_country($country) {
        $country = strtoupper((string) $country);
        return 'GB' === $country ? 'UK' : $country;
    }
}
