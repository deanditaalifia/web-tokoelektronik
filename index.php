<?php
require_once 'koneksi.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ========================================================
// 1. STRUKTUR DATA: STRUCT (Model Data Class Ber-tipe Kuat)
// ========================================================
class ProdukStruct {
    public int $id;
    public string $nama_produk;
    public string $kategori;
    public int $harga;
    public int $stok;
    public string $gambar;
    public string $deskripsi;

    public function __construct(int $id, string $nama, string $kategori, int $harga, int $stok, string $gambar, string $deskripsi = '') {
        $this->id = $id;
        $this->nama_produk = $nama;
        $this->kategori = $kategori;
        $this->harga = $harga;
        $this->stok = $stok;
        $this->gambar = $gambar;
        $this->deskripsi = $deskripsi;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'nama_produk' => $this->nama_produk,
            'kategori' => $this->kategori,
            'harga' => $this->harga,
            'stok' => $this->stok,
            'gambar' => $this->gambar,
            'deskripsi' => $this->deskripsi
        ];
    }
}

// ========================================================
// 2. STRUKTUR DATA: POINTER (Passing by Reference `&` di PHP)
// ========================================================
function terapkanPromoPointer(array &$produkList, int $diskonPersen, array &$pointerLogs): void {
    foreach ($produkList as &$itemRef) {
        if ($itemRef['harga'] >= 4000000) {
            $hargaAwal = $itemRef['harga'];
            $potongan = (int)($hargaAwal * ($diskonPersen / 100));
            $itemRef['harga'] -= $potongan;
            $itemRef['harga_awal'] = $hargaAwal;
            $itemRef['diskon_badge'] = "$diskonPersen%";
            $pointerLogs[] = "[POINTER &] Alamat memori '{$itemRef['nama_produk']}' diubah langsung: Rp " 
                . number_format($hargaAwal, 0, ',', '.') . " -> Rp " . number_format($itemRef['harga'], 0, ',', '.');
        }
    }
    unset($itemRef); // Lepaskan referensi pointer memori
}

// ========================================================
// 3. ALGORITMA SEARCHING (Linear Search) + LOG
// ========================================================
function searchLinear(array $data, string $keyword): array {
    $logs = [];
    $matched = [];
    $step = 0;
    $logs[] = "🔍 [LINEAR SEARCH] Memulai pencarian kata kunci: '" . htmlspecialchars($keyword) . "' pada total " . count($data) . " produk...";

    foreach ($data as $idx => $item) {
        $step++;
        $nama = $item['nama_produk'];
        $kat  = $item['kategori'];
        $desc = $item['deskripsi'];
        $isMatch = (stripos($nama, $keyword) !== false) || (stripos($kat, $keyword) !== false) || (stripos($desc, $keyword) !== false);

        if ($isMatch) {
            $logs[] = "  Step #$step [INDEX $idx]: Cocok dengan '{$nama}' -> DITEMUKAN pada perbandingan ke-$step!";
            $matched[] = $item;
        } else {
            $logs[] = "  Step #$step [INDEX $idx]: '{$nama}' -> Tidak cocok, lanjut ke indeks berikutnya...";
        }
    }

    $logs[] = "✅ Linear Search selesai: Ditemukan " . count($matched) . " produk setelah $step langkah perbandingan.";
    return ['results' => $matched, 'logs' => $logs, 'steps' => $step];
}

// ========================================================
// 4. ALGORITMA SORTING (3 TIPE: BUBBLE, SELECTION, INSERTION) + LOG
// ========================================================

// A. Bubble Sort
function sortBubble(array $arr, string $field, string $direction = 'asc'): array {
    $logs = [];
    $data = $arr;
    $n = count($data);
    $step = 1;
    $swaps = 0;
    $logs[] = "⚡ [BUBBLE SORT] Mengurutkan $n produk berdasarkan '$field' (" . strtoupper($direction) . ")...";

    for ($i = 0; $i < $n - 1; $i++) {
        $swapped = false;
        $logs[] = "--- PASS KE-" . ($i + 1) . " ---";
        for ($j = 0; $j < $n - $i - 1; $j++) {
            $v1 = $data[$j][$field];
            $v2 = $data[$j + 1][$field];
            $needSwap = ($direction === 'asc') ? ($v1 > $v2) : ($v1 < $v2);

            if ($needSwap) {
                $temp = $data[$j];
                $data[$j] = $data[$j + 1];
                $data[$j + 1] = $temp;
                $swapped = true;
                $swaps++;
                $valStr1 = is_numeric($v1) ? number_format($v1, 0, ',', '.') : $v1;
                $valStr2 = is_numeric($v2) ? number_format($v2, 0, ',', '.') : $v2;
                $logs[] = "  Langkah #$step [TUKAR / SWAP]: '{$data[$j + 1]['nama_produk']}' ($valStr1) ditukar dengan '{$data[$j]['nama_produk']}' ($valStr2)";
            } else {
                $logs[] = "  Langkah #$step [OK]: Posisi '{$data[$j]['nama_produk']}' terhadap '{$data[$j + 1]['nama_produk']}' sudah terurut.";
            }
            $step++;
        }
        if (!$swapped) {
            $logs[] = ">> Pass ke-" . ($i + 1) . ": Tidak terjadi swap lagi (Array sudah terurut optimal / Early Exit).";
            break;
        }
    }
    $logs[] = "✅ Bubble Sort selesai: Total " . ($step - 1) . " perbandingan | $swaps swap.";
    return ['data' => $data, 'logs' => $logs, 'steps' => $step - 1, 'swaps' => $swaps];
}

// B. Selection Sort
function sortSelection(array $arr, string $field, string $direction = 'asc'): array {
    $logs = [];
    $data = $arr;
    $n = count($data);
    $step = 1;
    $swaps = 0;
    $logs[] = "⚡ [SELECTION SORT] Mengurutkan $n produk berdasarkan '$field' (" . strtoupper($direction) . ")...";

    for ($i = 0; $i < $n - 1; $i++) {
        $targetIdx = $i;
        $logs[] = "--- PASS KE-" . ($i + 1) . ": Mencari nilai " . ($direction === 'asc' ? 'terkecil' : 'terbesar') . " mulai indeks $i ---";
        for ($j = $i + 1; $j < $n; $j++) {
            $condition = ($direction === 'asc') ? ($data[$j][$field] < $data[$targetIdx][$field]) : ($data[$j][$field] > $data[$targetIdx][$field]);
            if ($condition) {
                $targetIdx = $j;
                $logs[] = "  Langkah #$step [KANDIDAT BARU]: Indeks $j ('{$data[$j]['nama_produk']}')";
            }
            $step++;
        }
        if ($targetIdx !== $i) {
            $temp = $data[$i];
            $data[$i] = $data[$targetIdx];
            $data[$targetIdx] = $temp;
            $swaps++;
            $logs[] = "  [TUKAR ELEMEN]: Menukar indeks $i ('{$temp['nama_produk']}') dengan indeks $targetIdx ('{$data[$i]['nama_produk']}')";
        } else {
            $logs[] = "  [TETAP]: Elemen indeks $i sudah pada posisi yang benar.";
        }
    }
    $logs[] = "✅ Selection Sort selesai: Total " . ($step - 1) . " komparasi | $swaps swap.";
    return ['data' => $data, 'logs' => $logs, 'steps' => $step - 1, 'swaps' => $swaps];
}

// C. Insertion Sort
function sortInsertion(array $arr, string $field, string $direction = 'asc'): array {
    $logs = [];
    $data = $arr;
    $n = count($data);
    $step = 1;
    $shifts = 0;
    $logs[] = "⚡ [INSERTION SORT] Mengurutkan $n produk berdasarkan '$field' (" . strtoupper($direction) . ")...";

    for ($i = 1; $i < $n; $i++) {
        $current = $data[$i];
        $j = $i - 1;
        $logs[] = "--- PASS KE-$i: Sisipkan '{$current['nama_produk']}' ke sub-array terurut ---";
        while ($j >= 0 && (($direction === 'asc') ? ($data[$j][$field] > $current[$field]) : ($data[$j][$field] < $current[$field]))) {
            $logs[] = "  Langkah #$step [GESER]: Menggeser '{$data[$j]['nama_produk']}' dari indeks $j ke " . ($j + 1);
            $data[$j + 1] = $data[$j];
            $j--;
            $step++;
            $shifts++;
        }
        $data[$j + 1] = $current;
        $logs[] = "  [PENEMPATAN]: '{$current['nama_produk']}' disisipkan di indeks " . ($j + 1);
    }
    $logs[] = "✅ Insertion Sort selesai: Total " . ($step - 1) . " komparasi | $shifts pergeseran.";
    return ['data' => $data, 'logs' => $logs, 'steps' => $step - 1, 'swaps' => $shifts];
}

// ========================================================
// 5. PROSES KERANJANG & CHECKOUT
// ========================================================
if (isset($_POST['action']) && ($_POST['action'] === 'add_cart' || $_POST['action'] === 'buy_now')) {
    $id_produk = (int)$_POST['product_id'];
    $qty = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : 1;

    $cek_p = mysqli_query($conn, "SELECT stok, nama_produk FROM products WHERE id = $id_produk");
    if ($p = mysqli_fetch_assoc($cek_p)) {
        if ($p['stok'] <= 0) {
            set_flash('danger', 'Maaf, stok untuk produk ' . htmlspecialchars($p['nama_produk']) . ' sedang habis.');
            header("Location: index.php");
            exit;
        }

        $current_qty = $_SESSION['cart'][$id_produk] ?? 0;
        $total_desired = $current_qty + $qty;

        if ($total_desired > $p['stok']) {
            $_SESSION['cart'][$id_produk] = $p['stok'];
            set_flash('warning', 'Jumlah disesuaikan dengan sisa stok maksimal (' . $p['stok'] . ' unit).');
        } else {
            $_SESSION['cart'][$id_produk] = $total_desired;
            if ($_POST['action'] !== 'buy_now') {
                set_flash('success', htmlspecialchars($p['nama_produk']) . ' berhasil ditambahkan ke keranjang!');
            }
        }
    }

    if ($_POST['action'] === 'buy_now') {
        header("Location: index.php?open_checkout=1");
        exit;
    } else {
        $cur_menu = $_GET['menu'] ?? 'katalog';
        header("Location: index.php?menu=" . urlencode($cur_menu));
        exit;
    }
}

if (isset($_GET['hapus_cart'])) {
    $id_hapus = (int)$_GET['hapus_cart'];
    unset($_SESSION['cart'][$id_hapus]);
    set_flash('info', 'Item telah dihapus dari keranjang.');
    header("Location: index.php?open_cart=1");
    exit;
}

if (isset($_GET['kosongkan_cart'])) {
    $_SESSION['cart'] = [];
    set_flash('info', 'Keranjang belanja telah dikosongkan.');
    header("Location: index.php?open_cart=1");
    exit;
}

if (isset($_POST['update_cart'])) {
    if (isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $id_prod => $jumlah) {
            $id_prod = (int)$id_prod;
            $jumlah = (int)$jumlah;
            if ($jumlah <= 0) {
                unset($_SESSION['cart'][$id_prod]);
            } else {
                $cek_stok = mysqli_query($conn, "SELECT stok FROM products WHERE id = $id_prod");
                if ($st = mysqli_fetch_assoc($cek_stok)) {
                    $_SESSION['cart'][$id_prod] = min($jumlah, (int)$st['stok']);
                }
            }
        }
        set_flash('success', 'Jumlah keranjang belanja berhasil diperbarui.');
    }
    header("Location: index.php?open_cart=1");
    exit;
}

if (isset($_POST['proses_checkout'])) {
    $nama   = mysqli_real_escape_string($conn, trim($_POST['nama_pembeli'] ?? ''));
    $no_hp  = mysqli_real_escape_string($conn, trim($_POST['no_hp'] ?? ''));
    $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat'] ?? ''));
    $metode = mysqli_real_escape_string($conn, trim($_POST['metode_pembayaran'] ?? 'Transfer Bank'));
    $catatan= mysqli_real_escape_string($conn, trim($_POST['catatan'] ?? ''));

    if (empty($nama) || empty($no_hp) || empty($alamat)) {
        set_flash('danger', 'Mohon lengkapi Nama, Nomor WhatsApp, dan Alamat Pengiriman.');
        header("Location: index.php?open_checkout=1");
        exit;
    }

    if (empty($_SESSION['cart'])) {
        set_flash('warning', 'Keranjang belanja Anda masih kosong.');
        header("Location: index.php");
        exit;
    }

    $total_harga = 0;
    $items_data = [];

    foreach ($_SESSION['cart'] as $id_prod => $qty) {
        $res = mysqli_query($conn, "SELECT * FROM products WHERE id = $id_prod");
        if ($row = mysqli_fetch_assoc($res)) {
            $beli_qty = min($qty, (int)$row['stok']);
            if ($beli_qty > 0) {
                $subtotal = $row['harga'] * $beli_qty;
                $total_harga += $subtotal;
                $items_data[] = [
                    'product_id' => $id_prod,
                    'qty' => $beli_qty,
                    'harga' => $row['harga']
                ];
            }
        }
    }

    if (empty($items_data)) {
        set_flash('danger', 'Maaf, semua produk yang dipilih saat ini sedang kehabisan stok.');
        header("Location: index.php");
        exit;
    }

    $kode_order = 'ELK-' . date('Ymd') . '-' . rand(100, 999);
    $query_order = "INSERT INTO orders (kode_pesanan, nama_pembeli, no_hp, alamat, metode_pembayaran, catatan, total_harga, status) 
                    VALUES ('$kode_order', '$nama', '$no_hp', '$alamat', '$metode', '$catatan', $total_harga, 'Pending')";

    if (mysqli_query($conn, $query_order)) {
        $order_id = mysqli_insert_id($conn);

        foreach ($items_data as $item) {
            $pid = $item['product_id'];
            $q   = $item['qty'];
            $hrg = $item['harga'];
            mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, qty, harga) VALUES ($order_id, $pid, $q, $hrg)");
            mysqli_query($conn, "UPDATE products SET stok = GREATEST(0, stok - $q) WHERE id = $pid");
        }

        $_SESSION['cart'] = [];
        set_flash('success', "Pesanan $kode_order berhasil dibuat! Menunggu konfirmasi dari Admin Toko.");
        header("Location: index.php?menu=lacak&q=" . urlencode($no_hp));
        exit;
    } else {
        set_flash('danger', 'Gagal membuat pesanan: ' . mysqli_error($conn));
        header("Location: index.php?open_checkout=1");
        exit;
    }
}

// ========================================================
// 6. MENU & PENGAMBILAN DATA PRODUK (MENGGUNAKAN STRUCT)
// ========================================================
$active_menu = $_GET['menu'] ?? 'katalog';
$allowed_menus = ['katalog', 'promo', 'kategori', 'lacak', 'bantuan'];
if (!in_array($active_menu, $allowed_menus)) {
    $active_menu = 'katalog';
}

$kategori_filter = $_GET['kategori'] ?? 'Semua';
$search_query    = trim($_GET['cari'] ?? '');
$sort_criteria   = $_GET['sort_criteria'] ?? 'harga_asc'; // harga_asc, harga_desc, nama_asc, nama_desc, stok_desc
$sort_algo       = $_GET['sort_algo'] ?? 'bubble';        // bubble, selection, insertion

// Ambil data dari database lalu parsing ke bentuk ProdukStruct (Struct)
$where_kat = ($kategori_filter !== 'Semua' && in_array($kategori_filter, $kategori_list))
    ? "WHERE kategori = '" . mysqli_real_escape_string($conn, $kategori_filter) . "'"
    : "";

$raw_db = mysqli_query($conn, "SELECT * FROM products $where_kat");
$products_struct_list = [];
while ($row = mysqli_fetch_assoc($raw_db)) {
    $pObj = new ProdukStruct(
        (int)$row['id'],
        $row['nama_produk'],
        $row['kategori'],
        (int)$row['harga'],
        (int)$row['stok'],
        $row['gambar'],
        $row['deskripsi'] ?? ''
    );
    $products_struct_list[] = $pObj->toArray();
}

// 7. PENERAPAN POINTER / REFERENCE UNTUK DISKON PRODUK
$pointer_logs = [];
terapkanPromoPointer($products_struct_list, 10, $pointer_logs);

// 8. PENERAPAN ALGORITMA SEARCHING (Linear Search) + LOG
$search_logs = [];
$products_filtered = $products_struct_list;
if (!empty($search_query)) {
    $search_output = searchLinear($products_struct_list, $search_query);
    $products_filtered = $search_output['results'];
    $search_logs = $search_output['logs'];
}

// 9. PENERAPAN ALGORITMA SORTING (Bubble, Selection, Insertion) + LOG
$sorting_logs = [];
$sort_field = 'harga';
$sort_dir   = 'asc';

if ($sort_criteria === 'harga_asc') {
    $sort_field = 'harga';
    $sort_dir = 'asc';
} elseif ($sort_criteria === 'harga_desc') {
    $sort_field = 'harga';
    $sort_dir = 'desc';
} elseif ($sort_criteria === 'nama_asc') {
    $sort_field = 'nama_produk';
    $sort_dir = 'asc';
} elseif ($sort_criteria === 'nama_desc') {
    $sort_field = 'nama_produk';
    $sort_dir = 'desc';
} elseif ($sort_criteria === 'stok_desc') {
    $sort_field = 'stok';
    $sort_dir = 'desc';
}

$start_sort_time = microtime(true);
if ($sort_algo === 'bubble') {
    $sort_output = sortBubble($products_filtered, $sort_field, $sort_dir);
} elseif ($sort_algo === 'selection') {
    $sort_output = sortSelection($products_filtered, $sort_field, $sort_dir);
} elseif ($sort_algo === 'insertion') {
    $sort_output = sortInsertion($products_filtered, $sort_field, $sort_dir);
} else {
    $sort_output = sortBubble($products_filtered, $sort_field, $sort_dir);
}
$sort_exec_time = round((microtime(true) - $start_sort_time) * 1000, 3);

$final_catalog_products = $sort_output['data'];
$sorting_logs = $sort_output['logs'];
$total_products_found = count($final_catalog_products);

// Data Kategori Count
$category_counts = [];
$res_cc = mysqli_query($conn, "SELECT kategori, COUNT(*) as c FROM products GROUP BY kategori");
while ($r = mysqli_fetch_assoc($res_cc)) {
    $category_counts[$r['kategori']] = $r['c'];
}

// Data Lacak Pesanan untuk menu 'lacak'
$lacak_q = trim($_GET['q'] ?? '');
$filter_orders_res = null;
if (!empty($lacak_q)) {
    $lq_esc = mysqli_real_escape_string($conn, $lacak_q);
    $clean_lq = preg_replace('/[^0-9]/', '', $lq_esc);
    $filter_orders_res = mysqli_query($conn, "SELECT * FROM orders WHERE id = '$clean_lq' OR no_hp LIKE '%$lq_esc%' OR kode_pesanan LIKE '%$lq_esc%' ORDER BY id DESC");
} else {
    $filter_orders_res = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC LIMIT 5");
}

$total_item_cart = array_sum($_SESSION['cart']);
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElectroMart - Toko Elektronik Modern & Gadget Terpercaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/theme.js"></script>
    <style>
        .hero-gradient-box {
            background: linear-gradient(135deg, rgba(30, 27, 75, 0.95) 0%, rgba(15, 23, 42, 0.95) 50%, rgba(6, 78, 119, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 26px;
            padding: 3rem 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 45px -15px rgba(79, 70, 229, 0.3);
        }
        .hero-gradient-box::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.25) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .product-card-img {
            height: 210px;
            object-fit: cover;
            width: 100%;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            transition: transform 0.4s ease;
        }
        .product-card:hover .product-card-img {
            transform: scale(1.05);
        }
        .category-grid-item {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.75rem 1.5rem;
            text-align: center;
            text-decoration: none;
            color: var(--text-main);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
        }
        .category-grid-item:hover {
            border-color: #4f46e5;
            transform: translateY(-6px);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.2);
            color: #4f46e5;
        }
        .category-icon-circle {
            width: 65px;
            height: 65px;
            border-radius: 18px;
            background: var(--grad-card-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.9rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .category-grid-item:hover .category-icon-circle {
            background: var(--grad-primary);
            color: white !important;
            transform: scale(1.1);
        }
        .flash-deal-badge {
            background: var(--grad-accent);
            color: white;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.35);
        }
        .subnav-scroll {
            display: flex;
            gap: 0.6rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: none;
        }
        .subnav-scroll::-webkit-scrollbar {
            display: none;
        }
        .terminal-log-box {
            font-family: 'Fira Code', monospace;
            background: #090d16;
            color: #4ade80;
            padding: 1.25rem;
            border-radius: 16px;
            font-size: 0.8rem;
            border: 1px solid #1e293b;
            max-height: 280px;
            overflow-y: auto;
            white-space: pre-wrap;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top navbar-themed py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold fs-4 text-decoration-none" href="index.php">
            <div class="p-2 rounded-3 text-white bg-gradient-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                <i class="bi bi-cpu-fill fs-5"></i>
            </div>
            <span class="gradient-text fw-extrabold" style="letter-spacing: -0.5px;">ElectroMart</span>
        </a>

        <!-- Controls on right for mobile -->
        <div class="d-flex align-items-center gap-2 d-lg-none">
            <button class="theme-toggle-btn" type="button" aria-label="Ganti Tema">
                <i class="bi bi-moon-stars-fill text-primary"></i>
            </button>
            <button class="btn btn-gradient position-relative rounded-pill px-3 py-1" data-bs-toggle="modal" data-bs-target="#modalKeranjang">
                <i class="bi bi-cart3"></i>
                <?php if ($total_item_cart > 0): ?>
                    <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle">
                        <?= $total_item_cart ?>
                    </span>
                <?php endif; ?>
            </button>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navCollapse">
            <!-- Global Search Form (Linear Search Engine) -->
            <form method="GET" class="d-flex mx-lg-auto my-3 my-lg-0" style="max-width: 420px; width: 100%;">
                <input type="hidden" name="menu" value="katalog">
                <input type="hidden" name="sort_criteria" value="<?= htmlspecialchars($sort_criteria) ?>">
                <input type="hidden" name="sort_algo" value="<?= htmlspecialchars($sort_algo) ?>">
                <div class="input-group">
                    <span class="input-group-text border-end-0 rounded-start-pill ps-3 text-muted" style="background: var(--input-bg);">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="cari" class="form-control border-start-0 rounded-end-pill py-2" placeholder="Cari gadget, TV, laptop (Linear Search)..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
            </form>

            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0 justify-content-end">
                <!-- Theme Mode Toggle -->
                <button class="theme-toggle-btn d-none d-lg-flex" type="button" aria-label="Ganti Tema">
                    <i class="bi bi-moon-stars-fill text-primary"></i>
                </button>

                <!-- Cart Button -->
                <button class="btn btn-gradient position-relative rounded-pill px-3 fw-semibold d-none d-lg-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalKeranjang">
                    <i class="bi bi-cart3"></i>
                    <span>Keranjang</span>
                    <?php if ($total_item_cart > 0): ?>
                        <span class="badge rounded-pill bg-danger ms-1">
                            <?= $total_item_cart ?>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- Admin Link -->
                <a href="admin.php" class="btn btn-gradient-outline rounded-pill px-3 fw-semibold">
                    <i class="bi bi-shield-lock me-1"></i> Admin Panel
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4">
    <!-- FLASH MESSAGE -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipe'] ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i><?= $flash['pesan'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- HERO HEADER -->
    <div class="hero-gradient-box mb-4 text-white">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background: rgba(255, 255, 255, 0.12); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.15); font-size: 0.8rem; font-weight: 700;">
                    <span class="p-1 rounded-circle bg-success"></span> RESMI & BERGARANSI 1 TAHUN SE-INDONESIA
                </div>
                <h1 class="display-6 fw-extrabold mb-2">
                    Pusat Perangkat & <span class="gradient-text">Elektronik Canggih</span>
                </h1>
                <p class="opacity-75 mb-0" style="max-width: 600px; font-size: 1.05rem;">
                    Beli langsung, masukkan ke keranjang belanja, atau cek pesanan Anda melalui menu praktis di bawah ini.
                </p>
            </div>
            <div class="col-lg-4 d-none d-lg-block text-end">
                <div class="p-3 d-inline-block rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="d-flex align-items-center gap-3 text-start mb-2">
                        <i class="bi bi-truck fs-3 text-info"></i>
                        <div>
                            <div class="fw-bold small">Gratis Ongkir</div>
                            <div class="small opacity-75" style="font-size: 0.75rem;">Promo seluruh Indonesia</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 text-start">
                        <i class="bi bi-patch-check-fill fs-3 text-warning"></i>
                        <div>
                            <div class="fw-bold small">100% Produk Original</div>
                            <div class="small opacity-75" style="font-size: 0.75rem;">Jaminan uang kembali</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MENU TATA LAYOUT UTAMA (DIBAGI MENJADI BEBERAPA MENU) -->
    <!-- ======================================================== -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold text-uppercase small text-muted mb-0">
                <i class="bi bi-compass me-1"></i> Pilih Menu Tampilan
            </h6>
            <span class="small text-muted d-none d-sm-inline">Klik menu untuk berganti tampilan</span>
        </div>

        <div class="subnav-scroll pb-2">
            <a href="index.php?menu=katalog" class="menu-tab-btn <?= $active_menu === 'katalog' ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i>
                <span>Katalog Produk</span>
                <span class="badge-pill-count"><?= $total_products_found ?></span>
            </a>

            <a href="index.php?menu=promo" class="menu-tab-btn <?= $active_menu === 'promo' ? 'active' : '' ?>">
                <i class="bi bi-lightning-charge-fill text-warning"></i>
                <span>Promo & Flash Sale</span>
                <span class="badge-pill-count">HOT</span>
            </a>

            <a href="index.php?menu=kategori" class="menu-tab-btn <?= $active_menu === 'kategori' ? 'active' : '' ?>">
                <i class="bi bi-collection-fill"></i>
                <span>Jelajah Kategori</span>
                <span class="badge-pill-count"><?= count($kategori_list) ?></span>
            </a>

            <a href="index.php?menu=lacak" class="menu-tab-btn <?= $active_menu === 'lacak' ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i>
                <span>Lacak Pesanan</span>
            </a>

            <a href="index.php?menu=bantuan" class="menu-tab-btn <?= $active_menu === 'bantuan' ? 'active' : '' ?>">
                <i class="bi bi-shield-check"></i>
                <span>Garansi & Bantuan</span>
            </a>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- KONTEN MENU 1: KATALOG PRODUK (DENGAN SORTING & SEARCHING LOG) -->
    <!-- ======================================================== -->
    <?php if ($active_menu === 'katalog'): ?>
        <div class="card-themed p-4 mb-4">
            <!-- HEADER TOOLBAR KATALOG -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h4 class="fw-bold mb-1">
                        <i class="bi bi-box-seam me-2 text-primary"></i>Katalog Elektronik
                    </h4>
                    <p class="text-muted small mb-0">Menampilkan <?= $total_products_found ?> produk berkualitas tinggi.</p>
                </div>

                <!-- Tombol Toggle Log Algoritma (Sorting & Searching) -->
                <button class="btn btn-sm btn-gradient-outline rounded-pill px-3 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#boxLogAlgoritma" aria-expanded="false">
                    <i class="bi bi-terminal me-1 text-success"></i> Lihat Log Algoritma (Sorting & Search)
                    <span class="badge bg-success ms-1"><?= strtoupper($sort_algo) ?></span>
                </button>
            </div>

            <!-- FORM SORTING BARANG & PEMILIHAN ALGORITMA (SESUAI PERMINTAAN DOSEN) -->
            <form method="GET" class="p-3 rounded-4 mb-3 border" style="background: var(--bg-subtle); border-color: var(--border-color) !important;">
                <input type="hidden" name="menu" value="katalog">
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">
                <?php if (!empty($search_query)): ?>
                    <input type="hidden" name="cari" value="<?= htmlspecialchars($search_query) ?>">
                <?php endif; ?>

                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold mb-1 text-muted">
                            <i class="bi bi-arrow-down-up me-1 text-primary"></i>Urutkan Barang Sesuai:
                        </label>
                        <select name="sort_criteria" class="form-select form-select-sm rounded-3">
                            <option value="harga_asc" <?= $sort_criteria === 'harga_asc' ? 'selected' : '' ?>>💰 Harga Termurah</option>
                            <option value="harga_desc" <?= $sort_criteria === 'harga_desc' ? 'selected' : '' ?>>💎 Harga Termahal</option>
                            <option value="nama_asc" <?= $sort_criteria === 'nama_asc' ? 'selected' : '' ?>>🔤 Nama / Abjad (A ke Z)</option>
                            <option value="nama_desc" <?= $sort_criteria === 'nama_desc' ? 'selected' : '' ?>>🔠 Nama / Abjad (Z ke A)</option>
                            <option value="stok_desc" <?= $sort_criteria === 'stok_desc' ? 'selected' : '' ?>>📦 Stok Terbanyak</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1 text-muted">
                            <i class="bi bi-cpu me-1 text-info"></i>Tipe Algoritma Sorting:
                        </label>
                        <select name="sort_algo" class="form-select form-select-sm rounded-3">
                            <option value="bubble" <?= $sort_algo === 'bubble' ? 'selected' : '' ?>>1. Bubble Sort</option>
                            <option value="selection" <?= $sort_algo === 'selection' ? 'selected' : '' ?>>2. Selection Sort</option>
                            <option value="insertion" <?= $sort_algo === 'insertion' ? 'selected' : '' ?>>3. Insertion Sort</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end pt-md-3">
                        <button type="submit" class="btn btn-sm btn-gradient w-100 rounded-3 py-2 fw-bold">
                            <i class="bi bi-funnel-fill me-1"></i> Terapkan Sorting
                        </button>
                    </div>
                </div>
            </form>

            <!-- KOTAK LOG EKSEKUSI SORTING & SEARCHING (COLLAPSIBLE) -->
            <div class="collapse mb-4" id="boxLogAlgoritma">
                <div class="p-3 rounded-4 border" style="background: var(--bg-surface); border-color: rgba(79, 70, 229, 0.3) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="border-color: var(--border-color) !important;">
                        <span class="small fw-bold text-success font-monospace">
                            <i class="bi bi-terminal-fill me-1"></i> Log Eksekusi Algoritma (Sorting & Searching)
                        </span>
                        <div class="small text-muted font-monospace">
                            Komparasi: <strong><?= $sort_output['steps'] ?></strong> | Swap/Shift: <strong><?= $sort_output['swaps'] ?></strong> | Waktu CPU: <strong><?= $sort_exec_time ?> ms</strong>
                        </div>
                    </div>

                    <!-- LOG SEARCHING (JIKA ADA PENCARIAN) -->
                    <?php if (!empty($search_logs)): ?>
                        <div class="mb-2">
                            <span class="badge bg-info text-dark mb-1 font-monospace">LOG SEARCHING (LINEAR SEARCH)</span>
                            <div class="terminal-log-box mb-2" style="max-height: 140px;"><?= implode("\n", $search_logs) ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- LOG SORTING (BUBBLE, SELECTION, ATAU INSERTION) -->
                    <div>
                        <span class="badge bg-primary mb-1 font-monospace">LOG SORTING (<?= strtoupper($sort_algo) ?> SORT)</span>
                        <div class="terminal-log-box"><?= implode("\n", $sorting_logs) ?></div>
                    </div>
                </div>
            </div>

            <!-- Sub Filter Kategori -->
            <div class="subnav-scroll mb-4">
                <a href="index.php?menu=katalog&kategori=Semua&sort_criteria=<?= urlencode($sort_criteria) ?>&sort_algo=<?= urlencode($sort_algo) ?><?= !empty($search_query) ? '&cari=' . urlencode($search_query) : '' ?>" class="badge <?= $kategori_filter === 'Semua' ? 'bg-primary' : 'bg-subtle text-secondary' ?> py-2 px-3 rounded-pill text-decoration-none fw-semibold">
                    Semua (<?= count($category_counts) > 0 ? array_sum($category_counts) : 0 ?>)
                </a>
                <?php foreach ($kategori_list as $kat): 
                    $c = $category_counts[$kat] ?? 0;
                ?>
                    <a href="index.php?menu=katalog&kategori=<?= urlencode($kat) ?>&sort_criteria=<?= urlencode($sort_criteria) ?>&sort_algo=<?= urlencode($sort_algo) ?><?= !empty($search_query) ? '&cari=' . urlencode($search_query) : '' ?>" class="badge <?= $kategori_filter === $kat ? 'bg-primary' : 'bg-subtle text-secondary' ?> py-2 px-3 rounded-pill text-decoration-none fw-semibold">
                        <?= htmlspecialchars($kat) ?> (<?= $c ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- PRODUCT GRID -->
            <div class="row g-4">
                <?php if ($total_products_found == 0): ?>
                    <div class="col-12 py-5 text-center text-muted">
                        <i class="bi bi-search display-2 opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold">Produk Tidak Ditemukan</h5>
                        <p class="small">Silakan coba kata kunci pencarian yang lain atau pilih kategori Semua.</p>
                        <a href="index.php?menu=katalog" class="btn btn-gradient rounded-pill px-4">Tampilkan Semua Produk</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($final_catalog_products as $p): ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <div class="card-themed h-100 d-flex flex-column overflow-hidden product-card">
                                <div class="position-relative overflow-hidden bg-dark">
                                    <span class="position-absolute top-0 start-0 m-3 badge rounded-pill" style="background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2); font-size: 0.72rem;">
                                        <?= htmlspecialchars($p['kategori']) ?>
                                    </span>

                                    <?php if (!empty($p['diskon_badge'])): ?>
                                        <span class="position-absolute top-0 end-0 m-3 flash-deal-badge">
                                            HEMAT <?= $p['diskon_badge'] ?>
                                        </span>
                                    <?php elseif ($p['stok'] > 5): ?>
                                        <span class="position-absolute top-0 end-0 m-3 badge bg-success rounded-pill fw-bold" style="font-size: 0.72rem;">
                                            Stok: <?= $p['stok'] ?>
                                        </span>
                                    <?php elseif ($p['stok'] > 0): ?>
                                        <span class="position-absolute top-0 end-0 m-3 badge bg-warning text-dark rounded-pill fw-bold" style="font-size: 0.72rem;">
                                            Sisa <?= $p['stok'] ?>!
                                        </span>
                                    <?php else: ?>
                                        <span class="position-absolute top-0 end-0 m-3 badge bg-danger rounded-pill fw-bold" style="font-size: 0.72rem;">
                                            Habis
                                        </span>
                                    <?php endif; ?>

                                    <img src="<?= htmlspecialchars($p['gambar']) ?>" class="product-card-img" alt="<?= htmlspecialchars($p['nama_produk']) ?>" onerror="this.src='https://placehold.co/400x300?text=Gambar+Produk'">
                                </div>

                                <div class="p-3 d-flex flex-column flex-grow-1 justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center gap-1 mb-1 small text-warning">
                                            <i class="bi bi-star-fill"></i>
                                            <span class="fw-bold" style="color: var(--text-main);">4.9</span>
                                            <span class="text-muted ms-1" style="font-size: 0.75rem;">(100+ terjual)</span>
                                        </div>
                                        <h6 class="fw-bold mb-1 text-truncate" title="<?= htmlspecialchars($p['nama_produk']) ?>">
                                            <?= htmlspecialchars($p['nama_produk']) ?>
                                        </h6>
                                        <p class="small text-muted text-truncate mb-3" style="font-size: 0.8rem;">
                                            <?= htmlspecialchars($p['deskripsi'] ?? 'Perangkat elektronik kualitas resmi.') ?>
                                        </p>
                                    </div>

                                    <div>
                                        <div class="d-flex justify-content-between align-items-baseline mb-3">
                                            <div>
                                                <?php if (!empty($p['harga_awal'])): ?>
                                                    <div class="small text-decoration-line-through text-muted" style="font-size: 0.75rem;">
                                                        <?= rupiah($p['harga_awal']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="fs-5 fw-extrabold text-primary" style="font-weight: 800;">
                                                    <?= rupiah($p['harga']) ?>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-link p-0 small text-decoration-none text-info fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDetailProd<?= $p['id'] ?>">
                                                Detail <i class="bi bi-info-circle ms-1"></i>
                                            </button>
                                        </div>

                                        <?php if ($p['stok'] > 0): ?>
                                            <form method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="qty" value="1">
                                                <button type="submit" name="action" value="add_cart" class="btn btn-gradient-outline flex-fill py-2 text-center" title="Tambah ke Keranjang">
                                                    <i class="bi bi-cart-plus me-1"></i> +Keranjang
                                                </button>
                                                <button type="submit" name="action" value="buy_now" class="btn btn-gradient flex-fill py-2 text-center" title="Beli Langsung">
                                                    <i class="bi bi-lightning-charge-fill me-1"></i> Beli
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100 rounded-3 py-2 fw-semibold" disabled>
                                                <i class="bi bi-x-circle me-1"></i> Stok Habis
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Detail Produk -->
                            <div class="modal fade" id="modalDetailProd<?= $p['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content rounded-4 border-0">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-bold">Detail Produk Elektronik</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body py-4">
                                            <div class="row g-4 align-items-center">
                                                <div class="col-md-5">
                                                    <img src="<?= htmlspecialchars($p['gambar']) ?>" class="img-fluid rounded-4 shadow-sm" alt="Product" style="max-height: 300px; width: 100%; object-fit: cover;">
                                                </div>
                                                <div class="col-md-7">
                                                    <span class="badge bg-primary bg-opacity-20 text-primary px-3 py-2 rounded-pill fw-bold mb-2">
                                                        <?= htmlspecialchars($p['kategori']) ?>
                                                    </span>
                                                    <h4 class="fw-bold mb-2"><?= htmlspecialchars($p['nama_produk']) ?></h4>
                                                    <div class="fs-3 fw-bold text-primary mb-3"><?= rupiah($p['harga']) ?></div>

                                                    <div class="mb-3 small">
                                                        <strong>Status Ketersediaan:</strong> 
                                                        <?php if ($p['stok'] > 0): ?>
                                                            <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Tersedia (<?= $p['stok'] ?> unit)</span>
                                                        <?php else: ?>
                                                            <span class="text-danger fw-bold"><i class="bi bi-x-circle-fill me-1"></i>Habis</span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="mb-4">
                                                        <h6 class="fw-bold small text-muted text-uppercase">Deskripsi & Spesifikasi:</h6>
                                                        <p class="small" style="white-space: pre-line; color: var(--text-muted);">
                                                            <?= htmlspecialchars($p['deskripsi'] ?? 'Tidak ada keterangan tambahan.') ?>
                                                        </p>
                                                    </div>

                                                    <?php if ($p['stok'] > 0): ?>
                                                        <form method="POST">
                                                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                            <div class="d-flex align-items-center gap-3 mb-3">
                                                                <label class="form-label mb-0 small fw-bold">Jumlah Beli:</label>
                                                                <input type="number" name="qty" value="1" min="1" max="<?= $p['stok'] ?>" class="form-control rounded-3 text-center" style="width: 80px;">
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <button type="submit" name="action" value="add_cart" class="btn btn-gradient-outline flex-fill py-2">
                                                                    <i class="bi bi-cart-plus me-1"></i> Masukkan Keranjang
                                                                </button>
                                                                <button type="submit" name="action" value="buy_now" class="btn btn-gradient flex-fill py-2">
                                                                    <i class="bi bi-lightning-charge-fill me-1"></i> Beli Sekarang
                                                                </button>
                                                            </div>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <!-- ======================================================== -->
    <!-- KONTEN MENU 2: PROMO & FLASH SALE -->
    <!-- ======================================================== -->
    <?php elseif ($active_menu === 'promo'): ?>
        <?php $query_promo = mysqli_query($conn, "SELECT * FROM products ORDER BY harga DESC LIMIT 4"); ?>
        <div class="card-themed p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="flash-deal-badge"><i class="bi bi-lightning-fill me-1"></i>FLASH SALE</span>
                        <h4 class="fw-bold mb-0">Penawaran Eksklusif Hari Ini</h4>
                    </div>
                    <p class="text-muted small mb-0">Dapatkan harga spesial untuk lini produk gadget dan elektronik pilihan teratas.</p>
                </div>
                <div class="p-2 px-3 rounded-pill bg-danger bg-opacity-10 border border-danger border-opacity-25 small text-danger fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history"></i> Berakhir dalam: <span>08:42:15</span>
                </div>
            </div>

            <div class="row g-4">
                <?php while ($promo = mysqli_fetch_assoc($query_promo)): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card-themed h-100 d-flex flex-column overflow-hidden product-card">
                            <div class="position-relative overflow-hidden bg-dark">
                                <span class="position-absolute top-0 start-0 m-3 flash-deal-badge">HEMAT 25%</span>
                                <img src="<?= htmlspecialchars($promo['gambar']) ?>" class="product-card-img" alt="Promo">
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1 justify-content-between">
                                <div>
                                    <span class="badge bg-primary bg-opacity-20 text-primary rounded-pill mb-2" style="font-size: 0.7rem;">
                                        <?= htmlspecialchars($promo['kategori']) ?>
                                    </span>
                                    <h6 class="fw-bold mb-2 text-truncate"><?= htmlspecialchars($promo['nama_produk']) ?></h6>
                                    <div class="small text-decoration-line-through text-muted mb-1"><?= rupiah($promo['harga'] * 1.25) ?></div>
                                    <div class="fs-5 fw-extrabold text-danger mb-3"><?= rupiah($promo['harga']) ?></div>
                                </div>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="product_id" value="<?= $promo['id'] ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <button type="submit" name="action" value="add_cart" class="btn btn-gradient-outline flex-fill py-2 btn-sm">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                    <button type="submit" name="action" value="buy_now" class="btn btn-gradient flex-fill py-2 btn-sm">
                                        <i class="bi bi-lightning-charge-fill me-1"></i> Klaim & Beli
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

    <!-- ======================================================== -->
    <!-- KONTEN MENU 3: JELAJAH KATEGORI -->
    <!-- ======================================================== -->
    <?php elseif ($active_menu === 'kategori'): ?>
        <div class="card-themed p-4 mb-4">
            <div class="mb-4">
                <h4 class="fw-bold mb-1"><i class="bi bi-collection-fill me-2 text-primary"></i>Semua Kategori Elektronik</h4>
                <p class="text-muted small mb-0">Jelajahi beragam kategori peralatan digital, audio visual, dan komponen teknologi.</p>
            </div>

            <div class="row g-4">
                <?php 
                $icon_map = [
                    'Smartphone & Gadget' => 'bi-phone',
                    'Laptop & PC' => 'bi-laptop',
                    'TV & Audio' => 'bi-tv',
                    'Konsol & Gaming' => 'bi-controller',
                    'Kamera & Drone' => 'bi-camera-video',
                    'Elektronik Rumah' => 'bi-house-gear',
                    'Aksesoris PC' => 'bi-keyboard',
                    'Komponen IoT' => 'bi-motherboard'
                ];
                foreach ($kategori_list as $kat_name): 
                    $icon_class = $icon_map[$kat_name] ?? 'bi-cpu';
                    $count = $category_counts[$kat_name] ?? 0;
                ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <a href="index.php?menu=katalog&kategori=<?= urlencode($kat_name) ?>" class="category-grid-item">
                            <div class="category-icon-circle text-primary">
                                <i class="bi <?= $icon_class ?>"></i>
                            </div>
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($kat_name) ?></h6>
                            <span class="small text-muted"><?= $count ?> Produk Tersedia</span>
                            <div class="mt-3 btn btn-sm btn-gradient-outline rounded-pill px-3">
                                Lihat Produk <i class="bi bi-arrow-right ms-1"></i>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <!-- ======================================================== -->
    <!-- KONTEN MENU 4: LACAK PESANAN -->
    <!-- ======================================================== -->
    <?php elseif ($active_menu === 'lacak'): ?>
        <div class="card-themed p-4 mb-4">
            <div class="row align-items-center mb-4">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <h4 class="fw-bold mb-1"><i class="bi bi-receipt me-2 text-primary"></i>Lacak Status Pesanan</h4>
                    <p class="text-muted small mb-0">Masukkan No. WhatsApp atau ID Pesanan untuk memeriksa status konfirmasi dan resi pengiriman.</p>
                </div>
                <div class="col-lg-6">
                    <form method="GET" class="d-flex gap-2">
                        <input type="hidden" name="menu" value="lacak">
                        <input type="text" name="q" class="form-control rounded-pill px-3" placeholder="Contoh: 0812xxxx atau 1..." value="<?= htmlspecialchars($lacak_q) ?>">
                        <button type="submit" class="btn btn-gradient rounded-pill px-4">Lacak</button>
                        <?php if (!empty($lacak_q)): ?>
                            <a href="index.php?menu=lacak" class="btn btn-outline-secondary rounded-pill">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if (!$filter_orders_res || mysqli_num_rows($filter_orders_res) === 0): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-box2 display-2 opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold">Tidak Ada Pesanan Ditemukan</h5>
                    <p class="small">Periksa kembali nomor WhatsApp atau ID pesanan yang Anda masukkan.</p>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php while ($ord = mysqli_fetch_assoc($filter_orders_res)): 
                        $oid = $ord['id'];
                        $kode_p = !empty($ord['kode_pesanan']) ? $ord['kode_pesanan'] : '#ORD-' . str_pad($ord['id'], 5, '0', STR_PAD_LEFT);
                        $items_res = mysqli_query($conn, "SELECT oi.*, p.nama_produk FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $oid");
                    ?>
                        <div class="p-3 rounded-4 border" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                            <div class="d-flex flex-wrap justify-content-between align-items-center pb-2 border-bottom mb-2 gap-2">
                                <div>
                                    <span class="fw-bold text-primary fs-6"><?= htmlspecialchars($kode_p) ?></span>
                                    <small class="text-muted ms-2"><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($ord['tanggal'])) ?></small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?= get_status_badge($ord['status']) ?>
                                    <a href="invoice.php?id=<?= $ord['id'] ?>" target="_blank" class="btn btn-sm btn-gradient-outline rounded-pill px-3">
                                        <i class="bi bi-printer me-1"></i> Cetak Invoice
                                    </a>
                                </div>
                            </div>
                            <div class="row align-items-center">
                                <div class="col-md-8 small">
                                    <strong>Penerima:</strong> <?= htmlspecialchars($ord['nama_pembeli']) ?> (<?= htmlspecialchars($ord['no_hp']) ?>)<br>
                                    <strong>Barang:</strong> 
                                    <?php 
                                    $item_names = [];
                                    while ($it = mysqli_fetch_assoc($items_res)) {
                                        $item_names[] = htmlspecialchars($it['nama_produk'] ?? 'Item') . " (x{$it['qty']})";
                                    }
                                    echo implode(', ', $item_names);
                                    ?><br>
                                    <?php if (!empty($ord['no_resi'])): ?>
                                        <span class="text-info fw-bold"><i class="bi bi-truck me-1"></i>No Resi: <?= htmlspecialchars($ord['no_resi']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                                    <div class="small text-muted">Total Belanja:</div>
                                    <div class="fw-bold fs-5 text-primary"><?= rupiah($ord['total_harga']) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

    <!-- ======================================================== -->
    <!-- KONTEN MENU 5: GARANSI & BANTUAN -->
    <!-- ======================================================== -->
    <?php elseif ($active_menu === 'bantuan'): ?>
        <div class="card-themed p-4 mb-4">
            <div class="mb-4">
                <h4 class="fw-bold mb-1"><i class="bi bi-shield-check me-2 text-primary"></i>Pusat Bantuan & Ketentuan Garansi</h4>
                <p class="text-muted small mb-0">Informasi resmi seputar perlindungan garansi, pengiriman, dan layanan pelanggan.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="p-4 rounded-4 h-100 border" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                        <div class="fs-2 text-primary mb-2"><i class="bi bi-patch-check"></i></div>
                        <h6 class="fw-bold mb-2">Garansi Resmi 1 Tahun</h6>
                        <p class="small text-muted mb-0">
                            Semua produk elektronik di ElectroMart mendapatkan perlindungan garansi resmi 12 bulan untuk perbaikan atau penggantian jika ada kerusakan produksi.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="p-4 rounded-4 h-100 border" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                        <div class="fs-2 text-success mb-2"><i class="bi bi-box-seam"></i></div>
                        <h6 class="fw-bold mb-2">Pengemasan Ekstra Aman</h6>
                        <p class="small text-muted mb-0">
                            Setiap paket dibungkus dengan *bubble wrap* berlapis ganda dan pelindung kardus tebal untuk menjamin keselamatan perangkat selama perjalanan.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="p-4 rounded-4 h-100 border" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                        <div class="fs-2 text-info mb-2"><i class="bi bi-headset"></i></div>
                        <h6 class="fw-bold mb-2">Bantuan CS WhatsApp</h6>
                        <p class="small text-muted mb-3">
                            Butuh bantuan pemesanan atau konsultasi spesifikasi teknis alat elektronik? Hubungi tim support kami.
                        </p>
                        <a href="https://wa.me/6281234567890?text=Halo%20Admin%20ElectroMart%2C%20saya%20butuh%20bantuan" target="_blank" class="btn btn-sm btn-gradient rounded-pill px-3">
                            <i class="bi bi-whatsapp me-1"></i> Chat WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ======================================================== -->
<!-- MODAL KERANJANG & CHECKOUT TERPADU -->
<!-- ======================================================== -->
<div class="modal fade" id="modalKeranjang" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom pb-3">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-cart3 me-2 text-primary"></i>Keranjang Belanja & Checkout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <?php if (empty($_SESSION['cart'])): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x display-1 text-muted opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold">Keranjang Masih Kosong</h5>
                        <p class="text-muted small">Pilih alat elektronik yang Anda inginkan lalu klik "+ Keranjang" atau "Beli".</p>
                        <button type="button" class="btn btn-gradient rounded-pill px-4 mt-2" data-bs-dismiss="modal">
                            Mulai Belanja
                        </button>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="table-responsive mb-3">
                            <table class="table align-middle">
                                <thead>
                                    <tr class="small text-muted">
                                        <th>Produk</th>
                                        <th class="text-end">Harga</th>
                                        <th width="120" class="text-center">Qty</th>
                                        <th class="text-end">Subtotal</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $grand_total = 0;
                                    foreach ($_SESSION['cart'] as $id_prod => $qty):
                                        $res = mysqli_query($conn, "SELECT * FROM products WHERE id = $id_prod");
                                        if ($item = mysqli_fetch_assoc($res)):
                                            $sub = $item['harga'] * $qty;
                                            $grand_total += $sub;
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= htmlspecialchars($item['gambar']) ?>" class="rounded-2" width="45" height="45" style="object-fit: cover;" onerror="this.src='https://placehold.co/45x45?text=No+Img'">
                                                    <div>
                                                        <div class="fw-semibold small"><?= htmlspecialchars($item['nama_produk']) ?></div>
                                                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.7rem;"><?= htmlspecialchars($item['kategori']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end small"><?= rupiah($item['harga']) ?></td>
                                            <td>
                                                <input type="number" name="qty[<?= $id_prod ?>]" value="<?= $qty ?>" min="1" max="<?= $item['stok'] ?>" class="form-control form-control-sm text-center rounded-3">
                                            </td>
                                            <td class="text-end fw-bold"><?= rupiah($sub) ?></td>
                                            <td class="text-end">
                                                <a href="index.php?hapus_cart=<?= $id_prod ?>" class="btn btn-sm btn-outline-danger rounded-circle p-1" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                            <div class="d-flex gap-2">
                                <button type="submit" name="update_cart" class="btn btn-sm btn-gradient-outline rounded-pill px-3">
                                    <i class="bi bi-arrow-repeat me-1"></i> Update Jumlah
                                </button>
                                <a href="index.php?kosongkan_cart=1" onclick="return confirm('Kosongkan semua item di keranjang?')" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                    Kosongkan
                                </a>
                            </div>
                            <h5 class="fw-bold mb-0">Total: <span class="gradient-text"><?= rupiah($grand_total) ?></span></h5>
                        </div>
                    </form>

                    <hr class="my-4" style="border-color: var(--border-color);">

                    <!-- FORM DATA CHECKOUT PEMBELI -->
                    <div id="checkoutForm">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-truck me-2 text-primary"></i>Informasi & Alamat Pengiriman</h6>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                <i class="bi bi-gift-fill me-1"></i> Gratis Ongkir Aktif
                            </span>
                        </div>

                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nama Lengkap Pembeli <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_pembeli" class="form-control rounded-3" placeholder="Contoh: Budi Santoso" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">No. WhatsApp / HP Aktif <span class="text-danger">*</span></label>
                                    <input type="tel" name="no_hp" class="form-control rounded-3" placeholder="Contoh: 081234567890" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Alamat Lengkap Pengiriman <span class="text-danger">*</span></label>
                                    <textarea name="alamat" class="form-control rounded-3" rows="2" placeholder="Nama Jalan, No. Rumah, RT/RW, Kelurahan, Kecamatan, Kota & Kode Pos..." required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                                    <select name="metode_pembayaran" class="form-select rounded-3" required>
                                        <option value="Transfer Bank BCA">🏦 Transfer Bank BCA</option>
                                        <option value="Transfer Bank Mandiri">🏦 Transfer Bank Mandiri</option>
                                        <option value="Transfer Bank BRI">🏦 Transfer Bank BRI</option>
                                        <option value="QRIS / E-Wallet (GoPay, OVO, Dana)">📱 QRIS / E-Wallet (GoPay, OVO, Dana)</option>
                                        <option value="COD (Bayar di Tempat)">💵 COD (Bayar di Tempat)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Catatan Pesanan (Opsional)</label>
                                    <input type="text" name="catatan" class="form-control rounded-3" placeholder="Contoh: Titip ke satpam / bubble wrap tebal">
                                </div>
                            </div>

                            <div class="p-3 rounded-3 mt-4 small border" style="background: var(--bg-subtle); border-color: var(--border-color) !important;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Total Pembelian:</span>
                                    <span class="fw-bold"><?= rupiah($grand_total) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Ongkos Kirim:</span>
                                    <span class="text-success fw-bold">Rp 0 (Bebas Ongkir)</span>
                                </div>
                                <hr class="my-2" style="border-color: var(--border-color);">
                                <div class="d-flex justify-content-between fs-6">
                                    <span class="fw-bold">Total Tagihan:</span>
                                    <span class="fw-bold text-primary fs-5"><?= rupiah($grand_total) ?></span>
                                </div>
                            </div>

                            <div class="mt-4 text-end d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" name="proses_checkout" class="btn btn-gradient rounded-pill px-4 fw-bold shadow-sm">
                                    <i class="bi bi-bag-check-fill me-2"></i> Buat Pesanan Sekarang
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="py-5 mt-5 border-top" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="p-2 rounded-3 text-white bg-gradient-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                    <span class="gradient-text fw-bold fs-5">ElectroMart</span>
                </div>
                <p class="text-muted small">
                    Penyedia perangkat keras elektronik resmi, gadget terkini, perlengkapan komputer dan komponen cerdas dengan jaminan garansi 1 tahun.
                </p>
            </div>
            <div class="col-sm-6 col-lg-4">
                <h6 class="fw-bold mb-3">Menu Cepat</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 small">
                    <li><a href="index.php?menu=katalog" class="text-muted text-decoration-none hover-underline">🛍️ Katalog Belanja</a></li>
                    <li><a href="index.php?menu=promo" class="text-muted text-decoration-none hover-underline">⚡ Penawaran Flash Sale</a></li>
                    <li><a href="index.php?menu=kategori" class="text-muted text-decoration-none hover-underline">📱 Jelajah 8 Kategori</a></li>
                    <li><a href="index.php?menu=lacak" class="text-muted text-decoration-none hover-underline">📦 Cek Status Pesanan</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-lg-4">
                <h6 class="fw-bold mb-3">Bantuan & Hubungi Kami</h6>
                <p class="small text-muted mb-2">Punya pertanyaan seputar produk atau pesanan?</p>
                <a href="https://wa.me/6281234567890?text=Halo%20Admin%20ElectroMart" target="_blank" class="btn btn-sm btn-gradient-outline rounded-pill px-3">
                    <i class="bi bi-whatsapp me-1 text-success"></i> WhatsApp: +62 812-3456-7890
                </a>
            </div>
        </div>
        <hr class="my-4" style="border-color: var(--border-color);">
        <div class="d-flex flex-wrap justify-content-between align-items-center small text-muted">
            <div>&copy; <?= date('Y') ?> ElectroMart. Toko Elektronik Berbasis PHP Native & MySQL.</div>
            <div class="d-flex gap-3">
                <a href="admin.php" class="text-muted text-decoration-none"><i class="bi bi-shield-lock me-1"></i>Admin Dashboard</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php if (isset($_GET['open_cart']) || isset($_GET['open_checkout']) || isset($_GET['checkout'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var modalEl = document.getElementById('modalKeranjang');
        var modalObj = new bootstrap.Modal(modalEl);
        modalObj.show();
    });
</script>
<?php endif; ?>

</body>
</html>