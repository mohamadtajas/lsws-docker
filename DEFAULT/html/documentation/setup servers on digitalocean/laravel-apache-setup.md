
# Laravel and Apache Server Setup with PHP 8.0

This document provides a step-by-step guide on how to install and configure a Laravel application on an Apache server with PHP 8.0. It also includes how to properly configure Apache for the Laravel project using the server's IP address.

## Steps to Set Up Laravel on Apache

### 1. Update and Upgrade the System
Ensure your system is up-to-date with the latest packages and security patches.

```bash
sudo apt update
sudo apt upgrade -y
```

### 2. Install Apache Web Server
Install Apache, the web server that will serve your Laravel application.

```bash
sudo apt install apache2 -y
```

### 3. Install PHP 8.0 and Necessary PHP Extensions
Install PHP 8.0 along with required extensions for Laravel to work efficiently.

```bash
sudo add-apt-repository ppa:ondrej/php

sudo apt install php8.0 php8.0-cli php8.0-fpm php8.0-mysql php8.0-curl php8.0-mbstring php8.0-xml php8.0-zip php8.0-intl php8.0-gd -y
```

### 4. Install Composer
Composer is required to manage PHP dependencies for Laravel. Download and install Composer.

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 5. Transfer Files to the Server
Use `scp` to transfer local files to the server. Example command:

```bash
scp path/to/local/file root@ServerIp:path/to/server
```

### 6. Set Permissions
Ensure the correct file permissions are set for the Laravel project.

```bash
chmod -R 777 /path/to/laravel/directory
```

### 7. Install Zip Utilities
Install `zip` and `unzip` for managing compressed files on the server.

```bash
sudo apt install zip unzip
```

### 8. Extract Zip Files
If your project is zipped, extract the files.

```bash
unzip filename.zip -d /path/to/extract
```

### 9. Configure Apache for Laravel
You need to configure Apache to serve your Laravel application.

1. Open the Apache configuration file for the Laravel site:

    ```bash
    sudo nano /etc/apache2/sites-available/laravel-app.conf
    ```

2. Add the following configuration, replacing `yourdomain.com` with your server's domain or IP address:

    ```apache
    <VirtualHost *:80>
        ServerName yourdomain.com
        DocumentRoot /var/www/html/laravel-app/public
        <Directory /var/www/html/laravel-app>
            AllowOverride All
            Require all granted
        </Directory>
        ErrorLog ${APACHE_LOG_DIR}/laravel-error.log
        CustomLog ${APACHE_LOG_DIR}/laravel-access.log combined
    </VirtualHost>
    ```

3. Install additional Apache modules and enable the Laravel site:

    ```bash
    sudo apt install libapache2-mod-php8.0
    sudo a2ensite laravel-app
    sudo a2enmod rewrite
    sudo systemctl restart apache2
    ```

4. Remove the default `index.html` if it exists:

    ```bash
    rm /var/www/html/index.html
    sudo systemctl restart apache2
    ```

### 10. Install MySQL Database
Install MySQL to handle the database for your Laravel application.

```bash
sudo apt install mysql-server -y
```

### 11. Configure UFW Firewall for Apache
Allow Apache through the firewall and enable UFW.

```bash
sudo ufw allow 'Apache Full'
sudo ufw enable
```

### 12. Install Additional PHP Modules
Ensure you have all required PHP modules installed.

```bash
sudo apt update
sudo apt install php libapache2-mod-php php-mbstring php-xml php-curl php-zip
sudo systemctl restart apache2
```

### Example Apache Virtual Host Configuration for Laravel
For certain servers, especially when working with IP addresses, this is how the Apache configuration should look like.

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName 209.38.248.184
    DocumentRoot /var/www/html/laravel-app/public

    <Directory /var/www/html/laravel-app/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/laravel-error.log
    CustomLog ${APACHE_LOG_DIR}/laravel-access.log combined
</VirtualHost>
```

### Conclusion
This guide helps you set up a Laravel application on an Apache server with PHP 8.0 and MySQL, providing proper Apache configuration for the Laravel app using the server’s IP address.

### Add this rows to db sql

```sql
SET @ORIG_SQL_REQUIRE_PRIMARY_KEY = @@SQL_REQUIRE_PRIMARY_KEY;
SET SQL_REQUIRE_PRIMARY_KEY = 0;
```
