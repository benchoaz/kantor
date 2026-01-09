# Docku Disposition Module - Installation Guide

## 📦 Package Contents

This archive contains the new disposition tracking module for Docku application:

```
modules/disposisi/
├── inbox.php              - Main inbox page for viewing dispositions
└── submit-report.php      - Report submission form with PDF upload

includes/
└── notification_helper.php - Updated with new badge counter logic

uploads/
└── laporan/               - Directory for PDF report storage
```

## 🚀 Installation Steps

### 1. Extract Archive
```bash
cd /path/to/Docku
tar -xzf docku-disposition-module.tar.gz
```

### 2. Set Directory Permissions
```bash
chmod 755 modules/disposisi/
chmod 644 modules/disposisi/*.php
chmod 755 uploads/laporan/
chmod 777 uploads/laporan/  # For file uploads
```

### 3. Verify Database Tables
Ensure you have the following tables with the required columns:

**disposisi** table:
- id
- external_id (SuratQu ID)
- perihal
- instruksi
- sifat
- tgl_disposisi

**disposisi_penerima** table:
- id
- disposisi_id
- user_id
- status_followup (values: 'pending', 'in_progress', 'completed')
- laporan_catatan
- laporan_file_url
- tgl_selesai

### 4. Test the Module

1. Login as a staff user (Kasi/Sekcam)
2. Navigate to: `modules/disposisi/inbox.php`
3. Check notification badge in header
4. Test report submission

### 5. Optional: Update Navigation Links

If you want the main disposition navigation to point to the new inbox page, update `includes/header.php`:

Find and replace:
```php
href="<?= $base_url ?>modules/disposisi/index.php"
```

With:
```php
href="<?= $base_url ?>modules/disposisi/inbox.php"
```

## ✅ Features Included

- ✨ User-specific disposition inbox
- 📝 PDF report submission with validation
- 🔔 Real-time notification badges (desktop + mobile)
- 📱 Responsive Bootstrap 5 design
- 🔒 Secure database queries with prepared statements

## 🧪 Testing Checklist

- [ ] Inbox displays user-specific dispositions
- [ ] Empty state shows when no dispositions
- [ ] Badges show correct count
- [ ] Report form validates properly
- [ ] PDF uploads successfully
- [ ] Status updates to 'completed' after submission
- [ ] Reports visible in Camat application

## 📞 Support

For issues or questions, refer to the walkthrough.md documentation.

---
**Version:** 1.0  
**Date:** 2026-01-06  
**Compatible with:** Docku v1.x
