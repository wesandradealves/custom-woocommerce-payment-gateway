<?php
/*
 * Plugin Name: BDM Digital Payment Gateway
 * Plugin URI: https://mercado.dourado.cash/
 * Description: Um plugin para processar pagamentos utilizando BDM Digital. Suporta geração de QR codes, processamento de pagamentos, validação de transações e fornecimento de confirmações. Permite integração com várias carteiras e serviços associados.
 * Version: 1.1.4
 * Author: Dourado Cash
 * Author URI: https://mercado.dourado.cash/
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_REST_Response')) {
    require_once ABSPATH . 'wp-includes/rest-api.php';
}

$storefront_theme = wp_get_theme('storefront');

if ($storefront_theme->exists()) {
    switch_theme('storefront');
} 

register_activation_hook(__FILE__, 'init');
function init() {
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));

        wp_die(
            __('BDM Digital Payment Gateway requer que o WooCommerce esteja instalado e ativado.', 'bdm-digital-payment-gateway'),
            __('Plugin Activation Error', 'bdm-digital-payment-gateway'),
            ['back_link' => true]
        );
    } else {
        bdm_create_checkout_page();
    }
}

add_filter('woocommerce_currencies', 'bdm_add_custom_currency');
function bdm_add_custom_currency($currencies) {
    $currencies['BDM'] = __('BDM Digital', 'bdm-digital-payment-gateway'); 
    return $currencies;
}

add_filter('woocommerce_currency_symbol', 'bdm_add_custom_currency_symbol', 10, 2);
function bdm_add_custom_currency_symbol($currency_symbol, $currency) {
    switch ($currency) {
        case 'BDM':
            $currency_symbol = 'BDM'; 
            break;
    }
    return $currency_symbol;
}

add_filter('woocommerce_rest_prepare_shop_order_object', function($response, $post, $request) {
    $order = wc_get_order($post->ID);
    $origin = $order->get_meta('_order_origin');
    $response->data['meta_data']['_order_origin'] = $origin;
    return $response;
}, 10, 3);

function bdm_create_checkout_page() {
    $page = array(
        'post_title'    => 'BDM Checkout',
        'post_name'     => 'bdm-checkout',
        'post_content'  => '[woocommerce_checkout]', 
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_author'   => 1,
    );
    
    if (!get_page_by_path('bdm-checkout')) {
        wp_insert_post($page);

        update_option('woocommerce_checkout_page_id', get_page_by_path('bdm-checkout')->ID);        
    }
}

register_deactivation_hook(__FILE__, 'bdm_remove_checkout_page');
function bdm_remove_checkout_page() {
    $page = get_page_by_path('bdm-checkout');
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bdm_add_settings_link');
function bdm_add_settings_link($links) {
    $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=bdm-digital');
    $settings_link = '<a href="' . esc_url($url) . '">' . __('Configurações', 'bdm-digital-payment-gateway') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_filter('theme_page_templates', 'bdm_register_custom_template');
function bdm_register_custom_template($templates) {
    $templates['templates/checkout-template.php'] = __('BDM Checkout Template', 'bdm-digital-payment-gateway');
    return $templates;
}

add_filter('template_include', 'bdm_load_custom_template');
function bdm_load_custom_template($template) {
    if (is_page('bdm-checkout')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/checkout-template.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
}

add_filter( 'woocommerce_payment_gateways', 'add_gateway' );
function add_gateway( $gateways ) {
	$gateways[] = 'WC_BDM_GATEWAY'; 
	return $gateways;
}

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'woocommerce_page_wc-orders') {
        wp_enqueue_script(
            'bdm-admin-checkout-handler',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            array('jquery'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/admin.js'),
            true
        );
    }
});
function bdm_get_api_endpoint($sandbox) {
    return $sandbox && $sandbox !== "no"
        ? 'https://opiihi8ab4.execute-api.us-east-2.amazonaws.com/'
        : 'https://piyzov0kjl.execute-api.sa-east-1.amazonaws.com/';
}
function bdm_enqueue_scripts() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    if (is_checkout() || is_page('bdm-checkout')) {
        wp_enqueue_style(
            'bdm-checkout-style',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            array(),
            '1.0.0', 
            'all' 
        );

        wp_enqueue_style(
            'bootstrap',
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css',
            array(), 
            '1.0.0',
            'all' 
        );        

        wp_enqueue_script(
            'bdm-checkout-js',
            plugin_dir_url(__FILE__) . 'assets/js/main.js',
            array('jquery'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/main.js'),
            true
        );

        wp_enqueue_script(
            'toast-js',
            '//cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js',
            array('jquery'),
            '1.3.2',
            true
        );

        wp_enqueue_style(
            'toast-css',
            '//cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css',
            array(),
            '1.0.0', 
            'all' 
        );        

        $settings = get_option('woocommerce_bdm-digital_settings'); 

        $checkout_data = array(
            'products' => array_map(function($item) {
                return array(
                    'id' => $item['product_id'],
                    'title' => $item['data']->get_name(),
                    'quantity' => $item['quantity'],
                    'price' => wc_price($item['line_total'])
                );
            }, WC()->cart->get_cart()),
            'settings' => array(
                'api_key' => $settings['api_key'] ?? '',
                'endpoint' => bdm_get_api_endpoint($settings['sandbox']),
                'asset' => $settings['asset'] ?? '',
                'partner_email' => $settings['partner_email'] ?? '',
                'sandbox' => $settings['sandbox'] ?? '',
                'consumer_key' => $settings['rest_key'] ?? '',
                'endpoint_quotation' => 'https://opiihi8ab4.execute-api.us-east-2.amazonaws.com/ecommerce-partner/clients/quotation/all',
                'consumer_secret' => $settings['rest_secret'] ?? ''
            )
        );

        wp_localize_script('bdm-checkout-js', 'bdm_checkout_data', $checkout_data);
    }
}
add_action('wp_enqueue_scripts', 'bdm_enqueue_scripts');
add_action('wp_ajax_create_bdm_order', 'create_bdm_order');
add_action('wp_ajax_nopriv_create_bdm_order', 'create_bdm_order'); 

function create_bdm_order() {
    if (!isset($_POST['billing_code'], $_POST['amount'], $_POST['partner_email'], $_POST['products'])) {
        wp_send_json_error(['message' => 'Missing required parameters.']);
    }

    $billing_code = sanitize_text_field($_POST['billing_code']);
    $amount = floatval($_POST['amount']);
    $partner_email = sanitize_email($_POST['partner_email']);
    $products = $_POST['products']; 

    $order = wc_create_order();

    foreach ($products as $product_data) {
        $product_id = $product_data['id'];
        $quantity = $product_data['quantity'];

        $product = wc_get_product($product_id);
        if ($product) {
            $order->add_product($product, $quantity);
        }
    }

    $order->set_billing_email($partner_email);
    $order->set_billing_address_1('');
    $order->set_billing_city('');
    $order->set_billing_postcode(''); 

    $order->set_total($amount);

    $order->set_currency('BDM');

    $order->update_meta_data('_billing_code', $billing_code);

    $order->save();

    update_post_meta($order->get_id(), '_order_origin', 'BDM Checkout');

    wp_send_json_success(['order_id' => $order->get_id()]);
}

add_action('rest_api_init', function () {
    register_rest_route('store/v1', '/update-payment', array(
        'methods' => 'POST',
        'callback' => 'bdm_update_payment_status',
        'permission_callback' => '__return_true',
    ));
});

function bdm_update_payment_status($request) {
    $params = $request->get_json_params();
    $order_id = absint($params['order_id'] ?? 0);
    $status = strtolower(sanitize_text_field($params['status'] ?? ''));
    $key = sanitize_text_field($params['consumer_key'] ?? '');
    $secret = sanitize_text_field($params['consumer_secret'] ?? '');

    $valid_key = get_option('woocommerce_bdm-digital_settings')['rest_key'] ?? '';
    $valid_secret = get_option('woocommerce_bdm-digital_settings')['rest_secret'] ?? '';

    if ($key !== $valid_key || $secret !== $valid_secret) {
        return new WP_REST_Response(['message' => 'Unauthorized'], 401);
    }

    if (!$order_id || !in_array($status, ['completed', 'processing', 'failed'])) {
        return new WP_REST_Response(['message' => 'Invalid parameters'], 400);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_REST_Response(['message' => 'Order not found'], 404);
    }

    if (in_array($status, ['completed', 'processing'])) {
        $order->payment_complete(); 
        $order->update_status($status); 
    } elseif ($status === 'failed') {
        $order->update_status('failed', __('Pagamento falhou via BDM.', 'bdm-digital-payment-gateway'));
    }

    return new WP_REST_Response(['message' => 'Order updated'], 200);
}

add_action('plugins_loaded', 'init_gateway_class');
function init_gateway_class() {
    class WC_BDM_GATEWAY extends WC_Payment_Gateway {
        private $api_key;
        private $endpoint;
        private $partner_email;
        private $sandbox;
        private $asset;
        private $rest_key;
        private $rest_secret;

        public function __construct() {
            $this->id = 'bdm-digital';
            $this->method_title = __('BDM Digital', 'woocommerce');
            $this->method_description = __('Accept payments via BDM Digital.', 'woocommerce');
            $this->supports = array('products');
        
            $this->init_form_fields();
            $this->init_settings();
        
            $this->title = $this->get_option('title');
            $this->api_key = $this->get_option('api_key');
            $this->partner_email = $this->get_option('partner_email');
            $this->sandbox = $this->get_option('sandbox') === 'no';
            $this->asset = $this->get_option('asset') === 'BDM';
            $this->rest_key = $this->get_option('rest_key');
            $this->rest_secret = $this->get_option('rest_secret');
        
            $this->endpoint = bdm_get_api_endpoint($this->sandbox);
        
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable BDM Digital Payment', 'woocommerce'),
                    'default' => ''
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'default' => $this->method_title,
                ),   
                'method_description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'textarea',
                    'default' => $this->method_description,
                ),             
                'api_key' => array(
                    'title' => __('API Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your API Key', 'woocommerce'),
                    'default' => $this->get_option('api_key') ?? '',
                ),
                'partner_email' => array(
                    'title' => __('Partner Email', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter your partner email', 'woocommerce'),
                    'default' => $this->get_option('partner_email') ?? '',
                ),
                'asset' => array(
                    'title' => __('Asset', 'woocommerce'),
                    'type' => 'text',
                    'default' => $this->get_option('asset') ?? ''
                ),  
                'sandbox' => array(
                    'title' => __('Sandbox Mode', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable Sandbox Mode', 'woocommerce'),
                    'default' => $this->get_option('sandbox') ?? '',
                    'description' => __('Use the sandbox API endpoint for testing.', 'woocommerce'),
                ),  
                'rest_key' => array(
                    'title' => __('Rest API Key', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Rest API Key', 'woocommerce'),
                    'default' => $this->get_option('rest_key') ?? ''
                ),  
                'rest_secret' => array(
                    'title' => __('Rest API Secret', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Rest API Secret', 'woocommerce'),
                    'default' => $this->get_option('rest_secret') ?? ''
                )
            );
        }
    }
}