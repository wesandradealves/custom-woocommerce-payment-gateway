(function ($, window, document) {
    console.log("[BDM Checkout] Initializing...");

    const BDM = {
        config: {
            checkInterval: 15000,
            countdown: 5
        },

        state: {
            cotation: null,
            settings: null,
            products: null,
            intervalId: null,
        },

        getCotation: async function () {
            const { asset, endpoint_quotation } = this.state.settings;
            try {
                const endpointQuotation = `${endpoint_quotation}/${asset}`;
                if (!endpointQuotation) {
                    console.warn("[BDM Checkout] Endpoint Quotation is not defined.");
                    return this.config.fallbackCotation;
                }
        
                const response = await fetch(endpointQuotation);
                const data = await response.json();
                return data?.BRL;
            } catch (error) {
                console.warn("[BDM Checkout] Error fetching cotation:", error);
                return this.config.fallbackCotation;
            }
        },

        init: async function () {
            this.state.settings = bdm_digital_payment_gateway_checkout_data?.settings;
            this.state.products = bdm_digital_payment_gateway_checkout_data?.products;

            if (!this.state.settings || !this.state.products) {
                console.error("[BDM Checkout] Missing configuration data.");
                return;
            }

            this.state.cotation = await this.getCotation();
            this.clearLocalStorage();
            this.bindEvents();
        },

        clearLocalStorage: function () {
            sessionStorage.removeItem("order_id");
            sessionStorage.removeItem("billingcode");
        },

        bindEvents: function () {
            $("#bdm-checkout-button").on("click", async (e) => {
                e.preventDefault();
                await this.handleCheckout();
            });

            $("#bdm-copycode").on("click", (e) => {
                e.preventDefault();
                this.copyPaymentCode();
            });
        },

        handleCheckout: async function () {
            const totalPrice = this.calculateTotalPrice(this.state.products);

            const amount = parseFloat((totalPrice / this.state.cotation).toFixed(2)); 

            UI.updateCheckoutUIBefore(totalPrice, amount);

            try {
                const order = await this.createWooCommerceOrder(amount); 
                const billingData = await this.requestBillingCode(amount, order.data.order_id);
                if (billingData?.billingCode) {
                    this.updateUIAfterBillingCode(billingData);
                } else {
                    Toast.error("Failed to generate payment code.");
                }
            } catch (error) {
                console.error("[BDM Checkout] Error during checkout:", error);
                Toast.error("Error processing checkout.");
            }
        },

        calculateTotalPrice: function (products) {
            const formattedCart = this.formatCart(products);
            return Object.values(formattedCart).reduce((sum, item) => sum + item.price, 0);
        },

        formatCart: function (array) {
            const decodeHtml = (html) => {
                const textarea = document.createElement("textarea");
                textarea.innerHTML = html;
                return textarea.value;
            };

            const extractNumericPrice = (price) => {
                let cleanedPrice = decodeHtml(price).replace(/<[^>]*>/g, "").trim();
                cleanedPrice = cleanedPrice.replace(",", ".");
                const match = cleanedPrice.match(/[\d.]+/g);
                return match ? parseFloat(match.join("")) : 0;
            };

            return Object.entries(array).reduce((acc, [key, item]) => {
                acc[key] = {
                    ...item,
                    price: extractNumericPrice(item.price),
                };
                return acc;
            }, {});
        },

        requestBillingCode: async function (amount, orderId) {
            const { endpoint, api_key, partner_email, asset } = this.state.settings;

            const response = await fetch(`${endpoint}ecommerce-partner/billing-code`, {
                method: "POST",
                headers: { "Content-Type": "application/json", "x-api-key": api_key },
                body: JSON.stringify({
                    partnerEmail: partner_email,
                    amount: amount,
                    toAsset: asset,
                    attachment: `Pedido #${orderId}`,
                    fromAsset: asset,
                }),
            });

            return response.json();
        },

        updateUIAfterBillingCode: function (data) {
            UI.hideLoading();
            $("#step-1, #step-2").toggleClass("d-flex d-none");
            UI.setHTML("#billingcode", data.billingCode);
            $("#qrcode").html(`<img src="${data.qrCode}" alt="QR Code" />`);
            sessionStorage.setItem("billingcode", data.billingCode);
        },

        createWooCommerceOrder: function (amount) {
            const { partner_email } = this.state.settings;
            const nonce = bdm_digital_payment_gateway_checkout_data && bdm_digital_payment_gateway_checkout_data.nonce;
            if (!nonce) {
                console.error("[BDM Checkout] Nonce ausente. Não é possível criar o pedido.");
                Toast.error("Erro de segurança: nonce ausente. Recarregue a página.");
                return Promise.reject("Nonce ausente");
            }
            console.log("[BDM Checkout] Enviando nonce:", nonce);
            return new Promise((resolve, reject) => {
                $.post(bdm_digital_payment_gateway_Ajax.ajax_url, {
                    action: "bdm_digital_payment_gateway_create_order",
                    billing_code: sessionStorage.getItem("billingcode"),
                    amount: amount,
                    partner_email: partner_email,
                    products: JSON.stringify(this.state.products), 
                    nonce: nonce
                })
                    .done((response) => {
                        console.log("[BDM Checkout] Resposta AJAX:", response);
                        if (response.success) {
                            sessionStorage.setItem("order_id", response.data.order_id);
                            this.startCountdown(this.config.countdown);
                            this.startStatusInterval();
                            resolve(response);
                        } else {
                            const msg = response.message || "Erro desconhecido ao criar pedido.";
                            console.error("Error creating WooCommerce order:", msg);
                            Toast.error(msg);
                            reject(msg); 
                        }
                    })
                    .fail((err) => {
                        console.error("Error creating WooCommerce order:", err);
                        Toast.error("Erro de comunicação com o servidor.");
                        reject(err);
                    });
            });
        },

        startCountdown: function (minutes) {
            let totalSeconds = minutes * 60;
            const counter = document.getElementById("counter");

            const interval = setInterval(() => {
                const mins = Math.floor(totalSeconds / 60);
                const secs = totalSeconds % 60;
                counter.innerHTML = `${mins}:${secs < 10 ? "0" : ""}${secs}`;
                totalSeconds--;

                if (totalSeconds < 0) {
                    clearInterval(interval);
                    this.onCountdownFinished();
                }
            }, 1000);
        },

        onCountdownFinished: function () {
            Toast.warning("Payment time expired!");
            $("#step-2, #step-3").addClass("d-none");
            $("#step-1").removeClass("d-none");
            this.clearLocalStorage();
        },

        startStatusInterval: function () {
            if (this.state.intervalId) clearInterval(this.state.intervalId);
            this.state.intervalId = setInterval(this.checkPaymentStatus.bind(this), this.config.checkInterval);
        },

        checkPaymentStatus: async function () {
            const { endpoint, api_key, partner_email } = this.state.settings;
            const billingcode = sessionStorage.getItem("billingcode");

            try {
                const response = await fetch(
                    `${endpoint}ecommerce-partner/clients/billingcode-status/${partner_email}/${billingcode}`,
                    {
                        method: "GET",
                        headers: { "x-api-key": api_key },
                    }
                );

                const data = await response.json();

                if (data?.status === "COMPLETED") {
                    clearInterval(this.state.intervalId);
                    Toast.success("Payment confirmed!");
                    this.updateOrderStatus(sessionStorage.getItem("order_id"), data.status);

                    setTimeout(() => {
                        location.reload(); 
                    }, 5000);
                }
            } catch (error) {
                console.warn("[BDM Checkout] Error checking payment status:", error);
            }
        },

        updateOrderStatus: async function (orderId, status) {
            try {
                await fetch("/wp-json/bdm_digital_payment_gateway/v1/update-payment", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: status.toLowerCase(),
                        consumer_key: this.state.settings.consumer_key,
                        consumer_secret: this.state.settings.consumer_secret,
                    }),
                });
            } catch (error) {
                console.error("[BDM Checkout] Error updating order:", error);
            }
        },

        copyPaymentCode: function () {
            const code = $("#billingcode").text();
        
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code)
                    .then(() => Toast.success("Código copiado com sucesso!"))
                    .catch((err) => {
                        Toast.error("Erro ao copiar o código.");
                        console.error(err);
                    });
            } else {
                const tempInput = document.createElement("input");
                tempInput.value = code;
                document.body.appendChild(tempInput);
                tempInput.select();
                try {
                    document.execCommand("copy");
                    Toast.success("Código copiado com sucesso!");
                } catch (err) {
                    Toast.error("Erro ao copiar o código.");
                    console.error(err);
                }
                document.body.removeChild(tempInput);
            }
        },
    };

    const UI = {
        showLoading: () => $(".loading").removeClass("d-none").addClass("d-flex"),
        hideLoading: () => $(".loading").removeClass("d-flex").addClass("d-none"),
        setHTML: (selector, value) => $(selector).html(value),
        updateCheckoutUIBefore: (totalPrice, amount) => {
            UI.setHTML(".amount-bdm", amount);
            UI.setHTML(".amount", totalPrice);
            UI.setHTML(".cotation", BDM.state.cotation);
            UI.showLoading();
        },
    };

    const Toast = {
        success: (msg) => $.toast({ heading: "Success", text: msg, icon: "success", hideAfter: 4000 }),
        error: (msg) => $.toast({ heading: "Error", text: msg, icon: "error", hideAfter: 5000 }),
        warning: (msg) => $.toast({ heading: "Warning", text: msg, icon: "warning", hideAfter: 5000 }),
    };

    $(document).ready(() => BDM.init());
})(jQuery, window, document);
