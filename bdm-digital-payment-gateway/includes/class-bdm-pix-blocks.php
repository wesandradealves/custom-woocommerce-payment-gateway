<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class BDM_PIX_Blocks extends AbstractPaymentMethodType {
    public function get_name() {
        return 'bdm_pix';
    }

    public function initialize() {
        add_filter('woocommerce_rest_checkout_process_payment_with_context', [$this, 'process_payment'], 10, 3);
    }

    public function is_active() {
        $gateway = WC()->payment_gateways()->payment_gateways()['bdm_pix'] ?? null;
        return $gateway && 'yes' === $gateway->enabled;
    }

    public function get_payment_method_script_handles() {
        return [];
    }

    public function get_payment_method_data() {
        return [
            'title'       => __('BDM PIX', 'woocommerce'),
            'description' => __('Pay using PIX QR Code.', 'woocommerce'),
            'supports'    => ['products'],
        ];
    }

    public function process_payment($order, $data) {
        $gateway = WC()->payment_gateways()->payment_gateways()['bdm_pix'] ?? null;
        if (!$gateway) {
            throw new Exception(__('Payment method not available', 'woocommerce'));
        }
        return $gateway->process_payment($order->get_id());
    }
}
