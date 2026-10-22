<?php
/**
 * General Announcements Migration Runner
 * Visit: https://bofetoda-app.onrender.com/run_announcements_migration.php
 * This will create the general_announcements and announcement_images tables
 */

// Include database connection
require_once 'Admin_BOFETODA/db.php';

// Get PDO connection
$conn = get_pdo();

// Set content type
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Announcements Migration Runner</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #16213e; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
        .success { color: #16a34a; background: #dcfce7; padding: 12px 16px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #16a34a; }
        .error { color: #dc2626; background: #fee2e2; padding: 12px 16px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #dc2626; }
        .info { color: #1d4ed8; background: #dbeafe; padding: 12px 16px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #1d4ed8; }
        .warning { color: #ea580c; background: #fed7aa; padding: 12px 16px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #ea580c; }
        pre { background: #f8fafc; padding: 15px; border-radius: 6px; overflow-x: auto; border: 1px solid #e2e8f0; font-size: 13px; line-height: 1.6; }
        .step { margin: 25px 0; }
        .step-title { font-weight: 700; color: #16213e; margin-bottom: 8px; font-size: 16px; }
        .feature-list { background: #f8fafc; padding: 20px; border-radius: 6px; margin: 20px 0; }
        .feature-list h3 { margin-top: 0; color: #16213e; }
        .feature-list ul { margin: 10px 0; padding-left: 25px; }
        .feature-list li { margin: 8px 0; color: #374151; }
        .btn { display: inline-block; background: #dc2626; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; margin-top: 20px; }
        .btn:hover { background: #b91c1c; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 General Announcements Migration</h1>
        <p class='subtitle'>Setting up the announcements system for organizational events, reminders, updates, and news</p>
";

try {
    // Check if tables already exist
    $checkAnnouncements = $conn->query("SHOW TABLES LIKE 'general_announcements'");
    $checkImages = $conn->query("SHOW TABLES LIKE 'announcement_images'");
    
    $announcementsExists = $checkAnnouncements->rowCount() > 0;
    $imagesExists = $checkImages->rowCount() > 0;
    
    if ($announcementsExists && $imagesExists) {
        echo "<div class='warning'><strong>⚠️ Already Migrated</strong><br>";
        echo "The general announcements tables already exist in the database.<br>";
        echo "No changes needed.</div>";
    } else {
        echo "<div class='info'><strong>📋 Migration Status:</strong><br>";
        echo "general_announcements table: " . ($announcementsExists ? "✅ Exists" : "❌ Needs creation") . "<br>";
        echo "announcement_images table: " . ($imagesExists ? "✅ Exists" : "❌ Needs creation") . "</div>";
        
        // Run migrations
        $success = true;
        
        // Create general_announcements table
        if (!$announcementsExists) {
            echo "<div class='step'><div class='step-title'>Creating general_announcements table...</div>";
            $sql = "CREATE TABLE general_announcements (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                category ENUM('Event', 'Reminder', 'Update', 'News', 'Other') NOT NULL DEFAULT 'Other',
                event_date DATETIME NULL DEFAULT NULL COMMENT 'For event-type announcements',
                event_location VARCHAR(255) NULL DEFAULT NULL COMMENT 'Event location/venue',
                priority ENUM('normal', 'urgent') NOT NULL DEFAULT 'normal',
                is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Soft delete flag',
                created_by VARCHAR(20) NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                CONSTRAINT fk_gen_ann_officer FOREIGN KEY (created_by)
                    REFERENCES officers(officer_id) ON UPDATE CASCADE ON DELETE SET NULL,
                
                INDEX idx_gen_ann_category (category),
                INDEX idx_gen_ann_priority (priority),
                INDEX idx_gen_ann_active (is_active),
                INDEX idx_gen_ann_created (created_at),
                INDEX idx_gen_ann_priority_created (priority, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='General announcements for organizational events, reminders, updates, and news'";
            
            if ($conn->exec($sql) !== false) {
                echo "<div class='success'>✅ general_announcements table created successfully!</div></div>";
            } else {
                echo "<div class='error'>❌ Failed to create general_announcements table</div></div>";
                $success = false;
            }
        }
        
        // Create announcement_images table
        if (!$imagesExists) {
            echo "<div class='step'><div class='step-title'>Creating announcement_images table...</div>";
            $sql = "CREATE TABLE announcement_images (
                image_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                announcement_id INT UNSIGNED NOT NULL,
                image_path VARCHAR(255) NOT NULL,
                display_order INT NOT NULL DEFAULT 0 COMMENT 'Order for displaying images in gallery',
                uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                
                CONSTRAINT fk_ann_img_announcement FOREIGN KEY (announcement_id)
                    REFERENCES general_announcements(id) ON DELETE CASCADE ON UPDATE CASCADE,
                
                INDEX idx_ann_img_announcement (announcement_id),
                INDEX idx_ann_img_order (announcement_id, display_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Images associated with general announcements (supports multiple images per announcement)'";
            
            if ($conn->exec($sql) !== false) {
                echo "<div class='success'>✅ announcement_images table created successfully!</div></div>";
            } else {
                echo "<div class='error'>❌ Failed to create announcement_images table</div></div>";
                $success = false;
            }
        }
        
        if ($success) {
            echo "<div class='success'><strong>🎉 Migration Complete!</strong><br>";
            echo "The general announcements system has been set up successfully.<br>";
            echo "You can now manage announcements from the admin panel.</div>";
        }
    }
    
    // Show table structures
    echo "<div class='step'><h2>📊 Database Schema</h2>";
    
    echo "<h3>general_announcements Table:</h3><pre>";
    $result = $conn->query("DESCRIBE general_announcements");
    printf("%-25s %-30s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 80) . "\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        printf("%-25s %-30s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key']
        );
    }
    echo "</pre>";
    
    echo "<h3>announcement_images Table:</h3><pre>";
    $result = $conn->query("DESCRIBE announcement_images");
    printf("%-25s %-30s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 80) . "\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        printf("%-25s %-30s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key']
        );
    }
    echo "</pre></div>";
    
    // Feature overview
    echo "<div class='feature-list'>";
    echo "<h3>✨ What's New - General Announcements Feature</h3>";
    echo "<ul>";
    echo "<li><strong>Admin Panel:</strong> Create, edit, delete, and manage announcements</li>";
    echo "<li><strong>Categories:</strong> Event, Reminder, Update, News, Other</li>";
    echo "<li><strong>Priority Levels:</strong> Normal and Urgent (urgent announcements pinned to top)</li>";
    echo "<li><strong>Multiple Images:</strong> Upload up to 5 images per announcement</li>";
    echo "<li><strong>Event Details:</strong> Optional date/time and location for events</li>";
    echo "<li><strong>Driver Portal:</strong> View announcements on homepage and dedicated page</li>";
    echo "<li><strong>Search & Filter:</strong> Find announcements by category or search text</li>";
    echo "<li><strong>Active/Inactive:</strong> Toggle announcement visibility without deletion</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'><strong>📍 Next Steps:</strong><br>";
    echo "1. Access the admin panel: <a href='/Admin_BOFETODA/dashboard.php'>Admin Dashboard</a><br>";
    echo "2. Click on 'Announcements' in the sidebar (once code is deployed)<br>";
    echo "3. Create your first announcement for drivers<br>";
    echo "4. Drivers will see announcements on their homepage</div>";
    
    echo "<div class='warning'><strong>⚠️ Security Notice:</strong><br>";
    echo "Please delete this migration file (run_announcements_migration.php) after running it for security reasons.";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>❌ Database Error:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";
?>
