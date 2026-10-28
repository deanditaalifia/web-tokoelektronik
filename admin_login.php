<?php
require_once 'koneksi.php';

// Jika sudah login, langsung alihkan ke dashboard admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error = '';
$unauthorized_notice = false;

// Cek jika dialihkan karena mencoba akses dashboard tanpa izin
if (isset($_GET['access_denied']) || isset($_GET['unauthorized'])) {
    $unauthorized_notice = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Silakan isi username dan password Anda!';
    } else {
        $username_clean = mysqli_real_escape_string($conn, $username);
        $query = mysqli_query($conn, "SELECT * FROM admins WHERE username = '$username_clean' LIMIT 1");

        if ($query && mysqli_num_rows($query) === 1) {
            $admin = mysqli_fetch_assoc($query);

            // Cek status keaktifan akun
            if (isset($admin['status_aktif']) && (int)$admin['status_aktif'] === 0) {
                $error = 'Akses Ditolak: Akun Anda sedang dinonaktifkan oleh Super Admin.';
            } else {
                // Verifikasi password (hash atau fallback admin123 untuk dev)
                if (password_verify($password, $admin['password']) || $password === 'admin123') {
                    // Update timestamp terakhir login
                    $aid = $admin['id'];
                    mysqli_query($conn, "UPDATE admins SET terakhir_login = NOW() WHERE id = $aid");

                    // Set Sesi Autentikasi & Role Berbasis Hak Akses
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_user'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['nama_lengkap'];
                    $_SESSION['admin_role'] = $admin['role'] ?? 'Admin Penjualan';

                    set_flash('success', "Autentikasi Berhasil! Selamat datang, {$admin['nama_lengkap']} [{$admin['role']}].");
                    header('Location: admin.php');
                    exit;
                } else {
                    $error = 'Autentikasi Gagal: Password yang Anda masukkan tidak sesuai.';
                }
            }
        } else {
            $error = 'Autentikasi Gagal: Username admin tidak terdaftar dalam sistem.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator & User Role - ElectroMart</title>
    <script>
        (function() {
            var t = localStorage.getItem('electromart_theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/theme.js"></script>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: var(--bg-body);
        }
        .login-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 480px;
            padding: 2.5rem;
            position: relative;
        }
        .theme-toggle-floating {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .logo-icon {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            color: white;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.35);
        }
        .role-badge-pill {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="theme-toggle-floating">
        <button class="theme-toggle-btn" type="button" aria-label="Ganti Tema">
            <i class="bi bi-moon-stars-fill text-primary"></i>
        </button>
    </div>

    <!-- LOGO & TITLE -->
    <div class="text-center mb-4">
        <div class="logo-icon bg-gradient-primary mb-3">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h3 class="fw-bold mb-1">
            <span class="gradient-text">ElectroMart</span> Admin
        </h3>
        <p class="text-muted small mb-0">Autentikasi Akses & Pengendalian Hak Akses Role</p>
    </div>

    <!-- ALERT JIKA DITOLAK KARENA MENCOBA AKSES ADMIN TANPA LOGIN -->
    <?php if ($unauthorized_notice): ?>
        <div class="alert alert-danger d-flex align-items-start rounded-3 p-3 small mb-3 border-0 shadow-sm" role="alert">
            <i class="bi bi-shield-x-fill text-danger fs-5 me-2 flex-shrink-0"></i>
            <div>
                <strong>Akses Dibatasi (Unauthorized 401/403):</strong><br>
                Area Dashboard Admin memerlukan hak akses khusus. Silakan login menggunakan akun administrator berwenang di bawah ini.
            </div>
        </div>
    <?php endif; ?>

    <!-- ALERT ERROR LOGIN -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-warning d-flex align-items-center rounded-3 py-2 px-3 small mb-3 border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-6"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <!-- FORM LOGIN (BERSIH TANPA AUTO-FILL) -->
    <form method="POST">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Username Akun</label>
            <div class="input-group">
                <span class="input-group-text border-end-0 text-muted" style="background: var(--input-bg);">
                    <i class="bi bi-person"></i>
                </span>
                <input type="text" id="inputUsername" name="username" class="form-control border-start-0" placeholder="Ketik username..." required autofocus autocomplete="off">
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold mb-0">Password Akun</label>
                <small class="text-muted" style="font-size: 0.75rem;">Autentikasi Sandi</small>
            </div>
            <div class="input-group">
                <span class="input-group-text border-end-0 text-muted" style="background: var(--input-bg);">
                    <i class="bi bi-lock"></i>
                </span>
                <input type="password" id="inputPass" name="password" class="form-control border-start-0 border-end-0" placeholder="Ketik password..." required autocomplete="off">
                <button type="button" class="btn btn-outline-secondary border-start-0 text-muted" onclick="togglePass()">
                    <i class="bi bi-eye" id="iconEye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-gradient w-100 mb-3 py-2 fw-bold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Verifikasi & Masuk Dashboard
        </button>

        <!-- KOTAK INFORMASI AKUN UJI COBA USER ROLE -->
        <div class="p-3 rounded-4 mb-3 border" style="background: var(--bg-subtle); border-color: var(--border-color) !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-primary">
                    <i class="bi bi-person-badge-fill me-1"></i> Pilihan Akun Uji Coba Multi-Role:
                </span>
                <small class="text-muted" style="font-size: 0.72rem;">Klik untuk isi</small>
            </div>

            <div class="d-flex flex-column gap-2 small">
                <!-- Role 1: Super Admin -->
                <div class="d-flex justify-content-between align-items-center p-2 rounded-3 border" style="background: var(--bg-surface); cursor: pointer;" onclick="fillCredentials('superadmin', 'admin123')">
                    <div>
                        <span class="role-badge-pill bg-danger bg-opacity-15 text-danger">SUPER ADMIN</span>
                        <div class="small fw-semibold mt-1">Username: <code>superadmin</code></div>
                    </div>
                    <span class="badge bg-primary rounded-pill btn-sm">Gunakan <i class="bi bi-arrow-right"></i></span>
                </div>

                <!-- Role 2: Admin Penjualan -->
                <div class="d-flex justify-content-between align-items-center p-2 rounded-3 border" style="background: var(--bg-surface); cursor: pointer;" onclick="fillCredentials('admin', 'admin123')">
                    <div>
                        <span class="role-badge-pill bg-primary bg-opacity-15 text-primary">ADMIN PENJUALAN</span>
                        <div class="small fw-semibold mt-1">Username: <code>admin</code></div>
                    </div>
                    <span class="badge bg-primary rounded-pill btn-sm">Gunakan <i class="bi bi-arrow-right"></i></span>
                </div>

                <!-- Role 3: Admin Gudang -->
                <div class="d-flex justify-content-between align-items-center p-2 rounded-3 border" style="background: var(--bg-surface); cursor: pointer;" onclick="fillCredentials('gudang', 'admin123')">
                    <div>
                        <span class="role-badge-pill bg-warning bg-opacity-15 text-warning">ADMIN GUDANG</span>
                        <div class="small fw-semibold mt-1">Username: <code>gudang</code></div>
                    </div>
                    <span class="badge bg-primary rounded-pill btn-sm">Gunakan <i class="bi bi-arrow-right"></i></span>
                </div>
            </div>
            <div class="text-muted text-center mt-2" style="font-size: 0.72rem;">
                Semua akun demo di atas menggunakan password: <code>admin123</code>
            </div>
        </div>

        <div class="text-center">
            <a href="index.php" class="text-muted text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Toko Elektronik
            </a>
        </div>
    </form>
</div>

<script>
function togglePass() {
    const input = document.getElementById('inputPass');
    const icon = document.getElementById('iconEye');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function fillCredentials(user, pass) {
    document.getElementById('inputUsername').value = user;
    document.getElementById('inputPass').value = pass;
    document.getElementById('inputUsername').focus();
}
</script>
</body>
</html>
