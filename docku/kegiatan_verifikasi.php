<?php
// kegiatan_verifikasi.php
require_once 'config/database.php';
require_once 'includes/auth.php';

$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? 'verify'; // verify, unverify, or reject

if ($id) {
    try {
        // Get kegiatan data to check creator role and team status
        $stmt = $pdo->prepare("
            SELECT k.*, u.role as creator_role 
            FROM kegiatan k
            JOIN users u ON k.created_by = u.id
            WHERE k.id = ?
        ");
        $stmt->execute([$id]);
        $kegiatan = $stmt->fetch();
        
        if (!$kegiatan) {
            die("Kegiatan tidak ditemukan.");
        }
        
        $verifier_role = $_SESSION['role'];
        $verifier_id = $_SESSION['user_id'];
        $can_verify = false;
        
        // TEAM ACTIVITY: Only team leader (creator) can verify
        if (!empty($kegiatan['join_code'])) {
            if ($verifier_id == $kegiatan['created_by']) {
                $can_verify = true;
            } else {
                die("Hanya ketua tim (pembuat kegiatan) yang dapat memverifikasi laporan tim ini.");
            }
        }
        // INDIVIDUAL ACTIVITY: Role-based hierarchy
        else {
            $creator_role = $kegiatan['creator_role'];
            
            if ($creator_role === 'staff' && $verifier_role === 'pimpinan') {
                // Kasi can verify staff reports
                $can_verify = true;
            } elseif ($creator_role === 'pimpinan' && in_array($verifier_role, ['admin'])) {
                // Admin (Sekcam/Camat) can verify kasi reports
                $can_verify = true;
            } elseif ($verifier_role === 'admin') {
                // Admin can verify anything
                $can_verify = true;
            }
            
            if (!$can_verify) {
                die("Anda tidak memiliki wewenang untuk memverifikasi laporan ini. Hierarki: Staff → Kasi → Sekcam/Camat.");
            }
        }
        
        if ($action === 'verify') {
            $stmt = $pdo->prepare("UPDATE kegiatan SET is_verified = 1, verified_by = ?, verified_at = NOW(), status = 'verified' WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
            $_SESSION['message'] = "Kegiatan berhasil diverifikasi.";
            $_SESSION['message_type'] = "success";
        } elseif ($action === 'reject') {
            // Reject/Return for revision
            $revision_note = $_POST['revision_note'] ?? 'Perlu perbaikan';
            
            $stmt = $pdo->prepare("
                UPDATE kegiatan 
                SET is_verified = 0, 
                    verified_by = NULL, 
                    verified_at = NULL, 
                    status = 'revision',
                    revision_note = ?,
                    revision_by = ?,
                    revision_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$revision_note, $_SESSION['user_id'], $id]);
            $_SESSION['message'] = "Laporan dikembalikan untuk diralat.";
            $_SESSION['message_type'] = "warning";
        } else {
            // Unverify
            $stmt = $pdo->prepare("UPDATE kegiatan SET is_verified = 0, verified_by = NULL, verified_at = NULL, status = 'pending' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Verifikasi dibatalkan.";
            $_SESSION['message_type'] = "info";
        }
        
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    } catch (Exception $e) {
        die("Gagal memproses verifikasi: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}
?>
