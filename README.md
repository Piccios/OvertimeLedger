# Overtime Hours Manager

A modern, responsive web application for tracking overtime hours across multiple companies. Built with PHP, MySQL, and Bootstrap.

## Features

- ✅ **Dynamic Company Management** - Add, edit, and delete companies with custom colors
- ✅ **Multi-language Support** - Italian and English interfaces
- ✅ **Excel Export** - Generate professional Excel reports
- ✅ **Responsive Design** - Works on desktop, tablet, and mobile
- ✅ **Real-time Statistics** - Weekly and monthly summaries
- ✅ **Modern UI** - Beautiful gradient design with smooth animations

## Installation

### Prerequisites

- PHP 8.1 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for PhpSpreadsheet dependency)

### Setup Instructions

1. **Clone or download the project**
   ```bash
   git clone <your-repo-url>
   cd straordinari
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure the database**
   - Create a MySQL database named `straordinari`
   - Import the `database.sql` file to create tables
   - Run the `update_database.sql` file to add the color column

4. **Configure database connection**
   - Edit `config.php` with your database credentials
   - Update the database host, name, username, and password

5. **Set up your web server**
   - Point your web server to the project directory
   - Ensure PHP has write permissions for the directory

## Database Setup

### Initial Setup
```sql
-- Import the main database structure
mysql -u your_username -p straordinari < database.sql

-- Add the color column for companies
mysql -u your_username -p straordinari < update_database.sql
```

### Manual Database Creation
If you prefer to create the database manually:

```sql
CREATE DATABASE straordinari;
USE straordinari;

-- Companies table
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#3b82f6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Extra hours table
CREATE TABLE extra_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    date DATE NOT NULL,
    hours DECIMAL(4,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_company_date (company_id, date)
);

-- Indexes for performance
CREATE INDEX idx_extra_hours_date ON extra_hours(date);
CREATE INDEX idx_extra_hours_company ON extra_hours(company_id);
```

## Usage

### Getting Started

1. **Access the application** through your web browser
2. **Add your first company** by clicking "Manage Companies"
3. **Start tracking overtime** by adding records in the main interface

### Adding Companies

1. Click "Manage Companies" in the top-left corner
2. Fill in the company name and choose a color
3. Click the save button
4. Companies can be edited or deleted (if they have no records)

### Tracking Overtime

1. Select a company from the dropdown
2. Choose the date
3. Enter the number of hours (supports decimals like 2.5)
4. Add an optional description
5. Click save

### Exporting Data

- Click "Export Excel" in the monthly summary section
- The file will be downloaded with the current month's data
- Includes detailed records and company summaries

### Language Switching

- Use the language selector in the top-right corner
- Choose between Italian (🇮🇹) and English (🇺🇸)
- Language preference is maintained during navigation

## File Structure

```
straordinari/
├── index.php              # Main application interface
├── manage_companies.php   # Company management interface
├── export_excel.php       # Excel export functionality
├── config.php             # Database configuration
├── translations.php       # Language translations
├── database.sql           # Initial database structure
├── update_database.sql    # Database updates for colors
├── composer.json          # PHP dependencies
├── vendor/                # Composer packages
└── README.md             # This file
```

## Customization

### Adding New Languages

1. Edit `translations.php`
2. Add a new language array (e.g., `'es' => [...]`)
3. Add the language option to the UI

### Styling

- Main styles are in the `<style>` sections of each PHP file
- Uses Bootstrap 5 for responsive design
- Custom gradients and animations included

### Database Schema

The application uses a simple two-table structure:
- **companies**: Stores company information and colors
- **extra_hours**: Stores overtime records with foreign key to companies

## Security Features

- SQL injection protection with prepared statements
- XSS protection with `htmlspecialchars()`
- Input validation and sanitization
- Foreign key constraints for data integrity

## Browser Support

- Chrome/Chromium (recommended)
- Firefox
- Safari
- Edge

## Troubleshooting

### Common Issues

1. **Composer dependency issues**
   - Ensure PHP version is 8.1+
   - Run `composer update` to refresh dependencies

2. **Database connection errors**
   - Check `config.php` credentials
   - Ensure MySQL service is running
   - Verify database exists

3. **Excel export not working**
   - Check PhpSpreadsheet installation
   - Ensure proper file permissions
   - Verify PHP has enough memory

### Performance Tips

- Add database indexes for large datasets
- Consider caching for frequently accessed data
- Optimize images and assets for faster loading

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For issues and questions:
- Check the troubleshooting section above
- Review the code comments for implementation details
- Create an issue in the repository

---

**Happy overtime tracking!** 🕐✨ 