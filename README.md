**Note: The samples in this repo use the /1/account/info endpoint in Dropbox API v1, which has been retired. [Learn more.](https://blogs.dropbox.com/developers/2017/09/api-v1-shutdown-details/) The OAuth 2 implementations can still be used for [Dropbox API v2](https://www.dropbox.com/developers) though.**

Dropbox and OAuth 2 the Hard Way
--------------------------------

This project shows, in a variety of languages, how to authenticate a user and call the Dropbox API *without* using existing OAuth or Dropbox libraries.

Code is provided for the following languages:

* [C#](C%23) - ASP.NET MVC 4, HttpClient
* [Go](Go) - (no libraries)
* [Java](Java) - Spark, HttpClient
* [JavaScript (browser)](JavaScript) - Superagent
* [JavaScript (Node.js)](Node.js) - Express, Request
* [PHP](PHP) - Slim, Requests
* [Perl](Perl) - Dancer, HTTP::Tiny
* [Python](Python) - Flask, Requests
* [Ruby](Ruby) - Sinatra, Rest-Client

To run the samples, you'll need to [create a Dropbox API app](https://www.dropbox.com/developers/apps) and put your app key and secret into the code. You'll also need to set up the right OAuth 2 callback URL (`http://127.0.0.1:5000/callback` for most samples, `http://localhost:5000/callback` for C# and `http://127.0.0.1:5000` for JavaScript).

FAQ
===

Why?
----

There are [lots of libraries](https://www.dropbox.com/developers/core) for using the Dropbox Core API, but some languages don't have a library, and libraries don't always cover every option of every API method. Fortunately, the API is pretty simple, and OAuth 2 (which is used for authentication) is also pretty simple. By reading through these examples, a developer familiar with basic HTTP APIs should be able to write their own code for interacting with the Dropbox API without having to rely on an existing library.

It's also kind of fun and instructive to read and write the same app in multiple programming languages.

Your code sucks.
----------------
Good question! I'm not an expert in most of these languages&mdash;for example, this was my first time writing Go&mdash;so it's quite likely that I got some code wrong or failed to follow some language idioms. Please send me a pull request if you have suggestions for how to improve the code.

What about language X?
----------------------

Let me know by [opening an issue](https://github.com/dropbox/othw/issues) if there's another language you'd like to see a sample for. Better yet, write it yourself and send a pull request!
