# General Announcements Feature - User Guide

## 📋 Table of Contents
1. [Overview](#overview)
2. [Feature Highlights](#feature-highlights)
3. [Admin User Guide](#admin-user-guide)
4. [Driver User Guide](#driver-user-guide)
5. [Database Schema](#database-schema)
6. [Image Upload Guidelines](#image-upload-guidelines)
7. [Priority and Category Guidelines](#priority-and-category-guidelines)
8. [Testing Checklist](#testing-checklist)
9. [Troubleshooting](#troubleshooting)

---

## Overview

The General Announcements feature allows TODA administrators (President and Secretary) to post organizational announcements to all drivers. This system supports events, reminders, updates, news, and other general communications with multiple image attachments.

**Key Features:**
- Create, edit, delete, and manage announcements
- Multiple image uploads (up to 5 per announcement)
- Priority levels (Normal and Urgent)
- Category tagging (Event, Reminder, Update, News, Other)
- Optional event date/time and location
- Driver homepage integration
- Full-page announcements view with search and filters
- Image lightbox gallery

---

## Feature Highlights

### For Administrators
✅ **Easy Management** - Intuitive interface for creating and managing announcements  
✅ **Rich Content** - Add titles, messages, event details, and multiple images  
✅ **Flexible Filtering** - Filter by category, priority, and active status  
✅ **Search Functionality** - Quickly find announcements by title or content  
✅ **Edit Capabilities** - Update announcements and manage images after creation  
✅ **Soft Delete** - Deactivate announcements without permanent deletion  

### For Drivers
✅ **Homepage Display** - Latest 3 announcements shown prominently  
✅ **Full Announcements Page** - View all active announcements with pagination  
✅ **Visual Priority** - Urgent announcements stand out with red styling  
✅ **Category Badges** - Color-coded categories for quick identification  
✅ **Image Gallery** - View multiple images with full-screen lightbox  
✅ **Smart Dates** - Event dates shown as "Today", "Tomorrow", or specific dates  
✅ **Mobile Friendly** - Responsive design works on all devices  

---

## Admin User Guide

### Accessing Announcements Management

1. Log in to the Admin Portal at `/Admin_BOFETODA/login.php`
2. Click **"Announcements"** in the sidebar (between Dashboard and Renewals)
3. You'll see the announcements management page

### Creating an Announcement

1. Click the **"+ Create Announcement"** button
2. Fill in the required fields:
   - **Title** (required, max 200 characters)
   - **Message** (required, no limit)
   - **Category** (required):
     - `Event` - Organizational events, meetings, gatherings
     - `Reminder` - Important reminders, deadlines
     - `Update` - System updates, policy changes
     - `News` - General news and information
     - `Other` - Miscellaneous announcements
   - **Priority** (required):
     - `Normal` - Standard announcement
     - `Urgent` - High priority (pinned to top, red styling)
3. Optional fields:
   - **Event Date & Time** - For event-type announcements
   - **Event Location** - Venue or address
   - **Images** - Upload up to 5 images (5MB each max)
4. Click **"Save Announcement"**

### Editing an Announcement

1. Find the announcement in the list
2. Click the **"Edit"** button on the announcement card
3. Modify any fields as needed
4. To remove existing images, click the **×** button on the image
5. To add new images, use the upload section
6. Click **"Update Announcement"**

### Deleting or Deactivating

- **Deactivate**: Click **"Deactivate"** to hide from drivers (can be reactivated later)
- **Activate**: Click **"Activate"** to make visible again
- **Delete**: Click **"Delete"** to permanently soft-delete (sets is_active=0)

### Filtering and Searching

Use the filter bar to find specific announcements:
- **Search box** - Search by title or message content
- **Category dropdown** - Filter by specific category
- **Priority dropdown** - Show only normal or urgent
- **Status dropdown** - View active or inactive announcements
- Click **"Apply Filters"** to execute
- Click **"Clear"** to reset all filters

### Best Practices

**DO:**
- ✅ Use clear, concise titles
- ✅ Include complete information in the message
- ✅ Set appropriate categories for organization
- ✅ Use Urgent priority sparingly (only for critical info)
- ✅ Add event details for event-type announcements
- ✅ Use high-quality images that are relevant
- ✅ Test how announcements look on the driver side

**DON'T:**
- ❌ Create duplicate announcements
- ❌ Use ALL CAPS in titles (except for acronyms)
- ❌ Upload irrelevant or low-quality images
- ❌ Mark everything as Urgent
- ❌ Include sensitive personal information
- ❌ Forget to deactivate outdated announcements

---

## Driver User Guide

### Viewing Announcements on Homepage

When you log in to the Driver Portal, you'll see:
- **Latest 3 announcements** displayed at the top of your homepage
- **Urgent announcements** have a red border and 🔥 URGENT badge
- **Category badges** show the type (Event, Reminder, etc.)
- **Truncated messages** with "Read more..." link to expand
- **Image previews** (up to 4 images shown)
- **Event details** if applicable (date, time, location)
- **Posted date** for each announcement

### Viewing All Announcements

1. Click **"View All →"** on the homepage
2. You'll see the full announcements page with:
   - All active announcements in full detail
   - Search and filter options
   - Pagination (10 announcements per page)

### Searching and Filtering

- **Search box** - Type keywords to find specific announcements
- **Category filter** - Select a category to view only those announcements
- Click **"Search"** to apply filters
- Click **"Clear"** to show all announcements

### Viewing Images

- **Homepage**: Click on any thumbnail to view larger
- **Full page**: Click any image to open the lightbox viewer
- **Lightbox controls**:
  - Click **×** or press **Esc** to close
  - Click **‹ ›** arrows or press **Arrow keys** to navigate
  - Click outside the image to close
  - See image counter (e.g., "2 / 5")

### Understanding Badges

- **🔥 URGENT** (red) - High priority, requires immediate attention
- **Event** (blue) - Organizational events
- **Reminder** (orange) - Important reminders
- **Update** (green) - System or policy updates
- **News** (purple) - General news
- **Other** (gray) - Miscellaneous

---

## Database Schema

### `general_announcements` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT UNSIGNED | Primary key, auto-increment |
| `title` | VARCHAR(200) | Announcement title |
| `message` | TEXT | Full announcement message |
| `category` | ENUM | Event, Reminder, Update, News, Other |
| `event_date` | DATETIME | Optional event date/time |
| `event_location` | VARCHAR(255) | Optional event location |
| `priority` | ENUM | normal, urgent |
| `is_active` | TINYINT(1) | Active status (1=active, 0=inactive) |
| `created_by` | VARCHAR(20) | Officer ID (FK to officers) |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Indexes:**
- `idx_gen_ann_category` on `category`
- `idx_gen_ann_priority` on `priority`
- `idx_gen_ann_active` on `is_active`
- `idx_gen_ann_created` on `created_at`
- `idx_gen_ann_priority_created` on `priority, created_at` (composite)

### `announcement_images` Table

| Column | Type | Description |
|--------|------|-------------|
| `image_id` | BIGINT UNSIGNED | Primary key, auto-increment |
| `announcement_id` | INT UNSIGNED | FK to general_announcements.id |
| `image_path` | VARCHAR(255) | Relative path to image file |
| `display_order` | INT | Order for displaying in gallery |
| `uploaded_at` | TIMESTAMP | Upload timestamp |

**Indexes:**
- `idx_ann_img_announcement` on `announcement_id`
- `idx_ann_img_order` on `announcement_id, display_order` (composite)

**Foreign Key Constraints:**
- `announcement_images.announcement_id` → `general_announcements.id` (CASCADE on delete)
- `general_announcements.created_by` → `officers.officer_id` (SET NULL on delete)

---

## Image Upload Guidelines

### Technical Requirements

- **File Types**: JPG, JPEG, PNG, GIF, WEBP only
- **Maximum Size**: 5MB per image
- **Maximum Count**: 5 images per announcement
- **Dimensions**: No specific requirements, but optimized images recommended

### File Validation

The system performs comprehensive security checks:
1. **MIME Type Check** - Verifies file is actually an image
2. **Size Check** - Rejects files over 5MB
3. **Extension Check** - Ensures extension matches content
4. **Content Verification** - Uses `getimagesize()` to validate image data
5. **Filename Sanitization** - Generates unique, safe filenames

### Best Practices

**Image Dimensions:**
- Recommended: 1200×800 pixels (landscape) or 800×1200 (portrait)
- Minimum: 600×400 pixels
- Maximum: 4000×3000 pixels (will be displayed at appropriate size)

**Image Optimization:**
- Compress images before upload (use tools like TinyPNG, JPEG Optimizer)
- Target file size: 200KB - 1MB per image
- Use JPEG for photos, PNG for graphics/text
- Avoid screenshots with tiny text (hard to read)

**Content Guidelines:**
- Use high-quality, relevant images
- Ensure proper lighting and clarity
- Avoid blurry or pixelated images
- Check that text in images is readable
- Consider mobile viewing experience

### Storage and Security

- **Location**: `/uploads/announcements/`
- **Filename Format**: `announcement_{id}_{timestamp}_{random}.{ext}`
- **Security**: `.htaccess` prevents PHP execution in upload directory
- **Access**: Images publicly accessible but directory listing disabled

---

## Priority and Category Guidelines

### When to Use URGENT Priority

Use Urgent priority (🔥) only for:
- ✅ Critical deadlines (e.g., "Document submission deadline: Today")
- ✅ Emergency announcements (e.g., "Office closure due to weather")
- ✅ Mandatory meetings (e.g., "Emergency general assembly tomorrow")
- ✅ Important policy changes with immediate effect
- ✅ Safety or security alerts

Do NOT use Urgent for:
- ❌ Regular announcements
- ❌ General updates
- ❌ Events more than a week away
- ❌ Routine reminders

### Category Usage Guide

**Event** 🔵
- General assemblies
- Training sessions
- Social gatherings
- Organizational meetings
- Anniversary celebrations

**Reminder** 🟠
- Deadline reminders
- Document submission reminders
- Payment reminders
- Renewal period reminders
- Meeting reminders

**Update** 🟢
- System updates
- Policy changes
- Process improvements
- New features
- Office hours changes

**News** 🟣
- Organization achievements
- Member spotlights
- Community news
- Partnership announcements
- General information

**Other** ⚪
- Miscellaneous announcements
- Informal communications
- Seasonal greetings
- Announcements that don't fit other categories

---

## Testing Checklist

### Before Deployment

#### Admin Side Tests

- [ ] **Access Control**
  - [ ] President can access announcements page
  - [ ] Secretary can access announcements page
  - [ ] Menu item appears in sidebar for both roles

- [ ] **Create Announcement**
  - [ ] Form opens when clicking "Create Announcement"
  - [ ] All required fields validate properly
  - [ ] Title field accepts up to 200 characters
  - [ ] Message field accepts unlimited text
  - [ ] Category dropdown shows all 5 options
  - [ ] Priority radio buttons work (normal/urgent)
  - [ ] Event date/time picker functions correctly
  - [ ] Event location field accepts text
  - [ ] Image upload accepts multiple files
  - [ ] Image preview shows after selection
  - [ ] Form submits successfully
  - [ ] Success message displays
  - [ ] New announcement appears in list

- [ ] **Image Upload**
  - [ ] Accepts JPG, PNG, GIF, WEBP files
  - [ ] Rejects files over 5MB
  - [ ] Rejects non-image files (PDF, DOC, etc.)
  - [ ] Maximum 5 images enforced
  - [ ] Images save to uploads/announcements/ directory
  - [ ] Unique filenames generated

- [ ] **Edit Announcement**
  - [ ] Edit button opens form with existing data
  - [ ] All fields pre-filled correctly
  - [ ] Existing images display
  - [ ] Can delete existing images
  - [ ] Can add new images
  - [ ] Update saves changes
  - [ ] Success message displays

- [ ] **Delete/Toggle**
  - [ ] Deactivate button hides announcement from drivers
  - [ ] Activate button re-enables announcement
  - [ ] Delete button soft-deletes (is_active=0)
  - [ ] Confirmation prompt appears before delete
  - [ ] Deleted announcement disappears from active list

- [ ] **Filtering**
  - [ ] Search box filters by title and message
  - [ ] Category filter shows correct announcements
  - [ ] Priority filter works (normal/urgent)
  - [ ] Status filter works (active/inactive)
  - [ ] Clear button resets all filters
  - [ ] Filters persist in URL parameters

- [ ] **Display**
  - [ ] Announcements sort correctly (urgent first, then newest)
  - [ ] Cards show all metadata (title, category, priority, images, dates)
  - [ ] Badges display with correct colors
  - [ ] Image count shows correctly
  - [ ] Created by information displays
  - [ ] Empty state shows when no announcements

#### Driver Side Tests

- [ ] **Homepage Display**
  - [ ] Latest 3 announcements show at top of content
  - [ ] Announcements appear above renewal section
  - [ ] "View All" link present and functional
  - [ ] Urgent announcements have red border
  - [ ] Category badges show with correct colors
  - [ ] Message truncates at 150 characters
  - [ ] "Read more" link expands message inline
  - [ ] "Show less" link collapses message
  - [ ] Images display (up to 4 thumbnails)
  - [ ] "+N" indicator shows for more than 4 images
  - [ ] Event date displays if present
  - [ ] Event location displays if present
  - [ ] Posted date displays

- [ ] **Full Announcements Page**
  - [ ] Accessible via "View All" link
  - [ ] Shows all active announcements
  - [ ] Pagination works (10 per page)
  - [ ] Page numbers and navigation correct
  - [ ] Search filters announcements
  - [ ] Category filter works
  - [ ] Clear button resets filters
  - [ ] Full message displays (no truncation)
  - [ ] All images show in grid
  - [ ] Back to home link works

- [ ] **Image Lightbox**
  - [ ] Clicking image opens lightbox
  - [ ] Image displays at full size
  - [ ] Close button (×) works
  - [ ] Click outside closes lightbox
  - [ ] Esc key closes lightbox
  - [ ] Previous (‹) button navigates
  - [ ] Next (›) button navigates
  - [ ] Arrow keys navigate
  - [ ] Counter shows current/total (e.g., "2 / 5")
  - [ ] Navigation loops (last→first, first→last)
  - [ ] Lightbox prevents body scroll

- [ ] **Empty States**
  - [ ] Homepage shows nothing if no announcements
  - [ ] Full page shows empty state with message
  - [ ] Empty state appears for no search results

#### Responsive Design Tests

- [ ] **Desktop** (1920×1080)
  - [ ] Admin: Grid layout displays correctly
  - [ ] Admin: Modal centers properly
  - [ ] Driver: Homepage announcements fit well
  - [ ] Driver: Full page grid responsive

- [ ] **Tablet** (768×1024)
  - [ ] Admin: Cards stack appropriately
  - [ ] Driver: Images resize correctly
  - [ ] Lightbox fits screen

- [ ] **Mobile** (375×667)
  - [ ] Admin: Single column layout
  - [ ] Admin: Form fields full width
  - [ ] Driver: Touch-friendly buttons
  - [ ] Driver: Images don't overflow
  - [ ] Lightbox works with touch gestures

### Database Migration Test

- [ ] Run `run_announcements_migration.php`
- [ ] Verify `general_announcements` table created
- [ ] Verify `announcement_images` table created
- [ ] Check all columns exist with correct types
- [ ] Verify foreign key constraints
- [ ] Verify indexes created
- [ ] Delete migration runner after success

### Security Tests

- [ ] **.htaccess Protection**
  - [ ] PHP files cannot execute in uploads/announcements/
  - [ ] Directory listing disabled
  - [ ] Only image files accessible

- [ ] **File Upload Security**
  - [ ] PHP file disguised as image rejected
  - [ ] Executable file rejected
  - [ ] Oversized file rejected
  - [ ] Filename sanitized (no special characters)

- [ ] **Access Control**
  - [ ] Unauthenticated users redirected to login
  - [ ] Driver cannot access admin pages
  - [ ] Officer cannot access driver pages

---

## Troubleshooting

### Common Issues and Solutions

#### "Failed to create announcement"

**Possible Causes:**
- Database connection issue
- Missing required fields
- Image upload failure

**Solutions:**
1. Check database connection in `db.php`
2. Verify all required fields filled
3. Check image file size and type
4. Review PHP error logs
5. Ensure `uploads/announcements/` directory exists and is writable

#### Images not displaying

**Possible Causes:**
- Incorrect file path
- Missing `.htaccess` in uploads directory
- File permissions issue

**Solutions:**
1. Verify image path starts with `uploads/announcements/`
2. Check `.htaccess` file exists in `uploads/announcements/`
3. Set directory permissions: `chmod 755 uploads/announcements/`
4. Check file permissions: `chmod 644` for image files
5. Verify Apache mod_rewrite enabled

#### "Column not found" error

**Possible Cause:**
- Database migration not run

**Solution:**
1. Visit `/run_announcements_migration.php`
2. Execute the migration
3. Verify tables created: `DESCRIBE general_announcements;`
4. Delete migration runner after success

#### Announcements not showing on driver homepage

**Possible Causes:**
- Announcements marked inactive
- No active announcements exist
- Function not called in driver-home.php

**Solutions:**
1. Check announcement `is_active` status in database
2. Create at least one active announcement
3. Verify `get_latest_announcements(3)` called in driver-home.php
4. Clear browser cache

#### Lightbox not working

**Possible Causes:**
- JavaScript error
- Missing image data
- Click event not firing

**Solutions:**
1. Open browser console, check for errors
2. Verify image paths in HTML source
3. Check `announcementImages` JavaScript variable populated
4. Test with different browser
5. Clear browser cache

#### Search/filter returns no results

**Possible Causes:**
- No matching announcements
- SQL query issue
- Filter parameters incorrect

**Solutions:**
1. Verify announcements exist with expected criteria
2. Check filter parameters in URL
3. Try clearing all filters
4. Check database for matching records manually
5. Review `get_paginated_announcements()` function

#### Upload fails silently

**Possible Causes:**
- PHP upload_max_filesize limit
- PHP post_max_size limit
- Missing file in $_FILES

**Solutions:**
1. Check `php.ini`:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```
2. Restart web server after changes
3. Check PHP error log
4. Verify form has `enctype="multipart/form-data"`

---

## Additional Resources

### File Locations

- **Admin Management**: `/Admin_BOFETODA/manage_announcements.php`
- **Driver Homepage**: `/Driver_BOFETODA/driver-home.php`
- **Driver Full Page**: `/Driver_BOFETODA/driver-announcements.php`
- **Database Functions**: `/Admin_BOFETODA/config.php` (admin), `/Driver_BOFETODA/driver-database.php` (driver)
- **Upload Directory**: `/uploads/announcements/`
- **Migration**: `/add_general_announcements.sql`, `/run_announcements_migration.php`

### Database Queries

**Count total announcements:**
```sql
SELECT COUNT(*) FROM general_announcements WHERE is_active = 1;
```

**Find announcements by category:**
```sql
SELECT * FROM general_announcements WHERE category = 'Event' AND is_active = 1;
```

**List urgent announcements:**
```sql
SELECT * FROM general_announcements WHERE priority = 'urgent' AND is_active = 1 ORDER BY created_at DESC;
```

**Get announcements with image count:**
```sql
SELECT ga.*, COUNT(ai.image_id) as image_count
FROM general_announcements ga
LEFT JOIN announcement_images ai ON ga.id = ai.announcement_id
WHERE ga.is_active = 1
GROUP BY ga.id;
```

### Support Contacts

For technical issues:
- Review this documentation
- Check system error logs
- Consult with system administrator

---

**Document Version:** 1.0  
**Last Updated:** October 23, 2026  
**Feature Version:** Initial Release
