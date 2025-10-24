<?php
session_start();
require_once 'db.php'; 

// Fungsi helper SweetAlert (sama seperti di action_payment.php)
function showAlert($icon, $title, $text, $redirect = null) {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
        <script>
            Swal.fire({
                icon: '$icon',
                title: '$title',
                text: '$text',
                allowOutsideClick: false, 
                confirmButtonText: 'OK',
                confirmButtonColor: '#8b0000' 
            }).then(() => {
                " . ($redirect ? "window.location.replace('$redirect');" : "") . "
            });
        </script></body></html>";
    exit;
}

// Cek autentikasi dan role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    showAlert('error', 'Akses Ditolak', 'Anda tidak memiliki izin Admin.', 'login.php');
}

$id_reservasi = $_GET['id'] ?? null;

if (empty($id_reservasi) || !is_numeric($id_reservasi)) {
    showAlert('error', 'Gagal', 'ID Reservasi tidak valid.', 'dashboard_admin.php');
}

try {
    $db = new Database();
    $conn = $db->connect();
    $conn->beginTransaction();

    // 1. Update status di tabel reservasi
    $stmt_reservasi = $conn->prepare("UPDATE reservasi SET status_pembayaran = 'dikonfirmasi' WHERE id_reservasi = ? AND status_pembayaran = 'menunggu konfirmasi'");
    $stmt_reservasi->execute([$id_reservasi]);

    // 2. Update status di tabel pembayaran (jika ada)
    // Asumsi: Status di tabel pembayaran berubah dari 'menunggu' menjadi 'valid'
    $stmt_pembayaran = $conn->prepare("UPDATE pembayaran SET status = 'valid' WHERE id_reservasi = ? AND status = 'menunggu'");
    $stmt_pembayaran->execute([$id_reservasi]);

    $conn->commit();
    
    // Cek apakah ada baris yang terupdate
    if ($stmt_reservasi->rowCount() > 0) {
        showAlert('success', 'Berhasil Dikonfirmasi!', "Reservasi #$id_reservasi telah dikonfirmasi.", 'dashboard_admin.php');
    } else {
        showAlert('warning', 'Tidak Ada Perubahan', "Reservasi #$id_reservasi tidak menunggu konfirmasi atau sudah diproses.", 'dashboard_admin.php');
    }

} catch (PDOException $e) {
    $conn->rollBack();
    showAlert('error', 'Database Error', "Gagal konfirmasi: " . $e->getMessage(), 'dashboard_admin.php');
}
?>