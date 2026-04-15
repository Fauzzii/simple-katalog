<?php
session_start();
include "../conn.php";

if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    echo "<script>
          alert('Anda harus login terlebih dahulu')
          window.location.href='../auth/login.php'
        </script>";
    exit;
}

$id_pengguna = $_SESSION['id_pengguna'];

if ($_SERVER["REQUEST_METHOD"] == 'POST') {
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);

    $update = mysqli_query($conn, "UPDATE pengguna SET 
        alamat = '$alamat', 
        no_hp = '$no_hp' 
        WHERE id = '$id_pengguna'");

    if ($update) {
        echo "<script>alert('Profil berhasil diperbarui!'); window.location.href='profile.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui profil.');</script>";
    }
}

$query = mysqli_query($conn, "SELECT * FROM pengguna WHERE id = '$id_pengguna'");
$data_user = mysqli_fetch_assoc($query);

$query_trx = mysqli_query($conn, "SELECT id, tanggal_transaksi, total_bayar, status FROM transaksi WHERE id_pengguna = '$id_pengguna' ORDER BY tanggal_transaksi DESC LIMIT 3");

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 font-sans text-gray-800">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
            <a href="dashboard.php" class="text-gray-500 hover:text-blue-600 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">

            <div class="md:flex">
                <div class="md:w-1/3 bg-gray-50 p-8 border-r border-gray-100 flex flex-col items-center">
                    <div class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-5xl mb-4 shadow-inner">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-1">
                        <?php echo $data_user['username']; ?>
                    </h2>
                    <p class="text-sm text-gray-500 mb-6 px-3 py-1 bg-gray-200 rounded-full">
                        <?php echo $data_user['no_hp'] ?: 'No HP belum diatur'; ?>
                    </p>

                    <div class="w-full space-y-3 mt-4">
                        <button onclick="openEditModal()" class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-edit"></i> Edit Profil
                        </button>
                        <a href="../action/logout.php" class="w-full flex items-center justify-center bg-white border border-red-500 text-red-500 py-2 rounded-lg hover:bg-red-50 transition">
                            <i class="fas fa-sign-out-alt"></i> Keluar
                        </a>
                    </div>
                </div>

                <div class="md:w-2/3 p-8">
                    <h3 class="text-xl font-bold border-b pb-3 mb-6">Informasi Akun</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Username</label>
                            <p class="text-gray-900 font-medium bg-gray-50 p-3 rounded border border-gray-100">
                                <?php echo $data_user['username']; ?>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Alamat Email</label>
                            <p class="text-gray-900 font-medium bg-gray-50 p-3 rounded border border-gray-100">
                                <?php echo $data_user['email'] ?: 'Email tidak tersedia'; ?>
                            </p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Alamat Lengkap</label>
                            <p class="text-gray-900 font-medium bg-gray-50 p-3 rounded border border-gray-100">
                                <?php echo $data_user['alamat'] ?: 'Alamat belum diatur'; ?>
                            </p>
                        </div>
                    </div>

                    <h3 class="text-xl font-bold border-b pb-3 mb-6">Aktivitas Terakhir</h3>

                    <?php if (mysqli_num_rows($query_trx) > 0) { ?>
                        <div class="space-y-3">
                            <?php
                            while ($trx = mysqli_fetch_assoc($query_trx)) {
                                $status = $trx['status'];
                                $color = "text-gray-600";
                                if ($status == 'Selesai') $color = "text-green-600";
                                elseif ($status == 'Ditolak') $color = "text-red-600";
                                elseif ($status == 'Menunggu Pembayaran' || $status == 'Diproses') $color = "text-yellow-600";
                                elseif ($status == 'Diperjalanan') $color = "text-blue-600";
                            ?>
                                <div class="flex justify-between items-center bg-gray-50 p-4 rounded-lg border border-gray-100">
                                    <div>
                                        <p class="font-bold text-sm text-gray-900">#TRX-<?php echo $trx['id']; ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($trx['tanggal_transaksi'])); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-sm text-blue-600">Rp <?php echo number_format($trx['total_bayar'], 0, ',', '.'); ?></p>
                                        <p class="text-xs font-semibold <?php echo $color; ?>"><?php echo $status; ?></p>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="mt-5 text-center">
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                                <a href="../admin/transaksi.php" class="text-sm font-medium text-blue-600 hover:underline inline-flex items-center gap-1">Lihat Semua Transaksi Sistem <i class="fas fa-arrow-right"></i></a>
                            <?php } else { ?>
                                <a href="transaksi.php" class="text-sm font-medium text-blue-600 hover:underline inline-flex items-center gap-1">Lihat Semua Riwayat Pesanan <i class="fas fa-arrow-right"></i></a>
                            <?php } ?>
                        </div>

                    <?php } else { ?>
                        <div class="bg-gray-50 rounded-lg p-6 text-center border border-dashed border-gray-300">
                            <i class="fas fa-box-open text-3xl text-gray-400 mb-3"></i>
                            <p class="text-gray-500">Belum ada riwayat transaksi.</p>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') { ?>
                                <a href="dashboard.php" class="inline-block mt-3 text-blue-600 hover:underline font-medium">Mulai Belanja</a>
                            <?php } ?>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                                <a href="../admin/transaksi.php" class="inline-block mt-3 text-blue-600 hover:underline font-medium">Cek Transaksi Sistem</a>
                            <?php } ?>
                        </div>
                    <?php } ?>

                </div>
            </div>

        </div>
    </div>

    <div id="editModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 transition-opacity">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">

            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-lg font-bold text-gray-900">Edit Profil</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST" action="">
                <div class="mb-4">
                    <label for="no_hp" class="block text-sm font-medium text-gray-700 mb-2">Nomor Handphone</label>
                    <input type="text" id="no_hp" name="no_hp"
                        value="<?php echo $data_user['no_hp'] ?: ''; ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none"
                        placeholder="Nomor Handphone">
                </div>

                <div class="mb-6">
                    <label for="alamat" class="block text-sm font-medium text-gray-700 mb-2">Alamat Lengkap</label>
                    <textarea id="alamat" name="alamat" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none"
                        placeholder="Masukkan alamat"><?php echo $data_user['alamat'] ?: ''; ?></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>

</body>

</html>