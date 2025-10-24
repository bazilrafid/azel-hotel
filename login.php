<?php
// PHP untuk mengambil nilai redirect dari URL
$redirect_to = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Azel Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --primary-color: #8b0000; }
        .bg-primary-dark { background-color: var(--primary-color); }
        .bg-primary-dark:hover { background-color: #6a0000; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-2xl rounded-2xl w-full max-w-md p-10">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Login ke <span class="text-red-800">Azel Hotel</span></h2>
        <form action="action_login.php" method="POST" class="space-y-6">
            
            <?php if (!empty($redirect_to)): ?>
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">
            <?php endif; ?>
            
            <div>
                <label class="block text-gray-700 text-sm mb-2">Email</label>
                <input type="email" name="email" required
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-red-700 focus:border-red-700">
            </div>
            <div>
                <label class="block text-gray-700 text-sm mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-red-700 focus:border-red-700">
            </div>
            <button type="submit" class="w-full bg-primary-dark text-white py-3 rounded-lg font-bold hover:bg-red-900 transition">
                LOGIN
            </button>
            <p class="text-sm text-gray-600 text-center mt-4">
                Belum punya akun?
                <a href="register.php" class="text-red-700 font-semibold hover:underline">Daftar Sekarang</a>
            </p>
        </form>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</html>