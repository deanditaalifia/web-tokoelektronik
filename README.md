# ElectroMart - Toko Elektronik Online Modern (PHP Native & MySQL)

Website e-commerce penjualan alat dan perangkat elektronik modern berbasis **PHP Native** dan **MySQL** dengan arsitektur **CRUD Lengkap** untuk produk dan pesanan.

---

## 🚀 Cara Mengakses Website

Pastikan Laragon / XAMPP Anda aktif (Apache & MySQL):
1. **Halaman Beranda Pembeli:**  
   👉 [http://localhost/toko-elektronik/](http://localhost/toko-elektronik/)
2. **Halaman Lacak Pesanan:**  
   👉 [http://localhost/toko-elektronik/pesanan.php](http://localhost/toko-elektronik/pesanan.php)
3. **Halaman Dashboard Admin:**  
   👉 [http://localhost/toko-elektronik/admin.php](http://localhost/toko-elektronik/admin.php)

---

## 🔐 Kredensial Pengujian Multi-Role Admin (Password: `admin123`)

Sistem login dilengkapi kontrol otorisasi hak akses ketat (*Role-Based Access Control*):
1. **Super Admin** (Hak Akses Penuh: Kelola Pesanan, CRUD Produk, & Manajemen Role User):  
   • **Username:** `superadmin` | **Password:** `admin123`
2. **Admin Penjualan** (Konfirmasi Pesanan, Cetak Invoice, Hubungi WA Pembeli):  
   • **Username:** `admin` | **Password:** `admin123`
3. **Admin Gudang** (Kelola Stok Produk, Inventaris & Input Resi Pengiriman):  
   • **Username:** `gudang` | **Password:** `admin123`

---

## 🎓 Modul Khusus Perkuliahan: Struktur Data & Algoritma (`algoritma.php`)

Disediakan halaman laboratorium interaktif khusus untuk memenuhi persyaratan tugas kuliah / penilaian dosen:

### 1. Materi Struktur Data:
- **Array:** Single-dimensional array, Associative array produk, dan Multidimensional array keranjang belanja.
- **Pointer (Reference `&`):** Implementasi manipulasi langsung alamat memori variabel produk tanpa *copy* menggunakan parameter referensi `&$itemProduk`. Dilengkapi fitur uji coba live diskon langsung di memori.
- **Struct:** Representasi *Typed Object / Data Class* (`ProdukStruct`) dengan tipe data kuat (*strongly typed*) untuk memodelkan entitas produk elektronik secara terstruktur.

### 2. Tiga (3) Tipe Algoritma Sorting + Live Execution Log:
1. **Bubble Sort:** Pengurutan berulang dengan menukar pasangan elemen bersebelahan jika tidak terurut, dilengkapi *early exit detection*.
2. **Selection Sort:** Mencari nilai minimum/maksimum pada setiap iterasi lalu menukarnya ke posisi awal yang belum terurut.
3. **Insertion Sort:** Menyisipkan elemen kartu satu per satu ke bagian sub-array yang telah terurut.
- **Fitur Unggulan:** Pengguna dapat memilih kolom pengurutan (**Harga**, **Stok**, atau **Nama Produk**), memilih arah urutan (**Ascending** / **Descending**), dan melihat **Log Langkah Demi Langkah (Step-by-Step Terminal Log)** secara transparan!

### 3. Algoritma Searching + Live Search Log:
1. **Linear Search (Sequential):** Pencarian produk sekuensial dari indeks 0 hingga N-1 berdasarkan kata kunci nama produk / kategori dengan pencatatan jumlah perbandingan (*comparison count*).
2. **Binary Search (Divide & Conquer):** Pencarian efisien O(log N) pada data yang telah terurut berdasarkan harga dengan log interval titik tengah `low`, `mid`, dan `high`.

---

## 🌟 Fitur Utama

### 1. Halaman Beranda Pembeli (`index.php`)
- **Katalog Produk Elektronik:** Tampilan kartu produk modern dengan foto, badge kategori, indikator sisa stok, rating bintang, dan format harga Rupiah.
- **Filter & Pencarian:** Filter berdasarkan 8 kategori elektronik (Smartphone, Laptop & PC, TV & Audio, Konsol Gaming, Drone, Elektronik Rumah, Aksesoris PC, Komponen IoT) dan pencarian teks cepat.
- **Tombol "+ Keranjang":** Menambahkan item ke keranjang belanja tanpa reload yang mengganggu.
- **Tombol "Beli":** Menambahkan item dan otomatis membuka alur Checkout untuk pembelian cepat.
- **Modal Detail Produk:** Menampilkan spesifikasi lengkap, stok ketersediaan, dan pilihan jumlah beli.
- **Keranjang & Checkout Terintegrasi:**
  - Tambah, ubah jumlah (+/-), atau hapus barang di keranjang.
  - Form pengiriman lengkap (Nama, No WhatsApp, Alamat Lengkap).
  - Pilihan metode pembayaran (Transfer BCA/Mandiri/BRI, QRIS / E-Wallet, COD Bayar di Tempat).
  - Generator kode pesanan unik (contoh: `#ELK-20261028-123`).

### 2. Halaman Lacak Pesanan & Invoice (`pesanan.php` & `invoice.php`)
- Pembeli dapat melacak pesanan menggunakan No. WhatsApp atau ID Pesanan.
- **Visual Stepper Timeline:** Menampilkan 4 tahap perjalanan pesanan (Pesanan Masuk ⏳ -> Dikonfirmasi ⚙️ -> Dikirim 🚚 -> Selesai ✅).
- **Cetak Faktur / Invoice:** Layout nota faktur profesional siap cetak (`window.print()`).
- Tombol langsung terhubung ke WhatsApp CS Toko.

### 3. Dashboard Admin (`admin.php`)
- **Statistik Penjualan:** Total Omset (Pendapatan), Total Pesanan Masuk, Pesanan Menunggu Konfirmasi (Pending), dan Peringatan Stok Menipis.
- **Pengecekan & Konfirmasi Pesanan:**
  - Filter pesanan berdasarkan status (Semua, Pending, Dikonfirmasi, Dikirim, Selesai, Dibatalkan).
  - Modal detail pesanan (daftar barang yang dipesan, alamat kirim, kontak pembeli).
  - **Ubah & Konfirmasi Status Pesanan:** Ubah status menjadi Dikonfirmasi, Dikirim (lengkap dengan input nomor resi pengiriman), Selesai, atau Dibatalkan.
  - **Auto-Restorasi Stok:** Jika pesanan dibatalkan, sistem secara otomatis mengembalikan stok produk ke gudang!
  - Tombol **Hubungi Pembeli via WhatsApp** dengan template pesan otomatis.
- **CRUD Produk Elektronik Lengkap:**
  - **Create:** Tambah produk baru dengan upload file gambar (ke folder `uploads/`) atau gunakan URL gambar web.
  - **Read:** Tabel daftar produk dengan thumbnail, badge kategori, dan status stok.
  - **Update:** Edit nama produk, kategori, harga, stok, deskripsi, dan perbarui foto produk.
  - **Delete:** Hapus produk beserta file gambarnya.
