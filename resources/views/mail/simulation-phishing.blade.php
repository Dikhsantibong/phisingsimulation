<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<body
    style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; box-sizing: border-box; background-color: #edf2f7; color: #718096; height: 100%; hyphens: auto; line-height: 1.4; margin: 0; -moz-hyphens: auto; -ms-word-break: break-all; width: 100% !important; -webkit-hyphens: auto; -webkit-text-size-adjust: none; word-break: break-word;">
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="box-sizing: border-box; background-color: #edf2f7; margin: 0; padding: 0; width: 100%;">
        <tr>
            <td align="center" style="box-sizing: border-box;">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation"
                    style="box-sizing: border-box; margin: 0; padding: 0; width: 100%;">
                    <!-- Header -->
                    <tr>
                        <td class="header" style="box-sizing: border-box; padding: 25px 0; text-align: center;">
                            <a href="#"
                                style="box-sizing: border-box; color: #3d4852; font-size: 19px; font-weight: bold; text-decoration: none; display: inline-block;">
                                Pusat Layanan Pembelajaran
                            </a>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0"
                            style="box-sizing: border-box; background-color: #edf2f7; border-bottom: 1px solid #edf2f7; border-top: 1px solid #edf2f7; margin: 0; padding: 0; width: 100%; border: hidden !important;">
                            <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0"
                                role="presentation"
                                style="box-sizing: border-box; background-color: #ffffff; border-color: #e8e5ef; border-radius: 2px; border-width: 1px; box-shadow: 0 2px 0 rgba(0, 0, 150, 0.025), 2px 4px 0 rgba(0, 0, 150, 0.015); margin: 0 auto; padding: 0; width: 570px;">
                                <tr>
                                    <td class="content-cell"
                                        style="box-sizing: border-box; max-width: 100vw; padding: 32px;">
                                        <h1
                                            style="box-sizing: border-box; color: #3d4852; font-size: 18px; font-weight: bold; margin-top: 0; text-align: left;">
                                            Sistem Pembelajaran Digital — Notifikasi Keamanan
                                        </h1>

                                        <p
                                            style="box-sizing: border-box; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; color: #718096;">
                                            Halo Pengguna,
                                        </p>

                                        <p
                                            style="box-sizing: border-box; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; color: #718096;">
                                            Sistem mendeteksi aktivitas login tidak biasa pada akun pembelajaran digital
                                            Anda dari perangkat yang belum dikenali.
                                        </p>

                                        <p
                                            style="box-sizing: border-box; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; color: #718096;">
                                            Untuk menjaga keamanan akun, silakan lakukan verifikasi identitas melalui
                                            portal verifikasi berikut:
                                        </p>

                                        <!-- Button Verifikasi -->
                                        <table class="action" align="center" width="100%" cellpadding="0"
                                            cellspacing="0" role="presentation"
                                            style="box-sizing: border-box; margin: 30px auto; padding: 0; text-align: center; width: 100%;">
                                            <tr>
                                                <td align="center" style="box-sizing: border-box;">
                                                    <table border="0" cellpadding="0" cellspacing="0"
                                                        role="presentation" style="box-sizing: border-box;">
                                                        <tr>
                                                            <td style="box-sizing: border-box;">
                                                                <a href="{{ $accessUrl }}" target="_blank"
                                                                    rel="noopener"
                                                                    style="box-sizing: border-box; border-radius: 4px; color: #fff; display: inline-block; overflow: hidden; text-decoration: none; background-color: #2d3748; border-bottom: 8px solid #2d3748; border-left: 18px solid #2d3748; border-right: 18px solid #2d3748; border-top: 8px solid #2d3748;">Verifikasi
                                                                    Akun Sekarang</a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <p
                                            style="box-sizing: border-box; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; color: #718096;">
                                            Jika Anda merasa aktivitas tersebut bukan berasal dari Anda, silakan pilih
                                            tindakan berikut:
                                        </p>

                                        <!-- Button Tolak -->
                                        <table class="action" align="center" width="100%" cellpadding="0"
                                            cellspacing="0" role="presentation"
                                            style="box-sizing: border-box; margin: 30px auto; padding: 0; text-align: center; width: 100%;">
                                            <tr>
                                                <td align="center" style="box-sizing: border-box;">
                                                    <table border="0" cellpadding="0" cellspacing="0"
                                                        role="presentation" style="box-sizing: border-box;">
                                                        <tr>
                                                            <td style="box-sizing: border-box;">
                                                                <a href="{{ isset($rejectUrl) ? $rejectUrl : $accessUrl }}"
                                                                    target="_blank" rel="noopener"
                                                                    style="box-sizing: border-box; border-radius: 4px; color: #fff; display: inline-block; overflow: hidden; text-decoration: none; background-color: #e53e3e; border-bottom: 8px solid #e53e3e; border-left: 18px solid #e53e3e; border-right: 18px solid #e53e3e; border-top: 8px solid #e53e3e;">Tolak
                                                                    Aktivitas Ini</a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <p
                                            style="box-sizing: border-box; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; color: #718096;">
                                            Jika Anda tidak melakukan aktivitas ini, tetap lakukan verifikasi agar akun
                                            Anda tidak dinonaktifkan dalam 24 jam. Apabila tidak ada tindakan dalam
                                            beberapa waktu, akses akun pembelajaran Anda akan dinonaktifkan demi
                                            keamanan sistem.
                                        </p>

                                        <p
                                            style="box-sizing: border-box; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; color: #718096;">
                                            <strong style="box-sizing: border-box;">Catatan:</strong> Mohon segera
                                            tangani dalam waktu singkat agar akun tidak terkunci otomatis oleh sistem
                                            (pesan otomatis).
                                        </p>

                                        <p
                                            style="box-sizing: border-box; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left; color: #718096;">
                                            Terima kasih,<br>Pusat Layanan Sistem Pembelajaran Digital
                                        </p>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="box-sizing: border-box;">
                            <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0"
                                role="presentation"
                                style="box-sizing: border-box; margin: 0 auto; padding: 0; text-align: center; width: 570px;">
                                <tr>
                                    <td class="content-cell" align="center"
                                        style="box-sizing: border-box; max-width: 100vw; padding: 32px;">
                                        <p
                                            style="box-sizing: border-box; line-height: 1.5em; margin-top: 0; color: #b0adc5; font-size: 12px; text-align: center;">
                                            Email ini dikirim otomatis. Mohon tidak membalas pesan ini.
                                        </p>
                                        <p
                                            style="box-sizing: border-box; line-height: 1.5em; margin-top: 0; color: #b0adc5; font-size: 12px; text-align: center;">
                                            © {{ date('Y') }} Sistem Pembelajaran Digital. All rights reserved.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
