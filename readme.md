# EWU Senior Project Book Ordering System


## Deployment Instructions

### Dependencies
First, ensure that all dependencies are installed:

* Apache 2.4
* PHP 5.6
    * OpenSSL PHP Extension
    * PDO PHP Extension
    * Mbstring PHP Extension
    * Tokenizer PHP Extension
* MySQL 5.6

### Application Configuration
Next, clone this repository to the web root of your apache installation. Then, go to the root of the project (where this file is located) and

* Run `php composer.phar install` to install all of the packages needed for this application.
* Copy `.env.example` to `.env`, and then modify the settings within.
    * DB_HOST, DB_DATABASE, DB_USERNAME, and DB_PASSWORD are the configuration for MySQL
    * If deploying to production, set APP_ENV=production and APP_DEBUG=false
* Run `php artisan key:generate`
    * This will populate APP_KEY in your `.env` file.
* Run `php artisan migrate` to generate the database.
    * If you would like to seed the database with test data, run `php artisan migrate:refresh --seed` instead. Be warned that this will drop all tables and re-create them.
* Ensure that Apache has full permissions to the `storage` directory of the project.

### Apache Configuration

* This application makes the assumption that data returned from MySQL will be of the correct types (as opposed to only strings). This means that the `mysqlnd` native driver is [required](http://stackoverflow.com/questions/5323146/mysql-integer-field-is-returned-as-string-in-php). Install it with `apt-get install php5-mysqlnd`.
* In order for CAs (SSO) authentication to work, `php5-curl` is required. Install it with `apt-get install php5-curl`.
* Laravel, the framework used by this application, requires `mod_rewrite`. Enable it by running `a2enmod rewrite`.
* Set the `DocumentRoot` to the `public` directory in the root of the project.
* Find the `<Directory>` directive that governs the document root (it may be in your base apache configuration, or in a VirtualHost), and set `AllowOverride All`. Laravel makes use of directory-specific .htaccess files, and will not work if this is not set.
* Ensure that `AccessFileName .htaccess` is set.
* Don't forget to restart Apache to apply the configuration changes!
    
### Optimization
There are a few optimization steps that may be taken if this is a permanent installation.

* Run `php artisan config:cache`
    * If you update any configuration for the application, including what is in the `.env` file, you **MUST** re-run this command to apply your changes.
* Run `php artisan route:cache`
    * If you update the contents of `app/Http/routes.php`, you **MUST** re-run this command to apply your changes.
    
    
## Structure
The following is a brief primer on the structure of this project. If you would like to read more in-depth information about Laravel, check out the [Laravel 5.1 Documentation](http://laravel.com/docs/5.1)

* Laravel is an MVC framework. Important components may be found in the following locations:
    * Environment-specific configuration: `/.env`
    * All general configuration (which pulls from .env): `/config/*.php`
    * Models: `/app/Models`
    * Views: `/resources/views`
    * Controllers: `/app/Http/Controllers`
    * Routes: `/app/Http/routes.php`
    * Middleware: `/app/Http/Middleware`
        * Middleware is registered in `/app/Http/Kernel/php`
    * Static Resources (js/css/fonts/images): `/public`
* Other important components of this project are the following:
    * CAS (SSO) authentication enforcement point: `/app/Http/Middleware/CASAuth.php`
        * The library used may be found in `/vendor/xavrsl/cas`
    