# PRD — Aplikasi Pengurus Daerah AKP2I Jawa Barat

| | |
|---|---|
| Produk | Website + Aplikasi Pengurus Daerah (PD) AKP2I Jawa Barat |
| Basis kode | `baraasaproject` (Laravel) — di-upgrade dan dikembangkan, bukan ditulis ulang |
| Versi dokumen | 1.0 — 8 November 2026 |
| Status | Disetujui sebagai acuan implementasi |
| Bahasa UI | Bahasa Indonesia |

> Dokumen ini adalah acuan tunggal untuk membangun aplikasi sampai selesai. Setiap milestone (Bagian 12) harus bisa dikerjakan langsung dari dokumen ini. Hal yang belum pasti ditandai **[TBD]** dan dikumpulkan di Bagian 13.

---

## 1. Ringkasan & Tujuan

### 1.1 Latar belakang
AKP2I (Asosiasi Konsultan Pajak Publik Indonesia) berjenjang: **Pengurus Pusat (PP) → Pengurus Daerah (PD, provinsi) → Pengurus Cabang (PC, kota/kabupaten)**. Website pusat (akp2i.or.id) dan layanan anggota pusat (akp2i.id) sudah ada, tetapi PD Jawa Barat belum punya:
- website daerah sendiri dengan CMS,
- data anggota yang tahu setiap anggota ada di PC mana,
- catatan jabatan pengurus PD dan PC per periode,
- sistem event/pelatihan (PPL, Brevet) dengan pendaftaran dan sertifikat otomatis.

### 1.2 Tujuan
1. Website publik PD AKP2I Jawa Barat yang dikelola lewat CMS, bergaya visual selaras dengan website pusat.
2. Modul keanggotaan: seluruh anggota Jawa Barat tercatat beserta PC, jenis anggota, status iuran, dan jabatan PD/PC-nya.
3. Pusat pelatihan: event dengan pendaftaran mandiri, verifikasi, kehadiran, sertifikat PDF ber-QR, dan rekap jam PPL.
4. Ruang komunitas: Blog, Forum Diskusi, Jurnal.
5. API siap untuk mobile app.

### 1.3 Metrik sukses
| Metrik | Target |
|---|---|
| Anggota Jabar terdata lengkap dengan PC | 100% dalam 1 bulan setelah go-live |
| Event dengan pendaftaran & sertifikat lewat aplikasi | 100% event PD sejak go-live |
| Waktu terbit sertifikat setelah event selesai | ≤ 1 hari kerja (generate massal) |
| Halaman publik dibuka di HP tanpa scroll horizontal | 100% halaman |
| Temuan keamanan tinggi (Bagian 3.3) tersisa | 0 sebelum go-live |

---

## 2. Ruang Lingkup

### 2.1 Dalam lingkup (v1)
- Website publik (Bagian 7) + CMS.
- Struktur organisasi PD → PC, keanggotaan, status iuran, kepengurusan per periode.
- Blog, Forum Diskusi, Jurnal, Acara/Event (pendaftaran + sertifikat), Pengguna Aplikasi, Profil.
- API mobile v1 (milestone terakhir).

### 2.2 Di luar lingkup
| Hal | Alasan / pengganti |
|---|---|
| Pendaftaran anggota baru & layanan anggota (perpanjangan KTA, legalisir SK, peningkatan) | Diproses Pengurus Pusat. Website kita hanya **menautkan** ke `https://akp2i.id/pendaftaran-anggota/` dan `https://akp2i.id/pelayanan-anggota/` |
| Penerbitan KTA | Diterbitkan pusat (digital, via email) |
| Payment gateway | Pembayaran manual (transfer + upload bukti) |
| Multi-PD nasional | Skema data sudah mendukung, fitur tidak diaktifkan di v1 |
| Aplikasi mobile | Setelah API v1 stabil (Bagian 10) |
| Modul Open Recruitment baraasa | Dihapus — tidak relevan untuk asosiasi |
| Menu builder / page builder drag-and-drop | Menu tetap di kode, isi halaman dari CMS |

---

## 3. Kondisi Awal

### 3.1 baraasaproject (basis)
- **Stack**: Laravel 8.74, PHP `^7.3|^8.0`, MySQL, `spatie/laravel-permission` 5, `laravel/sanctum` 2, `yajra/laravel-datatables-oracle` 9, Breeze 1 (hanya auth). Asset dikompilasi Laravel Mix.
- **UI admin**: tema **Metronic 8 (Bootstrap 5)** di `public/css/style.bundle.css`, `public/plugins/global/plugins.bundle.*`, `public/js/scripts.bundle.js`; plugin jQuery DataTables, Select2, TinyMCE, flatpickr. Layout `resources/views/components/dashboard/layout.blade.php` (`<x-dashboard.layout>`), menu samping `resources/views/components/dashboard/aside/list.blade.php`. Tailwind terpasang tapi praktis tidak dipakai.
- **Modul yang ada** (semua di bawah `/dashboard`, route di `routes/permission/administrator.php`):

| Modul | Model / tabel | Keadaan |
|---|---|---|
| Blog | `Blog` / `blogs` | CRUD, thumbnail, `publication_status` bool, TinyMCE |
| Forum | `Thread`, `Comment` | Thread + komentar datar |
| Jurnal | `Research` / `research` | Judul, tanggal, file PDF saja |
| Acara | `Event` / `events` | CRUD, thumbnail, tanggal, lokasi, status berlangsung/terlaksana |
| Peserta | `Participant` / `participants` | Didaftarkan **admin**; sertifikat = upload gambar manual |
| Perekrutan | `Recruitment` | CV PDF + 2 boolean |
| Anggota / Divisi / Posisi | `User`, `Division`, `Position` | User punya satu `division_id` & `position_id` |
| Pengguna umum / Administrator | `User` + role | CRUD + promosi role |
| Pengaturan | `Setting` / `settings` (1 baris) | Email, telepon, alamat, IG, Spotify — tidak dibaca di mana pun |

- **Role (spatie)**: `super_administrator`, `administrator`, `member`, `general`. Permission berpola `{modul}-{view|create|update|delete}` (seeder `database/seeders/UserSeeder.php`). `Gate::before` memberi semua akses ke `super_administrator` (`app/Providers/AuthServiceProvider.php`).
- **Tidak ada website publik**: `routes/permission/public.php` kosong. **API** hanya `GET /api/user`.
- **Policy** (`app/Policies/*`) semuanya stub kosong dan tidak dipakai.

### 3.2 laracrot (referensi pola CMS)
Laravel 10 Blade dengan situs publik (`resources/views/user/*`) terpisah dari admin. Pola yang **ditiru**:
- slider beranda yang bisa diedit (`Home`),
- post sluggable + kategori + tag,
- galeri, daftar pengurus, inbox kontak,
- alur "admin input data dulu, anggota aktivasi akun",
- PDF dompdf + QR untuk verifikasi.

Yang **tidak ditiru**:
- statistik ditulis manual per unit (pakai query `groupBy`),
- URL gambar hard-code di API (pakai `Storage::url()`),
- FK berupa string,
- upload langsung ke `public/` (pakai disk `public`),
- hierarki satu level (pakai `organizations.parent_id`),
- tanpa SEO.

### 3.3 Temuan bug & keamanan yang wajib diperbaiki (M0)
| # | Masalah | Lokasi | Perbaikan |
|---|---|---|---|
| B1 | **Account takeover**: user login bisa mengubah email/password user lain | `ProfileController@update`, `@password` | Hanya user sendiri; pakai `auth()->user()`, bukan `{user}` dari URL |
| B2 | Tidak ada cek kepemilikan edit/hapus Blog, Thread, Comment; `ThreadController@update` mengganti `user_id` | Blog/Thread/CommentController | Policy nyata: pemilik atau moderator |
| B3 | `CommentController` & `RecruitmentController` tanpa permission middleware | controller terkait | Middleware `permission:` + policy (Recruitment dihapus) |
| B4 | Admin bisa menaikkan role ke admin / mengubah super admin | `MemberController@update`, `@generalAssign`, `GeneralController` | Guard di update/destroy, bukan hanya di edit; route binding dibatasi role |
| B5 | `publication_status` bisa dimanipulasi user umum | BlogController/ThreadController | Status diset server sesuai permission |
| B6 | Ganti file di edit tidak pernah jalan (`$request->input('file')`) | `ParticipantController@update`, `RecruitmentController@update` | `$request->hasFile('file')` |
| B7 | Link unduh sertifikat salah path | `resources/views/dashboard/profile/action.blade.php` | Pakai path tersimpan + `Storage::url()` |
| B8 | Beranda dashboard crash bila belum ada event | `HomeController@index` | Null-safe |
| B9 | Logout redirect ke `/` (404) | `AuthenticatedSessionController@destroy` | Terselesaikan saat situs publik ada di `/` |
| B10 | `positions.name` unique global; tidak ada `Division::positions()` | migrasi positions, model Division | Unique `(division_id, name)` + relasi |
| B11 | Peserta dobel per event; nama file upload bisa bentrok | `participants`, controller upload | Unique `(event_id, user_id)`; nama file `Str::uuid()` atau `store()` |
| B12 | DataTables memuat semua baris (`Model::all()`) | semua `*datatable` | `DataTables::eloquent($query)` server-side |
| B13 | Role `member` bisa melihat PII seluruh user | seeder permission | Permission per role direvisi (Bagian 4) |
| B14 | Branding "Bara Asa Project", "Palu", timezone `Asia/Makassar` hard-code | layout, meta, login, `config/app.php` | Rebranding AKP2I Jabar, `Asia/Jakarta` |

### 3.4 Website pusat AKP2I (referensi desain & domain)
- Tema Botble "Gerow" (Bootstrap). Token warna & font diambil dari CSS live (Bagian 6).
- Menu: Beranda · Profil (Tentang Kami, Pengurus, Galeri, Peraturan Organisasi) · Keanggotaan (Tentang Keanggotaan, Layanan Anggota) · Pusat Pelatihan (PPL, Brevet Pajak) · Berita & Artikel · Hubungi Kami.
- **Jenis anggota**:
  - **Utama**: memiliki izin praktik konsultan pajak.
  - **Madya**: tanpa izin praktik, tetapi memenuhi kriteria seperti USKP/penyetaraan, mantan pegawai DJP, atau Brevet resmi.
  - **Pratama**: termasuk Alumni Brevet AKP2I.
  - **Kehormatan**: ditetapkan PP.
- **Data anggota** yang dipakai pusat: Nomor Anggota, Nomor KIP (izin praktik), sertifikasi A/B/C, Brevet A/B/C, NPWP, KTP.
- **Iuran tahunan** periode 1 Jan–31 Des. Sanksi: teguran → **skors mulai 1 Mei tahun berikutnya** → diberhentikan.
- Mobile app `ahdiatahsan/baraasaproject-app`: Android **Java**, fitur setara baraasa. Web belum punya API untuk app ini.

---

## 4. Persona & Peran

### 4.1 Persona
| Persona | Kebutuhan utama |
|---|---|
| Admin PD (sekretariat PD) | Kelola data anggota se-Jabar, kepengurusan PD & PC, event, konten website |
| Editor konten | Menulis berita, mengelola halaman, galeri, dokumen, jurnal |
| Admin PC | Melihat anggota & pengurus PC-nya, membuat event/konten atas nama PC |
| Anggota AKP2I | Melihat profil & status keanggotaan, ikut forum, daftar event, unduh sertifikat |
| Umum (non-anggota) | Daftar peserta Brevet/seminar publik, unduh sertifikat |
| Pengunjung | Membaca info organisasi, berita, jadwal pelatihan; diarahkan ke pendaftaran pusat |

### 4.2 Role (spatie/laravel-permission)
| Role | Asal | Hak ringkas |
|---|---|---|
| `super_administrator` | ada | Semua (via `Gate::before`). Maksimal 5 orang (aturan yang sudah ada dipertahankan) |
| `admin_pd` | rename `administrator` | Semua modul kecuali kelola super admin. **Satu-satunya role yang boleh menambah/mengubah/menghapus anggota** |
| `editor` | baru | CMS (halaman, slider, galeri, dokumen), blog, jurnal, moderasi forum. Tidak bisa melihat data anggota |
| `admin_pc` | baru | **Read-only** anggota & pengurus PC-nya sendiri. Membuat event & blog atas nama PC-nya (scope `organization_id`) |
| `anggota` | rename `member` | Profil sendiri, forum, menulis blog (perlu review), daftar event, sertifikat, status iuran |
| `umum` | rename `general` | Registrasi akun mandiri, daftar event publik, sertifikat, profil sendiri |

### 4.3 Matriks permission
Pola tetap `{modul}-{view|create|update|delete}`. Kolom: SA = super_administrator, PD = admin_pd, ED = editor, PC = admin_pc, AG = anggota, UM = umum. Tanda ✓ = semua aksi, "sendiri" = hanya data milik sendiri, "scope PC" = hanya data PC-nya.

| Modul | SA | PD | ED | PC | AG | UM |
|---|---|---|---|---|---|---|
| organization (PD/PC) | ✓ | ✓ | – | view | – | – |
| member | ✓ | ✓ | – | view (scope PC) | – | – |
| due (iuran) | ✓ | ✓ | – | view (scope PC) | view sendiri | – |
| board (kepengurusan) | ✓ | ✓ | – | view (scope PC) | – | – |
| division / position | ✓ | ✓ | – | – | – | – |
| page / slider / gallery / document | ✓ | ✓ | ✓ | – | – | – |
| blog | ✓ | ✓ | ✓ | create + sendiri | create + sendiri | – |
| blog-publish | ✓ | ✓ | ✓ | – | – | – |
| thread / comment | ✓ | ✓ | moderasi | create + sendiri | create + sendiri | create + sendiri **[TBD]** |
| research (jurnal) | ✓ | ✓ | ✓ | view | view | view |
| event | ✓ | ✓ | – | ✓ (scope PC) | – | – |
| participant | ✓ | ✓ | – | ✓ (scope PC) | daftar sendiri | daftar sendiri |
| certificate | ✓ | ✓ | – | ✓ (scope PC) | view sendiri | view sendiri |
| user (pengguna aplikasi) | ✓ | ✓ kecuali SA | – | – | – | – |
| contact-message | ✓ | ✓ | ✓ | – | – | – |
| setting | ✓ | ✓ | – | – | – | – |
| profile | sendiri | sendiri | sendiri | sendiri | sendiri | sendiri |

Aturan implementasi:
- Setiap aksi controller memanggil **policy** (`$this->authorize(...)`). Middleware `permission:` tetap dipakai untuk akses kasar.
- Scope PC diterapkan di query builder (`whereIn('organization_id', $user->scopedOrganizationIds())`), bukan hanya di tampilan.
- Admin (non-SA) tidak bisa melihat, mengubah, atau menetapkan role `super_administrator`.

---

## 5. Arsitektur & Tech Stack

| Lapisan | Pilihan | Catatan |
|---|---|---|
| Framework | **Laravel 12**, PHP **8.2+** | Upgrade bertahap 8→9→10→11→12 di repo yang sama; struktur folder lama tetap dipertahankan (didukung Laravel 11+) |
| Database | MySQL 8 / MariaDB 10.6+ | utf8mb4 |
| Auth | Breeze (Blade) yang ada + route register diaktifkan untuk role `umum` | Email verification untuk akun umum |
| Otorisasi | `spatie/laravel-permission` 6 + Policy | |
| API | `laravel/sanctum` 4 (token) | Bagian 10 |
| Tabel admin | `yajra/laravel-datatables-oracle` 12, **server-side** | |
| PDF | `barryvdh/laravel-dompdf` | Sertifikat |
| QR | `simplesoftwareio/simple-qrcode` | Verifikasi sertifikat & tiket |
| Asset | **Vite** (ganti Laravel Mix) | Metronic tetap file statis di `public/` |
| UI admin | Metronic 8 Bootstrap 5 (sudah ada) | Pola view per modul: `index/create/edit/show` + partial `action`/`status` |
| UI publik | Blade + **Tailwind CSS** + Alpine.js (sudah ada di `package.json`) | Layout publik terpisah: `resources/views/components/site/*`, halaman di `resources/views/site/*` |
| File | Disk `public` + `php artisan storage:link`; simpan path relatif, tampilkan via `Storage::url()` | Nama file via `store()` (acak) |
| Queue | Driver `database` | Shared hosting: cron menjalankan `queue:work --stop-when-empty` |
| Scheduler | Cron `* * * * * php artisan schedule:run` | |
| Mail | SMTP (email domain / hosting) | Semua email lewat queue |
| Locale | `id`, timezone `Asia/Jakarta` | |
| Hosting | **Shared hosting cPanel** | Tanpa Redis, websocket, Docker. Document root diarahkan ke `public/` |

Prinsip:
- Pakai yang sudah ada dulu (controller, view, pola DataTables, select2, TinyMCE baraasa).
- Tanpa package baru di luar daftar di atas.
- Import/export CSV pakai `fgetcsv`/`fputcsv` native.

---

## 6. Design System Situs Publik

### 6.1 Token (diambil dari CSS akp2i.or.id)
```css
:root {
  --color-primary:        #00923f;  /* hijau AKP2I: tombol, link, aksen */
  --color-primary-hover:  #00612a;
  --color-secondary:      #00662c;  /* blok sekunder, badge */
  --color-heading:        #002d14;  /* judul */
  --color-text:           #000000;
  --color-muted:          #3f7959;  /* teks footer / sekunder */
  --color-footer-border:  #005726;
  --color-section-bg:     #f8faff;  /* latar section selang-seling */
  --color-border:         #e1e5ee;
  --color-surface:        #ffffff;
  --font-sans: 'Plus Jakarta Sans', sans-serif;
}
```
- Didaftarkan di `tailwind.config.js` sebagai `colors.brand.*` dan `fontFamily.sans`. Path `content` harus mencakup `resources/views/**/*.blade.php`.
- Font **Plus Jakarta Sans** (Google Fonts), bobot 400/500/600/700. Body 16px, line-height 1.6. Heading bold, line-height 1.2.
- Kontras minimal WCAG AA: teks putih di atas `#00923f` hanya untuk ukuran ≥ 18px bold; untuk teks kecil pakai `#00662c`.
- Dashboard admin **tidak** diganti temanya. Cukup: warna primary Metronic diubah ke `#00923f`, logo, dan judul aplikasi.

### 6.2 Komponen (Blade component `x-site.*`)
| Komponen | Spesifikasi |
|---|---|
| `topbar` | Kontak (telepon, email), sosmed, tombol "Pendaftaran Anggota" (link pusat), "Masuk" |
| `header` | Logo PD Jabar (dari Setting), menu utama dengan dropdown (Alpine), hamburger di < 1024px |
| `hero-slider` | Full-width, gambar + overlay hijau gelap 60%, judul, subjudul, CTA. Isi dari tabel `sliders` |
| `section-heading` | Eyebrow kecil uppercase hijau → heading besar → deskripsi |
| `stat-counter` | 4 kotak: Total Anggota, Anggota Berizin (Utama), Cabang (PC), Event Terselenggara — **dihitung dari database** |
| `card-post` | Thumbnail 16:10, badge kategori, judul, avatar + nama penulis, tanggal `DD MMM YYYY` |
| `card-event` | Poster, badge jenis (PPL/Brevet/Seminar), tanggal, mode (online/offline), kuota tersisa, tombol "Daftar" |
| `card-person` | Foto bulat/rounded, nama, jabatan, bidang (untuk halaman pengurus) |
| `button` | Uppercase, `rounded-md`, varian primary (hijau solid) & outline |
| `breadcrumb-header` | Header halaman dalam: judul + breadcrumb "Beranda › …" di atas latar hijau gelap |
| `footer` | Latar `#002d14`: logo + deskripsi singkat, alamat sekretariat PD, tautan cepat, tautan pusat (akp2i.or.id, eJurnal, DJP), sosmed, hak cipta |
| `pagination`, `empty-state`, `alert` | Konsisten di semua daftar |

Responsif mobile-first (breakpoint Tailwind default), gutter 16px di HP, tanpa scroll horizontal. Gambar `loading="lazy"`.

---

## 7. Sitemap Situs Publik

Menu tetap di kode (`resources/views/components/site/header.blade.php`); isi halaman dari CMS.

| Menu | URL | Sumber konten |
|---|---|---|
| **1. Beranda** | `/` | Slider, sambutan Ketua PD (`pages:sambutan`), statistik, 3 berita terbaru, 3 event mendatang, 6 foto galeri terbaru, CTA "Bergabung Menjadi Anggota" → `https://akp2i.id/pendaftaran-anggota/` |
| **2. Profil** | | |
| 2.1 Profil | `/profil` | `pages:profil` (sejarah, visi-misi) |
| 2.2 Pengurus (PD) | `/pengurus` | Kepengurusan PD periode aktif, dikelompokkan per bidang, urut `sort_order` |
| 2.3 Pengurus PC | `/pengurus-cabang` → `/pengurus-cabang/{slug-pc}` | Daftar PC (kartu: nama, kota, kontak, jumlah anggota) → pengurus periode aktif PC tersebut |
| 2.4 Galeri | `/galeri` → `/galeri/{slug}` | Album + lightbox |
| 2.5 Peraturan Organisasi | `/peraturan-organisasi` | Dokumen (AD/ART, PO, SK), dikelompokkan kategori, tombol unduh/lihat PDF |
| **3. Keanggotaan** | | |
| 3.1 Tentang Keanggotaan | `/keanggotaan` | `pages:tentang-keanggotaan` (jenis anggota, syarat, iuran) |
| 3.2 Layanan Keanggotaan | `/keanggotaan/layanan` | `pages:layanan-keanggotaan` + kartu tautan: **Pendaftaran Anggota Baru** → `https://akp2i.id/pendaftaran-anggota/`, **Pelayanan Anggota** (perpanjangan KTA, legalisir SK, peningkatan) → `https://akp2i.id/pelayanan-anggota/`. URL disimpan di Setting |
| 3.3 Cek Anggota | `/keanggotaan/cek` | Input nomor anggota → nama, jenis, PC, status (aktif/skors). Tanpa PII lain. Rate limit |
| **4. Pusat Pelatihan** | `/pelatihan` | Daftar event (filter: jenis, bulan, mode), tab "Akan Datang" / "Selesai" |
| 4.x Detail event | `/pelatihan/{slug}` | Detail, biaya anggota/umum, kuota, tombol daftar |
| **Berita & Artikel** (blog) | `/berita`, `/berita/{slug}`, `/berita/kategori/{slug}`, `/berita/tag/{slug}` | Blog |
| **Jurnal** | `/jurnal`, `/jurnal/{slug}` | Daftar + abstrak + unduh PDF; tautan eJurnal pusat |
| **Forum** | `/forum`, `/forum/{slug}` | Baca publik **[TBD]**, menulis harus login |
| **Hubungi Kami** | `/kontak` | Alamat, peta (embed dari Setting), form kontak → inbox |
| Halaman legal | `/kebijakan-privasi`, `/syarat-ketentuan` | `pages:*` |
| Verifikasi sertifikat | `/sertifikat/{kode}` | Nama peserta, event, tanggal, nomor sertifikat, status valid |
| Auth | `/login`, `/register` (akun umum), `/forgot-password` | Breeze |
| Dashboard | `/dashboard/...` | Aplikasi admin & area anggota |
| SEO | `/sitemap.xml`, `/robots.txt` | Otomatis |

Menu utama desktop: **Beranda · Profil ▾ · Keanggotaan ▾ · Pusat Pelatihan · Berita · Jurnal · Forum · Kontak**.

---

## 8. Modul & Kebutuhan Fungsional

Format setiap modul: tujuan → data → aturan → **AC** (acceptance criteria, wajib lulus feature test).

### 8.1 Struktur Organisasi (PD/PC)
**Tujuan**: memodelkan hierarki PP → PD → PC sehingga setiap anggota, pengurus, dan event terikat ke unit.

**Tabel `organizations`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| parent_id | FK organizations, null | PD → parent PP; PC → parent PD |
| type | enum `pusat`,`pd`,`pc` | |
| name | string | mis. "PC AKP2I Kota Bandung" |
| slug | string unique | |
| code | string(20) null unique | kode internal, mis. `JABAR-BDG` |
| city | string null | kota/kabupaten |
| address, phone, email | null | ditampilkan di halaman PC |
| is_active | bool default true | |
| timestamps, softDeletes | | |

**Seed**: 1 `pusat` (AKP2I), 1 `pd` (PD AKP2I Jawa Barat), dan `pc` untuk kab/kota Jabar yang punya cabang **[TBD daftar]**.

**Aturan**
- Unit "aktif" aplikasi = PD dari `config('akp2i.organization_id')`. Seluruh query publik di-scope ke PD ini dan PC turunannya. Ini cukup untuk nanti menjadi nasional tanpa ubah skema.
- PC tidak boleh dihapus bila masih punya anggota (hanya dinonaktifkan).

**AC**
- Admin PD bisa CRUD PC. Admin PC hanya melihat PC-nya.
- Halaman `/pengurus-cabang` menampilkan semua PC aktif beserta jumlah anggota aktif.

### 8.2 Keanggotaan
**Tujuan**: data anggota terpusat di PD, dengan PC asal yang jelas.

**Tabel `members`** (1–1 dengan `users`)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users unique, cascade | akun login anggota |
| organization_id | FK organizations | **PC** anggota (wajib type `pc`) |
| member_number | string unique | Nomor Anggota dari pusat |
| kip_number | string null | Nomor Izin Praktik Konsultan Pajak |
| membership_type | enum `utama`,`madya`,`pratama`,`kehormatan` | |
| certification | enum `A`,`B`,`C` null | sertifikasi konsultan pajak |
| brevet | json null | mis. `["A","B"]` |
| npwp | string null | hanya admin PD & pemilik |
| nik | string null | **hanya admin PD**; tidak pernah di API publik |
| office_name | string null | kantor/firma |
| joined_at | date null | |
| sk_number | string null | SK keanggotaan dari pusat |
| status | enum `aktif`,`skors`,`berhenti` default `aktif` | |
| notes | text null | catatan internal |
| timestamps, softDeletes | | |

Data pribadi (nama, gender, tempat/tanggal lahir, alamat, telepon, foto) tetap di `users`, kolom yang sudah ada.

**Aturan**
- **Hanya `admin_pd` (dan SA)** yang boleh membuat, mengubah, atau menghapus anggota. Admin PC hanya melihat.
- Membuat anggota = transaksi DB:
  1. buat `users` dengan password acak dan role `anggota`;
  2. buat `members`;
  3. kirim email "Aktivasi akun" berisi link set password (memakai `Password::broker()->createToken`, view email custom).
- Bila email sudah dipakai akun `umum`, akun itu **dinaikkan** ke `anggota` (bukan dibuat baru). Ini meneruskan pola `generalAssign` baraasa.
- Pindah PC: field `organization_id` diubah dan dicatat di `activity_logs`.
- Status `berhenti` → akun tetap ada, role diturunkan ke `umum`, tidak tampil di "Cek Anggota" sebagai aktif.
- **Import CSV**: template diunduh dari halaman import. Kolom: `nama,email,telepon,nomor_anggota,nomor_kip,jenis,kode_pc,sertifikasi,brevet,npwp,tanggal_bergabung`.
  - Validasi per baris; baris valid disimpan, baris gagal ditampilkan beserta alasannya dan bisa diunduh.
  - Email aktivasi untuk hasil import dikirim lewat queue, dan bisa ditunda dengan centang "kirim aktivasi nanti".
- **Export CSV** sesuai filter aktif.
- DataTable anggota: filter PC, jenis, status, status iuran; pencarian nama/nomor/email.

**AC**
- Admin PC yang membuka URL create/edit anggota mendapat 403.
- Anggota baru menerima email aktivasi dan bisa login setelah set password.
- Import 500 baris dengan 3 baris salah: 497 masuk, 3 dilaporkan dengan nomor baris dan alasan.
- `member_number` duplikat ditolak.
- Detail anggota menampilkan: data diri, PC, jenis, iuran, jabatan PD & PC aktif, riwayat event, jam PPL.

### 8.3 Iuran (status saja)
**Tabel `member_dues`**: id, member_id FK, year (smallint), amount (decimal 12,2), paid_at (date), proof_file (null), recorded_by FK users, notes, timestamps. Unique `(member_id, year)`.

**Aturan**
- Admin PD mencatat pembayaran (tidak ada pembayaran online).
- Status iuran dihitung (accessor, tidak disimpan):
  - **Lunas**: ada baris untuk tahun berjalan.
  - **Belum lunas**: tidak ada baris tahun berjalan.
  - **Menunggak**: tahun lalu belum lunas dan tanggal ≥ 1 Mei tahun berjalan.
- Job terjadwal harian membuat **daftar usulan skors** untuk anggota yang menunggak. Admin PD meninjau, lalu mengubah status ke `skors`. Tidak ada perubahan status otomatis tanpa konfirmasi.
- Nominal default per jenis anggota disimpan di Setting **[TBD nominal]**.

**AC**
- Anggota melihat riwayat iurannya di Profil; tidak bisa melihat iuran orang lain.
- Bukti pembayaran hanya bisa diunduh admin PD dan pemiliknya (route ber-policy, bukan URL publik).

### 8.4 Kepengurusan (PD & PC per periode)
**Tabel**
- `divisions` (ada) + kolom `level` enum `pd`,`pc`, `sort_order`. Contoh: Bidang Organisasi, Bidang Pendidikan & Pelatihan.
- `positions` (ada) + `sort_order`. Unique diganti dari `name` menjadi `(division_id, name)`. `division_id` boleh null untuk jabatan inti (Ketua, Sekretaris, Bendahara).
- **`board_periods`**: id, organization_id FK (PD atau PC), name ("2024–2029"), starts_at, ends_at, is_active bool, sk_number null, sk_file null, timestamps. Hanya satu `is_active` per organisasi.
- **`board_members`**: id, board_period_id FK, user_id FK, division_id FK null, position_id FK, sort_order, timestamps. Unique `(board_period_id, user_id, position_id)`.

**Migrasi**: kolom `users.division_id` dan `users.position_id` dipindahkan ke `board_members` di periode awal, lalu dihapus.

**Aturan**
- Satu anggota boleh menjabat di PD **dan** PC sekaligus, dan boleh lebih dari satu jabatan.
- Pengurus wajib user ber-role `anggota`, kecuali `super_administrator`/`admin_pd` yang juga anggota.
- Halaman publik hanya menampilkan periode aktif. Riwayat periode lama bisa dilihat di dashboard dan opsional di publik (`?periode=`).
- Menyalin periode: tombol "Buat periode baru dari periode ini" untuk mempercepat input.

**AC**
- Profil anggota menampilkan "Jabatan PD: …" dan "Jabatan PC: …" dari periode aktif.
- `/pengurus` dan `/pengurus-cabang/{pc}` tampil terurut per `sort_order` divisi → jabatan → anggota.

### 8.5 CMS Website
| Tabel | Kolom utama | Dipakai di |
|---|---|---|
| `pages` | slug unique, title, excerpt, body (HTML TinyMCE), cover, meta_title, meta_description, is_published, updated_by | Profil, Sambutan, Tentang Keanggotaan, Layanan Keanggotaan, Kebijakan Privasi, S&K (slug tetap, di-seed; tidak bisa dihapus) |
| `sliders` | title, subtitle, image, button_text, button_url, sort_order, is_active | Hero beranda |
| `galleries` | title, slug, description, cover, event_id null, published_at | Album galeri |
| `gallery_photos` | gallery_id, image, caption, sort_order | Foto dalam album (upload banyak sekaligus) |
| `documents` | title, slug, category (`AD/ART`,`Peraturan Organisasi`,`SK`,`Lainnya`), file (PDF), description, published_at, sort_order | Peraturan Organisasi |
| `contact_messages` | name, email, phone, city, subject (`Keanggotaan`,`Pelatihan`,`Iuran`,`Informasi Cabang`,`Lainnya`), message, read_at | Inbox Hubungi Kami |
| `settings` (diperluas, tetap 1 baris) | site_name, tagline, logo, logo_footer, favicon, address, phone, whatsapp, email, facebook, instagram, youtube, linkedin, maps_embed, url_registration (default `https://akp2i.id/pendaftaran-anggota/`), url_member_service (default `https://akp2i.id/pelayanan-anggota/`), url_ejournal, dues_amounts (json), bank_account_info | Header, footer, CTA, event berbayar. Kolom lama `spotify` & `recruitment_*` dihapus |

**Aturan**
- Upload gambar: jpg/png/webp maksimal 3 MB; PDF maksimal 10 MB.
- File lama dihapus dari disk saat diganti atau dihapus.
- Setiap halaman publik punya `<title>`, meta description, OpenGraph (judul, deskripsi, gambar), canonical. `sitemap.xml` dibangkitkan dari pages, blogs, events, research, galleries yang terbit.
- Form kontak: validasi, honeypot, throttle 5 kiriman per menit per IP; notifikasi email ke alamat Setting.

**AC**
- Editor mengubah teks "Tentang Keanggotaan" dan perubahan langsung tampil di `/keanggotaan`.
- Mengubah `url_member_service` di Setting mengubah semua tombol Layanan Anggota.
- Halaman berita yang dibagikan di WhatsApp menampilkan preview judul dan gambar.

### 8.6 Blog (Berita & Artikel)
**Perubahan `blogs`**:
- tambah `category_id` FK, `excerpt`, `published_at`, `status` enum `draft`,`review`,`published` (menggantikan `publication_status`), `views`, `organization_id` (PD/PC penulis), softDeletes;
- tabel `categories` (name, slug), `tags` (name, slug), `blog_tag`.

**Aturan**
- Anggota & admin PC menulis → status `review`. Editor/admin PD menerbitkan.
- Penulis hanya bisa mengedit tulisannya saat masih `draft`/`review`.
- Slug dibuat sekali saat pertama dibuat (tidak berubah saat judul diedit, supaya tautan tidak putus).
- `views` bertambah satu per sesi.
- Halaman publik: daftar + paginasi 9, filter kategori/tag, pencarian, artikel terkait (kategori sama), tombol bagikan (WhatsApp, Facebook, X, salin tautan).

**AC**
- Request `PATCH` dari anggota dengan `status=published` tetap tersimpan sebagai `review`.
- Anggota A tidak bisa mengedit atau menghapus blog anggota B (403).

### 8.7 Forum Diskusi
**Perubahan**:
- `threads`: tambah `forum_category_id`, `is_pinned`, `is_locked`, `views`, `last_activity_at`, softDeletes; ganti `publication_status` menjadi `status` (`published`,`hidden`).
- `comments`: tambah `parent_id` null (balasan satu level), softDeletes.
- Tabel `forum_categories` (name, slug, description, sort_order), `reports` (reportable morph, user_id, reason, resolved_at).

**Aturan**
- Menulis thread & komentar: anggota, admin PC, dan umum **[TBD]**. Membaca: publik **[TBD]**.
- Penulis bisa mengedit atau menghapus thread/komentarnya sendiri. Moderator (editor, admin PD) bisa menyembunyikan, mengunci, atau menyematkan.
- Thread terkunci tidak bisa dibalas.
- Pengguna bisa "Laporkan" konten. Moderator melihat antrean laporan.
- Pemilik thread mendapat email (queue) saat ada balasan, dan bisa dimatikan di profil.
- Throttle: 10 komentar per menit per user.

**AC**
- Mengedit thread milik orang lain → 403. Edit oleh pemilik tidak mengubah `user_id`.
- Komentar di thread terkunci ditolak.

### 8.8 Jurnal
**Perubahan `research`**: tambah `slug`, `authors` (string, dipisah koma), `abstract` (text), `keywords`, `category` (string), `cover` null, `uploaded_by` FK users, `status` (`draft`,`published`), `downloads`, softDeletes.

**Aturan**
- Dikelola editor/admin PD.
- Halaman publik `/jurnal`: daftar, pencarian judul/penulis/kata kunci, filter kategori & tahun.
- Detail menampilkan abstrak, tombol "Unduh PDF" (lewat route penghitung unduhan), dan sitasi sederhana.
- Tautan ke eJurnal pusat (dari Setting).

**AC**: jurnal `draft` tidak tampil dan tidak bisa diunduh publik.

### 8.9 Acara / Event (Pendaftaran & Sertifikat)
**Perubahan `events`**
| Kolom baru | Keterangan |
|---|---|
| organization_id | penyelenggara (PD atau PC) |
| type | enum `ppl`,`brevet_a`,`brevet_b`,`brevet_c`,`seminar`,`webinar`,`lainnya` |
| code | kode event unik pendek (untuk nomor sertifikat), mis. `PPL2611` |
| mode | enum `offline`,`online`,`hybrid` |
| location | ada; meeting_url baru (hanya tampil ke peserta terverifikasi) |
| starts_at, ends_at | datetime (menggantikan `date_start/date_end`) |
| registration_opens_at, registration_closes_at | |
| quota | int null (null = tak terbatas) |
| audience | enum `anggota`,`publik` |
| fee_member, fee_public | decimal; 0 = gratis |
| skp_hours | decimal null — jam/SKP PPL |
| status | enum `draft`,`open`,`closed`,`done` (menggantikan `event_status`) |
| certificate_template | gambar latar A4 landscape null |
| certificate_signatory | json: nama & jabatan penandatangan (maks. 2), gambar tanda tangan |
| softDeletes | |

**Perubahan `participants`**:
- tambah `status` enum `pending`,`verified`,`rejected`,`cancelled` (menggantikan `data_status`), `payment_proof` null, `amount` decimal, `verified_by`, `verified_at`, `attended_at` null, `ticket_code` (unik, untuk QR check-in), `notes`;
- `reason` & `file` dijadikan nullable (opsional per event);
- unique `(event_id, user_id)`.

**Tabel `certificates`**: id, participant_id unique, number unique, verification_code (string acak 12 karakter, unique), file (PDF), issued_at, revoked_at null.

**Alur pendaftaran**
1. Pengunjung membuka `/pelatihan/{slug}` → klik **Daftar** → login atau buat akun umum.
2. Sistem memeriksa: status `open`, dalam jendela pendaftaran, kuota tersisa, audience (event `anggota` hanya untuk role anggota), belum terdaftar.
3. Biaya = `fee_member` untuk anggota aktif, selain itu `fee_public`.
   - Biaya 0 → status `verified` otomatis.
   - Biaya > 0 → status `pending`; tampil info rekening (Setting) dan form upload bukti transfer.
4. Admin memverifikasi atau menolak (dengan alasan). Email dikirim untuk setiap perubahan status.
5. Peserta `verified` melihat tiket (QR `ticket_code`) dan `meeting_url` di dashboard.
6. Hari-H: admin menandai hadir dengan memindai QR (halaman check-in mobile-friendly, input kode manual sebagai cadangan) atau centang massal di DataTable.
7. Event `done` → admin klik **Terbitkan Sertifikat**. Sistem meng-generate PDF untuk semua peserta yang hadir (job queue, per peserta).
   - Isi PDF: nama, judul event, tanggal, jam SKP, nomor, QR ke `/sertifikat/{verification_code}`, tanda tangan.
   - Nomor sertifikat: `{urut 3 digit}/SERT/{code}/AKP2I-JABAR/{tahun}` **[TBD format resmi]**.
8. Peserta mengunduh sertifikat di Profil. Halaman verifikasi publik menampilkan valid / dicabut.

**Fitur admin**: duplikasi event, export peserta CSV, kirim email pengumuman ke semua peserta verified, cabut atau terbitkan ulang sertifikat. Upload sertifikat manual tetap tersedia sebagai cadangan (fitur lama baraasa).

**Rekap PPL**: jumlah `skp_hours` dari event yang dihadiri per anggota per tahun. Ditampilkan di Profil anggota dan detail anggota (admin), serta bisa diekspor CSV.

**AC**
- Pendaftar ke-(kuota+1) ditolak dengan pesan "Kuota penuh".
- Umum tidak bisa mendaftar event `audience=anggota`.
- Anggota dengan status `skors` dikenakan `fee_public` **[TBD]**.
- `meeting_url` tidak muncul di HTML untuk peserta `pending`.
- Sertifikat hanya terbit untuk peserta `attended_at` tidak null. Halaman `/sertifikat/{kode}` menampilkan data yang benar; kode salah → 404.
- Generate 300 sertifikat berjalan lewat queue tanpa timeout request.

### 8.10 Pengguna Aplikasi
- Satu halaman **Pengguna** dengan tab per role (mengembangkan `AdministratorController`, `GeneralController`, `MemberController`): cari, filter role/PC, aktif/nonaktif (`users.is_active`; user nonaktif tidak bisa login), kirim ulang link aktivasi/reset password, ubah role.
- Admin PC (`admin_pc`) wajib diikat ke satu PC: kolom `users.organization_id` untuk scope admin PC.
- Aturan baraasa dipertahankan: maksimal 5 super admin, super admin tidak bisa dihapus. Ditambah: hanya super admin yang bisa membuat/mengubah admin PD.
- Registrasi mandiri (`/register`) selalu menghasilkan role `umum` + verifikasi email.
- Login: throttle (Breeze), opsi "ingat saya".

**AC**: admin PD yang mencoba mengubah role user menjadi `super_administrator` lewat request langsung → 403.

### 8.11 Profil
- Halaman `/dashboard/profil` (user sendiri, tanpa `{user}` di URL):
  - data diri, foto (crop persegi di sisi klien bila memungkinkan, maksimal 2 MB), ganti password, preferensi notifikasi.
- Bagian **Keanggotaan** (role anggota, read-only):
  - nomor anggota, jenis, PC, sertifikasi, status keanggotaan, status iuran + riwayat, jabatan PD/PC aktif;
  - tombol ke Layanan Anggota pusat.
- Bagian **Pelatihan**: event yang diikuti (status), tiket, sertifikat (unduh), total jam PPL tahun ini.
- Bagian **Tulisan saya**: blog & thread milik user.

**AC**: `PATCH /dashboard/profil` hanya pernah mengubah user yang sedang login (test B1).

### 8.12 Modul dihapus
- **Recruitment** beserta controller, views, menu, permission, tabel (`recruitments`), dan field `recruitment_*` di settings.

### 8.13 Audit Log & Notifikasi
- Tabel `activity_logs`: user_id, action (`created`,`updated`,`deleted`,`status_changed`, …), subject_type, subject_id, changes (json before/after), ip, created_at. Dicatat lewat model observer untuk `members`, `member_dues`, `board_members`, `board_periods`, role user, `participants.status`, `certificates`.
- Halaman log (admin PD & SA), filter per subjek/user/tanggal.
- Email (semua via queue): aktivasi akun, reset password, status pendaftaran event, sertifikat terbit, balasan forum, pesan kontak masuk (ke admin), blog menunggu review (ke editor).

---

## 9. Kebutuhan Non-Fungsional

| Area | Kebutuhan |
|---|---|
| Keamanan | Policy di setiap aksi; validasi semua input (Form Request); upload divalidasi mime & ukuran; file privat (bukti bayar, NIK) di disk `local` lewat route ber-policy; CSRF; rate limit login, kontak, cek anggota, komentar; header keamanan dasar; tidak ada data sensitif di log |
| Privasi (UU PDP) | Data publik anggota hanya nama, jenis, PC, status, dan jabatan pengurus. NIK/NPWP/telepon/alamat tidak pernah tampil publik atau di API umum. Halaman Kebijakan Privasi |
| Performa | DataTables server-side; eager loading (tanpa N+1); paginasi; cache beranda & statistik 10 menit (driver `file`), dibersihkan saat konten berubah; gambar di-resize saat upload bila GD tersedia, bila tidak cukup batas ukuran |
| Ketersediaan data | SoftDeletes pada anggota & konten; backup DB harian via perintah `php artisan backup:database` (mysqldump → `storage/app/backups`, simpan 14 hari) dijadwalkan scheduler; panduan backup cPanel |
| Aksesibilitas | Kontras AA, `alt` gambar, label form, navigasi keyboard di menu dropdown |
| Kompatibilitas | Chrome, Edge, Firefox, Safari 2 versi terakhir; Android Chrome; lebar minimal 360px |
| Kualitas kode | Mengikuti pola baraasa (controller resource, view per modul, partial `action`/`status`); feature test PHPUnit untuk setiap AC; `php artisan test` hijau sebelum milestone ditutup |
| Observabilitas | Log Laravel harian; halaman `failed_jobs` sederhana untuk admin |

---

## 10. API Mobile v1

Prefix `/api/v1`, JSON, autentikasi **Sanctum token** (`Authorization: Bearer`). Respons memakai API Resource; daftar dipaginasi (`?page=`, `per_page` maksimal 50). Error berformat Laravel standar (`message`, `errors`).

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/auth/login` | – | email, password, device_name → token |
| POST | `/auth/logout` | ✓ | cabut token saat ini |
| POST | `/auth/register` | – | akun umum |
| POST | `/auth/forgot-password` | – | |
| GET | `/me` | ✓ | profil + role |
| PATCH | `/me` | ✓ | data diri |
| GET | `/me/membership` | ✓ anggota | nomor, jenis, PC, status, iuran, jabatan |
| GET | `/me/registrations` | ✓ | event yang diikuti + tiket |
| GET | `/me/certificates` | ✓ | daftar + URL unduh bertanda tangan (signed URL) |
| GET | `/pages/{slug}` | – | konten CMS |
| GET | `/sliders` | – | |
| GET | `/blogs`, `/blogs/{slug}` | – | filter `category`, `q` |
| GET | `/events`, `/events/{slug}` | – | filter `type`, `upcoming` |
| POST | `/events/{slug}/register` | ✓ | multipart (bukti bayar opsional) |
| GET | `/threads`, `/threads/{slug}` | – / ✓ | sesuai keputusan TBD forum |
| POST | `/threads`, `/threads/{slug}/comments` | ✓ | |
| GET | `/research`, `/research/{slug}` | – | |
| GET | `/organizations` | – | daftar PC |
| GET | `/organizations/{slug}/board` | – | pengurus periode aktif |
| GET | `/galleries`, `/galleries/{slug}` | – | |
| GET | `/certificates/verify/{code}` | – | verifikasi |

Rate limit 60 request/menit per token/IP. Dokumentasi endpoint ditulis di `docs/API.md`, dengan contoh request/response per endpoint. Aplikasi Android (fork `baraasaproject-app`, Java) disesuaikan setelah API stabil, sebagai proyek terpisah.

---

## 11. Model Data (ringkas)

```
organizations (self-ref parent_id: pusat → pd → pc)
 ├─< members >── users (1–1)                 users ──< model_has_roles (spatie)
 │      └─< member_dues                       users.organization_id → organizations (scope admin_pc)
 ├─< board_periods ──< board_members >── users
 │                         ├── divisions (level pd|pc) ──< positions
 │                         └── positions
 ├─< events ──< participants >── users
 │                  └── certificates (1–1)
 └─< blogs (organization_id)

blogs >── categories ; blogs >─< tags (blog_tag) ; blogs >── users
forum_categories ──< threads ──< comments (parent_id self-ref) ; reports (morph)
research >── users (uploaded_by)
pages · sliders · galleries ──< gallery_photos · documents · contact_messages · settings (1 row)
activity_logs (morph subject)
```

**Tabel baru**: organizations, members, member_dues, board_periods, board_members, categories, tags, blog_tag, forum_categories, reports, certificates, pages, sliders, galleries, gallery_photos, documents, contact_messages, activity_logs, jobs (queue).

**Tabel diubah**: users (+organization_id, +is_active, −division_id, −position_id), divisions, positions, blogs, threads, comments, research, events, participants, settings.

**Tabel dihapus**: recruitments.

Migrasi perubahan ditulis sebagai migrasi baru (bukan mengedit migrasi lama), supaya data yang sudah ada bisa dimigrasikan.

---

## 12. Roadmap & Milestone

Setiap milestone ditutup bila: semua AC terkait punya feature test yang lulus, `php artisan test` hijau, dan dicek manual di lebar desktop & 360px.

### M0 — Fondasi
1. Upgrade bertahap Laravel 8→9→10→11→12 (+ spatie 6, sanctum 4, yajra 12, Breeze/auth disesuaikan, Mix → Vite). Satu commit per lompatan versi, test hijau di setiap langkah.
2. Rebranding: nama, logo, meta, halaman login, warna primary Metronic `#00923f`, `Asia/Jakarta`.
3. Perbaiki B1–B14 (Bagian 3.3), masing-masing dengan test regresi.
4. Role baru & rename (`admin_pd`, `editor`, `admin_pc`, `anggota`, `umum`) lewat migrasi data + seeder permission baru (matriks 4.3); policy nyata untuk semua modul.
5. Hapus modul Recruitment.
6. Config `config/akp2i.php` (organization_id aktif, URL pusat default).

### M1 — Organisasi & Keanggotaan
organizations + seed PD/PC, members (CRUD, aktivasi email, import/export CSV), member_dues + usulan skors terjadwal, divisions/positions per level, board_periods/board_members + migrasi data jabatan lama, scope admin_pc, activity_logs, menu dashboard baru.

### M2 — Situs Publik & CMS
Layout publik + design token (Bagian 6), semua halaman Bagian 7 kecuali yang bergantung pada M3/M4 (menampilkan empty state dulu), CMS pages/sliders/galleries/documents/settings/contact_messages, Cek Anggota, SEO (meta, OG, sitemap.xml, robots.txt).

### M3 — Blog, Forum, Jurnal
Perubahan skema 8.6–8.8, alur review blog, moderasi forum & laporan, jurnal publik, halaman publik masing-masing, notifikasi terkait.

### M4 — Event, Pendaftaran & Sertifikat
Skema 8.9, pendaftaran mandiri + upload bukti bayar, verifikasi, tiket QR & halaman check-in, generate sertifikat PDF via queue, verifikasi publik, rekap PPL, export.

### M5 — API Mobile v1
Endpoint Bagian 10, API Resources, rate limit, test API, `docs/API.md`.

### M6 — Go-live
- Panduan deploy cPanel di `docs/DEPLOY.md`: upload, `.env`, `storage:link`, cron scheduler + queue, SMTP.
- Import data anggota riil, isi konten awal CMS.
- UAT bersama admin PD (checklist dari AC).
- Perbaikan hasil UAT.

---

## 13. Pertanyaan Terbuka [TBD]

| # | Pertanyaan | Dampak | Default bila belum dijawab |
|---|---|---|---|
| Q1 | Daftar PC AKP2I di Jawa Barat yang aktif (nama, kota, kontak) | Seed `organizations` | Seed contoh beberapa PC, bisa diedit admin |
| Q2 | Format resmi Nomor Anggota | Validasi `member_number` | String bebas, unique |
| Q3 | Format nomor sertifikat & penandatangan | 8.9 | Format di 8.9, penandatangan diisi per event |
| Q4 | Iuran: tahunan atau bulanan? Nominal PD Jabar per jenis | 8.3 | Tahunan, nominal diisi di Setting |
| Q5 | Struktur bidang & jabatan PD dan PC | Seed divisions/positions | Ketua, Wakil Ketua, Sekretaris, Bendahara + bidang diisi admin |
| Q6 | Forum: boleh dibaca publik? umum boleh menulis? | 8.7, API | Baca publik, menulis hanya anggota |
| Q7 | Anggota berstatus skors: biaya event anggota atau umum? | 8.9 | Biaya umum |
| Q8 | Rekening untuk event berbayar | Setting | Diisi admin |
| Q9 | Domain & email pengirim (SMTP) | Deploy | – |
| Q10 | Konten awal: profil, sejarah, visi-misi, sambutan, peraturan organisasi, logo PD | M2/M6 | Placeholder yang jelas ditandai |
| Q11 | Perlu versi bahasa Inggris? | Seluruh situs | Tidak (v1 hanya Bahasa Indonesia) |

---

## 14. Definition of Done (per fitur)
- [ ] Migrasi + model + relasi + factory
- [ ] Policy & permission sesuai matriks 4.3; scope PC diterapkan di query
- [ ] Form Request validasi; upload aman
- [ ] View admin mengikuti pola baraasa; view publik memakai komponen `x-site.*`
- [ ] Feature test untuk setiap AC (termasuk kasus 403)
- [ ] Tampil benar di 360px dan desktop, tanpa error di console
- [ ] Teks UI Bahasa Indonesia, tanggal format Indonesia
- [ ] Menu dashboard & seeder permission diperbarui
- [ ] Tidak ada PII yang bocor ke halaman publik atau API umum
