@echo off
chcp 65001 >nul
title Buku Tamu dan Antrean BPS Kab. Indragiri Hilir
color 0B

echo ==============================================================================
echo        SISTEM BUKU TAMU DAN ANTREAN BPS KABUPATEN INDRAGIRI HILIR
echo ==============================================================================
echo.

cd /d "%~dp0"

:: 1. Cek apakah php sudah ada di PATH
where php >nul 2>&1
if %ERRORLEVEL% EQU 0 goto :php_ready

:: Jika belum di PATH, cari di folder Laragon
if exist "C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" (
    set "PHP_DIR=C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64"
    goto :set_php_path
)

:: Cari folder php lain di C:\laragon\bin\php jika ada
for /d %%D in (C:\laragon\bin\php\*) do (
    if exist "%%D\php.exe" (
        set "PHP_DIR=%%D"
        goto :set_php_path
    )
)

:set_php_path
if defined PHP_DIR (
    set "PATH=%PHP_DIR%;C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;C:\laragon\bin\composer;%PATH%"
)

:php_ready
where php >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    color 0C
    echo [ERROR] PHP tidak ditemukan di sistem atau C:\laragon.
    echo Pastikan Laragon telah terpasang.
    echo.
    pause
    exit /b 1
)

:: 2. Cek apakah port MySQL 3306 sudah aktif
netstat -ano | findstr /R /C:":3306 .*LISTENING" >nul 2>&1
if %ERRORLEVEL% EQU 0 goto :mysql_ready

echo [INFO] Menyalakan database MySQL...
if exist "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe" (
    start "" /b "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe" --console
    timeout /t 3 /nobreak >nul
) else (
    echo [PERINGATAN] Pastikan database MySQL Anda sudah berjalan di Laragon.
)

:mysql_ready
:: 3. Cek file .env
if not exist ".env" (
    echo [INFO] Menyiapkan file .env...
    copy .env.example .env >nul
    php artisan key:generate
)

echo [OK] Basis data dan environment siap.
echo.
echo ==============================================================================
echo  Aplikasi berjalan di: http://localhost:8000
echo.
echo  AKUN LOGIN BAWAAN:
echo  * Admin        : admin / password
echo  * Resepsionis  : resepsionis / password
echo  * Petugas PST  : petugas1 (atau petugas2, petugas3) / password
echo.
echo  HALAMAN UTAMA:
echo  * Tamu Online  : http://localhost:8000/
echo  * Layar TV     : http://localhost:8000/layar
echo  * Resepsionis  : http://localhost:8000/resepsionis
echo  * Petugas      : http://localhost:8000/petugas
echo  * Admin        : http://localhost:8000/admin
echo  * Kios Lobi    : http://localhost:8000/kios
echo ==============================================================================
echo.
echo Membuka browser otomatis dalam 2 detik...
echo (Jangan tutup jendela terminal ini selama aplikasi digunakan).
echo (Tekan Ctrl + C untuk berhenti).
echo.

:: 4. Buka browser default ke http://localhost:8000
start "" cmd /c "timeout /t 2 /nobreak >nul & start http://localhost:8000"

:: 5. Jalankan server Laravel
php artisan serve --port=8000

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Server terhenti atau terjadi kendala.
    pause
)
