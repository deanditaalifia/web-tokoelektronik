<?php
require_once 'koneksi.php';

unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_user']);
unset($_SESSION['admin_name']);

set_flash('info', 'Anda telah berhasil keluar dari sistem admin.');
header('Location: admin_login.php');
exit;
