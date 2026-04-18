# Employee Leave Management System

Sistem REST API untuk manajemen pengajuan cuti karyawan dengan fitur autentikasi, validasi business logic, dan role-based access control.

## 📋 Teknologi

- **PHP**: 8.3+
- **Framework**: Laravel 13
- **Authentication**: Laravel Passport (OAuth2)
- **Database**: SQLite (default) / MySQL / PostgreSQL
- **Testing**: PHPUnit 12
- **File Storage**: PDF attachment support

## 🚀 Instalasi

### Prasyarat
- PHP 8.3 atau lebih tinggi
- Composer
- Node.js & npm (opsional, untuk frontend)

### Langkah-langkah

1. **Clone repository**
   ```bash
   git clone https://github.com/aditiaputra100/employee-leave-management-system.git
   cd employee-leave-management-system
   ```

2. **Install dependencies PHP**
   ```bash
   composer install
   ```

3. **Copy file environment**
   ```bash
   cp .env.example .env
   ```

4. **Generate application key**
   ```bash
   php artisan key:generate
   ```

5. **Buat database SQLite** (jika menggunakan SQLite)
   ```bash
   touch database/database.sqlite
   ```

6. **Jalankan migrasi database**
   ```bash
   php artisan migrate
   ```

7. **Jalankan seeder untuk data awal**
   ```bash
   php artisan db:seed
   ```

8. **Setup Passport OAuth2**
   ```bash
   php artisan passport:client --personal --no-interaction
   ```

9. **Setup symbolic link untuk file storage** (opsional, untuk download file)
   ```bash
   php artisan storage:link
   ```

10. **Jalankan development server**
    ```bash
    php artisan serve
    ```

Server akan berjalan di `http://localhost:8000`

---

## ⚙️ Konfigurasi Environment (.env)

### Variabel Penting

| Variabel | Default | Keterangan |
|---|---|---|
| `APP_NAME` | Laravel | Nama aplikasi |
| `APP_ENV` | local | Environment (`local` / `production`) |
| `APP_KEY` | - | Di-generate otomatis, jangan diubah manual |
| `APP_DEBUG` | true | Mode debug (set `false` di production) |
| `APP_URL` | http://localhost | Base URL aplikasi |
| `DB_CONNECTION` | sqlite | Driver database (`sqlite` / `mysql` / `pgsql`) |
| `DB_DATABASE` | database/database.sqlite | Path atau nama database |
| `FILESYSTEM_DISK` | local | Disk penyimpanan (`local` atau `public`) |

### Konfigurasi Database MySQL/PostgreSQL

Jika ingin menggunakan MySQL atau PostgreSQL, uncomment dan sesuaikan:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=employee_leave_management
DB_USERNAME=root
DB_PASSWORD=
```

### Konfigurasi File Upload

Agar file attachment dapat diakses via URL, jalankan:
```bash
php artisan storage:link
```

---

## 🏗️ Arsitektur Sistem

### Struktur Direktori Utama

| Path | Fungsi |
|---|---|
| `app/Models/User.php` | Model User dengan role (admin/employee) dan relasi ke LeaveRequest |
| `app/Models/LeaveRequest.php` | Model pengajuan cuti dengan relasi ke User |
| `app/Http/Controllers/Api/AuthController.php` | Controller autentikasi (register, login, logout) |
| `app/Http/Controllers/Api/LeaveRequestController.php` | Controller CRUD pengajuan cuti |
| `routes/api.php` | Definisi semua route API |
| `database/migrations/` | File migrasi database |
| `database/seeders/UserSeeder.php` | Seeder data user admin dan employee |
| `database/seeders/LeaveRequestSeeder.php` | Seeder data dummy pengajuan cuti |
| `tests/Feature/Api/` | Unit test untuk semua endpoint API |

### Autentikasi & Otorisasi

#### Laravel Passport (OAuth2)
- Token-based authentication menggunakan JWT
- Setiap user mendapat token akses saat register/login
- Token berisi scope sesuai role user (`admin` atau `employee`)
- Token expire time: 7 hari

#### Role-Based Access Control (RBAC)
Sistem memiliki 2 role:

**Admin**
- Melihat semua pengajuan cuti dari semua karyawan
- Melihat detail pengajuan cuti siapapun
- Mengubah status pengajuan cuti (approve/reject)
- Hanya dapat mengubah status yang masih `pending`

**Employee**
- Mengajukan pengajuan cuti baru
- Melihat daftar pengajuan cutinya sendiri saja
- Melihat detail pengajuan cutinya sendiri saja
- Tidak dapat mengubah status

#### Middleware
- `auth:api` — Melindungi semua endpoint yang membutuhkan login
- `CheckToken::using('admin')` — Melindungi endpoint yang hanya boleh admin akses

### Database Schema

#### Tabel `users`
```sql
- id (bigint, primary key)
- name (string) — Nama user
- email (string, unique) — Email user
- password (string) — Password (hashed)
- role (string, default: 'employee') — Role user (admin / employee)
- created_at, updated_at (timestamp)
```

#### Tabel `leave_requests`
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key) — FK ke users.id, cascade delete
- start_date (datetime) — Tanggal mulai cuti
- end_date (datetime) — Tanggal selesai cuti
- reason (string) — Alasan pengajuan cuti
- attachment (string, nullable) — Path file PDF lampiran
- status (enum: pending/approved/rejected, default: pending) — Status pengajuan
- created_at, updated_at (timestamp)
```

#### Relasi
- User -> LeaveRequest: **1-to-Many** (satu user memiliki banyak pengajuan cuti)

---

## 📊 Alur Sistem

### Alur Employee

1. **Register/Login**
   - POST `/api/register` — Daftar akun baru dengan email & password
   - POST `/api/login` — Login dan alami token JWT
   - Token berisi scope `employee`

2. **Mengajukan Cuti**
   - POST `/api/leave-requests` — Ajukan cuti baru
   - Input: `start_date`, `end_date`, `reason`, `attachment` (opsional)
   - **Validasi business logic:**
     - Tanggal tidak boleh sebelum hari ini
     - `end_date` tidak boleh sebelum `start_date`
     - Tidak boleh overlap dengan pengajuan cuti yang sudah ada
     - Maksimal 12 pengajuan per tahun
   - File hanya disimpan jika semua validasi lolos
   - Response: HTTP 201 dengan data pengajuan yang baru dibuat

3. **Melihat Daftar Cuti Sendiri**
   - GET `/api/leave-requests` — Lihat daftar pengajuan cuti dengan pagination
   - Hanya menampilkan milik sendiri
   - Response: List dengan pagination (10 per halaman), diurutkan paling baru

4. **Melihat Detail Cuti Sendiri**
   - GET `/api/leave-requests/{id}` — Lihat detail pengajuan cuti tertentu
   - Hanya bisa akses milik sendiri, akses milik orang lain return 403

5. **Logout**
   - POST `/api/logout` — Logout dan revoke semua token

### Alur Admin

1. **Login**
   - POST `/api/login` — Login dengan akun admin
   - Token berisi scope `admin`

2. **Melihat Semua Pengajuan Cuti**
   - GET `/api/leave-requests` — Lihat daftar pengajuan cuti dari semua karyawan
   - Response: List dengan pagination, menyertakan data user (nama, email, role)
   - Diurutkan paling baru

3. **Melihat Detail Pengajuan Cuti**
   - GET `/api/leave-requests/{id}` — Lihat detail pengajuan milik siapapun
   - Response: Lengkap dengan data relasi user

4. **Mengubah Status Pengajuan Cuti**
   - PATCH `/api/leave-requests/{id}/status` — Update status pengajuan
   - Input: `status` (hanya accept `approved` atau `rejected`)
   - **Validasi:**
     - Hanya bisa update jika status masih `pending` (bukan `approved`/`rejected`)
     - Response 422 jika sudah final
   - Response: Data pengajuan dengan status terbaru

5. **Logout**
   - POST `/api/logout` — Logout

---

## 🔌 API Endpoints

### Autentikasi

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/api/register` | - | Registrasi user baru |
| POST | `/api/login` | - | Login dan dapatkan token |
| POST | `/api/logout` | Bearer | Logout dan revoke token |
| GET | `/api/user` | Bearer + Admin | Lihat data user yang login |

### Pengajuan Cuti

| Method | Endpoint | Auth | Role | Deskripsi |
|---|---|---|---|---|
| GET | `/api/leave-requests` | Bearer | All | Lihat daftar pengajuan cuti (filtered by role) |
| POST | `/api/leave-requests` | Bearer | All | Buat pengajuan cuti baru |
| GET | `/api/leave-requests/{id}` | Bearer | All | Lihat detail pengajuan cuti |
| PATCH | `/api/leave-requests/{id}/status` | Bearer | Admin | Update status pengajuan cuti |

### Response Format

**Success (2xx)**
```json
{
  "id": 1,
  "user_id": 2,
  "start_date": "2026-05-01T00:00:00.000000Z",
  "end_date": "2026-05-05T00:00:00.000000Z",
  "reason": "Family vacation",
  "attachment": null,
  "status": "pending",
  "created_at": "2026-04-18T16:53:51Z",
  "updated_at": "2026-04-18T16:53:51Z"
}
```

**Error (4xx/5xx)**
```json
{
  "message": "Error description",
  "errors": {
    "field": ["Error message"]
  }
}
```

---

## 📚 Dokumentasi API Lengkap

Dokumentasi lengkap dengan request/response example untuk setiap endpoint tersedia di Postman:

### 📖 [https://documenter.getpostman.com/view/20476926/2sBXqDsiV9#85682004-e6f6-48d4-b32f-8b225c2f82f8](https://documenter.getpostman.com/view/20476926/2sBXqDsiV9#85682004-e6f6-48d4-b32f-8b225c2f82f8)

Di dokumentasi Postman tersebut, Anda akan menemukan:
- Contoh request untuk setiap endpoint
- Parameter dan header yang diperlukan
- Response format beserta status code
- Error handling dan pesan error

---

## 🧪 Testing

### Menjalankan Test

```bash
php artisan test
```

### Test Coverage

Project memiliki **52 test** dengan **141 assertions** yang mencakup:

- **Autentikasi** (Register, Login, Logout)
- **CRUD Pengajuan Cuti** (Create, Read, Update)
- **Validasi Input** (tanggal, format file, dll)
- **Business Logic** (limit 12/tahun, overlapping dates, dll)
- **Otorisasi Role-Based** (employee vs admin access)
- **File Upload Handling** (PDF, ukuran, cleanup)

### Test per Endpoint

| Endpoint | Scenarios |
|---|---|
| POST /api/register | 8 test |
| POST /api/login | 5 test |
| POST /api/logout | 2 test |
| POST /api/leave-requests | 16 test |
| GET /api/leave-requests | 7 test |
| GET /api/leave-requests/{id} | 6 test |
| PATCH /api/leave-requests/{id}/status | 8 test |

---

## 👤 Akun Default

Setelah menjalankan `php artisan db:seed`, tersedia 2 akun default untuk testing:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `admin123` |
| Employee | `employee@example.com` | `employee123` |

### Testing dengan Postman

1. Login dengan salah satu akun di atas
2. Copy-paste token dari response ke Authorization header (`Bearer <token>`)
3. Test endpoint sesuai role

---

## 🔐 Security

- Password di-hash menggunakan bcrypt (BCRYPT_ROUNDS=12)
- API dilindungi dengan OAuth2 token via Laravel Passport
- CSRF protection untuk form submissions
- SQL injection prevention via Eloquent ORM
- File upload di-validasi tipe (hanya PDF) dan ukuran (max 5MB)

---

## 📝 Changelog

### v1.0.0 (Latest)
- ✅ Register & Login dengan role-based auth
- ✅ CRUD pengajuan cuti dengan validasi lengkap
- ✅ Role-based access control (admin/employee)
- ✅ File upload support (PDF)
- ✅ 52 unit tests dengan 141 assertions
- ✅ Comprehensive API documentation

---

## 📧 Support & Feedback

Untuk pertanyaan atau feedback, silakan buka issue di GitHub atau hubungi tim development.

---

## 📄 Lisensi

Project ini dilisensikan di bawah MIT License. Lihat file [LICENSE](LICENSE) untuk detail.
