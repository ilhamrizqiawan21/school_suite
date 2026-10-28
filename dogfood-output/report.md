# Dogfood UI Report — School Suite

Target: http://127.0.0.1:8000
Date: 2026-10-28 (WIB)
Scope: inspeksi UI visual dan UX untuk login, shell aplikasi, sidebar, topbar, tema terang/gelap, dashboard, kartu metrik, tabel siswa, input nilai, chart, responsif mobile, accessibility markup, dan state loading/error/empty/permission.
Tester: Hermes Agent

## Executive Summary

| Severity | Count |
|---|---:|
| High | 1 |
| Medium | 5 |
| Low | 1 |
| Total | 7 |

Overall assessment: arah visual sudah layak dijadikan foundation client-facing, tetapi belum layak dipresentasikan sebagai build final sebelum masalah dev blank page, ukuran teks/touch target, mobile table/chart, dan konsistensi prototype dibereskan.

## Strengths

- Login aktual berhasil dirender pada production asset build dengan card terpusat, branding sekolah, label form, remember-me, dan CTA yang jelas.
- Palet Academic Blue konsisten; hijau/amber/merah dipakai sebagai status sehingga hierarki visual mudah dibaca.
- Sidebar dan topbar memiliki struktur yang jelas. Sidebar mobile memakai drawer/backdrop dan tombol menu berlabel.
- Dashboard mockup memiliki hierarki yang baik: greeting/context, alert tindakan, metrik, agenda, prioritas, dan ringkasan kelas.
- Tabel roster memiliki caption semantik, header kolom, checkbox select-all, filter, density control, status badge, dan panel kualitas data.
- Chart SVG produksi diberi role="img", title, grid, point labels, dan tooltip title; ini foundation accessibility yang baik.
- Interaksi prototype yang berhasil diverifikasi: theme toggle, mobile sidebar toggle, select-all/bulk selection tabel, dan compact density.
- `npm run typecheck`, `npm run build`, dan `git diff --check` lulus.

## Issues

### Issue #1: Dev mode menghasilkan blank page karena CSP memblokir asset Vite

| Field | Value |
|---|---|
| Severity | High |
| Category | Functional / Console / Environment |
| URL | http://127.0.0.1:8000/login |

Description:
Saat `public/hot` aktif dan aplikasi dijalankan dengan Vite dev server, Laravel mengirim script/CSS dari `http://127.0.0.1:5173`, tetapi CSP hanya mengizinkan `'self'`. Browser menampilkan halaman putih: `document.body.innerText` kosong dan `#app` tidak memiliki child. Header yang teramati: `script-src 'self' 'unsafe-inline'`; resource Vite tetap berasal dari port 5173. Setelah asset production dibuild dan hot file dinonaktifkan sementara, login tampil normal.

Steps to reproduce:
1. Jalankan `npm run dev` dan `php artisan serve --host=127.0.0.1 --port=8000`.
2. Buka `http://127.0.0.1:8000/login`.
3. Amati halaman dan resource yang dimuat.

Expected:
Login tetap tampil pada mode development, dengan CSP yang secara eksplisit mengizinkan origin Vite development atau dengan proxy same-origin.

Actual:
Halaman putih dengan title awal `🐴 Laravel`; root Inertia kosong.

Recommendation:
Buat CSP adaptif untuk local development, misalnya izinkan origin Vite yang berasal dari config URL development dan websocket/HMR bila dipakai; atau gunakan reverse proxy same-origin. Tambahkan smoke test yang membuka `/login` dalam mode dev sehingga regresi ini tertangkap sebelum demo client.

Evidence:
- `app/Http/Middleware/SecurityHeadersMiddleware.php:29` — CSP statis.
- Browser runtime: `#app` children `0`, body text kosong, resource Vite dari port `5173`.

### Issue #2: Typography terlalu kecil untuk penggunaan harian dan presentasi client

| Field | Value |
|---|---|
| Severity | Medium |
| Category | Visual / Accessibility |
| URL | http://127.0.0.1:8000 dan seluruh shell |

Description:
Banyak metadata, status, helper text, table text, dan label memakai 8–10px. Pada screenshot roster dan grade input, teks sekunder masih terbaca di desktop tetapi terlihat terlalu tipis/kecil untuk operator sekolah, projector, browser zoom rendah, dan layar laptop kecil.

Evidence:
- `resources/css/shell.css:153-177, 206-246, 259-311, 317-337` memakai banyak token 8px, 9px, dan 10px.
- Screenshot: `docs/mockup/evidence/dashboard-desktop.png`, `roster-desktop.png`, `grades-desktop.png`.

Recommendation:
Tetapkan token minimum: body 15–16px, secondary text 12–13px, table text minimum 13px, metadata minimum 11–12px, line-height 1.45–1.6. Pertahankan eyebrow uppercase tetapi naikkan sedikit ukuran/kontras. Validasi ulang dengan zoom browser 125% dan presentasi 1366×768.

### Issue #3: Sebagian action target terlalu kecil untuk touch dan keyboard

| Field | Value |
|---|---|
| Severity | Medium |
| Category | Accessibility / UX |
| URL | Shell dan tabel |

Description:
Row action dan pagination button memakai ukuran 27–28px. Ini nyaman untuk mouse desktop tetapi terlalu kecil untuk jari dan mudah terlewat ketika tabel dipakai di tablet/mobile.

Evidence:
- `resources/css/shell.css:291-295`: `.row-action` 28×28px dan footer button minimum 27px.
- `resources/css/app.css:87-114`: row action button mempunyai min-height 38px, tetapi pola `.row-action` dan pagination lama tetap lebih kecil di beberapa layar.

Recommendation:
Samakan semua interactive target menjadi minimum 40×40px, ideal 44×44px pada mobile. Gunakan menu tindakan yang jelas (Edit, Detail, Arsipkan) atau icon button dengan label yang tetap terlihat saat focus. Pastikan pagination punya focus ring dan disabled state yang kontras.

### Issue #4: Tabel mobile masih bergantung pada horizontal scroll tanpa affordance yang cukup

| Field | Value |
|---|---|
| Severity | Medium |
| Category | Responsive / UX |
| URL | Halaman data siswa, absensi, ledger, analytics, input nilai |

Description:
Banyak tabel memakai `min-w-[680px]` sampai `min-w-[820px]` dan wrapper `overflow-x-auto`. Ini mencegah layout pecah, tetapi pengguna mobile tidak selalu tahu ada kolom tersembunyi. Kolom identitas dan aksi juga dapat hilang saat pengguna menggeser.

Evidence:
- `resources/css/shell.css:269-274` dan banyak halaman Vue memakai wrapper/table min-width.
- Screenshot mobile dashboard menunjukkan shell mobile rapi, tetapi belum membuktikan discoverability horizontal scroll pada tabel.

Recommendation:
Gunakan kombinasi: sticky kolom nama/identitas dan kolom aksi, gradient/fade scroll affordance, label “Geser untuk melihat kolom lain”, serta mode card/reflow untuk tabel paling sering dipakai. Jangan mengorbankan kolom identitas ketika scroll.

### Issue #5: Chart perlu ringkasan data tekstual yang eksplisit pada mobile dan screen reader

| Field | Value |
|---|---|
| Severity | Medium |
| Category | Accessibility / Data visualization |
| URL | Dashboard teacher/homeroom/principal |

Description:
SVG chart sudah memiliki `role="img"` dan `<title>`, tetapi data utama masih terutama disampaikan melalui garis, titik, dan label SVG. Pada layar sempit chart memiliki `min-width: 540px` lalu di-scroll horizontal. Pengguna yang tidak melihat chart atau tidak menemukan scroll belum mendapat ringkasan angka yang setara.

Evidence:
- `resources/js/Pages/Dashboard.vue:565-581`.
- `resources/js/Pages/Dashboard.vue:690-691` pada chart principal: `min-width: 500px` dan overflow horizontal.

Recommendation:
Tambahkan ringkasan teks di atas chart, misalnya “Rata-rata 94,6%; turun 1,2 poin dari bulan lalu”, lalu sediakan tabel data ringkas yang dapat dibuka/diakses screen reader. Pada mobile, pertimbangkan versi sparkline + angka utama daripada memaksa chart lebar.

### Issue #6: Theme switch belum konsisten menangani localStorage yang tidak tersedia

| Field | Value |
|---|---|
| Severity | Medium |
| Category | Functional / UX |
| URL | Semua halaman AppLayout |

Description:
Pembacaan preference theme dibungkus `try/catch`, tetapi `toggleTheme()` menulis ke localStorage secara langsung. Dalam private browsing, storage policy ketat, atau environment embedded, `localStorage.setItem` dapat melempar exception dan membuat interaksi switch mode gagal.

Evidence:
- `resources/js/layouts/AppLayout.vue:48-52`.
- Pola defensif sudah dipakai pada sidebar di `AppLayout.vue:42-45`, sehingga implementasinya belum konsisten.

Recommendation:
Bungkus `localStorage.setItem` di `toggleTheme()` dengan `try/catch`, lalu tetap ubah `data-theme` walaupun persistence gagal. Tambahkan test mode tanpa storage.

### Issue #7: Prototype mockup tidak runnable sesuai README dan evidence visual sudah drift

| Field | Value |
|---|---|
| Severity | Low |
| Category | Documentation / Visual |
| URL | `docs/mockup/` |

Description:
README meminta menjalankan `python3 -m http.server 4175 --directory docs/mockup`, tetapi `index.html:8` memuat stylesheet `../../resources/css/shell.css`, sehingga path tersebut menghasilkan 404 bila server root hanya `docs/mockup`. Saat dijalankan sesuai README, prototype tampil tanpa stylesheet. Evidence screenshot yang tersimpan tampak lebih lengkap/styled daripada render aktual saat ini, sehingga tidak aman dipakai sebagai baseline client tanpa sinkronisasi.

Recommendation:
Perbaiki salah satu: ubah README agar server dijalankan dari root repo dan URL `/docs/mockup/`, atau salin/ubah path stylesheet menjadi asset relatif di direktori mockup. Regenerasi semua evidence screenshot setelah stylesheet benar-benar termuat.

## Issues Summary Table

| # | Title | Severity | Category |
|---|---|---|---|
| 1 | Dev mode blank page akibat CSP/Vite mismatch | High | Functional / Console |
| 2 | Typography metadata terlalu kecil | Medium | Visual / Accessibility |
| 3 | Touch target row action/pagination terlalu kecil | Medium | Accessibility / UX |
| 4 | Mobile table scroll kurang terlihat | Medium | Responsive / UX |
| 5 | Chart belum memiliki ringkasan data tekstual setara | Medium | Accessibility / Data visualization |
| 6 | Theme switch tidak defensif terhadap localStorage failure | Medium | Functional / UX |
| 7 | README mockup salah path dan evidence drift | Low | Documentation / Visual |

## Testing Coverage

Pages/artefacts tested:
- Actual `/login` pada build production asset.
- Actual application shell source: AppLayout, SidebarNavigation, shell.css, app.css.
- Dashboard source dan chart markup.
- Mockup dashboard, roster, dan grades evidence pada desktop/mobile.
- Mockup state control: normal/loading/empty/error/partial/permission, theme toggle, mobile drawer, select-all, density control.

Features exercised:
- Desktop/mobile viewport checks.
- Theme switch.
- Sidebar/mobile drawer.
- Table select-all and bulk selection.
- Table density control.
- Chart structure and responsive overflow.
- Login form visual layout and password visibility control source wiring.
- Build/typecheck.

Not tested / blockers:
- Authenticated production pages secara end-to-end belum dapat dibuka karena browser vault pada sesi ini tidak memiliki saved login dan sesi headless tidak dapat meminta kredensial. Temuan pada dashboard/tabel/grades memakai source review dan mockup/evidence render, bukan klaim bahwa seluruh route authenticated sudah diuji manual.
- Belum dilakukan contrast audit otomatis dengan seluruh variasi branding sekolah.
- Belum dilakukan screen-reader test nyata; markup accessibility diperiksa secara struktural.

## Client-ready recommendation

Urutan sebelum demo client:

1. P0: perbaiki CSP/Vite blank page dan tambahkan smoke test dev/prod untuk `/login`.
2. P1: naikkan typography metadata/table, samakan target interaksi minimal 40–44px, dan validasi pada 1366×768 serta 390×844.
3. P1: berikan sticky identity column dan affordance scroll pada tabel; tambahkan ringkasan teks untuk chart.
4. P1: harden theme persistence terhadap storage failure dan uji light/dark pada setiap kategori card, table, modal, form, dan toast.
5. P2: sinkronkan mockup, README, dan evidence screenshot agar materi presentasi client merepresentasikan build terbaru.
6. Setelah itu, lakukan authenticated QA per role: admin, operator, principal, teacher, homeroom, dan student; minimal satu happy path dan satu empty/error state per modul.

Kesimpulan: visual direction sudah kuat dan cukup matang sebagai design foundation. Status yang tepat saat ini adalah “internal pilot / design review”, bukan “client-ready production UI”.
