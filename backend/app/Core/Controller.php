<?php
namespace App\Core;

class Controller {
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function getJsonInput() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return $data ? $data : [];
    }
}
