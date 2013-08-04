require 'rubygems'
require 'bundler/setup'
require 'sinatra'
require 'rest_client'
require 'cgi'
require 'securerandom'
require 'json'

set :port, 5000
enable :sessions

APP_KEY = '<YOUR APP KEY>'
APP_SECRET = '<YOUR APP SECRET>'

get '/' do
	csrf_token = SecureRandom.base64(18)
	session[:csrf] = csrf_token

	params = {
		:client_id => APP_KEY,
		:response_type => :code,
		:redirect_uri => uri('/callback'),
		:state => csrf_token
	}
	qs = params.collect { |k, v| "#{k.to_s}=#{CGI::escape(v.to_s)}" }.join('&')
	redirect("https://www.dropbox.com/1/oauth2/authorize?#{qs}")
end

get '/callback' do
	csrf_token = session.delete(:csrf)
	if params[:state] != csrf_token then
		halt 401, 'Possible CSRF attack.'
	end

	response = RestClient.post("https://#{APP_KEY}:#{APP_SECRET}@api.dropbox.com/1/oauth2/token", {
		:code => params[:code],
		:redirect_uri => uri('/callback'),
		:grant_type => 'authorization_code' })
	token = JSON.parse(response.to_str)['access_token']

	info = JSON.parse(RestClient.get('https://api.dropbox.com/1/account/info', {:Authorization => "Bearer #{token}"}))

	"Successfully logged in as #{info['display_name']}." 
end
