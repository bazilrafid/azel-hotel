<?php
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo "<script>
        alert('Silakan login terlebih dahulu untuk melanjutkan pembayaran.');
        window.location.href = 'login.php';
    </script>";
    exit;
}

// Koneksi DB
$db = new Database();
$conn = $db->connect();

// Ambil data reservasi terakhir user yang statusnya masih 'pending'
$stmt = $conn->prepare("SELECT * FROM reservasi WHERE id_user = ? AND status_pembayaran = 'pending' ORDER BY id_reservasi DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<script>
        alert('Tidak ada pesanan yang ditemukan atau pesanan sudah diproses.');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Ambil data kamar
$stmt2 = $conn->prepare("SELECT harga_per_malam, nama_kamar FROM kamar WHERE id_kamar = ?");
$stmt2->execute([$order['id_kamar']]);
$room = $stmt2->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    echo "<script>
        alert('Data kamar tidak ditemukan untuk reservasi ini.');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Hitung total
$checkin = new DateTime($order['check_in']);
$checkout = new DateTime($order['check_out']);
$durasi = $checkin->diff($checkout)->days;
$total = $durasi * $room['harga_per_malam'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pembayaran | Azel Hotel</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">

  <div class="bg-white shadow-xl rounded-2xl p-10 w-full max-w-2xl">
    <h2 class="text-3xl font-light text-center text-gray-800 mb-8">Pembayaran Anda</h2>

    <div class="mb-6 text-gray-700">
      <p class="mb-1"><span class="font-semibold">Nama Kamar:</span> <?= htmlspecialchars($room['nama_kamar']); ?></p>
      <p class="mb-1"><span class="font-semibold">Durasi:</span> <?= $durasi; ?> malam</p>
      <p class="mb-1"><span class="font-semibold">Harga per malam:</span> Rp <?= number_format($room['harga_per_malam'], 0, ',', '.'); ?></p>
      <p class="text-xl font-bold mt-2 text-red-700">Total: Rp <?= number_format($total, 0, ',', '.'); ?></p>
    </div>

    <form action="action_payment.php" method="POST" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="id_reservasi" value="<?= $order['id_reservasi']; ?>">
      <input type="hidden" name="total_harga" value="<?= $total; ?>">

      <div>
        <label class="block text-gray-700 font-medium mb-1">Nama Lengkap</label>
        <input type="text" name="nama" value="<?= htmlspecialchars($_SESSION['user_nama'] ?? '') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none">
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Metode Pembayaran</label>
        <select name="metode" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none">
          <option value="">-- Pilih Metode --</option>
          <option value="Transfer Bank">Transfer Bank</option>
          <option value="E-Wallet">E-Wallet</option>
          <option value="Kartu Kredit">Kartu Kredit</option>
        </select>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Upload Bukti Pembayaran</label>
        <input type="file" name="bukti" accept="image/*" required class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-50">
      </div>

      <button type="submit" class="bg-red-700 hover:bg-red-900 text-white font-semibold py-3 px-6 rounded-lg w-full transition duration-200 shadow">
        Kirim Pembayaran
      </button>
    </form>
  </div>

  <footer class="absolute bottom-4 text-center w-full text-gray-500 text-sm">
    &copy; <?= date('Y'); ?> Azel Hotel Jakarta. Semua hak dilindungi.
  </footer>
</body>
</html>