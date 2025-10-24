<?php
require_once 'db.php';
session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $db = new Database();
    $conn = $db->connect();

    // Pastikan query mengambil kolom 'role' atau 'level' jika ada
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // --- Setting Session ---
        // Sesuaikan nama kolom primary key (banyak struktur pakai id_user)
        if (isset($user['id_user'])) {
            $_SESSION['user_id'] = $user['id_user'];
        } elseif (isset($user['id'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['id_user'] = $user['id'];
        } else {
            // fallback: store whole row id if present under another name
            $_SESSION['user_id'] = reset($user);
        }

        // simpan nama user
        if (isset($user['nama'])) {
            $_SESSION['user_nama'] = $user['nama'];
        } elseif (isset($user['name'])) {
            $_SESSION['user_nama'] = $user['name'];
        }

        // **PENTING: Tambahkan session role**
        // Asumsi: Kolom role ada di tabel users
        $_SESSION['user_role'] = $user['role'] ?? 'user'; 

        // Simpan session success untuk SweetAlert
        $_SESSION['success'] = "Login berhasil! Selamat datang, " . ($_SESSION['user_nama'] ?? 'User') . "!";
        
        // --- REVISI: Tentukan tujuan redirect ---
        $redirect_url = 'index.php'; // Default untuk user biasa
        
        // Cek jika user adalah ADMIN, arahkan ke dashboard_admin.php
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
            $redirect_url = 'dashboard_admin.php';
        }
        // Cek apakah ada tujuan redirect dari hidden input form login (ini override default/admin)
        elseif (isset($_POST['redirect_to']) && !empty($_POST['redirect_to'])) {
            $redirect_url = $_POST['redirect_to'];
        }
        
        header("Location: " . $redirect_url);
        exit;
        // --- END REVISI ---

    } else {
        $_SESSION['error'] = "Email atau password salah!";
        header("Location: login.php");
        exit;
    }
}
?>