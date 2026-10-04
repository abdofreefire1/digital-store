<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class SystemController extends Controller {
    public function health() {
        $dbStatus = false;
        try {
            $pdo = Database::getInstance();
            $pdo->query("SELECT 1");
            $dbStatus = true;
        } catch (\Exception $e) {
            $dbStatus = false;
        }

        $this->jsonResponse([
            'status' => 'ok',
            'timestamp' => time(),
            'database' => $dbStatus ? 'connected' : 'disconnected',
            'env' => APP_ENV
        ]);
    }
}
