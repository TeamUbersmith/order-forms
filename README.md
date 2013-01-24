Order Forms
===========

These online order forms give Ubersmith clients the ability to sell their services easily via their website.

Features
--------

* Feature-rich order checkout process for customers
* Encourage customers to buy value-added services during checkout process
* Orders are submitted directly to a configurable Order Manager queue in Ubersmith
* Supports monthly service plans with setup fees
* Supports coupons for discounts
* Master Services Agreement signage before placing order
* Integrated with Twilio for fraud verification (optional)
* Integrated with Google Analytics for tracking and statistics (optional)
* Supports CDN delivery of static assets (optional)
* Strings passed through i18n functions, ready for translation
* Built on top of CakePHP 2.2

Setup
-----

This has been tested on a LAMP stack running PHP 5.3+, MySQL 5+, Apache 2+, and CentOS. It should work on your favorite flavor or Linux and should be fine behind Nginx or another web server. It may even work with MySQL <5. The only hard requirement is PHP 5.3+.

To get started:

1. Clone the repository onto the server that will be housing the order forms:

		git clone https://github.com/TeamUbersmith/order-forms.git
		cd order-forms
		chmod -R 0777 app/tmp/

2. After setting up a MySQL database, import the SQL dump file, and then remove it

		mysql -u user -p database < dump.sql
		rm dump.sql

3. Copy the sample config files into their final resting place

		cp app/Config/database.php.sample app/Config/database.php
		cp app/Config/core.php.sample app/Config/core.php

4. Edit `app/Config/database.php` with your connection details

5. Edit `app/Config/core.php` with your preferences (relavant config is near the bottom of file)

6. Create your virtual hosts file for ports 80 and 443 (tweak for Nginx or other):

		<VirtualHost _default_:80>
			ServerName order-forms.yourdomain.com
			DocumentRoot /var/www/order-forms
			<Directory /var/www/order-forms>
				AllowOverride All
				Order allow,deny
				Allow from all
			</Directory>
		</VirtualHost>
		
		<VirtualHost _default_:443>
			ServerName order-forms.yourdomain.com
			DocumentRoot /var/www/order-forms
			<Directory /var/www/order-forms>
				AllowOverride All
				Order allow,deny
				Allow from all
			</Directory>
			SSLEngine on
			SSLCertificateFile /path/to/server.crt
			SSLCertificateKeyFile /path/to/server.key
		</VirtualHost>

7. Restart Apache, and then visit http://order-forms.yourdomain.com
