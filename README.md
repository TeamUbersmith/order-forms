Order Forms
===========

These online order forms give Ubersmith clients the ability to sell their services easily via their website.

Features
--------

* Feature-rich order checkout process for customers
* Orders are submitted directly to a configurable Order Manager queue
* Supports monthly service plans with setup fees
* Supports coupons for discounts
* Master Services Agreement signage before placing order
* Integrated with Twilio for fraud verification
* Integrated with Google Analytics for tracking and statistics
* Supports CDN delivery of static assets
* Translatable strings passed through i18n functions
* Built on top of CakePHP 2.2

Setup
-----

1. Clone the repository onto the server that will be housing the order forms

	git clone https://github.com/TeamUbersmith/order-forms.git

2. Copy

Create your virtual hosts file for 80 and 443:

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


