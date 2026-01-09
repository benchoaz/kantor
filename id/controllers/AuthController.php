<?php
namespace App\Controllers;

require_once __DIR__ . '/../models/User.php';

use App\Core\Request;
use App\Core\Response;
use App\Models\User;

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login() {
        $username = Request::input('username');
        $password = Request::input('password');

        if (!$username || !$password) {
            Response::error("Username and password are required", 400);
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::error("Invalid credentials", 401);
        }

        // Generate simple token (In real system use JWT or secure random string)
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+8 hours'));

        $this->userModel->createToken($user['id'], $token, $expiresAt);
        $this->userModel->updateLastLogin($user['id']);

        Response::success("Login successful", [
            'uuid_user' => $user['uuid_user'],
            'full_name' => $user['full_name'],
            'token' => $token,
            'expires_at' => $expiresAt
        ]);
    }

    public function verify() {
        $token = Request::headers('X-TOKEN');

        if (!$token) {
            Response::error("Token missing", 401);
        }

        $userData = $this->userModel->verifyToken($token);

        if (!$userData) {
            Response::error("Invalid or expired token", 401);
        }

        Response::success("Token is valid", $userData);
    }
}
