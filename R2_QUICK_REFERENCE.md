# Cloudflare R2 Quick Reference

## Environment Variables Required

```bash
R2_ACCESS_KEY_ID=your_access_key_here
R2_SECRET_ACCESS_KEY=your_secret_key_here
R2_ENDPOINT=account-id.r2.cloudflarestorage.com
R2_BUCKET_NAME=bofetoda-announcements
R2_PUBLIC_URL=https://pub-xxxxx.r2.dev
```

## Setup Checklist

- [ ] Create R2 bucket in Cloudflare dashboard
- [ ] Enable public access on bucket
- [ ] Generate API token with Object Read & Write permissions
- [ ] Add 5 environment variables to Render dashboard
- [ ] Run `composer install` (automatic on Render deploy)
- [ ] Test upload in Admin → Manage Announcements
- [ ] Verify image displays on Driver homepage
- [ ] Check Cloudflare R2 dashboard for uploaded files

## How It Works

### Upload Flow
```
1. Admin uploads image
2. System tries R2 upload first
3. If R2 available → Upload to R2 → Store R2 key in database
4. If R2 fails → Upload to local storage → Store local path in database
5. Image path stored: either "announcements/file.jpg" (R2) or "uploads/announcements/file.jpg" (local)
```

### Display Flow
```
1. Get image path from database
2. Check if it's R2 key (starts with "announcements/")
3. If R2 key → Prepend R2_PUBLIC_URL → https://pub-xxx.r2.dev/announcements/file.jpg
4. If local path → Prepend relative path → ../uploads/announcements/file.jpg
5. Display image with generated URL
```

## Key Functions

### Admin Side (`Admin_BOFETODA/config.php`)
- `upload_announcement_images()` - Handles multi-image upload with R2 integration
- `delete_announcement_image()` - Deletes from R2 or local storage

### R2 Storage (`Admin_BOFETODA/r2-storage.php`)
- `get_r2_client()` - Initializes AWS S3 client for R2
- `is_r2_enabled()` - Checks if R2 credentials are configured
- `r2_upload_file()` - Uploads file to R2 bucket
- `r2_delete_file()` - Deletes file from R2 bucket
- `upload_announcement_image_r2()` - Upload with automatic fallback to local
- `delete_announcement_image_r2()` - Delete from R2 or local

### Driver Side (`Driver_BOFETODA/driver-database.php`)
- `get_announcement_image_url()` - Converts database path to full URL

## Testing R2 Integration

### Test Upload
```bash
# 1. Create announcement with image
curl -X POST https://bofetoda-app.onrender.com/Admin_BOFETODA/manage_announcements.php \
  -F "title=Test Announcement" \
  -F "message=Testing R2 upload" \
  -F "images[]=@test.jpg"

# 2. Check Cloudflare R2 bucket for file
# 3. Check driver homepage for image display
```

### Check Storage Status
```bash
# View Render logs
# Look for:
# [info] R2 upload successful: announcements/announcement_X_XXXXX.jpg
# OR
# [warning] R2 upload failed, falling back to local storage
```

## Troubleshooting Commands

### Verify R2 Credentials
```php
<?php
require_once 'Admin_BOFETODA/r2-storage.php';
var_dump([
    'R2_ENABLED' => is_r2_enabled(),
    'ACCESS_KEY' => getenv('R2_ACCESS_KEY_ID') ? 'SET' : 'NOT SET',
    'ENDPOINT' => getenv('R2_ENDPOINT') ?: 'NOT SET',
    'BUCKET' => getenv('R2_BUCKET_NAME') ?: 'NOT SET',
    'PUBLIC_URL' => getenv('R2_PUBLIC_URL') ?: 'NOT SET'
]);
```

### Test R2 Upload Directly
```php
<?php
require_once 'Admin_BOFETODA/r2-storage.php';

$testFile = [
    'tmp_name' => '/path/to/test.jpg',
    'name' => 'test.jpg',
    'size' => filesize('/path/to/test.jpg'),
    'type' => 'image/jpeg'
];

$url = r2_upload_file($testFile, 'test/test.jpg');
echo $url ? "SUCCESS: $url" : "FAILED";
```

### Check Image Path Format
```sql
-- Check existing image paths
SELECT image_id, image_path 
FROM announcement_images 
LIMIT 10;

-- R2 format: announcements/announcement_X_XXXXX.jpg
-- Local format: uploads/announcements/announcement_X_XXXXX.jpg
```

## Common Issues

| Issue | Cause | Fix |
|-------|-------|-----|
| Images not uploading | R2 credentials missing | Add env vars to Render |
| Images upload but don't display | Public access not enabled | Enable in Cloudflare R2 settings |
| "Failed to initialize R2 client" | Wrong endpoint format | Use `account-id.r2.cloudflarestorage.com` (no https://) |
| Uploads work locally but not on Render | Env vars not set on Render | Add all 5 env vars in Render dashboard |
| Images display locally but not from R2 | Wrong R2_PUBLIC_URL | Copy exact URL from Cloudflare |

## Costs (Free Tier)

- **Storage**: 10 GB/month
- **Class A Operations** (uploads): 10 million/month
- **Class B Operations** (downloads): 100 million/month
- **Egress**: Unlimited (always free)

**Typical usage**: ~50 announcements/month × 3 images = 150 images (~75 MB)
**Cost**: $0.00/month ✓

## Migration from Local to R2

If you already have images in local storage:

```php
// Run once then delete
<?php
require_once 'Admin_BOFETODA/db.php';
require_once 'Admin_BOFETODA/r2-storage.php';

$pdo = get_pdo();
$images = $pdo->query("SELECT image_id, image_path FROM announcement_images WHERE image_path LIKE 'uploads/announcements/%'")->fetchAll();

foreach ($images as $img) {
    $localPath = __DIR__ . '/' . $img['image_path'];
    if (!file_exists($localPath)) continue;
    
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
        echo "✓ Migrated: {$img['image_path']}\n";
    }
}
```

## Resources

- Full setup guide: `CLOUDFLARE_R2_SETUP.md`
- Cloudflare R2 Docs: https://developers.cloudflare.com/r2/
- AWS SDK for PHP: https://docs.aws.amazon.com/sdk-for-php/

---

**Last Updated**: October 23, 2026
