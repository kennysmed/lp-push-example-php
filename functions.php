<?php

// Should be the common directory path from the URL, eg 
// '/lp-hello-world-php/'
$ROOT_DIRECTORY = preg_replace(
	'/(sample|edition|validate_config)\/(index.php)?(\?.*?)?$/', '', $_SERVER['REQUEST_URI']);

// Define greetings for different times of the day in different languages.
$GREETINGS = array( 
	'english'		=> array('Good morning', 'Hello', 'Good evening'), 
	'french'		=> array('Bonjour', 'Bonjour', 'Bonsoir'), 
	'german'		=> array('Guten morgen', 'Hallo', 'Guten abend'), 
	'spanish'		=> array('Buenos días', 'Hola', 'Buenas noches'), 
	'portuguese'	=> array('Bom dia', 'Olá', 'Boa noite'), 
	'italian'		=> array('Buongiorno', 'Ciao', 'Buonasera'), 
	'swedish'		=> array('God morgon', 'Hallå', 'God kväll')
);


/**
 * == POST parameters:
 * :config
 *   params[:config] contains a JSON array of responses to the options defined
 *   by the fields object in meta.json. In this case, something like:
 *   params[:config] = ["name":"SomeName", "lang":"SomeLanguage"]
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
	require $_SERVER['DOCUMENT_ROOT'] . $ROOT_DIRECTORY . 'template.php';
}


/**
 * Prepares and returns an edition of the publication.
 *
 * Expects GET values of 'lang' and 'name'.
 */
function display_edition() {
	global $ROOT_DIRECTORY, $GREETINGS;

	// We ignore timezones, but have to set a timezone or PHP will complain.
	date_default_timezone_set('UTC');

	if (array_key_exists('lang', $_GET)) {
		$language = $_GET['lang'];
	} else {
		$language = '';
	}

	if (array_key_exists('name', $_GET)) {
		$name = $_GET['name'];
	} else {
		$name = '';
	}

	if ($language == '' || ! array_key_exists($language, $GREETINGS)) {
		header('HTTP/1.0 400 Bad Request');
		print 'Error: Invalid or missing lang parameter';
		exit();
	}
	if ($name == '') {
		header('HTTP/1.0 400 Bad Request');
		print 'Error: No name provided';
		exit();
	}
	try {
		// local_delivery_time is like '2013-11-18T23:20:30-08:00'.
		$date = new DateTime($_GET['local_delivery_time']);
	} catch(Exception $e) {
		header('HTTP/1.0 400 Bad Request');
		print 'Error: Invalid or missing local_delivery_time';
		exit();
	}

	// The publication is only delivered on Mondays, so if it's not a Monday in
    // the subscriber's timezone, we return nothing but a 204 status.
	if ($date->format('D') !== 'Mon') {
		http_response_code(204);
		exit();
	}

	// Pick a time of day appropriate greeting.
	$i = 1;
	$hour = (int) $date->format('G');
	switch(TRUE) {
		case in_array($hour, range(0, 3));
			$i = 2;
			break;
		case in_array($hour, range(4, 11));
			$i = 0;
			break;
		case in_array($hour, range(12, 17));
			$i = 1;
			break;
		case in_array($hour, range(18, 23));
			$i = 2;
			break;
	}

	// Base the ETag on the unique content: language, name and time/date.
	// This means the user will not get the same content twice.
	// But, if they reset their subscription (with, say, a different language)
	// they will get new content.
	header("Content-Type: text/html; charset=utf-8");
	header('ETag: "' . md5($language . $name . $date->format('HdmY')) . '"');

	$greeting = sprintf('%s, %s', $GREETINGS[$language][$i], $name);

	require $_SERVER['DOCUMENT_ROOT'] . $ROOT_DIRECTORY . 'template.php';
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
