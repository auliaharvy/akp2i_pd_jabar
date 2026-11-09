# Data Demo

Data contoh yang realistis untuk presentasi aplikasi PD AKP2I Jawa Barat. **Bukan untuk produksi.**

## Menjalankan

```bash
php artisan migrate:fresh
php artisan db:seed --class='Database\Seeders\Demo\DemoSeeder'   # ±1 menit (termasuk ±200 PDF sertifikat)
php artisan storage:link   # sekali saja, agar gambar tampil
```

`php artisan migrate:fresh --seed` (seeder bawaan) tetap menghasilkan data uji sederhana yang dipakai test otomatis.

## Akun demo (password semua: `password`)

| Peran | Email |
|---|---|
| Super admin | superadmin@example.com |
| Admin PD (sekretariat) | sekretariat.pd@example.com |
| Editor (humas) | humas@example.com |
| Admin PC Kota Bandung / Bekasi / Bogor | adminpc.bandung@example.com · adminpc.bekasi@example.com · adminpc.bogor@example.com |
| Anggota (Utama, PC Kota Bandung) | anggota@example.com |
| Umum | umum@example.com |

Semua anggota lain juga bisa login dengan password `password` (lihat menu Data Anggota).

### Akun untuk skenario uji (password `password`)

Data demo deterministik, jadi akun ini sama setiap kali DemoSeeder dijalankan ulang.

| Skenario | Email | Nama |
|---|---|---|
| Ketua PD (pengurus inti, penanda tangan sertifikat) | parlindungan.harahap@example.com | Parlindungan Harahap |
| Sekretaris PD | arif.iskandar77@example.com | Arif Iskandar |
| Bendahara PD | dimas.nugraha@example.net | Dimas Nugraha |
| Admin PC dengan iuran belum lunas | adminpc.bandung@example.com | Bambang Purnomo |
| Anggota menunggak iuran (muncul di Usulan Skors) | siti.fauzi@example.com | Siti Fauzi |
| Anggota diskors | ratna.hermawan@example.org | Ratna Hermawan |
| Anggota berhenti (tidak lagi berperan anggota) | benny.salim@example.org | Benny Salim |
| Peserta pending (bukti transfer menunggu verifikasi) | ruhiyat.rukmana@example.com | Ruhiyat Rukmana — Brevet Angkatan III |
| Anggota dengan sertifikat | anggota@example.com | 2 event selesai, hadir |
| Umum dengan sertifikat + pendaftaran mendatang | umum@example.com | Brevet II, Seminar PPh 21, Webinar PPN (hadir); 2 event dibuka |

Seeder bawaan (`php artisan migrate:fresh --seed`, dipakai test otomatis) memakai akun lain, juga password `password`: `superadmin@mail.com`, `admin@mail.com` (admin PD), `editor@mail.com`, `adminpc@mail.com`, `member@mail.com` (anggota), `general@mail.com` (umum).

## Isi

- PD Jawa Barat + 12 PC (masing-masing dengan profil, sambutan ketua, dan kepengurusan 2025–2030).
- 150 anggota (aktif, skors, berhenti), iuran 4 tahun terakhir dengan sebagian menunggak (muncul di Usulan Skors), 25 akun umum.
- Kepengurusan PD 2024–2029 (pengurus inti + 5 bidang) dan riwayat 2019–2024.
- 11 event: 5 selesai (kehadiran + sertifikat PDF), 4 pendaftaran dibuka (pendaftar pending dengan bukti transfer, terverifikasi, ditolak), 1 ditutup, 1 draf.
- 15 berita (2 menunggu review, 1 draf), 12 diskusi forum dengan balasan & 1 laporan, 8 jurnal (PDF), 4 dokumen, 6 album galeri, 3 slider, 8 pesan kontak, halaman profil/sambutan/keanggotaan berisi teks lengkap.

## Catatan

- Semua **orang, alamat, NIK, NPWP, nomor telepon, dan rekening adalah fiktif**. Email memakai domain cadangan `example.com/.org/.net`, sehingga tidak pernah sampai ke orang sungguhan walau SMTP aktif.
- Foto galeri & latar poster: Wikimedia Commons (CC0/CC BY/CC BY-SA), sumber & pembuat di `database/seeders/demo-assets/photos/CREDITS.md`. Foto bukan dokumentasi kegiatan AKP2I; dipilih yang tanpa wajah dapat dikenali.
- Teks profil, sejarah, sambutan, dan artikel adalah contoh — ganti dengan konten resmi sebelum go-live (Q10). Artikel perpajakan sengaja ditulis umum tanpa angka/nomor regulasi.
- Logo: logo resmi AKP2I dari akp2i.or.id (`public/media/logos/akp2i.png`).
- Font poster: Plus Jakarta Sans (SIL Open Font License, `database/seeders/demo-assets/fonts/OFL.txt`).
