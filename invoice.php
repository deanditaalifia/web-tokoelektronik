<?php
require_once 'koneksi.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    die("ID Pesanan tidak valid.");
}

$query = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id LIMIT 1");
if (!$query || mysqli_num_rows($query) === 0) {
    die("Pesanan tidak ditemukan.");
}

$order = mysqli_fetch_assoc($query);
$kode_pesanan = !empty($order['kode_pesanan']) ? $order['kode_pesanan'] : '#ORD-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT);

// Ambil item pesanan
$items_query = mysqli_query($conn, "
    SELECT oi.*, p.nama_produk, p.kategori, p.gambar 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = $order_id
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($kode_pesanan) ?> - ElectroMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 2rem 0;
        }
        .invoice-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            padding: 3rem;
            max-width: 800px;
            margin: auto;
        }
        .invoice-brand {
            font-size: 1.6rem;
            font-weight: 800;
            color: #2563eb;
        }
        .table thead th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            border-top: none;
        }
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
        }
        @media print {
            body { background: white; padding: 0; }
            .invoice-card { box-shadow: none; padding: 1.5rem; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="invoice-card">
        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print pb-3 border-bottom">
            <button onclick="window.history.back()" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </button>
            <button onclick="window.print()" class="btn btn-primary rounded-pill btn-sm px-4 fw-semibold shadow-sm">
                <i class="bi bi-printer me-1"></i> Cetak Invoice / Nota
            </button>
        </div>

        <!-- Header -->
        <div class="row align-items-center mb-4">
            <div class="col-sm-6">
                <div class="invoice-brand d-flex align-items-center mb-1">
                    <i class="bi bi-cpu-fill me-2"></i> ElectroMart
                </div>
                <div class="text-muted small">
                    Pusat Peralatan & Gadget Elektronik Modern<br>
                    Email: support@electromart.local | CS: +62 812-3456-7890<br>
                    Jl. Teknologi Digital No. 88, Indonesia
                </div>
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <h4 class="fw-bold mb-1 text-primary">FAKTUR PESANAN</h4>
                <div class="fw-bold text-dark fs-5"><?= htmlspecialchars($kode_pesanan) ?></div>
                <div class="text-muted small">Tanggal: <?= date('d F Y, H:i', strtotime($order['tanggal'])) ?> WIB</div>
                <div class="mt-2">
                    <?= get_status_badge($order['status']) ?>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <!-- Informasi Pembeli & Pengiriman -->
        <div class="row mb-4">
            <div class="col-sm-6 mb-3 mb-sm-0">
                <h6 class="text-uppercase text-secondary fw-bold small mb-2">Tujuan Pengiriman:</h6>
                <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($order['nama_pembeli']) ?></div>
                <div class="text-muted small"><i class="bi bi-whatsapp me-1 text-success"></i><?= htmlspecialchars($order['no_hp']) ?></div>
                <div class="text-muted small mt-1">
                    <i class="bi bi-geo-alt me-1 text-danger"></i><?= nl2br(htmlspecialchars($order['alamat'])) ?>
                </div>
            </div>
            <div class="col-sm-6 text-sm-end">
                <h6 class="text-uppercase text-secondary fw-bold small mb-2">Informasi Pembayaran:</h6>
                <div class="fw-semibold text-dark">
                    Metode: <span class="badge bg-light text-dark border"><?= htmlspecialchars($order['metode_pembayaran'] ?? 'Transfer Bank') ?></span>
                </div>
                <?php if (!empty($order['no_resi'])): ?>
                    <div class="text-primary small mt-1 fw-bold">
                        <i class="bi bi-upc-scan me-1"></i>No. Resi: <?= htmlspecialchars($order['no_resi']) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($order['catatan'])): ?>
                    <div class="text-muted small mt-1 fst-italic">
                        Catatan: "<?= htmlspecialchars($order['catatan']) ?>"
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabel Barang -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th>Nama Produk Elektronik</th>
                        <th class="text-end" width="18%">Harga Satuan</th>
                        <th class="text-center" width="10%">Qty</th>
                        <th class="text-end" width="20%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $calc_total = 0;
                    while ($it = mysqli_fetch_assoc($items_query)):
                        $sub = $it['harga'] * $it['qty'];
                        $calc_total += $sub;
                    ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($it['nama_produk'] ?? 'Produk Elektronik') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($it['kategori'] ?? '') ?></div>
                            </td>
                            <td class="text-end"><?= rupiah($it['harga']) ?></td>
                            <td class="text-center fw-semibold"><?= $it['qty'] ?></td>
                            <td class="text-end fw-bold text-dark"><?= rupiah($sub) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-bold py-3">Total Pembayaran</td>
                        <td class="text-end fw-bold py-3 text-primary fs-5"><?= rupiah($order['total_harga']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Catatan Kaki -->
        <div class="p-3 rounded-3 bg-light border border-light-subtle small text-muted">
            <div class="fw-semibold text-dark mb-1"><i class="bi bi-shield-check text-primary me-1"></i> Ketentuan Garansi & Layanan:</div>
            • Semua produk elektronik yang dijual bergaransi resmi toko 1 tahun.<br>
            • Simpan bukti faktur ini untuk klaim garansi atau pertukaran produk jika terdapat cacat pabrik.<br>
            • Terima kasih telah berbelanja di <strong>ElectroMart</strong>!
        </div>
    </div>
</div>

</body>
</html>
