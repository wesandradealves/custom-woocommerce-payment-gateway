(function ($) {
    $(window).on("load", function () {
        if (typeof bdm_checkout_data === 'undefined') {
            console.error('bdm_checkout_data is not available!');
            return;
        }

        const { settings, products } = bdm_checkout_data;
        const threeshould = 30 * 1000; // 15 seconds
        const cotation = 1.00;

        console.log(settings)

        // Function to update WooCommerce order status via REST API
        const updateOrderStatus = async (order_id, status) => {
            try {
                const response = await fetch('/wp-json/store/v1/update-payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: order_id,
                        status: 'completed',
                        consumer_key: settings.consumer_key, // Use the correct key
                        consumer_secret: settings.consumer_secret // Use the correct key
                    })
                });            
                
                const data = await response.json();
                console.log(`Order ${order_id} updated successfully`, data);                
            } catch (error) {
                console.error('Error updating order status:', error);
            }
        };

        // Function to create a WooCommerce order via AJAX
        const createWooCommerceOrder = (billingCode, amount) => {
            $.ajax({
                url: '/wp-admin/admin-ajax.php', // WooCommerce's AJAX handler
                method: 'POST',
                data: {
                    action: 'create_bdm_order', // Custom action to trigger the PHP function
                    billing_code: billingCode,
                    amount: amount,
                    partner_email: bdm_checkout_data.settings.partner_email,
                    products: bdm_checkout_data.products, // Send the product data (optional)
                },
                success: function(response) {
                    if (response.success) {
                        console.log(response);
                        paymentCheckInterval = setInterval(checkPaymentStatus(billingCode, response.data.order_id), threeshould);
                        console.log('WooCommerce order created successfully!');
                    } else {
                        console.error('Error creating WooCommerce order:', response.message);
                    }
                },
                error: function(err) {
                    console.error('Error creating WooCommerce order:', err);
                }
            });
        };

        /**
         * Sum the prices of all items in the cart.
         * @param {Object} products - The products in the cart.
         * @returns {number} - Total sum of all prices.
         */
        const sumPrices = (products) => {
            return Object.values(products).reduce((total, product) => total + product.price, 0);
        };

        /**
         * Format cart data to ensure prices are numeric and clean.
         * @param {Object} products - The products to format.
         * @returns {Object} - The formatted products.
         */
        const formatCart = (products) => {
            const parser = new DOMParser();

            Object.keys(products).forEach((key) => {
                const strippedPrice = products[key].price.replace(/<\/?[^>]+(>|$)/g, "");
                const decodedPrice = parser.parseFromString(strippedPrice, "text/html").body.textContent;
                const numericPrice = parseFloat(decodedPrice.replace(/[^\d,.-]/g, '').replace(',', '.'));
                products[key].price = numericPrice;
            });

            return products;
        };

        /**
         * Check the payment status by fetching from the API.
         * @param {string} billingcode - The billing code for the payment.
         */
        const checkPaymentStatus = async (billingcode, order_id) => {
            try {
                const response = await fetch(`${settings.endpoint}ecommerce-partner/clients/billingcode-status/${settings.partner_email}/${billingcode}`, {
                    method: 'GET',
                    headers: {
                        'x-api-key': settings.api_key,
                    }
                });

                const data = await response.json();

                console.log(order_id, data, data.status);
                updateOrderStatus(order_id, data.status);

                if (data.status === 'COMPLETED') {
                    clearInterval(paymentCheckInterval);
                } else {
                    setTimeout(() => checkPaymentStatus(billingcode, ''), threeshould);
                }

                // if (data.status === 'COMPLETED') {
                //     $("#step-2, #step-3").toggleClass("d-none d-flex");
                //     console.log('Payment completed successfully!');

                //     if(order_id) {
                //         updateOrderStatus(order_id);
                //     }

                //     clearInterval(paymentCheckInterval); 
                // } else {
                //     console.error('Payment not completed yet. Retrying...');
                //     setTimeout(() => checkPaymentStatus(billingcode, ''), threeshould);
                // }
            } catch (error) {
                console.error('Error checking payment status:', error);
                setTimeout(() => checkPaymentStatus(billingcode, ''), threeshould);
            }
        };

        /**
         * Handle the checkout button click.
         */
        $("#bdm-checkout-button").on("click", async function (e) {
            e.preventDefault();

            const amount = sumPrices(formatCart(products)) * cotation;

            $(".loading").toggleClass("d-none d-flex");

            try {
                const response = await fetch(`${settings.endpoint}ecommerce-partner/billing-code`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'x-api-key': settings.api_key,
                    },
                    body: JSON.stringify({
                        partnerEmail: settings.partner_email,
                        amount: amount,
                        toAsset: settings.asset,
                        attachment: 'Teste',
                        fromAsset: settings.asset,
                    })
                });

                const data = await response.json();

                if (data && data.billingCode) {
                    $(".loading").toggleClass("d-none d-flex");

                    // Show the next step in the process
                    $("#step-1, #step-2").toggleClass("d-flex d-none");
                    $("#billingcode").html(data.billingCode);
                    $("#qrcode").html(`<img src="${data.qrCode}" />`);
                    // paymentCheckInterval = setInterval(checkPaymentStatus, threeshould);

                    // Create the WooCommerce order via AJAX after successful billing code creation
                    createWooCommerceOrder(data.billingCode, amount);
                } else {
                    console.error('Payment request failed:', data);
                }
            } catch (error) {
                console.error('Error during payment request:', error);
            }
        });
    });
})(jQuery);