<?php
/**
 * Template Name: BDM Checkout
 * Description: Template for the BDM Digital Checkout page.
 */

get_header();  // Standard for WordPress templates

?>

<section id="bdm-checkout-container" class="container mb-4 p-0">
    <ul class="steps d-flex flex-column p-0 m-0">
        <!-- Step 1: Checkout -->
        <li data-section="checkout" id="step-1" class="d-flex flex-column">
            <h2 class="mb-4"><?php esc_html_e( 'Pedido', 'bdm-digital-payment-gateway' ); ?></h2>

            <?php
                if ( class_exists( 'WooCommerce' ) ) {
                    echo wp_kses_post( get_bdm_checkout_cart() );
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
            <div id="qrcode" class="d-flex justify-content-center align-items-center text-center">
                <svg class="d-none" version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="512" height="512">
                    <title>padlock</title>
                    <defs>
                        <image  width="362" height="482" id="img1" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAWoAAAHiCAMAAAD25XZkAAAAAXNSR0IB2cksfwAAAwBQTFRF////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////vy5IYQAAAQB0Uk5TAA4tSVxugZSnu8zT3enuW0gNFkRtjq7N7f+tjWxDFQQxYehggLPv9MSITRMLS9T9k0oKImb3+3czATaM4N+LNSva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"/>
                    </defs>
                    <style>
                    </style>
                    <use id="Background" href="#img1" x="75" y="15"/>
                </svg>                
            </div>
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
function get_bdm_checkout_cart() {
    $cart_items = WC()->cart->get_cart();
    $total = 0;
    $output = '<ul class="cart">
        <li class="cart-header d-flex justify-content-between align-items-center">
            <p class="m-0 p-0">Produto</p>
            <p class="m-0 p-0">Subtotal</p>
        </li>';

    if ( ! empty( $cart_items ) ) {
        foreach ( $cart_items as $cart_item_key => $cart_item ) {
            $product_name = esc_html( $cart_item['data']->get_name() );
            $quantity = esc_html( $cart_item['quantity'] );
            $line_total = (float) $cart_item['line_total']; 
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