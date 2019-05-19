<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function automatic()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Initialize the OAuth client
        $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => env('OAUTH_APP_ID'),
            'clientSecret'            => env('OAUTH_APP_PASSWORD'),
            'redirectUri'             => env('OAUTH_REDIRECT_URI'),
            'urlAuthorize'            => env('OAUTH_AUTHORITY') . env('OAUTH_AUTHORIZE_ENDPOINT'),
            'urlAccessToken'          => env('OAUTH_AUTHORITY') . env('OAUTH_TOKEN_ENDPOINT'),
            'urlResourceOwnerDetails' => '',
            'scope'                  => env('OAUTH_SCOPES')
        ]);

        try {

            // Try to get an access token using the resource owner password credentials grant.
            $accessToken = $oauthClient->getAccessToken('password', [
                'username' => 'correspondenciacad@constructoracolpatria.com',
                'password' => 'Colpatri@18',
                //'username' => 'sebas-jsv97@hotmail.com',
                //'password' => 'jhon1997',
                //'username' => 'sebasjsv22@jsvptf.onmicrosoft.com',
                //'password' => 'Faq40437',
                'scope' => 'openid profile offline_access User.Read Mail.Read.Shared'
            ]);

            // Save the access token and refresh tokens in session
            // This is for demo purposes only. A better method would
            // be to store the refresh token in a secured database
            $tokenCache = new \App\TokenStore\TokenCache;
            $tokenCache->storeTokens(
                $accessToken->getToken(),
                $accessToken->getRefreshToken(),
                $accessToken->getExpires()
            );

            // Redirect back to mail page
            return redirect()->route('mail');
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            echo '<pre>';
            var_dump($e);
            echo '</pre>';
            exit;
            // Failed to get the access token
            exit($e->getMessage());
        }

        header('Location: mail');
        exit();
    }

    public function signin()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Initialize the OAuth client
        $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => env('OAUTH_APP_ID'),
            'clientSecret'            => env('OAUTH_APP_PASSWORD'),
            'redirectUri'             => env('OAUTH_REDIRECT_URI'),
            'urlAuthorize'            => env('OAUTH_AUTHORITY') . env('OAUTH_AUTHORIZE_ENDPOINT'),
            'urlAccessToken'          => env('OAUTH_AUTHORITY') . env('OAUTH_TOKEN_ENDPOINT'),
            'urlResourceOwnerDetails' => '',
            //'scopes'                  => env('OAUTH_SCOPES')
            'scopes' => 'openid profile offline_access User.Read Mail.Read.Shared'
        ]);

        // Generate the auth URL
        $authorizationUrl = $oauthClient->getAuthorizationUrl();

        // Save client state so we can validate in response
        $_SESSION['oauth_state'] = $oauthClient->getState();

        // Redirect to authorization endpoint
        header('Location: ' . $authorizationUrl);
        exit();
    }

    public function logout(){
        session_start();
        session_destroy();
        echo '<pre>';var_dump($_SESSION);echo '</pre>';exit;
    }

    public function gettoken()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Authorization code should be in the "code" query param
        if (isset($_GET['code'])) {
            // Check that state matches
            if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth_state'])) {
                exit('State provided in redirect does not match expected value.');
            }

            // Clear saved state
            unset($_SESSION['oauth_state']);

            // Initialize the OAuth client
            $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId'                => env('OAUTH_APP_ID'),
                'clientSecret'            => env('OAUTH_APP_PASSWORD'),
                'redirectUri'             => env('OAUTH_REDIRECT_URI'),
                'urlAuthorize'            => env('OAUTH_AUTHORITY') . env('OAUTH_AUTHORIZE_ENDPOINT'),
                'urlAccessToken'          => env('OAUTH_AUTHORITY') . env('OAUTH_TOKEN_ENDPOINT'),
                'urlResourceOwnerDetails' => '',
                'scopes'                  => env('OAUTH_SCOPES')
            ]);

            try {
                // Make the token request
                $accessToken = $oauthClient->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);

                // Save the access token and refresh tokens in session
                // This is for demo purposes only. A better method would
                // be to store the refresh token in a secured database
                $tokenCache = new \App\TokenStore\TokenCache;
                $tokenCache->storeTokens(
                    $accessToken->getToken(),
                    $accessToken->getRefreshToken(),
                    $accessToken->getExpires()
                );

                // Redirect back to mail page
                return redirect()->route('mail');
            } catch (League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                exit('ERROR getting tokens: ' . $e->getMessage());
            }
            exit();
        } elseif (isset($_GET['error'])) {
            exit('ERROR: ' . $_GET['error'] . ' - ' . $_GET['error_description']);
        }
    }
}
