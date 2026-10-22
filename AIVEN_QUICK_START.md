# ⚡ Quick Start - Deploy with Aiven MySQL (10 Minutes)

Fast deployment guide using **Aiven** for MySQL database.

---

## ✅ Step 1: Create Aiven Database (4 min)

1. **Sign up:** https://aiven.io → **100% FREE forever** (no credit card!)
2. **Create service:** MySQL → Choose "**Free Tier**" plan
3. **Region:** Choose closest to you
4. **Wait 5-10 minutes** for provisioning
5. **Create database:** Name it `bofetoda`
6. **Save credentials:**
   ```
   Host: bofetoda-mysql-yourproject.aivencloud.com
   Port: 12345 (your Aiven port)
   User: avnadmin
   Password: [long random password]
   ```

---

## ✅ Step 2: Import Database Schema (2 min)

**Option A:** Use Aiven Console
- Go to "Databases" tab → Click `bofetoda` → Run your CREATE TABLE statements

**Option B:** Use MySQL Client
```bash
mysql --host=YOUR_AIVEN_HOST \
      --port=YOUR_PORT \
      --user=avnadmin \
      --password=YOUR_PASSWORD \
      --database=bofetoda \
      --ssl-mode=REQUIRED < schema.sql
```

---

## ✅ Step 3: Push to GitHub (2 min)

```powershell
cd "e:\Users\Judd\Downloads\toda\BTODATGinc"

git init
git add .
git commit -m "Deploy with Aiven MySQL"

# Create repo on GitHub.com, then:
git remote add origin https://github.com/YOUR_USERNAME/bofetoda-toda.git
git push -u origin main
```

---

## ✅ Step 4: Deploy to Render (2 min)

1. **Sign up:** https://render.com (use GitHub login)
2. **New Web Service** → Connect `bofetoda-toda` repo
3. **Configure:**
   ```
   Name: bofetoda-app
   Runtime: PHP
   Start Command: php -S 0.0.0.0:$PORT -t .
   ```
4. **Add Environment Variables:**
   ```
   DB_HOST = [Your Aiven host from Step 1]
   DB_PORT = [Your Aiven port from Step 1]
   DB_NAME = bofetoda
   DB_USER = avnadmin
   DB_PASS = [Your Aiven password from Step 1]
   ```
5. **Create Web Service** → Wait 2-5 minutes

---

## ✅ Step 5: Test Your Site

Your app is live at: `https://bofetoda-app.onrender.com`

**Test URLs:**
- Landing: https://bofetoda-app.onrender.com/
- Admin: https://bofetoda-app.onrender.com/Admin_BOFETODA/login.php
- Driver: https://bofetoda-app.onrender.com/Driver_BOFETODA/driver-login.php

---

## 🎉 Done!

Your application is now running with:
- ✅ Render (web hosting)
- ✅ Aiven MySQL (database)
- ✅ SSL/HTTPS enabled
- ✅ Automatic backups

---

## 💰 Cost Breakdown

### Option 1: TOTALLY FREE 🎉
- **Render Free Tier:** $0 (sleeps after 15min)
- **Aiven Free Tier:** $0 (forever!)
- **Total: $0/month** ✅

### Option 2: ALWAYS-ON (Best Value)
- **Render Starter:** $7/month (always-on)
- **Aiven Free Tier:** $0 (forever!)
- **Total: $7/month** ✅

**Aiven Free Tier Includes:**
- ✅ 1GB storage (enough for TODA app!)
- ✅ 1GB RAM, 1 CPU
- ✅ True MySQL 8.0
- ✅ Daily backups
- ✅ SSL/TLS encryption
- ✅ No credit card required
- ✅ No time limits

---

## 🐛 Common Issues

**SSL Connection Error?**
- Make sure both `db.php` files are updated (I already did this for you)

**Wrong Credentials?**
- Copy credentials directly from Aiven console (no spaces)
- Use `avnadmin` as username

**Timeout?**
- Check Aiven service is "Running" in console
- Verify port number is correct

---

## 📋 Quick Checklist

- [ ] Aiven account created
- [ ] MySQL service running
- [ ] Database `bofetoda` created
- [ ] Credentials saved
- [ ] Schema imported
- [ ] Code pushed to GitHub
- [ ] Render service created
- [ ] Environment variables set
- [ ] Site loads successfully
- [ ] Can log in (database works)

---

**Need detailed help?** See [AIVEN_DEPLOYMENT_GUIDE.md](AIVEN_DEPLOYMENT_GUIDE.md)
