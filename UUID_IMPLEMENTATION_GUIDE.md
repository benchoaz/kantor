# UUID Implementation Guide - SIDIKSAE

## 🔒 ATURAN EMAS UUID

### 1️⃣ UUID Dibuat Sekali, Dikunci Selamanya

**RULE**: `uuid_surat` generated ONCE and NEVER regenerated

```
Surat masuk → UUID dihitung dari metadata
          ↓
       UUID disimpan di API
          ↓
UUID LOCKED (metadata boleh berubah, UUID tidak)
```

### 2️⃣ Metadata ≠ Identitas

- ❌ `no_agenda` bisa dikoreksi
- ❌ `no_surat` bisa salah ketik  
- ❌ `tgl_surat` bisa berubah format
- ✅ `uuid_surat` **IMMUTABLE**

### 3️⃣ UUID ≠ Nomor Surat

- UUID = identitas teknis sistem
- Nomor Surat = identitas administratif
- Keduanya **BERBEDA**

## 🧱 Implementasi Teknis

### UUID Generation (SuratQu Side)

**File**: `suratqu/includes/integrasi_sistem_handler.php`

**Method**: UUID v5 style with namespace

```php
// Namespace untuk prevent collision
$namespace = 'SIDIKSAE-SURATQU';

// Seed dari metadata (untuk idempotency)
$uuid_seed = sprintf(
    '%s|%s|%s|%s|%s',
    $namespace,           // Isolasi per source
    $no_agenda,
    $no_surat,
    $tgl_surat,
    $perihal              // Tambah uniqueness
);

// Generate UUID v5 style
$hash = sha1($uuid_seed);
$uuid = format_uuid_v5($hash);
```

**Properties**:
- ✅ **Deterministic** - Same metadata = same UUID
- ✅ **Namespaced** - No collision with other sources
- ✅ **Idempotent** - Retry safe

### UUID Locking (API Side)

**File**: `api/controllers/DisposisiController.php`

**Mechanism**:

```php
// Check if surat already registered
$stmt = $conn->prepare("SELECT uuid FROM surat WHERE uuid = :uuid");
$stmt->execute([':uuid' => $uuid_surat]);

if ($stmt->rowCount() > 0) {
    // UUID ALREADY EXISTS → Use existing record
    // DO NOT regenerate UUID
    // Metadata in payload ignored for UUID purposes
} else {
    // First time → Register with this UUID
    // UUID LOCKED forever
}
```

**Benefits**:
- ✅ **Immutability** - UUID never changes
- ✅ **Idempotency** - Resend safe
- ✅ **Audit trail** - Original UUID preserved

## 📊 UUID Format

### Structure

```
xxxxxxxx-xxxx-5xxx-xxxx-xxxxxxxxxxxx
└──┬──┘ └┬─┘ │└┬┘ └┬─┘ └─────┬─────┘
   │     │   │ │   │         │
   │     │   │ │   │         └─ Node (12 hex)
   │     │   │ │   └─────────── Clock seq (4 hex)
   │     │   │ └─────────────── Variant (5 = UUID v5)
   │     │   └───────────────── Version  
   │     └───────────────────── Time mid
   └─────────────────────────── Time low
```

### Example

```
f3f13c49-a760-5603-b9cd-d6fa12e1f20b
         ↑         ↑
      Version 5   Variant bits
```

## ⚠️ LARANGAN KERAS

### ❌ DILARANG

1. **Regenerate UUID** dari metadata yang berubah
2. **Mapping ulang** UUID ke ID lain
3. **Mengubah UUID** yang sudah tersimpan
4. **Menggunakan ID numerik** di URL publik
5. **Mengabaikan UUID** dalam audit log

### ✅ BOLEH

1. **Update metadata** surat (no_agenda, perihal, dll)
2. **Soft delete** surat (jangan hard delete)
3. **Relasi baru** dengan UUID yang sama
4. **Sync ulang** dengan UUID yang sama

## 🔍 Troubleshooting

### Q: Metadata surat berubah, apakah UUID berubah?

**A**: **TIDAK**. UUID di-lock saat pertama kali registered.

### Q: Bagaimana kalau ada duplikat metadata?

**A**: Namespace + perihal mencegah collision. Kalau benar-benar duplikat, UUID akan sama (by design - idempotent).

### Q: Bagaimana kalau UUID collision?

**A**: SHA1 hash space = 2^160. Collision probability negligible dengan namespace yang benar.

### Q: Backward compatibility dengan data lama?

**A**: Data lama tanpa UUID bisa di-generate sekali dengan script migration, lalu di-lock.

## 🎯 Summary

| Aspek | Implementation |
|-------|---------------|
| **Generator** | SuratQu (client-side) |
| **Method** | UUID v5 style (SHA1) |
| **Namespace** | `SIDIKSAE-SURATQU` |
| **Seed** | namespace + no_agenda + no_surat + tgl_surat + perihal |
| **Locking** | API auto-register (first-seen basis) |
| **Immutability** | ✅ Enforced by API |
| **Idempotency** | ✅ Guaranteed |
| **Collision Risk** | ✅ Minimal (namespaced) |

## 📝 Changelog

- **2026-01-08**: Initial UUID v5 implementation with namespace
- **2026-01-08**: Added API-side locking mechanism
- **2026-01-08**: Documented immutability rules

---

**Maintainer**: SIDIKSAE Development Team  
**Last Updated**: 2026-01-08 20:05 WIB
