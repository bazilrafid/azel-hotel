<?php
session_start();
require_once 'db.php';

// Fungsi helper SweetAlert DENGAN MARKUP HTML LENGKAP
function showAlert($icon, $title, $text, $redirect = null) {
    // Pastikan header belum terkirim
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    
    // Tambahkan HTML dasar agar SweetAlert berfungsi dan halaman tidak blank
    echo "<!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Status Pembayaran</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
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
        </script>
    </body>
    </html>";
    exit;
}

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    showAlert('warning', 'Login Diperlukan', 'Silakan login terlebih dahulu.', 'login.php');
}

// Pastikan form dikirim via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showAlert('error', 'Akses Ditolak', 'Gunakan form pembayaran.', 'index.php');
}

$id_user        = $_SESSION['user_id'];
$id_reservasi   = $_POST['id_reservasi'] ?? null;
$nama           = trim($_POST['nama'] ?? '');
$metode         = trim($_POST['metode'] ?? '');
$total          = $_POST['total_harga'] ?? null;
$bukti_file     = $_FILES['bukti'] ?? null;

// Validasi input dasar
if (empty($id_reservasi) || empty($nama) || empty($metode) || empty($total) || empty($bukti_file['name'])) {
    showAlert('error', 'Gagal', 'Semua field wajib diisi!', 'payment.php');
}

try {
    $db = new Database();
    $conn = $db->connect();

    // Cek status reservasi dan kepemilikan
    $check_reservasi = $conn->prepare("SELECT status_pembayaran FROM reservasi WHERE id_reservasi = ? AND id_user = ?");
    $check_reservasi->execute([$id_reservasi, $id_user]);
    $current_status = $check_reservasi->fetchColumn();

    if (!$current_status) {
        showAlert('error', 'Gagal', 'Reservasi tidak valid atau bukan milik Anda.', 'index.php');
    }
    
    if ($current_status === 'menunggu konfirmasi' || $current_status === 'dikonfirmasi') {
        showAlert('warning', 'Sudah Diproses', 'Pesanan ini sudah menunggu konfirmasi atau sudah dibayar.', 'index.php');
    }

    // --- Proses Upload Bukti Pembayaran ---
    $targetDir = "proofs/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . '_' . uniqid() . '_' . basename($bukti_file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']; 

    if (!in_array($fileType, $allowedTypes)) {
        showAlert('error', 'Gagal Upload', 'Format file bukti tidak valid. Gunakan JPG, PNG, atau GIF.', 'payment.php');
    }
    
    if ($bukti_file["size"] > 5242880) { // 5MB limit
        showAlert('error', 'Gagal Upload', 'Ukuran file terlalu besar, maksimal 5MB.', 'payment.php');
    }

    if (!move_uploaded_file($bukti_file["tmp_name"], $targetFilePath)) {
        showAlert('error', 'Gagal Upload', 'Terjadi kesalahan saat mengunggah bukti pembayaran.', 'payment.php');
    }

    // --- REVISI: Simpan Data Pembayaran ke Database ---
    // 1. INSERT ke tabel 'pembayaran'
    //    Gunakan kolom 'jumlah' (bukan 'total_harga') dan TIDAK perlu kolom 'bukti_pembayaran' atau 'nama' di tabel ini.
    $stmt = $conn->prepare("INSERT INTO pembayaran (id_reservasi, metode, jumlah, status, tanggal_bayar)
                             VALUES (?, ?, ?, 'menunggu', NOW())");
    // Catatan: Status enum di tabel pembayaran adalah 'menunggu'/'valid'/'gagal', bukan 'menunggu konfirmasi'.
    $stmt->execute([$id_reservasi, $metode, $total]);

    // 2. UPDATE tabel 'reservasi'
    //    Update status_pembayaran DAN masukkan nama file bukti ke kolom 'bukti_pembayaran'.
    $update = $conn->prepare("UPDATE reservasi 
                              SET status_pembayaran = 'menunggu konfirmasi', 
                                  bukti_pembayaran = ? 
                              WHERE id_reservasi = ? AND id_user = ?");
    $update->execute([$fileName, $id_reservasi, $id_user]);

    // Redirect setelah sukses
    showAlert('success', 'Pembayaran Terkirim!', 'Terima kasih, bukti pembayaran Anda telah dikirim dan akan segera dikonfirmasi.', 'index.php');

} catch (PDOException $e) {
    // Tangani error dan hapus file yang mungkin terlanjur terupload
    if (isset($targetFilePath) && is_file($targetFilePath)) {
        unlink($targetFilePath);
    }
    showAlert('error', 'Database Error', "Terjadi kesalahan database: " . $e->getMessage(), 'payment.php');
}
?>