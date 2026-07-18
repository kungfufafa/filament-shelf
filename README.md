# Filament Shelf

Aplikasi backend panel admin dan API untuk sistem **Shelf** yang dibangun menggunakan Laravel & Filament, serta menggunakan tema **Mekaya Admin Panel**.

## Fitur Utama

- **Laravel 13** & **PHP 8.4**
- **Filament v5** Admin Panel
- Integrasi **Mekaya Theme** dengan sidebar kustom, topbar, Blade components, dan UntitledUI icons
- Konfigurasi **Vite** terintegrasi dengan resource Mekaya (`theme.css` dan `mekaya.js`)
- Dukungan API **Laravel Sanctum**
- Konfigurasi **Laravel Boost** untuk optimalisasi pengembangan berbasis AI agentic

---

## Instalasi & Setup

1. **Clone repositori & install dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database & Migrasi:**
   Pastikan pengaturan database di `.env` sudah benar, lalu jalankan:
   ```bash
   php artisan migrate
   ```

4. **Kompilasi Frontend Assets:**
   Lakukan compile aset Vite termasuk stylesheet dan script dari Mekaya Theme:
   ```bash
   npm run build
   ```

5. **Jalankan Server Development:**
   ```bash
   composer run dev
   ```

---

## Integrasi Mekaya Theme

Proyek ini menggunakan package `kungfufafa/mekaya-theme`. Tema ini dimuat di [AdminPanelProvider.php](app/Providers/Filament/AdminPanelProvider.php) dan dikompilasi menggunakan Vite.

Untuk memastikan aset terkompilasi dengan benar, pastikan file berikut terdaftar di input [vite.config.js](vite.config.js):
- `vendor/kungfufafa/mekaya-theme/resources/css/theme.css`
- `vendor/kungfufafa/mekaya-theme/resources/js/mekaya.js`
