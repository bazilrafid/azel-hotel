<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Silakan login terlebih dahulu!";
        header("Location: login.php");
        exit;
    }

    $id_user = $_SESSION['user_id'];
    $id_kamar = $_POST['id_kamar'];
    $check_in = $_POST['checkin'];   // ✅ disesuaikan
    $check_out = $_POST['checkout']; // ✅ disesuaikan

    $db = new Database();
    $conn = $db->connect();

    // Ambil harga kamar
    $stmt = $conn->prepare("SELECT harga_per_malam FROM kamar WHERE id_kamar = ?");
    $stmt->execute([$id_kamar]);
    $harga = $stmt->fetchColumn();

    if (!$harga) {
        $_SESSION['error'] = "Kamar tidak ditemukan!";
        header("Location: index.php");
        exit;
    }

    // Hitung durasi menginap
    $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
    if ($days <= 0) {
        $_SESSION['error'] = "Tanggal tidak valid!";
        header("Location: booking.php?id=" . $id_kamar);
        exit;
    }

    $total = $days * $harga;

    // Simpan ke tabel reservasi
    $stmt = $conn->prepare("INSERT INTO reservasi (id_user, id_kamar, check_in, check_out, total_harga, status_pembayaran, created_at)
                            VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$id_user, $id_kamar, $check_in, $check_out, $total]);

    // Ambil ID reservasi terakhir
    $last_id = $conn->lastInsertId();

    $_SESSION['success'] = "Reservasi berhasil dibuat! Silakan lanjut ke pembayaran.";

    // ✅ Arahkan langsung ke payment.php
    header("Location: payment.php?id_reservasi=" . $last_id);
    exit;
}
?>
