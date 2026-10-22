# 🗄️ Import Database to Aiven (Easy Way!)

Since MySQL Workbench keeps crashing, use the **Aiven Web Console** instead!

---

## ✅ Method 1: Use Aiven Quick Connect (EASIEST!)

### Step 1: Open Aiven Console
1. Go to: https://console.aiven.io
2. Click on your **"bofetoda-mysql"** service
3. Look for **"Quick connect"** button (top right corner)
4. Click it

### Step 2: You'll see a web terminal
It will show something like:
```
MySQL [defaultdb]>
```

### Step 3: Copy and paste SQL commands
1. Open the file: `database_schema.sql`
2. **Copy ALL the content**
3. **Paste into the Aiven terminal**
4. Press **Enter**

Wait for it to finish (about 30 seconds)

### Step 4: Verify
Type this command:
```sql
USE bofetoda;
SHOW TABLES;
```

You should see all 11 tables!

---

## ✅ Method 2: Use Query Editor

If you don't see "Quick connect":

### Step 1: Find Query Editor
1. Go to https://console.aiven.io
2. Click your MySQL service
3. Look in the left sidebar for:
   - **"Query editor"** or
   - **"Console"** or  
   - **"SQL"** tab

### Step 2: Run SQL
1. Open `database_schema.sql` file
2. Copy sections one at a time:
   - First: Copy CREATE DATABASE + CREATE TABLE statements
   - Run them
   - Then: Copy INSERT statements  
   - Run them

---

## 📋 What the SQL Does:

✅ Creates `bofetoda` database
✅ Creates 11 tables (drivers, officers, applications, etc.)
✅ Inserts sample data:
   - 19 barangays
   - 10 colors
   - 2 officers (president & secretary)
   - 16 drivers (from your data)
   - 4 reject reasons

---

## 🔄 After Import is Complete:

### Update Render Environment Variable:

1. Go to your Render dashboard
2. Click on **"bofetoda-app"** service
3. Click **"Environment"** tab
4. Find `DB_NAME` variable
5. Change value from `defaultdb` to: **`bofetoda`**
6. Click **"Save Changes"**
7. Render will automatically redeploy

---

## 🎯 Quick Test:

After import, run this in Aiven console:

```sql
USE bofetoda;

-- Check drivers
SELECT COUNT(*) as driver_count FROM drivers;

-- Check officers  
SELECT officer_id, name, role FROM officers;

-- Check tables
SHOW TABLES;
```

Expected results:
- Driver count: 16 (or more if you ran all inserts)
- 2 officers
- 11 tables

---

## ⚠️ If You Get Errors:

### "Table already exists"
→ Normal! It means that table is already there. Keep going!

### "Cannot add foreign key constraint"
→ Make sure you ran the CREATE TABLE statements IN ORDER
→ Parent tables must exist before child tables

### Connection timeout
→ Refresh the page and continue from where you left off

---

## 💡 Pro Tip:

The SQL file is designed to be safe to run multiple times:
- Uses `CREATE DATABASE IF NOT EXISTS`
- Uses `CREATE TABLE IF NOT EXISTS`  
- Uses `INSERT IGNORE` (won't duplicate data)

So if something fails, just run it again!

---

## ✅ Once Import is Done:

1. ✓ Database created
2. ✓ All tables created
3. ✓ Sample data inserted
4. ✓ Update Render `DB_NAME` to `bofetoda`
5. ✓ Wait for Render to redeploy (~2 min)
6. ✓ Test your site!

**Your URLs:**
```
https://bofetoda-app.onrender.com/Admin_BOFETODA/login.php
https://bofetoda-app.onrender.com/Driver_BOFETODA/driver-login.php
```

**Test credentials:**
- Admin: `OFF-20240001` / `president123`
- Driver: `DRV-BG0064` / `renato36`

---

**Go to Aiven console now and start importing!** 🚀
