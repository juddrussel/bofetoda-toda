# Cloudflare R2 Folder Structure

## Overview

This document details the folder structure and file organization in the Cloudflare R2 bucket for the BOFETODA TODA Management System.

**Bucket Name**: `bofetoda-announcements`  
**Public URL**: `https://pub-xxxxx.r2.dev`

---

## Folder Structure

```
bofetoda-announcements/
├── announcements/                      # General announcement images
│   └── announcement_{id}_{timestamp}_{uniqueid}.{ext}
│
├── documents/                          # Driver renewal documents
│   └── {driver_id}/                    # One folder per driver
│       ├── drivers_license_{timestamp}_{uniqueid}.{ext}
│       ├── or_cr_{timestamp}_{uniqueid}.{ext}
│       ├── payment_receipt_{timestamp}_{uniqueid}.{ext}
│       └── picture_1x1_{timestamp}_{uniqueid}.{ext}
│
└── avatars/                            # Driver profile avatars
    └── {driver_id}.{ext}               # One avatar per driver
```

---

## Folder Details

### 1. `/announcements/`

**Purpose**: Store images attached to general announcements (events, reminders, updates, etc.)

**File Naming**:
```
announcement_{announcement_id}_{timestamp}_{uniqueid}.{extension}
```

**Examples**:
```
announcements/announcement_1_1729684800_abc123de.jpg
announcements/announcement_5_1729685123_f4e3d2c1.png
announcements/announcement_12_1729685500_9a8b7c6d.webp
```

**Allowed Extensions**: `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp`  
**Max File Size**: 5 MB per image  
**Max Images per Announcement**: 5 images  

**Access**: Public (read-only)

**Database Reference**: `announcement_images.image_path`
```sql
SELECT image_path FROM announcement_images WHERE announcement_id = 1;
-- Returns: announcements/announcement_1_1729684800_abc123de.jpg
```

---

### 2. `/documents/{driver_id}/`

**Purpose**: Store driver renewal application documents (license, OR/CR, receipt, 1x1 photo)

**Folder Naming**:
```
documents/{driver_id}/
```
Where `{driver_id}` is the driver's unique ID (e.g., `DRV-BG0001`, `DRV-BG0172`)

**File Naming**:
```
{document_type}_{timestamp}_{uniqueid}.{extension}
```

**Document Types**:
- `drivers_license` - Driver's license (front/back)
- `or_cr` - Official Receipt / Certificate of Registration (OR/CR)
- `payment_receipt` - Payment receipt for renewal
- `picture_1x1` - 1x1 ID photo

**Examples**:
```
documents/DRV-BG0001/drivers_license_1729684800_a1b2c3d4.jpg
documents/DRV-BG0001/or_cr_1729684800_e5f6g7h8.pdf
documents/DRV-BG0001/payment_receipt_1729684800_i9j0k1l2.jpg
documents/DRV-BG0001/picture_1x1_1729684800_m3n4o5p6.png

documents/DRV-BG0172/drivers_license_1729685000_q7r8s9t0.jpg
documents/DRV-BG0172/or_cr_1729685000_u1v2w3x4.jpeg
documents/DRV-BG0172/payment_receipt_1729685000_y5z6a7b8.pdf
documents/DRV-BG0172/picture_1x1_1729685000_c9d0e1f2.jpg
```

**Allowed Extensions**: `.jpg`, `.jpeg`, `.png`, `.pdf`  
**Max File Size**: 4 MB per document  
**Required Documents**: All 4 documents required per application

**Access**: Public (but requires knowing driver ID and file names)

**Database Reference**: `application_documents.file_path`
```sql
SELECT doc_type, file_path 
FROM application_documents 
WHERE app_no = 'APP-BG-2026-0001';

-- Returns:
-- license  | documents/DRV-BG0001/drivers_license_1729684800_a1b2c3d4.jpg
-- or_cr    | documents/DRV-BG0001/or_cr_1729684800_e5f6g7h8.pdf
-- receipt  | documents/DRV-BG0001/payment_receipt_1729684800_i9j0k1l2.jpg
-- photo    | documents/DRV-BG0001/picture_1x1_1729684800_m3n4o5p6.png
```

---

### 3. `/avatars/`

**Purpose**: Store driver profile avatars (profile pictures)

**File Naming**:
```
{driver_id}.{extension}
```

**Examples**:
```
avatars/DRV-BG0001.jpg
avatars/DRV-BG0172.png
avatars/DRV-BG0351.jpeg
```

**Allowed Extensions**: `.jpg`, `.jpeg`, `.png`  
**Max File Size**: 4 MB  
**Limit**: 1 avatar per driver (old avatar replaced on new upload)

**Access**: Public (read-only)

**Database Reference**: Stored in `drivers.avatar_path` (if implemented) or derived from driver_id

**Note**: When a driver uploads a new avatar, the old one is deleted from R2 before uploading the new one.

---

## File Naming Convention

### Format
```
{prefix}_{identifier}_{timestamp}_{uniqueid}.{extension}
```

### Components

| Component | Description | Example |
|-----------|-------------|---------|
| **prefix** | File type or purpose | `announcement`, `drivers_license`, `or_cr` |
| **identifier** | Related ID (announcement_id or driver_id) | `1`, `DRV-BG0001` |
| **timestamp** | Unix timestamp (seconds since epoch) | `1729684800` |
| **uniqueid** | Random hex string (8 characters) | `abc123de` |
| **extension** | File extension (lowercase) | `jpg`, `png`, `pdf` |

### Why This Format?

1. **Organized**: Easy to find files by type and owner
2. **Unique**: Timestamp + random ID prevents collisions
3. **Sortable**: Timestamp allows chronological sorting
4. **Immutable**: Files never overwrite each other (except avatars)
5. **Traceable**: Can trace back to source (announcement ID, driver ID)

---

## URL Resolution

### R2 Public URLs

Files stored in R2 are accessed via public bucket URL:

```
https://pub-xxxxx.r2.dev/{folder}/{filename}
```

**Examples**:
```
https://pub-xxxxx.r2.dev/announcements/announcement_1_1729684800_abc123de.jpg
https://pub-xxxxx.r2.dev/documents/DRV-BG0001/drivers_license_1729684800_a1b2c3d4.jpg
https://pub-xxxxx.r2.dev/avatars/DRV-BG0001.jpg
```

### Database Storage

The database stores **R2 keys** (not full URLs):

| Storage | Format | Example |
|---------|--------|---------|
| **Database** | R2 key (relative path) | `documents/DRV-BG0001/license.jpg` |
| **Display** | Full R2 URL | `https://pub-xxx.r2.dev/documents/DRV-BG0001/license.jpg` |

**Why store keys instead of URLs?**
- URLs may change (if bucket moves or domain changes)
- Keys are permanent and portable
- Application can construct URLs dynamically

### URL Generation

**PHP Function**: `get_file_url($filePath)`

```php
// Database stores: documents/DRV-BG0001/license.jpg
$filePath = 'documents/DRV-BG0001/drivers_license_1729684800_a1b2c3d4.jpg';

// Function returns: https://pub-xxxxx.r2.dev/documents/DRV-BG0001/license.jpg
$url = get_file_url($filePath);

echo '<img src="' . htmlspecialchars($url) . '" alt="License">';
```

---

## Storage Strategy: R2 First, Local Fallback

### Upload Flow

```
1. Driver/Admin uploads file
2. System tries R2 upload first
   ├─ R2 available → Upload to R2 → Store R2 key in database ✓
   └─ R2 unavailable → Upload to local storage → Store local path in database
3. File accessible via get_file_url()
```

### Path Format Detection

| Path Format | Storage Type | Example |
|-------------|--------------|---------|
| `documents/...` | R2 storage | `documents/DRV-BG0001/license.jpg` |
| `avatars/...` | R2 storage | `avatars/DRV-BG0001.jpg` |
| `announcements/...` | R2 storage | `announcements/announcement_1_xxx.jpg` |
| `uploads/...` | Local storage | `uploads/documents/DRV-BG0001/license.jpg` |
| `Driver_BOFETODA/uploads/...` | Local storage | `Driver_BOFETODA/uploads/avatars/DRV-BG0001.jpg` |

---

## Migration from Local to R2

### Existing Files in Local Storage

If you have existing files in local storage (`/uploads/`), migrate them to R2:

#### Step 1: Identify Local Files

```sql
-- Find all local document paths
SELECT app_no, doc_type, file_path 
FROM application_documents 
WHERE file_path LIKE 'uploads/%';

-- Find all local avatar paths (if tracked in database)
SELECT driver_id, avatar_path 
FROM drivers 
WHERE avatar_path LIKE 'uploads/%';
```

#### Step 2: Run Migration Script

Create `migrate_all_files_to_r2.php`:

```php
<?php
require_once __DIR__ . '/Admin_BOFETODA/db.php';
require_once __DIR__ . '/Admin_BOFETODA/r2-storage.php';

$pdo = get_pdo();

// Migrate announcement images
echo "=== Migrating Announcement Images ===\n";
$images = $pdo->query("SELECT image_id, announcement_id, image_path FROM announcement_images WHERE image_path LIKE 'uploads/announcements/%'")->fetchAll();

foreach ($images as $img) {
    $localPath = __DIR__ . '/' . $img['image_path'];
    if (!file_exists($localPath)) {
        echo "Skip: {$img['image_path']} (not found)\n";
        continue;
    }
    
    $file = [
        'tmp_name' => $localPath,
        'name' => basename($localPath),
        'size' => filesize($localPath),
        'type' => mime_content_type($localPath)
    ];
    
    $r2Key = 'announcements/' . basename($localPath);
    $url = r2_upload_file($file, $r2Key);
    
    if ($url) {
        $pdo->prepare("UPDATE announcement_images SET image_path = ? WHERE image_id = ?")
            ->execute([$r2Key, $img['image_id']]);
        echo "✓ Migrated: {$img['image_path']} → R2\n";
    } else {
        echo "✗ Failed: {$img['image_path']}\n";
    }
}

// Migrate driver documents
echo "\n=== Migrating Driver Documents ===\n";
$docs = $pdo->query("SELECT app_no, doc_type, file_path FROM application_documents WHERE file_path LIKE 'uploads/documents/%'")->fetchAll();

foreach ($docs as $doc) {
    $localPath = __DIR__ . '/Driver_BOFETODA/' . $doc['file_path'];
    if (!file_exists($localPath)) {
        echo "Skip: {$doc['file_path']} (not found)\n";
        continue;
    }
    
    // Extract driver ID from path: uploads/documents/DRV-BG0001/file.jpg
    preg_match('#uploads/documents/([^/]+)/#', $doc['file_path'], $matches);
    $driverId = $matches[1] ?? 'unknown';
    
    $file = [
        'tmp_name' => $localPath,
        'name' => basename($localPath),
        'size' => filesize($localPath),
        'type' => mime_content_type($localPath)
    ];
    
    $r2Key = 'documents/' . $driverId . '/' . basename($localPath);
    $url = r2_upload_file($file, $r2Key);
    
    if ($url) {
        $pdo->prepare("UPDATE application_documents SET file_path = ? WHERE app_no = ? AND doc_type = ?")
            ->execute([$r2Key, $doc['app_no'], $doc['doc_type']]);
        echo "✓ Migrated: {$doc['file_path']} → R2\n";
    } else {
        echo "✗ Failed: {$doc['file_path']}\n";
    }
}

// Migrate avatars (if you have them in database or file system)
echo "\n=== Migrating Driver Avatars ===\n";
$avatarDir = __DIR__ . '/Driver_BOFETODA/uploads/avatars/';
if (is_dir($avatarDir)) {
    $avatarFiles = glob($avatarDir . '*.*');
    foreach ($avatarFiles as $localPath) {
        $filename = basename($localPath);
        // Extract driver ID: DRV-BG0001.jpg
        $driverId = pathinfo($filename, PATHINFO_FILENAME);
        
        $file = [
            'tmp_name' => $localPath,
            'name' => $filename,
            'size' => filesize($localPath),
            'type' => mime_content_type($localPath)
        ];
        
        $r2Key = 'avatars/' . $filename;
        $url = r2_upload_file($file, $r2Key);
        
        if ($url) {
            echo "✓ Migrated: avatars/$filename → R2\n";
            // Update database if you track avatars
            // $pdo->prepare("UPDATE drivers SET avatar_path = ? WHERE driver_id = ?")->execute([$r2Key, $driverId]);
        } else {
            echo "✗ Failed: avatars/$filename\n";
        }
    }
}

echo "\n=== Migration Complete ===\n";
```

#### Step 3: Run and Verify

```bash
# Run migration
php migrate_all_files_to_r2.php

# Verify in Cloudflare R2 dashboard
# Check database for updated paths
```

#### Step 4: Clean Up

```bash
# Delete migration script
rm migrate_all_files_to_r2.php

# Optional: Remove old local files after verifying R2 migration
# Be careful - ensure R2 has all files first!
```

---

## Security Considerations

### Public Access

All folders are **public-readable** by default:
- Anyone with the URL can view files
- No authentication required
- Suitable for non-sensitive data

**Why Public?**
- Simplifies image display (no signed URLs needed)
- Faster access (no API calls for each request)
- CDN-friendly (better caching)

**Security through Obscurity:**
- File names include random unique IDs
- Driver IDs are required to construct URLs
- Files are not indexed (no directory listing)

### Private Data Concerns

If you need to store sensitive documents privately:

1. **Use Private Bucket**:
   - Disable public access in Cloudflare
   - Generate signed URLs on-demand

2. **Implement Access Control**:
   - Check user permissions before serving files
   - Use `doc-file.php` proxy for authentication

3. **Encrypt Sensitive Data**:
   - Encrypt files before upload
   - Decrypt on download

---

## Storage Quotas & Monitoring

### Cloudflare R2 Free Tier

| Resource | Free Tier Limit | Typical Usage |
|----------|----------------|---------------|
| **Storage** | 10 GB | ~75 MB/month |
| **Class A Operations** (uploads) | 10 million/month | ~150/month |
| **Class B Operations** (downloads) | 100 million/month | ~12,000/month |
| **Egress** | Unlimited | Always free |

### Estimated Storage by File Type

| File Type | Avg Size | Files per GB | Expected Count |
|-----------|----------|--------------|----------------|
| Announcement images | 500 KB | 2,000 | 150 images/month |
| Driver documents (JPG) | 300 KB | 3,400 | 800 docs/month |
| Driver documents (PDF) | 500 KB | 2,000 | 200 docs/month |
| Driver avatars | 200 KB | 5,000 | 200 avatars total |

**Total Expected Usage**: ~500 MB/month (5% of free tier ✓)

### Monitoring Commands

**List all files in bucket**:
```bash
# Install AWS CLI
aws configure set aws_access_key_id $R2_ACCESS_KEY_ID
aws configure set aws_secret_access_key $R2_SECRET_ACCESS_KEY
aws configure set region auto

# List files
aws s3 ls s3://bofetoda-announcements/ --recursive --endpoint-url https://$R2_ENDPOINT
```

**Check bucket size**:
```bash
aws s3 ls s3://bofetoda-announcements/ --recursive --summarize --human-readable --endpoint-url https://$R2_ENDPOINT
```

---

## Troubleshooting

### Issue: Files not uploading to R2

**Check**:
1. R2 credentials set in environment variables
2. Bucket name correct (`bofetoda-announcements`)
3. API token has Write permissions
4. Check error logs: `error_log('R2 upload failed: ...')`

**Solution**: Verify `.env` or Render environment variables

---

### Issue: Files upload but can't be accessed

**Check**:
1. Bucket public access enabled
2. `R2_PUBLIC_URL` set correctly
3. File path format in database (R2 key vs local path)

**Solution**: Enable public access in Cloudflare R2 settings

---

### Issue: Old local files still being used

**Check**:
1. Database paths still point to `uploads/` instead of R2 keys
2. Migration script not run
3. New uploads not using R2

**Solution**: Run migration script to update database paths

---

## Summary

| Aspect | Details |
|--------|---------|
| **Bucket** | `bofetoda-announcements` |
| **Folders** | `announcements/`, `documents/{driver_id}/`, `avatars/` |
| **Access** | Public (read-only) |
| **Naming** | `{prefix}_{id}_{timestamp}_{unique}.{ext}` |
| **Storage** | R2 first, local fallback |
| **Cost** | $0/month (within free tier) |
| **Security** | Public URLs, random filenames, no directory listing |

---

**Last Updated**: October 23, 2026  
**Version**: 1.0.0
