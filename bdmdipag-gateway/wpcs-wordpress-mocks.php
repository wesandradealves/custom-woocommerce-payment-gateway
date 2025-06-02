<?php
// Arquivo de mocks para rodar PHPCS sem erros de funções WordPress.
if ( ! function_exists( 'add_action' ) ) {
	function add_action() {}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {}
}
if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook() {}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook() {}
}
if ( ! function_exists( 'is_plugin_active' ) ) {
	/**
	 * Mock for is_plugin_active.
	 *
	 * @param string $plugin Optional. Path to the plugin file relative to the plugins directory.
	 * @return bool Always returns true in mock.
	 */
	function is_plugin_active( $plugin = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return true;
	}
}
if ( ! function_exists( 'deactivate_plugins' ) ) {
	/**
	 * Mock for deactivate_plugins.
	 *
	 * @param string|array $plugins Optional. Single plugin or list of plugins to deactivate.
	 */
	function deactivate_plugins( $plugins = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return;
	}
}
if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename() { return ''; }
}
if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Mock for esc_html__.
	 *
	 * @param string $text Text to translate.
	 * @param string $domain Optional. Text domain. Not used in mock.
	 * @return string Returns $text.
	 */
	function esc_html__( $text, $domain = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return $text;
	}
}
if ( ! function_exists( 'wp_die' ) ) {
	function wp_die() {}
}
if ( ! function_exists( '__' ) ) {
	/**
	 * Mock for __().
	 *
	 * @param string $text Text to translate.
	 * @param string $domain Optional. Text domain. Not used in mock.
	 * @return string Returns $text.
	 */
	function __( $text, $domain = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return $text;
	}
}
if ( ! function_exists( 'get_page_by_path' ) ) {
	/**
	 * Mock for get_page_by_path.
	 *
	 * @param string $page_path Optional. Page path.
	 * @return object Returns mock page object.
	 */
	function get_page_by_path( $page_path = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return (object) array( 'ID' => 1 );
	}
}
if ( ! function_exists( 'wp_insert_post' ) ) {
	/**
	 * Mock for wp_insert_post.
	 *
	 * @param array $postarr Optional. Post data.
	 */
	function wp_insert_post( $postarr = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Mock for update_option.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 */
	function update_option( $option = '', $value = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return;
	}
}
if ( ! function_exists( 'wp_delete_post' ) ) {
	/**
	 * Mock for wp_delete_post.
	 *
	 * @param int  $postid Post ID.
	 * @param bool $force_delete Optional. Not used in mock.
	 */
	function wp_delete_post( $postid = 0, $force_delete = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return;
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url($url = '') { return $url; }
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url($url) { return $url; }
}
if ( ! function_exists( 'is_page' ) ) {
	function is_page() { return false; }
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path() { return ''; }
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url() { return ''; }
}
if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style() {}
}
if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script() {}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option() { return array(); }
}
if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo() { return ''; }
}
if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce() { return 'mocked_nonce'; }
}
if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script() {}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash($value) { return $value; }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field($value) { return $value; }
}
if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email($value) { return $value; }
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	/**
	 * Mock for wp_verify_nonce.
	 *
	 * @param string $nonce  The nonce value.
	 * @param string $action The action name.
	 * @return bool Always returns true in mock.
	 */
	function wp_verify_nonce( $nonce = '', $action = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return true;
	}
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
	/**
	 * Mock for wp_send_json_error.
	 *
	 * @param mixed $data Optional. Data to send.
	 */
	function wp_send_json_error( $data = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return;
	}
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
	/**
	 * Mock for wp_send_json_success.
	 *
	 * @param mixed $data Optional. Data to send.
	 */
	function wp_send_json_success( $data = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return;
	}
}
if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta() {}
}
if ( ! function_exists( 'absint' ) ) {
	function absint($val) { return (int) $val; }
}
if ( ! function_exists( 'register_rest_route' ) ) {
	/**
	 * Mock for register_rest_route.
	 *
	 * @param string $namespace Namespace.
	 * @param string $route     Route.
	 * @param array  $args      Arguments.
	 */
	function register_rest_route( $namespace = '', $route = '', $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return;
	}
}
if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		public function __construct( $data = array(), $status = 200 ) {}
	}
}
if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	class WC_Payment_Gateway {
		public function get_option( $key ) { return ''; }
		public function init_form_fields() {}
		public function init_settings() {}
	}
}
if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Mock for WP_REST_Request class.
	 */
	class WP_REST_Request {}
}
