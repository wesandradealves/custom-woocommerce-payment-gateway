<?php
/*
 * Plugin Name: BDM Digital Payment Gateway
 * Plugin URI: https://mercado.dourado.cash/
 * Description: Um plugin para processar pagamentos utilizando BDM Digital. Suporta geração de QR codes, processamento de pagamentos, validação de transações e fornecimento de confirmações. Permite integração com várias carteiras e serviços associados.
 * Version: 0.0.4
 * Author: Dourado Cash
 * Author URI: https://mercado.dourado.cash/
 */

// Definir constantes
define('BDM_PAYMENT_GATEWAY_OPTION_KEY', 'bdm_payment_gateway_options');
define('BDM_PAYMENT_GATEWAY_MENU_SLUG', 'bdm-payment-gateway-settings');
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Registrar menu de administração
// add_action('admin_menu', 'bdm_register_settings_page');
// function bdm_register_settings_page() {
//     add_options_page(
//         __('Configurações do BDM Digital Payment Gateway', 'bdm-digital-payment-gateway'), 
//         __('BDM Digital Payment Gateway', 'bdm-digital-payment-gateway'), 
//         'manage_options', 
//         BDM_PAYMENT_GATEWAY_MENU_SLUG, 
//         'bdm_render_settings_page'
//     );
// }

// Carregar estilos personalizados
add_action('admin_enqueue_scripts', 'bdm_enqueue_admin_styles');
function bdm_enqueue_admin_styles($hook) {
    if ($hook === 'settings_page_' . BDM_PAYMENT_GATEWAY_MENU_SLUG) {
        wp_enqueue_style(
            'bdm-admin-styles',
            plugin_dir_url(__FILE__) . 'assets/css/style.css'
        );
    }
}

// Renderizar a página de configurações
function bdm_render_settings_page() {
    ?>
    <section id="bdm-settings-page" class="p-5 animate__animated animate__fadeIn">
        <header class="d-block mb-4">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/img/logo.png'; ?>" alt="BDM Digital" />
        </header>
        <form method="post" action="options.php" class="bdm-settings-form">
            <?php
                settings_fields(BDM_PAYMENT_GATEWAY_OPTION_KEY);
                do_settings_sections(BDM_PAYMENT_GATEWAY_MENU_SLUG);
                submit_button(__('Salvar Configurações', 'bdm-digital-payment-gateway'), 'primary large');
            ?>
        </form>
    </section>
    <?php
}

// Inicializar configurações do plugin
add_action('admin_init', 'bdm_initialize_settings');
function bdm_initialize_settings() {
    register_setting(
        BDM_PAYMENT_GATEWAY_OPTION_KEY,
        BDM_PAYMENT_GATEWAY_OPTION_KEY,
        'bdm_validate_settings'
    );

    add_settings_section(
        'bdm_settings_section', 
        __('Configuração do Gateway de Pagamento', 'bdm-digital-payment-gateway'), 
        'bdm_section_description', 
        BDM_PAYMENT_GATEWAY_MENU_SLUG
    );

    $fields = [
        'bdm_access_token' => __('Token de Acesso', 'bdm-digital-payment-gateway'),
        'bdm_webhook_url' => __('URL do Webhook', 'bdm-digital-payment-gateway'),
        'bdm_base_endpoint' => __('Endpoint Base', 'bdm-digital-payment-gateway'),
        'bdm_wallet_email' => __('E-mail da Carteira', 'bdm-digital-payment-gateway'),
    ];

    foreach ($fields as $field_id => $field_label) {
        add_settings_field(
            $field_id,
            $field_label,
            'bdm_render_field',
            BDM_PAYMENT_GATEWAY_MENU_SLUG,
            'bdm_settings_section',
            ['id' => $field_id]
        );
    }
}

// Descrição da seção
function bdm_section_description() {
    echo '<p>' . __('Insira os detalhes necessários para integração com o gateway de pagamento BDM Digital.<br/>Todos os campos são obrigatórios.', 'bdm-digital-payment-gateway') . '</p>';
}

// Sanitizar e validar configurações
function bdm_validate_settings($input) {
    $sanitized = [];
    $required_fields = [
        'bdm_access_token' => __('Token de Acesso', 'bdm-digital-payment-gateway'),
        'bdm_webhook_url' => __('URL do Webhook', 'bdm-digital-payment-gateway'),
        'bdm_base_endpoint' => __('Endpoint Base', 'bdm-digital-payment-gateway'),
        'bdm_wallet_email' => __('E-mail da Carteira', 'bdm-digital-payment-gateway'),
    ];

    foreach ($required_fields as $field_id => $field_label) {
        if (empty($input[$field_id])) {
            add_settings_error(
                BDM_PAYMENT_GATEWAY_OPTION_KEY,
                $field_id,
                sprintf(__('O campo %s é obrigatório.', 'bdm-digital-payment-gateway'), $field_label),
                'error'
            );
        } else {
            switch ($field_id) {
                case 'bdm_webhook_url':
                case 'bdm_base_endpoint':
                    $sanitized[$field_id] = esc_url_raw($input[$field_id]);
                    break;
                case 'bdm_wallet_email':
                    $sanitized[$field_id] = sanitize_email($input[$field_id]);
                    break;
                default:
                    $sanitized[$field_id] = sanitize_text_field($input[$field_id]);
            }
        }
    }

    return $sanitized;
}

// Renderizar campos individuais
function bdm_render_field($args) {
    $options = get_option(BDM_PAYMENT_GATEWAY_OPTION_KEY, []);
    $value = $options[$args['id']] ?? '';
    $type = match ($args['id']) {
        'bdm_webhook_url', 'bdm_base_endpoint' => 'url',
        'bdm_wallet_email' => 'email',
        default => 'text',
    };
    printf(
        '<input type="%1$s" id="%2$s" name="%3$s[%2$s]" value="%4$s" class="regular-text" required>',
        esc_attr($type),
        esc_attr($args['id']),
        BDM_PAYMENT_GATEWAY_OPTION_KEY,
        esc_attr($value)
    );
}

// Adicionar link de configurações na lista de plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bdm_add_settings_link');
function bdm_add_settings_link($links) {
    $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=bdm-digital');
    $settings_link = '<a href="' . esc_url($url) . '">' . __('Configurações', 'bdm-digital-payment-gateway') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Register Template

add_filter('theme_page_templates', 'bdm_register_custom_template');
function bdm_register_custom_template($templates) {
    $templates['bdm-checkout-template.php'] = __('BDM Checkout Template', 'bdm-digital-payment-gateway');
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


// Hook into plugin activation
register_activation_hook(__FILE__, 'bdm_plugin_activation');

function bdm_plugin_activation() {
    // Check if WooCommerce is active
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('BDM Digital Payment Gateway requires WooCommerce to be installed and activated.', 'bdm-digital-payment-gateway'),
            __('Plugin Activation Error', 'bdm-digital-payment-gateway'),
            ['back_link' => true]
        );
    }

    // Create checkout page if WooCommerce is active
    bdm_create_checkout_page();
}

function bdm_create_checkout_page() {
    $checkout_page = [
        'post_title'    => 'BDM - Checkout',
        'post_content'  => '',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_name'     => 'bdm-checkout',
    ];

    $existing_page = get_page_by_path('bdm-checkout');
    if (!$existing_page) {
        $post_id = wp_insert_post($checkout_page);
        if ($post_id) {
            update_post_meta($post_id, '_wp_page_template', 'bdm-checkout-template.php');
        }
    }
}

// Display admin notice if WooCommerce is missing
add_action('admin_notices', 'bdm_woocommerce_missing_notice');

function bdm_woocommerce_missing_notice() {
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        echo '<div class="notice notice-error"><p>'
            . __('BDM Digital Payment Gateway requires WooCommerce to function properly. Please install and activate WooCommerce.', 'bdm-digital-payment-gateway')
            . '</p></div>';
    }
}


// Remove checkout page when uninstalling
register_uninstall_hook(__FILE__, 'bdm_remove_checkout_page');

function bdm_remove_checkout_page() {
    $checkout_page = get_page_by_path('bdm-checkout');
    if ($checkout_page) {
        wp_delete_post($checkout_page->ID, true);
    }
}

add_action('wp_enqueue_scripts', 'bdm_enqueue_checkout_scripts');

// Enqueue Scripts

function bdm_enqueue_checkout_scripts() {
    if (is_page('bdm-checkout')) {
        wp_enqueue_script(
            'bdm-checkout-app',
            plugin_dir_url(__FILE__) . 'assets/js/app.js',
            [],
            '1.0.0',
            true
        );
    }
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'misha_add_gateway_class' );
function misha_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Misha_Gateway'; // your class name is here
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'misha_init_gateway_class' );
function misha_init_gateway_class() {

	class WC_Misha_Gateway extends WC_Payment_Gateway {

 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
            $this->id = 'bdm-digital'; 
            $this->icon = ''; 
            $this->has_fields = true; 
            $this->method_title = 'BDM Digital Payment Gateway';
            $this->method_description = 'Payment processing with BDM Digital'; 
        
            $this->supports = array(
                'products'
            );

            $this->init_form_fields();

            $this->init_settings();

            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->api_key = $this->get_option( 'api_key' );
            // $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
        
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
            
            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
 		}

		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable',
                    'label'       => 'Enable paymets with BDM Digital',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'BDM Digital',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    // 'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pague com BDM Digital',
                ),
                'testmode' => array(
                    'title'       => 'Sandbox',
                    'label'       => 'Sandbox',
                    'type'        => 'checkbox',
                    // 'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'api_key' => array(
                    'title'       => 'API Key',
                    'type'        => 'text'
                ),
                // 'test_private_key' => array(
                //     'title'       => 'Test Private Key',
                //     'type'        => 'password',
                // ),
                // 'publishable_key' => array(
                //     'title'       => 'Live Publishable Key',
                //     'type'        => 'text'
                // ),
                // 'private_key' => array(
                //     'title'       => 'Live Private Key',
                //     'type'        => 'password'
                // )
            );
	 	}

		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {

				 
		}

		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function payment_scripts() {

	
	 	}

		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {


		}

		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {

					
	 	}

		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {

					
	 	}
 	}
}