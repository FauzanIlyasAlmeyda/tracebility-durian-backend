from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime
from pathlib import Path
from xml.sax.saxutils import escape
import zipfile


OUT_PATH = Path(__file__).with_name("DurianTrace_Backend_Project_Documentation_v2.docx")


@dataclass
class Paragraph:
    text: str
    style: str | None = None


@dataclass
class Table:
    rows: list[list[str]]


@dataclass
class Endpoint:
    name: str
    method: str
    path: str
    auth: str
    headers: str
    request_body: str
    success_response: str
    notes: str


CONTENT: list[Paragraph | Table | Endpoint] = [
    Paragraph("Dokumentasi Proyek DurianTrace Backend", "Title"),
    Paragraph(f"Versi dokumen: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}", "Subtitle"),
    Paragraph(""),
    Paragraph("1. Ringkasan Proyek", "Heading1"),
    Paragraph(
        "DurianTrace Backend adalah API Laravel 12 untuk aplikasi pelacakan durian. Backend ini menangani "
        "registrasi dan login berbasis role, profil per role, data kebun dan batch panen, verifikasi dan "
        "shipment oleh pengepul, receipt distributor, katalog dan order UMKM, transaksi konsumen, serta "
        "public trace tanpa login."
    ),
    Paragraph(""),
    Paragraph("2. Standar Umum API", "Heading1"),
    Table([
        ["Komponen", "Keterangan"],
        ["Format", "REST API JSON"],
        ["Authentication", "Laravel Sanctum Bearer Token"],
        ["Content-Type", "application/json untuk request JSON"],
        ["Accept", "application/json"],
        ["Envelope response", "success, message, data"],
        ["Validation error", "success = false dan errors berisi detail field"],
    ]),
    Paragraph(""),
    Paragraph("Contoh response sukses"),
    Paragraph(
        '{\n'
        '  "success": true,\n'
        '  "message": "OK",\n'
        '  "data": {}\n'
        '}'
    ),
    Paragraph("Contoh response error validasi"),
    Paragraph(
        '{\n'
        '  "success": false,\n'
        '  "message": "Data tidak valid",\n'
        '  "errors": {\n'
        '    "email": ["Email wajib diisi."]\n'
        '  }\n'
        '}'
    ),
    Paragraph(""),
    Paragraph("3. Role Pengguna", "Heading1"),
    Table([
        ["Role", "Deskripsi"],
        ["petani", "Mengelola kebun, batch panen, dan data profil petani"],
        ["pengepul", "Melihat stok, verifikasi batch, membuat shipment, dan memproses shipment"],
        ["distributor", "Menerima shipment dan membuat receipt"],
        ["umkm", "Mengelola produk dan order UMKM"],
        ["konsumen", "Melihat produk konsumen dan membuat transaksi"],
    ]),
    Paragraph(""),
    Paragraph("4. Aturan Penting", "Heading1"),
    Table([
        ["Aturan", "Penjelasan"],
        ["Role tunggal", "Satu user hanya memiliki satu role aktif"],
        ["Kode stabil", "Kode domain tidak boleh berubah setelah data dibuat"],
        ["Traceability", "Setiap perubahan penting dicatat sebagai event batch"],
        ["Public trace", "Endpoint trace dapat dibuka tanpa login"],
        ["Payload konsisten", "Frontend selalu menerima envelope JSON yang seragam"],
    ]),
    Paragraph(""),
    Paragraph("5. Endpoint Auth", "Heading1"),
    Endpoint(
        name="Registrasi pengguna",
        method="POST",
        path="/api/register",
        auth="Public",
        headers="Accept: application/json\nContent-Type: application/json",
        request_body="""{
  "first_name": "Risqi",
  "last_name": "Firdaus",
  "phone": "81234567890",
  "email": "risqi@example.com",
  "password": "secret123",
  "password_confirmation": "secret123",
  "role": "petani"
}""",
        success_response="""{
  "success": true,
  "message": "Registrasi berhasil",
  "data": {
    "user": { "...": "..." },
    "token": "1|xxxxxxxxxxxxxxxx",
    "dashboard": "petani"
  }
}""",
        notes="Role harus salah satu dari petani, pengepul, distributor, umkm, konsumen. Status sukses: 201.",
    ),
    Endpoint(
        name="Login pengguna",
        method="POST",
        path="/api/login",
        auth="Public",
        headers="Accept: application/json\nContent-Type: application/json",
        request_body="""{
  "identifier": "risqi@example.com",
  "password": "secret123",
  "role": "petani"
}""",
        success_response="""{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": { "...": "..." },
    "token": "1|xxxxxxxxxxxxxxxx",
    "dashboard": "petani"
  }
}""",
        notes="Identifier dapat berupa email, username, atau phone sesuai policy backend. Error 401/403/404/422 memakai message yang mudah dibaca.",
    ),
    Endpoint(
        name="Session aktif",
        method="GET",
        path="/api/me",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "Session aktif",
  "data": {
    "user": { "...": "..." }
  }
}""",
        notes="Dipakai untuk restore session. Token tidak valid akan menghasilkan 401.",
    ),
    Endpoint(
        name="Logout",
        method="POST",
        path="/api/logout",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "Logout berhasil",
  "data": null
}""",
        notes="Token aktif dihapus dari server jika memungkinkan.",
    ),
    Paragraph(""),
    Paragraph("6. Endpoint Profil per Role", "Heading1"),
    Table([
        ["Role", "GET", "PUT"],
        ["petani", "/api/farmer/profile", "/api/farmer/profile"],
        ["pengepul", "/api/collector/profile", "/api/collector/profile"],
        ["distributor", "/api/distributor/profile", "/api/distributor/profile"],
        ["umkm", "/api/umkm/profile", "/api/umkm/profile"],
        ["konsumen", "/api/consumer/profile", "/api/consumer/profile"],
    ]),
    Paragraph("Request body profil umum"),
    Paragraph(
        '{\n'
        '  "full_name": "Risqi Firdaus Setiawan",\n'
        '  "contact": "081234567890",\n'
        '  "email": "risqi@example.com",\n'
        '  "location": "Desa Pakis, Kabupaten Jember"\n'
        '}'
    ),
    Paragraph("Catatan: backend bisa menerima field profil sesuai role, misalnya business_name, owner_name, display_name, address, contact, avatar_path."),
    Paragraph(""),
    Paragraph("7. Endpoint Petani", "Heading1"),
    Endpoint(
        name="Daftar kebun",
        method="GET",
        path="/api/farmer/farms",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 1,
      "farmer_id": 10,
      "name": "Kebun Pakis 1",
      "province": "Jawa Timur",
      "city": "Kabupaten Jember",
      "district": "Pakis",
      "village": "Pakis",
      "address": "Jl. Raya Pakis No. 1",
      "latitude": null,
      "longitude": null
    }
  ]
}""",
        notes="Hanya data kebun milik petani yang login.",
    ),
    Endpoint(
        name="Tambah kebun",
        method="POST",
        path="/api/farmer/farms",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="""{
  "name": "Kebun Pakis 2",
  "province": "Jawa Timur",
  "city": "Kabupaten Jember",
  "district": "Pakis",
  "village": "Pakis",
  "address": "Jl. Raya Pakis No. 2",
  "latitude": -8.1234,
  "longitude": 113.5678
}""",
        success_response="""{
  "success": true,
  "message": "Kebun berhasil dibuat",
  "data": { "...": "..." }
}""",
        notes="Latitude dan longitude opsional tetapi harus numerik jika diisi.",
    ),
    Endpoint(
        name="Daftar batch",
        method="GET",
        path="/api/farmer/batches",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": [
    {
      "code": "DRN-2026-000128",
      "farmer_id": 10,
      "farm_id": 2,
      "farm_name_snapshot": "Kebun Pakis 2",
      "variety": "Montong",
      "grade": "A",
      "quantity_kg": 52,
      "unit": "kg",
      "fruit_count": 18,
      "harvest_date": "2026-07-20",
      "status": "created"
    }
  ]
}""",
        notes="List diurutkan dari data terbaru.",
    ),
    Endpoint(
        name="Tambah batch",
        method="POST",
        path="/api/farmer/batches",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="""{
  "farm_id": 2,
  "variety": "Montong",
  "grade": "A",
  "quantity_kg": 52,
  "unit": "kg",
  "fruit_count": 18,
  "harvest_date": "2026-07-20",
  "fertilizer": "Organik Kompos",
  "harvest_method": "Jatuh Alami",
  "maturity_level": "Matang Pohon",
  "shelf_life_estimate": "2-3 hari",
  "storage_suggestion": "Simpan di tempat sejuk.",
  "notes": "Kulit utuh, aroma kuat.",
  "photo_path": "uploads/batches/batch-001.jpg"
}""",
        success_response="""{
  "success": true,
  "message": "Batch berhasil dibuat",
  "data": {
    "code": "DRN-2026-000128",
    "status": "created",
    "harvest_date": "2026-07-20"
  }
}""",
        notes="Backend membuat kode unik stabil dan menulis event Batch Dibuat.",
    ),
    Endpoint(
        name="Detail batch",
        method="GET",
        path="/api/farmer/batches/{code}",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": {
    "batch": { "...": "..." },
    "events": [ { "...": "..." } ]
  }
}""",
        notes="Dipakai oleh detail batch dan timeline trace.",
    ),
    Endpoint(
        name="Update batch",
        method="PATCH",
        path="/api/farmer/batches/{code}",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="""{
  "farm_id": 2,
  "variety": "Montong",
  "grade": "A",
  "quantity_kg": 55,
  "fruit_count": 19,
  "harvest_date": "2026-07-20",
  "notes": "Updated after correction window."
}""",
        success_response="""{
  "success": true,
  "message": "Batch berhasil diperbarui",
  "data": {
    "code": "DRN-2026-000128",
    "status": "created"
  }
}""",
        notes="Hanya boleh saat status masih created.",
    ),
    Paragraph(""),
    Paragraph("8. Endpoint Pengepul", "Heading1"),
    Endpoint(
        name="Ringkasan stok",
        method="GET",
        path="/api/collector/stock",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": {
    "active_batch_count": 2,
    "total_weight_kg": 158,
    "total_fruit_count": 38,
    "grade_breakdown": [ { "...": "..." } ],
    "variety_breakdown": [ { "...": "..." } ]
  }
}""",
        notes="Menampilkan ringkasan stok aktif milik pengepul.",
    ),
    Endpoint(
        name="Daftar shipment",
        method="GET",
        path="/api/collector/shipment-batches",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": [
    {
      "code": "PGL-2026-000001",
      "collector_id": 20,
      "source_batch_codes": ["DRN-2026-000128"],
      "total_weight_kg": 158,
      "total_fruit_count": 38,
      "status": "readyToShip",
      "destination_type": "distributor",
      "packaged_at": "2026-07-20T10:00:00Z"
    }
  ]
}""",
        notes="List diurutkan dari data terbaru.",
    ),
    Endpoint(
        name="Tambah shipment",
        method="POST",
        path="/api/collector/shipment-batches",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="""{
  "source_batch_codes": ["DRN-2026-000128", "DRN-2026-000110"],
  "destination_type": "distributor",
  "warehouse_note": "Pengiriman via truk pendingin."
}""",
        success_response="""{
  "success": true,
  "message": "Shipment berhasil dibuat",
  "data": {
    "code": "PGL-2026-000001",
    "status": "readyToShip"
  }
}""",
        notes="Backend memvalidasi batch, menyimpan snapshot, dan membuat kode shipment stabil.",
    ),
    Endpoint(
        name="Kirim shipment",
        method="PATCH",
        path="/api/collector/shipment-batches/{code}/send",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "Shipment terkirim",
  "data": {
    "code": "PGL-2026-000001",
    "status": "sent",
    "sent_at": "2026-07-20T11:00:00Z"
  }
}""",
        notes="Hanya boleh jika status shipment readyToShip.",
    ),
    Endpoint(
        name="Selesaikan shipment",
        method="PATCH",
        path="/api/collector/shipment-batches/{code}/complete",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "Shipment selesai",
  "data": {
    "code": "PGL-2026-000001",
    "status": "completed",
    "completed_at": "2026-07-20T13:00:00Z"
  }
}""",
        notes="Hanya boleh jika status shipment sent.",
    ),
    Endpoint(
        name="Verifikasi batch",
        method="POST",
        path="/api/collector/batches/{code}/verify",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="""{
  "received_quantity_kg": 94,
  "received_fruit_count": 17,
  "grade_breakdown": [
    { "grade": "A", "weight_kg": 60, "fruit_count": 11 },
    { "grade": "B", "weight_kg": 34, "fruit_count": 6 }
  ],
  "quality_notes": "Mayoritas grade A.",
  "verified_by": "Pengepul Jember"
}""",
        success_response="""{
  "success": true,
  "message": "Batch terverifikasi",
  "data": {
    "code": "DRN-2026-000128",
    "status": "verifiedByCollector"
  }
}""",
        notes="Menyimpan grade breakdown dan event verifikasi.",
    ),
    Endpoint(
        name="Tolak batch",
        method="POST",
        path="/api/collector/batches/{code}/reject",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="""{
  "reason": "Beberapa buah retak dan tingkat kematangan tidak seragam.",
  "rejected_by": "Pengepul Jember"
}""",
        success_response="""{
  "success": true,
  "message": "Batch ditolak",
  "data": {
    "code": "DRN-2026-000128",
    "status": "rejected"
  }
}""",
        notes="Batch yang ditolak berhenti dari alur mutasi berikutnya.",
    ),
    Paragraph(""),
    Paragraph("9. Endpoint Distributor", "Heading1"),
    Endpoint(
        name="Daftar shipment distributor",
        method="GET",
        path="/api/distributor/shipments",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": [
    {
      "code": "PGL-2026-000001",
      "collector_id": 20,
      "destination_type": "distributor",
      "status": "sent",
      "total_weight_kg": 158,
      "total_fruit_count": 38
    }
  ]
}""",
        notes="Hanya shipment dengan destination_type = distributor.",
    ),
    Endpoint(
        name="Detail shipment distributor",
        method="GET",
        path="/api/distributor/shipments/{code}",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": {
    "shipment": { "...": "..." },
    "source_batches": [ { "...": "..." } ],
    "receipt": null
  }
}""",
        notes="Frontend dapat menampilkan shipment, source batch, dan receipt dalam satu layar detail.",
    ),
    Endpoint(
        name="Simpan receipt",
        method="POST",
        path="/api/distributor/shipments/{code}/receipt",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="""{
  "received_weight_kg": 156,
  "received_fruit_count": 37,
  "condition": "minorDamage",
  "discrepancy_note": "Selisih kecil karena sortasi ulang.",
  "quality_note": "Barang masih layak jual."
}""",
        success_response="""{
  "success": true,
  "message": "Receipt berhasil disimpan",
  "data": {
    "shipment_code": "PGL-2026-000001",
    "condition": "minorDamage",
    "received_at": "2026-07-20T13:30:00Z"
  }
}""",
        notes="Jika jumlah diterima berbeda dari jumlah diharapkan, discrepancy_note wajib diisi.",
    ),
    Paragraph(""),
    Paragraph("10. Endpoint UMKM", "Heading1"),
    Endpoint(
        name="Daftar produk",
        method="GET",
        path="/api/umkm/products",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": [
    {
      "code": "UMKM-P-001",
      "name": "Pancake Durian Premium",
      "category": "Olahan",
      "status": "aktif",
      "price_label": "Rp 68.000",
      "stock_label": "Stok 24 paket",
      "description": "Pancake durian lembut.",
      "qr_code_data": "UMKM-P-001"
    }
  ]
}""",
        notes="Produk adalah milik UMKM yang sedang login.",
    ),
    Endpoint(
        name="Tambah produk",
        method="POST",
        path="/api/umkm/products",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="""{
  "name": "Pancake Durian Premium",
  "category": "Olahan",
  "price_label": "Rp 68.000",
  "stock_label": "Stok 24 paket",
  "description": "Pancake durian lembut.",
  "status": "aktif",
  "qr_code_data": "UMKM-P-001",
  "image_path": "uploads/products/p-001.jpg"
}""",
        success_response="""{
  "success": true,
  "message": "Produk berhasil dibuat",
  "data": {
    "code": "UMKM-P-001",
    "status": "aktif"
  }
}""",
        notes="Backend menerima price_label dan stock_label lalu mengonversinya ke data numerik.",
    ),
    Endpoint(
        name="Daftar order",
        method="GET",
        path="/api/umkm/orders",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": "ORD-2026-0001",
      "product_name": "Pancake Durian Premium",
      "buyer_name": "Rina Saputri",
      "quantity": 2,
      "total_label": "Rp 136.000",
      "status": "diproses",
      "created_at": "2026-07-20T10:00:00Z",
      "qr_code_data": "ORD-2026-0001"
    }
  ]
}""",
        notes="Urutan data dari yang terbaru.",
    ),
    Endpoint(
        name="Tambah order",
        method="POST",
        path="/api/umkm/orders",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="""{
  "product_id": 1,
  "buyer_name": "Rina Saputri",
  "quantity": 2,
  "total_label": "Rp 136.000",
  "status": "diproses",
  "qr_code_data": "ORD-2026-0001",
  "note": "Bayar di tempat."
}""",
        success_response="""{
  "success": true,
  "message": "Order berhasil dibuat",
  "data": {
    "id": "ORD-2026-0001",
    "status": "diproses"
  }
}""",
        notes="Backend menerima product_id dan total_label, lalu menyimpan order dengan kode stabil.",
    ),
    Paragraph(""),
    Paragraph("11. Endpoint Konsumen", "Heading1"),
    Endpoint(
        name="Daftar produk konsumen",
        method="GET",
        path="/api/consumer/products",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": [
    {
      "code": "UMKM-P-001",
      "name": "Pancake Durian Premium",
      "category": "Paket",
      "status": "readyToSell",
      "price_label": "Rp 68.000",
      "short_description": "Paket isi 4 potong.",
      "umkm_name": "UMKM Sari Durian Jember",
      "location": "Kabupaten Jember, Jawa Timur",
      "rating": 4.9,
      "stock_label": "Stok 24 paket"
    }
  ]
}""",
        notes="Hanya produk yang terlihat untuk konsumen.",
    ),
    Endpoint(
        name="Detail produk konsumen",
        method="GET",
        path="/api/consumer/products/{code}",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": {
    "product": {
      "code": "UMKM-P-001",
      "name": "Pancake Durian Premium",
      "source_batch_code": "DRN-2026-000119",
      "source_variety": "Montong",
      "source_grade": "B"
    }
  }
}""",
        notes="Dipakai oleh layar detail konsumen untuk menampilkan trace sumber produk.",
    ),
    Endpoint(
        name="Tambah transaksi konsumen",
        method="POST",
        path="/api/consumer/transactions",
        auth="Bearer token",
        headers="Accept: application/json\nContent-Type: application/json\nAuthorization: Bearer <token>",
        request_body="""{
  "product_id": 1,
  "quantity": 2,
  "buyer_address": "Desa Pakis, Kec. Panti, Kab. Jember",
  "buyer_coordinates": "-8.2285, 113.6204",
  "payment_method": "QRIS",
  "payment_status": "unpaid",
  "bank_name": null,
  "account_number": null,
  "note": "Menunggu pembayaran."
}""",
        success_response="""{
  "success": true,
  "message": "Transaksi berhasil dibuat",
  "data": {
    "id": "TRX-2026-0001",
    "status": "processing",
    "payment_status": "unpaid",
    "qr_code_data": "TRX-2026-0001"
  }
}""",
        notes="Jika product_id kosong, backend akan menolak request dengan validasi.",
    ),
    Endpoint(
        name="Daftar transaksi konsumen",
        method="GET",
        path="/api/consumer/transactions",
        auth="Bearer token",
        headers="Accept: application/json\nAuthorization: Bearer <token>",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": "TRX-2026-0001",
      "status": "processing",
      "payment_status": "unpaid",
      "created_at": "2026-07-20T10:30:00Z"
    }
  ]
}""",
        notes="Urutan data dari yang terbaru.",
    ),
    Paragraph(""),
    Paragraph("12. Public Trace API", "Heading1"),
    Endpoint(
        name="Trace batch publik",
        method="GET",
        path="/api/trace/{batchCode}",
        auth="Public",
        headers="Accept: application/json",
        request_body="Tidak ada body request.",
        success_response="""{
  "success": true,
  "message": "OK",
  "data": {
    "batch": {
      "code": "DRN-2026-000128",
      "variety": "Montong",
      "grade": "A",
      "status": "verifiedByCollector",
      "farm_name_snapshot": "Kebun Pakis 2",
      "farm_location": "Kabupaten Jember, Jawa Timur"
    },
    "events": [ { "...": "..." } ],
    "source_batches": [],
    "shipment_history": [],
    "public_url": "https://domain-produk/api/trace/DRN-2026-000128"
  }
}""",
        notes="Endpoint ini penting untuk QR scan tanpa login. Response berisi timeline dan URL publik yang stabil.",
    ),
    Paragraph(""),
    Paragraph("13. Catatan Maintenance", "Heading1"),
    Paragraph(
        "Backend sekarang memakai helper response dan formatter terpusat. Jika nanti kontrak frontend berubah, "
        "biasanya cukup menyesuaikan di satu tempat: helper response, formatter payload, atau controller yang bersangkutan."
    ),
]


def p(text: str, style: str | None = None) -> str:
    if text == "":
        return "<w:p/>"

    runs = []
    for i, line in enumerate(text.split("\n")):
        if i > 0:
            runs.append("<w:r><w:br/></w:r>")
        runs.append(f'<w:r><w:t xml:space="preserve">{escape(line)}</w:t></w:r>')

    style_xml = f'<w:pPr><w:pStyle w:val="{style}"/></w:pPr>' if style else ""
    return f"<w:p>{style_xml}{''.join(runs)}</w:p>"


def table(rows: list[list[str]]) -> str:
    tr = []
    for row in rows:
        cells = "".join(
            f"<w:tc><w:p><w:r><w:t xml:space=\"preserve\">{escape(cell)}</w:t></w:r></w:p></w:tc>"
            for cell in row
        )
        tr.append(f"<w:tr>{cells}</w:tr>")
    return "<w:tbl>" + "".join(tr) + "</w:tbl>"


def endpoint_block(ep: Endpoint) -> list[str]:
    return [
        p(ep.name, "Heading2"),
        table([
            ["Metode", ep.method],
            ["Path", ep.path],
            ["Auth", ep.auth],
            ["Header", ep.headers],
            ["Request Body", ep.request_body],
            ["Response Sukses", ep.success_response],
            ["Catatan", ep.notes],
        ]),
        p(""),
    ]


def build_document_xml() -> str:
    body: list[str] = []
    for item in CONTENT:
        if isinstance(item, Paragraph):
            body.append(p(item.text, item.style))
        elif isinstance(item, Table):
            body.append(table(item.rows))
        elif isinstance(item, Endpoint):
            body.extend(endpoint_block(item))

    body.append(
        '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" '
        'w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
    )

    return (
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" '
        'xmlns:mo="http://schemas.microsoft.com/office/mac/office/2008/main" '
        'xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" '
        'xmlns:o="urn:schemas-microsoft-com:office:office" '
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
        'xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" '
        'xmlns:v="urn:schemas-microsoft-com:vml" '
        'xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" '
        'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
        'xmlns:w10="urn:schemas-microsoft-com:office:word" '
        'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
        'xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" '
        'xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" '
        'xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" '
        'xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" '
        'xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" '
        'mc:Ignorable="w14 wp14">'
        f"<w:body>{''.join(body)}</w:body>"
        "</w:document>"
    )


def build_styles_xml() -> str:
    return """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/>
    <w:qFormat/>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Title">
    <w:name w:val="Title"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:rPr><w:b/><w:sz w:val="36"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Subtitle">
    <w:name w:val="Subtitle"/>
    <w:basedOn w:val="Normal"/>
    <w:rPr><w:i/><w:sz w:val="20"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/>
    <w:basedOn w:val="Normal"/>
    <w:rPr><w:b/><w:sz w:val="28"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/>
    <w:basedOn w:val="Normal"/>
    <w:rPr><w:b/><w:sz w:val="24"/></w:rPr>
  </w:style>
</w:styles>
"""


def build_content_types_xml() -> str:
    return """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>
"""


def build_rels_xml() -> str:
    return """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
"""


def build_doc_rels_xml() -> str:
    return """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>
"""


def main() -> None:
    OUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(OUT_PATH, "w", compression=zipfile.ZIP_DEFLATED) as docx:
        docx.writestr("[Content_Types].xml", build_content_types_xml())
        docx.writestr("_rels/.rels", build_rels_xml())
        docx.writestr("word/document.xml", build_document_xml())
        docx.writestr("word/styles.xml", build_styles_xml())
        docx.writestr("word/_rels/document.xml.rels", build_doc_rels_xml())


if __name__ == "__main__":
    main()
