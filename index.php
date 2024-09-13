<?php
// Enable error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

// Initialize Facebook SDK
require 'vendor/autoload.php';
session_start();

// Facebook App Configuration
$fb = new Facebook\Facebook([
    'app_id' => '650259894259842', // Your app id
    'app_secret' => '12b672131f37fd32a4561825c6334ed9', // Your app secret
    'default_graph_version' => 'v12.0', // Updated Graph API version
]);

// Helper to manage Facebook Login
$helper = $fb->getRedirectLoginHelper();
$permissions = ['email']; // Optional permissions

try {
    // Check if there's an existing access token in the session
    if (isset($_SESSION['facebook_access_token'])) {
        $accessToken = $_SESSION['facebook_access_token'];
    } else {
        // Get the OAuth access token from the redirect
        $accessToken = $helper->getAccessToken();
    }
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}

if (isset($accessToken)) {
    // Logged in!
    if (!isset($_SESSION['facebook_access_token'])) {
        // Store the access token in session for future use
        $_SESSION['facebook_access_token'] = (string) $accessToken;

        // OAuth 2.0 client handler
        $oAuth2Client = $fb->getOAuth2Client();

        // Exchange short-lived access token for a long-lived one
        try {
            $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;
            $fb->setDefaultAccessToken($longLivedAccessToken);
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Error getting long-lived access token: ' . $e->getMessage();
            exit;
        }
    } else {
        // Set the default access token for requests
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }

    // Fetch user's basic information
    try {
        $profileRequest = $fb->get('/me?fields=id,name,first_name,last_name,email');
        $requestPicture = $fb->get('/me/picture?redirect=false&height=200'); // Get user picture
        $profile = $profileRequest->getGraphUser();
        $picture = $requestPicture->getGraphUser();

        // Store profile information in session
        $_SESSION['fb_id'] = $profile->getId();
        $_SESSION['fb_name'] = $profile->getName();
        $_SESSION['fb_email'] = $profile->getEmail();
        $_SESSION['fb_pic'] = "<img src='" . $picture['url'] . "' class='img-rounded'/>";

        // Redirect to profile page
        header('Location: profile.php');
        exit;
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        session_destroy();
        header("Location: ./");
        exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
} else {
    // Login URL
    $loginUrl = $helper->getLoginUrl('http://localhost/facebook_login/', $permissions);
    echo '<a style="border:2px solid black; padding:50px; margin-top: 100px; display:flex; width:40%; align-items:center; justify-content:center;" href="' . htmlspecialchars($loginUrl) . '"> <img style="height:30px"; src="./image/image.png">Log in with Facebook!</a>';
}
?>