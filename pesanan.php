<?php
require_once 'koneksi.php';

$search_query = trim($_GET['q'] ?? '');
$filter_order = null;

if (!empty($search_query)) {
    $sq = mysqli_real_escape_string($conn, $search_query);
    $clean_num = preg_replace('/[^0-9]/', '', $sq);
    $order_sql = "SELECT * FROM orders WHERE id = '$clean_num' OR no_hp LIKE '%$sq%' OR kode_pesanan LIKE '%$sq%' ORDER BY id DESC";
    $filter_order = mysqli_query($conn, $order_sql);
} else {
    $filter_order = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC LIMIT 10");
}

$total_item_cart = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak & Cek Pesanan - ElectroMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/theme.js"></script>
    <style>
        .order-card {
            background: var(--bg-surface);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .order-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            border-color: rgba(79, 70, 229, 0.4);
        }
        .status-timeline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin: 1.75rem 0;
            padding: 0 1rem;
        }
        .status-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        .step-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            background: var(--bg-subtle);
            color: var(--text-muted);
            font-weight: bold;
            margin-bottom: 0.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .step-circle.active {
            background: var(--grad-primary);
            color: white;
            border-color: transparent;
            box-shadow: 0 0 0 5px rgba(79, 70, 229, 0.25);
        }
        .step-circle.completed {
            background: #10b981;
            color: white;
            border-color: transparent;
        }
        .step-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-align: center;
        }
        .step-label.active {
            color: #4f46e5;
            font-weight: 700;
        }
        .timeline-line {
            position: absolute;
            top: 21px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: var(--border-color);
            z-index: 1;
        }
        .timeline-progress {
            height: 100%;
            background: var(--grad-primary);
            transition: width 0.4s ease;
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
                <i class="bi bi-house-door me-1"></i> Beranda Belanja
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

<div class="container py-5">
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipe'] ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i><?= $flash['pesan'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- HEADER & SEARCH FORM -->
    <div class="row align-items-center mb-4">
        <div class="col-lg-6 mb-3 mb-lg-0">
            <h2 class="fw-bold mb-1">Status & Pelacakan Pesanan</h2>
            <p class="text-muted mb-0 small">Pantau status konfirmasi pesanan, nomor resi pengiriman, serta cetak bukti faktur.</p>
        </div>
        <div class="col-lg-6">
            <form method="GET" class="d-flex gap-2">
                <div class="input-group">
                    <span class="input-group-text border-end-0 rounded-start-pill ps-3 text-muted" style="background: var(--input-bg);">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control border-start-0 rounded-end-pill py-2" placeholder="Masukkan Nomor HP / No. Pesanan..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
                <button type="submit" class="btn btn-gradient rounded-pill px-4 fw-semibold">Lacak</button>
                <?php if (!empty($search_query)): ?>
                    <a href="pesanan.php" class="btn btn-outline-secondary rounded-pill">Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- LIST ORDERS -->
    <?php if (!$filter_order || mysqli_num_rows($filter_order) === 0): ?>
        <div class="card-themed text-center py-5">
            <div class="card-body">
                <i class="bi bi-search display-3 text-muted opacity-50 mb-3 d-block"></i>
                <h5 class="fw-bold mt-2">Tidak Ada Pesanan Ditemukan</h5>
                <p class="text-muted small">Coba masukkan nomor WhatsApp atau ID Pesanan yang Anda gunakan saat checkout.</p>
                <a href="index.php" class="btn btn-gradient rounded-pill px-4 mt-2">
                    <i class="bi bi-bag-plus me-1"></i> Mulai Belanja Elektronik
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php while ($ord = mysqli_fetch_assoc($filter_order)): 
            $oid = $ord['id'];
            $kode_p = !empty($ord['kode_pesanan']) ? $ord['kode_pesanan'] : '#ORD-' . str_pad($ord['id'], 5, '0', STR_PAD_LEFT);

            $items_res = mysqli_query($conn, "
                SELECT oi.*, p.nama_produk, p.gambar, p.kategori 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = $oid
            ");

            $st = $ord['status'];
            $step_val = 1;
            if ($st === 'Pending') $step_val = 1;
            elseif ($st === 'Dikonfirmasi') $step_val = 2;
            elseif ($st === 'Dikirim') $step_val = 3;
            elseif ($st === 'Selesai') $step_val = 4;
            elseif ($st === 'Dibatalkan') $step_val = 0;

            $progress_pct = 0;
            if ($step_val === 1) $progress_pct = 0;
            elseif ($step_val === 2) $progress_pct = 33;
            elseif ($step_val === 3) $progress_pct = 66;
            elseif ($step_val === 4) $progress_pct = 100;
        ?>
            <div class="order-card p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 border-bottom gap-2" style="border-color: var(--border-color) !important;">
                    <div>
                        <span class="fw-bold text-primary fs-5"><?= htmlspecialchars($kode_p) ?></span>
                        <span class="text-muted small ms-2">
                            <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($ord['tanggal'])) ?> WIB
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?= get_status_badge($ord['status']) ?>
                        <a href="invoice.php?id=<?= $ord['id'] ?>" target="_blank" class="btn btn-sm btn-gradient-outline rounded-pill px-3">
                            <i class="bi bi-printer me-1"></i> Cetak Invoice
                        </a>
                    </div>
                </div>

                <!-- PROGRESS TIMELINE -->
                <?php if ($st !== 'Dibatalkan'): ?>
                    <div class="status-timeline my-4">
                        <div class="timeline-line">
                            <div class="timeline-progress" style="width: <?= $progress_pct ?>%;"></div>
                        </div>

                        <div class="status-step">
                            <div class="step-circle <?= $step_val >= 1 ? ($step_val == 1 ? 'active' : 'completed') : '' ?>">
                                <i class="bi <?= $step_val > 1 ? 'bi-check-lg' : 'bi-hourglass-split' ?>"></i>
                            </div>
                            <div class="step-label <?= $step_val >= 1 ? 'active' : '' ?>">Pesanan Masuk</div>
                        </div>

                        <div class="status-step">
                            <div class="step-circle <?= $step_val >= 2 ? ($step_val == 2 ? 'active' : 'completed') : '' ?>">
                                <i class="bi <?= $step_val > 2 ? 'bi-check-lg' : 'bi-gear-wide-connected' ?>"></i>
                            </div>
                            <div class="step-label <?= $step_val >= 2 ? 'active' : '' ?>">Dikonfirmasi & Disiapkan</div>
                        </div>

                        <div class="status-step">
                            <div class="step-circle <?= $step_val >= 3 ? ($step_val == 3 ? 'active' : 'completed') : '' ?>">
                                <i class="bi <?= $step_val > 3 ? 'bi-check-lg' : 'bi-truck' ?>"></i>
                            </div>
                            <div class="step-label <?= $step_val >= 3 ? 'active' : '' ?>">Dalam Pengiriman</div>
                        </div>

                        <div class="status-step">
                            <div class="step-circle <?= $step_val == 4 ? 'completed' : '' ?>">
                                <i class="bi bi-box2-heart"></i>
                            </div>
                            <div class="step-label <?= $step_val == 4 ? 'active' : '' ?>">Pesanan Diterima</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger rounded-3 py-2 px-3 small my-3" role="alert">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i>Pesanan ini telah dibatalkan oleh pihak toko atau pembeli.
                    </div>
                <?php endif; ?>

                <!-- DETAIL BARANG -->
                <div class="row g-3 align-items-center">
                    <div class="col-lg-8">
                        <div class="d-flex flex-column gap-2">
                            <?php while ($it = mysqli_fetch_assoc($items_res)): ?>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= htmlspecialchars($it['gambar'] ?? 'https://placehold.co/60x60') ?>" class="rounded-3 border" width="55" height="55" style="object-fit: cover;" onerror="this.src='https://placehold.co/60x60?text=No+Img'">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($it['nama_produk'] ?? 'Barang Elektronik') ?></div>
                                        <div class="text-muted small">
                                            <?= $it['qty'] ?> x <?= rupiah($it['harga']) ?> 
                                            <span class="badge bg-primary bg-opacity-10 text-primary ms-1"><?= htmlspecialchars($it['kategori'] ?? '') ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="small text-muted mt-3">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i> Alamat Pengiriman: <strong><?= htmlspecialchars($ord['alamat']) ?></strong><br>
                            <i class="bi bi-credit-card me-1"></i> Metode Pembayaran: <strong><?= htmlspecialchars($ord['metode_pembayaran'] ?? 'Transfer Bank') ?></strong>
                            <?php if (!empty($ord['no_resi'])): ?>
                                &nbsp;|&nbsp; <i class="bi bi-truck text-primary me-1"></i> No. Resi: <strong class="text-info"><?= htmlspecialchars($ord['no_resi']) ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0 pt-3 pt-lg-0 border-top border-lg-0" style="border-color: var(--border-color) !important;">
                        <div class="text-muted small">Total Tagihan:</div>
                        <div class="fw-bold fs-4 mb-2 gradient-text"><?= rupiah($ord['total_harga']) ?></div>

                        <a href="https://wa.me/6281234567890?text=Halo%20Admin%20ElectroMart%2C%20saya%20ingin%20menanyakan%20status%20pesanan%20saya%20<?= urlencode($kode_p) ?>" target="_blank" class="btn btn-gradient-outline btn-sm rounded-pill px-3">
                            <i class="bi bi-whatsapp me-1 text-success"></i> Hubungi CS Toko
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<footer class="py-4 mt-5 border-top" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
    <div class="container text-center text-muted small">
        &copy; <?= date('Y') ?> <strong>ElectroMart</strong>. Solusi Belanja Perangkat & Alat Elektronik Terpercaya.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
