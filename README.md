# Little Printer Push API Example (PHP)

This is an example publication, written in PHP. The same example can also be seen in:

This example expands on the Hello World example, and demonstrates how to use the Push API to send messages directly to subscribed Little Printers.

## Database

	CREATE DATABASE push_example CHARACTER SET utf8;
	CREATE TABLE IF NOT EXISTS subscribers (
		subscription_id VARCHAR(64) NOT NULL,
		name VARCHAR(255) NOT NULL,
		language VARCHAR(30) NOT NULL,
		endpoint VARCHAR(255) NOT NULL,
		PRIMARY KEY(subscription_id)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;

	GRANT ALL ON push_example.* TO username@localhost IDENTIFIED BY 'password';
	FLUSH PRIVILEGES;

## Run it

Requires PHP >= 5.2.0.

Upload these files to your server, then you should be able to visit these URLs:

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

