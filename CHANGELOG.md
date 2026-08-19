# Riwayat Lengkap Pengembangan Sistem SIMCYBER

Dokumen ini merangkum seluruh rekam jejak pengembangan, perancangan fitur, serta perbaikan sistem (UI/UX dan Backend) pada platform _Phishing Simulation_ (SIMCYBER) terhitung sejak awal pencetusan hingga iterasi terbaru.

---

## 1. Pembangunan Fondasi Penelitian & Konseptualisasi Sistem

Pada awal proyek, platform ini tidak dibangun sekadar sebagai produk IT, melainkan sebagai instrumen penelitian ilmiah berbasis pendekatan **KAB (Knowledge, Attitude, Behavior)** khusus untuk audiens siswa sekolah menengah.

- **Skema Simulasi:** Merumuskan berbagai skenario jebakan phishing (seperti _email tiruan_ dan _pesan chat palsu_).
- **Pengukuran Perilaku:** Membangun instrumen sensor di dalam kode agar dapat mengukur waktu respons (response time), keputusan akhir siswa (mengeklik, memasukkan data, atau menolak), dan variabel tekanan waktu (time pressure).
- **Alur Responden (User Flow):** Merancang perjalanan sistematis di mana siswa tidak merasa terawasi secara langsung, guna menghasilkan data _Behavior_ senyata mungkin.

## 2. Sistem Integrasi Kuesioner (Google Form & Tally.so)

- **Desain Pengumpulan Data:** Sistem SIMCYBER dirangkai agar siswa yang selesai mengikuti simulasi praktis (praktik) langsung diwajibkan menjawab serangkaian soal teoretis (KAB).
- **Tracking Berbasis Session ID:** Merancang sistem kode identifikasi acak (`session_token`) untuk tiap responden. Token ini mengikat aktivitas praktik siswa di website dengan hasil ujian teoretis mereka.
- **Transisi ke Tally.so:** Walau sempat menggunakan Google Forms dengan berbagai _entry link_ yang padat, pengembangan akhirnya dialihkan ke **Tally.so** karena dukungannya yang superior terhadap fitur _Hidden Fields_, memungkinkan sistem menyisipkan _Session Token_ siswa secara rahasia dan elegan.

## 3. Pengembangan Anatomi Frontend & Komunikasi Email

- **Rekayasa Ulang Visi Antarmuka:** Mengatasi perbedaan tampilan (bug layout) pada halaman Monitor Penelitian ketika dipublikasikan ke server _Hosting_. Perbaikan ini mencakup penyempurnaan fitur _Tab Navigation_, pemunculan tombol WhatsApp, hingga penyusunan rapi tata letak `monitor.blade.php`.
- **Desain Kartu Statistik & Interaksi:** Memberikan _styling_ CSS khusus untuk membuat Monitor Card beranimasi, rapi secara visual, dan memberikan kesan elegan khas _modern cybersecurity dashboard_.
- **Format Surel Phishing Pure HTML:** Merombak kode _email template_ yang awalnya terstruktur dari format _Markdown/Blade_ rawan patah, menjadi format HTML Murni (_Pure HTML Email_). Ini memaksa tampilan email phishing jebakan (seperti email layanan IT bodong) tampil 100% konsisten, meyakinkan, dan profesional pada perangkat korban.
- **Sistem Popup Modern:** Menambahkan _SweetAlert2_ untuk notifikasi agar semua aksi terlihat interaktif.

## 4. Keamanan Akses Peneliti (Researcher Security)

Mengingat ini adalah platform sensitif yang dapat menembakkan email simulasi jebakan ke ratusan responden:

- **Research Key (Kunci Keamanan):** Mengimplementasikan sistem **Double Verification (Sudo Mode)**. Setiap kali Peneliti mau mengakses data rahasia atau menjalankan pengiriman (_Send Simulation_), mereka wajib memasukkan _Research Key_.
- **Refactoring Arsitektur UI Kunci:** Memindahkan form pembuatan dan perubahan _Research Key_ dari halaman `Kirim Simulasi` (`send-simulation.tsx`) agar antarmukanya lebih sederhana. Menu pembuatan kunci kini dipusatkan ke dalam halaman manajemen profil utama (`Pengaturan Akun > Security Settings`), sehingga halaman pengiriman murni hanya berisi fitur pengolahan responden _Phishing_.
- **Otomatisasi Peringatan:** Sistem kini mampu memunculkan panel peringatan cerdas apabila Peneliti kedapatan menuju halaman Kirim Simulasi padahal mereka belum pernah menyetel _Research Key_.

## 5. Sinkronisasi Webhook & Halaman Edukasi (Reveal Page)

Bagian terpenting dari edukasi simulasi peretasan adalah _Debriefing_ setelah partisipasi:

- **Penyempurnaan Webhook Tally:** Menyesuaikan pengaturan _Redirect on Completion_ pada Tally dengan menggunakan sintaksis yang benar (`@session_token`), sehingga mengatasi badai _Error 404 (Not Found)_ saat responden dilempar pulang ke situs SIMCYBER setelah mereka mengklik "Submit".
- **Lencana Apresiasi (Completion Badge):** Mendesain ulang tampilan muka halaman Edukasi Phishing (`reveal.tsx`). Ketika variabel parameter mencatat indikator penyelesaian kuesioner `?completed=true`, sistem akan merender secara dinamis sebuah _Banner Notifikasi Premium_ berwarna hijau besar di puncak artikel sebagai bentuk apresiasi resmi buat siswa.
- **Pembersihan Logika Countdown:** Menata ulang seluruh _source code_ React dan menghapus _timer_ hitungan mundur (pengalihan otomatis) pada skenario tertentu agar halaman berfokus pada navigasi bebas bagi penggunanya.
- **Pembersihan Komponen:** Melibas tumpukan fungsi dan _imports_ (komponen) React yang tidak lagi digunakan (unused code) agar aplikasi dapat berjalan semringah dan secepat kilat.

---

**Status Proyek Saat ini:** Aplikasi SIMCYBER telah menjadi instrumen riset fungsional yang stabil, aman (berstandar Tally & Laravel Fortify), serta dilengkapi presentasi _user-experience_ (UX) yang sangat terpoles untuk Peneliti maupun Responden.
