# ⚡ Quick Start - Deploy in 10 Minutes

Follow these steps to deploy your BOFETODA application to Render.com.

---

## ✅ Step 1: Set Up Database (5 min)

1. Go to **https://planetscale.com** → Sign up (free)
2. Create new database: `bofetoda`
3. Click "Connect" → "Create password"
4. **COPY AND SAVE** these credentials:
   ```
   Host: xxxxx.psdb.cloud
   Username: xxxxx
   Password: pscale_pw_xxxxx
   ```
5. Import your database schema (in PlanetScale Console)

---

## ✅ Step 2: Push Code to GitHub (2 min)

```powershell
# In your project folder
cd "e:\Users\Judd\Downloads\toda\BTODATGinc"

# Initialize git
git init
git add .
git commit -m "Initial deployment"

# Create repo on GitHub.com, then:
git remote add origin https://github.com/YOUR_USERNAME/bofetoda-toda.git
git push -u origin main
```

---

## ✅ Step 3: Deploy to Render (3 min)

1. Go to **https://render.com** → Sign up with GitHub
2. Click **"New +"** → **"Web Service"**
3. Connect your repository: `bofetoda-toda`
4. Configure:
   ```
   Name: bofetoda-app
   Runtime: PHP
   Start Command: php -S 0.0.0.0:$PORT -t .
   Plan: Free (or Starter for always-on)
   ```
5. Add **Environment Variables**:
   ```
   DB_HOST = [Your PlanetScale Host]
   DB_PORT = 3306
   DB_NAME = bofetoda
   DB_USER = [Your PlanetScale Username]
   DB_PASS = [Your PlanetScale Password]
   ```
6. Click **"Create Web Service"**

---

## ✅ Step 4: Test Your Site

After deployment completes (~2-5 minutes):

**Your site URL:** `https://bofetoda-app.onrender.com`

- **Landing:** https://bofetoda-app.onrender.com/
- **Admin:** https://bofetoda-app.onrender.com/Admin_BOFETODA/login.php
- **Driver:** https://bofetoda-app.onrender.com/Driver_BOFETODA/driver-login.php

---

## 🎉 Done!

Your application is now live!

### Need Help?
- See full guide: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
- Check logs in Render dashboard if errors occur
- Verify database credentials are correct

---

## 💡 Pro Tips

- **Free tier sleeps** after 15 min inactivity (slow first load)
- Upgrade to **Starter ($7/mo)** for always-on
- Add **persistent disk** for uploads:
  - Render Dashboard → Your Service → "Disks"
  - Mount path: `/opt/render/project/src/Driver_BOFETODA/uploads`
  - Size: 1GB

---

## 🐛 Common Issues

**Database connection fails?**
- Double-check credentials in Render environment variables
- Ensure PlanetScale database is active

**Site shows error?**
- Check Render logs (Dashboard → Logs tab)
- Look for PHP errors

**Uploads don't work?**
- Add persistent disk (see Pro Tips above)
