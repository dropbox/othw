import java.util.Map;
import java.net.URISyntaxException;
import java.security.SecureRandom;
import org.apache.commons.io.*;
import org.apache.commons.codec.binary.Base64;
import org.apache.http.client.fluent.Form;
import org.apache.http.client.fluent.Request;
import org.apache.http.client.utils.URIBuilder;
import org.json.simple.*;
import static spark.Spark.*;
import spark.*;

public class App {
	static String APP_KEY = "<YOUR APP KEY>";
	static String APP_SECRET = "<YOUR APP SECRET>";

	// Generate a random string to use as a CSRF token.
	public static String generateCSRFToken() {
		byte[] b = new byte[18];
		new SecureRandom().nextBytes(b);
		return Base64.encodeBase64URLSafeString(b);
	}

	public static String getRedirectURI(spark.Request request) throws URISyntaxException {
		return new URIBuilder(request.url()).setPath("/callback").build().toString();
	}

	public static void main(String[] args) throws Exception {
		setPort(5000);

		// Main route when the app is initially loaded.
		get(new Route("/") {
			@Override
			public Object handle(spark.Request request, Response response) {
				String csrfToken = generateCSRFToken();

				// Store the CSRF token in a cookie, to be checked in the callback.
				response.cookie("csrf", csrfToken);

				try {
					// Redirect the user to authorize the app with Dropbox.
					response.redirect(new URIBuilder("https://www.dropbox.com/1/oauth2/authorize")
						.addParameter("client_id", APP_KEY)
						.addParameter("response_type", "code")
						.addParameter("redirect_uri", getRedirectURI(request))
						.addParameter("state", csrfToken)
						.build().toString());

					return null;
				} catch (Exception e) {
					return "ERROR: " + e.toString();
				}
			}
		});

		// Route for when the user is redirected back to our app.
		get(new Route("/callback") {
			@Override
			public Object handle(spark.Request request, Response response) {
				// The CSRF token will only be used once.
				response.removeCookie("csrf");

				// If the CSRF token doesn't match, raise an error.
				if (!request.cookie("csrf").equals(request.queryParams("state"))) {
					halt(401, "Potential CSRF attack.");
				}

				// This is the authorization code from the OAuth flow.
				String code = request.queryParams("code");

				try {
					// Exchange the authorization code for an access token.
					Map json = (Map) JSONValue.parse(
						Request.Post("https://api.dropbox.com/1/oauth2/token")
							.bodyForm(Form.form()
								.add("code", code)
								.add("grant_type", "authorization_code")
								.add("redirect_uri", getRedirectURI(request))
							.build())
							.addHeader("Authorization", "Basic " + Base64.encodeBase64String((APP_KEY+":"+APP_SECRET).getBytes()))
							.execute().returnContent().asString());
					String accessToken = (String) json.get("access_token");

					// Call the /account/info API with the access token.
					json = (Map) JSONValue.parse(
						Request.Get("https://api.dropbox.com/1/account/info")
							.addHeader("Authorization", "Bearer " + accessToken)
							.execute().returnContent().asString());

					return String.format("Successfully authenticated as %s.", json.get("display_name"));
				} catch (Exception e) {
					return "ERROR: " + e.toString();
				}
			}
		});
	}
}
