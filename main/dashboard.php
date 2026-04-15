<?php
session_start();
include "../conn.php";

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

if (isset($_GET['action'])) {
    
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id_hapus = mysqli_real_escape_string($conn, $_GET['id']);
        
        $query_foto = mysqli_query($conn, "SELECT foto FROM produk WHERE id = '$id_hapus'");
        $data_foto = mysqli_fetch_assoc($query_foto);
        $foto_hapus = $data_foto['foto'];
        
        if (file_exists("../image/" . $foto_hapus) && $foto_hapus != '') {
            unlink("../image/" . $foto_hapus);
        }

        $delete = mysqli_query($conn, "DELETE FROM produk WHERE id = '$id_hapus'");
        
        if ($delete) {
            echo "<script>alert('Produk berhasil dihapus!'); window.location.href = window.location.pathname;</script>";
        } else {
            echo "<script>alert('Gagal menghapus produk!'); window.location.href = window.location.pathname;</script>";
        }
        exit;
    }

    if ($_GET['action'] == 'remove_cart' && isset($_GET['id'])) {
        $id_hapus_cart = $_GET['id'];
        if (isset($_SESSION['cart'][$id_hapus_cart])) {
            unset($_SESSION['cart'][$id_hapus_cart]);
        }
        echo "<script>window.location.href = window.location.pathname;</script>";
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    if ($_POST['action'] == 'add_cart') {
        $id_produk = (int)$_POST['id_produk'];
        $jumlah = (int)$_POST['jumlah'];

        $q_stok = mysqli_query($conn, "SELECT stok FROM produk WHERE id = '$id_produk'");
        $d_stok = mysqli_fetch_assoc($q_stok);
        
        $qty_di_keranjang = isset($_SESSION['cart'][$id_produk]) ? $_SESSION['cart'][$id_produk] : 0;
        $total_req_qty = $qty_di_keranjang + $jumlah;

        if ($total_req_qty > $d_stok['stok']) {
            echo "<script>alert('Maaf, jumlah melebihi stok yang tersedia!');</script>";
        } else {
            $_SESSION['cart'][$id_produk] = $total_req_qty;
            echo "<script>alert('Produk berhasil ditambahkan ke keranjang!'); window.location.href = window.location.pathname;</script>";
        }
    }

    elseif ($_POST['action'] == 'checkout') {
        if (empty($_SESSION['cart'])) {
            echo "<script>alert('Keranjang Anda kosong!');</script>";
        } else {
            $id_pengguna = isset($_SESSION['id_pengguna']) ? $_SESSION['id_pengguna'] : 0;
            $tanggal_transaksi = date('Y-m-d H:i:s');
            $status = 'Menunggu Pembayaran'; 

            $total_bayar = 0;
            foreach ($_SESSION['cart'] as $id_produk => $jumlah) {
                $q_prod = mysqli_query($conn, "SELECT harga FROM produk WHERE id = '$id_produk'");
                $d_prod = mysqli_fetch_assoc($q_prod);
                $total_bayar += ($d_prod['harga'] * $jumlah);
            }

            $q_insert_trx = "INSERT INTO transaksi (id_pengguna, tanggal_transaksi, total_bayar, status) 
                             VALUES ('$id_pengguna', '$tanggal_transaksi', '$total_bayar', '$status')";
            
            if (mysqli_query($conn, $q_insert_trx)) {
                
                $id_transaksi = mysqli_insert_id($conn);
                $checkout_berhasil = true;

                foreach ($_SESSION['cart'] as $id_produk => $jumlah) {
                    $q_prod = mysqli_query($conn, "SELECT harga, stok FROM produk WHERE id = '$id_produk'");
                    $d_prod = mysqli_fetch_assoc($q_prod);
                    
                    $subtotal = $d_prod['harga'] * $jumlah;

                    $q_insert_detail = "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, subtotal) 
                                        VALUES ('$id_transaksi', '$id_produk', '$jumlah', '$subtotal')";
                    
                    if (mysqli_query($conn, $q_insert_detail)) {
                        $sisa_stok = $d_prod['stok'] - $jumlah;
                        mysqli_query($conn, "UPDATE produk SET stok = '$sisa_stok' WHERE id = '$id_produk'");
                    } else {
                        $checkout_berhasil = false;
                    }
                }

                if ($checkout_berhasil) {
                    unset($_SESSION['cart']);
                    echo "<script>alert('Checkout Berhasil! Pesanan Anda sedang diproses.'); window.location.href = window.location.pathname;</script>";
                } else {
                    echo "<script>alert('Terjadi kesalahan saat menyimpan detail pesanan.');</script>";
                }
                
            } else {
                echo "<script>alert('Terjadi kesalahan saat membuat pesanan (Header).');</script>";
            }
        }
    }

    elseif ($_POST['action'] == 'add') {
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $harga = mysqli_real_escape_string($conn, $_POST['harga']);
        $stok = mysqli_real_escape_string($conn, $_POST['stok']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $folder_tujuan = "../image/";

        $foto = $_FILES['foto']['name'];
        $tmp_foto = $_FILES['foto']['tmp_name'];
        $ekstensi = pathinfo($foto, PATHINFO_EXTENSION);
        $nama_foto_baru = time() . '_' . rand(100, 999) . '.' . $ekstensi;
        $path_simpan = $folder_tujuan . $nama_foto_baru;

        if (move_uploaded_file($tmp_foto, $path_simpan)) {
            $query_insert = "INSERT INTO produk (nama, harga, stok, foto, deskripsi) VALUES ('$nama', '$harga', '$stok', '$nama_foto_baru', '$deskripsi')";
            $insert = mysqli_query($conn, $query_insert);
            if ($insert) {
                echo "<script>alert('Produk berhasil ditambahkan!'); window.location.href = window.location.pathname;</script>";
            }
        }
    }

    elseif ($_POST['action'] == 'edit') {
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $harga = mysqli_real_escape_string($conn, $_POST['harga']);
        $stok = mysqli_real_escape_string($conn, $_POST['stok']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $foto_lama = mysqli_real_escape_string($conn, $_POST['foto_lama']);
        $folder_tujuan = "../image/";
        $foto = $_FILES['foto']['name'];

        if ($foto != "") {
            $tmp_foto = $_FILES['foto']['tmp_name'];
            $ekstensi = pathinfo($foto, PATHINFO_EXTENSION);
            $nama_foto_baru = time() . '_' . rand(100, 999) . '.' . $ekstensi;
            $path_simpan = $folder_tujuan . $nama_foto_baru;

            if (move_uploaded_file($tmp_foto, $path_simpan)) {
                if (file_exists("../image/" . $foto_lama) && $foto_lama != "") {
                    unlink("../image/" . $foto_lama);
                }
                $query_update = "UPDATE produk SET nama='$nama', harga='$harga', stok='$stok', deskripsi='$deskripsi', foto='$nama_foto_baru' WHERE id='$id'";
            }
        } else {
            $query_update = "UPDATE produk SET nama='$nama', harga='$harga', stok='$stok', deskripsi='$deskripsi' WHERE id='$id'";
        }

        $update = mysqli_query($conn, $query_update);
        if ($update) {
            echo "<script>alert('Produk berhasil diperbarui!'); window.location.href = window.location.pathname;</script>";
        }
    }
}

$where_clauses = array();

if (isset($_GET['search']) && $_GET['search'] != '') {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clauses[] = "nama LIKE '%$search%'";
}
if (isset($_GET['min']) && $_GET['min'] != '') {
    $min = mysqli_real_escape_string($conn, $_GET['min']);
    $where_clauses[] = "harga >= '$min'";
}
if (isset($_GET['max']) && $_GET['max'] != '') {
    $max = mysqli_real_escape_string($conn, $_GET['max']);
    $where_clauses[] = "harga <= '$max'";
}

$query_tampil_string = "SELECT * FROM produk";
if (count($where_clauses) > 0) {
    $query_tampil_string .= " WHERE " . implode(" AND ", $where_clauses);
}

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';
switch ($sort) {
    case 'terlama': $query_tampil_string .= " ORDER BY id ASC"; break;
    case 'termurah': $query_tampil_string .= " ORDER BY harga ASC"; break;
    case 'termahal': $query_tampil_string .= " ORDER BY harga DESC"; break;
    default: $query_tampil_string .= " ORDER BY id DESC"; break;
}

$query_tampil = mysqli_query($conn, $query_tampil_string);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800">

    <form id="filterForm" method="GET" action=""></form>

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0 flex items-center">
                    <a href="katalog.php" class="text-2xl font-bold text-blue-600">Katalog</a>
                </div>

                <div class="flex-1 flex justify-center px-2 lg:ml-6 lg:justify-end">
                    <div class="max-w-lg w-full lg:max-w-xs relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input id="search" name="search" form="filterForm"
                            value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>"
                            onkeypress="if(event.keyCode==13){document.getElementById('filterForm').submit();}"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-gray-50 placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 sm:text-sm transition duration-150 ease-in-out"
                            placeholder="Cari produk..." type="search">
                    </div>
                </div>

                <div class="ml-4 flex items-center md:ml-6 gap-4">
                    <button onclick="openCartModal()" class="relative p-2 text-gray-400 hover:text-blue-600 transition-colors">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php 
                        $jml_cart = array_sum($_SESSION['cart']); 
                        if($jml_cart > 0) { 
                        ?>
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                                <?php echo $jml_cart; ?>
                            </span>
                        <?php } ?>
                    </button>

                    <a href="profile.php" class="p-2 text-gray-400 hover:text-gray-500">
                        <i class="fas fa-user-circle text-2xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row gap-8">

            <aside class="w-full md:w-64 flex-shrink-0">
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <h3 class="font-bold text-lg mb-4 border-b pb-2">Filter</h3>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-3">Range Harga</h4>
                        <div class="flex items-center space-x-2">
                            <input type="number" name="min" form="filterForm" value="<?php echo isset($_GET['min']) ? $_GET['min'] : ''; ?>" placeholder="Min" class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:border-blue-500">
                            <span class="text-gray-500">-</span>
                            <input type="number" name="max" form="filterForm" value="<?php echo isset($_GET['max']) ? $_GET['max'] : ''; ?>" placeholder="Max" class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <button type="submit" form="filterForm" class="mt-4 w-full bg-blue-700 text-white text-sm py-2 rounded hover:bg-blue-400 transition font-medium">
                            Terapkan Filter
                        </button>

                        <?php 
                        if (isset($_GET['search']) || isset($_GET['min']) || isset($_GET['max'])) { 
                        ?>
                            <a href="katalog.php" class="mt-2 block w-full text-center bg-gray-100 text-gray-600 text-sm py-2 rounded hover:bg-gray-200 transition">Reset</a>
                        <?php } ?>
                    </div>
                </div>
            </aside>

            <main class="flex-1">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-800">Daftar Produk</h2>

                    <select name="sort" form="filterForm" onchange="document.getElementById('filterForm').submit();" class="border border-gray-300 rounded-md text-sm py-1.5 pl-2 pr-6 focus:outline-none focus:border-blue-500">
                        <option value="terbaru" <?php echo ($sort == 'terbaru') ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="terlama" <?php echo ($sort == 'terlama') ? 'selected' : ''; ?>>Terlama</option>
                        <option value="termurah" <?php echo ($sort == 'termurah') ? 'selected' : ''; ?>>Harga: Rendah ke Tinggi</option>
                        <option value="termahal" <?php echo ($sort == 'termahal') ? 'selected' : ''; ?>>Harga: Tinggi ke Rendah</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6">

                    <?php
                    if (mysqli_num_rows($query_tampil) > 0) {
                        while ($data_produk = mysqli_fetch_array($query_tampil)) {
                    ?>
                            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden group hover:shadow-md transition-shadow flex flex-col">

                                <div class="h-56 bg-gray-200 overflow-hidden relative border-b border-gray-100">
                                    <img src="../image/<?php echo $data_produk['foto']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Foto Produk">
                                </div>

                                <div class="p-5 flex flex-col flex-1 justify-between">
                                    <div>
                                        <div class="flex justify-between items-start gap-2 mb-2">
                                            <h3 class="text-lg font-bold text-gray-900 leading-tight">
                                                <?php echo $data_produk['nama']; ?>
                                            </h3>
                                            <span class="bg-blue-50 text-blue-600 text-xs font-semibold px-2.5 py-1 rounded border border-blue-100 whitespace-nowrap">
                                                Stok: <?php echo $data_produk['stok']; ?>
                                            </span>
                                        </div>

                                        <p class="text-2xl font-bold text-blue-600 mb-4">
                                            Rp <?php echo number_format($data_produk['harga'], 0, ',', '.'); ?>
                                        </p>

                                        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Detail Produk</h4>
                                        <textarea class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 focus:outline-none resize-none h-28 custom-scrollbar mb-4" readonly><?php echo $data_produk['deskripsi']; ?></textarea>
                                    </div>

                                    <div class="mt-auto border-t border-gray-100 pt-4">
                                        
                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                                            <div class="flex gap-2">
                                                <button onclick="openEditProductModal(<?php echo $data_produk['id']; ?>)" class="flex-1 bg-yellow-50 text-yellow-600 border border-yellow-200 py-2 rounded text-sm font-semibold hover:bg-yellow-500 hover:text-white transition-colors flex items-center justify-center gap-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <a href="?action=delete&id=<?php echo $data_produk['id']; ?>" onclick="return confirm('Yakin ingin menghapus produk ini?');" class="flex-1 text-center bg-red-50 text-red-600 border border-red-200 py-2 rounded text-sm font-semibold hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center gap-2">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </div>
                                        <?php } else { ?>
                                            <form method="POST" action="" class="flex gap-2">
                                                <input type="hidden" name="action" value="add_cart">
                                                <input type="hidden" name="id_produk" value="<?php echo $data_produk['id']; ?>">
                                                
                                                <input type="number" name="jumlah" min="1" max="<?php echo $data_produk['stok']; ?>" value="1" 
                                                    class="w-20 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none text-center" 
                                                    <?php echo ($data_produk['stok'] <= 0) ? 'disabled' : ''; ?>>
                                                
                                                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded text-sm font-semibold hover:bg-blue-700 transition-colors flex items-center justify-center gap-2 disabled:bg-gray-400 disabled:cursor-not-allowed" 
                                                    <?php echo ($data_produk['stok'] <= 0) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-cart-plus"></i> <?php echo ($data_produk['stok'] <= 0) ? 'Habis' : 'Tambah'; ?>
                                                </button>
                                            </form>
                                        <?php } ?>

                                    </div>
                                </div>

                            </div>

                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                                <div id="editProductModal<?php echo $data_produk['id']; ?>" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 transition-opacity px-4">
                                    <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 relative max-h-[90vh] overflow-y-auto text-left">
                                        <div class="flex justify-between items-center border-b pb-3 mb-4">
                                            <h3 class="text-lg font-bold text-gray-900">Edit Produk</h3>
                                            <button onclick="closeEditProductModal(<?php echo $data_produk['id']; ?>)" class="text-gray-400 hover:text-gray-600 transition-colors">
                                                <i class="fas fa-times text-xl"></i>
                                            </button>
                                        </div>
                                        <form method="POST" action="" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?php echo $data_produk['id']; ?>">
                                            <input type="hidden" name="foto_lama" value="<?php echo $data_produk['foto']; ?>">

                                            <div class="mb-4 text-center">
                                                <img src="../image/<?php echo $data_produk['foto']; ?>" class="h-24 mx-auto rounded mb-2 border border-gray-200 object-cover">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Ganti Foto Produk (Opsional)</label>
                                                <input type="file" name="foto" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                            </div>

                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Produk</label>
                                                <input type="text" name="nama" required value="<?php echo $data_produk['nama']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                            </div>

                                            <div class="grid grid-cols-2 gap-4 mb-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                                                    <input type="number" name="harga" min="0" required value="<?php echo $data_produk['harga']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Stok</label>
                                                    <input type="number" name="stok" min="0" required value="<?php echo $data_produk['stok']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                                </div>
                                            </div>

                                            <div class="mb-6">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Produk</label>
                                                <textarea name="deskripsi" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"><?php echo $data_produk['deskripsi']; ?></textarea>
                                            </div>

                                            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                                                <button type="button" onclick="closeEditProductModal(<?php echo $data_produk['id']; ?>)" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">Batal</button>
                                                <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition flex items-center gap-2">
                                                    <i class="fas fa-save"></i> Simpan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php } ?>
                    <?php
                        }
                    } else {
                        echo "<div class='col-span-full py-10 text-center text-gray-500'>
                                <i class='fas fa-box-open text-4xl mb-3 text-gray-300'></i>
                                <p>Tidak ada produk yang cocok dengan pencarian atau filter.</p>
                              </div>";
                    }
                    ?>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                        <div onclick="openAddModal()" class="flex justify-center items-center bg-white rounded-lg shadow-sm border border-dashed border-gray-300 overflow-hidden group hover:shadow-md hover:border-blue-300 transition-all cursor-pointer min-h-[350px]">
                            <div class="flex flex-col items-center justify-center p-6 gap-2">
                                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-50 group-hover:bg-blue-100 transition text-blue-500">
                                    <i class="fas fa-plus text-xl"></i>
                                </div>
                                <p class="text-sm font-medium text-gray-600 group-hover:text-blue-600">Tambah Produk Baru</p>
                            </div>
                        </div>
                    <?php } ?>

                </div>
            </main>
        </div>
    </div>

    <div id="cartModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 transition-opacity px-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative max-h-[90vh] overflow-y-auto custom-scrollbar">

            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-shopping-cart text-blue-600 mr-2"></i> Keranjang Anda</h3>
                <button onclick="closeCartModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="mb-4">
                <?php
                if (empty($_SESSION['cart'])) {
                    echo "<div class='text-center py-6 text-gray-500'>
                            <i class='fas fa-shopping-basket text-4xl mb-2 text-gray-300'></i>
                            <p>Keranjang Anda masih kosong.</p>
                          </div>";
                } else {
                    $total_belanja = 0;
                    $ids = implode(',', array_keys($_SESSION['cart']));
                    $q_cart = mysqli_query($conn, "SELECT id, nama, harga, foto FROM produk WHERE id IN ($ids)");
                    
                    while($row = mysqli_fetch_assoc($q_cart)) {
                        $qty = $_SESSION['cart'][$row['id']];
                        $subtotal = $row['harga'] * $qty;
                        $total_belanja += $subtotal;
                ?>
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-3">
                            <div class="flex items-center gap-3">
                                <img src="../image/<?php echo $row['foto']; ?>" class="w-12 h-12 object-cover rounded border border-gray-200">
                                <div>
                                    <p class="font-bold text-sm text-gray-800 line-clamp-1"><?php echo $row['nama']; ?></p>
                                    <p class="text-xs text-gray-500">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?> x <?php echo $qty; ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <p class="font-bold text-sm text-blue-600">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></p>
                                <a href="?action=remove_cart&id=<?php echo $row['id']; ?>" class="text-red-400 hover:text-red-600 transition-colors" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                <?php 
                    } 
                ?>
                    <div class="mt-4 pt-4 border-t-2 border-dashed border-gray-200">
                        <div class="flex justify-between items-center mb-6">
                            <span class="font-bold text-gray-700">Total Harga:</span>
                            <span class="text-xl font-bold text-blue-700">Rp <?php echo number_format($total_belanja, 0, ',', '.'); ?></span>
                        </div>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="checkout">
                            <button type="submit" class="w-full bg-green-500 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                                <i class="fas fa-check-circle"></i> Proses Checkout
                            </button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div id="addModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 transition-opacity px-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 relative max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-lg font-bold text-gray-900">Tambah Produk Baru</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto Produk</label>
                    <input type="file" name="foto" accept="image/*" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Produk</label>
                    <input type="text" name="nama" required placeholder="Masukan Nama Produk" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                        <input type="number" name="harga" min="0" required placeholder="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stok Awal</label>
                        <input type="number" name="stok" min="0" required placeholder="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Produk</label>
                    <textarea name="deskripsi" rows="3" required placeholder="Masukan Deskripsi Produk" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fas fa-save"></i> Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() { document.getElementById('addModal').classList.remove('hidden'); }
        function closeAddModal() { document.getElementById('addModal').classList.add('hidden'); }

        function openEditProductModal(id) { document.getElementById('editProductModal' + id).classList.remove('hidden'); }
        function closeEditProductModal(id) { document.getElementById('editProductModal' + id).classList.add('hidden'); }

        function openCartModal() { document.getElementById('cartModal').classList.remove('hidden'); }
        function closeCartModal() { document.getElementById('cartModal').classList.add('hidden'); }
    </script>

</body>
</html>