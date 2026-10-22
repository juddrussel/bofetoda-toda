# BOFETODA - Tricycle Membership Permit Management System

A web-based management system for Bocaue Federation of Tricycle Operators and Drivers Association Transport Group Inc. (BOFETODA), Municipality of Bocaue, Bulacan.

## 🎯 Features

### Admin Portal
- Dashboard with analytics
- Driver management (add, edit, archive)
- Application validation and approval workflow
- Renewal announcements management
- Reports and activity logs
- Document verification system

### Driver Portal
- Member login and profile management
- Renewal application submission
- Document upload (License, OR/CR, Payment Receipt, Photo)
- Application status tracking
- Notifications system

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Architecture**: Monolithic PHP application

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
│   ├── reports.php          # Reporting system
│   └── config.php           # Core configuration
│
├── Driver_BOFETODA/         # Driver portal files
│   ├── driver-login.php     # Driver authentication
│   ├── driver-home.php      # Driver dashboard
│   ├── driver-profile.php   # Profile management
│   ├── driver-renewal.php   # Renewal application
│   └── uploads/             # Document storage
│
└── index.php                # Landing page
```

## 🚀 Deployment

See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for complete instructions on deploying to Render.com with PlanetScale MySQL.

### Quick Start (Local Development)

1. **Requirements:**
   - PHP 7.4 or higher
   - MySQL 5.7+ or MariaDB 10.3+
   - Apache/Nginx web server

2. **Setup:**
   ```bash
   # Clone or download the repository
   cd BTODATGinc
   
   # Copy environment file
   cp .env.example .env
   
   # Edit .env with your database credentials
   
   # Import database schema (create your schema.sql first)
   mysql -u root -p bofetoda < database/schema.sql
   
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

```
DB_HOST - Database host
DB_PORT - Database port (default: 3306)
DB_NAME - Database name (default: bofetoda)
DB_USER - Database username
DB_PASS - Database password
```

## 🐛 Known Issues

1. **Upload folder**: On some hosting, uploads may need special permissions
2. **Session handling**: May need adjustment for load-balanced setups
3. **File paths**: Absolute paths may need adjustment depending on hosting

## 📄 License

Proprietary - Bocaue TODA Inc.

## 👥 Support

For technical support or questions about the system, contact the BOFETODA administrative office.

---

**Municipality of Bocaue, Bulacan** | **Bocaue TODA Inc.**
