# Audit Management CMS - Phase 5

A simple, professional Audit Management Content Management System built with vanilla PHP, MySQL, and Bootstrap 5.

## Phase 5 Features

- **Authentication System**: Secure login/logout with session management
- **User Management**: Admin-only CRUD operations for users
- **Role-Based Access Control**: Admin, Auditor, and Staff roles
- **Profile Management**: Users can update their profile and avatar
- **Password Management**: Secure password change functionality
- **Audit Management**: Create, view, edit, and manage audits with role-based access
- **Checklist Management**: Master checklist templates and audit-specific checklists
- **Findings & Issues**: Create, track, resolve, and close audit findings with role-based workflow
- **Documents / Evidence**: Upload, view, download, and manage audit evidence documents with secure file handling
- **Responsive Design**: Mobile-friendly with collapsible sidebar

## Tech Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript, Bootstrap 5
- **Backend**: Raw PHP (no frameworks), PDO for database operations
- **Database**: MySQL with InnoDB engine
- **Icons**: Bootstrap Icons

## Installation Steps

### 1. Import the Database

1. Open phpMyAdmin or your MySQL client
2. Create a new database (or use existing)
3. Import the SQL files in order:
   - `database/01_users_roles.sql`
   - `database/02_audits.sql`
   - `database/03_checklist.sql`
   - `database/04_findings.sql`
   - `database/05_documents.sql`
4. This will create the necessary tables and seed data

### 2. Configure Database Connection

Edit the file `config/db.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');      // Your database host
define('DB_NAME', 'audit_cms');      // Your database name
define('DB_USER', 'root');           // Your database username
define('DB_PASS', '');               // Your database password
```

### 3. Configure Base URL (if needed)

Edit the file `config/constants.php` if your project is not in the root directory:

```php
define('BASE_URL', '/audit-mgt');    // Update if needed
```

**Note**: Currently configured for XAMPP Apache server at `http://localhost/audit-mgt`.

### 4. Set File Permissions

Ensure the following directories are writable by the web server:

- `uploads/avatars/` - for user avatar uploads

### 5. Access the Application

Open your browser and navigate to: `http://localhost/audit-mgt`

## Default Login Credentials

- **Username**: `admin`
- **Password**: `Admin@123`

⚠️ **Important**: Change the default admin password after first login!

## Project Structure

```
/audit-mgt
  /config
    - db.php                # Database connection
    - constants.php         # Application constants
  /includes
    - header.php            # Page header
    - sidebar.php           # Navigation sidebar
    - footer.php            # Page footer
    - auth_check.php        # Authentication guard
    - functions.php         # Helper functions
  /assets
    /css
      - style.css           # Custom styles
    /js
      - script.js           # JavaScript functionality
    /img
      - default-avatar.png  # Default user avatar
  /uploads
    /avatars                # User uploaded avatars
    /documents              # Audit evidence documents
  /database
    - 01_users_roles.sql   # Database schema and seed data
    - 02_audits.sql        # Audit management schema
    - 03_checklist.sql     # Checklist schema
    - 04_findings.sql      # Findings schema
    - 05_documents.sql     # Documents schema
  /modules
    /auth
      - login.php           # Login page
      - logout.php          # Logout handler
    /users
      - list.php            # User list (Admin only)
      - create.php          # Create user (Admin only)
      - edit.php            # Edit user (Admin only)
      - delete.php          # Deactivate user (Admin only)
      - profile.php         # User profile
      - change-password.php # Change password
    /audits
      - list.php            # Audit list
      - create.php          # Create audit (Admin/Auditor)
      - edit.php            # Edit audit (Admin/Auditor)
      - view.php            # View audit details
      - delete.php          # Soft delete redirect
    /checklist
      - manage.php          # Manage checklist templates (Admin only)
      - fill.php            # Fill audit checklist
    /findings
      - list.php            # Findings list
      - create.php          # Create finding (Admin/Auditor)
      - edit.php            # Edit finding (Admin/Auditor)
      - view.php            # View finding details
      - resolve.php         # Resolve finding
      - close.php           # Close finding
    /documents
      - list.php            # Documents list
      - upload.php          # Upload document
      - view.php            # View/download document
      - delete.php          # Delete document
  index.php                 # Entry point
  dashboard.php             # Main dashboard
  README.md                 # This file
```

## Security Features

- **SQL Injection Prevention**: All database queries use PDO prepared statements
- **XSS Prevention**: All output is escaped with htmlspecialchars()
- **Password Hashing**: Passwords are hashed using bcrypt (password_hash)
- **Session Security**: Secure session configuration with httponly cookies
- **Role-Based Access**: Pages are protected based on user roles

## User Roles

1. **Admin**: Full access to all features including user management and audit management
2. **Auditor**: Can create and manage audits assigned to them
3. **Staff**: View-only access to audits where they are assigned as auditor

## Browser Compatibility

- Chrome (recommended)
- Firefox
- Edge
- Safari

## Future Phases

This is Phase 5 of a 7-phase project. Future phases will include:

- Phase 6: Audit Review & Report
- Phase 7: Dashboard & Activity Log

## Support

For issues or questions, please refer to the project documentation or contact the development team.

---

**Version**: Phase 5  
**Last Updated**: 2026-08-08
