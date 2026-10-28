<?php
require_once 'koneksi.php';

// ==========================================
// 1. KETATKAN KONTROL AKSES & OTORISASI ROLE
// ==========================================
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php?access_denied=1');
    exit;
}

$current_user_id   = $_SESSION['admin_id'] ?? 0;
$current_username  = $_SESSION['admin_user'] ?? 'admin';
$current_name      = $_SESSION['admin_name'] ?? 'Administrator';
$current_role      = $_SESSION['admin_role'] ?? 'Admin Penjualan';

// Helper perizinan role
$is_superadmin = ($current_role === 'Super Admin');
$can_manage_products = ($current_role === 'Super Admin' || $current_role === 'Admin Gudang');
$can_manage_orders   = ($current_role === 'Super Admin' || $current_role === 'Admin Penjualan');

$active_tab = $_GET['tab'] ?? 'overview';

// ==========================================
// 2. PROSES CRUD USER ROLE (SUPER ADMIN ONLY)
// ==========================================
if (isset($_POST['tambah_admin'])) {
    if (!$is_superadmin) {
        set_flash('danger', 'Akses Ditolak: Hanya Super Admin yang berwenang menambah user admin baru.');
        header('Location: admin.php?tab=overview');
        exit;
    }

    $new_user  = mysqli_real_escape_string($conn, trim($_POST['username']));
    $new_name  = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $new_pass  = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $new_role  = mysqli_real_escape_string($conn, trim($_POST['role']));

    // Cek username duplikat
    $cek_user = mysqli_query($conn, "SELECT id FROM admins WHERE username = '$new_user'");
    if (mysqli_num_rows($cek_user) > 0) {
        set_flash('danger', "Gagal: Username '$new_user' sudah digunakan.");
    } else {
        $ins = mysqli_query($conn, "INSERT INTO admins (username, password, nama_lengkap, role, status_aktif) VALUES ('$new_user', '$new_pass', '$new_name', '$new_role', 1)");
        if ($ins) {
            set_flash('success', "Akun admin baru '$new_user' dengan role <strong>$new_role</strong> berhasil dibuat!");
        } else {
            set_flash('danger', "Gagal membuat admin: " . mysqli_error($conn));
        }
    }
    header('Location: admin.php?tab=users');
    exit;
}

if (isset($_GET['toggle_status_admin'])) {
    if (!$is_superadmin) {
        set_flash('danger', 'Akses Ditolak: Hanya Super Admin yang berwenang mengubah status akun.');
        header('Location: admin.php?tab=overview');
        exit;
    }
    $target_id = (int)$_GET['toggle_status_admin'];
    if ($target_id === $current_user_id) {
        set_flash('warning', 'Anda tidak dapat menonaktifkan akun Anda sendiri!');
    } else {
        mysqli_query($conn, "UPDATE admins SET status_aktif = 1 - status_aktif WHERE id = $target_id");
        set_flash('success', 'Status keaktifan user admin berhasil diubah.');
    }
    header('Location: admin.php?tab=users');
    exit;
}

if (isset($_GET['hapus_admin'])) {
    if (!$is_superadmin) {
        set_flash('danger', 'Akses Ditolak: Hanya Super Admin yang berwenang menghapus akun.');
        header('Location: admin.php?tab=overview');
        exit;
    }
    $target_id = (int)$_GET['hapus_admin'];
    if ($target_id === $current_user_id) {
        set_flash('danger', 'Keamanan: Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif login!');
    } else {
        mysqli_query($conn, "DELETE FROM admins WHERE id = $target_id");
        set_flash('success', 'User admin berhasil dihapus dari sistem.');
    }
    header('Location: admin.php?tab=users');
    exit;
}

// ==========================================
// 3. PROSES CRUD PRODUK
// ==========================================
if (isset($_POST['tambah_produk'])) {
    $nama      = mysqli_real_escape_string($conn, trim($_POST['nama_produk']));
    $kategori  = mysqli_real_escape_string($conn, trim($_POST['kategori']));
    $harga     = (int)$_POST['harga'];
    $stok      = (int)$_POST['stok'];
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
    $gambar    = 'https://images.unsplash.com/photo-1550009158-9ebf69173e03?auto=format&fit=crop&w=600&q=80';

    if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['gambar_file']['tmp_name'];
        $file_name = $_FILES['gambar_file']['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($file_ext, $allowed)) {
            $new_name = 'prod_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            $destination = 'uploads/' . $new_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                $gambar = $destination;
            }
        }
    } elseif (!empty($_POST['gambar_url'])) {
        $gambar = mysqli_real_escape_string($conn, trim($_POST['gambar_url']));
    }

    $sql = "INSERT INTO products (nama_produk, kategori, harga, stok, gambar, deskripsi) 
            VALUES ('$nama', '$kategori', $harga, $stok, '$gambar', '$deskripsi')";
    if (mysqli_query($conn, $sql)) {
        set_flash('success', 'Produk elektronik baru berhasil ditambahkan!');
    } else {
        set_flash('danger', 'Gagal menambahkan produk: ' . mysqli_error($conn));
    }
    header('Location: admin.php?tab=produk');
    exit;
}

if (isset($_POST['edit_produk'])) {
    $id        = (int)$_POST['id'];
    $nama      = mysqli_real_escape_string($conn, trim($_POST['nama_produk']));
    $kategori  = mysqli_real_escape_string($conn, trim($_POST['kategori']));
    $harga     = (int)$_POST['harga'];
    $stok      = (int)$_POST['stok'];
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));

    $old_q = mysqli_query($conn, "SELECT gambar FROM products WHERE id = $id");
    $old_data = mysqli_fetch_assoc($old_q);
    $gambar = $old_data['gambar'] ?? '';

    if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['gambar_file']['tmp_name'];
        $file_name = $_FILES['gambar_file']['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($file_ext, $allowed)) {
            $new_name = 'prod_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            $destination = 'uploads/' . $new_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                if (!empty($gambar) && file_exists($gambar) && strpos($gambar, 'uploads/') === 0) {
                    unlink($gambar);
                }
                $gambar = $destination;
            }
        }
    } elseif (!empty($_POST['gambar_url'])) {
        $gambar = mysqli_real_escape_string($conn, trim($_POST['gambar_url']));
    }

    $sql = "UPDATE products SET 
            nama_produk = '$nama',
            kategori = '$kategori',
            harga = $harga,
            stok = $stok,
            gambar = '$gambar',
            deskripsi = '$deskripsi'
            WHERE id = $id";
            
    if (mysqli_query($conn, $sql)) {
        set_flash('success', 'Data produk #' . $id . ' berhasil diperbarui!');
    } else {
        set_flash('danger', 'Gagal memperbarui produk: ' . mysqli_error($conn));
    }
    header('Location: admin.php?tab=produk');
    exit;
}

if (isset($_GET['hapus_produk'])) {
    $id = (int)$_GET['hapus_produk'];
    $prod_q = mysqli_query($conn, "SELECT gambar FROM products WHERE id = $id");
    if ($prod = mysqli_fetch_assoc($prod_q)) {
        if (!empty($prod['gambar']) && file_exists($prod['gambar']) && strpos($prod['gambar'], 'uploads/') === 0) {
            unlink($prod['gambar']);
        }
        mysqli_query($conn, "DELETE FROM products WHERE id = $id");
        set_flash('success', 'Produk berhasil dihapus!');
    }
    header('Location: admin.php?tab=produk');
    exit;
}

// ==========================================
// 4. PROSES STATUS & KONFIRMASI PESANAN
// ==========================================
if (isset($_POST['update_status_pesanan'])) {
    $order_id   = (int)$_POST['order_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $no_resi    = mysqli_real_escape_string($conn, trim($_POST['no_resi'] ?? ''));

    $curr_q = mysqli_query($conn, "SELECT status FROM orders WHERE id = $order_id");
    $curr = mysqli_fetch_assoc($curr_q);
    $old_status = $curr['status'] ?? '';

    // Kembalikan stok bila dibatalkan
    if ($new_status === 'Dibatalkan' && $old_status !== 'Dibatalkan') {
        $items = mysqli_query($conn, "SELECT product_id, qty FROM order_items WHERE order_id = $order_id");
        while ($it = mysqli_fetch_assoc($items)) {
            $pid = $it['product_id'];
            $qty = $it['qty'];
            mysqli_query($conn, "UPDATE products SET stok = stok + $qty WHERE id = $pid");
        }
    } elseif ($old_status === 'Dibatalkan' && $new_status !== 'Dibatalkan') {
        $items = mysqli_query($conn, "SELECT product_id, qty FROM order_items WHERE order_id = $order_id");
        while ($it = mysqli_fetch_assoc($items)) {
            $pid = $it['product_id'];
            $qty = $it['qty'];
            mysqli_query($conn, "UPDATE products SET stok = GREATEST(0, stok - $qty) WHERE id = $pid");
        }
    }

    $update_resi = !empty($no_resi) ? ", no_resi = '$no_resi'" : "";
    $sql = "UPDATE orders SET status = '$new_status' $update_resi WHERE id = $order_id";
    if (mysqli_query($conn, $sql)) {
        set_flash('success', "Status pesanan #ORD-$order_id berhasil diubah menjadi: <strong>$new_status</strong> oleh [$current_role].");
    } else {
        set_flash('danger', 'Gagal mengubah status: ' . mysqli_error($conn));
    }
    header('Location: admin.php?tab=pesanan');
    exit;
}

if (isset($_GET['hapus_pesanan'])) {
    $order_id = (int)$_GET['hapus_pesanan'];
    mysqli_query($conn, "DELETE FROM order_items WHERE order_id = $order_id");
    mysqli_query($conn, "DELETE FROM orders WHERE id = $order_id");
    set_flash('success', "Pesanan #ORD-$order_id berhasil dihapus dari sistem.");
    header('Location: admin.php?tab=pesanan');
    exit;
}

// ==========================================
// 5. STATISTIK DASHBOARD
// ==========================================
$total_revenue_q = mysqli_query($conn, "SELECT SUM(total_harga) as rev FROM orders WHERE status != 'Dibatalkan'");
$total_revenue = mysqli_fetch_assoc($total_revenue_q)['rev'] ?? 0;

$total_orders_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders");
$total_orders = mysqli_fetch_assoc($total_orders_q)['c'] ?? 0;

$pending_orders_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'Pending'");
$pending_orders = mysqli_fetch_assoc($pending_orders_q)['c'] ?? 0;

$confirmed_orders_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'Dikonfirmasi'");
$confirmed_orders = mysqli_fetch_assoc($confirmed_orders_q)['c'] ?? 0;

$total_products_q = mysqli_query($conn, "SELECT COUNT(*) as c, SUM(stok) as total_stok FROM products");
$prod_stats = mysqli_fetch_assoc($total_products_q);
$total_products = $prod_stats['c'] ?? 0;
$total_stok = $prod_stats['total_stok'] ?? 0;

$low_stock_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM products WHERE stok <= 5");
$low_stock_count = mysqli_fetch_assoc($low_stock_q)['c'] ?? 0;

$total_admins_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM admins");
$total_admins = mysqli_fetch_assoc($total_admins_q)['c'] ?? 1;

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrator - ElectroMart</title>
    <!-- SCRIPT SYNCHRONOUS ANTI-FLICKER (MENCEGAH KEDAP-KEDIP PADA LOAD) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('electromart_theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/theme.js"></script>
    <style>
        .admin-sidebar {
            width: 260px;
            background: var(--bg-surface);
            border-right: 1px solid var(--border-color);
            min-height: 100vh;
            color: var(--text-muted);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
        }
        .admin-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
            background-color: var(--bg-body);
        }
        .nav-link-admin {
            display: flex;
            align-items: center;
            color: var(--text-muted);
            padding: 0.85rem 1.25rem;
            border-radius: 14px;
            margin-bottom: 0.35rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.15s, background-color 0.15s;
        }
        .nav-link-admin:hover {
            color: #ffffff;
            background: rgba(79, 70, 229, 0.15);
        }
        .nav-link-admin.active {
            color: #ffffff;
            background: var(--grad-primary);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.35);
        }
        .stat-card {
            background: var(--bg-surface);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .role-pill {
            font-size: 0.72rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            letter-spacing: 0.5px;
        }
        @media (max-width: 991.98px) {
            .admin-sidebar {
                margin-left: -260px;
            }
            .admin-sidebar.show {
                margin-left: 0;
            }
            .admin-content {
                margin-left: 0;
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR ADMIN -->
<aside class="admin-sidebar d-flex flex-column p-3" id="adminSidebar">
    <div class="d-flex align-items-center gap-2 px-2 py-3 mb-3 border-bottom" style="border-color: var(--border-color) !important;">
        <div class="bg-gradient-primary text-white p-2 rounded-3 fs-5 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="bi bi-shield-check"></i>
        </div>
        <div>
            <div class="fw-bold gradient-text fs-6">ElectroMart</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Control Panel</div>
        </div>
    </div>

    <!-- ROLE USER PROFILE BADGE -->
    <div class="p-3 rounded-4 mb-3 border" style="background: var(--bg-subtle); border-color: var(--border-color) !important;">
        <div class="small text-muted mb-1">User Aktif Saat Ini:</div>
        <div class="fw-bold text-truncate"><?= htmlspecialchars($current_name) ?></div>
        <div class="d-flex align-items-center justify-content-between mt-2">
            <span class="small text-muted font-monospace">@<?= htmlspecialchars($current_username) ?></span>
            <?php if ($current_role === 'Super Admin'): ?>
                <span class="role-pill bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25">SUPER ADMIN</span>
            <?php elseif ($current_role === 'Admin Penjualan'): ?>
                <span class="role-pill bg-primary bg-opacity-20 text-primary border border-primary border-opacity-25">PENJUALAN</span>
            <?php else: ?>
                <span class="role-pill bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25">GUDANG</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- NAVIGASI MENU DASHBOARD -->
    <div class="flex-grow-1">
        <a href="admin.php?tab=overview" class="nav-link-admin <?= $active_tab === 'overview' ? 'active' : '' ?>">
            <i class="bi bi-grid-fill me-3 fs-5"></i> Dashboard
        </a>

        <a href="admin.php?tab=pesanan" class="nav-link-admin <?= $active_tab === 'pesanan' ? 'active' : '' ?> position-relative">
            <i class="bi bi-bag-check-fill me-3 fs-5"></i> Pesanan Masuk
            <?php if ($pending_orders > 0): ?>
                <span class="badge bg-warning text-dark rounded-pill ms-auto fw-bold"><?= $pending_orders ?> Baru</span>
            <?php endif; ?>
        </a>

        <a href="admin.php?tab=produk" class="nav-link-admin <?= $active_tab === 'produk' ? 'active' : '' ?>">
            <i class="bi bi-box-seam-fill me-3 fs-5"></i> Kelola Produk
        </a>

        <!-- TAB KHUSUS SUPER ADMIN: MANAJEMEN USER ROLE -->
        <?php if ($is_superadmin): ?>
            <a href="admin.php?tab=users" class="nav-link-admin <?= $active_tab === 'users' ? 'active' : '' ?>">
                <i class="bi bi-people-fill me-3 fs-5"></i> Kelola Role Admin
                <span class="badge bg-danger rounded-pill ms-auto fw-bold"><?= $total_admins ?></span>
            </a>
        <?php endif; ?>
    </div>

    <div class="pt-3 border-top" style="border-color: var(--border-color) !important;">
        <a href="index.php" target="_blank" class="nav-link-admin mb-2 text-info">
            <i class="bi bi-shop me-3 fs-5"></i> Buka Toko Publik
        </a>
        <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background: var(--bg-subtle);">
            <div class="small text-muted"><i class="bi bi-lock me-1"></i>Hak Akses Terverifikasi</div>
            <a href="admin_logout.php" class="btn btn-outline-danger btn-sm rounded-pill py-1 px-3 fw-semibold" title="Keluar">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="admin-content">
    <!-- TOP BAR -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 gap-3 border-bottom" style="border-color: var(--border-color) !important;">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none" type="button" onclick="document.getElementById('adminSidebar').classList.toggle('show')">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div>
                <h3 class="fw-bold mb-0">
                    <?php 
                    if ($active_tab === 'overview') echo 'Dashboard Overview';
                    elseif ($active_tab === 'pesanan') echo 'Kelola & Konfirmasi Pesanan';
                    elseif ($active_tab === 'produk') echo 'Katalog & CRUD Produk';
                    elseif ($active_tab === 'users') echo 'Manajemen User & Role Administrator';
                    ?>
                </h3>
                <small class="text-muted">
                    Login sebagai: <strong class="text-primary"><?= htmlspecialchars($current_name) ?></strong> 
                    (<span class="fw-bold"><?= $current_role ?></span>)
                </small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Theme Toggle -->
            <button class="theme-toggle-btn" type="button" aria-label="Ganti Tema">
                <i class="bi bi-moon-stars-fill text-primary"></i>
            </button>

            <a href="admin.php?tab=<?= $active_tab ?>" class="btn btn-gradient-outline rounded-pill btn-sm px-3">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </a>

            <?php if ($active_tab === 'produk' && $can_manage_products): ?>
                <button class="btn btn-gradient rounded-pill btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTambahProduk">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Produk
                </button>
            <?php elseif ($active_tab === 'users' && $is_superadmin): ?>
                <button class="btn btn-gradient rounded-pill btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTambahAdmin">
                    <i class="bi bi-person-plus-fill me-1"></i> Tambah Admin Baru
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- FLASH MESSAGE -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipe'] ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i><?= $flash['pesan'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- TAB 1: OVERVIEW -->
    <!-- ============================================================== -->
    <?php if ($active_tab === 'overview'): ?>
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Total Pendapatan</div>
                        <h4 class="fw-bold mt-1 mb-0 gradient-text"><?= rupiah($total_revenue) ?></h4>
                        <div class="small text-success mt-1"><i class="bi bi-graph-up-arrow me-1"></i>Dari pesanan sukses</div>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Perlu Konfirmasi</div>
                        <h4 class="fw-bold <?= $pending_orders > 0 ? 'text-warning' : '' ?> mt-1 mb-0"><?= $pending_orders ?></h4>
                        <div class="small <?= $pending_orders > 0 ? 'text-warning fw-bold' : 'text-muted' ?> mt-1">
                            <i class="bi bi-bell-fill me-1"></i>Status Pending
                        </div>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Total Pesanan</div>
                        <h4 class="fw-bold mt-1 mb-0"><?= $total_orders ?></h4>
                        <div class="small text-muted mt-1"><i class="bi bi-check2-circle me-1"></i><?= $confirmed_orders ?> Dikonfirmasi</div>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-bag-check"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Total Produk</div>
                        <h4 class="fw-bold mt-1 mb-0"><?= $total_products ?> Item</h4>
                        <div class="small <?= $low_stock_count > 0 ? 'text-danger fw-bold' : 'text-muted' ?> mt-1">
                            <i class="bi bi-exclamation-circle me-1"></i><?= $low_stock_count ?> Stok <= 5
                        </div>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-cpu"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-themed p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Pesanan Masuk Terbaru</h6>
                        <a href="admin.php?tab=pesanan" class="btn btn-sm btn-gradient-outline rounded-pill px-3">Lihat Semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr class="small text-muted">
                                    <th>No. Order</th>
                                    <th>Pembeli</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_q = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC LIMIT 5");
                                if (mysqli_num_rows($recent_q) == 0):
                                ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada pesanan masuk.</td></tr>
                                <?php else: ?>
                                    <?php while ($ord = mysqli_fetch_assoc($recent_q)): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">#ORD-<?= $ord['id'] ?></td>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($ord['nama_pembeli']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($ord['no_hp']) ?></small>
                                            </td>
                                            <td class="fw-bold"><?= rupiah($ord['total_harga']) ?></td>
                                            <td><?= get_status_badge($ord['status']) ?></td>
                                            <td class="text-end">
                                                <a href="admin.php?tab=pesanan&id=<?= $ord['id'] ?>" class="btn btn-sm btn-gradient-outline rounded-pill px-3">
                                                    Proses <i class="bi bi-chevron-right ms-1"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-themed p-3 mb-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Hak Akses Anda: <?= $current_role ?></h6>
                    <div class="small text-muted mb-3">
                        <?php if ($is_superadmin): ?>
                            • Akses Penuh: Konfirmasi pesanan, CRUD produk, kelola role dan buat admin baru.
                        <?php elseif ($current_role === 'Admin Penjualan'): ?>
                            • Akses Penjualan: Konfirmasi pesanan, cetak faktur invoice, hubungi pembeli WhatsApp.
                        <?php else: ?>
                            • Akses Gudang: Pembaruan stok produk, inventaris, dan input resi pengiriman.
                        <?php endif; ?>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="admin.php?tab=pesanan&filter_status=Pending" class="btn btn-gradient rounded-3 text-start py-2 px-3 fw-semibold">
                            <i class="bi bi-hourglass-split me-2"></i> Cek Pesanan Pending (<?= $pending_orders ?>)
                        </a>
                        <?php if ($can_manage_products): ?>
                            <button class="btn btn-gradient-outline rounded-3 text-start py-2 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTambahProduk">
                                <i class="bi bi-plus-lg me-2"></i> Tambah Produk Baru
                            </button>
                        <?php endif; ?>
                        <?php if ($is_superadmin): ?>
                            <a href="admin.php?tab=users" class="btn btn-gradient-outline rounded-3 text-start py-2 px-3 fw-semibold text-danger">
                                <i class="bi bi-people-fill me-2"></i> Kelola Role Admin (<?= $total_admins ?>)
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-themed p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Stok Menipis (<= 5)</h6>
                        <a href="admin.php?tab=produk" class="small text-decoration-none">Kelola</a>
                    </div>
                    <?php
                    $low_q = mysqli_query($conn, "SELECT id, nama_produk, stok, gambar FROM products WHERE stok <= 5 ORDER BY stok ASC LIMIT 4");
                    if (mysqli_num_rows($low_q) == 0):
                    ?>
                        <div class="text-success small py-2"><i class="bi bi-check-circle me-1"></i> Semua stok produk dalam kondisi aman.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php while ($lp = mysqli_fetch_assoc($low_q)): ?>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center border-0 bg-transparent">
                                    <div class="text-truncate me-2 small fw-semibold" style="max-width: 180px;">
                                        <?= htmlspecialchars($lp['nama_produk']) ?>
                                    </div>
                                    <span class="badge bg-danger rounded-pill">Sisa <?= $lp['stok'] ?></span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <!-- ============================================================== -->
    <!-- TAB 2: KELOLA PESANAN & KONFIRMASI -->
    <!-- ============================================================== -->
    <?php elseif ($active_tab === 'pesanan'): ?>
        <?php
        $filter_status = $_GET['filter_status'] ?? 'Semua';
        $search_order = $_GET['cari_order'] ?? '';

        $where_clauses = [];
        if ($filter_status !== 'Semua') {
            $status_escaped = mysqli_real_escape_string($conn, $filter_status);
            $where_clauses[] = "status = '$status_escaped'";
        }
        if (!empty($search_order)) {
            $kw = mysqli_real_escape_string($conn, $search_order);
            $where_clauses[] = "(nama_pembeli LIKE '%$kw%' OR no_hp LIKE '%$kw%' OR id = '$kw' OR kode_pesanan LIKE '%$kw%')";
        }

        $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";
        $orders_query = mysqli_query($conn, "SELECT * FROM orders $where_sql ORDER BY id DESC");
        ?>

        <div class="card-themed p-3 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex flex-wrap gap-1">
                    <?php 
                    $status_list = ['Semua', 'Pending', 'Dikonfirmasi', 'Dikirim', 'Selesai', 'Dibatalkan'];
                    foreach ($status_list as $st):
                        $active_cls = ($filter_status === $st) ? 'btn-gradient' : 'btn-gradient-outline';
                    ?>
                        <a href="admin.php?tab=pesanan&filter_status=<?= urlencode($st) ?>" class="btn btn-sm <?= $active_cls ?> rounded-pill px-3 fw-semibold">
                            <?= $st ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="tab" value="pesanan">
                    <input type="hidden" name="filter_status" value="<?= htmlspecialchars($filter_status) ?>">
                    <input type="text" name="cari_order" class="form-control form-control-sm rounded-pill px-3" placeholder="Cari nama, no hp, kode..." value="<?= htmlspecialchars($search_order) ?>">
                    <button type="submit" class="btn btn-sm btn-gradient rounded-pill px-3">Cari</button>
                    <?php if (!empty($search_order)): ?>
                        <a href="admin.php?tab=pesanan&filter_status=<?= urlencode($filter_status) ?>" class="btn btn-sm btn-outline-secondary rounded-pill">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="card-themed overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr class="small text-muted">
                            <th class="p-3">No. Pesanan</th>
                            <th class="p-3">Pembeli & Kontak</th>
                            <th class="p-3">Barang Dipesan</th>
                            <th class="p-3">Total Belanja</th>
                            <th class="p-3">Status Saat Ini</th>
                            <th class="p-3 text-center" width="220">Konfirmasi / Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($orders_query) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    Tidak ada data pesanan yang sesuai filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($ord = mysqli_fetch_assoc($orders_query)): 
                                $oid = $ord['id'];
                                $items_q = mysqli_query($conn, "SELECT oi.*, p.nama_produk, p.gambar FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $oid");
                                $items_arr = [];
                                while ($it = mysqli_fetch_assoc($items_q)) {
                                    $items_arr[] = $it;
                                }
                            ?>
                                <tr>
                                    <td class="p-3">
                                        <div class="fw-bold text-primary fs-6">#ORD-<?= $ord['id'] ?></div>
                                        <small class="text-muted"><?= date('d M Y, H:i', strtotime($ord['tanggal'])) ?></small>
                                        <?php if (!empty($ord['no_resi'])): ?>
                                            <div class="small text-info fw-semibold mt-1">Resi: <?= htmlspecialchars($ord['no_resi']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3">
                                        <div class="fw-bold"><?= htmlspecialchars($ord['nama_pembeli']) ?></div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $ord['no_hp'])) ?>?text=Halo%20<?= urlencode($ord['nama_pembeli']) ?>%2C%20kami%20dari%20ElectroMart%20mengenai%20pesanan%20%23ORD-<?= $ord['id'] ?>" target="_blank" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none py-1 px-2 rounded-2">
                                                <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($ord['no_hp']) ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted text-truncate mt-1" style="max-width: 200px;" title="<?= htmlspecialchars($ord['alamat']) ?>">
                                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ord['alamat']) ?>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <ul class="list-unstyled mb-0 small">
                                            <?php foreach ($items_arr as $it): ?>
                                                <li class="mb-1">
                                                    • <span class="fw-semibold"><?= htmlspecialchars($it['nama_produk'] ?? 'Item') ?></span>
                                                    <span class="badge bg-subtle text-secondary ms-1">x<?= $it['qty'] ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td class="p-3">
                                        <div class="fw-bold fs-6"><?= rupiah($ord['total_harga']) ?></div>
                                        <span class="badge bg-subtle text-secondary border small mt-1">
                                            <?= htmlspecialchars($ord['metode_pembayaran'] ?? 'Transfer Bank') ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <?= get_status_badge($ord['status']) ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="d-flex flex-column gap-1">
                                            <button class="btn btn-sm btn-gradient rounded-pill fw-semibold py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalStatus<?= $ord['id'] ?>">
                                                <i class="bi bi-patch-check me-1"></i> Konfirmasi
                                            </button>
                                            
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="invoice.php?id=<?= $ord['id'] ?>" target="_blank" class="btn btn-sm btn-gradient-outline rounded-pill py-1 px-2 flex-fill" title="Cetak Invoice">
                                                    <i class="bi bi-printer me-1"></i> Invoice
                                                </a>
                                                <button class="btn btn-sm btn-gradient-outline rounded-pill py-1 px-2 flex-fill" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $ord['id'] ?>" title="Detail Lengkap">
                                                    <i class="bi bi-eye"></i> Detail
                                                </button>
                                                <?php if ($is_superadmin): ?>
                                                    <a href="admin.php?hapus_pesanan=<?= $ord['id'] ?>" onclick="return confirm('Hapus permanen pesanan #ORD-<?= $ord['id'] ?>?')" class="btn btn-sm btn-outline-danger rounded-pill py-1 px-2" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- MODAL UBAH STATUS / KONFIRMASI -->
                                        <div class="modal fade text-start" id="modalStatus<?= $ord['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0">
                                                    <form method="POST">
                                                        <input type="hidden" name="update_status_pesanan" value="1">
                                                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                                        <div class="modal-header border-0 pb-0">
                                                            <h5 class="modal-title fw-bold">Konfirmasi Pesanan #ORD-<?= $ord['id'] ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body py-3">
                                                            <div class="p-3 rounded-3 mb-3 small" style="background: var(--bg-subtle);">
                                                                <strong>Pembeli:</strong> <?= htmlspecialchars($ord['nama_pembeli']) ?><br>
                                                                <strong>Total:</strong> <?= rupiah($ord['total_harga']) ?><br>
                                                                <strong>Status Saat Ini:</strong> <?= $ord['status'] ?>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Ubah Status Menjadi:</label>
                                                                <select name="status" class="form-select rounded-3" id="selectStatus<?= $ord['id'] ?>" onchange="toggleResi(<?= $ord['id'] ?>)">
                                                                    <option value="Pending" <?= $ord['status'] === 'Pending' ? 'selected' : '' ?>>⏳ Pending (Menunggu Konfirmasi)</option>
                                                                    <option value="Dikonfirmasi" <?= $ord['status'] === 'Dikonfirmasi' ? 'selected' : '' ?>>⚙️ Dikonfirmasi (Pesanan Diterima & Disiapkan)</option>
                                                                    <option value="Dikirim" <?= $ord['status'] === 'Dikirim' ? 'selected' : '' ?>>🚚 Dikirim (Dalam Perjalanan Kurir)</option>
                                                                    <option value="Selesai" <?= $ord['status'] === 'Selesai' ? 'selected' : '' ?>>✅ Selesai (Pesanan Telah Tiba)</option>
                                                                    <option value="Dibatalkan" <?= $ord['status'] === 'Dibatalkan' ? 'selected' : '' ?>>❌ Dibatalkan (Stok Otomatis Dikembalikan)</option>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3" id="resiGroup<?= $ord['id'] ?>" style="<?= $ord['status'] === 'Dikirim' ? '' : 'display:none;' ?>">
                                                                <label class="form-label fw-semibold small">Nomor Resi Pengiriman (Opsional):</label>
                                                                <input type="text" name="no_resi" class="form-control rounded-3" placeholder="Contoh: JNE123456789 / J&T..." value="<?= htmlspecialchars($ord['no_resi'] ?? '') ?>">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
                                                            <button type="submit" class="btn btn-gradient rounded-pill px-4 fw-bold">Simpan Konfirmasi</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- MODAL DETAIL PESANAN -->
                                        <div class="modal fade text-start" id="modalDetail<?= $ord['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold">Detail Pesanan #ORD-<?= $ord['id'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body py-3">
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <div class="p-3 rounded-3 h-100" style="background: var(--bg-subtle);">
                                                                    <h6 class="fw-bold small text-muted text-uppercase mb-2">Penerima & Pengiriman</h6>
                                                                    <div><strong>Nama:</strong> <?= htmlspecialchars($ord['nama_pembeli']) ?></div>
                                                                    <div><strong>No. WA:</strong> <?= htmlspecialchars($ord['no_hp']) ?></div>
                                                                    <div><strong>Alamat:</strong> <?= nl2br(htmlspecialchars($ord['alamat'])) ?></div>
                                                                    <?php if (!empty($ord['catatan'])): ?>
                                                                        <div class="mt-2 text-muted small fst-italic">Catatan: "<?= htmlspecialchars($ord['catatan']) ?>"</div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="p-3 rounded-3 h-100" style="background: var(--bg-subtle);">
                                                                    <h6 class="fw-bold small text-muted text-uppercase mb-2">Status Pembayaran</h6>
                                                                    <div><strong>Tanggal:</strong> <?= date('d F Y, H:i', strtotime($ord['tanggal'])) ?> WIB</div>
                                                                    <div><strong>Metode:</strong> <?= htmlspecialchars($ord['metode_pembayaran'] ?? 'Transfer Bank') ?></div>
                                                                    <div><strong>Status:</strong> <?= get_status_badge($ord['status']) ?></div>
                                                                    <?php if (!empty($ord['no_resi'])): ?>
                                                                        <div><strong>No. Resi:</strong> <?= htmlspecialchars($ord['no_resi']) ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <h6 class="fw-bold mb-2">Barang Elektronik Dipesan:</h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered align-middle small mb-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Produk</th>
                                                                        <th class="text-end">Harga Satuan</th>
                                                                        <th class="text-center">Qty</th>
                                                                        <th class="text-end">Subtotal</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($items_arr as $it): 
                                                                        $sub = $it['harga'] * $it['qty'];
                                                                    ?>
                                                                        <tr>
                                                                            <td>
                                                                                <div class="d-flex align-items-center gap-2">
                                                                                    <img src="<?= htmlspecialchars($it['gambar'] ?? 'https://placehold.co/50x50') ?>" class="rounded-2" width="40" height="40" style="object-fit: cover;">
                                                                                    <span class="fw-semibold"><?= htmlspecialchars($it['nama_produk'] ?? 'Item') ?></span>
                                                                                </div>
                                                                            </td>
                                                                            <td class="text-end"><?= rupiah($it['harga']) ?></td>
                                                                            <td class="text-center fw-bold"><?= $it['qty'] ?></td>
                                                                            <td class="text-end fw-bold"><?= rupiah($sub) ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                                <tfoot>
                                                                    <tr>
                                                                        <td colspan="3" class="text-end fw-bold">Total Pembayaran:</td>
                                                                        <td class="text-end fw-bold text-primary fs-6"><?= rupiah($ord['total_harga']) ?></td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <a href="invoice.php?id=<?= $ord['id'] ?>" target="_blank" class="btn btn-gradient-outline rounded-pill px-3">
                                                            <i class="bi bi-printer me-1"></i> Cetak Invoice
                                                        </a>
                                                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ============================================================== -->
    <!-- TAB 3: KELOLA PRODUK (CRUD LENGKAP) -->
    <!-- ============================================================== -->
    <?php elseif ($active_tab === 'produk'): ?>
        <?php
        $search_prod = $_GET['cari_produk'] ?? '';
        $filter_kat = $_GET['filter_kat'] ?? '';

        $where_p = [];
        if (!empty($search_prod)) {
            $kw = mysqli_real_escape_string($conn, $search_prod);
            $where_p[] = "(nama_produk LIKE '%$kw%' OR deskripsi LIKE '%$kw%')";
        }
        if (!empty($filter_kat)) {
            $fk = mysqli_real_escape_string($conn, $filter_kat);
            $where_p[] = "kategori = '$fk'";
        }

        $where_p_sql = count($where_p) > 0 ? "WHERE " . implode(' AND ', $where_p) : "";
        $prod_list_q = mysqli_query($conn, "SELECT * FROM products $where_p_sql ORDER BY id DESC");
        ?>

        <div class="card-themed p-3 mb-4">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="produk">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text border-end-0" style="background: var(--input-bg);"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="cari_produk" class="form-control border-start-0" placeholder="Cari nama produk / deskripsi..." value="<?= htmlspecialchars($search_prod) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="filter_kat" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori_list as $kat): ?>
                            <option value="<?= htmlspecialchars($kat) ?>" <?= $filter_kat === $kat ? 'selected' : '' ?>><?= htmlspecialchars($kat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-gradient rounded-pill flex-fill">Terapkan</button>
                    <?php if (!empty($search_prod) || !empty($filter_kat)): ?>
                        <a href="admin.php?tab=produk" class="btn btn-outline-secondary rounded-pill">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card-themed overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr class="small text-muted">
                            <th width="70" class="p-3">Gambar</th>
                            <th class="p-3">Nama Produk</th>
                            <th class="p-3">Kategori</th>
                            <th class="p-3">Harga</th>
                            <th class="p-3">Stok</th>
                            <th class="p-3 text-end" width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($prod_list_q) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-50"></i>
                                    Tidak ada produk yang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while ($p = mysqli_fetch_assoc($prod_list_q)): ?>
                                <tr>
                                    <td class="p-3">
                                        <img src="<?= htmlspecialchars($p['gambar']) ?>" class="product-thumb" alt="Product" onerror="this.src='https://placehold.co/60x60?text=No+Img'">
                                    </td>
                                    <td class="p-3">
                                        <div class="fw-bold fs-6"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 320px;">
                                            <?= htmlspecialchars($p['deskripsi'] ?? '-') ?>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="badge bg-primary bg-opacity-15 text-primary px-3 py-2 rounded-pill fw-semibold">
                                            <?= htmlspecialchars($p['kategori']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 fw-bold"><?= rupiah($p['harga']) ?></td>
                                    <td class="p-3">
                                        <?php if ($p['stok'] > 5): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold">
                                                <?= $p['stok'] ?> Unit
                                            </span>
                                        <?php elseif ($p['stok'] > 0): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill fw-bold">
                                                Sisa <?= $p['stok'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-3 py-2 rounded-pill fw-bold">Habis</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-sm btn-gradient-outline rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $p['id'] ?>">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </button>
                                            <?php if ($is_superadmin): ?>
                                                <a href="admin.php?hapus_produk=<?= $p['id'] ?>" onclick="return confirm('Hapus produk <?= htmlspecialchars(addslashes($p['nama_produk'])) ?>?')" class="btn btn-sm btn-outline-danger rounded-pill px-2">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <!-- MODAL EDIT PRODUK -->
                                        <div class="modal fade text-start" id="modalEdit<?= $p['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0">
                                                    <form method="POST" enctype="multipart/form-data">
                                                        <input type="hidden" name="edit_produk" value="1">
                                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                        <div class="modal-header border-0 pb-0">
                                                            <h5 class="modal-title fw-bold">Edit Produk #<?= $p['id'] ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body py-3">
                                                            <div class="row g-3">
                                                                <div class="col-md-8">
                                                                    <label class="form-label small fw-semibold">Nama Produk Elektronik</label>
                                                                    <input type="text" name="nama_produk" class="form-control rounded-3" value="<?= htmlspecialchars($p['nama_produk']) ?>" required>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="form-label small fw-semibold">Kategori</label>
                                                                    <select name="kategori" class="form-select rounded-3" required>
                                                                        <?php foreach ($kategori_list as $kat): ?>
                                                                            <option value="<?= htmlspecialchars($kat) ?>" <?= $p['kategori'] === $kat ? 'selected' : '' ?>><?= htmlspecialchars($kat) ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label small fw-semibold">Harga (Rp)</label>
                                                                    <input type="number" name="harga" class="form-control rounded-3" value="<?= $p['harga'] ?>" min="0" required>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label small fw-semibold">Jumlah Stok</label>
                                                                    <input type="number" name="stok" class="form-control rounded-3" value="<?= $p['stok'] ?>" min="0" required>
                                                                </div>
                                                                <div class="col-12">
                                                                    <label class="form-label small fw-semibold">Ganti Gambar Produk</label>
                                                                    <div class="d-flex align-items-center gap-3 mb-2">
                                                                        <img src="<?= htmlspecialchars($p['gambar']) ?>" class="rounded-3 border" width="60" height="60" style="object-fit: cover;">
                                                                        <div class="small text-muted">Gambar saat ini. Unggah file baru atau masukkan link URL baru.</div>
                                                                    </div>
                                                                    <input type="file" name="gambar_file" class="form-control rounded-3 mb-2" accept="image/*">
                                                                    <input type="url" name="gambar_url" class="form-control rounded-3" placeholder="Atau paste link URL gambar eksternal (https://...)" value="<?= (strpos($p['gambar'], 'uploads/') !== 0) ? htmlspecialchars($p['gambar']) : '' ?>">
                                                                </div>
                                                                <div class="col-12">
                                                                    <label class="form-label small fw-semibold">Deskripsi / Spesifikasi Produk</label>
                                                                    <textarea name="deskripsi" class="form-control rounded-3" rows="3"><?= htmlspecialchars($p['deskripsi'] ?? '') ?></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-gradient rounded-pill px-4 fw-bold">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ============================================================== -->
    <!-- TAB 4: MANAJEMEN USER & ROLE ADMINISTRATOR (SUPER ADMIN ONLY) -->
    <!-- ============================================================== -->
    <?php elseif ($active_tab === 'users' && $is_superadmin): ?>
        <div class="card-themed p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-people-fill text-danger me-2"></i>Daftar Pengguna Administrator & Hak Akses Role</h5>
                    <p class="text-muted small mb-0">Kelola kredensial, peran hak akses (*Super Admin, Admin Penjualan, Admin Gudang*), serta status keaktifan akun.</p>
                </div>
                <button class="btn btn-gradient rounded-pill btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahAdmin">
                    <i class="bi bi-person-plus-fill me-1"></i> Tambah Admin Baru
                </button>
            </div>
        </div>

        <div class="card-themed overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr class="small text-muted">
                            <th class="p-3">Pengguna</th>
                            <th class="p-3">Peran / User Role</th>
                            <th class="p-3">Status Akun</th>
                            <th class="p-3">Terakhir Login</th>
                            <th class="p-3">Terdaftar Sejak</th>
                            <th class="p-3 text-end" width="160">Aksi Otoritas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $admins_q = mysqli_query($conn, "SELECT * FROM admins ORDER BY id ASC");
                        while ($adm = mysqli_fetch_assoc($admins_q)):
                        ?>
                            <tr>
                                <td class="p-3">
                                    <div class="fw-bold fs-6"><?= htmlspecialchars($adm['nama_lengkap']) ?></div>
                                    <small class="text-muted font-monospace">@<?= htmlspecialchars($adm['username']) ?></small>
                                    <?php if ($adm['id'] == $current_user_id): ?>
                                        <span class="badge bg-success-subtle text-success ms-1 small">Sedang Login (Anda)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <?php if ($adm['role'] === 'Super Admin'): ?>
                                        <span class="role-pill bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25">SUPER ADMIN</span>
                                    <?php elseif ($adm['role'] === 'Admin Penjualan'): ?>
                                        <span class="role-pill bg-primary bg-opacity-20 text-primary border border-primary border-opacity-25">ADMIN PENJUALAN</span>
                                    <?php else: ?>
                                        <span class="role-pill bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25">ADMIN GUDANG</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <?php if ((int)($adm['status_aktif'] ?? 1) === 1): ?>
                                        <span class="badge bg-success rounded-pill px-3 py-1">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill px-3 py-1">Dinonaktifkan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 small text-muted">
                                    <?= !empty($adm['terakhir_login']) ? date('d M Y, H:i', strtotime($adm['terakhir_login'])) : '<span class="fst-italic">Belum pernah</span>' ?>
                                </td>
                                <td class="p-3 small text-muted">
                                    <?= date('d M Y', strtotime($adm['created_at'] ?? 'now')) ?>
                                </td>
                                <td class="p-3 text-end">
                                    <?php if ($adm['id'] != $current_user_id): ?>
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="admin.php?tab=users&toggle_status_admin=<?= $adm['id'] ?>" class="btn btn-sm btn-gradient-outline rounded-pill px-2 py-1" title="Ubah Status Aktif/Nonaktif">
                                                <i class="bi <?= (int)($adm['status_aktif'] ?? 1) === 1 ? 'bi-toggle-on text-success fs-5' : 'bi-toggle-off text-muted fs-5' ?>"></i>
                                            </a>
                                            <a href="admin.php?tab=users&hapus_admin=<?= $adm['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus user @<?= htmlspecialchars($adm['username']) ?>?')" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Hapus User">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="small text-muted fst-italic">Akun Anda</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL TAMBAH ADMIN BARU -->
        <div class="modal fade" id="modalTambahAdmin" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0">
                    <form method="POST">
                        <input type="hidden" name="tambah_admin" value="1">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill text-danger me-2"></i>Tambah Pengguna Admin Baru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control rounded-3" placeholder="Contoh: Rian Pratama" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Username Login</label>
                                <input type="text" name="username" class="form-control rounded-3" placeholder="Contoh: rian_admin" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Password Akun</label>
                                <input type="password" name="password" class="form-control rounded-3" placeholder="Ketik kata sandi..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Pilih User Role (Hak Akses)</label>
                                <select name="role" class="form-select rounded-3" required>
                                    <option value="Admin Penjualan">Admin Penjualan (Konfirmasi pesanan, invoice & WhatsApp)</option>
                                    <option value="Admin Gudang">Admin Gudang (Kelola stok & logistik pengiriman)</option>
                                    <option value="Super Admin">Super Admin (Hak Akses Penuh Termasuk Kelola Admin)</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-gradient rounded-pill px-4 fw-bold">Buat Akun Admin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- MODAL TAMBAH PRODUK BARU -->
<div class="modal fade" id="modalTambahProduk" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tambah_produk" value="1">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Tambah Produk Elektronik Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nama Produk Elektronik</label>
                            <input type="text" name="nama_produk" class="form-control rounded-3" placeholder="Contoh: Smart TV 55 Inch 4K UHD" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kategori</label>
                            <select name="kategori" class="form-select rounded-3" required>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?= htmlspecialchars($kat) ?>"><?= htmlspecialchars($kat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Harga Satuan (Rp)</label>
                            <input type="number" name="harga" class="form-control rounded-3" placeholder="Contoh: 3500000" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jumlah Stok Awal</label>
                            <input type="number" name="stok" class="form-control rounded-3" placeholder="Contoh: 15" min="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Foto / Gambar Produk</label>
                            <input type="file" name="gambar_file" class="form-control rounded-3 mb-2" accept="image/*">
                            <input type="url" name="gambar_url" class="form-control rounded-3" placeholder="Atau gunakan URL gambar web">
                            <small class="text-muted">Bisa upload foto dari perangkat atau paste link gambar.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Deskripsi & Spesifikasi Produk</label>
                            <textarea name="deskripsi" class="form-control rounded-3" rows="3" placeholder="Fitur utama, spesifikasi teknis, garansi..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-gradient rounded-pill px-4 fw-bold">Tambah Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleResi(orderId) {
    const select = document.getElementById('selectStatus' + orderId);
    const resiGroup = document.getElementById('resiGroup' + orderId);
    if (select && resiGroup) {
        if (select.value === 'Dikirim') {
            resiGroup.style.display = 'block';
        } else {
            resiGroup.style.display = 'none';
        }
    }
}
</script>
</body>
</html>
