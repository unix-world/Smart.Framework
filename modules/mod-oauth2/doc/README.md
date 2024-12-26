
# Sample OAuth2 Step-By-Step for Standalone Apps

###### Fixes, for upcomming OAuth 2.1 to support in parallel Oauth 2.0
/authorize#skip-PKCE=unsupported
/token#skip-PKCE=refresh

This OAuth2 API uses Oauth2 and Google Oauth2 Standards.
Thus other, non-standard Oauth2 implementations may not be compatible with this implementation API.

To setup an Oauth2 Api there are 3 steps:

Step1: Setup API on provider's website and obtain the ClientID and ClientSecret -> Step2: Obtain the code -> Step 3

## The 1st step is to setup the OAuth2 application with the Oauth2 provider in order to get: THE_CLIENT_ID and THE_CLIENT_SECRET

## The 2nd step is to authorize the OAuth2 application by running the below generated link in a web browser to get the Authorization Code

### Example (GitLab Oauth2) ; THE_REDIRECT_URL must be changed to match your needs:
* THE_OAUTH2_URI 	= "https://gitlab.com/oauth/authorize"
* THE_SCOPE 		= "read_repository"
* THE_REDIRECT_URL 	= "https://127.0.0.1/sites/smart-framework/?page=oauth2.get-code"
### For the step 3 add the above + these:
THE_TOKEN_URL		= "https://gitlab.com/oauth/token"
* THE_CODE			= will be provided in a web browser by the THE_REDIRECT_URL via $_REQUEST['code'] after completing the first step


### Example (GitHub Oauth2) ; THE_REDIRECT_URL must be changed to match your needs ; notice that GitHub does not support classic URL with Query Parameters, thus the URL has been supplied in a different way, but still works in Smart.Framework
* THE_OAUTH2_URI 	= "https://github.com/login/oauth/authorize"
* THE_SCOPE 		= "repo:status"
* THE_REDIRECT_URL 	= "https://127.0.0.1/your-web-app/?/page/oauth2.get-code/"
### For the step 3 add the above + these:
THE_TOKEN_URL		= "https://github.com/login/oauth/access_token"
* THE_CODE			= will be provided in a web browser by the THE_REDIRECT_URL via $_REQUEST['code'] after completing the first step


```
#!/bin/sh

THE_OAUTH2_URI="https://the-uri/to/oauth2/auth"
THE_CLIENT_ID="[Client ID Goes Here]"
THE_CLIENT_SECRET="[Client Secret Goes Here]"
THE_SCOPE="[The Scope goes here]"
THE_REDIRECT_URL="urn:ietf:wg:oauth:2.0:oob"


# hit first the below URL in a browser to get the Authorization Code (will be printed in the browser after successful login and authorization ok)
echo "Open a web browser and enter the below URL to get the Authorization Code:"
echo "${THE_OAUTH2_URI}?client_id=${THE_CLIENT_ID}&redirect_uri=${THE_REDIRECT_URL}&scope=${THE_SCOPE}&response_type=code"

```


## The 3rd step is to get the first Access Token (and the Expiration Time and the Refresh Token if provided ; some providers do not use expiring tokens so the first Access Token provided will be valid until it is being Revoked)
The obtained Access Token in this step can be used for XOAUTH2 logins but will expire after a limited time. It have to be stored together with the Expiration Time to be used for future logins but will no more work after the Expiration Time thus after it expires go to step 4.
The obtained Refresh Token in this step must be stored securely somewhere in order to be used for 4th step anytime when a new Access Token is needed because the old Access Token was expired.
If the Refresh Token is lost, the application needs a full re-authorization starting from step 1 again.
After this step, use only step 4 to refresh the Access Token after it's expiration.

```
#!/bin/sh

THE_OAUTH2_URI="https://the-uri/to/oauth2/auth"
THE_CLIENT_ID="[Client ID Goes Here]"
THE_CLIENT_SECRET="[Client Secret Goes Here]"
THE_SCOPE="[The Scope goes here]"
THE_REDIRECT_URL="urn:ietf:wg:oauth:2.0:oob"


THE_TOKEN_URL="https://the-uri/to/oauth2/token"
THE_CODE="[Code get from 2nd step goes here]"

curl -v \
--request POST \
--data "code=${THE_CODE}&client_id=${THE_CLIENT_ID}&client_secret=${THE_CLIENT_SECRET}&redirect_uri=${THE_REDIRECT_URL}&grant_type=authorization_code" \
"${THE_TOKEN_URL}"

#{
#  "access_token": "this is the first access token",
#  "expires_in": 3600,
#  "refresh_token": "this is the refresh token that need to be used to get new access tokens on step 4 after the access token expires",
#  "scope": "the scope of this token",
#  "token_type": "Bearer"
#}

```

## The 4th (last step) is required to be run only after a valid Access Token obtained at step 3 or at this step is expired
Run this step regularily in order to get new valid Access Token anytime the Access Token was expired.
This step returns a new valid Access Token and it's new Expiration Time

```
#!/bin/sh

THE_OAUTH2_URI="https://the-uri/to/oauth2/auth"
THE_CLIENT_ID="[Client ID Goes Here]"
THE_CLIENT_SECRET="[Client Secret Goes Here]"
THE_SCOPE="[The Scope goes here]"
THE_REDIRECT_URL="urn:ietf:wg:oauth:2.0:oob"


THE_TOKEN_URL="https://the-uri/to/oauth2/token"
THE_CODE="[Code get from 2nd step goes here]"


THE_REFRESH_TOKEN="[The Refresh Token from 3rd step goes here]"

curl -v \
--request POST \
--data "client_id=${THE_CLIENT_ID}&client_secret=${THE_CLIENT_SECRET}&refresh_token=${THE_REFRESH_TOKEN}&grant_type=refresh_token" \
"${THE_TOKEN_URL}"

#{
#  "access_token": "The 2..n access token",
#  "expires_in": 7200,
#  "scope": "the scope of this token",
#  "token_type": "Bearer"
#}

```

### IMPORTANT: This is a generic, minimal, simple OAUTH2 example. Some providers may require extra URL parameters. The OAUTH2 Scopes are custom defined for each provider.

