# BOFETODA - Tricycle Membership Permit Management System

A web-based management system for Bocaue Federation of Tricycle Operators and Drivers Association Transport Group Inc. (BOFETODA), Municipality of Bocaue, Bulacan.

## 🎯 Features

### Admin Portal
- Dashboard with analytics
- Driver management (add, edit, archive)
- Application validation and approval workflow
- Renewal announcements management
- **General announcements with multi-image support** ✨ NEW
- Reports and activity logs
- Document verification system
- Forgot password with security questions

### Driver Portal
- Member login and profile management
- Renewal application submission
- Document upload (License, OR/CR, Payment Receipt, Photo)
- Application status tracking
- Notifications system
- **General announcements feed with image gallery** ✨ NEW
- Forgot password with security questions

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+ (Aiven MySQL in production)
- **Storage**: Cloudflare R2 (with local fallback)
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Architecture**: Monolithic PHP application
- **Deployment**: Render.com (Docker)

## 📁 Project Structure

```
BTODATGinc/
├── Admin_BOFETODA/          # Admin portal files
│   ├── login.php            # Admin authentication
│   ├── dashboard.php        # Admin dashboard
│   ├── driver_list.php      # Driver management
│   ├── validation_tools.php # Document validation
│   ├── approval_workflow.php # President approval
│   ├── announcements.php    # Renewal management
│   ├── manage_announcements.php # General announcements CRUD
│   ├── reports.php          # Reporting system
│   ├── forgot_password.php  # Password recovery
│   ├── r2-storage.php       # Cloudflare R2 integration
│   └── config.php           # Core configuration
│
├── Driver_BOFETODA/         # Driver portal files
│   ├── driver-login.php     # Driver authentication
│   ├── driver-home.php      # Driver dashboard
│   ├── driver-profile.php   # Profile management
│   ├── driver-renewal.php   # Renewal application
│   ├── driver-announcements.php # General announcements page
│   ├── driver-forgot-password.php # Password recovery
│   └── uploads/             # Document storage
│
└── index.php                # Landing page
```

## 🚀 Deployment

### Production Deployment (Render + Aiven MySQL + Cloudflare R2)

See comprehensive guides:
- **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** - Complete Render.com deployment instructions
- **[AIVEN_DEPLOYMENT_GUIDE.md](AIVEN_DEPLOYMENT_GUIDE.md)** - Aiven MySQL setup guide
- **[CLOUDFLARE_R2_SETUP.md](CLOUDFLARE_R2_SETUP.md)** - R2 storage integration (31 pages)
- **[R2_DEPLOYMENT_CHECKLIST.md](R2_DEPLOYMENT_CHECKLIST.md)** - Step-by-step deployment checklist

**Live Application**: https://bofetoda-app.onrender.com

### Quick Local Development Setup

1. **Requirements:**
   - PHP 7.4 or higher
   - MySQL 5.7+ or MariaDB 10.3+
   - Apache/Nginx web server

2. **Setup:**
   ```bash
   # Clone the repository
   git clone https://github.com/juddrussel/bofetoda-toda.git
   cd bofetoda-toda
   
   # Copy environment file
   cp .env.example .env
   
   # Edit .env with your database credentials
   nano .env
   
   # Install dependencies (for R2 storage)
   composer install
   
   # Import database schema
   mysql -u root -p bofetoda < database_schema.sql
   
   # Run security questions migration
   mysql -u root -p bofetoda < add_security_questions_safe.sql
   
   # Run announcements migration
   mysql -u root -p bofetoda < add_general_announcements.sql
   
   # Start PHP development server
   php -S localhost:8000
   ```

3. **Access:**
   - Landing: http://localhost:8000
   - Admin: http://localhost:8000/Admin_BOFETODA/login.php
   - Driver: http://localhost:8000/Driver_BOFETODA/driver-login.php

## 🔐 Security Notes

- Change default database credentials
- Use HTTPS in production (Render provides this automatically)
- Keep uploaded documents outside web root in production
- Regularly update PHP and dependencies
- Enable error logging, disable display_errors in production

## 📋 Database Configuration

The application uses environment variables for database connection:

```env
# Database (MySQL/MariaDB)
DB_HOST=your-database-host
DB_PORT=3306
DB_NAME=bofetoda
DB_USER=your-username
DB_PASS=your-password

# Cloudflare R2 Storage (Optional - falls back to local storage if not set)
R2_ACCESS_KEY_ID=your-r2-access-key
R2_SECRET_ACCESS_KEY=your-r2-secret-key
R2_ENDPOINT=account-id.r2.cloudflarestorage.com
R2_BUCKET_NAME=bofetoda-announcements
R2_PUBLIC_URL=https://pub-xxxxx.r2.dev
```

### Storage Strategy

- **Images**: Cloudflare R2 (persistent) with automatic fallback to local storage
- **Documents**: Local filesystem (`/uploads/documents/`)
- **Why R2?**: Render's free tier uses ephemeral storage - files are lost on deployment. R2 provides persistent storage at no cost (10 GB free tier).

See [CLOUDFLARE_R2_SETUP.md](CLOUDFLARE_R2_SETUP.md) for R2 configuration details.

## 📚 Documentation

### Deployment & Setup
- **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** - Render.com deployment with Docker
- **[AIVEN_DEPLOYMENT_GUIDE.md](AIVEN_DEPLOYMENT_GUIDE.md)** - Aiven MySQL database setup
- **[CLOUDFLARE_R2_SETUP.md](CLOUDFLARE_R2_SETUP.md)** - R2 storage integration guide (31 pages)
- **[R2_DEPLOYMENT_CHECKLIST.md](R2_DEPLOYMENT_CHECKLIST.md)** - Step-by-step deployment checklist
- **[DATABASE_COMPARISON.md](DATABASE_COMPARISON.md)** - MySQL hosting options comparison

### Feature Documentation
- **[SECURITY_QUESTIONS_GUIDE.md](SECURITY_QUESTIONS_GUIDE.md)** - Forgot password implementation
- **[ANNOUNCEMENTS_FEATURE_GUIDE.md](ANNOUNCEMENTS_FEATURE_GUIDE.md)** - General announcements feature (31 pages)

### Technical Reference
- **[R2_QUICK_REFERENCE.md](R2_QUICK_REFERENCE.md)** - R2 integration quick reference
- **[R2_INTEGRATION_SUMMARY.md](R2_INTEGRATION_SUMMARY.md)** - Technical analysis and cost breakdown

## 🐛 Known Issues & Solutions

1. **Ephemeral storage on Render**: ✅ Solved with Cloudflare R2 integration
2. **Upload folder permissions**: Ensure `uploads/` has write permissions (755)
3. **Session handling**: Works correctly on single-instance deployments
4. **File paths**: Use relative paths for portability

## 🎉 Recent Updates

### Version 2.1.0 (October 2026)
- ✨ Added Cloudflare R2 storage integration for announcement images
- ✨ Added General Announcements feature with multi-image support
- ✨ Added Forgot Password with security questions for Admin and Driver
- 🔧 Implemented password hashing (bcrypt) for security
- 📚 Comprehensive documentation (100+ pages total)
- 🚀 Deployed to Render.com with Aiven MySQL

### Version 2.0.0 (Initial Production)
- 🚀 Deployed to Render.com with Docker
- 🗄️ Migrated to Aiven MySQL (cloud database)
- 🔐 Enhanced security with password hashing
- 📊 Dashboard analytics and reporting
- 📄 Document verification system

## 📄 License

Proprietary - Bocaue TODA Inc.

## 👥 Support

For technical support or questions about the system:
- **GitHub Issues**: https://github.com/juddrussel/bofetoda-toda/issues
- **Admin Office**: BOFETODA Administrative Office, Municipality of Bocaue, Bulacan

## 🤝 Contributing

This is a proprietary system for BOFETODA. For contributions or modifications, contact the administrative office.

## 📊 Project Statistics

- **Total Files**: 50+ PHP files
- **Documentation**: 100+ pages across 10 guides
- **Database Tables**: 15+ tables
- **Lines of Code**: 10,000+ lines
- **Features**: 30+ features across admin and driver portals

---

**Built with ❤️ for BOFETODA**  
**Municipality of Bocaue, Bulacan** | **Bocaue TODA Inc.**
