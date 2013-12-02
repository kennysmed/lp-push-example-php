# Little Printer Push API Example (PHP)

This is an example publication, written in PHP. The same example can also be seen in:

This example expands on the Hello World example, and demonstrates how to use the Push API to send messages directly to subscribed Little Printers.


## Setup and configuration

Requires PHP >= 5.3.3 compiled with the cURL extension, a MySQL database, and
the [Guzzle](http://docs.guzzlephp.org/en/latest/index.html) client framework.

### Database

You can use an existing MySQL database, or create a new one.

To create a new one, either use your server's web admin system or do something like this if you've logged into mysql on the command line. You might want to change the database name, username and password from `push_example`, `pushuser` and `password`:

	CREATE DATABASE push_example CHARACTER SET utf8 COLLATE utf8_general_ci;
	GRANT ALL ON push_example.* TO pushuser@localhost IDENTIFIED BY 'password';
	FLUSH PRIVILEGES;

Then create the one table we'll need:

	CREATE TABLE IF NOT EXISTS subscribers (
		subscription_id VARCHAR(64) NOT NULL,
		name VARCHAR(255) NOT NULL,
		language VARCHAR(30) NOT NULL,
		endpoint VARCHAR(255) NOT NULL,
		PRIMARY KEY(subscription_id)
	);

If you're using an existing database you might want to use a different table
name to avoid clashes with any existing or future tables. In this case, prefix
the name `subscribers` with something else, and set the `DB_TABLE_PREFIX` in
the next configuration step. eg, if you rename the table to `push_subscribers`,
then ensure this line is in your `config.php`:

	define('DB_TABLE_PREFIX', 'push_');


### Configuration file

Copy `config.php.example` to `config.php` and replace the placeholders with your details. Use the database host, name, username and password that you used for your MySQL database.

You will need to get the BERG Cloud OAuth authentication tokens from the page for your newly-created Little Printer publication (in [Your publications](http://remote.bergcloud.com/developers/publications/)).

For better security you should put `config.php` outside of your web root. If you do this, then open `functions.php` and change the path to require `config.php` to match its new location.

### Installing Guzzle

This example uses [Guzzle](http://docs.guzzlephp.org/en/latest/index.html) to make an OAuth POST request. If you already have [Composer](http://getcomposer.org/) you should be able to install the required dependencies using the included `composer.json` and `composer.lock` files by doing something like:

	$ composer install

while in the `lp-push-example-php/` directory.

Otherwise, see Guzzle's [installation instructions](http://docs.guzzlephp.org/en/latest/getting-started/installation.html).

If you've installed Guzzle using Composer, this should result in a `vendor/` directory within the `lp-push-example-php/` directory, with contents something like this:

	autoload.php
	composer/
	guzzle/
	symfony/

If you install this in a location other than a 'vendor/' directory, you'll need to change the path to `vendor/autoload.php` at the top of the included `functions.php`.

You could install Guzzle etc on your server, having uploaded the Push Example files, or on your local machine, and then upload the whole lot to your server.


## Run it

Once the set-up is complete, and all the files are on your server, you should be able to visit these URLs:

* `/icon.png`
* `/meta.json`
* `/sample/`
* `/push/`

The `/push/` page lets you send a greeting to all subscribed Little Printers.

In addition, the `/validate_config/` URL should accept a POST request with a field named `config` containing a string like:

	{"lang":"english", "name":"Phil", "endpoint": "http://api.bergcloud.com/v1/subscriptions/2ca7287d935ae2a6a562a3a17bdddcbe81e79d43/publish", "subscription_id": "2ca7287d935ae2a6a562a3a17bdddcbe81e79d43"}

but with a unique `endpoint` and `subscription_id`.

----

BERG Cloud Developer documentation: http://remote.bergcloud.com/developers/

