<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $db = new Database();
    $conn = $db->connect();

    // Cek apakah email sudah terdaftar
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $_SESSION['error'] = "Email sudah digunakan!";
        header("Location: register.php");
        exit;
    }

    // Insert data user baru
    $stmt = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$nama, $email, $password])) {
        $_SESSION['success'] = "Akun berhasil dibuat!";
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat registrasi.";
        header("Location: register.php");
        exit;
    }
}
?>
