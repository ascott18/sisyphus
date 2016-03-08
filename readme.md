# EWU Senior Project - Faculty Book Ordering System


## Deployment Instructions

### Dependencies
First, ensure that all dependencies are installed:

* Apache 2.4
* PHP 5.6
* MySQL 5.6

### Application Configuration
Next, clone this repository to the web root of your apache installation. Then, go to the root of the project (where this file is located) and

* Run `php composer.phar install` to install all of the packages needed for this application.
* Copy `.env.example` to `.env`, and then modify the settings within.
    * `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` are the configuration for MySQL. Set these appropriately.
    * If deploying to production, set `APP_ENV=production` and `APP_DEBUG=false`
    * In order for email sending to function, the MAIL_ settings must be configured. 
        * Set `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` to an appropriate set of SMTP credentials.
    * If deploying to production, or if you need to test SSO, comment out `CAS_PRETEND_USER`.
    * Set `GOOGLE_API_KEY` to a valid Google API key for Google Books
        * This is used to get the cover thumbnails of books
        * Go to https://developers.google.com/books/docs/v1/using#APIKey and follow the instructions for "Public API access":
            * Go to https://console.developers.google.com/apis/credentials?project=_
            * Create a project
            * Create an API Key
            * When asked, choose to create a Server Key
            * Copy and paste the given key into the GOOGLE_API_KEY value in `.env`
    * Save and close the file.
* Run `php artisan key:generate`
    * This will populate APP_KEY in your `.env` file.
* Choose one of the following options to create the database:
    * If you would like to seed the database with data (highly recommended for testing, and crucial for an initial deployment), run `php artisan migrate:refresh --seed`. Be warned that this will drop all relevant tables and re-create them.
        * Don't worry if you see a lot of output while seeding that states things like "ELI 099-99 was not found" - this is normal.
    * Run `php artisan migrate` to simply generate the database without any seed data.
* Ensure that Apache has full permissions to the `storage` directory of the project.
    * This includes execute permission. Cached version of the compiled Laravel blade templates are stored here, and they need to be executable.
    * This directory contains compiled views, sessions (if the session driver is set to 'file'), and caches (if the cache driver is set to 'file').
    * The following should get permissions set up sufficiently:
        * `sudo chown -R www-data:www-data` to set the appropriate owner and group of all the files in the project.
        * `sudo chmod -R 755 storage/` to set the appropriate permissions for /storage. 

### PHP Configuration
This application requires the following PHP extensions. There is a good chance they are all already installed and enabled. Check `phpinfo()` output to check if any are missing. You can also check the output of `php -m` to see if they are installed.

* OpenSSL PHP Extension (`openssl`)
    * Look for "OpenSSL support" in `phpinfo()`
* PDO PHP Extension (`PDO` and `pdo_mysql`)
    * Look for "PDO drivers" in `phpinfo()`
* Mbstring PHP Extension (`mbstring`)
    * Look for "Multibyte Support" in `phpinfo()`
* Tokenizer PHP Extension (`tokenizer`)
    * Look for "Tokenizer Support" in `phpinfo()`
* ZIP PHP Extension (for PHPExcel) (`zip`)
    * Look for "Libzip version" in `phpinfo()`
* XML PHP Extension (for PHPExcel) (`xml`)
    * Look for "XML Support" in `phpinfo()`
* CURL PHP Extension (`curl`)
    * Required for CAS (SSO) authentication
    * Look for "cURL support" in `phpinfo()`
    * Install it with `apt-get install php5-curl` if it isn't installed.
* **IMPORTANT**: This application makes the assumption that data returned from MySQL will be of the correct types (as opposed to only strings). This means that the `mysqlnd` native driver is [required](http://stackoverflow.com/questions/5323146/mysql-integer-field-is-returned-as-string-in-php). Install it with `apt-get install php5-mysqlnd`.
    * If you are using XAMPP locally, you already have it.
    
    
    
### Apache Configuration

* This part of this file assumes you have at least a basic understanding of apache and its configuration files. If you don't, go do some Googling and come back later.
* Laravel, the framework used by this application, requires `mod_rewrite`. Enable it by running `a2enmod rewrite`.
    * If you are using XAMPP, ensure that `LoadModule rewrite_module modules/mod_rewrite.so` isn't commented out in your apache conf.
* Set the `DocumentRoot` to the `public` directory in the root of the project.
* Find the `<Directory>` directive that governs the document root (it may be in your base apache configuration, or in a VirtualHost), and set `AllowOverride All`. Laravel makes use of directory-specific .htaccess files, and will not work if this is not set.
* Ensure that `AccessFileName .htaccess` is set.
* Don't forget to restart Apache to apply the configuration changes!
* Here is an example vhost:

    ```
    <VirtualHost *:80>
      ServerName localhost
      DocumentRoot "C:/xampp/htdocs/sisyphus/public"
      <Directory "C:/xampp/htdocs/sisyphus/public">
        AllowOverride all
      </Directory>
    </VirtualHost>
    ```
    
### Optimization
There are a few optimization steps that may be taken **if this is a production deployment**.

* Run `php artisan config:cache`
    * If you update any configuration for the application, including what is in the `.env` file, you **MUST** re-run this command to apply your changes.
* Run `php artisan route:cache`
    * If you update the contents of `app/Http/routes.php`, you **MUST** re-run this command to apply your changes.
    
    
## Structure
The following is a brief primer on the structure of this project. If you would like to read more in-depth information about Laravel, I strongly recommend you read the [Laravel 5.2 Documentation](http://laravel.com/docs/5.2)

Laravel is an MVC framework. Important components may be found in the following locations:

* Error logs: `/storage/logs`
    * All errors that occur with the application will be logged here.
    * If APP_DEBUG is false, errors will not be reported in any detail to clients.
    * If APP_DEBUG is true, full stack traces will be sent back to clients.
* Environment-specific configuration: `/.env`
    * Be aware of the Optimization section above if you modify this file.
* All general configuration (which pulls from .env): `/config/*.php`
    * Be aware of the Optimization section above if you modify these files.
* Models: `/app/Models`
* Views: `/resources/views`
* Controllers: `/app/Http/Controllers`
* Routes: `/app/Http/routes.php`
* Middleware: `/app/Http/Middleware`
    * Middleware is registered in `/app/Http/Kernel.php`
* Static Resources (js/css/fonts/images): `/public`
    
* Other important components of this project are the following:
    * CAS (SSO) authentication enforcement point: `/app/Http/Middleware/CASAuth.php`
        * The library used may be found in `/vendor/xavrsl/cas`
    
