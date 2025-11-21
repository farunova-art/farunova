/**
 * FARUNOVA Payment Processing
 * Handles M-Pesa payment flow with STK Push
 * 
 * @version 1.0
 */

class PaymentProcessor {
    constructor() {
        this.init();
    }

    init() {
        this.attachEventListeners();
    }

    attachEventListeners() {
        // Payment method selection
        document.addEventListener('change', (e) => {
            if (e.target.closest('[name="paymentMethod"]')) {
                this.handlePaymentMethodChange(e);
            }
        });

        // Payment form submission
        const paymentForm = document.getElementById('paymentForm');
        if (paymentForm) {
            paymentForm.addEventListener('submit', (e) => this.handlePaymentSubmit(e));
        }

        // Query payment status button
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-query-payment]')) {
                this.handleQueryPayment(e);
            }
        });
    }

    /**
     * Handle payment method change
     */
    handlePaymentMethodChange(e) {
        const method = e.target.value;
        const mpesaSection = document.getElementById('mpesaSection');
        const cardSection = document.getElementById('cardSection');

        if (method === 'mpesa') {
            if (mpesaSection) mpesaSection.style.display = 'block';
            if (cardSection) cardSection.style.display = 'none';
        } else if (method === 'card') {
            if (mpesaSection) mpesaSection.style.display = 'none';
            if (cardSection) cardSection.style.display = 'block';
        }
    }

    /**
     * Handle payment form submission
     */
    async handlePaymentSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const method = form.querySelector('[name="paymentMethod"]').value || 'mpesa';

        if (method === 'mpesa') {
            await this.processM - PesaPayment(form);
        } else if (method === 'card') {
            await this.processCardPayment(form);
        }
    }

    /**
     * Process M-Pesa payment
     */
    async processMPesaPayment(form) {
        const orderId = form.querySelector('[name="orderId"]').value;
        const phone = form.querySelector('[name="phone"]').value;
        const amount = form.querySelector('[name="amount"]').value;

        if (!orderId || !phone || !amount) {
            showNotification('Please fill all required fields', 'warning');
            return;
        }

        // Show loading state
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
            // Initiate payment
            const response = await apiRequest('/api/payments.php?action=initiate', 'POST', {
                orderId: orderId,
                phone: phone,
                amount: amount
            });

            if (!response.success) {
                showNotification(response.message || 'Payment initiation failed', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }

            const checkoutRequestID = response.checkoutRequestID;

            showNotification('STK push sent to ' + phone + '. Check your phone to complete payment.', 'info');

            // Store payment info in session/localStorage
            localStorage.setItem('currentPayment', JSON.stringify({
                checkoutRequestID: checkoutRequestID,
                orderId: orderId,
                amount: amount,
                initiatedAt: Date.now()
            }));

            // Poll for payment status
            this.pollPaymentStatus(checkoutRequestID, orderId);

            // Reset button after 2 seconds
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }, 2000);

        } catch (error) {
            showNotification('Error initiating payment: ' + error.message, 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    /**
     * Poll payment status
     */
    pollPaymentStatus(checkoutRequestID, orderId, attempts = 0) {
        const maxAttempts = 30; // Poll for 5 minutes (10 seconds x 30)
        const pollInterval = 10000; // 10 seconds

        const pollTimeout = setTimeout(async() => {
            if (attempts >= maxAttempts) {
                showNotification('Payment verification timeout. You can check payment status in your account.', 'warning');
                localStorage.removeItem('currentPayment');
                return;
            }

            try {
                const response = await apiRequest(`/api/payments.php?action=query`, 'POST', {
                    checkoutRequestID: checkoutRequestID
                }, false);

                if (response.success) {
                    showNotification('✅ ' + response.message, 'success');
                    localStorage.removeItem('currentPayment');

                    // Redirect to order confirmation
                    setTimeout(() => {
                        window.location.href = '/order_confirmation.php?orderId=' + orderId;
                    }, 2000);

                    return;
                } else if (response.resultCode !== 1032 && response.resultCode !== 1) {
                    // Payment failed (not timeout or user cancel)
                    showNotification('❌ ' + response.message, 'error');
                    localStorage.removeItem('currentPayment');
                    return;
                }

                // Continue polling for pending, timeout, or user cancel
                this.pollPaymentStatus(checkoutRequestID, orderId, attempts + 1);

            } catch (error) {
                // Continue polling on error
                this.pollPaymentStatus(checkoutRequestID, orderId, attempts + 1);
            }
        }, pollInterval);
    }

    /**
     * Handle manual payment query
     */
    async handleQueryPayment(e) {
        e.preventDefault();

        const checkoutRequestID = document.getElementById('checkoutRequestID').value;
        const orderId = document.getElementById('orderId').value;

        if (!checkoutRequestID) {
            showNotification('Checkout Request ID is required', 'warning');
            return;
        }

        const btn = e.target;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Checking...';

        try {
            const response = await apiRequest('/api/payments.php?action=query', 'POST', {
                checkoutRequestID: checkoutRequestID
            });

            if (response.success) {
                showNotification('✅ ' + response.message, 'success');

                if (orderId) {
                    setTimeout(() => {
                        window.location.href = '/order_confirmation.php?orderId=' + orderId;
                    }, 2000);
                }
            } else {
                showNotification(response.message || 'Payment query failed', 'error');
            }

        } catch (error) {
            showNotification('Error querying payment: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    /**
     * Process card payment (placeholder)
     */
    async processCardPayment(form) {
        showNotification('Card payment is under development. Please use M-Pesa.', 'info');
    }

    /**
     * Format phone number
     */
    formatPhone(phone) {
        phone = phone.replace(/[^0-9]/g, '');

        if (phone.startsWith('0')) {
            phone = '254' + phone.substring(1);
        } else if (!phone.startsWith('254')) {
            phone = '254' + phone;
        }

        return phone;
    }

    /**
     * Validate phone number
     */
    validatePhone(phone) {
        phone = phone.replace(/[^0-9]/g, '');

        if (phone.startsWith('0')) {
            phone = '254' + phone.substring(1);
        }

        if (phone.length !== 12 || !phone.startsWith('254')) {
            return false;
        }

        return true;
    }
}

// Initialize payment processor on page load
document.addEventListener('DOMContentLoaded', () => {
    new PaymentProcessor();
});