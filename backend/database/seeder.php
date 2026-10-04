<?php
// backend/database/seeder.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getInstance();

try {
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM payment_methods");
    if ($stmt->fetchColumn() > 0) {
        echo "Database already seeded.\n";
        exit;
    }

    // Insert Payment Methods
    $pdo->exec("
        INSERT INTO payment_methods (name, provider_type, details, active) VALUES
        ('BaridiMob', 'manual', '{\"RIP\": \"00799999999999999999\", \"Phone\": \"0770000000\", \"Name\": \"Store Admin\"}', 1),
        ('USDT TRC20', 'usdt', '{\"Network\": \"TRC20\"}', 1)
    ");

    // Insert Categories
    $pdo->exec("
        INSERT INTO categories (name, slug, description, active) VALUES
        ('Subscriptions', 'subscriptions', 'Streaming and Software subscriptions', 1),
        ('Gift Cards', 'gift-cards', 'Digital gift cards and codes', 1)
    ");

    // Insert Products
    $pdo->exec("
        INSERT INTO products (category_id, name, slug, description, image, price_dzd, old_price_dzd, product_type, delivery_type, active) VALUES
        (1, 'Netflix 1 Month - 4K Premium', 'netflix-1-month', 'Account details will be sent to your email.\\n- 4K Ultra HD\\n- 4 Screens', 'https://placehold.co/400x300?text=Netflix', 1500.00, 2000.00, 'account', 'email', 1),
        (2, 'Binance Gift Card 10$', 'binance-10', '10 USDT Binance Gift Card. Instant delivery.', 'https://placehold.co/400x300?text=Binance', 2600.00, NULL, 'code', 'code', 1)
    ");

    // Insert Product Fields for Netflix (Example)
    $pdo->exec("
        INSERT INTO product_fields (product_id, field_name, field_type, is_required) VALUES
        (1, 'Email Address for Profile Name', 'email', 1)
    ");

    // Insert Inventory for Codes
    $pdo->exec("
        INSERT INTO inventory (product_id, is_unlimited, stock_count) VALUES
        (1, 1, 999), -- Netflix is manually activated, so practically unlimited
        (2, 0, 5)    -- Binance cards
    ");

    $pdo->exec("
        INSERT INTO inventory_items (inventory_id, item_data, is_used) VALUES
        (2, 'BNC-XXXX-1111', 0),
        (2, 'BNC-XXXX-2222', 0),
        (2, 'BNC-XXXX-3333', 0),
        (2, 'BNC-XXXX-4444', 0),
        (2, 'BNC-XXXX-5555', 0)
    ");

    echo "Database seeded successfully!\n";

} catch (\Exception $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
}
