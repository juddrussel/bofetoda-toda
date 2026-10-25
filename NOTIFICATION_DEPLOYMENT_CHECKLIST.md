# Notification System Deployment Checklist
## BOFETODA TODA Management System

Use this checklist to deploy the web push notification and reminder system.

---

## Pre-Deployment

### 1. Install Dependencies
```bash
composer install
```
**Verify:** Check that `vendor/minishlink/web-push` directory exists

### 2. Run Database Migration
```bash
# Access your deployment URL
https://your-app.onrender.com/run_push_notification_migration.php
```
**Verify:** Check that these tables exist:
- ✅ `push_subscriptions`
- ✅ `reminder_log`
- ✅ `notifications` (with new columns: `notification_type`, `metadata`)

**After verification, delete the migration file:**
```bash
rm run_push_notification_migration.php
```

### 3. Generate VAPID Keys
```bash
# Access your deployment URL
https://your-app.onrender.com/generate_vapid_keys.php
```
**Copy the keys that are displayed** - you'll need them in the next step!

**After copying keys, delete the generator file:**
```bash
rm generate_vapid_keys.php
```

---

## Render Configuration

### 4. Set Environment Variables

In Render Dashboard → Your Service → Environment:

Add these 3 variables:

```
VAPID_PUBLIC_KEY=BHxyz123...  (your public key)
VAPID_PRIVATE_KEY=abc456...   (your private key - keep secret!)
VAPID_SUBJECT=mailto:admin@bofetoda.com
```

**Important:** 
- Replace with YOUR actual keys from step 3
- Change the email to your actual admin email
- Click **"Save Changes"**
- Wait for automatic redeployment

### 5. Verify Configuration

After redeployment, check logs for:
```
✅ "VAPID keys configured"
✅ "[Service Worker] Loaded version: 1.0.0"
✅ "[Push Init] Initialization complete"
```

❌ If you see "VAPID keys not configured" - environment variables weren't set correctly

---

## Testing

### 6. Test Service Worker Registration

**As any user (driver or officer):**

1. Log in to the system
2. Open browser console (F12)
3. Look for these messages:
```
[Service Worker] Installing version: 1.0.0
[Service Worker] Activated version: 1.0.0
[SW Registration] Success
[Push Init] Initialization complete
```

4. Check service worker is registered:
```javascript
navigator.serviceWorker.getRegistrations().then(regs => console.log(regs));
```
Should show 1 registration

### 7. Test Push Subscription

**Continue in browser console:**

1. You should see permission prompt automatically (or click "Allow" if prompted)
2. After allowing, check console for:
```
[Push Subscribe] Permission granted
[Push Subscribe] Subscribed: [endpoint]
[Push Subscribe] Subscription saved to server
```

3. Verify in database:
```sql
SELECT * FROM push_subscriptions LIMIT 5;
```
Should show your subscription

### 8. Test Toast Notifications

**In browser console, run:**
```javascript
Toast.success('Test Success', 'This is a test success message');
Toast.error('Test Error', 'This is a test error message');
Toast.warning('Test Warning', 'This is a test warning message');
Toast.info('Test Info', 'This is a test info message');
```

**Expected:** Toasts appear in top-right corner, auto-dismiss after 5 seconds

### 9. Test Message Notifications

**As Officer:**
1. Go to Driver Inbox
2. Open conversation with any driver
3. Send a test message: "Testing notifications"

**As Driver (in different browser/incognito):**
1. Wait 5-10 seconds
2. **Expected:**
   - ✅ Browser push notification appears (even if tab is closed!)
   - ✅ In-app notification in Notifications page
   - ✅ Badge count updates
   - ✅ Toast notification if online

**Check logs for:**
```
✅ "Notification sent to driver [ID] for officer reply"
✅ "Push notification sent: 1 success, 0 failures"
```

### 10. Test Application Status Notifications

**As Officer:**
1. Go to Applications list
2. Approve or reject a test application

**As Driver:**
1. **Expected:**
   - ✅ Browser push notification: "Application Approved" or "Application Update"
   - ✅ In-app notification
   - ✅ Toast if online

**Check logs for:**
```
✅ "Approval notification sent for application [APP_NO]"
```

### 11. Test Deadline Reminders (Manual)

**Setup:**
1. Create a test announcement with deadline 7 days from now
2. Or temporarily change an existing announcement's deadline_date

**Trigger:**
1. Delete cache file: `rm uploads/.reminder_last_run`
2. Visit any page between 7:30-8:30 AM Manila time
3. OR temporarily modify `check_and_send_reminders()` to remove time restriction

**Expected:**
- ✅ Drivers who haven't submitted get "Application deadline is in 7 days" notification
- ✅ Drivers who submitted get acknowledgment notification
- ✅ Officers get summary notification

**Check logs for:**
```
✅ "Reminder scheduler executed"
✅ "Notification sent to X drivers"
✅ "Notification sent to X officers"
```

**Check database:**
```sql
SELECT * FROM reminder_log ORDER BY sent_at DESC LIMIT 5;
```

### 12. Test Notification Settings UI

**Add to driver profile page** (optional):

```php
<div class="notification-settings">
    <h3>Push Notifications</h3>
    <p>Status: <span data-notification-status></span></p>
    <p><span data-notification-info></span></p>
    <button data-notification-toggle>Toggle Notifications</button>
</div>
```

**Expected:**
- Status shows "Enabled", "Disabled", or "Blocked"
- Button allows toggling on/off
- Info text explains current state

---

## Post-Deployment Monitoring

### Day 1: Check Logs

Monitor for these:
```
✅ Users subscribing: "[Push Subscribe] Subscription saved to server"
✅ Notifications sending: "Push notification sent: X success"
❌ No errors: "WebPush library not loaded" or "VAPID keys not configured"
```

### Week 1: Check Database

```sql
-- Subscription growth
SELECT DATE(subscribed_at) as date, COUNT(*) as new_subs
FROM push_subscriptions 
GROUP BY DATE(subscribed_at)
ORDER BY date DESC;

-- Notifications sent
SELECT notification_type, COUNT(*) as count
FROM notifications
WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY notification_type;

-- Reminders sent
SELECT * FROM reminder_log ORDER BY sent_at DESC LIMIT 10;
```

### Month 1: Performance Check

- Average page load time (should not increase significantly)
- Notification delivery success rate (check `push_subscriptions` for removed expired ones)
- User engagement (check unread counts, click-through rates)

---

## Rollback Plan

If issues occur:

### Quick Disable (No Code Changes)
```sql
-- Remove all subscriptions (stops all push notifications)
TRUNCATE TABLE push_subscriptions;
```

### Full Rollback
1. Remove environment variables from Render
2. Service worker will fail to initialize (gracefully)
3. System continues working without notifications

---

## Common Deployment Issues

### Issue 1: "Composer dependencies not installed"
```bash
# In Render, add to Build Command:
composer install
```

### Issue 2: "VAPID keys not configured"
- Check Render environment variables are set
- Check for typos in variable names
- Restart service after adding variables

### Issue 3: "Service worker not registering"
- Check HTTPS is enabled (required for service workers)
- Check `/sw.js` and `/register-sw.js` are accessible
- Clear browser cache and hard reload (Ctrl+Shift+R)

### Issue 4: "Permission denied immediately"
- User previously blocked notifications
- Instruct user to reset permissions in browser settings
- Or use different browser/incognito

### Issue 5: "Reminders not sending"
- Check current time is 7:30-8:30 AM Manila time
- Check `uploads/.reminder_last_run` file (delete to reset)
- Check active announcements exist in database
- Check logs for scheduler errors

### Issue 6: "Notifications work but images don't show"
- Verify logo files exist:
  - `/Admin_BOFETODA/LOGO_TODA.png`
  - `/Admin_BOFETODA/LOGO_BOCAUE.png`
- Check file permissions (readable)

---

## Security Checklist

- ✅ Private VAPID key is in environment variables (not in code)
- ✅ VAPID keys not committed to git
- ✅ Migration and key generator files deleted after use
- ✅ All notification endpoints require authentication
- ✅ XSS protection on all notification content
- ✅ Expired subscriptions automatically cleaned up

---

## Success Criteria

Your deployment is successful when:

- ✅ Users can enable notifications on first login
- ✅ Officers receive notifications when drivers message them
- ✅ Drivers receive notifications for application status changes
- ✅ Drivers receive notifications for message replies
- ✅ Deadline reminders send automatically at 7/3/1 days before deadline
- ✅ Toast notifications appear for online users
- ✅ No errors in server logs related to notifications
- ✅ Browser console shows successful service worker registration

---

## Next Steps After Deployment

1. **Announce to users** - Let drivers and officers know about the new notification feature
2. **Monitor adoption** - Track how many users enable notifications
3. **Gather feedback** - Ask users about notification frequency and relevance
4. **Optimize timing** - Adjust reminder schedule if needed based on user behavior
5. **Expand** - Consider adding more notification types (new announcements, document uploads, etc.)

---

## Support Contacts

For technical issues during deployment:
- Check this checklist first
- Review logs in Render dashboard
- Check browser console for client-side errors
- Verify database tables and data

Document any issues encountered for future reference.
