# Web Push Notification System - User Guide
## BOFETODA TODA Management System

## Overview

The notification system provides real-time alerts to drivers and officers through:
- **Web Push Notifications** - Browser notifications even when the site is closed
- **In-App Toast Notifications** - Small popups that appear while using the system
- **Application Deadline Reminders** - Automatic reminders at 7, 3, and 1 days before deadline

---

## For Drivers

### What Notifications You'll Receive

1. **Message Replies** - When an officer replies to your message
2. **Application Status Updates** - When your application is approved or rejected
3. **Deadline Reminders** - Reminders to submit your application (7, 3, 1 day before deadline)
4. **Submission Confirmations** - Acknowledgment that your application was received

### Enabling Notifications

#### Automatic Prompt
When you first log in, you'll see a browser prompt asking:
> "Allow notifications from this site?"

Click **"Allow"** to enable notifications.

#### Manual Setup
If you missed the prompt or declined:
1. Go to your **Profile** page
2. Look for the **Notifications** section
3. Click **"Enable Notifications"**
4. Allow the browser permission when prompted

#### If Blocked
If you accidentally blocked notifications:
1. Click the 🔒 or ⓘ icon in your browser's address bar
2. Find "Notifications" in the permissions
3. Change from "Block" to "Ask" or "Allow"
4. Refresh the page

---

## For Officers

### What Notifications You'll Receive

1. **New Driver Messages** - When a driver sends you a message
2. **Deadline Reminders** - Status of pending applications and submission rates
3. **System Alerts** - Important system updates and announcements

### Managing Notifications

Officers receive the same setup process as drivers. Enable notifications to stay informed about driver communications and important deadlines.

---

## Notification Types Explained

### 1. Message Notifications
**When:** Officer replies to driver, or driver messages officers
**Example:** "New message from Officer Juan"
**Action:** Click to open the conversation

### 2. Application Status Notifications
**When:** Application is approved or rejected
**Example:** 
- ✅ "Application Approved - Your renewed permit is now issued"
- ❌ "Application Update - Your application was rejected. Reason: [details]"
**Action:** Click to view application details

### 3. Deadline Reminders

#### For Drivers Who Haven't Submitted:
- **7 days:** "Application deadline is in 7 days. Submit your renewal application soon!"
- **3 days:** "Urgent: Only 3 days left to submit your application!"
- **1 day:** "Final reminder: Application deadline is tomorrow! Submit now!"

#### For Drivers Who Have Submitted:
- "Your application has been submitted. Deadline is in X days. Check your status anytime."

#### For Officers:
- **7 days:** "X drivers haven't submitted yet. Y applications pending review."
- **3 days:** "Reminder: Review pending applications. Deadline in 3 days."
- **1 day:** "Urgent: Application deadline tomorrow! X pending applications need review."

---

## Testing the Notification System

### For Administrators

#### Test 1: Web Push Setup
1. Open browser console (F12)
2. Look for messages:
   - `[Service Worker] Loaded version: 1.0.0`
   - `[SW Registration] Success`
   - `[Push Subscribe] Subscribed`
3. Check database: `SELECT * FROM push_subscriptions` - should show your subscription

#### Test 2: Message Notifications
1. As officer, reply to a driver message
2. Driver should receive:
   - Browser push notification
   - In-app notification in notifications page
   - Toast notification if online
3. Check logs: Look for "Notification sent to driver"

#### Test 3: Application Status Notifications
1. Approve or reject an application
2. Driver should receive notification immediately
3. Check database: `SELECT * FROM notifications WHERE notification_type = 'status_change'`

#### Test 4: Deadline Reminders
**Manual Test:**
1. Create announcement with deadline 7 days from now
2. Visit any page to trigger scheduler
3. Check logs: Look for "Reminder scheduler" messages
4. Check database: `SELECT * FROM reminder_log`

**Date Simulation:**
- Temporarily modify announcement deadline_date to test different thresholds
- Clear reminder cache: Delete `uploads/.reminder_last_run`
- Reload page between 7:30-8:30 AM to trigger reminders

#### Test 5: Toast Notifications
Open browser console and run:
```javascript
// Test success toast
Toast.success('Test Title', 'This is a success message');

// Test error toast
Toast.error('Error', 'Something went wrong');

// Test warning toast
Toast.warning('Warning', 'Please be careful');

// Test info toast
Toast.info('Info', 'Just letting you know');
```

---

## Troubleshooting

### Notifications Not Appearing

**Check 1: Browser Support**
- Chrome/Edge: ✅ Fully supported
- Firefox: ✅ Fully supported
- Safari: ⚠️ Requires iOS 16.4+ or macOS 13+

**Check 2: Permission Status**
```javascript
// Run in browser console
console.log('Permission:', Notification.permission);
// Should show: "granted"
```

**Check 3: Service Worker**
```javascript
// Run in browser console
navigator.serviceWorker.getRegistration().then(reg => {
  console.log('Service Worker:', reg ? 'Registered' : 'Not registered');
});
```

**Check 4: Subscription Status**
```javascript
// Run in browser console
console.log('Subscribed:', localStorage.getItem('pushSubscribed'));
// Should show: "true"
```

**Check 5: Server Logs**
Look for these in error logs:
- "Push notification sent: X success, Y failures"
- "Notification sent to driver/officer"
- Any errors related to WebPush or VAPID

### Common Issues

**Issue:** "Push notifications not enabled - VAPID keys not configured"
**Solution:** 
1. Run `php generate_vapid_keys.php`
2. Add keys to environment variables on Render
3. Restart service

**Issue:** "WebPush library not loaded"
**Solution:**
```bash
composer install
```

**Issue:** Notifications work but images don't load
**Solution:** Check that logo files exist:
- `/Admin_BOFETODA/LOGO_TODA.png`
- `/Admin_BOFETODA/LOGO_BOCAUE.png`

**Issue:** Reminders not sending
**Solution:**
1. Check time: Scheduler runs 7:30-8:30 AM Manila time
2. Check cache file: `uploads/.reminder_last_run`
3. Delete cache file to force re-run
4. Check active announcements: `SELECT * FROM announcements WHERE status = 'OPEN'`

---

## Database Queries for Monitoring

### Check Push Subscriptions
```sql
-- Total subscribers by type
SELECT user_type, COUNT(*) as count 
FROM push_subscriptions 
GROUP BY user_type;

-- Recent subscriptions
SELECT user_id, user_type, subscribed_at 
FROM push_subscriptions 
ORDER BY subscribed_at DESC 
LIMIT 10;
```

### Check Notifications Sent
```sql
-- Recent notifications by type
SELECT notification_type, COUNT(*) as count, MAX(created_at) as last_sent
FROM notifications 
GROUP BY notification_type;

-- Unread notifications
SELECT driver_id, COUNT(*) as unread_count
FROM notifications 
WHERE is_read = 0 
GROUP BY driver_id;
```

### Check Reminder History
```sql
-- Reminders sent by type
SELECT announcement_id, reminder_type, sent_at, recipient_count
FROM reminder_log 
ORDER BY sent_at DESC;

-- Upcoming deadlines
SELECT title, deadline_date, DATEDIFF(deadline_date, CURDATE()) as days_remaining
FROM announcements 
WHERE status = 'OPEN' 
AND deadline_date >= CURDATE()
ORDER BY deadline_date ASC;
```

---

## Performance Notes

- **Scheduler overhead:** ~100ms per page load (skipped if already run today)
- **Push notification sending:** ~200-500ms per batch of subscriptions
- **Toast notifications:** No performance impact (client-side only)
- **Database queries:** All queries use proper indexes for fast execution

---

## Security

- **VAPID keys:** Private key must be kept secret (never commit to git)
- **Endpoint validation:** All endpoints check user authentication
- **Subscription cleanup:** Expired subscriptions automatically removed
- **XSS protection:** All notification content is sanitized

---

## Browser Notification Examples

### Desktop (Windows/Mac/Linux)
- Appears in system notification center
- Shows app icon, title, and message
- Clicking opens browser to specified URL
- Stays visible until dismissed or auto-timeout

### Mobile (Android)
- Appears in notification drawer
- Works even when browser is closed
- Supports vibration patterns
- Clicking opens app/browser

### Mobile (iOS)
- Requires iOS 16.4+ and Safari
- Must add to home screen for full support
- Limited compared to Android

---

## API Reference

### JavaScript API

```javascript
// Subscribe to notifications
await window.subscribeToPush();

// Unsubscribe
await window.unsubscribeFromPush();

// Check if should prompt
const shouldPrompt = window.shouldPromptForPush();

// Show toast
Toast.success('Title', 'Message', 5000);
Toast.error('Title', 'Message');
Toast.warning('Title', 'Message');
Toast.info('Title', 'Message');

// Update notification settings UI
window.updateNotificationStatus();
```

### PHP API

```php
// Send push notification to user
send_push_notification($userId, $userType, $title, $body, $url, $options);

// Create in-app notification
create_in_app_notification($userId, $userType, $title, $message, $notificationType);

// Unified notification (push + in-app)
trigger_notification($userId, $userType, $notificationType, $data);

// Broadcast to all officers
notify_all_officers($title, $message, $url, $notificationType);

// Broadcast to all drivers
notify_all_drivers($title, $message, $url, $notificationType, $createInApp);
```

---

## Maintenance

### Daily Tasks
- Monitor error logs for failed notifications
- Check reminder_log to ensure reminders are sending

### Weekly Tasks
- Review push_subscriptions for growth/churn
- Check for expired subscriptions (cleaned automatically)

### Monthly Tasks
- Review notification engagement metrics
- Update notification messages if needed
- Test on different browsers/devices

---

## Support

For technical issues:
1. Check browser console for errors
2. Check server error logs
3. Verify VAPID keys are configured
4. Test with `php generate_vapid_keys.php`

For feature requests or bugs:
- Document the issue with screenshots
- Include browser and OS version
- Note if issue is driver-side or officer-side
