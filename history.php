<?php
session_start();
require_once 'db.php'; 

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    // Arahkan ke login jika belum login
    header("Location: login.php?redirect=history.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'] ?? 'Pelanggan Azel';

// Koneksi DB
$db = new Database();
if (method_exists($db, 'connect')) {
    $conn = $db->connect();
} elseif (property_exists($db, 'conn') && $db->conn) {
    $conn = $db->conn;
} else {
    die('Koneksi database tidak ditemukan.');
}

// 2. Query Ambil Data Riwayat
try {
    // Mengambil data dari tabel reservasi dan kamar
    $sql = "SELECT 
                r.id_reservasi, r.check_in, r.check_out, r.total_harga, r.status_pembayaran,
                r.bukti_pembayaran, r.created_at, k.nama_kamar
            FROM 
                reservasi r
            JOIN 
                kamar k ON r.id_kamar = k.id_kamar
            WHERE 
                r.id_user = ?
            ORDER BY 
                r.created_at DESC"; // Tampilkan yang terbaru duluan
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_user]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error saat mengambil data riwayat: " . $e->getMessage());
}

// 3. Fungsi Helper untuk Badge Status
function getStatusBadge($status) {
    $status = strtolower($status);
    $classes = "px-3 py-1 rounded-full text-xs font-semibold ";
    switch ($status) {
        case 'dikonfirmasi':
            $classes .= "bg-green-100 text-green-800";
            break;
        case 'menunggu konfirmasi':
        case 'pending':
            $classes .= "bg-yellow-100 text-yellow-800";
            break;
        case 'dibatalkan':
            $classes .= "bg-red-100 text-red-800";
            break;
        default:
            $classes .= "bg-gray-100 text-gray-800";
            break;
    }
    return "<span class='$classes'>" . ucwords(str_replace('_', ' ', $status)) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Riwayat Pesanan | Azel Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --primary: #8b0000; }
        .bg-primary { background-color: var(--primary); }
        .text-primary { color: var(--primary); }
        /* Style untuk meniru navbar di index.php */
        .pt-nav { padding-top: 5.5rem; } 
    </style>
</head>
<body class="bg-gray-50 pt-nav text-gray-800">

    <nav class="fixed top-0 left-0 w-full bg-white shadow z-40">
      <div class="flex items-center justify-between px-10 py-4 w-full">
        <div class="flex items-center gap-3">
          <i class="fas fa-bed text-2xl text-primary"></i>
          <a href="index.php" class="text-2xl font-bold text-primary tracking-wide">AZEL HOTEL</a>
        </div>
        <div class="flex items-center gap-4">
            <a href="index.php" class="hover:text-primary font-semibold">Home</a>
            <a href="logout.php" class="text-red-600 hover:text-red-800 font-semibold">Logout</a>
        </div>
      </div>
    </nav>
    <div class="max-w-7xl mx-auto px-6 py-10">
        <h1 class="text-4xl font-extralight text-gray-800 mb-2">Riwayat Pesanan Anda</h1>
        <p class="text-lg text-gray-600 mb-10">Berikut adalah daftar lengkap reservasi Anda, <?= htmlspecialchars($user_nama); ?>.</p>

        <?php if (empty($reservations)): ?>
            <div class="text-center py-10 bg-white rounded-lg shadow-xl border border-gray-200">
                <i class="fas fa-box-open text-6xl text-gray-400 mb-4"></i>
                <p class="text-xl text-gray-600">Anda belum memiliki riwayat pesanan.</p>
                <a href="index.php#rooms" class="mt-5 inline-block bg-primary text-white px-5 py-2 rounded-lg hover:bg-red-900 transition">Mulai Reservasi</a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($reservations as $res): ?>
                <?php 
                    // Tentukan warna border berdasarkan status
                    $borderColor = 'border-gray-300';
                    if ($res['status_pembayaran'] == 'dikonfirmasi') $borderColor = 'border-green-500';
                    elseif ($res['status_pembayaran'] == 'dibatalkan') $borderColor = 'border-red-500';
                    elseif ($res['status_pembayaran'] == 'menunggu konfirmasi') $borderColor = 'border-yellow-500';
                ?>
                <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 <?= $borderColor; ?>">
                    <div class="flex justify-between items-start border-b pb-3 mb-3">
                        <div>
                            <p class="text-xl font-bold text-gray-800"><?= htmlspecialchars($res['nama_kamar']); ?></p>
                            <p class="text-sm text-gray-500">Dipesan pada: <?= date('d M Y H:i', strtotime($res['created_at'])); ?></p>
                        </div>
                        <div class="text-right">
                            <?= getStatusBadge($res['status_pembayaran']); ?>
                            <p class="text-xs text-gray-400 mt-1">ID Reservasi: #<?= str_pad($res['id_reservasi'], 5, '0', STR_PAD_LEFT); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-5 gap-y-4 gap-x-6 text-sm text-gray-700">
                        <div class="col-span-2 md:col-span-1">
                            <span class="font-semibold block">Check-in</span>
                            <?= date('d M Y', strtotime($res['check_in'])); ?>
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <span class="font-semibold block">Check-out</span>
                            <?= date('d M Y', strtotime($res['check_out'])); ?>
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <span class="font-semibold block">Total Harga</span>
                            <span class="font-bold text-primary">Rp <?= number_format($res['total_harga'], 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="col-span-2 md:col-span-2">
                            <span class="font-semibold block">Bukti Pembayaran</span>
                            <?php if ($res['bukti_pembayaran']): ?>
                                <a href="proofs/<?= htmlspecialchars($res['bukti_pembayaran']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    <i class="fas fa-file-image mr-1"></i> Lihat Bukti
                                </a>
                            <?php else: ?>
                                <span class="text-red-500"><i class="fas fa-times-circle mr-1"></i> Belum diunggah</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($res['status_pembayaran'] == 'pending'): ?>
                        <div class="mt-4 pt-4 border-t text-right">
                             <a href="payment.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-red-900 transition text-sm">
                                <i class="fas fa-money-check-alt mr-1"></i> Lanjutkan Pembayaran
                             </a>
                        </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
    
    <footer class="bg-gray-900 text-gray-300 py-8 mt-10">
      <div class="max-w-7xl mx-auto px-6 text-center">
        <p>&copy; <?php echo date('Y'); ?> Azel Hotel. All rights reserved.</p>
      </div>
    </footer>
</body>
</html>