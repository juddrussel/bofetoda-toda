# 🚀 Deploy BOFETODA to Render.com

Complete step-by-step guide to deploy your PHP TODA application to Render.

---

## 📋 Prerequisites

1. **GitHub Account** (free) - https://github.com
2. **Render Account** (free) - https://render.com
3. **MySQL Database** - We'll use PlanetScale (free tier)
4. **Git installed** on your computer

---

## 🗄️ PART 1: Set Up MySQL Database (PlanetScale)

### Why PlanetScale?
- Free tier includes 5GB storage
- MySQL compatible
- Easy to set up
- Works great with Render

### Steps:

1. **Create PlanetScale Account**
   - Go to: https://planetscale.com
   - Sign up (free)
   - Verify your email

2. **Create Database**
   - Click "New Database"
   - Name: `bofetoda`
   - Region: Choose closest to you (e.g., `AWS us-east-1`)
   - Click "Create database"

3. **Create Password**
   - Go to "Settings" → "Passwords"
   - Click "New password"
   - Name it: `render-production`
   - **SAVE THESE CREDENTIALS** (you'll need them):
     ```
     Host: xxxxxxx.us-east-1.psdb.cloud
     Username: xxxxxxxxxxxxx
     Password: pscale_pw_xxxxxxxxxxxxx
     Database: bofetoda
     ```

4. **Import Your Database Schema**
   - Go to "Console" tab
   - Run your SQL schema/tables creation scripts
   - OR use PlanetScale CLI:
     ```bash
     pscale shell bofetoda main < your_schema.sql
     ```

---

## 📦 PART 2: Prepare Your Code for Deployment

### A. Initialize Git Repository

Open PowerShell in your project folder:

```powershell
cd "e:\Users\Judd\Downloads\toda\BTODATGinc"

# Initialize git
git init

# Add all files
git add .

# Commit
git commit -m "Initial commit - BOFETODA application"
```

### B. Push to GitHub

1. **Create GitHub Repository**
   - Go to https://github.com/new
   - Repository name: `bofetoda-toda`
   - Make it **Private** (recommended for production apps)
   - Don't initialize with README (you already have code)
   - Click "Create repository"

2. **Push Your Code**
   ```powershell
   # Link to GitHub (replace YOUR_USERNAME)
   git remote add origin https://github.com/YOUR_USERNAME/bofetoda-toda.git
   
   # Push code
   git branch -M main
   git push -u origin main
   ```

---

## 🌐 PART 3: Deploy to Render

### A. Create Render Account

1. Go to https://render.com
2. Sign up with GitHub (easier integration)
3. Authorize Render to access your repositories

### B. Create Web Service

1. **Click "New +"** → Select "Web Service"

2. **Connect Repository**
   - Find: `bofetoda-toda`
   - Click "Connect"

3. **Configure Service**
   ```
   Name: bofetoda-app
   Region: Oregon (US West) or closest to you
   Branch: main
   Root Directory: (leave blank)
   Runtime: PHP
   Build Command: (leave blank)
   Start Command: php -S 0.0.0.0:$PORT -t .
   ```

4. **Select Plan**
   - Choose "Free" (for testing) or "Starter" ($7/mo)
   - Note: Free tier sleeps after inactivity

5. **Advanced Settings** → Add Environment Variables:
   ```
   DB_HOST = [Your PlanetScale Host]
   DB_PORT = 3306
   DB_NAME = bofetoda
   DB_USER = [Your PlanetScale Username]
   DB_PASS = [Your PlanetScale Password]
   ```

6. **Click "Create Web Service"**

### C. Wait for Deployment
- First deploy takes 2-5 minutes
- Watch the logs for any errors
- Once you see "Your service is live 🎉", it's ready!

---

## 🔧 PART 4: Post-Deployment Configuration

### A. Set Up URLs

Your Render URL will be: `https://bofetoda-app.onrender.com`

### B. Test Access

1. **Admin Portal:**
   ```
   https://bofetoda-app.onrender.com/Admin_BOFETODA/login.php
   ```

2. **Driver Portal:**
   ```
   https://bofetoda-app.onrender.com/Driver_BOFETODA/driver-login.php
   ```

### C. Fix File Upload Issues

If uploads don't work, you need persistent storage:

1. Go to Render Dashboard → Your Service
2. Click "Environment" → "Disks"
3. Add disk:
   ```
   Name: uploads
   Mount Path: /opt/render/project/src/Driver_BOFETODA/uploads
   Size: 1GB (free tier)
   ```

---

## 🐛 Troubleshooting

### Issue: "Connection Refused" Database Error

**Solution:**
- Check PlanetScale credentials in Render environment variables
- Make sure PlanetScale database is not sleeping (free tier limitation)
- Verify SSL requirement (PlanetScale requires SSL)

### Issue: "File Upload Fails"

**Solution:**
- Add persistent disk (see Part 4C)
- Check folder permissions
- Verify upload path in code matches disk mount path

### Issue: "Application Error" or "500 Error"

**Solution:**
- Check Render logs: Dashboard → Your Service → "Logs" tab
- Look for PHP errors
- Common issues:
  - Missing PHP extensions
  - Path issues (use absolute paths)
  - Database connection problems

### Issue: "Site is Slow" (Free Tier)

**Solution:**
- Free tier sleeps after 15 min inactivity
- Upgrade to Starter plan ($7/mo) for always-on
- Or use a service like UptimeRobot to ping it every 5 minutes

### Issue: CSS/Images Not Loading

**Solution:**
- Check that paths are relative
- Verify files are in the repository
- Check browser console for 404 errors

---

## 💰 Cost Breakdown

### Free Option:
- **Render Free Tier**: $0 (sleeps after inactivity)
- **PlanetScale Free Tier**: $0 (5GB storage)
- **Total: $0/month** ✅

### Production Option:
- **Render Starter**: $7/month (always on, custom domain)
- **PlanetScale Scaler**: $29/month (better performance) OR stay free
- **Total: $7-36/month**

---

## 🎯 Quick Checklist

- [ ] PlanetScale database created
- [ ] Database credentials saved
- [ ] Code pushed to GitHub
- [ ] Render web service created
- [ ] Environment variables configured
- [ ] Database schema imported
- [ ] Admin portal accessible
- [ ] Driver portal accessible
- [ ] Test login works
- [ ] File uploads working

---

## 🆘 Need Help?

Common issues:
1. **Can't push to GitHub?** Make sure you have Git installed and authenticated
2. **Render build failing?** Check the build logs for specific errors
3. **Database not connecting?** Verify PlanetScale credentials are correct
4. **Getting SSL errors?** PlanetScale requires SSL - add `?ssl=true` to connection string

---

## 📞 Support Resources

- **Render Docs**: https://render.com/docs
- **PlanetScale Docs**: https://planetscale.com/docs
- **PHP on Render**: https://render.com/docs/deploy-php

---

## 🎉 You're Done!

Your application should now be live at:
```
https://bofetoda-app.onrender.com
```

Share this URL with your users! 🚀
