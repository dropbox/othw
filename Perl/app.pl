package oauth2;

use strict;
use warnings;
use local::lib 'extlib';
use Dancer;
use Bytes::Random::Secure qw(random_bytes_base64);
use URI;
use HTTP::Tiny;

my $APP_KEY = "<YOUR APP KEY>";
my $APP_SECRET = "<YOUR APP SECRET>";

set 'port' => 5000;

get '/' => sub {
	my $csrf = random_bytes_base64(18);
	$csrf =~ tr/+\/=\n/-_/;
	cookie csrf => $csrf;
	my $u = URI->new('https://www.dropbox.com/1/oauth2/authorize');
	$u->query_form(
		client_id => $APP_KEY,
		redirect_uri => uri_for('/callback'),
		response_type => 'code',
		state => $csrf,
	);
	redirect $u->as_string;
};

get '/callback' => sub {
	my $csrf = cookie 'csrf';
	cookie csrf => '', expires => -1;
	send_error('Possible CSRF attack.', 401) unless defined $csrf and param('state') eq $csrf;

	my $http = HTTP::Tiny->new(verify_SSL => 1);

	my $response = $http->post_form("http://$APP_KEY:$APP_SECRET\@api.dropbox.com/1/oauth2/token", {
		redirect_uri => uri_for('/callback'),
		code => param('code'),
		grant_type => 'authorization_code',
	});
	my $token = from_json($response->{content})->{access_token};

	$response = $http->get('https://api.dropbox.com/1/account/info', {
		headers => {Authorization => "Bearer $token"}
	});

	my $json = from_json($response->{content});

	return "Successfully authenticated as $json->{display_name}.";
};

start;
