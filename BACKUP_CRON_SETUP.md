# Database Backup System - Cron Setup Guide

This guide explains how to set up automated daily backups for the BOFETODA database system.

## Overview

The backup system consists of two components:
1. **Manual Backups** - President can download backups on-demand via the admin panel
2. **Automated Backups** - Daily backups run via cron and are stored in Cloudflare R2

## Automated Backup Setup

### Prerequisites

- Server with cron access
- `mysqldump` command available
- Cloudflare R2 configured (already done if announcements work)
- BACKUP_CRON_KEY environment variable set (optional but recommended)

### Step 1: Set Security Key (Recommended)

Add to your `.env` file or environment variables:

```bash
BACKUP_CRON_KEY=your-random-secure-key-here
```

Generate a random key:
```bash
openssl rand -hex 32
```

### Step 2: Add Cron Job

Edit your crontab:
```bash
crontab -e
```

Add one of these lines:

**Option A: With security key (recommended)**
```bash
0 0 * * * curl -X POST "https://your-domain.com/Admin_BOFETODA/api-automated-backup.php?key=YOUR_SECRET_KEY"
```

**Option B: Without security key**
```bash
0 0 * * * curl -X POST "https://your-domain.com/Admin_BOFETODA/api-automated-backup.php"
```

**Option C: Using wget instead of curl**
```bash
0 0 * * * wget -q -O - "https://your-domain.com/Admin_BOFETODA/api-automated-backup.php?key=YOUR_SECRET_KEY"
```

### Step 3: Test the Backup

Run manually to test:
```bash
curl -X POST "https://your-domain.com/Admin_BOFETODA/api-automated-backup.php?key=YOUR_SECRET_KEY"
```

Expected response:
```json
{
  "success": true,
  "message": "Backup completed successfully",
  "filename": "backup_2026-10-25_120000.sql.gz",
  "size": "1.2 MB",
  "r2_key": "backups/2026-10-25/backup_2026-10-25_120000.sql.gz",
  "timestamp": "2026-10-25 12:00:00",
  "old_backups_deleted": 0
}
```

### Step 4: Verify Backup Storage

1. Login as President
2. Go to **Database Backup** in sidebar
3. Check that the backup appears in the "Available Backups" table
4. Try downloading it to verify

## Backup Retention

- Automated backups are kept for **7 days**
- Backups older than 7 days are automatically deleted during each backup run
- Manual backups are downloaded directly (not stored in R2)

## Cron Schedule Examples

```bash
# Daily at midnight
0 0 * * * curl ...

# Daily at 2:00 AM
0 2 * * * curl ...

# Twice daily (midnight and noon)
0 0,12 * * * curl ...

# Every 6 hours
0 */6 * * * curl ...
```

## Troubleshooting

### Check Cron Logs

```bash
# View cron logs (Ubuntu/Debian)
grep CRON /var/log/syslog

# View cron logs (CentOS/RHEL)
grep CRON /var/log/cron
```

### Test Manually

```bash
# Test without key
curl -X POST "https://your-domain.com/Admin_BOFETODA/api-automated-backup.php"

# Test with key
curl -X POST "https://your-domain.com/Admin_BOFETODA/api-automated-backup.php?key=YOUR_KEY"

# View detailed response
curl -v -X POST "https://your-domain.com/Admin_BOFETODA/api-automated-backup.php?key=YOUR_KEY"
```

### Common Issues

**Issue:** "Invalid authentication key"
- **Solution:** Check that BACKUP_CRON_KEY environment variable matches the key in URL

**Issue:** "R2 storage not configured"
- **Solution:** Verify R2 credentials in `.env`:
  - `R2_ACCESS_KEY_ID`
  - `R2_SECRET_ACCESS_KEY`
  - `R2_ENDPOINT`
  - `R2_BUCKET_NAME`

**Issue:** "mysqldump failed"
- **Solution:** 
  - Verify mysqldump is installed: `which mysqldump`
  - Check database credentials in `.env`
  - Test manually: `mysqldump --host=... --user=... --password=... dbname`

**Issue:** No backups appear in list
- **Solution:**
  - Check R2 bucket in Cloudflare dashboard
  - Verify R2_PUBLIC_URL is set correctly
  - Check server error logs: `tail -f /var/log/apache2/error.log` or `tail -f /var/log/nginx/error.log`

## Security Best Practices

1. **Use HTTPS** - Always use HTTPS for cron endpoints
2. **Set BACKUP_CRON_KEY** - Use a strong random key
3. **Restrict IP** - Optionally whitelist cron server IP in `api-automated-backup.php`
4. **Monitor logs** - Regularly check backup logs for failures
5. **Test restores** - Periodically test that backups can be restored

## Backup File Structure

```
R2 Bucket (bofetoda-announcements)
└── backups/
    ├── 2026-10-25/
    │   ├── backup_2026-10-25_000000.sql.gz
    │   └── backup_2026-10-25_120000.sql.gz
    ├── 2026-10-24/
    │   └── backup_2026-10-24_000000.sql.gz
    └── 2026-10-23/
        └── backup_2026-10-23_000000.sql.gz
```

## Restoring from Backup

### Step 1: Download Backup

1. Login as President
2. Go to **Database Backup**
3. Download the backup file

### Step 2: Extract Backup

```bash
gunzip backup_2026-10-25_000000.sql.gz
```

### Step 3: Restore Database

```bash
mysql -h hostname -u username -p database_name < backup_2026-10-25_000000.sql
```

**⚠️ Warning:** Restoring will **overwrite** the current database!

### Step 4: Verify

- Check that tables exist
- Verify data integrity
- Test application functionality

## Monitoring

### Check Last Backup

```bash
curl "https://your-domain.com/Admin_BOFETODA/api-list-backups.php" \
  -H "Cookie: PHPSESSID=your-session-id"
```

### Email Notifications (Optional)

Add to cron job:
```bash
0 0 * * * curl -X POST "https://your-domain.com/api-automated-backup.php?key=KEY" || echo "Backup failed" | mail -s "Backup Alert" admin@example.com
```

## Support

For issues or questions:
1. Check application logs
2. Check cron logs
3. Test manually with curl
4. Verify R2 configuration
5. Contact system administrator

## Version History

- **v1.0** (2026-10-25) - Initial backup system with manual and automated backups
