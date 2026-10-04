<?php
namespace App\Providers;

use Exception;

class Q8QPayProvider implements PaymentProviderInterface {
    private $apiKey;
    private $webhookSecret;
    private $baseUrl;

    public function __construct() {
        $this->apiKey = Q8QPAY_API_KEY;
        $this->webhookSecret = Q8QPAY_WEBHOOK_SECRET;
        $this->baseUrl = Q8QPAY_BASE_URL;
        
        if (empty($this->apiKey) || empty($this->baseUrl)) {
            throw new Exception("Q8QPay configuration is missing in environment variables.");
        }
    }

    public function createInvoice(array $paymentData): array {
        $endpoint = $this->baseUrl . '/api/v1/invoices';
        
        $payload = [
            'reference' => $paymentData['order_reference'],
            'assetCode' => $paymentData['asset_code'] ?? 'USDT_TRC20',
            'amountUsdt' => (float) $paymentData['amount_usdt'],
            'expirySeconds' => 1800, // 30 minutes
            'callbackUrl' => APP_URL . '/api/v1/webhooks/q8qpay',
            'returnUrl' => FRONTEND_URL . '/payment-success?order=' . $paymentData['order_reference'],
            'useWhiteLabel' => true
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new Exception("Failed to create Q8QPay invoice. HTTP Code: {$httpCode}. Response: {$response}");
        }

        return json_decode($response, true);
    }

    public function verifyPayment(string $invoiceId): array {
        $endpoint = $this->baseUrl . '/api/v1/invoices/' . $invoiceId;

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Failed to verify Q8QPay invoice. HTTP Code: {$httpCode}");
        }

        return json_decode($response, true);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }
}
