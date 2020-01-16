<?php

namespace jakeoandasan\office365api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use App\TokenStore\TokenCache;

class MicrosoftGraphController extends Controller
{
    public function signin()
    {
      // Initialize the OAuth client
      $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
        'clientId'                => env('OAUTH_APP_ID'),
        'clientSecret'            => env('OAUTH_APP_PASSWORD'),
        'redirectUri'             => env('OAUTH_REDIRECT_URI'),
        'urlAuthorize'            => env('OAUTH_AUTHORITY').env('OAUTH_AUTHORIZE_ENDPOINT'),
        'urlAccessToken'          => env('OAUTH_AUTHORITY').env('OAUTH_TOKEN_ENDPOINT'),
        'urlResourceOwnerDetails' => '',
        'scopes'                  => env('OAUTH_SCOPES')
      ]);
  
      $authUrl = $oauthClient->getAuthorizationUrl();
  
      // Save client state so we can validate in callback
      session(['oauthState' => $oauthClient->getState()]);
  
      // Redirect to AAD signin page
      return redirect()->away($authUrl);
    }
  
    public function callback(Request $request)
    {
      // Validate state
      $expectedState = session('oauthState');
      $request->session()->forget('oauthState');
      $providedState = $request->query('state');
  
      if (!isset($expectedState)) {
        // If there is no expected state in the session,
        // do nothing and redirect to the home page.
        return redirect('/');
      }
  
      if (!isset($providedState) || $expectedState != $providedState) {
        return redirect('/')
          ->with('error', 'Invalid auth state')
          ->with('errorDetail', 'The provided auth state did not match the expected value');
      }
  
      // Authorization code should be in the "code" query param
      $authCode = $request->query('code');
      if (isset($authCode)) {
        // Initialize the OAuth client
        $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
          'clientId'                => env('OAUTH_APP_ID'),
          'clientSecret'            => env('OAUTH_APP_PASSWORD'),
          'redirectUri'             => env('OAUTH_REDIRECT_URI'),
          'urlAuthorize'            => env('OAUTH_AUTHORITY').env('OAUTH_AUTHORIZE_ENDPOINT'),
          'urlAccessToken'          => env('OAUTH_AUTHORITY').env('OAUTH_TOKEN_ENDPOINT'),
          'urlResourceOwnerDetails' => '',
          'scopes'                  => env('OAUTH_SCOPES')
        ]);
  
        try {
          // Make the token request
          $accessToken = $oauthClient->getAccessToken('authorization_code', [
            'code' => $authCode
          ]);
  
          $graph = new Graph();
          $graph->setAccessToken($accessToken->getToken());
  
          $user = $graph->createRequest('GET', '/me')
            ->setReturnType(Model\User::class)
            ->execute();
  
          $tokenCache = new TokenCache();
          $tokenCache->storeTokens($accessToken, $user);
  
          return redirect('/');
        }
        catch (League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
          return redirect('/')
            ->with('error', 'Error requesting access token')
            ->with('errorDetail', $e->getMessage());
        }
      }
  
      return redirect('/')
        ->with('error', $request->query('error'))
        ->with('errorDetail', $request->query('error_description'));
    }
  
    public function signout()
    {
      $tokenCache = new TokenCache();
      $tokenCache->clearTokens();
      return redirect('/');
    }

    public function requestQuery($uri, $method = 'GET', array $body = null)
    {
        //validate method
        if ($method != 'GET' && $method != 'POST')
        {
          return redirect('/')
            ->with('error', 'HTTP Method is not valid.');
        }

        // Get the access token from the cache
        $tokenCache = new TokenCache();
        $accessToken = $tokenCache->getAccessToken();

        // Check if there's an access token
        if ($accessToken == null)
        {
          return redirect('/')
            ->with('error', 'No access token available.')
            ->with('errorDetail', 'If you are not signed in, please sign in.');
        }
        
        // Create a Graph client
        $graph = new Graph();
        $graph->setAccessToken($accessToken);
        
        $getEventsUrl = $uri;
        
        if ($body == null)
          return $graph->createRequest($method, $getEventsUrl)
            ->setReturnType(Model\Event::class)
            ->execute();
        else
          return $graph->createRequest($method, $getEventsUrl)
            ->attachBody($body)
            ->setReturnType(Model\Event::class)
            ->execute();
    }

    public function getMyInfo()
    {
        return $this->requestQuery('/me');
    }

    public function getAllUsers()
    {
        return $this->requestQuery('/users');
    }

    public function getMyTeams()
    {
        return $this->requestQuery('/me/joinedTeams');
    }

    public function getAllGroups()
    {
        return $this->requestQuery('/groups');
    }

    public function getTeamMembers($groupId)
    {
        return $this->requestQuery('/groups/' . $groupId . '/members');
    }

    public function getTeamInfo($groupId)
    {
        return $this->requestQuery('/teams/' . $groupId);
    }

    public function getMyLicense()
    {
        return $this->requestQuery('/me/licenseDetails');
    }

    public function getUserLicense($userId)
    {
        return $this->requestQuery('/users/' . $userId . '/licenseDetails');
    }

    public function addUser($displayName, $nickName, $principalName, $password)
    {
        return $this->requestQuery('/users', 'POST',
          array(
            'accountEnabled'=>true,
            'displayName'=>$displayName,
            'mailNickname'=>$nickName,
            'userPrincipalName'=>$principalName,
            'passwordProfile'=>array(
              'forceChangePasswordNextSignIn'=>true,
              'password'=>$password
            )  
          ));
    }

    public function assignLicense($userId)
    {
        return $this->requestQuery('/users/' . $userId . '/assignLicense', 'POST',
          array(
            'addLicenses'=>[
              array(
                'disabledPlans'=>[],
                'skuId'=>'3b555118-da6a-4418-894f-7df1e2096870'
              )],
              'removedLicenses'=>[]
          ));
    }
}
