<?php
// backend/routes/api.php

/** @var \App\Core\Router $router */

// System
$router->get('/api/v1/health', 'Api\\SystemController@health');

// Products
$router->get('/api/v1/products', 'Api\\ProductController@index');
$router->get('/api/v1/products/{id}', 'Api\\ProductController@show');

// Checkout & Orders
$router->post('/api/v1/orders', 'Api\\CheckoutController@createOrder');

// Webhooks
$router->post('/api/v1/webhooks/q8qpay', 'Api\\Q8QPayWebhookController@handle');

// Add more routes here as we implement them (e.g., manual payment proof upload)
