# CBT Exam Browser

APK shell Android untuk membuka CBT lama di `https://cbtdn.itstore.my.id/`.

## Fitur v1

- WebView dengan JavaScript dan DOM storage karena halaman CBT membutuhkannya.
- Pinch zoom untuk memperbesar teks soal, tanpa tombol zoom yang menutupi konten.
- Cookie sesi diaktifkan agar login dan navigasi ujian tetap bekerja.
- Navigasi top-level hanya mengizinkan HTTPS pada host `cbtdn.itstore.my.id`.
- `intent://`, popup, upload file, geolocation, kamera/mikrofon, dan unduhan diblokir.
- Safe Browsing, blok mixed content, blok cleartext HTTP, dan pembatalan sertifikat TLS yang tidak valid.
- Mode immersive fullscreen dan `FLAG_SECURE` untuk mengurangi screenshot/screen capture.
- Tombol Back hanya kembali ke halaman CBT sebelumnya; tidak menutup aplikasi dari root page.

## Build APK

Buka folder `exam-browser/` di Android Studio terbaru, tunggu Gradle sync, lalu pilih:

`Build > Build Bundle(s) / APK(s) > Build APK(s)`

Toolchain lokal yang sudah disiapkan berada di `/home/ilhamzp/.local/share/codex-android`.
Dengan toolchain itu, build dapat dijalankan memakai:

```bash
cd exam-browser
./scripts/build-debug.sh
```

APK debug akan berada di `app/build/outputs/apk/debug/app-debug.apk`.

## Catatan keamanan dan operasional

Ini adalah WebView wrapper, bukan kiosk device management penuh. Android tetap dapat menampilkan system gesture atau keluar melalui kontrol perangkat. Untuk ujian berisiko tinggi, gunakan perangkat yang dikelola sekolah melalui Android lock task/device-owner dan kebijakan MDM.

`FLAG_SECURE` mengurangi screenshot dan screen sharing, tetapi juga dapat menghambat bantuan teknis melalui screen sharing.

URL `fbclid` dari tautan Facebook tidak disimpan di aplikasi karena tidak diperlukan untuk login CBT; aplikasi memakai URL dasar yang sama.
