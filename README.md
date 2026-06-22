# Carian Jodoh 💕

Sistem cari jodoh rawak dalam Bahasa Melayu. Identiti tersembunyi sehingga kedua-dua pengguna tekan butang ❤️.

---

## Cara Deploy (Railway + GitHub)

### 1. Upload ke GitHub

1. Buka [github.com](https://github.com) → buat repo baru (private kalau nak)
2. Upload **semua fail** dari folder ini ke repo tu
   - Pastikan upload termasuk fail tersembunyi seperti `.gitignore` dan `.env.example`
   - **Jangan upload folder `vendor/`** — Railway akan install sendiri

### 2. Setup Railway

1. Pergi ke [railway.app](https://railway.app) → **New Project**
2. Pilih **Deploy from GitHub repo** → pilih repo yang baru upload
3. Tambah **MySQL database**: klik **+ New** → **Database** → **MySQL**
4. Pergi ke service Laravel anda → tab **Variables** → tambah semua ini:

```
APP_NAME=Carian Jodoh
APP_ENV=production
APP_DEBUG=false
APP_KEY=                  ← WAJIB ISI (tengok cara bawah)
APP_URL=                  ← isi lepas dapat domain Railway (cth: https://xxx.railway.app)

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
LOG_CHANNEL=stderr
```

> **Cara generate APP_KEY:**
> Pergi ke [generate-random.org/encryption-key-generator](https://generate-random.org/encryption-key-generator)
> → pilih **256-bit** → copy hasilnya → tampal dalam `APP_KEY` dengan prefix `base64:`
>
> Contoh: `APP_KEY=base64:AbCdEfGh...` (32 random bytes dalam base64)

5. Railway akan auto-deploy. Tunggu build habis.

---

## Login Admin

| | |
|---|---|
| **URL** | `https://your-domain.railway.app/admin/login` |
| **Username** | `admin` |
| **Password** | `TrGVdxcdZXbIfr` |

> ⚠️ Tukar password selepas login pertama di halaman **Settings**.

---

## Benda Pertama Nak Buat Lepas Deploy

1. Log masuk ke `/admin/login`
2. Pergi **Settings** → upload **QR code DuitNow/bank** anda
3. Tukar **password admin** kepada sesuatu yang anda ingat
4. Test daftar sebagai user biasa

---

## Pakej Credit

| Pakej | Harga |
|---|---|
| 50 kali main | RM 2.00 |
| 100 kali main | RM 3.00 |

User percuma dapat **5 kali main sebulan** (auto-refresh).

---

## Stack

- Laravel 10
- MySQL (Railway)
- Plain CSS (no Vite/npm)
- JS polling (no WebSocket)
- Storage: Railway disk (public)
