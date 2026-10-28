<?php
require_once 'koneksi.php';

// Ambil semua produk dari database untuk bahan demo struktur data dan algoritma
$raw_products = [];
$res = mysqli_query($conn, "SELECT id, nama_produk, kategori, harga, stok, gambar FROM products ORDER BY id ASC");
while ($r = mysqli_fetch_assoc($res)) {
    $raw_products[] = $r;
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

    public function __construct(int $id, string $nama, string $kategori, int $harga, int $stok) {
        $this->id = $id;
        $this->nama_produk = $nama;
        $this->kategori = $kategori;
        $this->harga = $harga;
        $this->stok = $stok;
    }

    public function getFormattedHarga(): string {
        return "Rp " . number_format($this->harga, 0, ',', '.');
    }
}

// ========================================================
// 2. STRUKTUR DATA: POINTER (Passing by Reference `&` di PHP)
// ========================================================
/**
 * Contoh Pointer/Reference:
 * Mengubah harga barang langsung pada alamat memori variabel aslinya tanpa return duplicate.
 */
function terapkanDiskonPointer(array &$itemProduk, int $persenDiskon, array &$logsPointer): void {
    $hargaLama = $itemProduk['harga'];
    $potongan = (int)($hargaLama * ($persenDiskon / 100));
    // Manipulasi langsung pointer memori
    $itemProduk['harga'] = $hargaLama - $potongan;
    $itemProduk['diskon_diterapkan'] = $persenDiskon . '%';

    $logsPointer[] = "[POINTER REF] Alamat variabel '{$itemProduk['nama_produk']}' dimanipulasi langsung di memori: "
                   . "Rp " . number_format($hargaLama, 0, ',', '.') . " -> Rp " . number_format($itemProduk['harga'], 0, ',', '.') . " (Diskon $persenDiskon%)";
}

// ========================================================
// 3. ALGORITMA SORTING (3 TIPE: BUBBLE, SELECTION, INSERTION)
// ========================================================

// A. Bubble Sort
function runBubbleSort(array $arr, string $key, string $order = 'asc'): array {
    $logs = [];
    $data = $arr;
    $n = count($data);
    $step = 1;
    $totalSwaps = 0;

    $logs[] = "⚡ [MULAI BUBBLE SORT] Mengurutkan $n data berdasarkan '$key' secara " . strtoupper($order);

    for ($i = 0; $i < $n - 1; $i++) {
        $swapped = false;
        $logs[] = "--- PASS KE-" . ($i + 1) . " ---";
        for ($j = 0; $j < $n - $i - 1; $j++) {
            $v1 = $data[$j][$key];
            $v2 = $data[$j + 1][$key];
            $needSwap = ($order === 'asc') ? ($v1 > $v2) : ($v1 < $v2);

            if ($needSwap) {
                $temp = $data[$j];
                $data[$j] = $data[$j + 1];
                $data[$j + 1] = $temp;
                $swapped = true;
                $totalSwaps++;
                $logs[] = "  Step #$step [TUKAR / SWAP]: '{$data[$j + 1]['nama_produk']}' ($v1) ditukar dengan '{$data[$j]['nama_produk']}' ($v2)";
            } else {
                $logs[] = "  Step #$step [OK]: '{$data[$j]['nama_produk']}' ($v1) sudah sesuai posisi terhadap '{$data[$j + 1]['nama_produk']}' ($v2)";
            }
            $step++;
        }
        if (!$swapped) {
            $logs[] = ">> Pass ke-" . ($i + 1) . ": Tidak terjadi swap. Array sudah terurut optimal (Early Exit)!";
            break;
        }
    }

    $logs[] = "✅ [SELESAI BUBBLE SORT] Total perulangan/langkah: " . ($step - 1) . " | Total swap: $totalSwaps";
    return ['data' => $data, 'logs' => $logs, 'steps' => $step - 1, 'swaps' => $totalSwaps];
}

// B. Selection Sort
function runSelectionSort(array $arr, string $key, string $order = 'asc'): array {
    $logs = [];
    $data = $arr;
    $n = count($data);
    $step = 1;
    $totalSwaps = 0;

    $logs[] = "⚡ [MULAI SELECTION SORT] Mengurutkan $n data berdasarkan '$key' secara " . strtoupper($order);

    for ($i = 0; $i < $n - 1; $i++) {
        $targetIdx = $i;
        $logs[] = "--- PASS KE-" . ($i + 1) . ": Mencari elemen " . ($order === 'asc' ? 'terkecil' : 'terbesar') . " mulai dari indeks $i ---";
        for ($j = $i + 1; $j < $n; $j++) {
            $condition = ($order === 'asc')
                ? ($data[$j][$key] < $data[$targetIdx][$key])
                : ($data[$j][$key] > $data[$targetIdx][$key]);

            if ($condition) {
                $targetIdx = $j;
                $logs[] = "  Step #$step [KANDIDAT BARU]: Indeks $j '{$data[$j]['nama_produk']}' bernilai {$data[$j][$key]}";
            }
            $step++;
        }

        if ($targetIdx !== $i) {
            $temp = $data[$i];
            $data[$i] = $data[$targetIdx];
            $data[$targetIdx] = $temp;
            $totalSwaps++;
            $logs[] = "  [TUKAR ELEMEN]: Menukar indeks $i ('{$temp['nama_produk']}') dengan indeks $targetIdx ('{$data[$i]['nama_produk']}')";
        } else {
            $logs[] = "  [TETAP]: Elemen indeks $i sudah pada posisi yang benar.";
        }
    }

    $logs[] = "✅ [SELESAI SELECTION SORT] Total komparasi: " . ($step - 1) . " | Total swap: $totalSwaps";
    return ['data' => $data, 'logs' => $logs, 'steps' => $step - 1, 'swaps' => $totalSwaps];
}

// C. Insertion Sort
function runInsertionSort(array $arr, string $key, string $order = 'asc'): array {
    $logs = [];
    $data = $arr;
    $n = count($data);
    $step = 1;
    $totalShifts = 0;

    $logs[] = "⚡ [MULAI INSERTION SORT] Mengurutkan $n data berdasarkan '$key' secara " . strtoupper($order);

    for ($i = 1; $i < $n; $i++) {
        $current = $data[$i];
        $j = $i - 1;
        $logs[] = "--- PASS KE-$i: Sisipkan '{$current['nama_produk']}' ({$current[$key]}) ke sub-array terurut ---";

        while ($j >= 0 && (($order === 'asc') ? ($data[$j][$key] > $current[$key]) : ($data[$j][$key] < $current[$key]))) {
            $logs[] = "  Step #$step [GESER]: Menggeser '{$data[$j]['nama_produk']}' ({$data[$j][$key]}) dari indeks $j ke " . ($j + 1);
            $data[$j + 1] = $data[$j];
            $j--;
            $step++;
            $totalShifts++;
        }
        $data[$j + 1] = $current;
        $logs[] = "  [PENEMPATAN]: '{$current['nama_produk']}' disisipkan di indeks " . ($j + 1);
    }

    $logs[] = "✅ [SELESAI INSERTION SORT] Total perbandingan: " . ($step - 1) . " | Total pergeseran: $totalShifts";
    return ['data' => $data, 'logs' => $logs, 'steps' => $step - 1, 'swaps' => $totalShifts];
}

// ========================================================
// 4. ALGORITMA SEARCHING (LINEAR SEARCH & BINARY SEARCH)
// ========================================================

// A. Linear Search
function runLinearSearch(array $arr, string $keyword): array {
    $logs = [];
    $results = [];
    $comparisons = 0;
    $n = count($arr);

    $logs[] = "🔍 [MULAI LINEAR SEARCH] Mencari '$keyword' pada total $n produk satu per satu dari awal sampai akhir...";

    foreach ($arr as $idx => $item) {
        $comparisons++;
        $nama = $item['nama_produk'];
        $kat  = $item['kategori'];
        $isMatch = (stripos($nama, $keyword) !== false) || (stripos($kat, $keyword) !== false);

        if ($isMatch) {
            $logs[] = "  Langkah #$comparisons [INDEX $idx] Memeriksa '$nama' -> COCOK / MATCH! Ditemukan di indeks $idx";
            $results[] = $item;
        } else {
            $logs[] = "  Langkah #$comparisons [INDEX $idx] Memeriksa '$nama' -> Tidak cocok, lanjut ke indeks berikutnya...";
        }
    }

    $logs[] = "✅ [SELESAI LINEAR SEARCH] Diperiksa $comparisons kali. Ditemukan " . count($results) . " produk cocok.";
    return ['results' => $results, 'logs' => $logs, 'comparisons' => $comparisons];
}

// B. Binary Search (Berdasarkan Harga Produk pada data terurut)
function runBinarySearch(array $arr, int $targetPrice): array {
    $logs = [];
    // Binary search wajib diurutkan terlebih dahulu
    usort($arr, fn($a, $b) => $a['harga'] <=> $b['harga']);

    $low = 0;
    $high = count($arr) - 1;
    $found = null;
    $comparisons = 0;

    $logs[] = "🔍 [MULAI BINARY SEARCH] Mencari produk dengan harga persis: Rp " . number_format($targetPrice, 0, ',', '.');
    $logs[] = "ℹ️ Prasyarat terpenuhi: Dataset telah diurutkan berdasarkan harga secara Ascending.";

    while ($low <= $high) {
        $comparisons++;
        $mid = (int)floor(($low + $high) / 2);
        $midPrice = $arr[$mid]['harga'];
        $midName = $arr[$mid]['nama_produk'];

        $logs[] = "  Langkah #$comparisons: Interval pencarian low=$low, high=$high. Titik tengah mid=$mid -> '$midName' (Rp " . number_format($midPrice, 0, ',', '.') . ")";

        if ($midPrice === $targetPrice) {
            $logs[] = "  🎉 [MATCH] Target harga Rp " . number_format($targetPrice, 0, ',', '.') . " DITEMUKAN pada indeks $mid ('$midName')!";
            $found = $arr[$mid];
            break;
        } elseif ($midPrice < $targetPrice) {
            $logs[] = "  >> Nilai tengah (Rp " . number_format($midPrice, 0, ',', '.') . ") < Target (Rp " . number_format($targetPrice, 0, ',', '.') . "). Buang separuh kiri, geser low = " . ($mid + 1);
            $low = $mid + 1;
        } else {
            $logs[] = "  >> Nilai tengah (Rp " . number_format($midPrice, 0, ',', '.') . ") > Target (Rp " . number_format($targetPrice, 0, ',', '.') . "). Buang separuh kanan, geser high = " . ($mid - 1);
            $high = $mid - 1;
        }
    }

    if (!$found) {
        $logs[] = "❌ [TIDAK DITEMUKAN] Tidak ada produk dengan harga persis Rp " . number_format($targetPrice, 0, ',', '.') . " setelah $comparisons langkah.";
    }

    $logs[] = "✅ [SELESAI BINARY SEARCH] Kompleksitas O(log N) diselesaikan hanya dalam $comparisons perbandingan!";
    return ['result' => $found, 'logs' => $logs, 'comparisons' => $comparisons, 'sorted_data' => $arr];
}

// ========================================================
// CONTROLLER: PROSES FORM SORTING & SEARCHING
// ========================================================
$active_tab = $_GET['tab'] ?? 'sorting';

// Controller Sorting
$sort_algo = $_POST['sort_algo'] ?? 'bubble';
$sort_by   = $_POST['sort_by'] ?? 'harga';
$sort_dir  = $_POST['sort_dir'] ?? 'asc';
$sort_result = null;

if (isset($_POST['run_sort'])) {
    $active_tab = 'sorting';
    $startTime = microtime(true);
    if ($sort_algo === 'bubble') {
        $sort_result = runBubbleSort($raw_products, $sort_by, $sort_dir);
    } elseif ($sort_algo === 'selection') {
        $sort_result = runSelectionSort($raw_products, $sort_by, $sort_dir);
    } elseif ($sort_algo === 'insertion') {
        $sort_result = runInsertionSort($raw_products, $sort_by, $sort_dir);
    }
    $sort_exec_time = round((microtime(true) - $startTime) * 1000, 3);
}

// Controller Searching
$search_method = $_POST['search_method'] ?? 'linear';
$search_keyword = trim($_POST['search_keyword'] ?? '');
$search_price   = (int)($_POST['search_price'] ?? 0);
$search_result = null;

if (isset($_POST['run_search'])) {
    $active_tab = 'searching';
    $startTime = microtime(true);
    if ($search_method === 'linear') {
        $search_result = runLinearSearch($raw_products, $search_keyword);
    } elseif ($search_method === 'binary') {
        $search_result = runBinarySearch($raw_products, $search_price);
    }
    $search_exec_time = round((microtime(true) - $startTime) * 1000, 3);
}

// Controller Demo Pointer
$demo_pointer_logs = [];
$demo_pointer_data = array_slice($raw_products, 0, 3); // Ambil 3 sampel
if (isset($_POST['run_pointer_demo'])) {
    $active_tab = 'materi';
    $diskon_input = (int)($_POST['diskon_input'] ?? 15);
    foreach ($demo_pointer_data as &$itemRef) {
        terapkanDiskonPointer($itemRef, $diskon_input, $demo_pointer_logs);
    }
    unset($itemRef); // Break reference
}

$total_item_cart = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Struktur Data & Algoritma - ElectroMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/theme.js"></script>
    <style>
        .code-block {
            font-family: 'Fira Code', monospace;
            background: #0b0f19;
            color: #38bdf8;
            padding: 1.25rem;
            border-radius: 16px;
            font-size: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow-x: auto;
        }
        .terminal-log {
            font-family: 'Fira Code', monospace;
            background: #090d16;
            color: #4ade80;
            padding: 1.5rem;
            border-radius: 18px;
            font-size: 0.82rem;
            border: 1px solid #1e293b;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);
        }
        .terminal-header {
            background: #1e293b;
            padding: 0.6rem 1rem;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #334155;
            border-bottom: none;
        }
        .circle-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .algo-tab-btn {
            padding: 0.85rem 1.6rem;
            border-radius: 16px;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-muted);
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
        }
        .algo-tab-btn:hover {
            color: #4f46e5;
            border-color: #4f46e5;
            transform: translateY(-2px);
        }
        .algo-tab-btn.active {
            background: var(--grad-primary);
            color: #ffffff !important;
            border-color: transparent;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.35);
        }
        .card-preview {
            border-radius: 16px;
            border: 1px solid var(--border-color);
            background: var(--bg-surface);
            padding: 1rem;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top navbar-themed py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold fs-4 text-decoration-none" href="index.php">
            <div class="p-2 rounded-3 text-white bg-gradient-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-cpu-fill fs-5"></i>
            </div>
            <span class="gradient-text fw-extrabold" style="letter-spacing: -0.5px;">ElectroMart</span>
        </a>

        <div class="d-flex align-items-center gap-2 ms-auto">
            <a href="index.php" class="btn btn-gradient-outline rounded-pill btn-sm px-3 fw-semibold">
                <i class="bi bi-shop me-1"></i> Toko Elektronik
            </a>
            <button class="theme-toggle-btn" type="button" aria-label="Ganti Tema">
                <i class="bi bi-moon-stars-fill text-primary"></i>
            </button>
            <a href="admin.php" class="btn btn-gradient-outline rounded-pill btn-sm px-3 fw-semibold">
                <i class="bi bi-shield-lock me-1"></i> Admin
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <!-- HERO HEADER LAB -->
    <div class="p-4 p-lg-5 rounded-4 mb-4 text-white" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 50%, #064e3b 100%); border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background: rgba(255,255,255,0.1); font-size: 0.8rem; font-weight: 700;">
            <i class="bi bi-mortarboard-fill text-warning"></i> MODUL PRAKTIKUM & TUGAS STRUKTUR DATA & ALGORITMA
        </div>
        <h2 class="fw-extrabold mb-2">
            Implementasi <span class="gradient-text">Array, Pointer, Struct</span>, Sorting & Searching
        </h2>
        <p class="opacity-75 mb-0" style="max-width: 750px;">
            Halaman laboratorium interaktif yang mendemonstrasikan struktur data dasar serta simulasi 3 tipe algoritma sorting (*Bubble, Selection, Insertion*) dan 2 tipe algoritma searching (*Linear & Binary Search*) dengan visualisasi log langkah demi langkah.
        </p>
    </div>

    <!-- SUB-NAV TABS MENU -->
    <div class="d-flex gap-2 overflow-x-auto pb-3 mb-4">
        <a href="algoritma.php?tab=sorting" class="algo-tab-btn <?= $active_tab === 'sorting' ? 'active' : '' ?>">
            <i class="bi bi-sort-numeric-down"></i>
            <span>Simulasi 3 Tipe Sorting & Log</span>
        </a>

        <a href="algoritma.php?tab=searching" class="algo-tab-btn <?= $active_tab === 'searching' ? 'active' : '' ?>">
            <i class="bi bi-search"></i>
            <span>Simulasi Searching & Log</span>
        </a>

        <a href="algoritma.php?tab=materi" class="algo-tab-btn <?= $active_tab === 'materi' ? 'active' : '' ?>">
            <i class="bi bi-journal-code"></i>
            <span>Materi: Array, Pointer & Struct</span>
        </a>
    </div>

    <!-- ======================================================== -->
    <!-- TAB 1: SIMULASI SORTING (MINIMAL 3 TIPE SORTING + LOG) -->
    <!-- ======================================================== -->
    <?php if ($active_tab === 'sorting'): ?>
        <div class="card-themed p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h4 class="fw-bold mb-1">
                        <i class="bi bi-sort-numeric-down-alt me-2 text-primary"></i>Simulasi Algoritma Sorting
                    </h4>
                    <p class="text-muted small mb-0">Uji coba perbandingan Bubble Sort, Selection Sort, dan Insertion Sort dengan dataset alat elektronik.</p>
                </div>
                <span class="badge bg-primary bg-opacity-15 text-primary px-3 py-2 rounded-pill fw-bold">
                    Total Dataset: <?= count($raw_products) ?> Produk
                </span>
            </div>

            <!-- FORM KONTROL SORTING -->
            <form method="POST" class="p-4 rounded-4 mb-4 border" style="background: var(--bg-subtle); border-color: var(--border-color) !important;">
                <input type="hidden" name="run_sort" value="1">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Pilih Algoritma Sorting:</label>
                        <select name="sort_algo" class="form-select rounded-3">
                            <option value="bubble" <?= $sort_algo === 'bubble' ? 'selected' : '' ?>>1. Bubble Sort (Gelembung Tukar)</option>
                            <option value="selection" <?= $sort_algo === 'selection' ? 'selected' : '' ?>>2. Selection Sort (Pencarian Nilai Ekstrem)</option>
                            <option value="insertion" <?= $sort_algo === 'insertion' ? 'selected' : '' ?>>3. Insertion Sort (Penyisipan Kartu)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Kriteria Kolom Pengurutan:</label>
                        <select name="sort_by" class="form-select rounded-3">
                            <option value="harga" <?= $sort_by === 'harga' ? 'selected' : '' ?>>Harga Produk (Rp)</option>
                            <option value="stok" <?= $sort_by === 'stok' ? 'selected' : '' ?>>Jumlah Stok</option>
                            <option value="nama_produk" <?= $sort_by === 'nama_produk' ? 'selected' : '' ?>>Nama Produk Elektronik</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Arah Pengurutan:</label>
                        <select name="sort_dir" class="form-select rounded-3">
                            <option value="asc" <?= $sort_dir === 'asc' ? 'selected' : '' ?>>Ascending (Kecil ke Besar / A-Z)</option>
                            <option value="desc" <?= $sort_dir === 'desc' ? 'selected' : '' ?>>Descending (Besar ke Kecil / Z-A)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-gradient w-100 rounded-3 py-2 fw-bold">
                            <i class="bi bi-play-fill me-1"></i> Jalankan
                        </button>
                    </div>
                </div>
            </form>

            <?php if ($sort_result): ?>
                <!-- STATISTIK EKSEKUSI -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 border text-center" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                            <small class="text-muted text-uppercase fw-bold">Algoritma Dipilih</small>
                            <h5 class="fw-bold mt-1 text-primary"><?= strtoupper($sort_algo) ?> SORT</h5>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 border text-center" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                            <small class="text-muted text-uppercase fw-bold">Total Langkah / Perbandingan</small>
                            <h5 class="fw-bold mt-1 text-warning"><?= $sort_result['steps'] ?> Komparasi (<?= $sort_result['swaps'] ?> Operasi)</h5>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 border text-center" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                            <small class="text-muted text-uppercase fw-bold">Waktu Eksekusi CPU</small>
                            <h5 class="fw-bold mt-1 text-success"><?= $sort_exec_time ?> ms</h5>
                        </div>
                    </div>
                </div>

                <!-- TERMINAL LOG DISPLAY -->
                <div class="mb-4">
                    <div class="terminal-header">
                        <span class="circle-dot bg-danger"></span>
                        <span class="circle-dot bg-warning"></span>
                        <span class="circle-dot bg-success"></span>
                        <span class="small text-muted ms-2 fw-bold font-monospace">Live Execution Log (<?= strtoupper($sort_algo) ?> SORT)</span>
                    </div>
                    <div class="terminal-log"><?= implode("\n", $sort_result['logs']) ?></div>
                </div>

                <!-- HASIL DATA TERURUT -->
                <h5 class="fw-bold mb-3"><i class="bi bi-table me-2 text-primary"></i>Tabel Hasil Produk Terurut:</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle small mb-0">
                        <thead>
                            <tr class="small text-muted">
                                <th width="60">No</th>
                                <th>Nama Produk Elektronik</th>
                                <th>Kategori</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sort_result['data'] as $idx => $item): ?>
                                <tr>
                                    <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= htmlspecialchars($item['gambar']) ?>" class="rounded-2" width="36" height="36" style="object-fit: cover;">
                                            <span class="fw-semibold"><?= htmlspecialchars($item['nama_produk']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($item['kategori']) ?></td>
                                    <td class="text-end fw-bold text-primary"><?= rupiah($item['harga']) ?></td>
                                    <td class="text-center"><span class="badge bg-subtle text-secondary"><?= $item['stok'] ?> Unit</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-arrow-down-up display-3 opacity-50 mb-3 d-block"></i>
                    <p>Silakan pilih algoritma sorting di atas lalu klik <strong>"Jalankan"</strong> untuk melihat log proses per langkah.</p>
                </div>
            <?php endif; ?>
        </div>

    <!-- ======================================================== -->
    <!-- TAB 2: SIMULASI SEARCHING (LINEAR & BINARY SEARCH + LOG) -->
    <!-- ======================================================== -->
    <?php elseif ($active_tab === 'searching'): ?>
        <div class="card-themed p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h4 class="fw-bold mb-1">
                        <i class="bi bi-search me-2 text-primary"></i>Simulasi Algoritma Searching
                    </h4>
                    <p class="text-muted small mb-0">Mendemonstrasikan Linear Search (O(N)) dan Binary Search (O(log N)) lengkap dengan rekaman log langkah perbandingan.</p>
                </div>
            </div>

            <form method="POST" class="p-4 rounded-4 mb-4 border" style="background: var(--bg-subtle); border-color: var(--border-color) !important;">
                <input type="hidden" name="run_search" value="1">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Pilih Metode Searching:</label>
                        <select name="search_method" class="form-select rounded-3" id="selectSearchMethod" onchange="toggleSearchInputs()">
                            <option value="linear" <?= $search_method === 'linear' ? 'selected' : '' ?>>1. Linear Search (Sequential Search - O(N))</option>
                            <option value="binary" <?= $search_method === 'binary' ? 'selected' : '' ?>>2. Binary Search (Bagi Dua Terurut - O(log N))</option>
                        </select>
                    </div>

                    <!-- Input Linear Search (Nama/Keyword) -->
                    <div class="col-md-5" id="groupLinear">
                        <label class="form-label small fw-bold">Masukkan Kata Kunci Nama / Kategori:</label>
                        <input type="text" name="search_keyword" class="form-control rounded-3" placeholder="Contoh: TV, Keyboard, Apple, Sony..." value="<?= htmlspecialchars($search_keyword) ?>">
                    </div>

                    <!-- Input Binary Search (Harga) -->
                    <div class="col-md-5" id="groupBinary" style="<?= $search_method === 'binary' ? '' : 'display: none;' ?>">
                        <label class="form-label small fw-bold">Cari Produk Berdasarkan Harga (Rp):</label>
                        <select name="search_price" class="form-select rounded-3">
                            <option value="">-- Pilih Nilai Harga Sampel --</option>
                            <?php foreach ($raw_products as $rp): ?>
                                <option value="<?= $rp['harga'] ?>" <?= $search_price === (int)$rp['harga'] ? 'selected' : '' ?>>
                                    <?= rupiah($rp['harga']) ?> (<?= htmlspecialchars($rp['nama_produk']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-gradient w-100 rounded-3 py-2 fw-bold">
                            <i class="bi bi-search me-1"></i> Mulai Pencarian
                        </button>
                    </div>
                </div>
            </form>

            <?php if ($search_result): ?>
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 border text-center" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                            <small class="text-muted text-uppercase fw-bold">Metode Searching</small>
                            <h5 class="fw-bold mt-1 text-primary"><?= strtoupper($search_method) ?> SEARCH</h5>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 border text-center" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                            <small class="text-muted text-uppercase fw-bold">Jumlah Perbandingan</small>
                            <h5 class="fw-bold mt-1 text-warning"><?= $search_result['comparisons'] ?> Langkah Perbandingan</h5>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 rounded-3 border text-center" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
                            <small class="text-muted text-uppercase fw-bold">Waktu Eksekusi</small>
                            <h5 class="fw-bold mt-1 text-success"><?= $search_exec_time ?> ms</h5>
                        </div>
                    </div>
                </div>

                <!-- TERMINAL LOG SEARCH -->
                <div class="mb-4">
                    <div class="terminal-header">
                        <span class="circle-dot bg-danger"></span>
                        <span class="circle-dot bg-warning"></span>
                        <span class="circle-dot bg-success"></span>
                        <span class="small text-muted ms-2 fw-bold font-monospace">Live Search Execution Log</span>
                    </div>
                    <div class="terminal-log"><?= implode("\n", $search_result['logs']) ?></div>
                </div>

                <!-- HASIL DITEMUKAN -->
                <h5 class="fw-bold mb-3"><i class="bi bi-check2-circle me-2 text-success"></i>Produk Yang Ditemukan:</h5>
                <?php 
                $items_found = [];
                if ($search_method === 'linear') {
                    $items_found = $search_result['results'];
                } elseif ($search_method === 'binary' && $search_result['result']) {
                    $items_found = [$search_result['result']];
                }
                ?>

                <?php if (empty($items_found)): ?>
                    <div class="alert alert-warning rounded-3 small">
                        <i class="bi bi-exclamation-triangle me-1"></i> Data yang dicari tidak ditemukan dalam dataset.
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($items_found as $prod): ?>
                            <div class="col-md-4">
                                <div class="card-preview d-flex align-items-center gap-3">
                                    <img src="<?= htmlspecialchars($prod['gambar']) ?>" class="rounded-3" width="60" height="60" style="object-fit: cover;">
                                    <div>
                                        <div class="fw-bold small"><?= htmlspecialchars($prod['nama_produk']) ?></div>
                                        <div class="text-primary fw-bold"><?= rupiah($prod['harga']) ?></div>
                                        <span class="badge bg-subtle text-secondary" style="font-size: 0.7rem;"><?= htmlspecialchars($prod['kategori']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-search display-3 opacity-50 mb-3 d-block"></i>
                    <p>Pilih metode pencarian lalu klik <strong>"Mulai Pencarian"</strong> untuk melihat log perbandingannya.</p>
                </div>
            <?php endif; ?>
        </div>

    <!-- ======================================================== -->
    <!-- TAB 3: MATERI STRUKTUR DATA (ARRAY, POINTER, STRUCT) -->
    <!-- ======================================================== -->
    <?php elseif ($active_tab === 'materi'): ?>
        <div class="row g-4 mb-4">
            <!-- 1. MATERI ARRAY -->
            <div class="col-lg-6">
                <div class="card-themed p-4 h-100">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="p-2 rounded-3 bg-gradient-primary text-white"><i class="bi bi-grid-3x3 fs-5"></i></div>
                        <div>
                            <h5 class="fw-bold mb-0">1. Struktur Data: Array</h5>
                            <small class="text-muted">Koleksi elemen dengan tipe sejenis/heterogen menggunakan indeks</small>
                        </div>
                    </div>
                    <p class="small text-muted">
                        Dalam aplikasi toko elektronik ini, Array digunakan untuk:
                        <br>• <strong>Array Tunggal (Numerik):</strong> Menyimpan list ID produk atau indeks perulangan.
                        <br>• <strong>Associative Array:</strong> Menyimpan pasangan kunci-nilai data produk (<code>'nama' => '...', 'harga' => ...</code>).
                        <br>• <strong>Multidimensional Array:</strong> Menyimpan sesi keranjang belanja <code>$_SESSION['cart'][$id] = $qty</code> dan result query MySQL.
                    </p>
                    <div class="code-block mb-3">
// Contoh Implementasi Array pada Toko Elektronik:
$katalog = [
    ["id" => 1, "nama" => "Smart TV 43 Inch", "harga" => 4250000],
    ["id" => 2, "nama" => "Gaming Keyboard", "harga" => 650000]
];

// Akses elemen associative array:
echo $katalog[0]['nama']; // Output: Smart TV 43 Inch
                    </div>
                </div>
            </div>

            <!-- 2. MATERI STRUCT -->
            <div class="col-lg-6">
                <div class="card-themed p-4 h-100">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="p-2 rounded-3 bg-gradient-secondary text-white"><i class="bi bi-diagram-3-fill fs-5"></i></div>
                        <div>
                            <h5 class="fw-bold mb-0">2. Struktur Data: Struct</h5>
                            <small class="text-muted">Struktur tipe data majemuk (Data Transfer Object)</small>
                        </div>
                    </div>
                    <p class="small text-muted">
                        Karena PHP tidak memiliki keyword bawaan <code>struct</code> seperti C/C++, di PHP modern Struct diimplementasikan menggunakan <strong>Data Class ber-tipe kuat (*Strongly Typed Object*)</strong> untuk menjamin integritas tipe data properti:
                    </p>
                    <div class="code-block mb-3">
class ProdukStruct {
    public int $id;
    public string $nama_produk;
    public int $harga;
    public int $stok;

    public function __construct(int $id, string $nama, int $harga, int $stok) {
        $this->id = $id;
        $this->nama_produk = $nama;
        $this->harga = $harga;
        $this->stok = $stok;
    }
}
                    </div>
                </div>
            </div>

            <!-- 3. MATERI POINTER -->
            <div class="col-12">
                <div class="card-themed p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="p-2 rounded-3 bg-gradient-accent text-white"><i class="bi bi-cursor-fill fs-5"></i></div>
                        <div>
                            <h5 class="fw-bold mb-0">3. Konsep Pointer (Passing by Reference `&` di PHP)</h5>
                            <small class="text-muted">Mengakses dan memanipulasi langsung alamat memori variabel sumber tanpa copy</small>
                        </div>
                    </div>

                    <p class="small text-muted mb-3">
                        Pointer mengacu pada referensi alamat memori variabel. Di PHP, konsep pointer direpresentasikan oleh <strong>Reference Operator <code>&</code></strong>. Saat parameter fungsi atau loop didefinisikan dengan <code>&$variable</code>, PHP tidak menduplikasi nilai di memori (*no copy-on-write*), melainkan langsung memanipulasi lokasi memori asli variabel tersebut!
                    </p>

                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <div class="code-block">
// Fungsi dengan Pointer/Reference:
function terapkanDiskonPointer(array &$item, int $persenDiskon) {
    // Memodifikasi langsung data pada alamat memori sumber
    $item['harga'] -= ($item['harga'] * ($persenDiskon / 100));
}

// Simulasi eksekusi:
$barang = ["nama" => "Laptop", "harga" => 10000000];
terapkanDiskonPointer($barang, 10);
// Nilai asli $barang['harga'] otomatis berubah menjadi 9.000.000!
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="p-3 rounded-4 border" style="background: var(--bg-subtle); border-color: var(--border-color) !important;">
                                <h6 class="fw-bold mb-2 small"><i class="bi bi-play-circle me-1 text-primary"></i>Uji Coba Live Pointer:</h6>
                                <form method="POST">
                                    <input type="hidden" name="run_pointer_demo" value="1">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Diskon Persen Langsung ke Memori:</label>
                                        <div class="input-group">
                                            <input type="number" name="diskon_input" class="form-control" value="20" min="1" max="90">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-gradient btn-sm w-100 rounded-pill">
                                        Terapkan Diskon via Pointer (&)
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($demo_pointer_logs)): ?>
                        <div class="mt-4">
                            <div class="terminal-header">
                                <span class="circle-dot bg-success"></span>
                                <span class="small text-muted ms-2 fw-bold font-monospace">Hasil Manipulasi Pointer di Memori:</span>
                            </div>
                            <div class="terminal-log"><?= implode("\n", $demo_pointer_logs) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer class="py-4 mt-5 border-top" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
    <div class="container text-center text-muted small">
        &copy; <?= date('Y') ?> <strong>ElectroMart</strong>. Modul Praktikum Struktur Data & Algoritma (Array, Pointer, Struct, Sorting & Searching).
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSearchInputs() {
    const method = document.getElementById('selectSearchMethod').value;
    const groupLinear = document.getElementById('groupLinear');
    const groupBinary = document.getElementById('groupBinary');

    if (method === 'linear') {
        groupLinear.style.display = 'block';
        groupBinary.style.display = 'none';
    } else {
        groupLinear.style.display = 'none';
        groupBinary.style.display = 'block';
    }
}
</script>
</body>
</html>
