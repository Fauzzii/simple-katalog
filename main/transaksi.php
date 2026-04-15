<?php
session_start();
include "../conn.php";

if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    echo "<script>
            alert('Silakan login terlebih dahulu untuk melihat riwayat pesanan.');
            window.location.href = '../auth/login.php';
          </script>";
    exit;
}

$id_pengguna = $_SESSION['id_pengguna'];

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_trx = mysqli_real_escape_string($conn, $_GET['id']);

    $cek_kepemilikan = mysqli_query($conn, "SELECT id FROM transaksi WHERE id = '$id_trx' AND id_pengguna = '$id_pengguna'");

    if (mysqli_num_rows($cek_kepemilikan) > 0) {

        if ($_GET['action'] == 'complete') {
            $update = mysqli_query($conn, "UPDATE transaksi SET status = 'Selesai' WHERE id = '$id_trx'");
            if ($update) {
                echo "<script>alert('Terima kasih! Pesanan telah dikonfirmasi selesai.'); window.location.href = window.location.pathname;</script>";
            }
        }
        elseif ($_GET['action'] == 'fail') {
            $query_detail = mysqli_query($conn, "SELECT id_produk, jumlah FROM detail_transaksi WHERE id_transaksi = '$id_trx'");

            while ($detail = mysqli_fetch_assoc($query_detail)) {
                $id_p = $detail['id_produk'];
                $qty = $detail['jumlah'];

                mysqli_query($conn, "UPDATE produk SET stok = stok + $qty WHERE id = '$id_p'");
            }

            $update = mysqli_query($conn, "UPDATE transaksi SET status = 'Gagal' WHERE id = '$id_trx'");
            if ($update) {
                echo "<script>alert('Pesanan ditandai Gagal. Stok barang telah dikembalikan ke sistem.'); window.location.href = window.location.pathname;</script>";
            }
        }
        exit;
    } else {
        echo "<script>alert('Akses ilegal!'); window.location.href = window.location.pathname;</script>";
        exit;
    }
}

$query_transaksi = mysqli_query($conn, "
    SELECT id, tanggal_transaksi, total_bayar, status 
    FROM transaksi 
    WHERE id_pengguna = '$id_pengguna'
    ORDER BY tanggal_transaksi DESC
");
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0 flex items-center gap-4">
                    <a href="profile.php" class="text-gray-500 hover:text-blue-600 transition-colors">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Pesanan Saya</h2>
            <p class="text-sm text-gray-500 mt-1">Lacak status pesanan dan lihat riwayat belanja Anda.</p>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID Pesanan</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Belanja</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">

                        <?php
                        if (mysqli_num_rows($query_transaksi) > 0) {
                            while ($row = mysqli_fetch_array($query_transaksi)) {

                                $status_text = $row['status'];

                                if ($status_text == 'Menunggu Pembayaran' || $status_text == 'Diproses') {
                                    $status_color = "bg-yellow-100 text-yellow-800 border border-yellow-200";
                                } elseif ($status_text == 'Diperjalanan') {
                                    $status_color = "bg-blue-100 text-blue-800 border border-blue-200";
                                } elseif ($status_text == 'Selesai') {
                                    $status_color = "bg-green-100 text-green-800 border border-green-200";
                                } elseif ($status_text == 'Ditolak' || $status_text == 'Gagal') {
                                    $status_color = "bg-red-100 text-red-800 border border-red-200";
                                } else {
                                    $status_color = "bg-gray-100 text-gray-800 border border-gray-200";
                                }
                        ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900">#TRX-<?php echo $row['id']; ?></span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo date('d M Y', strtotime($row['tanggal_transaksi'])); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo date('H:i', strtotime($row['tanggal_transaksi'])); ?> WIB</div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-bold text-blue-600">
                                            Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">

                                            <?php
                                            if ($status_text == 'Diperjalanan') {
                                            ?>
                                                <a href="?action=complete&id=<?php echo $row['id']; ?>" onclick="return confirm('Apakah pesanan sudah Anda terima dengan baik?');" class="text-white bg-green-500 hover:bg-green-600 px-3 py-2 rounded-lg transition-colors font-semibold text-xs inline-flex items-center gap-1" title="Pesanan Selesai">
                                                    <i class="fas fa-check"></i> Selesai
                                                </a>
                                                <a href="?action=fail&id=<?php echo $row['id']; ?>" onclick="return confirm('Apakah pesanan gagal Anda terima?');" class="text-white bg-red-500 hover:bg-red-600 px-3 py-2 rounded-lg transition-colors font-semibold text-xs inline-flex items-center gap-1" title="Pesanan Gagal">
                                                    <i class="fas fa-times"></i> Gagal
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-shopping-bag text-5xl mb-4 text-gray-300"></i>
                                        <p class="text-lg font-medium text-gray-600 mb-1">Belum ada pesanan</p>
                                        <p class="text-sm text-gray-400 mb-4">Anda belum pernah melakukan transaksi apa pun.</p>
                                        <a href="katalog.php" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                            Mulai Belanja
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>

                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>

</html>