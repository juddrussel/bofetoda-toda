# Cloudflare R2 Deployment Checklist

Complete this checklist to deploy R2 storage integration for BOFETODA announcement images.

---

## Pre-Deployment

### ☐ 1. Cloudflare Account Setup
- [ ] Have active Cloudflare account
- [ ] Payment method added (required for R2, but free tier available)
- [ ] R2 enabled on account

### ☐ 2. Review Documentation
- [ ] Read `CLOUDFLARE_R2_SETUP.md` (full guide)
- [ ] Review `R2_QUICK_REFERENCE.md` (quick commands)
- [ ] Understand storage costs (free tier limits)

---

## Cloudflare R2 Configuration

### ☐ 3. Create R2 Bucket
- [ ] Log in to Cloudflare dashboard
- [ ] Navigate to **R2** section
- [ ] Click **Create bucket**
- [ ] Name: `bofetoda-announcements`
- [ ] Location: **Automatic** (or Asia if available)
- [ ] Click **Create bucket**

### ☐ 4. Enable Public Access
- [ ] Go to bucket settings
- [ ] Navigate to **Settings** → **Public access**
- [ ] Click **Allow Access**
- [ ] Enable **Public Bucket**
- [ ] Copy **Public Bucket URL**: `https://pub-xxxxx.r2.dev`
- [ ] Save URL as `R2_PUBLIC_URL` for later

### ☐ 5. Generate API Token
- [ ] In R2 dashboard, click **Manage R2 API Tokens**
- [ ] Click **Create API Token**
- [ ] Token name: `bofetoda-app-token`
- [ ] Permissions: **Object Read & Write** ✓
- [ ] Apply to specific buckets: `bofetoda-announcements` ✓
- [ ] TTL: Forever (or set expiration)
- [ ] Click **Create API Token**
- [ ] **IMMEDIATELY COPY** these values:
  - [ ] Access Key ID
  - [ ] Secret Access Key
  - [ ] Endpoint (format: `account-id.r2.cloudflarestorage.com`)

**⚠️ WARNING**: Secret Access Key is shown ONLY ONCE. Save securely!

---

## Render Configuration

### ☐ 6. Add Environment Variables
- [ ] Go to [Render Dashboard](https://dashboard.render.com/)
- [ ] Select **bofetoda-app** web service
- [ ] Click **Environment** tab
- [ ] Add these 5 variables:

| Variable | Value | Status |
|----------|-------|--------|
| `R2_ACCESS_KEY_ID` | [From Cloudflare] | ☐ |
| `R2_SECRET_ACCESS_KEY` | [From Cloudflare] | ☐ |
| `R2_ENDPOINT` | [From Cloudflare] | ☐ |
| `R2_BUCKET_NAME` | `bofetoda-announcements` | ☐ |
| `R2_PUBLIC_URL` | [From Cloudflare] | ☐ |

### ☐ 7. Deploy Changes
- [ ] Click **Save Changes** in Render
- [ ] Wait for automatic redeployment to start
- [ ] Monitor deployment logs for success
- [ ] Check logs for `composer install` success
- [ ] Wait for deployment to complete (~2-3 minutes)

---

## Testing

### ☐ 8. Test R2 Upload (Admin Side)
- [ ] Go to `https://bofetoda-app.onrender.com/Admin_BOFETODA/login.php`
- [ ] Log in as President or Secretary
- [ ] Navigate to **Manage Announcements**
- [ ] Click **Create New Announcement**
- [ ] Fill in required fields:
  - [ ] Title: "Test R2 Upload"
  - [ ] Message: "Testing Cloudflare R2 integration"
  - [ ] Category: Event
  - [ ] Priority: Normal
- [ ] Upload 1-2 test images (JPG/PNG, < 5MB each)
- [ ] Click **Create**
- [ ] Verify success message appears

### ☐ 9. Verify R2 Storage
- [ ] Go back to Cloudflare R2 dashboard
- [ ] Open `bofetoda-announcements` bucket
- [ ] Look for files in `announcements/` folder
- [ ] Verify file name format: `announcement_X_TIMESTAMP_UNIQUEID.jpg`
- [ ] Check file size matches upload

### ☐ 10. Test Image Display (Driver Side)
- [ ] Log out of admin portal
- [ ] Go to `https://bofetoda-app.onrender.com/Driver_BOFETODA/driver-login.php`
- [ ] Log in as any driver
- [ ] Check homepage for announcement section
- [ ] Verify test announcement appears
- [ ] Verify images load correctly (no broken images)
- [ ] Click image to open lightbox
- [ ] Verify lightbox displays R2 image

### ☐ 11. Verify R2 URLs
- [ ] On driver homepage, right-click an announcement image
- [ ] Select **Inspect** (or F12)
- [ ] Check `<img>` tag `src` attribute
- [ ] Verify URL format: `https://pub-xxxxx.r2.dev/announcements/...`
- [ ] If URL is `../uploads/announcements/...`, R2 failed (check logs)

### ☐ 12. Check Render Logs
- [ ] Go to Render dashboard → **Logs** tab
- [ ] Look for R2 status messages:
  - ✅ Success: `[info] R2 upload successful: announcements/announcement_X...`
  - ⚠️ Fallback: `[warning] R2 upload failed, falling back to local storage`
  - ❌ Error: `[error] Failed to initialize R2 client`
- [ ] If errors found, proceed to Troubleshooting section

---

## Verification

### ☐ 13. Image Upload Test
- [ ] Upload 5 images to single announcement (max limit)
- [ ] Verify all 5 images appear in Cloudflare R2
- [ ] Verify all 5 images display on driver side
- [ ] Try uploading 6th image (should be rejected)

### ☐ 14. Image Delete Test
- [ ] Go to admin **Manage Announcements**
- [ ] Edit an announcement with images
- [ ] Click **Delete** on an image
- [ ] Verify image removed from display
- [ ] Check Cloudflare R2 bucket (file should be deleted)

### ☐ 15. Fallback Test (Optional)
- [ ] Temporarily remove `R2_ACCESS_KEY_ID` from Render
- [ ] Trigger redeploy
- [ ] Try uploading image (should fallback to local)
- [ ] Check logs for fallback message
- [ ] Re-add `R2_ACCESS_KEY_ID` and redeploy

### ☐ 16. Cross-Browser Test
- [ ] Test in Chrome/Edge
- [ ] Test in Firefox
- [ ] Test in Safari (if available)
- [ ] Test on mobile device

---

## Post-Deployment

### ☐ 17. Clean Up Test Data
- [ ] Delete test announcements from admin panel
- [ ] Verify files deleted from R2 bucket
- [ ] Create real announcements as needed

### ☐ 18. Monitor Usage
- [ ] Go to Cloudflare R2 dashboard
- [ ] Click **Metrics** tab
- [ ] Note current storage usage
- [ ] Set reminder to check monthly usage

### ☐ 19. Documentation
- [ ] Update internal documentation with R2 setup
- [ ] Share `R2_QUICK_REFERENCE.md` with team
- [ ] Document where credentials are stored (password manager)

### ☐ 20. Security Review
- [ ] Verify API token has minimal permissions (Object R/W only)
- [ ] Confirm credentials not committed to Git
- [ ] Verify `.gitignore` excludes `vendor/` and `.env`
- [ ] Check render environment variables are marked as "Secret"

---

## Troubleshooting

### Issue: "R2 credentials not configured"
**Steps:**
1. [ ] Check Render environment variables are set correctly
2. [ ] Verify variable names (exact spelling):
   - `R2_ACCESS_KEY_ID` (not `R2_ACCESS_KEY`)
   - `R2_SECRET_ACCESS_KEY`
   - `R2_ENDPOINT`
   - `R2_BUCKET_NAME`
   - `R2_PUBLIC_URL`
3. [ ] Redeploy after adding variables
4. [ ] Check deployment logs for errors

### Issue: Images upload but don't display
**Steps:**
1. [ ] Check bucket public access is enabled
2. [ ] Verify `R2_PUBLIC_URL` is correct
3. [ ] Test direct URL: `https://pub-xxxxx.r2.dev/announcements/test.jpg`
4. [ ] Check for CORS errors in browser console
5. [ ] Verify no trailing slash in `R2_PUBLIC_URL`

### Issue: "Failed to initialize R2 client"
**Steps:**
1. [ ] Verify `R2_ENDPOINT` format (no `https://` prefix)
2. [ ] Regenerate API token in Cloudflare
3. [ ] Update credentials in Render
4. [ ] Check token permissions include selected bucket
5. [ ] Redeploy application

### Issue: Uploads work locally but not on Render
**Steps:**
1. [ ] Verify `composer.json` exists in repository
2. [ ] Check `composer install` ran in deployment logs
3. [ ] Verify `vendor/autoload.php` is created
4. [ ] Check PHP version compatibility (>= 7.4)
5. [ ] Review Render build logs for errors

---

## Rollback Plan

If R2 integration causes issues:

### ☐ Emergency Rollback
1. [ ] Remove all R2 environment variables from Render
2. [ ] Redeploy (system will use local storage fallback)
3. [ ] Images will save to `/uploads/announcements/` (ephemeral)
4. [ ] Fix R2 issue, then re-add environment variables

### ☐ Revert Code Changes
```bash
# If needed, revert to previous version
git revert 2f59343
git push origin main
```

---

## Success Criteria

✅ **Deployment is successful when:**
- [ ] Images upload to Cloudflare R2 bucket
- [ ] Images display correctly on driver homepage
- [ ] Image URLs use R2 public URL (not local path)
- [ ] Image delete removes files from R2
- [ ] No errors in Render logs
- [ ] All browsers display images correctly
- [ ] Lightbox shows R2 images
- [ ] Free tier usage is within limits

---

## Next Steps After Successful Deployment

1. [ ] **Monitor for 24 hours**: Check for any errors or issues
2. [ ] **Review costs**: Ensure staying within free tier
3. [ ] **Train users**: Show admins how to create announcements
4. [ ] **Set up alerts**: Configure Cloudflare usage alerts
5. [ ] **Schedule review**: Check R2 storage monthly
6. [ ] **Plan backups**: Consider periodic R2 bucket backups
7. [ ] **Rotate tokens**: Set calendar reminder for 6 months

---

## Support Contacts

- **Cloudflare Support**: https://dash.cloudflare.com/?to=/:account/support
- **Render Support**: https://dashboard.render.com/support
- **GitHub Issues**: https://github.com/juddrussel/bofetoda-toda/issues

---

## Completion

**Deployment Date**: _______________

**Deployed By**: _______________

**R2 Bucket URL**: _______________

**Status**: ☐ Success  ☐ Partial  ☐ Failed

**Notes**:
```
[Add any deployment notes, issues encountered, or special configurations here]
```

---

**Checklist Version**: 1.0.0  
**Last Updated**: October 23, 2026
