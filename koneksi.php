<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_elektronik";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Helper Rupiah
function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

// Flash Message Helper
function set_flash($tipe, $pesan) {
    $_SESSION['flash'] = [
        'tipe' => $tipe, // 'success', 'danger', 'warning', 'info'
        'pesan' => $pesan
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Daftar Kategori Produk Elektronik
$kategori_list = [
    'Smartphone & Gadget',
    'Laptop & PC',
    'TV & Audio',
    'Konsol & Gaming',
    'Kamera & Drone',
    'Elektronik Rumah',
    'Aksesoris PC',
    'Komponen IoT'
];

// Helper Status Badge Order
function get_status_badge($status) {
    switch ($status) {
        case 'Pending':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Menunggu Konfirmasi</span>';
        case 'Dikonfirmasi':
            return '<span class="badge bg-info text-dark"><i class="bi bi-gear-fill me-1"></i>Dikonfirmasi & Diproses</span>';
        case 'Dikirim':
            return '<span class="badge bg-primary"><i class="bi bi-truck me-1"></i>Sedang Dikirim</span>';
        case 'Selesai':
            return '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Pesanan Selesai</span>';
        case 'Dibatalkan':
            return '<span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>Dibatalkan</span>';
        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}
?>