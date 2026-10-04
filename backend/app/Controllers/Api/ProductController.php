<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class ProductController extends Controller {
    public function index() {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name, pr.discount_type, pr.discount_value
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN promotions pr ON p.promotion_id = pr.id AND pr.active = 1 AND (pr.start_date IS NULL OR pr.start_date <= NOW()) AND (pr.end_date IS NULL OR pr.end_date >= NOW())
            WHERE p.active = 1
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();

        // Calculate final prices dynamically
        foreach ($products as &$product) {
            $product['final_price_dzd'] = $product['price_dzd'];
            if ($product['discount_type']) {
                if ($product['discount_type'] === 'percentage') {
                    $product['final_price_dzd'] = $product['price_dzd'] - ($product['price_dzd'] * ($product['discount_value'] / 100));
                } else if ($product['discount_type'] === 'fixed') {
                    $product['final_price_dzd'] = max(0, $product['price_dzd'] - $product['discount_value']);
                }
            }
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $products
        ]);
    }

    public function show($id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name, pr.discount_type, pr.discount_value
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN promotions pr ON p.promotion_id = pr.id AND pr.active = 1 AND (pr.start_date IS NULL OR pr.start_date <= NOW()) AND (pr.end_date IS NULL OR pr.end_date >= NOW())
            WHERE p.id = ? AND p.active = 1
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            $this->jsonResponse(['success' => false, 'error' => 'Product not found'], 404);
        }

        // Calculate final price
        $product['final_price_dzd'] = $product['price_dzd'];
        if ($product['discount_type']) {
            if ($product['discount_type'] === 'percentage') {
                $product['final_price_dzd'] = $product['price_dzd'] - ($product['price_dzd'] * ($product['discount_value'] / 100));
            } else if ($product['discount_type'] === 'fixed') {
                $product['final_price_dzd'] = max(0, $product['price_dzd'] - $product['discount_value']);
            }
        }

        // Fetch required fields
        $fieldsStmt = $pdo->prepare("SELECT field_name, field_type, is_required FROM product_fields WHERE product_id = ?");
        $fieldsStmt->execute([$id]);
        $product['required_fields'] = $fieldsStmt->fetchAll();

        $this->jsonResponse([
            'success' => true,
            'data' => $product
        ]);
    }
}
