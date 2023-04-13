#FileAdmin
Manajer file PHP ringan dengan pengalaman luar biasa

Proyek ini dikembangkan dan dikelola oleh NLR DevTeam // [Bergabunglah dengan kami](https://join.nlrdev.top)

Pendahulu dari proyek ini adalah [SimAdmin](https://github.com/YanJi314/SimAdmin), tetapi tidak menggunakan kodenya

Fungsi dasar dari proyek ini telah dikembangkan dan memasuki tahap iterasi lambat. Jika Anda memiliki umpan balik bug atau saran fungsi baru, kirimkan Masalah~

Jika proyek ini bermanfaat bagi Anda, klik Bintang dan lanjutkan

## fitur
- Selesaikan semua operasi file umum secara langsung menggunakan browser
- Built-in [tombol pintas] (#gunakan) yang sesuai dengan kebiasaan pengoperasian Windows
- Tidak ada iklan, tidak ada statistik, tidak ada pembayaran
- Mendukung pemeriksaan pembaruan otomatis dan pembaruan ontologi satu-klik
- Ringan, tidak memerlukan konfigurasi ekstensif; file tunggal, tidak membuat file lain di dalam lingkungan
- Editor kuat bawaan yang disediakan oleh ACE-Editor
- Sepenuhnya otomatis dan non-intrusif [kebingungan kode Js] (kebingungan #Js), secara efektif mencegah kode sumber dicuri
- Mungkin pengelola file termudah untuk digunakan di ponsel

## Kompatibilitas
- Server: sangat kompatibel dengan PHP 7.x - 8.x.
- Browser: Hanya kompatibel dengan Google Chrome / Microsoft Edge versi terbaru.

Pengujian sebenarnya kompatibel dengan sebagian besar host virtual distribusi Pagoda dan host virtual Kangle, dan beberapa lingkungan khusus tanpa sistem file nyata mungkin tidak dapat dijalankan.
## Install
- Unduh versi instalasi FileAdmin langsung dari [Hydrosoft API](https://api.simsoft.top/fileadmin/download/). **Anda juga dapat mengunduh versi lain selain versi penginstalan, lihat bagian "[versi](#versi)" dari artikel ini untuk detailnya**
- Unggah fileadmin.php yang baru saja Anda unduh langsung ke host Anda.
- Anda dapat mengganti nama file jika menurut Anda nama file aslinya tidak aman. Cobalah untuk menginstalnya di direktori root situs web Anda.
- **[Penting] Buka file ini, ubah variabel $PASSWORD pada baris pertama, dan masukkan kata sandi Anda sendiri. Kegagalan untuk mengubah pengaturan ini bisa berbahaya karena orang lain dapat dengan bebas melihat dan memodifikasi file Anda. **

## menggunakan
- FileAdmin mendefinisikan berbagai tombol pintas yang nyaman bagi pengguna.

|Tombol pintasan|Halaman terkait|Fungsi|
|--|--|--|
|/|Halaman masukan kata sandi|fokus kotak masukan kata sandi|
|/|Halaman Manajemen Berkas|Edit Jalur Berkas|
|Ctrl+A|Halaman manajemen file|Pilih semua file|
|Ctrl+C|Halaman manajemen file|Salin file yang dipilih|
|Ctrl+X|Halaman manajemen file|Potong file yang dipilih|
|Ctrl+V|Halaman manajemen file|Ketika ada file di clipboard panel (yaitu, ketika tombol "Tempel" ditampilkan di menu), tempel file yang sebelumnya disalin/dipotong di panel; jika tidak ada item di papan klip panel, Dapatkan file secara otomatis (jika ada) dari papan klip sistem dan unggah|
|Hapus|Halaman manajemen file|Hapus file yang dipilih|
|F5|Halaman manajemen file|Refresh daftar file|
|F2|Halaman manajemen file|Ubah nama file yang dipilih|
|ESC|Halaman manajemen file|Kembali ke direktori induk|
|Ctrl+S|Editor Teks|Simpan Berkas|
|Ctrl+Z|Editor Teks|Urungkan Tindakan Terakhir|
|Ctrl+Y|Editor Teks|Kembalikan Perubahan yang Dibatalkan|
|Ctrl+F|Editor Teks|Temukan atau Ganti Konten|
|F5|Editor Teks|Segarkan Editor Teks|
|ESC|Editor Teks|Keluar dari Editor Teks|

- FileAdmin juga dilengkapi dengan beberapa pintasan mouse yang membantu meningkatkan efisiensi

|Operasi Mouse|Fungsi|
|--|--|
|Klik file|Buka file ini saat tidak dalam mode yang dipilih; centang/hapus centang file ini saat dalam mode yang dipilih|
|Klik kanan pada file|Ketika mode yang dipilih tidak dimasukkan, pilih file ini dan masuk ke mode yang dipilih|
|Tekan dan geser mouse di daftar file|Pilih beberapa atau batalkan pilihan file yang digeser mouse|
|Klik mouse di area kosong|Hapus centang semua file|

- FileAdmin memiliki fungsi bawaan untuk mendapatkan kode sumber dari gudang ini dan memperbarui program ontologi secara otomatis. Klik kata "FileAdmin" di kiri atas antarmuka apa pun untuk memeriksa pembaruan. Host/server di beberapa wilayah daratan mungkin tidak mendukung fitur ini.

## Kebingungan Js
FileAdmin memiliki obfuscator Js (Javascript Obfuscator) bawaan yang kuat, yang memudahkan pengembang untuk melindungi kode sumber yang mereka kembangkan. Silakan baca instruksi berikut dengan seksama sebelum mengaktifkan fungsi ini:
- Kebingungan Js akan menggandakan penggunaan penyimpanan file Js Anda, dan dapat menyebabkan penyimpanan file lambat
-Js kebingungan dapat menyebabkan beberapa kode gagal dijalankan, harap pastikan untuk mengujinya sendiri sepenuhnya
- Mungkin sulit bagi Anda untuk men-debug kode Js yang disamarkan
- FA tidak memiliki kemampuan untuk melakukan de-obfuscate, jadi setelah Js obfuscation diaktifkan, file .fajs akan dibuat di direktori saat ini untuk menyimpan file sumber Js
- Pastikan menggunakan firewall untuk memblokir akses orang lain ke file .fajs
- Jangan langsung memodifikasi, memindahkan, atau menghapus file .fajs, jika tidak, Anda tidak akan dapat mengedit kode sumber Js

Terlampir adalah metode konfigurasi firewall umum:
- Aturan penyaringan URL firewall gratis Pagoda: `\.(fajs)`
- Aturan pemblokiran firewall Cloudflare: `(http.request.full_uri berisi ".fajs")`

Konfigurasi firewall lainnya sama

## situs web resmi
- Situs web resmi: https://fa.nlrdev.top/
- Pendahuluan: https://www.bilibili.com/video/BV1XZ4y1m7WK
- Donasi: https://i.simsoft.top/#donasi

## Versi: sekarang

Saat ini ada tiga versi FileAdmin yang terbuka untuk instalasi.
- Versi terinstal: dirancang untuk memungkinkan Anda mendapatkan ukuran kode terkecil. [[Klik untuk mendownload](https://api.simsoft.top/fileadmin/download/)]
- Rilis pemeliharaan: Dapatkan kode sumber langsung dari Github, termasuk komentar dan lekukan lengkap. [[Klik untuk mendownload](https://api.simsoft.top/fileadmin/download/maintain.php)]
- Versi terinstal: Versi pengembangan real-time resmi Hydrosoft, tempat Anda dapat mempelajari kemajuan pengembangan terbaru. [[Klik untuk mendownload](https://api.simsoft.top/fileadmin/download/dev.php)]

Tips: Apa pun versi yang Anda pilih, pembaruan otomatis tersemat tunduk pada rilis versi yang diinstal, dan akan menggunakan pembaruan versi yang diinstal untuk mencakup versi lainnya. Jika Anda perlu menggunakan versi non-instal untuk waktu yang lama, perbarui secara manual langsung dari saluran di atas alih-alih menggunakan pembaruan otomatis.

## Hak Cipta & Penafian
- Program ini bersumber terbuka menggunakan protokol AGPL-3.0. Setiap karya rilis kedua harus bersumber terbuka di bawah protokol yang sama. Penggunaan komersial tidak disarankan.
- Pengembang tidak bertanggung jawab atas kerugian yang disebabkan oleh penyalahgunaan program ini.

## bersyukur
- [Star Cloud](https://starxn.com) menyediakan dukungan lingkungan pengembangan
- [XIAYM](https://github.com/XIAYM-gh) memberikan dukungan lingkungan pengembangan
- [Javascript Obfuscator](https://obfuscator.io) memberikan dukungan teknis kebingungan Js
