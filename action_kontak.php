<?php
session_start();
require_once 'db.php';

function showAlert($icon, $title, $text, $redirect = null, $timer = 0) {
    echo "<!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Pesan Kontak</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            icon: '$icon',
            title: '$title',
            text: '$text',
            " . ($timer > 0 ? "timer: $timer, showConfirmButton: false," : "") . "
            confirmButtonColor: '#3085d6'
        }).then(() => {
            " . ($redirect ? "window.location = '$redirect';" : "") . "
        });
    </script>
    </body>
    </html>";
    exit;
}

// ✅ Cek login dulu
if (!isset($_SESSION['id_user'])) {
    showAlert('warning', 'Login Diperlukan', 'Silakan login terlebih dahulu untuk mengirim pesan.', 'login.php');
}

// ✅ Cek method form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showAlert('error', 'Akses Ditolak', 'Gunakan form untuk mengirim pesan.', 'index.php');
}

// ✅ Ambil data form
$nama  = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$pesan = trim($_POST['pesan'] ?? '');
$id_user = $_SESSION['id_user'];

// ✅ Validasi input
if ($nama === '' || $email === '' || $pesan === '') {
    showAlert('error', 'Gagal!', 'Semua field harus diisi.', 'index.php');
}

try {
    $db = new Database();
    $conn = $db->connect();

    $sql = "INSERT INTO pesan_kontak (id_user, nama, email, pesan) VALUES (:id_user, :nama, :email, :pesan)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_user', $id_user);
    $stmt->bindParam(':nama', $nama);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':pesan', $pesan);
    $stmt->execute();

    showAlert('success', 'Pesan Terkirim!', 'Terima kasih telah menghubungi kami.', 'index.php', 2000);

} catch (PDOException $e) {
    showAlert('error', 'Database Error', $e->getMessage(), 'index.php');
}
?>
