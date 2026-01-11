# WordPress Server Access & CLI Guide

## Server Environment Overview

### Docker-based Local WordPress Setup
- **WordPress URL**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **Container Name**: `wordpress_site`
- **Database Container**: `wordpress_db`
- **Database**: MySQL 8.0

### Server Status Commands
```bash
# Check if containers are running
docker ps

# Start the WordPress environment
docker-compose up -d

# Stop the WordPress environment
docker-compose down

# View WordPress logs
docker logs wordpress_site

# View database logs
docker logs wordpress_db
```

## CLI Access Methods

### 1. WordPress CLI (WP-CLI) Access
```bash
# Access WordPress container shell
docker exec -it wordpress_site bash

# Run WP-CLI commands directly
docker exec -it wordpress_site wp --info

# Common WP-CLI commands
docker exec -it wordpress_site wp user list
docker exec -it wordpress_site wp plugin list
docker exec -it wordpress_site wp theme list
docker exec -it wordpress_site wp post list
```

### 2. Database CLI Access
```bash
# Access MySQL container
docker exec -it wordpress_db mysql -u root -p
# Password: rootpassword

# Direct database queries
docker exec -it wordpress_db mysql -u root -prootpassword wordpress -e "SHOW TABLES;"
```

### 3. File System Access
```bash
# Access WordPress files
docker exec -it wordpress_site ls -la /var/www/html

# Edit files (if needed)
docker exec -it wordpress_site nano /var/www/html/wp-config.php

# Copy files to/from container
docker cp file.php wordpress_site:/var/www/html/
docker cp wordpress_site:/var/www/html/wp-config.php ./
```

## WordPress Admin Access

### Default Admin Credentials
- **URL**: http://localhost:8080/wp-admin
- **Username**: admin
- **Password**: (set during WordPress installation)
- **Email**: your-email@example.com

### Admin Capabilities
- Full WordPress admin access
- WooCommerce management
- Plugin/theme installation
- User management
- Database access via phpMyAdmin

## Container Management

### Essential Docker Commands
```bash
# View container status
docker-compose ps

# Restart specific service
docker-compose restart wordpress

# View resource usage
docker stats

# Clean up (removes containers and data)
docker-compose down -v

# Rebuild containers
docker-compose up -d --build
```

### Volume Management
- **WordPress Data**: `wordpress_data` volume
- **Database Data**: `db_data` volume
- **Persistent Storage**: Data survives container restarts

## Troubleshooting Access Issues

### WordPress Not Loading
```bash
# Check container status
docker ps

# Restart WordPress container
docker-compose restart wordpress

# Check WordPress logs
docker logs wordpress_site --tail=50
```

### Database Connection Issues
```bash
# Check database container
docker logs wordpress_db --tail=50

# Test database connection
docker exec -it wordpress_db mysql -u root -prootpassword -e "SELECT 1;"
```

### Permission Issues
```bash
# Fix WordPress file permissions
docker exec -it wordpress_site chown -R www-data:www-data /var/www/html
docker exec -it wordpress_site chmod -R 755 /var/www/html
```

## Security Notes

- **Local Development Only**: This setup is for local development
- **Default Passwords**: Change default passwords for production use
- **Network Access**: Containers are accessible only on localhost
- **Data Persistence**: Database and WordPress files persist between restarts