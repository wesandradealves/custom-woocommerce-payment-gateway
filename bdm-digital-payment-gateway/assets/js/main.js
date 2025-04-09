(function ($, window, document) {
    const BDM = {
        config: {
            checkInterval: 15000,
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
            this.state.settings = bdm_checkout_data?.settings;
            this.state.products = bdm_checkout_data?.products;

            if (!this.state.settings || !this.state.products) {
                console.error("[BDM Checkout] Missing configuration data.");
                return;
            }

            this.state.cotation = await this.getCotation();
            this.clearLocalStorage();
            this.bindEvents();
        },

        clearLocalStorage: function () {
            localStorage.removeItem("order_id");
            localStorage.removeItem("billingcode");
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
            const amount = parseFloat((20 / 14.27).toFixed(6)); 

            UI.updateCheckoutUIBefore(totalPrice, amount);

            try {
                const billingData = await this.requestBillingCode(amount);
                if (billingData?.billingCode) {
                    this.updateUIAfterBillingCode(billingData);
                    this.createWooCommerceOrder(amount);
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

        requestBillingCode: async function (amount) {
            const { endpoint, api_key, partner_email, asset } = this.state.settings;

            const response = await fetch(`${endpoint}ecommerce-partner/billing-code`, {
                method: "POST",
                headers: { "Content-Type": "application/json", "x-api-key": api_key },
                body: JSON.stringify({
                    partnerEmail: partner_email,
                    amount: amount,
                    toAsset: asset,
                    attachment: "Teste",
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
            localStorage.setItem("billingcode", data.billingCode);
        },

        createWooCommerceOrder: function (amount) {
            const { partner_email } = this.state.settings;

            $.post("/wp-admin/admin-ajax.php", {
                action: "create_bdm_order",
                billing_code: localStorage.getItem("billingcode"),
                amount: amount,
                partner_email: partner_email,
                products: this.state.products,
            })
                .done((response) => {
                    if (response.success) {
                        console.log("✅ WooCommerce order created successfully!");
                        localStorage.setItem("order_id", response.data.order_id);
                        this.startCountdown(1);
                        this.startStatusInterval();
                    } else {
                        console.error("Error creating WooCommerce order:", response.message);
                    }
                })
                .fail((err) => {
                    console.error("Error creating WooCommerce order:", err);
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
            const billingcode = localStorage.getItem("billingcode");

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
                    this.updateOrderStatus(localStorage.getItem("order_id"), data.status);
                }
            } catch (error) {
                console.warn("[BDM Checkout] Error checking payment status:", error);
            }
        },

        updateOrderStatus: async function (orderId, status) {
            try {
                await fetch("/wp-json/store/v1/update-payment", {
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

            navigator.clipboard.writeText(code)
                .then(() => Toast.success("Code copied to clipboard."))
                .catch((err) => {
                    Toast.error("Failed to copy code.");
                    console.error(err);
                });
        },
    };

    // Helpers
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