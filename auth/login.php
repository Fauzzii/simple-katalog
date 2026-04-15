<?php
session_start();
include '../conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM pengguna where email = '$email' and password = '$password'");
    $data_user = mysqli_fetch_array($query);

    if (mysqli_num_rows($query) > 0) {
        $_SESSION['status_login'] = true;
        $_SESSION['id_pengguna'] = $data_user['id'];
        $_SESSION['username'] = $data_user['username'];
        $_SESSION['role'] = $data_user['role'];

        echo "<script>
              alert('Login berhasil!')
              window.location.href='../main/dashboard.php'
            </script>";
    }else {
        echo "<script>alert('Email atau password tidak sesuai')</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen font-sans">

    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        
        <div class="text-center mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900">Selamat Datang</h2>
            <p class="text-sm text-gray-500 mt-2">Silakan masuk ke akun Anda</p>
        </div>

        <form action="#" method="POST">
            
            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Alamat Email</label>
                <input type="email" id="email" name="email" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                    placeholder="Email" required>
            </div>

            <div class="mb-5">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Kata Sandi</label>
                <input type="password" id="password" name="password" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                    placeholder="Password" required>
            </div>

            <button type="submit" 
                class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-300">
                Masuk
            </button>
        </form>

        <div class="mt-8 grid grid-cols-3 items-center text-gray-400">
            <hr class="border-gray-300">
            <p class="text-center text-sm">atau</p>
            <hr class="border-gray-300">
        </div>

        <p class="mt-6 text-center text-sm text-gray-600">
            Belum punya akun? 
            <a href="register.php" class="font-bold text-blue-600 hover:text-blue-500 hover:underline">Daftar sekarang</a>
        </p>
    </div>

</body>
</html>