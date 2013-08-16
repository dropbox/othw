<?php
	require 'vendor/autoload.php';

	// Insert your own app key and secret here.
	$APP_KEY = '<YOUR APP KEY>';
	$APP_SECRET = '<YOUR APP SECRET>';

	$app = new \Slim\Slim();
	date_default_timezone_set('UTC');
	$app->add(new \Slim\Middleware\SessionCookie());
	$env = $app->environment();

	// Safe base64 encoding for URLs.
	function base64url_encode($data) { 
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
	}
	// Generate a random token used for CSRF protection.
	function generateCSRFToken() {
		return base64url_encode(openssl_random_pseudo_bytes(18));
	}

	// Generate a redirect URI corresponding to the given route.
	// Note that OAuth 2.0 only allows HTTPS URLs in general, but Dropbox
	// allows HTTP URLs for localhost/127.0.0.1 endpoints only.
	function generate_redirect_uri($route_name) {
		$app = \Slim\Slim::getInstance();
		$env = $app->environment();

		return $env['slim.url_scheme'] . '://' .
			$_SERVER['HTTP_HOST'] . $app->urlFor($route_name);
	}

	// Main endpoint for the app. This page just starts the OAuth flow by
	// redirecting the user to Dropbox to sign in (if necessary) and allow
	// the app's request for access.
	$app->get('/', function () use ($app) {
		$csrfToken = generateCSRFToken();
		$_SESSION['csrfToken'] = $csrfToken;
		// Redirect to the OAuth authorize endpoint, using the authorization
		// code flow.
		$app->redirect('https://www.dropbox.com/1/oauth2/authorize?' .
			http_build_query(array(
				'response_type' => 'code',
				'client_id' => $GLOBALS['APP_KEY'],
				'redirect_uri' => generate_redirect_uri('callback'),
				'state' => $csrfToken
			)));
	});

	// OAuth callback URL, which the user is redirected to by Dropbox after
	// allowing access to the app. The query parameters will include an
	// access code, which is then exchanged for an access token. The access
	// token is what's used to make calls to the Dropbox API.
	$app->get('/callback', function () use ($app, $env) {
		$params = array();
		parse_str($env['QUERY_STRING'], $params);

		// If there's an error, display it.
		if (isset($params['error'])) {
			echo 'Received an "' . $params['error'] . '" error with the message "' . $params['error_description'] . '"';
			return;
		}

		// Check that the CSRF token matches.
		if ($params['state'] != $_SESSION['csrfToken']) {
			echo 'CSRF protection error! State parameter doesn\'t match CSRF token';
			return;
		}

		$token_request = Requests::post('https://www.dropbox.com/1/oauth2/token',
			array(), // headers
			array( // form body
				'code' => $params['code'],
				'grant_type' => 'authorization_code',
				'client_id' => $GLOBALS['APP_KEY'],
				'client_secret' => $GLOBALS['APP_SECRET'],
				// This redirect URI must exactly match the one used when
				// calling the authorize endpoint previously.
				'redirect_uri' => generate_redirect_uri('callback')
			));
		if ($token_request->status_code !== 200) {
			echo 'Error, possibly expired token.';
			return;
		}
		// Get the bearer token from the response.
		$json = json_decode($token_request->body, true);
		$token = $json['access_token'];

		// Use the bearer token to make calls to the Dropbox API.
		$info_request = Requests::get('https://api.dropbox.com/1/account/info',
			array( // headers
				'Authorization' => 'Bearer ' . $token
			));

		$json = json_decode($info_request->body, true);
		$name = $json['display_name'];

		echo 'Successfully authenticated as ' . $name . '.';
	})->name('callback');

	$app->run();
?>
