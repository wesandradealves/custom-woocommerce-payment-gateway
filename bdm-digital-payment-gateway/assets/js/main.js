(function ($) {
    $(window).on("load", function () {
        localStorage.removeItem("order_id");
        localStorage.removeItem("billingcode");

        const settings = bdm_checkout_data?.settings;
        const products = bdm_checkout_data?.products;
        const API_KEY = settings?.api_key;
        const ENDPOINT = settings?.endpoint;
        const COTATION = 10;
        const CHECK_INTERVAL = 15000;
        let paymentCheckInterval;

        if (!settings || !products) {
            console.error("bdm_checkout_data is not available!");
            return;
        }

        const countdown = (minutes) => {
            let seconds = 59;
            let mins = minutes;
            const tick = () => {
                const counter = document.getElementById("counter");
                if (!counter) return;
                const currentMinutes = mins - 1;
                counter.innerHTML = `${currentMinutes}:${seconds < 10 ? "0" : ""}${seconds}`;
                seconds--;
                if (seconds > 0) {
                    setTimeout(tick, 1000);
                } else if (mins > minutes) {
                    countdown(mins - 1);
                } else {
                    $("#expiration").toggleClass('d-none d-block');
                    $.toast({ heading: 'Tempo expirou',  hideAfter: false, text: 'O tempo para pagar expirou, atualize a página e tente novamente.', icon: 'error' }),
                    $('#bdm-copycode').prop('disabled', true),
                    $("#qrcode svg").toggleClass("d-none d-block"),
                    clearInterval(paymentCheckInterval);
                }
            };
            tick();
        };

        const formatCart = (array) => {
            const decodeHtml = (html) => {
                const textarea = document.createElement("textarea");
                textarea.innerHTML = html;
                return textarea.value;
            };

            const extractNumericPrice = (price) => {
                let cleanedPrice = decodeHtml(price).replace(/<[^>]*>/g, "").trim();

                cleanedPrice = cleanedPrice.replace(",", ".");

                let match = cleanedPrice.match(/[\d.]+/g);
                return match ? match.join("") : "0"; 
            };

            let newCart = Object.entries(array).reduce((acc, [key, item]) => {
                acc[key] = {
                    ...item,
                    price: parseFloat(extractNumericPrice(item.price)) 
                };
                return acc;
            }, {});

            return newCart;
        };        

        const updateOrderStatus = async (orderId, status) => {
            try {
                const response = await fetch("/wp-json/store/v1/update-payment", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ order_id: orderId, status: status.toLowerCase(), consumer_key: settings.consumer_key, consumer_secret: settings.consumer_secret })
                });
                console.log(`✅ Order ${orderId} updated successfully.`, await response.json());
            } catch (error) {
                console.error("Error updating order status:", error);
            }
        };

        const checkPaymentStatus = async () => {
            try {
                // `https://partner.dourado.cash/ecommerce-partner/clients/billingcode-status/celsoj@gmail.com/BDM_DIGITAL_339e947d-933a-49b2-8b61-6e429c355b99_17a9`
                // `${settings.endpoint}ecommerce-partner/clients/billingcode-status/${settings.partner_email}/${billingcode}`
                                
                const response = await fetch(`${settings.endpoint}ecommerce-partner/clients/billingcode-status/${settings.partner_email}/${localStorage.getItem("billingcode")}`, {
                    method: "GET",
                    headers: { "x-api-key": API_KEY }
                });
                const data = await response.json();

                if (data.status === "COMPLETED") {
                    console.log("✅ Payment completed!");
                    updateOrderStatus(localStorage.getItem("order_id"), data.status);
                    $("#step-2, #step-3").toggleClass("d-none d-flex");
                    clearInterval(paymentCheckInterval);
                }
            } catch (error) {
            }
        };

        const createWooCommerceOrder = (amount) => {
            $.post("/wp-admin/admin-ajax.php", {
                action: "create_bdm_order",
                billing_code: localStorage.getItem("billingcode"),
                amount: amount,
                partner_email: settings.partner_email,
                products
            }).done((response) => {
                if (response.success) {
                    console.log("✅ WooCommerce order created successfully!");
                    localStorage.setItem("order_id", response.data.order_id);
                    countdown(1);
                    setTimeout(() => {
                        checkPaymentStatus();
                        paymentCheckInterval = setInterval(checkPaymentStatus, CHECK_INTERVAL);
                    }, CHECK_INTERVAL);
                } else {
                    console.error("Error creating WooCommerce order:", response.message);
                }
            }).fail((err) => console.error("Error creating WooCommerce order:", err));
        };

        $("#bdm-checkout-button").on("click", async function (e) {
            e.preventDefault();

            const totalPrice = Object.values(formatCart(products)).reduce((sum, item) => sum + item.price, 0);
            const amount = totalPrice/COTATION;

            $(".amount-bdm").html(amount);
            $(".amount").html(totalPrice);
            $(".cotation").html(COTATION);
            $(".loading").toggleClass("d-none d-flex");

            try {
                const response = await fetch(`${ENDPOINT}ecommerce-partner/billing-code`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "x-api-key": API_KEY },
                    body: JSON.stringify({
                        partnerEmail: settings.partner_email,
                        amount: amount,
                        toAsset: settings.asset,
                        attachment: "Teste",
                        fromAsset: settings.asset
                    })
                });
                const data = await response.json();
                if (data?.billingCode) {
                    $(".loading").toggleClass("d-none d-flex");
                    $("#step-1, #step-2").toggleClass("d-flex d-none");
                    $("#billingcode").html(data.billingCode);
                    $("#qrcode").append(`<img src="${data.qrCode}" />`);
                    localStorage.setItem("billingcode", data.billingCode);
                    createWooCommerceOrder(amount);
                } else {
                    console.error("Payment request failed:", data);
                }
            } catch (error) {
                console.error("Error during payment request:", error);
            }
        });

        $("#bdm-copycode").on("click", async function (e) {
            e.preventDefault();
            try {
                await navigator.clipboard.writeText($("#billingcode").text());
                $.toast({ heading: 'Copiado!', text: 'Código copiado com sucesso.', icon: 'success' });
            } catch (err) {
                $.toast({ heading: 'Erro', text: 'Falha ao copiar código.', icon: 'error' });
            }
        });
    });
})(jQuery);