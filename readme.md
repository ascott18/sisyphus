# EWU Senior Project Book Ordering System


## Setup Instructions
If you are using XAMPP:

* Install [Composer](https://getcomposer.org/download/)
* Clone this repo to your htdocs folder.
* Ensure PHP is in your path (verify this by opening a command line anywhere and typing php). If it isn't, then add it to your path.
* Go to the root of the project (where this file is located), and
    * Run ```composer update``` (If you didn't use the Windows installer, you will probably need to run ```php composer.phar update```).
    * Copy ```.env.example``` to ```.env```, and then modify the database settings to match your local mysql settings using your favorite text editor (Configure MySQL by starting XAMPP, starting Apache and MySQL, going to ```localhost``` in your browser, and clicking on phpMyAdmin in the top right corner).
    * Open a command line in the root of the project, and run `php artisan key:generate`
* Open ```\xampp\apache\conf\extra\httpd-vhosts.conf``` in your favorite text editor.
* Add the following to it:
        
    ```        
    <VirtualHost *:8080>
      ServerName localhost
      DocumentRoot "C:/xampp/htdocs/sisyphus/public"
      <Directory "C:/xampp/htdocs/sisyphus/public">
        AllowOverride all
      </Directory>
    </VirtualHost>
    ```
* Open ```\xampp\apache\conf\httpd.conf``` in your favorite text editor.
* Find the line ```Listen 80``` and add ```Listen 8080``` after it.
* Open XAMPP control panel, and start apache (restart it if it was already running).
* Open your favorite web browser, and go to localhost:8080.
    
