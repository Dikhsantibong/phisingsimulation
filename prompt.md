# Prompt Build Sistem — Simulasi Phishing & Kuesioner KAB
*(Untuk skripsi: Klasifikasi Tingkat Kesadaran Keamanan Informasi Siswa terhadap Ancaman Phishing menggunakan Pendekatan KAB dan Algoritma Random Forest)*

---

## Konteks Penggunaan

Prompt ini ditujukan untuk agen coding (Claude Code) guna men-scaffold aplikasi web penelitian akademik. Sistem ini adalah **instrumen pengumpulan data untuk skripsi**, bukan alat serangan nyata: seluruh interaksi bersifat simulasi dengan persetujuan institusional (izin sekolah), tidak menyimpan kredensial apa pun, dan diakhiri dengan halaman debrief + materi edukasi. Desain ini mengikuti metodologi simulated-phishing yang lazim dipakai dalam riset security awareness (mis. GoPhish, KnowBe4).

---

## 1. Ringkasan Sistem

Bangun aplikasi web bernama **"Sistem Simulasi Kesadaran Keamanan Phishing"** untuk mengukur tingkat kesadaran keamanan informasi siswa SMA Negeri 1 Kendari melalui dua instrumen yang salinkan (data-link) satu sama lain:

1. **Simulasi phishing tersamar** (perilaku aktual/behavioral) — dikelola penuh oleh sistem ini.
2. **Kuesioner Knowledge-Attitude-Behavior (self-report)** — dikelola pihak ketiga (Tally), disinkronkan via webhook.

Output akhirnya adalah dataset gabungan (perilaku simulasi + jawaban KAB + metadata perangkat + timestamp) yang menjadi fitur untuk model klasifikasi Random Forest (proses training model dilakukan terpisah di notebook Python — sistem ini fokus ke pengumpulan data yang bersih dan siap-ekspor CSV).

---

## 2. Tech Stack

- **Backend & Frontend:** Laravel 13 + Inertia.js + React (SPA experience, routing di sisi Laravel)
- **Database:** MySQL
- **Antrian/Job:** Laravel Queue (database driver cukup untuk skala skripsi — hindari Redis kecuali sudah tersedia di server)
- **Email:** Laravel Mail dengan SMTP — **rekomendasi praktis:** pakai Gmail SMTP (App Password) untuk pengujian awal/kelas kecil, atau **Mailtrap** untuk staging tanpa risiko salah kirim ke email asli saat development. Untuk pengiriman massal ke seluruh sampel penelitian, pertimbangkan provider transactional email gratis-tier seperti **Brevo (dulu Sendinblue)** — kuota gratis harian cukup besar dan reputasi domain lebih terjaga dibanding SMTP pribadi.
- **WhatsApp API:** **Fonnte** — direkomendasikan karena berbasis nomor WhatsApp pribadi/API tidak resmi tapi stabil, harga terjangkau untuk kebutuhan riset skala kecil-menengah, dan setup jauh lebih cepat dibanding WhatsApp Business API resmi Meta (yang butuh verifikasi bisnis formal, tidak praktis untuk timeline skripsi).
- **Kuesioner:** Tally.so (form eksternal), disinkron balik via **Tally Webhook** ke endpoint Laravel.
- **Autentikasi panel peneliti:** Laravel Breeze (simple, cukup untuk single-researcher access) + kolom `research_key` terpisah sebagai lapisan otorisasi kedua khusus fitur "kirim simulasi".
- **Frontend charting dashboard:** Recharts (sudah lazim dipakai di ekosistem React).
- **Export data:** Laravel Excel (maatwebsite/excel) untuk ekspor CSV/XLSX siap pakai ke tahap pemodelan Random Forest.

---

## 3. Skema Database (MySQL)

### `respondents`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| session_token | string, unique, indexed | Token unik per responden (UUID v4), penghubung ke Tally |
| class_group | string | Kelompok kelas |
| email | string | Alamat email target |
| whatsapp_number | string, nullable | Nomor WA (untuk reminder) |
| status | enum | `pending`, `sent`, `clicked`, `completed_behavior`, `completed_questionnaire`, `finished` |
| created_at, updated_at | timestamp | |

### `simulation_events`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| respondent_id | FK → respondents | |
| sent_at | timestamp, nullable | Waktu email simulasi dikirim |
| first_access_at | timestamp, nullable | Waktu pertama klik tautan (presisi hingga detik) |
| response_at | timestamp, nullable | Waktu tekan tombol final (submit/tolak/mundur) |
| behavior_status | enum | `berisiko`, `waspada`, `netral`, `tidak_merespons` |
| keystroke_detected | boolean | True jika sistem mendeteksi input teks apa pun (bukan isi teksnya) |
| device_type | enum | `mobile`, `tablet`, `desktop` |
| os_name | string, nullable | Diparsing dari User-Agent |
| browser_name | string, nullable | Diparsing dari User-Agent |
| ip_hash | string, nullable | **Hash saja** (bukan IP mentah) — untuk cek duplikasi tanpa menyimpan PII berlebih |

### `reminder_logs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| respondent_id | FK → respondents | |
| reminder_type | enum | `simulasi_diabaikan`, `kuesioner_belum_selesai` |
| channel | enum | `whatsapp`, `email` |
| scheduled_at | timestamp | Kapan reminder dijadwalkan |
| sent_at | timestamp, nullable | Kapan benar-benar terkirim (null jika masih di antrean) |
| attempt_number | int | Reminder ke berapa (untuk batasi maksimal N kali) |

### `questionnaire_results`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| respondent_id | FK → respondents | |
| tally_submission_id | string | ID submission dari Tally, untuk idempoten webhook |
| completion_status | enum | `selesai`, `belum_selesai` |
| knowledge_answers | JSON | Jawaban section Knowledge |
| attitude_answers | JSON | Jawaban section Attitude |
| behavior_answers | JSON | Jawaban section Behavior (self-report, beda dari behavior_status simulasi) |
| submitted_at | timestamp, nullable | |

### `researchers`
Tabel default Laravel Breeze `users`, ditambah kolom `research_key_hash` (hash bcrypt dari kunci panel, bukan disimpan plain).

---

## 4. Alur Sistem (User Flow)

```
[Peneliti: Dashboard]
   → Input daftar email + nomor WA + kelompok kelas (CSV import atau form manual)
   → Input research_key untuk otorisasi pengiriman
   → Sistem generate session_token unik per responden
   → Job queue: kirim email menyerupai "Peringatan Keamanan Akun Pembelajaran Digital"
        (link mengandung session_token, bukan data personal langsung)

[Responden: Email]
   → Klik tautan → dicatat first_access_at, device_type, os_name, browser_name
   → Masuk ke Portal Login Tiruan generik ("Portal Pembelajaran Digital")

[Responden: Portal Palsu]
   → Opsi A: Isi form (email/password apa pun) → submit
        → keystroke_detected = true, behavior_status = "berisiko"
   → Opsi B: Klik "Tolak/Laporkan Mencurigakan" tanpa mengisi apa pun
        → behavior_status = "waspada"
   → Opsi C: Mulai mengetik lalu batal/keluar/klik tolak
        → keystroke_detected = true, behavior_status = "netral"
   → (PENTING) Sistem TIDAK PERNAH menyimpan string yang diketik — hanya
     boolean "ada input" via event listener (onInput → set flag, tidak
     pernah kirim value ke backend)

[Responden: Halaman Reveal]
   → "Ini adalah simulasi edukatif, bukan serangan nyata"
   → Jelaskan tujuan riset, minta lanjut ke kuesioner
   → Redirect ke Tally dengan session_token sebagai hidden field/URL param

[Responden: Kuesioner Tally]
   → Isi KAB → submit
   → Tally kirim webhook ke Laravel → cocokkan via session_token
     → simpan ke questionnaire_results
   → Sistem redirect/tampilkan halaman pembelajaran interaktif (materi anti-phishing)

[Job Terjadwal - Reminder]
   → Jika X jam setelah sent_at belum ada first_access_at
        → jadwalkan reminder WhatsApp ke peneliti (bukan ke responden langsung,
          sesuai desain: peneliti yang follow-up manual via WA)
   → Jika sudah completed_behavior tapi belum completed_questionnaire dalam Y jam
        → jadwalkan reminder serupa
   → Maksimal N kali reminder per responden, lalu status berhenti di "tidak_merespons"
```

> Catatan penting soal alur reminder: dari deskripsi Anda, WhatsApp reminder itu **mengingatkan peneliti** (bukan mengirim WA langsung ke siswa) — karena disebut "sistem mengingatkan peneliti untuk mengirimkan melalui WhatsApp". Jika sebenarnya WA ini otomatis terkirim ke siswa tanpa peneliti menekan tombol apa pun, beri tahu saya — perlu tambahan tabel consent/opt-in nomor WA karena itu menyentuh isu privasi yang lebih ketat.

---

## 5. Dashboard Peneliti — Kebutuhan Fitur

1. **Panel Kirim Simulasi**
   - Import CSV (kolom: nama/kelas, email, no WA) atau input manual
   - Field research_key wajib, validasi sebelum job dikirim ke queue
   - Preview jumlah responden sebelum submit

2. **Tabel Data Responden (real-time)**
   - Kolom: token, kelas, status, behavior_status, sent_at, first_access_at, response_at, device_type
   - Filter per kelas/status, search
   - Live update (Laravel Echo + polling sederhana sudah cukup untuk skala skripsi — tidak perlu WebSocket kompleks)

3. **Tabel Reminder (Daftar Jadwal Antrean)**
   - Kolom: token responden, jenis reminder, dijadwalkan pukul, status terkirim/belum
   - Tombol "tandai sudah di-follow-up manual" jika reminder WA memang manual oleh peneliti

4. **Halaman Ekspor Data**
   - Export gabungan (respondents + simulation_events + reminder_logs + questionnaire_results) → CSV siap pakai untuk notebook Random Forest
   - Anonimisasi opsional: toggle untuk exclude email/no WA dari file ekspor (hanya sisakan token)

---

## 6. Prinsip Etika & Keamanan yang Wajib Ditegakkan di Kode

- Form portal tiruan **tidak pernah mengirim value input ke server** — hanya event boolean.
- Tidak ada logging request body yang berisi field password/email dari form tiruan (cek middleware logging Laravel, exclude route ini secara eksplisit).
- IP address, jika dicatat, **di-hash** (bukan disimpan mentah) sejak awal — bukan dihash belakangan.
- research_key disimpan sebagai hash, dicek via `Hash::check()`, bukan perbandingan string biasa.
- Wajib ada halaman **debrief/reveal** sebelum lanjut ke kuesioner — tidak boleh di-skip oleh alur sistem.
- Rate limiting pada endpoint klik tautan simulasi untuk mencegah bot/crawler otomatis mengisi data palsu.

---

## 7. Struktur Instruksi untuk Claude Code

Saat menempelkan prompt ini ke Claude Code, tambahkan baris berikut di awal agar sesuai konvensi proyek Anda:

```
Ikuti konvensi di claude.md project ini. Gunakan Laravel 13 + Inertia.js + React,
MySQL, Fonnte untuk WhatsApp, Brevo/SMTP untuk email. Scaffold sesuai skema database
dan alur sistem di bawah ini terlebih dahulu (migration + model), baru lanjut ke
controller dan halaman React.
```

---

**Catatan:** Bagian tech stack email/WhatsApp di atas adalah rekomendasi awal — silakan sesuaikan setelah saya bisa melihat isi `claude.md` Anda yang sebenarnya, karena mungkin sudah ada konvensi provider yang berbeda dari proyek-proyek Anda sebelumnya.

---

## 8. Status Build — SELESAI ✅ (2026-08-13)

Sistem telah di-scaffold penuh dan lulus verifikasi. Ringkasan yang sudah dibangun:

### Backend (Laravel 13 + Inertia v3)
- **Enum** (`app/Enums/`): `RespondentStatus`, `BehaviorStatus`, `DeviceType`, `ReminderType`, `ReminderChannel`, `CompletionStatus` — masing-masing dengan `label()` bahasa Indonesia.
- **Migrasi & tabel**: `respondents`, `simulation_events`, `reminder_logs` (+ kolom `followed_up_at` untuk tombol follow-up manual), `questionnaire_results`, dan kolom `research_key_hash` pada `users`.
- **Model + factory + seeder**: relasi lengkap, `session_token` = UUID v7 via `HasUuids` (primary key `id` tetap auto-increment). `DatabaseSeeder` mengisi 1 peneliti (`test@example.com`, research key `rahasia-riset`) + data demo responden/behavior/kuesioner.
- **Service**: `UserAgentParser` (device/os/browser tanpa dependensi, UA mentah tidak disimpan), `SimulationRecorder` (rekam akses + behavior, hash IP saat capture), `FonnteService` (WhatsApp ke peneliti), `ReminderScheduler` (jadwalkan reminder + cap N kali).
- **Mail/Job/Command**: `SimulationPhishingMail` (markdown, subject "Peringatan Keamanan…"), `SendSimulationEmail`, `SendReminderNotification` (ke nomor peneliti). Command `simulation:process-reminders` tetap ada untuk **run manual** (opsional), tapi **tidak lagi bergantung cron** — lihat mekanisme heartbeat di bawah.
- **Reminder tanpa cron (heartbeat)**: middleware `ProcessDueReminders` (terdaftar di grup `web`, `bootstrap/app.php`) menjalankan `ReminderScheduler` pada `terminate()` — **setelah respons dikirim ke browser**, jadi tidak menambah latensi. Guard `Cache::add` (atomik) memastikan scan berjalan **maksimal sekali per interval** (`SIMULATION_REMINDER_INTERVAL_MINUTES`, default 1 menit) berapa pun jumlah trafik. Dikendalikan flag `SIMULATION_AUTO_REMINDERS` (default `true`).
- **Controller & rute**:
  - Publik: `GET /s/{token}` (portal palsu, throttle 30/mnt), `POST /s/{token}/behavior`, `GET /s/{token}/reveal`.
  - Webhook: `POST /webhooks/tally` (dikecualikan CSRF via `preventRequestForgery`, idempoten per `tally_submission_id`, verifikasi HMAC signature bila secret diisi, kategorisasi K/A/B via prefix `k_`/`a_`/`b_`).
  - Peneliti (auth+verified): dashboard, kirim-simulasi (validasi `research_key` via `Hash::check`), atur research key, tabel responden (filter kelas/status/search + paginasi), tabel reminder + tandai follow-up, ekspor CSV (toggle anonimisasi).

### Frontend (React + Inertia)
- **Landing page** (`welcome`): halaman publik bertema akademik/institusional (SMA Negeri 1 Kendari, KAB + Random Forest) — header + hero + panel alur pengumpulan data + dua kartu instrumen + footer judul skripsi. Memakai token tema aplikasi (light/dark), tanpa gradien/emoji/gaya marketing. Tombol menuju login/dashboard.
- Publik standalone (tanpa chrome peneliti): `phishing/portal` (form login tiruan — **hanya mengirim `{action, keystroke_detected}`, tidak pernah value input**), `phishing/reveal` (debrief edukatif + tombol lanjut ke Tally).
- Peneliti: `dashboard` (kartu statistik + bar breakdown status & perilaku), `send-simulation` (impor CSV/manual + preview + research key), `respondents/index`, `reminders/index`, `data-export`. Nav sidebar diperbarui.

### Etika & keamanan (terverifikasi lewat test)
- Form portal tidak pernah mengirim value input ke server (uncontrolled input + boolean flag).
- IP di-hash sejak awal (`sha256` + app key), 64-char, bukan IP mentah.
- `research_key` disimpan sebagai hash, dicek dengan `Hash::check()`.
- Halaman debrief wajib sebelum kuesioner; rate limiting di endpoint klik.

### Verifikasi
- **67 test PHPUnit lulus** (212 assertion): alur simulasi, mapping behavior, hash IP, webhook Tally (idempotensi + signature + kategorisasi), otorisasi research key, ekspor CSV + anonimisasi, penjadwalan reminder + cap, **serta heartbeat reminder berbasis trafik web (`ProcessDueReminders`)**.
- `vendor/bin/pint` bersih, `tsc --noEmit` bersih.

### Penyimpangan/keputusan dari spec awal (perlu diketahui)
1. **Auth panel**: proyek memakai **Laravel Fortify** (bukan Breeze) — sudah terpasang di starter kit; fungsional setara.
2. **Charting**: **Recharts tidak dipasang** karena aturan proyek melarang menambah dependensi tanpa persetujuan. Dashboard memakai bar chart CSS ringan (tanpa dependensi). Bila ingin Recharts, setujui `npm i recharts` lalu ganti komponen `BreakdownBars`.
3. **Ekspor**: memakai **streamed CSV native** (`response()->streamDownload`) alih-alih `maatwebsite/excel`, agar tidak menambah dependensi. Sudah UTF-8 BOM (aman dibuka Excel) dan siap untuk notebook Random Forest.
4. **Reminder**: sesuai desain, WhatsApp mengingatkan **peneliti** (bukan siswa langsung) — tidak ada tabel consent/opt-in nomor WA siswa.
5. **Webhook Tally**: agar jawaban terpetakan ke kolom K/A/B, beri prefix field key `k_`/`a_`/`b_` (atau kata kunci pengetahuan/sikap/perilaku). Field tak terkategori tetap disimpan di `behavior_answers['_uncategorized']` (tidak ada data hilang).
6. **Tanpa cron sama sekali** (untuk shared hosting): scheduler cron dihapus, diganti middleware heartbeat `ProcessDueReminders` (dipicu trafik web), dan `QUEUE_CONNECTION=sync` sehingga job (email simulasi & WA reminder) berjalan **inline** tanpa queue worker. Konsekuensi: (a) reminder hanya berjalan saat ada kunjungan web — cukup untuk studi yang aktif; (b) mengirim ke banyak responden sekaligus berjalan sinkron saat request, jadi untuk batch besar kirim per-kelas/bertahap. Bila server punya cron & worker, Anda tetap bisa kembali ke pola lama (aktifkan `QUEUE_CONNECTION=database` + `queue:work`, dan set `SIMULATION_AUTO_REMINDERS=false` lalu jadwalkan `simulation:process-reminders`).

### Konfigurasi yang perlu diisi sebelum produksi (`.env`)
`FONNTE_TOKEN`, `FONNTE_RESEARCHER_NUMBER`, `SIMULATION_TALLY_URL`, `SIMULATION_TALLY_SIGNING_SECRET`, kredensial `MAIL_*`. Biarkan `QUEUE_CONNECTION=sync`, `SIMULATION_AUTO_REMINDERS=true`, `SIMULATION_REMINDER_INTERVAL_MINUTES=1` untuk mode tanpa cron. Lihat `.env.example` untuk daftar lengkap.

### Cara menjalankan
```bash
composer run dev      # serve + vite (dev)
php artisan db:seed   # data demo + akun peneliti
```
Tidak perlu `schedule:work` maupun `queue:work` — reminder dipicu trafik web (middleware `ProcessDueReminders`) dan job berjalan inline (`QUEUE_CONNECTION=sync`). Untuk memicu reminder manual kapan pun: `php artisan simulation:process-reminders`.
