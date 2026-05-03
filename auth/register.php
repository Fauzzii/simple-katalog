<?php
session_start();
include '../conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $cek_email = mysqli_query($conn, "SELECT email FROM pengguna WHERE email = '$email'");

    if (mysqli_num_rows($cek_email) > 0) {
        echo "<script>alert('Email sudah terdaftar!'); window.history.back();</script>";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO pengguna (username, email, password, role) 
                                       VALUES ('$username', '$email', '$password', 'user')");

        if ($insert) {
            echo "<script>
                    alert('Registrasi Berhasil! Silakan login.');
                    window.location.href='login.php';
                  </script>";
        } else {
            echo "<script>alert('Gagal melakukan registrasi, coba lagi.');</script>";
        }
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
            <h2 class="text-3xl font-extrabold text-gray-900">Buat Akun</h2>
            <p class="text-sm text-gray-500 mt-2">Lengkapi data di bawah ini</p>
        </div>

        <form action="#" method="POST">

            <div class="mb-5">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input type="text" id="username" name="username"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    placeholder="Masukkan username" required>
            </div>

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
                Daftar Sekarang
            </button>
        </form>

        <div class="mt-8 grid grid-cols-3 items-center text-gray-400">
            <hr class="border-gray-300">
            <p class="text-center text-sm">atau</p>
            <hr class="border-gray-300">
        </div>

        <p class="mt-6 text-center text-sm text-gray-600">
            Sudah punya akun?
            <a href="login.php" class="font-bold text-blue-600 hover:text-blue-500 hover:underline">Masuk di sini</a>
        </p>
    </div>

</body>

</html>