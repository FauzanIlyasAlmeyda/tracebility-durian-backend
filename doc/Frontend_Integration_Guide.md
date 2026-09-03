# DurianTrace Frontend Integration Guide

Dokumen ini disusun untuk tim frontend agar bisa langsung mengonsumsi backend DurianTrace tanpa harus membaca seluruh PRD mentah.

## Fungsi Dokumen

Dokumen ini berfungsi sebagai pedoman integrasi antara frontend dan backend DurianTrace. Isinya membantu tim frontend memahami:

- ruang lingkup aplikasi yang benar-benar dipakai,
- role pengguna beserta akses utamanya,
- format respons API yang harus dibaca oleh frontend,
- alur autentikasi dan session,
- struktur data utama seperti user, farm, batch, shipment, receipt, product, order, dan transaction,
- pemetaan status agar label di UI konsisten dengan backend,
- daftar endpoint yang dipakai pada masing-masing halaman,
- aturan khusus untuk halaman public trace yang dibuka dari QR code,
- penanganan error dan catatan blockchain dummy yang perlu ditampilkan secara aman di UI.

Secara umum, dokumen ini dibuat agar frontend tidak perlu menebak struktur data backend. Semua kebutuhan dasar untuk membangun halaman login, dashboard role-based, manajemen batch, shipment, receipt, produk UMKM, transaksi konsumen, dan public trace sudah dijelaskan di sini.

## Ringkasan Isi Dokumen

Bagian-bagian di bawah ini menjelaskan isi dokumen secara ringkas untuk kebutuhan laporan.

### 1. Scope Produk

Bagian ini menjelaskan batasan produk DurianTrace. Fokus utamanya adalah traceability atau pelacakan asal-usul durian dari petani sampai ke konsumen. Dokumen ini menegaskan bahwa DurianTrace bukan marketplace penuh, bukan sistem logistik armada, dan bukan POS retail. Isi bagian ini penting agar frontend hanya membangun fitur yang relevan dengan traceability.

### 2. Prinsip UI

Bagian ini memuat prinsip dasar tampilan antarmuka. Isi utamanya adalah bahwa setiap pengguna hanya memiliki satu role aktif, semua halaman bersifat role-based, kode batch menjadi identitas utama traceability, public trace harus bisa dibuka tanpa login, dan UI harus menonjolkan data trace serta provenance, bukan promosi harga.

### 3. Role dan Aksi

Bagian ini menjelaskan siapa saja jenis pengguna di sistem dan apa fokus halaman yang harus mereka lihat. Role yang dibahas adalah petani, pengepul, distributor, UMKM, dan konsumen. Setiap role memiliki aksi utama yang berbeda, sehingga frontend perlu menampilkan menu, halaman, dan tombol yang sesuai dengan hak akses masing-masing.

### 4. Format Response API

Bagian ini berisi standar bentuk respons dari backend. Semua endpoint memakai envelope yang seragam dengan field `success`, `message`, dan `data`. Jika terjadi validasi gagal, respons juga menyertakan objek `errors`. Informasi ini penting agar frontend bisa membuat handler sukses dan gagal secara konsisten.

### 5. Auth Flow

Bagian ini menjelaskan alur autentikasi pengguna, mulai dari register, login, restore session lewat `/api/me`, sampai logout. Di bagian ini juga dijelaskan data request, data respons sukses, dan aturan validasi penting seperti role wajib sesuai akun. Isi bagian ini menjadi dasar pembuatan halaman login dan pendaftaran akun.

### 6. Shared Data Contracts

Bagian ini menjabarkan bentuk data utama yang dipakai berulang di berbagai halaman. Contohnya adalah struktur `User`, `Farm`, `Batch`, `Shipment`, `Receipt`, `Product`, `Order`, dan `Consumer Transaction`. Tujuannya agar frontend mengetahui field apa saja yang harus dirender, disimpan, atau dikirim kembali saat melakukan request.

### 7. Status Mapping

Bagian ini memuat daftar status backend beserta label yang harus ditampilkan di UI. Contohnya status batch, shipment, receipt condition, product, order, dan consumer transaction. Isi bagian ini penting supaya frontend bisa menampilkan badge, label, dan state visual yang seragam.

### 8. Endpoint Map for Frontend

Bagian ini adalah daftar endpoint yang harus dipakai oleh frontend berdasarkan role. Isinya dibagi ke endpoint auth, petani, pengepul, distributor, UMKM, konsumen, dan public trace. Bagian ini membantu tim frontend menentukan API mana yang dipanggil untuk setiap halaman atau aksi.

### 9. Request Body per Endpoint

Bagian ini berisi contoh body request untuk endpoint yang paling penting. Isinya mencakup payload untuk membuat kebun, membuat batch, memverifikasi batch, menolak batch, membuat shipment, membuat receipt, membuat produk, membuat order, membuat transaksi, dan memperbarui profil. Bagian ini sangat penting karena menjadi acuan saat frontend membangun form input.

### 10. Public Trace Contract

Bagian ini menjelaskan endpoint public trace yang paling penting untuk halaman publik dan QR scan. Isi utamanya adalah data `batch`, `events`, `source_batches`, `downstream_products`, `shipment_history`, `public_url`, dan `blockchain`. Bagian ini menjadi dasar untuk tampilan hero section, timeline perjalanan batch, riwayat distribusi, serta informasi verifikasi internal.

### 11. Collector Stock Response

Bagian ini memuat struktur ringkasan stok milik pengepul. Isinya meliputi jumlah batch aktif, total berat, total buah, breakdown grade, dan breakdown varietas. Data ini biasanya dipakai untuk dashboard pengepul agar kondisi stok mudah dipantau.

### 12. Distributor Shipment Detail Response

Bagian ini menjelaskan respons detail shipment untuk distributor, termasuk detail shipment, daftar batch sumber, dan receipt jika sudah tersedia. Jika receipt belum ada, nilainya `null`. Bagian ini membantu frontend menampilkan halaman detail shipment secara lengkap.

### 13. Consumer Product Detail Response

Bagian ini menjelaskan isi respons detail produk konsumen. Data yang ditampilkan adalah kode produk, nama produk, kode batch sumber, varietas sumber, dan grade sumber. Isi bagian ini penting untuk halaman detail produk dan halaman trace konsumsi.

### 14. Error Handling

Bagian ini menjelaskan kode status HTTP yang dipakai backend, seperti `200`, `201`, `401`, `403`, `404`, `422`, dan `500`. Ada juga contoh error validasi dan business rule. Tujuannya agar frontend bisa menampilkan pesan error yang tepat dan mudah dipahami pengguna.

### 15. Dummy Blockchain Notes

Bagian ini menjelaskan bahwa blockchain pada sistem masih bersifat dummy atau simulasi. Isi utamanya adalah field seperti `network`, `status`, `anchor_ref`, `tx_hash`, `block_number`, `event_count`, dan `last_event_hash`. Frontend harus menampilkan data ini sebagai verifikasi internal, bukan sebagai integrasi blockchain sungguhan.

### 16. Akun Seed untuk Testing

Bagian ini menyediakan akun contoh untuk tiap role dengan password `password`. Informasi ini berguna untuk pengujian frontend selama tahap development agar setiap role dapat dicoba tanpa harus membuat akun baru secara manual.

## 1. Scope Produk

DurianTrace adalah sistem traceability durian, bukan marketplace utama.
Fokus utamanya:

- Mencatat asal-usul durian dari petani.
- Menyimpan perpindahan kepemilikan dan kondisi fisik di sepanjang rantai pasok.
- Menyediakan halaman public trace yang bisa dibuka dari QR code atau kode batch.
- Menyimpan jejak audit yang append-only.

Hal yang bukan scope inti:

- Checkout / cart / marketplace penuh.
- Manajemen logistik armada.
- POS retail.
- Rating / review produk.

## 2. Prinsip UI

- Satu user hanya punya satu role aktif.
- Semua screen berbasis role.
- Kode batch adalah identitas utama traceability.
- Public trace harus bisa dibuka tanpa login.
- Tampilkan trace dan provenance sebagai data utama, bukan harga atau promosi.

## 3. Role dan Aksi

| Role | Fokus UI | Aksi Utama |
| --- | --- | --- |
| `petani` | Kebun, batch panen | Kelola profil, kebun, tambah dan edit batch |
| `pengepul` | Incoming batch, verifikasi, shipment | Lihat stok, verifikasi fisik, buat shipment, kirim, selesai, reject |
| `distributor` | Receipt dan penerimaan | Lihat shipment, simpan receipt, cek selisih |
| `umkm` | Produk olahan dan sumber batch | Buat produk dengan banyak sumber batch, lihat order internal |
| `konsumen` | Produk dan trace konsumsi | Lihat produk, detail asal batch, simpan transaksi sederhana |

## 4. Format Response API

Semua endpoint mengikuti envelope ini:

```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

Jika validasi gagal:

```json
{
  "success": false,
  "message": "Data tidak valid",
  "errors": {
    "field_name": ["Pesan error"]
  }
}
```

## 5. Auth Flow

### Register

- `POST /api/register`

Request:

```json
{
  "first_name": "Budi",
  "last_name": "Petani",
  "phone": "081234567890",
  "email": "budi@example.com",
  "username": "budi.petani",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "petani"
}
```

Catatan validasi:

- `first_name` wajib, max 100 karakter.
- `last_name` wajib, max 100 karakter.
- `phone` wajib, unik, max 20 karakter.
- `email` wajib, unik, format email valid.
- `username` opsional, unik, max 50 karakter.
- `password` wajib, min 8 karakter, harus ada `password_confirmation`.
- `role` wajib, nilai: `petani`, `pengepul`, `distributor`, `umkm`, `konsumen`.

Response sukses (201):

```json
{
  "success": true,
  "message": "Registrasi berhasil",
  "data": {
    "user": {
      "id": 1,
      "first_name": "Budi",
      "last_name": "Petani",
      "phone": "081234567890",
      "email": "budi@example.com",
      "username": "budi.petani",
      "role": "petani",
      "is_active": true,
      "last_login_at": null
    },
    "token": "1|xxxxxxxxxxxxxxxx",
    "dashboard": "petani"
  }
}
```

### Login

- `POST /api/login`

Request:

```json
{
  "identifier": "budi@example.com",
  "password": "password123",
  "role": "petani"
}
```

Catatan:

- `identifier` bisa berupa email, username, atau nomor telepon.
- `role` wajib diisi dan harus sesuai dengan role akun yang terdaftar.
- Jika role tidak cocok, server mengembalikan 403.

Response sukses (200):

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": { "...": "..." },
    "token": "2|xxxxxxxxxxxxxxxx",
    "dashboard": "petani"
  }
}
```

### Session

- `GET /api/me` — Restore session dari token yang tersimpan.
- `POST /api/logout` — Hapus token aktif.

Semua endpoint yang memerlukan autentikasi harus menyertakan header:

```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

## 6. Shared Data Contracts

### User

```json
{
  "id": 1,
  "first_name": "Budi",
  "last_name": "Petani",
  "phone": "081234567890",
  "email": "budi@example.com",
  "username": "budi.petani",
  "role": "petani",
  "is_active": true,
  "last_login_at": "2026-07-22T10:00:00.000Z"
}
```

### Farm

```json
{
  "id": 1,
  "farmer_id": 1,
  "name": "Kebun Pakis 2",
  "province": "Jawa Timur",
  "city": "Kabupaten Jember",
  "district": "Pakis",
  "village": "Pakis Aji",
  "address": "Jl. Kebun No. 1",
  "latitude": -8.1234,
  "longitude": 113.5678
}
```

### Batch

```json
{
  "code": "DRN-2026-000128",
  "farmer_id": 1,
  "farm_id": 1,
  "farm_name_snapshot": "Kebun Pakis 2",
  "variety": "Montong",
  "grade": "A",
  "quantity_kg": 120.5,
  "unit": "kg",
  "fruit_count": 48,
  "harvest_date": "2026-07-20",
  "status": "created",
  "blockchain": { "...": "..." }
}
```

### Shipment

```json
{
  "code": "SHP-2026-000045",
  "collector_id": 2,
  "source_batch_codes": ["DRN-2026-000128"],
  "total_weight_kg": 120.5,
  "total_fruit_count": 48,
  "status": "readyToShip",
  "destination_type": "distributor",
  "packaged_at": "2026-07-22T08:00:00.000Z",
  "sent_at": null,
  "completed_at": null
}
```

### Receipt

```json
{
  "code": "RCP-2026-000010",
  "shipment_code": "SHP-2026-000045",
  "condition": "good",
  "received_at": "2026-07-23T09:00:00.000Z"
}
```

### Product

```json
{
  "code": "PRD-2026-000012",
  "name": "Paket Durian Montong Premium",
  "category": "Paket",
  "status": "aktif",
  "price_label": "Rp 150.000",
  "stock_label": "Stok 20 paket",
  "description": "Durian Montong grade A dari Jember.",
  "qr_code_data": "PRD-2026-000012"
}
```

### Order

```json
{
  "id": "ORD-2026-000008",
  "product_name": "Paket Durian Montong Premium",
  "buyer_name": "Toko Sari Rasa",
  "quantity": 5,
  "total_label": "Rp 750.000",
  "status": "diproses",
  "created_at": "2026-07-22T11:00:00.000Z",
  "qr_code_data": "ORD-2026-000008"
}
```

### Consumer Transaction

```json
{
  "id": "TRX-2026-000003",
  "status": "processing",
  "payment_status": "unpaid",
  "created_at": "2026-07-22T12:00:00.000Z",
  "qr_code_data": "TRX-2026-000003"
}
```

## 7. Status Mapping

### Harvest Batch

| Value | UI Label |
| --- | --- |
| `draft` | Draft |
| `created` | Dibuat |
| `verifiedByCollector` | Diverifikasi pengepul |
| `inDistribution` | Dalam distribusi |
| `receivedByUmkm` | Diterima UMKM |
| `processed` | Diproses |
| `sold` | Terjual |
| `rejected` | Ditolak |

### Shipment

| Value | UI Label |
| --- | --- |
| `readyToShip` | Siap dikirim |
| `sent` | Dikirim |
| `completed` | Selesai |

### Receipt Condition

| Value | UI Label |
| --- | --- |
| `good` | Baik |
| `minorDamage` | Rusak ringan |
| `damaged` | Rusak |

### Product

| Value | UI Label |
| --- | --- |
| `aktif` | Aktif |
| `habis` | Habis |

### Order

| Value | UI Label |
| --- | --- |
| `diproses` | Diproses |
| `selesai` | Selesai |

### Consumer Transaction

| Value | UI Label |
| --- | --- |
| `processing` | Diproses |

## 8. Endpoint Map for Frontend

### Auth

| Method | Path | Auth | Use Case |
| --- | --- | --- | --- |
| `POST` | `/api/register` | Tidak | Buat akun baru |
| `POST` | `/api/login` | Tidak | Login role-based |
| `GET` | `/api/me` | Ya | Restore session |
| `POST` | `/api/logout` | Ya | Hapus token |

### Petani

| Method | Path | Auth | Use Case |
| --- | --- | --- | --- |
| `GET` | `/api/farmer/profile` | Ya | Load profil petani |
| `PUT` | `/api/farmer/profile` | Ya | Simpan profil petani |
| `GET` | `/api/farmer/farms` | Ya | List kebun |
| `POST` | `/api/farmer/farms` | Ya | Tambah kebun |
| `GET` | `/api/farmer/batches` | Ya | List batch |
| `POST` | `/api/farmer/batches` | Ya | Tambah batch |
| `GET` | `/api/farmer/batches/{code}` | Ya | Detail batch |
| `PATCH` | `/api/farmer/batches/{code}` | Ya | Update batch (hanya saat status `created`) |

### Pengepul

| Method | Path | Auth | Use Case |
| --- | --- | --- | --- |
| `GET` | `/api/collector/profile` | Ya | Load profil pengepul |
| `PUT` | `/api/collector/profile` | Ya | Simpan profil pengepul |
| `GET` | `/api/collector/stock` | Ya | Ringkasan stok aktif |
| `GET` | `/api/collector/shipment-batches` | Ya | List shipment |
| `POST` | `/api/collector/shipment-batches` | Ya | Buat shipment |
| `PATCH` | `/api/collector/shipment-batches/{code}/send` | Ya | Kirim shipment |
| `PATCH` | `/api/collector/shipment-batches/{code}/complete` | Ya | Selesaikan shipment |
| `POST` | `/api/collector/batches/{code}/verify` | Ya | Verifikasi batch |
| `POST` | `/api/collector/batches/{code}/reject` | Ya | Tolak batch |

### Distributor

| Method | Path | Auth | Use Case |
| --- | --- | --- | --- |
| `GET` | `/api/distributor/profile` | Ya | Load profil distributor |
| `PUT` | `/api/distributor/profile` | Ya | Simpan profil distributor |
| `GET` | `/api/distributor/shipments` | Ya | List shipment distributor |
| `GET` | `/api/distributor/shipments/{code}` | Ya | Detail shipment |
| `POST` | `/api/distributor/shipments/{code}/receipt` | Ya | Simpan receipt |

### UMKM

| Method | Path | Auth | Use Case |
| --- | --- | --- | --- |
| `GET` | `/api/umkm/profile` | Ya | Load profil UMKM |
| `PUT` | `/api/umkm/profile` | Ya | Simpan profil UMKM |
| `GET` | `/api/umkm/products` | Ya | List produk UMKM |
| `POST` | `/api/umkm/products` | Ya | Buat produk |
| `GET` | `/api/umkm/orders` | Ya | List order internal |
| `POST` | `/api/umkm/orders` | Ya | Buat order internal |

### Konsumen

| Method | Path | Auth | Use Case |
| --- | --- | --- | --- |
| `GET` | `/api/consumer/profile` | Ya | Load profil konsumen |
| `PUT` | `/api/consumer/profile` | Ya | Simpan profil konsumen |
| `GET` | `/api/consumer/products` | Ya | List produk aktif untuk konsumen |
| `GET` | `/api/consumer/products/{code}` | Ya | Detail produk dan source batch |
| `GET` | `/api/consumer/transactions` | Ya | List transaksi |
| `POST` | `/api/consumer/transactions` | Ya | Buat transaksi |

### Public Trace

| Method | Path | Auth | Use Case |
| --- | --- | --- | --- |
| `GET` | `/api/trace/{batchCode}` | Tidak | Halaman trace tanpa login |

## 9. Request Body per Endpoint

### POST /api/farmer/farms

```json
{
  "name": "Kebun Pakis 2",
  "province": "Jawa Timur",
  "city": "Kabupaten Jember",
  "district": "Pakis",
  "village": "Pakis Aji",
  "address": "Jl. Kebun No. 1",
  "latitude": -8.1234,
  "longitude": 113.5678,
  "notes": "Kebun utama"
}
```

### POST /api/farmer/batches

```json
{
  "farm_id": 1,
  "farm_name_snapshot": "Kebun Pakis 2",
  "variety": "Montong",
  "grade": "A",
  "quantity_kg": 120.5,
  "unit": "kg",
  "fruit_count": 48,
  "harvest_date": "2026-07-20",
  "fertilizer": "Organik",
  "harvest_method": "Manual",
  "maturity_level": "Matang optimal",
  "shelf_life_estimate": "3 hari",
  "storage_suggestion": "Simpan di tempat sejuk",
  "notes": "Panen pagi hari",
  "photo_path": null
}
```

Catatan: `quantity_kg` wajib. Semua field lain opsional.

### PATCH /api/farmer/batches/{code}

Sama dengan POST batches, semua field opsional. Hanya bisa diubah saat status `created`.

### PUT /api/farmer/profile

```json
{
  "full_name": "Budi Santoso",
  "role_label": "Petani",
  "location": "Jember",
  "village": "Pakis Aji",
  "district": "Pakis",
  "city": "Kabupaten Jember",
  "contact": "081234567890",
  "avatar_path": null
}
```

### POST /api/collector/shipment-batches

```json
{
  "destination_type": "distributor",
  "source_batch_codes": ["DRN-2026-000128", "DRN-2026-000129"],
  "packaged_at": "2026-07-22T08:00:00",
  "warehouse_note": "Dikemas pagi"
}
```

Catatan: `destination_type` wajib, nilai: `distributor` atau `umkm`. `source_batch_codes` wajib minimal 1 item.

### POST /api/collector/batches/{code}/verify

```json
{
  "received_quantity_kg": 118.0,
  "received_fruit_count": 47,
  "grade_breakdown": [
    { "grade": "A", "weight_kg": 80.0, "fruit_count": 32 },
    { "grade": "B", "weight_kg": 38.0, "fruit_count": 15 }
  ],
  "quality_notes": "Kondisi baik, sedikit lecet",
  "verified_by": "Pak Karto"
}
```

### POST /api/collector/batches/{code}/reject

```json
{
  "reason": "Buah terlalu matang, tidak layak distribusi",
  "rejected_by": "Pak Karto"
}
```

### PUT /api/collector/profile

```json
{
  "business_name": "UD Karto Jaya",
  "address": "Jl. Pasar Baru No. 5, Jember",
  "contact": "082345678901",
  "avatar_path": null
}
```

### POST /api/distributor/shipments/{code}/receipt

```json
{
  "received_weight_kg": 117.5,
  "received_fruit_count": 46,
  "condition": "good",
  "received_at": "2026-07-23T09:00:00",
  "discrepancy_note": "Selisih 1 buah, kemungkinan jatuh saat bongkar",
  "quality_note": "Kondisi keseluruhan baik"
}
```

Catatan: Jika `received_weight_kg` atau `received_fruit_count` berbeda dari data shipment, `discrepancy_note` wajib diisi.

### PUT /api/distributor/profile

```json
{
  "business_name": "CV Distribusi Nusantara",
  "address": "Jl. Raya Surabaya No. 10",
  "contact": "083456789012",
  "avatar_path": null
}
```

### POST /api/umkm/products

```json
{
  "name": "Paket Durian Montong Premium",
  "category": "Paket",
  "price": 150000,
  "price_label": "Rp 150.000",
  "stock_qty": 20,
  "stock_label": "20 paket",
  "description": "Durian Montong grade A dari Jember.",
  "status": "aktif",
  "photo_path": null,
  "source_codes": ["DRN-2026-000128"]
}
```

Catatan: `name` wajib. `price` atau `price_label` bisa dipakai bergantian. `source_codes` adalah array kode batch yang menjadi bahan baku produk ini.

### POST /api/umkm/orders

```json
{
  "umkm_product_id": 1,
  "buyer_name": "Toko Sari Rasa",
  "buyer_phone": "084567890123",
  "buyer_address": "Jl. Mawar No. 3, Surabaya",
  "quantity": 5,
  "total_amount": 750000,
  "note": "Kirim pagi"
}
```

Catatan: `umkm_product_id` atau `product_id` wajib salah satu. `buyer_name`, `buyer_address`, `quantity` wajib.

### PUT /api/umkm/profile

```json
{
  "name": "UMKM Durian Jember",
  "owner_name": "Siti Rahayu",
  "about": "Produsen olahan durian premium dari Jember.",
  "address": "Jl. Industri No. 7, Jember",
  "contact": "085678901234",
  "image_path": null
}
```

### POST /api/consumer/transactions

```json
{
  "umkm_product_id": 1,
  "quantity": 2,
  "total_amount": 300000,
  "buyer_address": "Jl. Melati No. 5, Malang",
  "buyer_coordinates": "-7.9797,112.6304",
  "payment_method": "transfer",
  "payment_status": "unpaid",
  "bank_name": "BCA",
  "account_number": "1234567890",
  "note": "Tolong dikemas rapi"
}
```

### PUT /api/consumer/profile

```json
{
  "display_name": "Andi Konsumen",
  "address": "Jl. Melati No. 5, Malang",
  "phone": "086789012345",
  "avatar_path": null
}
```

## 10. Public Trace Contract

Endpoint ini adalah yang paling penting untuk QR scan dan halaman publik.

- `GET /api/trace/{batchCode}` — Tidak memerlukan autentikasi.

Contoh response:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "batch": {
      "code": "DRN-2026-000128",
      "variety": "Montong",
      "grade": "A",
      "status": "verifiedByCollector",
      "farm_name_snapshot": "Kebun Pakis 2",
      "farm_location": "Kabupaten Jember, Jawa Timur",
      "blockchain": {
        "network": "dummy-ledger",
        "status": "simulated",
        "anchor_ref": "DRT-XXXXXXXXXXXX",
        "tx_hash": "0x...",
        "block_number": 100128,
        "event_count": 3,
        "last_event_hash": "..."
      }
    },
    "events": [
      {
        "status": "created",
        "title": "Batch Dibuat",
        "actor_label": "Budi Petani",
        "event_at": "2026-07-22T10:00:00.000Z",
        "ledger_hash": "abc123...",
        "previous_ledger_hash": null,
        "ledger_height": 1
      }
    ],
    "source_batches": [],
    "downstream_products": [
      {
        "code": "PRD-2026-000012",
        "name": "Paket Durian Montong Premium",
        "status": "aktif"
      }
    ],
    "shipment_history": [
      {
        "code": "SHP-2026-000045",
        "status": "completed",
        "destination_type": "distributor",
        "packaged_at": "2026-07-22T08:00:00.000Z",
        "sent_at": "2026-07-22T14:00:00.000Z",
        "completed_at": "2026-07-23T09:00:00.000Z"
      }
    ],
    "public_url": "https://domain/api/trace/DRN-2026-000128",
    "blockchain": {
      "network": "dummy-ledger",
      "status": "simulated",
      "anchor_ref": "DRT-XXXXXXXXXXXX",
      "tx_hash": "0x...",
      "block_number": 100128,
      "event_count": 3,
      "last_event_hash": "..."
    }
  }
}
```

### UI Notes for Trace Page

- `batch` untuk hero section.
- `events` untuk timeline perjalanan, urutkan berdasarkan `event_at` ascending.
- `source_batches` untuk source list jika batch dipakai sebagai bahan olahan.
- `downstream_products` untuk menunjukkan hasil olahan di tahap UMKM.
- `shipment_history` untuk riwayat perpindahan.
- `blockchain` adalah dummy proof, tampilkan sebagai status verifikasi internal, bukan integrasi chain nyata.

## 11. Collector Stock Response

`GET /api/collector/stock` mengembalikan ringkasan stok batch aktif.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "active_batch_count": 5,
    "total_weight_kg": 600.0,
    "total_fruit_count": 240,
    "grade_breakdown": [
      {
        "key": "A",
        "label": "Grade A",
        "total_weight_kg": 400.0,
        "total_fruit_count": 160,
        "batch_count": 3
      }
    ],
    "variety_breakdown": [
      {
        "key": "montong",
        "label": "Durian Montong",
        "total_weight_kg": 600.0,
        "total_fruit_count": 240,
        "batch_count": 5
      }
    ]
  }
}
```

## 12. Distributor Shipment Detail Response

`GET /api/distributor/shipments/{code}` mengembalikan detail shipment beserta receipt jika sudah ada.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "shipment": { "...": "..." },
    "source_batches": [
      {
        "code": "DRN-2026-000128",
        "variety": "Montong",
        "status": "inDistribution"
      }
    ],
    "receipt": {
      "code": "RCP-2026-000010",
      "shipment_code": "SHP-2026-000045",
      "condition": "good",
      "received_at": "2026-07-23T09:00:00.000Z"
    }
  }
}
```

Jika receipt belum ada, field `receipt` bernilai `null`.

## 13. Consumer Product Detail Response

`GET /api/consumer/products/{code}` mengembalikan detail produk beserta informasi source batch.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "product": {
      "code": "PRD-2026-000012",
      "name": "Paket Durian Montong Premium",
      "source_batch_code": "DRN-2026-000128",
      "source_variety": "Montong",
      "source_grade": "A"
    }
  }
}
```

## 14. Error Handling

### HTTP Status Codes

| Code | Kondisi |
| --- | --- |
| `200` | Sukses |
| `201` | Data berhasil dibuat |
| `401` | Password salah atau token tidak valid |
| `403` | Role tidak sesuai atau akun dinonaktifkan |
| `404` | Data tidak ditemukan |
| `422` | Validasi gagal atau business rule dilanggar |
| `500` | Server error |

### Contoh Error 422 Validasi

```json
{
  "success": false,
  "message": "Data tidak valid",
  "errors": {
    "quantity_kg": ["The quantity kg field is required."],
    "role": ["The selected role is invalid."]
  }
}
```

### Contoh Error 403 Role

```json
{
  "success": false,
  "message": "Role tidak sesuai"
}
```

### Contoh Error 422 Business Rule

```json
{
  "success": false,
  "message": "Batch hanya bisa diubah saat status created"
}
```

## 15. Dummy Blockchain Notes

Saat ini backend belum terhubung ke blockchain sungguhan.

Yang sudah tersedia:

- Hash chain append-only per event.
- Proof batch yang berubah setiap ada event baru.
- Anchor reference dan tx hash dummy.

Field blockchain yang dikembalikan di setiap batch dan trace:

| Field | Keterangan |
| --- | --- |
| `network` | Selalu `dummy-ledger` |
| `status` | Selalu `simulated` |
| `anchor_ref` | Referensi anchor internal |
| `tx_hash` | Hash transaksi dummy |
| `block_number` | Nomor blok dummy |
| `event_count` | Jumlah event yang tercatat |
| `last_event_hash` | Hash event terakhir |

Yang perlu diingat frontend:

- Jangan tampilkan sebagai on-chain verified sungguhan.
- Label yang aman: `Simulated ledger` atau `Verifikasi Internal`.

## 16. Akun Seed untuk Testing

Seeder menyediakan akun contoh untuk tiap role dengan password `password`.

| Role | Email Contoh |
| --- | --- |
| `petani` | petani@example.com |
| `pengepul` | pengepul@example.com |
| `distributor` | distributor@example.com |
| `umkm` | umkm@example.com |
| `konsumen` | konsumen@example.com |

## 17. Snippet Kode dan Diagram Kelas

Bagian snippet kode yang penting dan diagram kelas inti dipindahkan ke file terpisah agar guide utama tetap lebih ringkas.

- [Frontend_Integration_Code_Snippets.md](Frontend_Integration_Code_Snippets.md)
