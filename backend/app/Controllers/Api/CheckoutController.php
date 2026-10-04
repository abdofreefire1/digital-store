<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Services\ExchangeRateService;
use App\Providers\Q8QPayProvider;

class CheckoutController extends Controller {
    
    public function createOrder() {
        $input = $this->getJsonInput();
        
        $customerName = $input['customer_name'] ?? '';
        $customerEmail = $input['customer_email'] ?? '';
        $customerPhone = $input['customer_phone'] ?? null;
        $productId = $input['product_id'] ?? null;
        $paymentMethodId = $input['payment_method_id'] ?? null;
        $customerData = $input['customer_data'] ?? []; // JSON custom fields

        if (!$customerName || !$customerEmail || !$productId || !$paymentMethodId) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }

        $pdo = Database::getInstance();
        
        // 1. Verify Product
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            $this->jsonResponse(['error' => 'Product not found or inactive'], 404);
        }

        // Apply Promotion if any
        $finalPrice = $product['price_dzd'];
        if ($product['promotion_id']) {
            $promStmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ? AND active = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW())");
            $promStmt->execute([$product['promotion_id']]);
            $promotion = $promStmt->fetch();
            
            if ($promotion) {
                if ($promotion['discount_type'] === 'percentage') {
                    $finalPrice -= ($finalPrice * ($promotion['discount_value'] / 100));
                } else {
                    $finalPrice -= $promotion['discount_value'];
                }
                $finalPrice = max(0, $finalPrice);
            }
        }

        // 2. Check Inventory if required
        $invStmt = $pdo->prepare("SELECT * FROM inventory WHERE product_id = ?");
        $invStmt->execute([$productId]);
        $inventory = $invStmt->fetch();

        if ($inventory && !$inventory['is_unlimited'] && $inventory['stock_count'] <= 0) {
            $this->jsonResponse(['error' => 'Product is out of stock'], 400);
        }

        // 3. Verify Payment Method
        $pmStmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = ? AND active = 1");
        $pmStmt->execute([$paymentMethodId]);
        $paymentMethod = $pmStmt->fetch();

        if (!$paymentMethod) {
            $this->jsonResponse(['error' => 'Invalid or inactive payment method'], 400);
        }

        try {
            $pdo->beginTransaction();

            // Create Order
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $secureToken = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("
                INSERT INTO orders (order_number, customer_email, customer_name, customer_phone, total_dzd, payment_method_id, secure_token)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderNumber, $customerEmail, $customerName, $customerPhone, 
                $finalPrice, $paymentMethodId, $secureToken
            ]);
            $orderId = $pdo->lastInsertId();

            // Create Order Item
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price, customer_input_data)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderId, $productId, 1, $finalPrice, $finalPrice, json_encode($customerData)
            ]);

            // Create Payment Request
            if ($paymentMethod['provider_type'] === 'usdt') {
                $exchangeService = new ExchangeRateService();
                $rate = $exchangeService->getDzdToUsdtRate();
                $amountUsdt = $exchangeService->convertDzdToUsdt((float)$finalPrice);

                $q8qpay = new Q8QPayProvider();
                $invoice = $q8qpay->createInvoice([
                    'order_reference' => $orderNumber,
                    'amount_usdt' => $amountUsdt,
                    'asset_code' => 'USDT_TRC20'
                ]);

                $stmt = $pdo->prepare("
                    INSERT INTO payments (order_id, payment_method_id, price_dzd, exchange_rate, rate_source, amount_usdt, asset_code, network, q8qpay_invoice_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderId, $paymentMethodId, $finalPrice, $rate, USDT_RATE_SOURCE, 
                    $amountUsdt, 'USDT_TRC20', $invoice['network'] ?? 'TRC20', $invoice['invoiceId'] ?? null, 'pending'
                ]);

                $pdo->commit();
                
                $this->jsonResponse([
                    'success' => true,
                    'order_number' => $orderNumber,
                    'token' => $secureToken,
                    'payment_type' => 'usdt',
                    'invoice' => $invoice // Return QR code, address, etc. to frontend
                ]);

            } else {
                // Manual Payment (BaridiMob, CCP, etc.)
                $stmt = $pdo->prepare("
                    INSERT INTO payments (order_id, payment_method_id, price_dzd, status)
                    VALUES (?, ?, ?, 'pending')
                ");
                $stmt->execute([$orderId, $paymentMethodId, $finalPrice]);

                $pdo->commit();
                
                $this->jsonResponse([
                    'success' => true,
                    'order_number' => $orderNumber,
                    'token' => $secureToken,
                    'payment_type' => 'manual',
                    'instructions' => json_decode($paymentMethod['details'], true)
                ]);
            }

        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->jsonResponse(['error' => 'Failed to create order', 'details' => $e->getMessage()], 500);
        }
    }
}
