-- Database: db_elektronik
-- Toko Elektronik Modern (ElectroMart)
-- Native PHP & MySQL

CREATE DATABASE IF NOT EXISTS `db_elektronik`;
USE `db_elektronik`;

-- 1. Tabel Admin & User Role
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'Admin Penjualan',
    `status_aktif` TINYINT(1) DEFAULT 1,
    `terakhir_login` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Akun admin default multi-role (password: admin123)
INSERT INTO `admins` (`id`, `username`, `password`, `nama_lengkap`, `role`, `status_aktif`) VALUES
(1, 'superadmin', '$2y$10$wB4b7wW2xRzM5mUa7rR49edv49xQ2jU2B6nI6oTq6OqGZlqE1L9qy', 'Direktur / Super Admin', 'Super Admin', 1),
(2, 'admin', '$2y$10$wB4b7wW2xRzM5mUa7rR49edv49xQ2jU2B6nI6oTq6OqGZlqE1L9qy', 'Staff Admin Penjualan', 'Admin Penjualan', 1),
(3, 'gudang', '$2y$10$wB4b7wW2xRzM5mUa7rR49edv49xQ2jU2B6nI6oTq6OqGZlqE1L9qy', 'Koordinator Gudang & Logistik', 'Admin Gudang', 1)
ON DUPLICATE KEY UPDATE `role`=VALUES(`role`);

-- 2. Tabel Produk
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_produk` VARCHAR(150) NOT NULL,
    `kategori` VARCHAR(50) NOT NULL,
    `harga` INT NOT NULL,
    `stok` INT NOT NULL DEFAULT 0,
    `gambar` VARCHAR(255) NOT NULL,
    `deskripsi` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Pesanan (Orders)
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_pesanan` VARCHAR(50) NULL,
    `nama_pembeli` VARCHAR(100) NOT NULL,
    `no_hp` VARCHAR(20) NOT NULL,
    `alamat` TEXT NOT NULL,
    `metode_pembayaran` VARCHAR(50) DEFAULT 'Transfer Bank',
    `catatan` TEXT NULL,
    `total_harga` INT NOT NULL,
    `status` VARCHAR(30) DEFAULT 'Pending',
    `no_resi` VARCHAR(100) NULL,
    `tanggal` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Detail Item Pesanan (Order Items)
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `qty` INT NOT NULL,
    `harga` INT NOT NULL,
    INDEX (`order_id`),
    INDEX (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
