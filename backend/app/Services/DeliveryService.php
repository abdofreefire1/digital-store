<?php
namespace App\Services;

use App\Core\Database;

class DeliveryService {

    public function processDelivery(int $orderId) {
        $pdo = Database::getInstance();
        
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND order_status IN ('processing', 'pending')");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new \Exception("Order not found or invalid status for delivery.");
        }

        // Get Order Items
        $itemsStmt = $pdo->prepare("
            SELECT oi.*, p.delivery_type, p.name 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as $item) {
            try {
                $pdo->beginTransaction();

                if ($item['delivery_type'] === 'code') {
                    $this->deliverCode($item, $pdo);
                } elseif ($item['delivery_type'] === 'email') {
                    $this->deliverEmail($item, $pdo);
                } elseif ($item['delivery_type'] === 'manual') {
                    $this->flagForManualDelivery($item, $pdo);
                }

                $pdo->commit();
            } catch (\Exception $e) {
                $pdo->rollBack();
                // Log delivery failure for this item
                error_log("Delivery failed for OrderItem ID: {$item['id']} - " . $e->getMessage());
            }
        }

        // Check if all items delivered, then update order status
        $this->updateOrderStatusIfCompleted($orderId, $pdo);
    }

    private function deliverCode($item, $pdo) {
        // SELECT FOR UPDATE to lock the row and prevent double assignment
        $stmt = $pdo->prepare("
            SELECT ii.id, ii.item_data 
            FROM inventory_items ii 
            JOIN inventory i ON ii.inventory_id = i.id 
            WHERE i.product_id = ? AND ii.is_used = 0 
            LIMIT 1 FOR UPDATE
        ");
        $stmt->execute([$item['product_id']]);
        $inventoryItem = $stmt->fetch();

        if (!$inventoryItem) {
            throw new \Exception("Out of stock for Code delivery on product ID {$item['product_id']}");
        }

        // Mark code as used
        $updateStmt = $pdo->prepare("UPDATE inventory_items SET is_used = 1, used_at = NOW() WHERE id = ?");
        $updateStmt->execute([$inventoryItem['id']]);

        // Decrease stock count
        $stockStmt = $pdo->prepare("UPDATE inventory SET stock_count = stock_count - 1 WHERE product_id = ?");
        $stockStmt->execute([$item['product_id']]);

        // Save delivery record
        $delStmt = $pdo->prepare("INSERT INTO deliveries (order_item_id, delivery_type, delivery_data) VALUES (?, 'code', ?)");
        $delStmt->execute([$item['id'], $inventoryItem['item_data']]);
    }

    private function deliverEmail($item, $pdo) {
        $msg = "Thank you for purchasing {$item['name']}. Your account details will be processed shortly.";
        
        $delStmt = $pdo->prepare("INSERT INTO deliveries (order_item_id, delivery_type, delivery_data) VALUES (?, 'email', ?)");
        $delStmt->execute([$item['id'], $msg]);

        // Trigger Resend Email API here
        // EmailService::send(...)
    }

    private function flagForManualDelivery($item, $pdo) {
        $delStmt = $pdo->prepare("INSERT INTO deliveries (order_item_id, delivery_type, delivery_data) VALUES (?, 'manual', ?)");
        $delStmt->execute([$item['id'], 'Pending Manual Activation']);
    }

    private function updateOrderStatusIfCompleted($orderId, $pdo) {
        // Logic to check if count of items == count of delivered items
        // If yes, UPDATE orders SET order_status = 'delivered' WHERE id = ?
    }
}
