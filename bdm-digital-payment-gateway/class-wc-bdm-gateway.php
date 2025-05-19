<?php
/**
 * Plugin Name: BDM Digital Payment Gateway
 * Description: Um plugin para processar pagamentos utilizando BDM Digital. Suporta geração de QR codes, processamento de pagamentos, validação de transações e fornecimento de confirmações. Permite integração com várias carteiras e serviços associados.
 * Version: 1.2.0
 * Author: Dourado Cash
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package BDM_Digital_Payment_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	require_once ABSPATH . 'wp-includes/rest-api.php';
}

register_activation_hook( __FILE__, 'bdm_activate_plugin' );
/**
 * Ativa o plugin e garante que o WooCommerce está ativo.
 *
 * @return void
 */
function bdm_activate_plugin() {
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'BDM Digital Payment Gateway requer WooCommerce.', 'bdm-digital-payment-gateway' ),
			esc_html__( 'Plugin Activation Error', 'bdm-digital-payment-gateway' ),
			array( 'back_link' => true )
		);
	} else {
		bdm_create_checkout_page();
	}
}

add_filter( 'woocommerce_currencies', 'bdm_add_custom_currency' );
/**
 * Adiciona a moeda BDM Digital ao WooCommerce.
 *
 * @param array $currencies Moedas existentes.
 * @return array Moedas modificadas.
 */
function bdm_add_custom_currency( $currencies ) {
	$currencies['BDM'] = __( 'BDM Digital', 'bdm-digital-payment-gateway' );
	return $currencies;
}

add_filter( 'woocommerce_currency_symbol', 'bdm_add_custom_currency_symbol', 10, 2 );
/**
 * Adiciona o símbolo da moeda BDM Digital.
 *
 * @param string $currency_symbol Símbolo existente.
 * @param string $currency Código da moeda.
 * @return string Símbolo modificado.
 */
function bdm_add_custom_currency_symbol( $currency_symbol, $currency ) {
	switch ( $currency ) {
		case 'BDM':
			$currency_symbol = 'BDM';
			break;
	}
	return $currency_symbol;
}

add_filter(
	'woocommerce_rest_prepare_shop_order_object',
	function ( $response, $post ) {
		$order                                        = wc_get_order( $post->ID );
		$origin                                       = $order->get_meta( '_order_origin' );
		$response->data['meta_data']['_order_origin'] = $origin;
		return $response;
	},
	10,
	3
);

/**
 * Cria a página de checkout personalizada para BDM.
 *
 * @return void
 */
function bdm_create_checkout_page() {
	$page = array(
		'post_title'   => 'BDM Checkout',
		'post_name'    => 'bdm-checkout',
		'post_content' => '[woocommerce_checkout]',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_author'  => 1,
	);

	if ( ! get_page_by_path( 'bdm-checkout' ) ) {
		wp_insert_post( $page );
		update_option( 'woocommerce_checkout_page_id', get_page_by_path( 'bdm-checkout' )->ID );
	}
}

register_deactivation_hook( __FILE__, 'bdm_remove_checkout_page' );
/**
 * Remove a página de checkout personalizada do BDM.
 *
 * @return void
 */
function bdm_remove_checkout_page() {
	$page = get_page_by_path( 'bdm-checkout' );
	if ( $page ) {
		wp_delete_post( $page->ID, true );
	}
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bdm_add_settings_link' );
/**
 * Adiciona link de configurações na página de plugins.
 *
 * @param array $links Links existentes.
 * @return array Links modificados.
 */
function bdm_add_settings_link( $links ) {
	$url           = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bdm-digital' );
	$settings_link = '<a href="' . esc_url( $url ) . '">' . __( 'Configurações', 'bdm-digital-payment-gateway' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

add_filter( 'theme_page_templates', 'bdm_register_custom_template' );
/**
 * Registra template customizado para o checkout BDM.
 *
 * @param array $templates Templates existentes.
 * @return array Templates modificados.
 */
function bdm_register_custom_template( $templates ) {
	$templates['templates/checkout-template.php'] = __( 'BDM Checkout Template', 'bdm-digital-payment-gateway' );
	return $templates;
}

add_filter( 'template_include', 'bdm_load_custom_template' );
/**
 * Carrega o template customizado para o checkout BDM.
 *
 * @param string $template Caminho do template existente.
 * @return string Caminho do template modificado.
 */
function bdm_load_custom_template( $template ) {
	if ( is_page( 'bdm-checkout' ) ) {
		$plugin_template = plugin_dir_path( __FILE__ ) . 'templates/checkout-template.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}
	return $template;
}

add_filter( 'woocommerce_payment_gateways', 'bdm_register_gateway_class' );
/**
 * Registra a classe do gateway BDM no WooCommerce.
 *
 * @param array $gateways Gateways existentes.
 * @return array Gateways modificados.
 */
function bdm_register_gateway_class( $gateways ) {
	$gateways[] = 'WC_BDM_GATEWAY';
	return $gateways;
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'woocommerce_page_wc-orders' === $hook ) {
			wp_enqueue_script(
				'bdm-admin-checkout-handler',
				plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
				array( 'jquery' ),
				filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/admin.js' ),
				true
			);
		}
	}
);

/**
 * Retorna o endpoint da API conforme o modo sandbox.
 *
 * @param string $sandbox Modo sandbox.
 * @return string Endpoint da API.
 */
function bdm_get_api_endpoint( $sandbox ) {
	return ( $sandbox && 'no' !== $sandbox )
		? 'https://opiihi8ab4.execute-api.us-east-2.amazonaws.com/'
		: 'https://partner.dourado.cash/';
}

/**
 * Enfileira scripts e estilos para o checkout BDM.
 *
 * @return void
 */
function bdm_enqueue_scripts() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( is_checkout() || is_page( 'bdm-checkout' ) ) {
		$plugin_url = ( function_exists( 'plugins_url' ) ) ? plugins_url( '', __FILE__ ) : '';
		wp_enqueue_style(
			'bdm-checkout-style',
			$plugin_url . '/assets/css/style.min.css',
			array(),
			'1.0.0',
			'all'
		);
		wp_enqueue_style(
			'bootstrap',
			$plugin_url . '/assets/css/bootstrap.min.css',
			array(),
			'5.3.3',
			'all'
		);
		wp_enqueue_script(
			'bdm-checkout-js',
			$plugin_url . '/assets/js/main.js',
			array( 'jquery' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/main.js' ),
			true
		);
		wp_enqueue_script(
			'toast-js',
			$plugin_url . '/assets/js/jquery.toast.min.js',
			array( 'jquery' ),
			'1.3.2',
			true
		);
		wp_enqueue_style(
			'toast-css',
			$plugin_url . '/assets/css/jquery.toast.min.css',
			array(),
			'1.3.2',
			'all'
		);

		$settings = get_option( 'woocommerce_bdm-digital_settings' );

		$checkout_data = array(
			'products' => array_map(
				function ( $item ) {
					return array(
						'id'       => $item['product_id'],
						'title'    => $item['data']->get_name(),
						'quantity' => $item['quantity'],
						'price'    => wc_price( $item['line_total'] ),
					);
				},
				WC()->cart->get_cart()
			),
			'settings' => array(
				'api_key'            => isset( $settings['api_key'] ) ? $settings['api_key'] : '',
				'endpoint'           => bdm_get_api_endpoint( isset( $settings['sandbox'] ) ? $settings['sandbox'] : '' ),
				'asset'              => isset( $settings['asset'] ) ? $settings['asset'] : '',
				'partner_email'      => isset( $settings['partner_email'] ) ? $settings['partner_email'] : '',
				'sandbox'            => isset( $settings['sandbox'] ) ? $settings['sandbox'] : '',
				'consumer_key'       => isset( $settings['rest_key'] ) ? $settings['rest_key'] : '',
				'endpoint_quotation' => bdm_get_api_endpoint( isset( $settings['sandbox'] ) ? $settings['sandbox'] : '' ) . 'ecommerce-partner/clients/quotation/all',
				'consumer_secret'    => isset( $settings['rest_secret'] ) ? $settings['rest_secret'] : '',
				'site_url'           => get_bloginfo( 'url' ),
				'site_name'          => get_bloginfo( 'name' ),
			),
		);

		wp_localize_script(
			'bdm-checkout-js',
			'bdm_checkout_data',
			array_merge(
				$checkout_data,
				array(
					'nonce' => wp_create_nonce( 'bdm_create_order_nonce' ),
				)
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'bdm_enqueue_scripts' );
add_action( 'wp_ajax_create_bdm_order', 'create_bdm_order' );
add_action( 'wp_ajax_nopriv_create_bdm_order', 'create_bdm_order' );

/**
 * Lida com a criação de pedidos BDM via AJAX.
 *
 * @return void
 */
function create_bdm_order() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bdm_create_order_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
	}
	$billing_code  = isset( $_POST['billing_code'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_code'] ) ) : '';
	$amount        = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
	$partner_email = isset( $_POST['partner_email'] ) ? sanitize_email( wp_unslash( $_POST['partner_email'] ) ) : '';
	$products      = isset( $_POST['products'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['products'] ) ), true ) : array();
	if ( ! is_array( $products ) ) {
		wp_send_json_error( array( 'message' => 'Invalid products data.' ) );
	}

	$order = wc_create_order();

	foreach ( $products as $product_data ) {
		$product_id = $product_data['id'];
		$quantity   = $product_data['quantity'];

		$product = wc_get_product( $product_id );
		if ( $product ) {
			$order->add_product( $product, $quantity );
		}
	}

	$order->set_billing_email( $partner_email );
	$order->set_billing_address_1( '' );
	$order->set_billing_city( '' );
	$order->set_billing_postcode( '' );

	$order->set_total( $amount );
	$order->set_currency( 'BDM' );
	$order->update_meta_data( '_billing_code', $billing_code );
	$order->save();
	update_post_meta( $order->get_id(), '_order_origin', 'BDM Checkout' );
	wp_send_json_success( array( 'order_id' => $order->get_id() ) );
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'store/v1',
			'/update-payment',
			array(
				'methods'             => 'POST',
				'callback'            => 'bdm_update_payment_status',
				'permission_callback' => '__return_true',
			)
		);
	}
);

/**
 * Atualiza o status de pagamento do pedido BDM via REST API.
 *
 * @param WP_REST_Request $request Objeto da requisição REST.
 * @return WP_REST_Response Resposta REST.
 */
function bdm_update_payment_status( $request ) {
	$params   = $request->get_json_params();
	$order_id = absint( isset( $params['order_id'] ) ? $params['order_id'] : 0 );
	$status   = strtolower( sanitize_text_field( isset( $params['status'] ) ? $params['status'] : '' ) );
	$key      = sanitize_text_field( isset( $params['consumer_key'] ) ? $params['consumer_key'] : '' );
	$secret   = sanitize_text_field( isset( $params['consumer_secret'] ) ? $params['consumer_secret'] : '' );

	$settings     = get_option( 'woocommerce_bdm-digital_settings' );
	$valid_key    = isset( $settings['rest_key'] ) ? $settings['rest_key'] : '';
	$valid_secret = isset( $settings['rest_secret'] ) ? $settings['rest_secret'] : '';

	if ( $key !== $valid_key || $secret !== $valid_secret ) {
		return new WP_REST_Response( array( 'message' => 'Unauthorized' ), 401 );
	}

	if ( ! $order_id || ! in_array( $status, array( 'completed', 'processing', 'failed' ), true ) ) {
		return new WP_REST_Response( array( 'message' => 'Invalid parameters' ), 400 );
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return new WP_REST_Response( array( 'message' => 'Order not found' ), 404 );
	}

	if ( in_array( $status, array( 'completed', 'processing' ), true ) ) {
		$order->payment_complete();
		$order->update_status( $status );
	} elseif ( 'failed' === $status ) {
		$order->update_status( 'failed', __( 'Pagamento falhou via BDM.', 'bdm-digital-payment-gateway' ) );
	}

	return new WP_REST_Response( array( 'message' => 'Order updated' ), 200 );
}

add_action( 'plugins_loaded', 'bdm_init_gateway_class' );
/**
 * Inicializa a classe do gateway BDM.
 *
 * @return void
 */
function bdm_init_gateway_class() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	/**
	 * Classe do gateway BDM Digital.
	 */
	class WC_BDM_GATEWAY extends WC_Payment_Gateway {
		/**
		 * API Key utilizada para autenticação.
		 *
		 * @var string
		 */
		public $api_key;
		/**
		 * Endpoint da API BDM.
		 *
		 * @var string
		 */
		public $endpoint;
		/**
		 * E-mail do parceiro BDM.
		 *
		 * @var string
		 */
		public $partner_email;
		/**
		 * Indica se o modo sandbox está ativado.
		 *
		 * @var bool
		 */
		public $sandbox;
		/**
		 * Asset utilizado nas transações.
		 *
		 * @var string
		 */
		public $asset;
		/**
		 * Chave REST API.
		 *
		 * @var string
		 */
		public $rest_key;
		/**
		 * Segredo REST API.
		 *
		 * @var string
		 */
		public $rest_secret;

		/**
		 * Construtor da classe.
		 */
		public function __construct() {
			$this->id                 = 'bdm-digital';
			$this->method_title       = __( 'BDM Digital', 'bdm-digital-payment-gateway' );
			$this->method_description = __( 'Accept payments via BDM Digital.', 'bdm-digital-payment-gateway' );
			$this->supports           = array( 'products' );

			$this->init_form_fields();
			$this->init_settings();

			$this->title         = $this->get_option( 'title' );
			$this->api_key       = $this->get_option( 'api_key' );
			$this->partner_email = $this->get_option( 'partner_email' );
			$this->sandbox       = ( 'yes' === $this->get_option( 'sandbox' ) );
			$this->asset         = $this->get_option( 'asset' );
			$this->rest_key      = $this->get_option( 'rest_key' );
			$this->rest_secret   = $this->get_option( 'rest_secret' );

			$this->endpoint = bdm_get_api_endpoint( $this->sandbox );

			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				array( $this, 'process_admin_options' )
			);
		}

		/**
		 * Inicializa os campos do formulário de configuração.
		 *
		 * @return void
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'       => array(
					'title'   => __( 'Enable/Disable', 'bdm-digital-payment-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable BDM Digital Payment', 'bdm-digital-payment-gateway' ),
					'default' => 'no',
				),
				'title'         => array(
					'title'   => __( 'Title', 'bdm-digital-payment-gateway' ),
					'type'    => 'text',
					'default' => __( 'BDM Digital Payment', 'bdm-digital-payment-gateway' ),
				),
				'api_key'       => array(
					'title'       => __( 'API Key', 'bdm-digital-payment-gateway' ),
					'type'        => 'text',
					'description' => __( 'Enter your API Key', 'bdm-digital-payment-gateway' ),
					'default'     => '',
				),
				'partner_email' => array(
					'title'       => __( 'Partner Email', 'bdm-digital-payment-gateway' ),
					'type'        => 'email',
					'description' => __( 'Enter your partner email', 'bdm-digital-payment-gateway' ),
					'default'     => '',
				),
				'asset'         => array(
					'title'   => __( 'Asset', 'bdm-digital-payment-gateway' ),
					'type'    => 'text',
					'default' => 'BDM',
				),
				'sandbox'       => array(
					'title'       => __( 'Sandbox Mode', 'bdm-digital-payment-gateway' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable Sandbox Mode', 'bdm-digital-payment-gateway' ),
					'default'     => 'no',
					'description' => __( 'Use the sandbox API endpoint for testing.', 'bdm-digital-payment-gateway' ),
				),
				'rest_key'      => array(
					'title'   => __( 'REST API Key', 'bdm-digital-payment-gateway' ),
					'type'    => 'text',
					'default' => '',
				),
				'rest_secret'   => array(
					'title'   => __( 'REST API Secret', 'bdm-digital-payment-gateway' ),
					'type'    => 'text',
					'default' => '',
				),
			);
		}
	}
}

add_action( 'plugins_loaded', 'bdm_load_plugin_textdomain' );
/**
 * Carrega o textdomain do plugin para traduções.
 *
 * @return void
 */
function bdm_load_plugin_textdomain() {
	load_plugin_textdomain( 'bdm-digital-payment-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
