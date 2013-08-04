# Prerequisites

* Perl
* [cpanminus](https://github.com/miyagawa/cpanminus)
* [local::lib](https://metacpan.org/module/local::lib)

# Setup

1. Enter your app key and secret in the code. (Look for `APP_KEY` and `APP_SECRET`.)
2. Install dependencies: `cpanm -L extlib Dancer JSON Bytes::Random::Secure HTTP::Tiny Net::SSLeay Mozilla::CA`

# Running

`perl app.pl`

Open the browser to http://127.0.0.1:5000.
