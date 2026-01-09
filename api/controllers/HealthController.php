<?php
// /home/beni/projectku/kantor/api/controllers/HealthController.php

require_once __DIR__ . '/../core/Response.php';

class HealthController {
    public function check() {
        Response::json([
            'status' => 'ok',
            'service' => 'SidikSae API',
            'timestamp' => date('Y-m-d H:i:s')
        ], 'API is running');
    }
}
