<?php
require 'vendor/autoload.php';
require 'config.php';

use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;


// Should be the common directory path from the URL, eg 
// '/lp-hello-world-php/'
$ROOT_DIRECTORY = preg_replace(
	'/(sample|validate_config|push)\/(index.php)?(\?.*?)?$/', '', $_SERVER['REQUEST_URI']);

// Define greetings for different times of the day in different languages.
$GREETINGS = array( 
	'english'		=> array('Hello', 'Hi'), 
	'french'		=> array('Salut'), 
	'german'		=> array('Hallo', 'Tag'), 
	'spanish'		=> array('Hola'), 
	'portuguese'	=> array('Olá'), 
	'italian'		=> array('Ciao'), 
	'swedish'		=> array('Hallå')
);

$DB = FALSE;

function db() {
	global $DB;

	if ($DB === FALSE) {
		$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		if (mysqli_connect_errno()) {
			printf("Connect failed: %s\n", mysqli_connect_error());
			exit();
		} else {
			$DB = $mysqli;
		}
	}
	return $DB;
}

function client() {
	$client = new Client(BERGCLOUD_SITE);
	$oauth = new OauthPlugin(array(
		'consumer_key'    => BERGLCOUD_CONSUMER_TOKEN,
		'consumer_secret' => BERGCLOUD_CONSUMER_TOKEN_SECRET,
		'token'           => BERGCLOUD_ACCESS_TOKEN,
		'token_secret'    => BERGCLOUD_ACCESS_TOKEN_SECRET
	));
	$client->addSubscriber($oauth);
	return $client;
}

/**
 * == POST parameters:
 * :config
 *   params[:config] contains a JSON array of responses to the options defined
 *   by the fields object in meta.json. In this case, something like:
 *   params[:config] = ["name":"SomeName", "lang":"SomeLanguage"]
 * :endpoint
 *   the URL to POST content to be printed out by Push.
 * :subscription_id
 *   a string used to identify the subscriber and their Little Printer.
 * 
 * Most of this is identical to a non-Push publication.
 * The only difference is that we have an `endpoint` and `subscription_id` and
 * need to store this data in our database. All validation is the same.
 *
 * == Returns:
 * A JSON response object.
 * If the parameters passed in are valid: {"valid":true}
 * If the parameters passed in are not valid: {"valid":false,"errors":["No name was provided"], ["The language you chose does not exist"]}
 */
function display_validate_config() {
	global $GREETINGS;

	if (array_key_exists('config', $_POST)) {
		$config = $_POST['config'];
	} else {
		header('HTTP/1.0 400 Bad Request');
		print 'There is no config to validate';
		exit();
	}

	// Preparing what will be returned:
	$response = array(
		'errors' => array(),
		'valid' => TRUE
	);

	// Extract the config from the POST data and parse its JSON contents.
	// user_settings will be something like:
	// {"name":"Alice", "lang":"english"}.
	$user_settings = json_decode(stripslashes($config), TRUE);

	// If the user did not choose a language:
	if ( ! array_key_exists('lang', $user_settings) || $user_settings['lang'] == '') {
		$response['valid'] = FALSE;
		array_push($response['errors'], 'Please choose a language from the menu.');
	}

	// If the user did not fill in the name option:
	if ( ! array_key_exists('name', $user_settings) || $user_settings['name'] == '') {
		$response['valid'] = FALSE;
		array_push($response['errors'], 'Please enter your name into the name box.');
	}

	if ( ! array_key_exists(strtolower($user_settings['lang']), $GREETINGS)) {
		// Given that the select field is populated from a list of languages
		// we defined this should never happen. Just in case.
		$response['valid'] = FALSE;
		array_push($response['errors'], sprintf("We couldn't find the language you selected (%s). Please choose another.", $user_settings['lang']));
	}
	
    /************************
    * This section is Push-specific, different to a conventional publication: 
	*/
	if ( ! array_key_exists('endpoint', $_GET) || $_GET['endpoint'] == '') {
		$response['valid'] = FALSE;
		array_push($response['errors'], "No Push endpoint was provided.");
	}

	if ( ! array_key_exists('subscription_id', $_GET) || $_GET['subscription_id'] == '') {
		$response['valid'] = FALSE;
		array_push($response['errors'], "No Push subscription_id was provided.");
	}

	if ($response['valid']) {
        // Assuming the form validates, we store the endpoint, plus this user's
        // language choice and name, keyed by their subscription_id.
		$db = db();
		$stmt = $db->prepare("
			INSERT INTO " . DB_TABLE_PREFIX . "subscribers
			(subscription_id, name, language, endpoint)
			VALUES (?, ?, ?, ?)
		");
		$stmt->bind_param(
			$_GET['subscription_id'],
			$user_settings['name'],
			$user_settings['lang'],
			$_GET['endpoint']
		);
		$stmt->execute();
		$stmt->close();
		$db->close();
	}
	/*
     * Ending the Push-specific section.
	 ************************/
	
	header('Content-type: application/json');
	echo json_encode($response);
}


/**
 * Called to generate the sample shown on BERG Cloud Remote.
 */
function display_sample() {
	global $ROOT_DIRECTORY, $GREETINGS;

	// The values we'll use for the sample:
	$language = 'english';
	$name = 'Little Printer';
	
	$greeting = sprintf('%s, %s', $GREETINGS[$language][0], $name);

	// Set the ETag to match the content.
	header("Content-Type: text/html; charset=utf-8");
	header('ETag: "' . md5($language . $name . gmdate('dmY')) . '"');
	require $_SERVER['DOCUMENT_ROOT'] . $ROOT_DIRECTORY . 'templates/edition.php';
}


/**
 * Work out whether we came here via a GET or POST request.
 */
function display_push() {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		display_push_post();
	} else {
		display_push_get();
	}
}


/**
 * A button to press to send print events to subscribed Little Printers.
 */
function display_push_get() {
	$pushed = FALSE;
	require $_SERVER['DOCUMENT_ROOT'] . $ROOT_DIRECTORY . 'templates/push.php';
}


function display_push_post() {
	$subscribed_count = 0;
	$unsubscribed_count = 0;

	$template = file_get_contents('templates/edition.php');

	$db = db();
	if ($result = $db->query("SELECT name, language, endpoint
						FROM " . DB_TABLE_PREFIX . "subscribers")) {

		$client = client();
		while($obj = $result->fetch_object()) {
			$greeting = array_rand($GREETINGS[$obj->language]);

			$content = preg_replace('/\{\$greeting\}/', $greeting, $template);
		
			$request = $client->post(
							$obj->endpoint,
							array('Content-Type' => 'text/html; charset=utf-8'),
							$content
						);
			$response = $request->send();

			if ($response->getStatusCode() == '410') {
				$stmt = $db->prepare("
					DELETE FROM " . DB_TABLE_PREFIX . "subscribers
					WHERE subscription_id = ?
				");
				$stmt->bind_param($obj->subscription_id);
				$stmt->execute();
				$stmt->close();
				$unsubscribed_count += 1;
			} else {
				$subscribed_count += 1;
			}
		}
		$result->close();
	};
	$db->close();

	$pushed = TRUE;
	require $_SERVER['DOCUMENT_ROOT'] . $ROOT_DIRECTORY . 'templates/push.php';
}


/**
 * For 4.3.0 <= PHP <= 5.4.0
 * PHP >= 5.4 already has a http_response_code() function.
 */
if ( ! function_exists('http_response_code')) {
	function http_response_code($newcode = NULL) {
		static $code = 200;
		if ($newcode !== NULL) {
			header('X-PHP-Response-Code: '.$newcode, true, $newcode);
			if ( ! headers_sent()) {
				$code = $newcode;
			}
		}
		return $code;
	}
}

?>
