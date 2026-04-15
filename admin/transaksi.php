<?php
session_start();
include "../conn.php";

if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true || $_SESSION['role'] !== 'admin') {
    echo "<script>
            alert('Akses ditolak! Halaman ini khusus Admin.');
            window.location.href = 'katalog.php';
          </script>";
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_trx = mysqli_real_escape_string($conn, $_GET['id']);
    
    if ($_GET['action'] == 'accept') {
        $update = mysqli_query($conn, "UPDATE transaksi SET status = 'Diperjalanan' WHERE id = '$id_trx'");
        if ($update) {
            echo "<script>alert('Pesanan Diterima!'); window.location.href = window.location.pathname;</script>";
        }
    } 
    elseif ($_GET['action'] == 'reject') {
        $query_detail = mysqli_query($conn, "SELECT id_produk, jumlah FROM detail_transaksi WHERE id_transaksi = '$id_trx'");
        
        while($detail = mysqli_fetch_assoc($query_detail)){
            $id_p = $detail['id_produk'];
            $qty = $detail['jumlah'];
            
            mysqli_query($conn, "UPDATE produk SET stok = stok + $qty WHERE id = '$id_p'");
        }
        
        $update = mysqli_query($conn, "UPDATE transaksi SET status = 'Ditolak' WHERE id = '$id_trx'");
        if ($update) {
            echo "<script>alert('Pesanan Ditolak!'); window.location.href = window.location.pathname;</script>";
        }
    }
    exit;
}

$query_transaksi = mysqli_query($conn, "
    SELECT t.id, t.tanggal_transaksi, t.total_bayar, t.status, p.username 
    FROM transaksi t
    LEFT JOIN pengguna p ON t.id_pengguna = p.id
    ORDER BY t.tanggal_transaksi DESC
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
                    <a href="katalog.php" class="text-gray-500 hover:text-blue-600 transition-colors">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Daftar Transaksi</h2>
                <p class="text-sm text-gray-500 mt-1">Kelola pesanan pelanggan</p>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID Pesanan</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Bayar</th>
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
                                } elseif ($status_text == 'Ditolak') {
                                    $status_color = "bg-red-100 text-red-800 border border-red-200";
                                } else {
                                    $status_color = "bg-red-100 text-red-800 border border-red-200";
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
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900">
                                            <?php echo $row['username'] ? $row['username'] : 'Anonim'; ?>
                                        </span>
                                    </div>
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
                                        if ($status_text == 'Menunggu Pembayaran' || $status_text == 'Diproses') { 
                                        ?>
                                            <a href="?action=accept&id=<?php echo $row['id']; ?>" onclick="return confirm('Terima pesanan ini dan ubah status menjadi Diperjalanan?');" class="text-white bg-green-500 hover:bg-green-600 px-3 py-1.5 rounded transition-colors" title="Terima Pesanan">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?action=reject&id=<?php echo $row['id']; ?>" onclick="return confirm('Tolak pesanan ini? Stok barang akan dikembalikan ke gudang.');" class="text-white bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded transition-colors" title="Tolak Pesanan">
                                                <i class="fas fa-times"></i>
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
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                    <i class="fas fa-receipt text-4xl mb-3 text-gray-300"></i>
                                    <p>Belum ada data transaksi masuk.</p>
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