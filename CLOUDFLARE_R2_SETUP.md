# Cloudflare R2 Storage Setup Guide

This guide walks you through setting up Cloudflare R2 storage for announcement images in the BOFETODA TODA Management System.

## Why R2?

Render's free tier uses ephemeral storage, meaning uploaded files are lost on each deployment or restart. R2 provides persistent cloud storage with:
- **Free tier**: 10 GB storage, 10 million Class A operations, 100 million Class B operations per month
- **No egress fees**: Unlike AWS S3, R2 doesn't charge for data transfer
- **S3 compatibility**: Works with AWS SDK
- **Global CDN**: Fast image delivery worldwide

---

## Step 1: Create Cloudflare R2 Bucket

### 1.1 Access R2 Dashboard
1. Log in to your [Cloudflare dashboard](https://dash.cloudflare.com/)
2. Navigate to **R2** from the left sidebar
3. If prompted, enable R2 on your account (requires payment method, but free tier is generous)

### 1.2 Create Bucket
1. Click **Create bucket**
2. Enter bucket name: `bofetoda-announcements`
3. Select location: **Automatic** (or choose closest to Philippines)
4. Click **Create bucket**

### 1.3 Configure Public Access
1. Go to your bucket settings
2. Under **Settings** → **Public access**
3. Click **Allow Access**
4. Enable **Public Bucket**
5. Copy the **Public Bucket URL** (looks like: `https://pub-xxxxx.r2.dev`)
   - Save this for later as `R2_PUBLIC_URL`

**Note**: Public access allows direct image viewing via URL. For sensitive files, use private buckets with signed URLs.

---

## Step 2: Generate R2 API Token

### 2.1 Create API Token
1. In R2 dashboard, click **Manage R2 API Tokens**
2. Click **Create API Token**
3. Configure token:
   - **Token name**: `bofetoda-app-token`
   - **Permissions**: 
     - ✅ Object Read & Write
   - **Specify bucket(s)**: Select `bofetoda-announcements`
   - **TTL**: Leave as "Forever" or set expiration
4. Click **Create API Token**

### 2.2 Save Credentials
**IMPORTANT**: Copy these values immediately (they won't be shown again):

```
Access Key ID: xxxxxxxxxxxxxxxxxxxxx
Secret Access Key: yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy
Endpoint: account-id.r2.cloudflarestorage.com
```

Save these securely - you'll need them in the next step.

---

## Step 3: Configure Environment Variables

### 3.1 On Render Dashboard
1. Go to your [Render dashboard](https://dashboard.render.com/)
2. Select your **bofetoda-app** web service
3. Go to **Environment** tab
4. Click **Add Environment Variable**

Add these 4 variables:

| Key | Value | Example |
|-----|-------|---------|
| `R2_ACCESS_KEY_ID` | Your Access Key ID | `abc123def456...` |
| `R2_SECRET_ACCESS_KEY` | Your Secret Access Key | `xyz789uvw012...` |
| `R2_ENDPOINT` | Your account endpoint | `abc123.r2.cloudflarestorage.com` |
| `R2_BUCKET_NAME` | Bucket name | `bofetoda-announcements` |
| `R2_PUBLIC_URL` | Public bucket URL | `https://pub-xxxxx.r2.dev` |

**Security Note**: Make sure these are added as **Secret** environment variables (not visible in logs).

### 3.2 Save and Deploy
1. Click **Save Changes**
2. Render will automatically redeploy your app
3. Wait for deployment to complete (~2-3 minutes)

---

## Step 4: Install Dependencies

### 4.1 Local Development
If testing locally, run:

```bash
composer install
```

This installs the AWS SDK for PHP (v3) required for R2 integration.

### 4.2 Render Deployment
The `composer install` command is automatically run during Render deployment. Verify in your build logs:

```
Running 'composer install'
Installing dependencies from lock file
```

If you see errors, check that `composer.json` exists in your repository root.

---

## Step 5: Test R2 Integration

### 5.1 Test Upload
1. Log in to **Admin Portal**: `https://bofetoda-app.onrender.com/Admin_BOFETODA/login.php`
2. Go to **Manage Announcements**
3. Click **Create New Announcement**
4. Fill in details and upload an image
5. Click **Create**

### 5.2 Verify Upload
**Check in Cloudflare Dashboard**:
1. Go to R2 → `bofetoda-announcements` bucket
2. You should see a file like: `announcements/announcement_1_1234567890_abc123.jpg`

**Check on Driver Side**:
1. Log out and go to **Driver Portal**: `https://bofetoda-app.onrender.com/Driver_BOFETODA/driver-login.php`
2. Log in as a driver
3. Check homepage - images should display from R2

### 5.3 Check Image URL
Right-click an image → **Inspect** → verify the `src` attribute:
- ✅ **R2**: `https://pub-xxxxx.r2.dev/announcements/announcement_1_...jpg`
- ❌ **Local**: `../uploads/announcements/announcement_1_...jpg`

---

## Step 6: Fallback Behavior

The system automatically falls back to local storage if R2 is unavailable:

### When Fallback Occurs
- R2 credentials are missing/invalid
- R2 service is down
- Network connectivity issues
- API rate limits exceeded

### Fallback Process
1. Upload attempts R2 first
2. On failure, saves to `/uploads/announcements/` (ephemeral on Render)
3. Error logged: `"R2 upload failed, falling back to local storage"`

### Check Logs
View Render logs to see storage status:

```bash
# In Render dashboard → Logs tab
[info] R2 upload successful: announcements/announcement_1_...jpg
# OR
[warning] R2 upload failed, falling back to local storage
```

---

## Troubleshooting

### Issue: "R2 credentials not configured"

**Cause**: Environment variables not set correctly.

**Fix**:
1. Check Render environment variables are spelled exactly:
   - `R2_ACCESS_KEY_ID` (not `R2_ACCESS_KEY`)
   - `R2_SECRET_ACCESS_KEY`
   - `R2_ENDPOINT`
   - `R2_BUCKET_NAME`
2. Redeploy after adding variables

### Issue: "Failed to initialize R2 client"

**Cause**: Invalid credentials or endpoint format.

**Fix**:
1. Verify `R2_ENDPOINT` format: `account-id.r2.cloudflarestorage.com` (no https://)
2. Regenerate API token if needed
3. Check token has **Object Read & Write** permissions

### Issue: Images not displaying

**Cause**: Public access not enabled or wrong `R2_PUBLIC_URL`.

**Fix**:
1. Enable **Public Bucket** in Cloudflare R2 settings
2. Verify `R2_PUBLIC_URL` matches public bucket URL from Cloudflare
3. Check browser console for CORS errors

### Issue: "Access Denied" error

**Cause**: API token doesn't have permission for the bucket.

**Fix**:
1. Go to Cloudflare → R2 → Manage R2 API Tokens
2. Edit token → ensure `bofetoda-announcements` is selected
3. Regenerate token if needed
4. Update `R2_ACCESS_KEY_ID` and `R2_SECRET_ACCESS_KEY` in Render

### Issue: Uploads work but images don't load

**Cause**: Bucket is not public or wrong URL format.

**Fix**:
1. Check bucket → Settings → Public access is enabled
2. Test direct URL: `https://pub-xxxxx.r2.dev/announcements/test.jpg`
3. If 403 error, enable public access
4. Verify `R2_PUBLIC_URL` ends without trailing slash

---

## Cost Estimation

### Free Tier Limits (per month)
- **Storage**: 10 GB (enough for ~10,000 images at 1MB each)
- **Class A operations** (uploads): 10 million (~330,000 per day)
- **Class B operations** (downloads): 100 million (~3.3 million per day)
- **Egress**: Unlimited (always free!)

### Typical Usage
For BOFETODA with ~200 active drivers:
- **Uploads**: ~50 announcements/month × 3 images = 150 uploads
- **Storage**: 150 images × 500 KB = 75 MB
- **Downloads**: 200 drivers × 150 images × 5 views = 150,000 downloads

**Estimated cost**: $0.00/month (well within free tier)

---

## Monitoring Usage

### Cloudflare Dashboard
1. Go to R2 → `bofetoda-announcements`
2. View **Metrics** tab
3. Monitor:
   - Storage used (GB)
   - Requests (Class A/B operations)
   - Bandwidth (egress)

### Set Up Alerts (Optional)
1. Go to **Notifications** in Cloudflare dashboard
2. Create alert for:
   - Storage > 8 GB (80% of free tier)
   - Requests > 8 million Class A operations

---

## Migration: Move Existing Local Images to R2

If you already have images in `/uploads/announcements/`, migrate them to R2:

### Option 1: Manual Upload (Small Number)
1. Download images from Render (if still available)
2. Upload to R2 bucket via Cloudflare dashboard
3. Update database `image_path` to R2 keys

### Option 2: Automated Migration Script (Recommended)

Create `migrate_images_to_r2.php`:

```php
<?php
require_once __DIR__ . '/Admin_BOFETODA/db.php';
require_once __DIR__ . '/Admin_BOFETODA/r2-storage.php';

$pdo = get_pdo();
$images = $pdo->query("SELECT image_id, image_path FROM announcement_images")->fetchAll();

foreach ($images as $img) {
    $localPath = __DIR__ . '/' . $img['image_path'];
    
    if (!file_exists($localPath)) {
        echo "Skip: {$img['image_path']} (not found locally)\n";
        continue;
    }
    
    // Prepare file array for R2 upload
    $file = [
        'tmp_name' => $localPath,
        'name' => basename($localPath),
        'size' => filesize($localPath),
        'type' => mime_content_type($localPath)
    ];
    
    $r2Key = 'announcements/' . basename($localPath);
    $url = r2_upload_file($file, $r2Key);
    
    if ($url) {
        // Update database with R2 key
        $stmt = $pdo->prepare("UPDATE announcement_images SET image_path = :path WHERE image_id = :id");
        $stmt->execute(['path' => $r2Key, 'id' => $img['image_id']]);
        echo "Migrated: {$img['image_path']} → R2\n";
    } else {
        echo "Failed: {$img['image_path']}\n";
    }
}

echo "\nMigration complete!\n";
```

Run once via browser, then **delete the script** for security.

---

## Security Best Practices

### 1. Never Commit Credentials
- Keep R2 credentials in environment variables only
- Don't hardcode in source code
- Add `.env` to `.gitignore`

### 2. Rotate API Tokens Regularly
- Regenerate R2 tokens every 6-12 months
- Update Render environment variables after rotation

### 3. Use IAM Permissions
- Limit token permissions to specific bucket only
- Use separate tokens for staging/production

### 4. Monitor Access Logs
- Enable R2 access logging (if available)
- Review for suspicious activity

### 5. Content Security
- Validate file types before upload (already implemented)
- Scan for malware if handling user uploads at scale
- Set size limits (current: 5MB max per image)

---

## Additional Resources

- [Cloudflare R2 Documentation](https://developers.cloudflare.com/r2/)
- [AWS SDK for PHP (S3 Client)](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-examples.html)
- [R2 Pricing](https://developers.cloudflare.com/r2/pricing/)
- [Render Environment Variables](https://render.com/docs/environment-variables)

---

## Support

For issues specific to:
- **R2 setup**: Contact Cloudflare support
- **Render deployment**: Contact Render support
- **Application code**: Check GitHub repository issues

---

**Last Updated**: October 23, 2026
**Version**: 1.0.0
