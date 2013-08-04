using System;
using System.Collections.Generic;
using System.Linq;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Security.Cryptography;
using System.Text;
using System.Threading.Tasks;
using System.Web;
using System.Web.Mvc;
using System.Web.Helpers;

namespace othw.Controllers
{
    public class HomeController : Controller
    {
        private static string APP_KEY = "<YOUR APP KEY>";
        private static string APP_SECRET = "<YOUR APP SECRET>";

        private static string GenerateCsrfToken()
        {
            var bytes = new byte[18];
            new RNGCryptoServiceProvider().GetBytes(bytes);
            return Convert.ToBase64String(bytes).Replace("+", "-").Replace("/", "_");
        }

        public ActionResult Index()
        {
            var token = GenerateCsrfToken();
            Response.SetCookie(new HttpCookie("csrf") { Value = token });
            return Redirect(string.Format(
                "https://www.dropbox.com/1/oauth2/authorize?client_id={0}&response_type=code&state={1}&redirect_uri={2}",
                APP_KEY,
                token,
                Uri.EscapeDataString(Url.Action("Callback", null, null, Request.Url.Scheme))
                ));
        }

        public async Task<ActionResult> Callback()
        {
            string csrf = null;
            var cookie = Request.Cookies["csrf"];
            if (cookie != null)
            {
                csrf = cookie.Value;
            }
            Response.Cookies.Set(new HttpCookie("csrf") { Expires = DateTime.UtcNow.AddDays(-1) });

            if (csrf != Request.QueryString["state"])
            {
                return new HttpUnauthorizedResult("Potential CSRF attack.");
            }

            var code = Request.QueryString["code"];

            var client = new HttpClient()
            {
                BaseAddress = new Uri("https://api.dropbox.com"),
            };
            client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Basic", Convert.ToBase64String(Encoding.ASCII.GetBytes(APP_KEY + ":" + APP_SECRET)));

            var response = await client.PostAsync("/1/oauth2/token",
                new FormUrlEncodedContent(new List<KeyValuePair<string,string>> {
                    new KeyValuePair<string,string>("code", Request.QueryString["code"]),
                    new KeyValuePair<string,string>("grant_type", "authorization_code"),
                    new KeyValuePair<string,string>("redirect_uri", Url.Action("Callback", null, null, Request.Url.Scheme))
                }));
            var json = System.Web.Helpers.Json.Decode(await response.Content.ReadAsStringAsync());
            var token = json.access_token;

            client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", token);

            response = await client.GetAsync("/1/account/info");
            json = System.Web.Helpers.Json.Decode(await response.Content.ReadAsStringAsync());
            return Content(string.Format("Successfully logged in as {0}.", json.display_name));
        }
    }
}
