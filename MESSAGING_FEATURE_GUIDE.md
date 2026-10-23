# Driver-to-Officer Messaging System
## Feature Documentation

### Overview
This messaging system enables drivers to communicate with officers in a helpdesk-style format. Drivers send messages to all officers as a group, and any officer can view and reply to driver messages. The system supports file attachments, real-time badge notifications, and maintains full conversation history.

---

## Key Features

### 1. **Helpdesk-Style Messaging**
- Drivers message officers as a **group** (not individual officers)
- All officers can see all driver messages
- Any officer can reply to any driver conversation
- Officers are identified by name when they reply

### 2. **WhatsApp-Style Conversation View**
- Chronological message display (oldest first)
- Driver messages appear on the **right side** with **blue background**
- Officer messages appear on the **left side** with **gray background**
- Auto-scroll to bottom on page load
- Each message shows sender name and timestamp

### 3. **File Attachments**
- **Maximum**: 3 files per message
- **Size limit**: 5MB per file
- **Allowed formats**: PDF, DOC, DOCX, JPG, PNG
- **Storage**: Cloudflare R2 (with local fallback)
- **Folder structure**: `messages/{driver_id}/`
- File validation includes MIME type checking

### 4. **Badge Notifications**
- **Driver side**: Shows count of **unreplied messages** (red badge)
  - Badge appears when officer hasn't replied yet (`has_reply=0`)
  - Disappears when officer sends first reply
- **Officer side**: Shows count of **unread messages** (red badge)
  - Badge appears for messages not yet viewed (`is_read=0`)
  - Updates when officer opens conversation
- **Real-time updates**: Polls every 30 seconds
- Displays "9+" for counts greater than 9

### 5. **Conversation Management**
- **Full history**: All messages preserved indefinitely
- **Status indicators**:
  - "Needs Reply" badge for unreplied driver messages
  - "Replied" badge when officer has responded
- **Read receipts**: Messages marked as read when officer views conversation
- **Reply tracking**: `has_reply` flag set to 1 when officer responds

### 6. **Security Features**
- Authentication required for all actions
- **Driver access control**: Can only view/download their own attachments
- **Officer access control**: Can view/download all attachments
- Access logging for attachment downloads
- SQL injection protection via prepared statements
- XSS protection via htmlspecialchars escaping

---

## Database Schema

### Table: `driver_messages`
```sql
CREATE TABLE driver_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id VARCHAR(10) NOT NULL,
    sender_type ENUM('driver', 'officer') NOT NULL,
    sender_id VARCHAR(10) NOT NULL,
    message_text TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    has_reply TINYINT(1) DEFAULT 0,
    
    INDEX idx_driver_messages (driver_id, sent_at),
    INDEX idx_unread_messages (driver_id, is_read, sender_type),
    INDEX idx_unreplied_messages (driver_id, has_reply, sender_type)
);
```

### Table: `message_attachments`
```sql
CREATE TABLE message_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    r2_key VARCHAR(500),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES driver_messages(id) ON DELETE CASCADE,
    INDEX idx_message_attachments (message_id)
);
```

---

## File Structure

### Driver Portal Files
```
Driver_BOFETODA/
├── driver-messages.php          # Main messaging interface
├── download-attachment.php      # Secure file download handler
├── api-message-count.php        # Badge counter API endpoint
└── driver-database.php          # Messaging functions (updated)
```

### Admin Portal Files
```
Admin_BOFETODA/
├── driver_inbox.php             # List of drivers with messages
├── driver_conversation.php      # Conversation view and reply interface
├── download_message_attachment.php  # Secure file download handler
├── api-message-count.php        # Badge counter API endpoint
└── config.php                   # Core functions (updated)
```

### Database & Migration
```
add_driver_messaging.sql         # Database schema
run_messaging_migration.php      # Web-based migration runner
```

---

## User Interface

### Driver Side Navigation
- **Location**: Bottom navigation bar
- **Position**: Between "Submit" and "Notifications"
- **Icon**: Message bubble
- **Badge**: Shows unreplied message count

### Officer Side Navigation
- **Location**: Sidebar
- **Position**: Between "Driver List" and "Reports"
- **Label**: "Driver Inbox"
- **Badge**: Shows unread message count

---

## Key Functions

### Admin Functions (config.php)
```php
// Core messaging
get_driver_messages($driverId)           // Get all messages for a driver with attachments
send_driver_message($driverId, $senderType, $senderId, $messageText, $files)
mark_driver_messages_as_read($driverId)
update_message_reply_status($driverId)

// Badge counters
get_unread_message_count()               // Officer badge: unread messages
get_driver_unreplied_count($driverId)    // Driver badge: unreplied messages

// Inbox helpers
get_drivers_with_messages()              // Get list of drivers with messages
get_last_message_preview($driverId)
get_driver_info($driverId)
driver_has_messages($driverId)

// Formatting
format_relative_time($timestamp)         // "2 hours ago", "Just now"
get_file_icon_admin($filename)
format_file_size_admin($bytes)
```

### Driver Functions (driver-database.php)
```php
get_my_messages($driverId)               // Get all messages for logged-in driver
send_message_to_officers($driverId, $messageText, $files)
get_my_unread_reply_count($driverId)     // For badge counter
mark_messages_as_read($driverId)
has_messages($driverId)

// Formatting
format_message_time($timestamp)
get_file_icon($filename)
format_file_size($bytes)
```

### R2 Storage Functions (r2-storage.php)
```php
validate_message_attachment($file)
upload_message_attachment_r2($driverId, $messageId, $file)
delete_message_attachment_r2($r2Key)
get_message_attachment_url($r2Key)
```

---

## Usage Workflow

### Driver Sends Message
1. Driver navigates to "Messages" from bottom navigation
2. Types message in text area
3. Optionally attaches up to 3 files (PDF, DOC, DOCX, JPG, PNG)
4. Clicks "Send Message"
5. System validates files and uploads to R2
6. Message saved to database with `sender_type='driver'`
7. Badge appears for driver showing "unreplied" status
8. Badge appears for all officers showing "unread" count

### Officer Views and Replies
1. Officer sees badge on "Driver Inbox" in sidebar
2. Clicks "Driver Inbox" to see list of drivers with messages
3. List shows:
   - Driver avatar with initials
   - Last message preview
   - Timestamp (relative, e.g., "2 hours ago")
   - Unread badge (if unread)
   - "Needs Reply" badge (if unreplied)
4. Officer clicks driver to open conversation
5. All messages marked as `is_read=1` automatically
6. Officer views full conversation history
7. Officer types reply and optionally attaches files
8. Clicks "Send Reply"
9. System saves message with `sender_type='officer'` and `sender_id={officer_id}`
10. System updates `has_reply=1` for driver's messages
11. Driver's "unreplied" badge disappears

### Real-Time Updates
- JavaScript polls API endpoints every 30 seconds
- Driver badge updates when officer replies
- Officer badge updates when driver sends new message
- Badges dynamically created/updated/removed

---

## Installation & Setup

### Step 1: Database Migration
**Option A: Web-Based (Recommended)**
1. Navigate to `run_messaging_migration.php` in browser
2. Click "Run Migration"
3. Verify success message

**Option B: Manual SQL**
1. Execute `add_driver_messaging.sql` in MySQL client
2. Verify tables created: `driver_messages`, `message_attachments`

### Step 2: R2 Configuration (Already Done)
The system uses existing R2 configuration from `r2-storage.php`:
- R2_ENDPOINT
- R2_BUCKET_NAME
- R2_ACCESS_KEY_ID
- R2_SECRET_ACCESS_KEY

Files are stored in `messages/{driver_id}/` folder.

### Step 3: Verify Permissions
Ensure web server has write permissions to:
- `uploads/messages/` (local fallback)

### Step 4: Testing
1. Log in as a driver
2. Navigate to "Messages"
3. Send a test message with attachment
4. Verify badge appears on driver navigation
5. Log in as officer
6. Verify badge appears on admin sidebar
7. Open "Driver Inbox"
8. Verify driver appears in list
9. Open conversation
10. Send reply with attachment
11. Verify driver's badge updates
12. Test file downloads

---

## Troubleshooting

### Issue: Badges Not Updating
**Cause**: JavaScript polling failed or API endpoint error
**Fix**: 
- Check browser console for errors
- Verify API endpoints are accessible:
  - `Driver_BOFETODA/api-message-count.php`
  - `Admin_BOFETODA/api-message-count.php`
- Check session is active

### Issue: File Upload Fails
**Cause**: R2 connection error or file validation failure
**Fix**:
- Verify R2 credentials in `r2-storage.php`
- Check file meets requirements (size, type)
- Check PHP upload limits (`upload_max_filesize`, `post_max_size`)
- System falls back to local storage if R2 fails
- Check `uploads/messages/` permissions

### Issue: Attachment Download 404
**Cause**: File not found in R2 or local storage
**Fix**:
- Verify R2_KEY or file_path in database
- Check R2 bucket permissions
- For local storage, verify file exists at path

### Issue: "Needs Reply" Badge Persists
**Cause**: `has_reply` flag not updated
**Fix**:
- Verify `update_message_reply_status()` called after officer reply
- Check database: `SELECT has_reply FROM driver_messages WHERE driver_id = ?`
- Should be `1` after officer reply

### Issue: Messages Not Appearing
**Cause**: Database query error or incorrect driver_id
**Fix**:
- Check browser console and PHP error logs
- Verify driver is logged in with correct session
- Check database: `SELECT * FROM driver_messages WHERE driver_id = ?`

---

## Technical Notes

### Badge Count Logic

**Driver Badge (Unreplied Count)**:
```sql
SELECT COUNT(DISTINCT driver_id) 
FROM driver_messages 
WHERE driver_id = ? 
  AND sender_type = 'driver' 
  AND has_reply = 0
```

**Officer Badge (Unread Count)**:
```sql
SELECT COUNT(*) 
FROM driver_messages 
WHERE sender_type = 'driver' 
  AND is_read = 0
```

### File Upload Process
1. Validate file (extension, size, MIME type)
2. Generate unique filename with timestamp
3. Attempt R2 upload via AWS SDK
4. If R2 fails, save to local `uploads/messages/{driver_id}/`
5. Save metadata to `message_attachments` table
6. Associate with message via `message_id` foreign key

### Security Considerations
- All inputs escaped with `htmlspecialchars()` or PDO prepared statements
- File MIME types validated on server side
- Authentication required for all messaging endpoints
- Download handlers verify ownership/permissions
- XSS protection on message text display
- SQL injection protection via PDO
- Access logging for audit trail

### Performance Optimizations
- Indexed columns: `driver_id`, `sent_at`, `is_read`, `has_reply`
- Badge counts use efficient COUNT queries with indexes
- API endpoints return JSON only (no HTML rendering)
- Real-time polling limited to 30-second intervals
- Attachments lazy-loaded (not in message list queries)

---

## Future Enhancements (Not Implemented)

- Push notifications via WebSocket
- Message search functionality
- Bulk message operations
- Message archiving/deletion
- Typing indicators
- Message reactions/emojis
- Draft messages
- Message templates
- Auto-responses
- Read receipts with timestamps
- Conversation export (PDF/CSV)

---

## Support & Maintenance

### Regular Maintenance Tasks
1. **Monitor storage**: Check R2 bucket size and costs
2. **Database cleanup**: Consider archiving old messages (optional)
3. **Audit logs**: Review attachment download logs periodically
4. **Performance**: Monitor badge count query performance
5. **Backups**: Include `driver_messages` and `message_attachments` in backups

### Monitoring Queries
```sql
-- Total messages
SELECT COUNT(*) FROM driver_messages;

-- Messages by sender type
SELECT sender_type, COUNT(*) 
FROM driver_messages 
GROUP BY sender_type;

-- Unread message count
SELECT COUNT(*) FROM driver_messages 
WHERE sender_type = 'driver' AND is_read = 0;

-- Unreplied conversations
SELECT COUNT(DISTINCT driver_id) FROM driver_messages 
WHERE sender_type = 'driver' AND has_reply = 0;

-- Attachment storage usage
SELECT 
    COUNT(*) as total_files,
    SUM(file_size) as total_bytes,
    SUM(file_size) / 1048576 as total_mb
FROM message_attachments;

-- Most active drivers
SELECT 
    driver_id, 
    COUNT(*) as message_count 
FROM driver_messages 
WHERE sender_type = 'driver'
GROUP BY driver_id 
ORDER BY message_count DESC 
LIMIT 10;
```

---

## Version History

**Version 1.0** (Current)
- Initial release
- Helpdesk-style messaging
- File attachments (R2 storage)
- Badge notifications
- Real-time polling
- WhatsApp-style UI
- Secure downloads
- Full conversation history

---

## Credits

**Developed for**: BOFETODA TODA Management System  
**Database**: Aiven MySQL  
**Storage**: Cloudflare R2  
**Deployment**: Render  

---

*For technical support or questions, contact the development team.*
