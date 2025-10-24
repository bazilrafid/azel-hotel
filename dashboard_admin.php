<?php
session_start();
require_once 'db.php'; 

// Cek autentikasi dan role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    // Arahkan ke login jika belum login atau bukan admin
    $_SESSION['error'] = 'Akses ditolak. Anda harus login sebagai Admin.';
    header("Location: login.php");
    exit;
}

$admin_nama = $_SESSION['user_nama'] ?? 'Admin';

// Koneksi DB
$db = new Database();
// Asumsi: Method connect() atau property conn ada
if (method_exists($db, 'connect')) {
    $conn = $db->connect();
} elseif (property_exists($db, 'conn') && $db->conn) {
    $conn = $db->conn;
} else {
    die('Koneksi database tidak ditemukan.');
}

// Query untuk mengambil semua reservasi yang menunggu konfirmasi pembayaran
try {
    $sql = "SELECT 
                r.id_reservasi, r.check_in, r.check_out, r.total_harga, r.status_pembayaran, r.bukti_pembayaran, r.created_at,
                k.nama_kamar, u.nama AS user_nama, u.email AS user_email, p.metode, p.jumlah
            FROM 
                reservasi r
            JOIN 
                kamar k ON r.id_kamar = k.id_kamar
            JOIN
                users u ON r.id_user = u.id_user 
            LEFT JOIN
                pembayaran p ON r.id_reservasi = p.id_reservasi
            WHERE 
                r.status_pembayaran IN ('menunggu konfirmasi', 'dikonfirmasi', 'dibatalkan', 'pending')
            ORDER BY 
                r.created_at DESC"; 
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error saat mengambil data pesanan: " . $e->getMessage());
}

// Fungsi helper untuk badge status
function getStatusBadge($status) {
    $status = strtolower($status);
    $classes = "px-3 py-1 rounded-full text-xs font-semibold ";
    switch ($status) {
        case 'dikonfirmasi':
            $classes .= "bg-green-100 text-green-800";
            break;
        case 'menunggu konfirmasi':
            $classes .= "bg-yellow-100 text-yellow-800";
            break;
        case 'dibatalkan':
            $classes .= "bg-red-100 text-red-800";
            break;
        case 'pending':
            $classes .= "bg-blue-100 text-blue-800";
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
    <title>Dashboard Admin | Azel Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --primary: #8b0000; }
        .bg-primary { background-color: var(--primary); }
        .text-primary { color: var(--primary); }
        .pt-nav { padding-top: 5.5rem; } 
    </style>
</head>
<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <nav class="fixed top-0 left-0 w-full bg-white shadow z-40">
      <div class="flex items-center justify-between px-10 py-4 w-full">
        <div class="flex items-center gap-3">
          <i class="fas fa-chart-line text-2xl text-primary"></i>
          <a href="dashboard_admin.php" class="text-2xl font-bold text-primary tracking-wide">ADMIN DASHBOARD</a>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-gray-600">Halo, <?= htmlspecialchars($admin_nama); ?></span>
            <a href="logout.php" class="text-red-600 hover:text-red-800 font-semibold">Logout</a>
        </div>
      </div>
    </nav>
    
    <br>
    <br>

    <div class="max-w-8xl mx-auto px-6 py-10 flex-grow pt-nav">
        <h1 class="text-4xl font-extralight text-gray-800 mb-2">Konfirmasi Pembayaran</h1>
        <p class="text-lg text-gray-600 mb-10">Daftar semua reservasi dan status pembayaran.</p>

        <?php if (empty($reservations)): ?>
            <div class="text-center py-10 bg-white rounded-lg shadow-xl border border-gray-200">
                <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                <p class="text-xl text-gray-600">Tidak ada pesanan yang perlu dikonfirmasi saat ini.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto bg-white rounded-xl shadow-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID/Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kamar & Check-in</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode/Total Bayar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($reservations as $res): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                #<?= str_pad($res['id_reservasi'], 5, '0', STR_PAD_LEFT); ?><br>
                                <span class="text-xs text-gray-500"><?= date('d M Y', strtotime($res['created_at'])); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-semibold"><?= htmlspecialchars($res['nama_kamar']); ?></span><br>
                                <span class="text-xs text-gray-500">Check-in: <?= date('d M Y', strtotime($res['check_in'])); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($res['user_nama']); ?><br>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars($res['user_email']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($res['metode'] ?? '-'); ?><br>
                                <span class="font-bold text-primary">Rp <?= number_format($res['total_harga'], 0, ',', '.'); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?= getStatusBadge($res['status_pembayaran']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <a href="proofs/<?= htmlspecialchars($res['bukti_pembayaran']); ?>" target="_blank" class="text-blue-600 hover:text-blue-900 mr-3" title="Lihat Bukti">
                                    <i class="fas fa-file-image"></i>
                                </a>
                                <?php if ($res['status_pembayaran'] === 'menunggu konfirmasi'): ?>
                                    <button 
                                        onclick="confirmPayment(<?= $res['id_reservasi']; ?>)"
                                        class="bg-green-600 text-white px-3 py-1 rounded-lg hover:bg-green-700 text-xs transition">
                                        Konfirmasi
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
    
    <footer class="bg-gray-900 text-gray-300 py-8 mt-10">
      <div class="max-w-7xl mx-auto px-6 text-center">
        <p>&copy; <?php echo date('Y'); ?> Azel Hotel Admin. All rights reserved.</p>
      </div>
    </footer>

    <script>
    function confirmPayment(id_reservasi) {
        Swal.fire({
            title: 'Yakin Konfirmasi?',
            text: "Status reservasi akan berubah menjadi Dikonfirmasi.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10B981', // green-500
            cancelButtonColor: '#8b0000', // primary
            confirmButtonText: 'Ya, Konfirmasi!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Arahkan ke script action_konfirmasi.php
                window.location.href = 'action_konfirmasi.php?id=' + id_reservasi;
            }
        })
    }
    </script>
</body>
</html>