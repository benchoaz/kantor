<?php
/**
 * DISPOSISI AUDIT LOGGER
 * 
 * Purpose: Log all disposisi actions to audit table
 * For BPK/Inspektorat compliance and forensics
 * 
 * @author SidikSae Backend Team
 * @date 2026-01-10
 */

require_once __DIR__ . '/../config/database.php';

class DisposisiAuditLogger {
    private $conn;
    
    public function __construct($connection = null) {
        if ($connection) {
            $this->conn = $connection;
        } else {
            $db = new Database();
            $this->conn = $db->getConnection();
        }
    }
    
    /**
     * Log disposisi action
     * 
     * @param array $data Log data
     * @return bool Success
     */
    public function log($data) {
        try {
            $sql = "INSERT INTO disposisi_audit (
                uuid_disposisi,
                user_id,
                user_role,
                action,
                uuid_surat,
                to_role,
                metadata,
                ip_address,
                created_at
            ) VALUES (
                :uuid_disposisi,
                :user_id,
                :user_role,
                :action,
                :uuid_surat,
                :to_role,
                :metadata,
                :ip_address,
                NOW()
            )";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':uuid_disposisi' => $data['uuid_disposisi'] ?? null,
                ':user_id' => $data['user_id'],
                ':user_role' => $data['user_role'],
                ':action' => $data['action'],
                ':uuid_surat' => $data['uuid_surat'],
                ':to_role' => $data['to_role'] ?? null,
                ':metadata' => json_encode($data['metadata'] ?? []),
                ':ip_address' => $this->getClientIP()
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log('[Audit] Failed to log: ' . $e->getMessage());
            // Don't throw - audit failure shouldn't break main flow
            return false;
        }
    }
    
    /**
     * Log CREATE action
     * 
     * @param string $uuid_disposisi Disposisi UUID
     * @param string $user_id User UUID
     * @param string $user_role User role (pimpinan, sekcam, etc)
     * @param string $uuid_surat Surat UUID
     * @param string $to_role Target role
     * @param array $metadata Additional data (instruksi, sifat, deadline)
     * @return bool
     */
    public function logCreate($uuid_disposisi, $user_id, $user_role, $uuid_surat, $to_role, $metadata = []) {
        return $this->log([
            'uuid_disposisi' => $uuid_disposisi,
            'user_id' => $user_id,
            'user_role' => $user_role,
            'action' => 'CREATE',
            'uuid_surat' => $uuid_surat,
            'to_role' => $to_role,
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Log READ action
     * 
     * @param string $uuid_disposisi Disposisi UUID
     * @param string $user_id User UUID
     * @param string $user_role User role
     * @param string $uuid_surat Surat UUID
     * @return bool
     */
    public function logRead($uuid_disposisi, $user_id, $user_role, $uuid_surat) {
        return $this->log([
            'uuid_disposisi' => $uuid_disposisi,
            'user_id' => $user_id,
            'user_role' => $user_role,
            'action' => 'READ',
            'uuid_surat' => $uuid_surat
        ]);
    }
    
    /**
     * Log DONE action (disposition completed)
     * 
     * @param string $uuid_disposisi Disposisi UUID
     * @param string $user_id User UUID
     * @param string $user_role User role
     * @param string $uuid_surat Surat UUID
     * @param array $metadata Laporan, etc
     * @return bool
     */
    public function logDone($uuid_disposisi, $user_id, $user_role, $uuid_surat, $metadata = []) {
        return $this->log([
            'uuid_disposisi' => $uuid_disposisi,
            'user_id' => $user_id,
            'user_role' => $user_role,
            'action' => 'DONE',
            'uuid_surat' => $uuid_surat,
            'metadata' => $metadata
        ]);
    }
    
    /**
     * Log CANCEL action (pimpinan only)
     * 
     * @param string $uuid_disposisi Disposisi UUID
     * @param string $user_id User UUID
     * @param string $user_role User role (should be pimpinan)
     * @param string $uuid_surat Surat UUID
     * @param string $reason Cancellation reason
     * @return bool
     */
    public function logCancel($uuid_disposisi, $user_id, $user_role, $uuid_surat, $reason = '') {
        return $this->log([
            'uuid_disposisi' => $uuid_disposisi,
            'user_id' => $user_id,
            'user_role' => $user_role,
            'action' => 'CANCEL',
            'uuid_surat' => $uuid_surat,
            'metadata' => ['reason' => $reason]
        ]);
    }
    
    /**
     * Log UPDATE action (pimpinan/sekcam only)
     * 
     * @param string $uuid_disposisi Disposisi UUID
     * @param string $user_id User UUID
     * @param string $user_role User role
     * @param string $uuid_surat Surat UUID
     * @param array $changes Changed fields
     * @return bool
     */
    public function logUpdate($uuid_disposisi, $user_id, $user_role, $uuid_surat, $changes = []) {
        return $this->log([
            'uuid_disposisi' => $uuid_disposisi,
            'user_id' => $user_id,
            'user_role' => $user_role,
            'action' => 'UPDATE',
            'uuid_surat' => $uuid_surat,
            'metadata' => ['changes' => $changes]
        ]);
    }
    
    /**
     * Get audit trail for disposisi
     * 
     * @param string $uuid_disposisi Disposisi UUID
     * @return array Audit records
     */
    public function getAuditTrail($uuid_disposisi) {
        try {
            $sql = "SELECT 
                        da.*,
                        u.nama_lengkap as user_name
                    FROM disposisi_audit da
                    LEFT JOIN users u ON da.user_id = u.uuid_user
                    WHERE da.uuid_disposisi = :uuid
                    ORDER BY da.created_at ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':uuid' => $uuid_disposisi]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('[Audit] Failed to retrieve trail: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get audit trail for surat (all related disposisi)
     * 
     * @param string $uuid_surat Surat UUID
     * @return array Audit records
     */
    public function getAuditTrailBySurat($uuid_surat) {
        try {
            $sql = "SELECT 
                        da.*,
                        u.nama_lengkap as user_name
                    FROM disposisi_audit da
                    LEFT JOIN users u ON da.user_id = u.uuid_user
                    WHERE da.uuid_surat = :uuid
                    ORDER BY da.created_at ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':uuid' => $uuid_surat]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('[Audit] Failed to retrieve surat trail: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get client IP address (for audit trail)
     * 
     * @return string IP address
     */
    private function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Take first IP if multiple (proxy chain)
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        
        return $ip ?: 'UNKNOWN';
    }
}
