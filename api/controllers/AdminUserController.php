<?php
// /home/beni/projectku/kantor/api/controllers/AdminUserController.php

require_once __DIR__ . '/../config/database.php';

class AdminUserController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function checkAuth() {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            header("Location: login.php");
            exit;
        }
    }

    public function login() {
        // PHASE 1 SAFE MIGRATION: Authentication delegated to Identity Module
        // This endpoint is DEPRECATED and should redirect to Identity Module
        
        header('Content-Type: application/json');
        http_response_code(410); // Gone
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Local authentication disabled. Please use Identity Module (id.sidiksae.my.id) for login.',
            'redirect_url' => 'https://id.sidiksae.my.id/v1/auth/login',
            'migration_note' => 'This endpoint is deprecated as part of Phase 1 Safe Migration'
        ]);
        exit;
    }

    public function getAllUsers() {
        $this->checkAuth();
        $stmt = $this->conn->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getUser($id) {
        $this->checkAuth();
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function createUser($data) {
        $this->checkAuth();
        
        // Validation
        // Validation
        if (empty($data['username']) || empty($data['password']) || empty($data['nama'])) {
            return ['success' => false, 'message' => 'Data wajib tidak lengkap!'];
        }

        $role = $data['role'] ?? 'staff';
        $nip = !empty($data['nip']) ? $data['nip'] : null;

        // MANIFESTO RULE: Operational Users MUST have NIP
        $operational_roles = ['camat', 'sekcam', 'kasi', 'kasubag', 'staff', 'operator']; // Adjust based on system roles
        $is_admin = ($role === 'admin' || $role === 'system_admin');

        if (!$is_admin && empty($nip)) {
             return ['success' => false, 'message' => 'User operasional WAJIB memiliki NIP!'];
        }

        // MANIFESTO RULE: Admin MUST NOT have NIP (or ignored)
        if ($is_admin) {
            $nip = null; 
        }

        // Check Username
        $check = $this->conn->prepare("SELECT id FROM users WHERE username = :username");
        $check->execute([':username' => $data['username']]);
        if ($check->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username sudah digunakan!'];
        }

        try {
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("INSERT INTO users (username, password, nama, jabatan, role, nip, is_active, created_at) 
                                          VALUES (:username, :pass, :nama, :jabatan, :role, :nip, :active, NOW())");
            
            $stmt->execute([
                ':username' => $data['username'],
                ':pass' => $hashed_password,
                ':nama' => $data['nama'],
                ':jabatan' => $data['jabatan'] ?? 'Staff',
                ':role' => $role,
                ':nip' => $nip,
                ':active' => isset($data['is_active']) ? 1 : 0
            ]);

            return ['success' => true, 'message' => 'User berhasil ditambahkan!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function updateUser($id, $data) {
        $this->checkAuth();
        
        try {
            $query = "UPDATE users SET nama = :nama, jabatan = :jabatan, role = :role, is_active = :active, nip = :nip";
            
            $role = $data['role'];
            $nip = !empty($data['nip']) ? $data['nip'] : null;
            $operational_roles = ['camat', 'sekcam', 'kasi', 'kasubag', 'staff', 'operator'];
            $is_admin = ($role === 'admin' || $role === 'system_admin');

             if (!$is_admin && empty($nip)) {
                 return ['success' => false, 'message' => 'User operasional WAJIB memiliki NIP!'];
             }
             if ($is_admin) {
                 $nip = null;
             }

            $params = [
                ':nama' => $data['nama'],
                ':jabatan' => $data['jabatan'],
                ':role' => $role,
                ':active' => isset($data['is_active']) ? 1 : 0,
                ':nip' => $nip,
                ':id' => $id
            ];

            // Only update password if filled
            if (!empty($data['password'])) {
                $query .= ", password = :pass";
                $params[':pass'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $query .= " WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'User berhasil diperbarui!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function deleteUser($id) {
        $this->checkAuth();
        
        // Prevent deleting self
        if ($id == $_SESSION['admin_id']) {
            return ['success' => false, 'message' => 'Tidak bisa menghapus akun sendiri!'];
        }

        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return ['success' => true, 'message' => 'User berhasil dihapus!'];
        } catch (Exception $e) {
             return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
