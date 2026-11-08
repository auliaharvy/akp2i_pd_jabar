<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
| Situs publik (PRD 7). Menu tetap di resources/views/components/site/header.blade.php.
*/
Route::controller(SiteController::class)->name('site.')->group(function () {
    Route::get('/', 'home')->name('home');

    # Profil
    Route::get('profil', 'profile')->name('profile');
    Route::get('pengurus', 'board')->name('board');
    Route::get('pengurus-cabang', 'branches')->name('branches');
    Route::get('pengurus-cabang/{slug}', 'branch')->name('branch');
    Route::get('galeri', 'galleries')->name('gallery');
    Route::get('galeri/{slug}', 'gallery')->name('gallery.show');
    Route::get('peraturan-organisasi', 'documents')->name('documents');

    # Keanggotaan
    Route::get('keanggotaan', 'membership')->name('membership');
    Route::get('keanggotaan/layanan', 'memberServices')->name('membership.services');
    Route::get('keanggotaan/cek', 'memberCheck')->middleware('throttle:member-check')->name('membership.check');

    # Kontak & legal
    Route::get('kontak', 'contact')->name('contact');
    Route::post('kontak', 'sendContact')->middleware('throttle:contact')->name('contact.send');
    Route::get('kebijakan-privasi', 'privacy')->name('privacy');
    Route::get('syarat-ketentuan', 'terms')->name('terms');
    Route::get('halaman/{slug}', 'page')->name('page');

    # Menyusul M3/M4: tampil empty state
    $soon = fn (string $uri, string $name, string $title, string $milestone) => Route::get($uri, 'comingSoon')
        ->name($name)->defaults('title', $title)->defaults('milestone', $milestone);
    $soon('pelatihan', 'training', 'Pusat Pelatihan', 'M4');
    $soon('pelatihan/{slug}', 'training.show', 'Pusat Pelatihan', 'M4');
    $soon('berita', 'news', 'Berita & Artikel', 'M3');
    $soon('berita/{slug}', 'news.show', 'Berita & Artikel', 'M3');
    $soon('jurnal', 'journal', 'Jurnal', 'M3');
    $soon('jurnal/{slug}', 'journal.show', 'Jurnal', 'M3');
    $soon('forum', 'forum', 'Forum Diskusi', 'M3');
    $soon('forum/{slug}', 'forum.show', 'Forum Diskusi', 'M3');
    $soon('sertifikat/{kode}', 'certificate', 'Verifikasi Sertifikat', 'M4');

    # SEO
    Route::get('sitemap.xml', 'sitemap')->name('sitemap');
    Route::get('robots.txt', 'robots')->name('robots');
});
