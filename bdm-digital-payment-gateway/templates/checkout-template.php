<?php
/**
 * Template de checkout personalizado para o plugin BDM Digital Payment Gateway.
 *
 * @package BDM_Digital_Payment_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Template Name: BDM Checkout
 * Description: Template for the BDM Digital Checkout page.
 *
 * @package BDM_Digital_Payment_Gateway
 */

// Standard for WordPress templates.
get_header();

?>

<section id="bdm-checkout-container" class="container mb-4 p-0">
	<ul class="steps d-flex flex-column p-0 m-0">
		<!-- Step 1: Checkout. -->
		<li data-section="checkout" id="step-1" class="d-flex flex-column">
			<h2 class="mb-4"><?php esc_html_e( 'Pedido', 'bdm-digital-payment-gateway' ); ?></h2>

			<?php
			if ( class_exists( 'WooCommerce' ) ) {
				echo wp_kses_post( bdmdipag_get_checkout_cart() );
			}
			?>

			<h3 class="d-flex align-items-center gap-2 m-0 mt-4 mb-4">
				<strong>
					<?php esc_html_e( 'BDM Digital', 'bdm-digital-payment-gateway' ); ?>
				</strong>
				<img width="24" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../assets/img/icon.png' ); ?>" alt="BDM Icon" />
			</h3>

			<div id="disclaimer" class="d-flex flex-column gap-3 p-4 mb-4">
				<img class="d-block me-auto" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../assets/img/logo.png' ); ?>" alt="BDM Logo" />
				<p class="m-0"><strong>Após a conclusão da compra, geraremos o código de pagamento em BDM DIGITAL.</strong></p>
				<p class="m-0">
					<small>Nosso sistema identifica automaticamente o pagamento, dispensando a necessidade de envio de comprovantes.</small>
				</p>
			</div>

			<button id="bdm-checkout-button" class="btn btn-primary d-block m-auto col-12 col-sm-auto"><?php esc_html_e( 'Finalizar Pedido', 'bdm-digital-payment-gateway' ); ?></button>
		</li>

		<!-- Step 2: Payment Instructions -->
		<li data-section="billingcode" id="step-2" class="d-none flex-column gap-3">
			<h2 class="m-0"><?php esc_html_e( 'Efetue o pagamento para concluir.', 'bdm-digital-payment-gateway' ); ?></h2>
			<p class="m-0"><?php esc_html_e( 'Escaneie o QR code ou copie o código abaixo para realizar o pagamento em BDM DIGITAL.', 'bdm-digital-payment-gateway' ); ?><br/>
				<?php esc_html_e( 'O sistema irá reconhecer automaticamente a transferência.', 'bdm-digital-payment-gateway' ); ?>
				<br/><br/>
				<?php esc_html_e( 'O pagamento pode levar até 5 minutos para a confirmação.', 'bdm-digital-payment-gateway' ); ?>
			</p>
			<p class="d-flex cotation-block m-0 flex-column text-center align-items-center justify-items-center">
				<span>R$<span class="amount"></span> | BDM <span class="amount-bdm"></span></span>
				<span>Cotação oficial 1 BDM Digital = R$ <span class="cotation"></span></span>
			</p>
			<div id="qrcode" class="d-flex justify-content-center align-items-center text-center"></div>
			<p id="expiration" class="text-center col-12 col-sm-auto m-auto d-block mt-2 mb-3">O QR Code e o código expiram em <span id="counter"></span></p>
			<p id="billingcode" class="d-flex m-auto justify-content-center align-items-center text-center"></p>
			<button id="bdm-copycode" class="btn btn-primary d-block m-auto col-12 col-sm-auto"><?php esc_html_e( 'Copiar Código', 'bdm-digital-payment-gateway' ); ?></button>
		</li>

		<!-- Step 3: Payment Success -->
		<li data-section="success" id="step-3" class="d-none flex-column justify-content-center align-items-center text-center gap-4">
			<div class="d-flex w-100">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../assets/img/logo.png' ); ?>" alt="BDM Logo" />
			</div>
			
			<h3 class="me-auto"><?php esc_html_e( 'Pagamento realizado com sucesso!', 'bdm-digital-payment-gateway' ); ?></h3>

			<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../assets/img/success.png' ); ?>" alt="Success" />

			<p class="d-flex cotation-block m-0 flex-column text-center align-items-center justify-items-center">
				<span>R$<span class="amount"></span> | BDM <span class="amount-bdm"></span></span>
				<span>Cotação oficial 1 BDM Digital = R$ <span class="cotation"></span></span>
			</p>            

			<p>
				<?php esc_html_e( 'Seu pagamento em BDM DIGITAL foi confirmado!', 'bdm-digital-payment-gateway' ); ?><br/><br/>
				<?php esc_html_e( 'Seu pedido já está sendo preparado e em breve será enviado para o seu endereço.', 'bdm-digital-payment-gateway' ); ?>
			</p>
		</li>
	</ul>
</section>

<!-- Loading State -->
<div
	class="loading d-none flex-column justify-content-center align-items-center h-100 vw-100">
	<div class="loader"></div>
</div>

<?php get_footer(); ?>

<?php
/**
 * Function to display the WooCommerce cart in the checkout template.
 *
 * @return string HTML of cart items and total.
 */
function bdmdipag_get_checkout_cart() {
	$cart_items = WC()->cart->get_cart();
	$total      = 0;
	$output     = '<ul class="cart">
        <li class="cart-header d-flex justify-content-between align-items-center">
            <p class="m-0 p-0">Produto</p>
            <p class="m-0 p-0">Subtotal</p>
        </li>';

	if ( ! empty( $cart_items ) ) {
		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			$product_name  = esc_html( $cart_item['data']->get_name() );
			$quantity      = esc_html( $cart_item['quantity'] );
			$line_total    = (float) $cart_item['line_total'];
			$product_price = wc_price( $line_total );

			$total += $line_total;

			$output .= sprintf(
				'<li class="m-0 p-0 d-flex justify-content-between align-items-center">%s (%s) <span>%s</span></li>',
				$product_name,
				$quantity,
				$product_price
			);
		}

		$output .= sprintf( '<li class="cart-footer d-flex justify-content-between align-items-center"><p class="m-0 p-0">Total</p> <p class="m-0 p-0">%s</p></li>', wc_price( $total ) );
		$output .= '</ul>';
	} else {
		$output .= '<p>' . esc_html__( 'No products in cart.', 'bdm-digital-payment-gateway' ) . '</p>';
	}

	return $output;
}
?>