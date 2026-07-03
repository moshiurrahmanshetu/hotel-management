# Hotel & Resort Management System

A comprehensive hotel and resort management system built with PHP, MySQL, and Bootstrap 5.

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Apache with mod_rewrite enabled
- XAMPP (for local development)

## Installation

### 1. Clone or Download

Clone the repository or download the source files to your web server directory.

### 2. Configure Environment

Copy `.env.example` to `.env` and configure your environment:

```bash
cp .env.example .env
```

Edit `.env` and update the following values:

```env
APP_NAME="Hotel & Resort Management System"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/hotel-management
APP_TIMEZONE=UTC

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=hotel_management
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Import Database

Import the SQL files in the following order:

1. `database/hotel_structure.sql` - Core tables (users, roles, permissions, properties, buildings, floors)
2. `database/rooms.sql` - Rooms module tables
3. `database/room_media.sql` - Room media tables
4. `database/room_rates.sql` - Room pricing tables
5. `database/custom_fields.sql` - Custom fields tables
6. `database/master_data.sql` - Master data tables (if available)

### 4. Set Permissions

Ensure the following directories are writable:

- `logs/`
- `uploads/`
- `uploads/rooms/`
- `uploads/users/`
- `uploads/temp/`
- `cache/`

### 5. Access the Application

Open your browser and navigate to:

```
http://localhost/hotel-management/
```

You will be redirected to the login page.

## Default Credentials

After importing the database, you can use the default admin account:

- **Email:** admin@hotel.com
- **Password:** admin123

**Important:** Change the default password after first login.

## Directory Structure

```
hotel-management/
├── api/                    # API endpoints
├── assets/                 # Static assets (CSS, JS, images)
├── config/                 # Configuration files
│   ├── config.php         # Main configuration
│   └── database.php       # Database configuration
├── database/               # SQL files
├── includes/               # Include files
│   ├── auth.php           # Authentication functions
│   ├── env.php            # Environment loader
│   ├── footer.php         # Footer template
│   ├── header.php         # Header template
│   ├── helpers.php        # Helper functions
│   ├── install.php        # Installation checker
│   ├── security.php       # Security functions
│   ├── sidebar.php        # Sidebar navigation
│   └── topbar.php         # Top bar template
├── logs/                   # Application logs
├── modules/                # Application modules
│   ├── bookings/          # Booking management
│   ├── custom-fields/     # Custom fields management
│   ├── floors/            # Floor management
│   ├── master-data/       # Master data management
│   ├── permissions/       # Permission management
│   ├── properties/        # Property management
│   ├── reservations/      # Reservation management
│   ├── room-rates/        # Room pricing management
│   ├── rooms/             # Room management
│   ├── roles/             # Role management
│   ├── settings/          # Settings management
│   ├── staff/             # Staff management
│   └── users/             # User management
├── uploads/                # Upload directories
├── .env                    # Environment configuration
├── .env.example           # Environment template
├── .gitignore              # Git ignore rules
├── .htaccess               # Apache configuration
├── 403.php                 # 403 error page
├── 404.php                 # 404 error page
├── dashboard.php           # Dashboard
├── forgot-password.php     # Forgot password
├── index.php               # Index page
├── login.php               # Login page
├── logout.php              # Logout page
└── reset-password.php      # Reset password
```

## Features

### Core Features
- **Authentication & Authorization** - Role-based access control (RBAC)
- **User Management** - Create, edit, delete users
- **Role & Permission Management** - Flexible permission system
- **Settings Management** - System configuration

### Hotel Structure
- **Property Management** - Manage multiple properties
- **Building Management** - Manage buildings within properties
- **Floor Management** - Manage floors within buildings

### Room Management
- **Room Management** - Full CRUD for rooms
- **Room Categories & Types** - Master data-driven
- **Amenities Management** - Assign amenities to rooms
- **Room Gallery** - Image upload and management
- **Room Notes** - Add notes to rooms
- **Custom Fields** - Dynamic custom fields for rooms

### Room Pricing
- **Rate Plans** - Create reusable rate plans
- **Room Rates** - Assign rate plans to rooms with specific pricing
- **Flexible Pricing** - Base price, weekend price, extra adult/child pricing
- **Tax Configuration** - Tax included/excluded options

### Master Data Engine
- **Dynamic Groups** - Create custom data groups
- **Dynamic Items** - Add items to groups with icons and colors
- **Reusable Across Modules** - Use master data in any module

### Custom Fields Engine
- **Dynamic Fields** - Add custom fields to any module
- **Multiple Field Types** - Text, number, select, checkbox, date, file, etc.
- **Validation Rules** - Configure field validation
- **Conditional Logic** - Show/hide fields based on conditions

## Security

- CSRF protection on all forms
- PDO prepared statements for SQL injection prevention
- Password hashing with PHP's password_hash()
- Session management with secure cookies
- XSS protection with output escaping
- SQL injection prevention with prepared statements
- File upload validation

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| APP_NAME | Application name | Hotel & Resort Management System |
| APP_ENV | Environment (development/production) | development |
| APP_DEBUG | Debug mode | true |
| APP_URL | Application URL | http://localhost/hotel-management |
| APP_TIMEZONE | Application timezone | UTC |
| DB_HOST | Database host | localhost |
| DB_PORT | Database port | 3306 |
| DB_DATABASE | Database name | hotel_management |
| DB_USERNAME | Database username | root |
| DB_PASSWORD | Database password | (empty) |
| UPLOAD_PATH | Upload directory | uploads |
| SESSION_NAME | Session cookie name | HOTEL_SESSION |
| SESSION_LIFETIME | Session lifetime (seconds) | 3600 |

## Troubleshooting

### Installation Check Fails

If you see an installation error page, check:

1. `.env` file exists in the root directory
2. Database credentials in `.env` are correct
3. Database server is running
4. Required directories exist and are writable

### Database Connection Failed

1. Verify MySQL/MariaDB is running
2. Check database credentials in `.env`
3. Ensure the database exists
4. Check if the database user has proper permissions

### Upload Not Working

1. Ensure `uploads/` directory exists and is writable
2. Check PHP upload_max_filesize and post_max_size settings
3. Verify file permissions

## Development

### Adding New Modules

1. Create module directory in `modules/`
2. Follow existing module structure
3. Use existing authentication and helpers
4. Implement proper security (CSRF, validation)
5. Add navigation link in `includes/sidebar.php`

### Adding Custom Fields

1. Go to Custom Fields module
2. Create new field for desired module
3. Configure field type and validation
4. Field will automatically appear in module forms

## License

Proprietary - All rights reserved.

## Support

For support and issues, contact the development team.