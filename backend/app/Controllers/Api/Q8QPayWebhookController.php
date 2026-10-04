<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Providers\Q8QPayProvider;

class Q8QPayWebhookController extends Controller {
    
    public function handle() {
        $provider = new Q8QPayProvider();
        $payloadRaw = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

        // 1. Verify Signature
        if (!$provider->verifyWebhookSignature($payloadRaw, $signature)) {
            $this->jsonResponse(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($payloadRaw, true);
        if (!$payload) {
            $this->jsonResponse(['error' => 'Invalid JSON payload'], 400);
        }

        $invoiceId = $payload['invoiceId'] ?? null;
        $reference = $payload['reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$invoiceId || !$reference) {
            $this->jsonResponse(['error' => 'Missing critical data'], 400);
        }

        $pdo = Database::getInstance();

        // 2. Idempotency Check
        $stmt = $pdo->prepare("SELECT id FROM webhook_logs WHERE provider = 'q8qpay' AND reference = ?");
        $stmt->execute([$invoiceId . '_' . $status]);
        if ($stmt->fetch()) {
            $this->jsonResponse(['message' => 'Already processed'], 200); // 200 to stop webhook retries
        }

        // Log the webhook
        $stmt = $pdo->prepare("INSERT INTO webhook_logs (provider, reference, payload, headers, processed) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['q8qpay', $invoiceId . '_' . $status, $payloadRaw, json_encode(getallheaders()), 1]);

        // 3. Find the Payment in DB
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE q8qpay_invoice_id = ? AND status = 'pending'");
        $stmt->execute([$invoiceId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            $this->jsonResponse(['error' => 'Payment not found or not pending'], 404);
        }

        // 4. Verify status via API to prevent spoofing
        try {
            $verifyData = $provider->verifyPayment($invoiceId);
            if ($verifyData['status'] !== $status) {
                 $this->jsonResponse(['error' => 'Status mismatch'], 400);
            }
        } catch (\Exception $e) {
             $this->jsonResponse(['error' => 'Verification failed'], 500);
        }

        // 5. Database Transaction for Order Update
        try {
            $pdo->beginTransaction();

            if ($status === 'confirmed' || $status === 'paid') {
                // Mark payment as completed
                $stmt = $pdo->prepare("UPDATE payments SET status = 'completed', transaction_id = ? WHERE id = ?");
                $stmt->execute([$payload['transactionHash'] ?? null, $payment['id']]);

                // Mark order as paid
                $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'processing' WHERE id = ?");
                $stmt->execute([$payment['order_id']]);

                // TODO: Trigger Delivery Engine (Code, Email, etc.)
            } elseif ($status === 'expired' || $status === 'cancelled') {
                $stmt = $pdo->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
                $stmt->execute([$payment['id']]);

                $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed', order_status = 'cancelled' WHERE id = ?");
                $stmt->execute([$payment['order_id']]);
            }

            $pdo->commit();
            $this->jsonResponse(['message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->jsonResponse(['error' => 'Database update failed'], 500);
        }
    }
}
