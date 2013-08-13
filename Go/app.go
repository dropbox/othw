package main

import (
	"fmt"
	"net/http"
	"net/url"
	"encoding/base64"
	"crypto/rand"
	"encoding/json"
)

const APP_KEY = "<YOUR APP KEY>"
const APP_SECRET = "<YOUR APP SECRET>"

func getCallbackURL(r *http.Request) string {
	scheme := "http"
	forwarded := r.Header["X-Forwarded-Proto"]
	if len(forwarded) > 0 {
		scheme = forwarded[0]
	}
	return (&url.URL{
		Scheme: scheme,
		Host: r.Host,
		Path: "/callback",
	}).String()
}

func index(w http.ResponseWriter, r *http.Request) {
	if r.URL.Path != "/" {
		http.NotFound(w, r)
		return
	}

	b := make([]byte, 18)
	rand.Read(b)
	csrf := base64.StdEncoding.EncodeToString(b)
	http.SetCookie(w, &http.Cookie{Name: "csrf", Value: csrf})

	http.Redirect(w, r, "https://www.dropbox.com/1/oauth2/authorize?" +
		url.Values{
			"client_id": {APP_KEY},
			"redirect_uri": {getCallbackURL(r)},
			"response_type": {"code"},
			"state": {csrf},
		}.Encode(), 302)
}

func decodeResponse(r *http.Response, m interface{}) {
	defer r.Body.Close()
	json.NewDecoder(r.Body).Decode(m)
}

func callback(w http.ResponseWriter, r *http.Request) {
	http.SetCookie(w, &http.Cookie{Name: "csrf", MaxAge: -1})
	r.ParseForm()
	state := r.FormValue("state");
	cookie, _ := r.Cookie("csrf")
	if cookie == nil || cookie.Value != state {
		w.WriteHeader(401)
		fmt.Fprint(w, "Possible CSRF attack.")
		return
	}

	resp, err := http.PostForm(fmt.Sprintf("https://%s:%s@api.dropbox.com/1/oauth2/token", APP_KEY, APP_SECRET),
		url.Values{
			"redirect_uri": {getCallbackURL(r)},
			"code": {r.Form["code"][0]},
			"grant_type": {"authorization_code"},
		})
	if err != nil {
		panic(err);
	}
	tokenMessage := struct { Access_token string }{}
	decodeResponse(resp, &tokenMessage)
	token := tokenMessage.Access_token

	req, _ := http.NewRequest("GET", "https://api.dropbox.com/1/account/info", nil)
	req.Header.Set("Authorization", "Bearer " + token)
	resp, err = http.DefaultClient.Do(req)
	if err != nil {
		panic(err)
	}
	nameMessage := struct { Display_name string }{}
	decodeResponse(resp, &nameMessage)

	fmt.Fprintf(w, "Successfully authenticated as %s.", nameMessage.Display_name)
}

func main() {
	http.HandleFunc("/callback", callback)
	http.HandleFunc("/", index)
	http.ListenAndServe(":5000", nil)
}
