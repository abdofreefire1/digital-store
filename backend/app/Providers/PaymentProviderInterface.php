<?php
namespace App\Providers;

interface PaymentProviderInterface {
    /**
     * Create an invoice/payment request.
     * @param array $paymentData Data containing order details, amount, etc.
     * @return array The gateway response containing invoice ID, address, QR data, etc.
     */
    public function createInvoice(array $paymentData): array;

    /**
     * Verify a payment based on the provider's transaction/invoice ID.
     * @param string $transactionId
     * @return array Information about the payment status
     */
    public function verifyPayment(string $transactionId): array;
}
