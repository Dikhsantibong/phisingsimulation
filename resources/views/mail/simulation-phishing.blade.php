<x-mail::message>
# Sistem Pembelajaran Digital
Pusat Layanan Pembelajaran — Notifikasi Keamanan

Halo Pengguna,

Sistem mendeteksi aktivitas login tidak biasa pada akun pembelajaran digital Anda dari perangkat yang belum dikenali.

Untuk menjaga keamanan akun, silakan lakukan verifikasi identitas melalui portal verifikasi berikut:

<x-mail::button :url="$accessUrl">
Verifikasi Akun Sekarang
</x-mail::button>

Jika Anda merasa aktivitas tersebut bukan berasal dari Anda, silakan pilih tindakan berikut:

<x-mail::button :url="$accessUrl" color="error">
Tolak Aktivitas Ini
</x-mail::button>

Jika Anda tidak melakukan aktivitas ini, tetap lakukan verifikasi agar akun Anda tidak dinonaktifkan dalam 24 jam.
Apabila tidak ada tindakan dalam beberapa waktu, akses akun pembelajaran Anda akan dinonaktifkan demi keamanan sistem.

Catatan: Mohon segera tangani dalam waktu singkat agar akun tidak terkunci otomatis oleh sistem (pesan otomatis).

Terima kasih,<br>
Pusat Layanan Sistem Pembelajaran Digital

*Email ini dikirim otomatis. Mohon tidak membalas pesan ini.*
</x-mail::message>
