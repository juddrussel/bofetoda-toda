# Driver-to-Officer Messaging System
## Testing Checklist

### Pre-Testing Setup
- [ ] Database migration completed successfully
- [ ] R2 credentials configured in `r2-storage.php`
- [ ] `uploads/messages/` directory exists with write permissions
- [ ] Test driver account available
- [ ] Test officer account available

---

## Driver Side Testing

### Navigation & Access
- [ ] "Messages" menu item appears in bottom navigation
- [ ] Icon displays correctly (message bubble)
- [ ] Menu item positioned between "Submit" and "Notifications"
- [ ] Clicking navigates to `driver-messages.php`
- [ ] Page requires authentication (redirects if not logged in)

### Messaging Interface
- [ ] Page loads without errors
- [ ] Empty state displays when no messages exist
- [ ] Text area is present and functional
- [ ] Character input works correctly
- [ ] Line breaks (Enter key) work in textarea
- [ ] "Send Message" button is present

### File Upload
- [ ] File upload button displays ("📎 Attach Files")
- [ ] File picker opens when clicked
- [ ] Can select PDF files
- [ ] Can select DOC/DOCX files
- [ ] Can select JPG/PNG files
- [ ] Can select multiple files (up to 3)
- [ ] Selected files display with names
- [ ] Can remove individual files with × button
- [ ] Error message shows when selecting >3 files
- [ ] Error message shows for invalid file types
- [ ] Error message shows for files >5MB

### Sending Messages
- [ ] Can send text-only message
- [ ] Can send message with 1 attachment
- [ ] Can send message with 3 attachments
- [ ] Success message displays after sending
- [ ] Page redirects after successful send
- [ ] New message appears in conversation view
- [ ] Message shows correct sender ("You")
- [ ] Message shows correct timestamp
- [ ] Attachments display with icons and filenames
- [ ] File size displays correctly (KB/MB)

### Message Display
- [ ] Messages display in chronological order (oldest first)
- [ ] Driver messages appear on right side
- [ ] Driver messages have blue background
- [ ] Officer messages appear on left side
- [ ] Officer messages have gray background
- [ ] Officer name displays correctly
- [ ] Timestamps display in readable format
- [ ] Auto-scrolls to bottom on page load
- [ ] Long messages wrap correctly
- [ ] Multiple attachments display in list format

### File Downloads
- [ ] Clicking attachment link initiates download
- [ ] File downloads with correct name
- [ ] Downloaded file is not corrupted
- [ ] PDF files open correctly
- [ ] Image files display correctly
- [ ] Access denied if trying to access another driver's attachment

### Badge Notifications
- [ ] Badge appears when message is unreplied
- [ ] Badge shows correct count
- [ ] Badge displays "9+" for >9 unreplied messages
- [ ] Badge disappears when officer replies
- [ ] Badge updates without page refresh (within 30 seconds)
- [ ] Badge persists across page navigation
- [ ] Badge counter is accurate

---

## Officer Side Testing

### Navigation & Access
- [ ] "Driver Inbox" appears in sidebar
- [ ] Icon displays correctly (message bubble)
- [ ] Item positioned between "Driver List" and "Reports"
- [ ] Clicking navigates to `driver_inbox.php`
- [ ] Page requires authentication
- [ ] Badge appears when unread messages exist
- [ ] Badge shows correct count
- [ ] Badge displays "9+" for >9 unread messages

### Inbox List View
- [ ] Page loads without errors
- [ ] Empty state displays when no messages
- [ ] Driver list displays all drivers with messages
- [ ] Driver avatars show correct initials
- [ ] Driver names display correctly
- [ ] Last message preview shows correct text
- [ ] Preview truncates long messages with "..."
- [ ] Attachment count displays (📎 icon + count)
- [ ] Timestamps show relative time ("2 hours ago")
- [ ] Unread messages have blue background
- [ ] Unread badge shows correct count per driver
- [ ] "Needs Reply" badge shows for unreplied messages
- [ ] "Replied" badge shows after officer response
- [ ] List updates when new messages arrive

### Conversation View
- [ ] Clicking driver opens conversation page
- [ ] Back button (←) returns to inbox
- [ ] Driver name displays in header
- [ ] Driver ID displays in header
- [ ] Driver avatar shows correct initials
- [ ] All messages load correctly
- [ ] Messages display in chronological order
- [ ] Driver messages on left with blue background
- [ ] Officer messages on right with gray background
- [ ] Officer names display correctly on replies
- [ ] Timestamps display correctly
- [ ] Auto-scrolls to bottom on page load
- [ ] Messages marked as read automatically

### Replying to Messages
- [ ] Reply form is present at bottom
- [ ] Text area is functional
- [ ] Can type multi-line messages
- [ ] File upload button works
- [ ] Can attach up to 3 files
- [ ] File preview shows selected files
- [ ] Can remove selected files
- [ ] "Send Reply" button works
- [ ] Success message displays after sending
- [ ] Page redirects after send
- [ ] Reply appears in conversation
- [ ] Reply shows officer name correctly
- [ ] Reply marks conversation as "replied"
- [ ] Driver's "unreplied" badge disappears

### Attachment Handling
- [ ] Attachments display with correct icons
- [ ] File names display correctly
- [ ] File sizes display correctly
- [ ] Clicking attachment initiates download
- [ ] Downloaded files are not corrupted
- [ ] Can download driver attachments
- [ ] Can download officer attachments
- [ ] PDF files open correctly
- [ ] Image files display correctly
- [ ] All file types download correctly

### Multiple Officer Scenario
- [ ] Multiple officers can see same conversations
- [ ] Officer A's reply visible to Officer B
- [ ] Officer names distinguish who replied
- [ ] Both officers can reply to same driver
- [ ] Messages appear in correct chronological order
- [ ] No conflicts with concurrent replies

---

## Real-Time Badge Updates

### Driver Side
- [ ] Badge appears immediately after sending unreplied message
- [ ] Badge updates within 30 seconds of officer reply
- [ ] Badge disappears when officer replies
- [ ] Badge persists across page refreshes
- [ ] Badge count is accurate
- [ ] Polling doesn't cause performance issues

### Officer Side
- [ ] Badge appears immediately for new driver messages
- [ ] Badge updates within 30 seconds of new message
- [ ] Badge disappears when all messages read
- [ ] Badge count includes all unread messages
- [ ] Badge updates without page refresh
- [ ] Badge persists across page navigation
- [ ] Polling doesn't cause errors in console

---

## API Endpoints

### Driver API (`api-message-count.php`)
- [ ] Endpoint is accessible
- [ ] Returns JSON format
- [ ] Requires authentication
- [ ] Returns correct count
- [ ] Returns 401 if not logged in
- [ ] Responds quickly (<500ms)

### Officer API (`api-message-count.php`)
- [ ] Endpoint is accessible
- [ ] Returns JSON format
- [ ] Requires authentication
- [ ] Returns correct count
- [ ] Returns 401 if not logged in
- [ ] Responds quickly (<500ms)

---

## Security Testing

### Authentication
- [ ] Unauthenticated users redirected to login
- [ ] Sessions expire correctly
- [ ] Cannot access other driver's messages
- [ ] Cannot access officer pages as driver
- [ ] Cannot access driver pages as officer

### File Upload Security
- [ ] Rejects files >5MB
- [ ] Rejects invalid file types (.exe, .php, etc.)
- [ ] MIME type validation works
- [ ] Uploaded files have safe names
- [ ] Cannot upload executable files
- [ ] File path traversal prevented

### Download Security
- [ ] Driver can only download own attachments
- [ ] Officers can download all attachments
- [ ] Direct URL access without auth blocked
- [ ] Cannot access files by guessing URLs
- [ ] Download logs created for audit

### XSS Protection
- [ ] HTML in messages is escaped
- [ ] JavaScript in messages doesn't execute
- [ ] Special characters display correctly
- [ ] Attachment names with special chars handled

### SQL Injection
- [ ] Cannot inject SQL via message text
- [ ] Cannot inject SQL via driver_id parameter
- [ ] Cannot inject SQL via file names
- [ ] All queries use prepared statements

---

## Database Testing

### Data Integrity
- [ ] Messages save correctly
- [ ] Attachments link to correct messages
- [ ] Foreign key constraints work (cascade delete)
- [ ] Timestamps are accurate
- [ ] Sender types are correct
- [ ] Read/reply flags update correctly

### Performance
- [ ] Badge count queries are fast (<100ms)
- [ ] Message list loads quickly
- [ ] Conversation view loads quickly
- [ ] Indexes are being used (check EXPLAIN)
- [ ] No N+1 query problems

### Monitoring Queries
```sql
-- Verify message counts
SELECT COUNT(*) FROM driver_messages;

-- Check attachment storage
SELECT COUNT(*), SUM(file_size)/1048576 as MB 
FROM message_attachments;

-- Find unreplied messages
SELECT driver_id, COUNT(*) 
FROM driver_messages 
WHERE sender_type='driver' AND has_reply=0 
GROUP BY driver_id;

-- Find unread messages
SELECT COUNT(*) 
FROM driver_messages 
WHERE sender_type='driver' AND is_read=0;
```

---

## R2 Storage Testing

### File Upload
- [ ] Files upload to R2 successfully
- [ ] Folder structure correct (`messages/{driver_id}/`)
- [ ] File names are unique
- [ ] Metadata saved to database
- [ ] r2_key populated correctly
- [ ] Falls back to local storage if R2 fails

### File Download
- [ ] Generates presigned URLs correctly
- [ ] URLs are accessible
- [ ] URLs expire appropriately
- [ ] Falls back to local files if R2 unavailable
- [ ] Correct Content-Type headers

### Error Handling
- [ ] Handles R2 connection failures gracefully
- [ ] Shows user-friendly error messages
- [ ] Logs errors for debugging
- [ ] Falls back to local storage
- [ ] Doesn't crash on R2 errors

---

## Edge Cases & Error Scenarios

### Empty/Invalid Input
- [ ] Cannot send empty message
- [ ] Error message for empty text
- [ ] Cannot send without text (attachments only blocked)
- [ ] Whitespace-only message rejected

### File Upload Errors
- [ ] >3 files rejected with message
- [ ] >5MB file rejected with message
- [ ] Invalid file type rejected
- [ ] Network error handled gracefully
- [ ] Disk full handled gracefully

### Database Errors
- [ ] Connection loss handled gracefully
- [ ] Transaction rollback on error
- [ ] User-friendly error messages
- [ ] Errors logged for debugging

### Concurrent Access
- [ ] Multiple drivers can send simultaneously
- [ ] Multiple officers can reply simultaneously
- [ ] Race conditions don't corrupt data
- [ ] Badge counts remain accurate

### Long-Running Sessions
- [ ] Session doesn't expire during typing
- [ ] Badge polling continues over time
- [ ] No memory leaks in JavaScript
- [ ] Page remains responsive

---

## Browser Compatibility

### Desktop Browsers
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

### Mobile Browsers
- [ ] Chrome Mobile
- [ ] Safari iOS
- [ ] Firefox Mobile

### Features to Test Per Browser
- [ ] File upload picker works
- [ ] Badge updates work
- [ ] Scrolling works smoothly
- [ ] Message bubbles display correctly
- [ ] Attachments download correctly
- [ ] No JavaScript errors in console

---

## Performance Testing

### Page Load Times
- [ ] Driver messages page <2s
- [ ] Officer inbox page <2s
- [ ] Conversation page <2s
- [ ] File download <5s

### Response Times
- [ ] Send message <1s
- [ ] API badge count <500ms
- [ ] Mark as read <500ms
- [ ] File upload <10s

### Scalability
- [ ] 100+ messages load correctly
- [ ] 10+ attachments per message work
- [ ] 50+ drivers in inbox list
- [ ] Badge polling doesn't slow page

---

## Final Integration Test

### Complete User Flow - Driver
1. [ ] Driver logs in
2. [ ] Navigates to Messages
3. [ ] Sees empty state or previous messages
4. [ ] Types new message
5. [ ] Attaches 2 files
6. [ ] Sends message
7. [ ] Sees success confirmation
8. [ ] Sees message in conversation
9. [ ] Sees badge appear (unreplied)
10. [ ] Waits for officer reply
11. [ ] Sees badge disappear
12. [ ] Reads officer reply
13. [ ] Downloads attachment
14. [ ] Sends follow-up message

### Complete User Flow - Officer
1. [ ] Officer logs in
2. [ ] Sees badge on Driver Inbox
3. [ ] Clicks Driver Inbox
4. [ ] Sees list of drivers
5. [ ] Identifies driver needing reply
6. [ ] Opens conversation
7. [ ] Reads all messages
8. [ ] Downloads attachment
9. [ ] Types reply
10. [ ] Attaches file
11. [ ] Sends reply
12. [ ] Sees reply in conversation
13. [ ] Badge count decreases
14. [ ] Returns to inbox
15. [ ] "Needs Reply" badge gone

---

## Sign-Off

### Tested By: _______________
### Date: _______________
### Environment: _______________
### Result: [ ] PASS [ ] FAIL

### Critical Issues Found:
1. 
2. 
3. 

### Notes:


---

*Complete all checklist items before deploying to production.*
