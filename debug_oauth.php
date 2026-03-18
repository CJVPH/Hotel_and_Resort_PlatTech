<?php
require_once 'config/google_oauth.php';

// Create a temporary session to test the redirect URI
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    $google = new GoogleOAuth();
    
    echo '<h2>Google OAuth Debug Information</h2>';
    echo '<p><strong>Your Redirect URI:</strong></p>';
    echo '<code style="background: #f0f0f0; padding: 10px; display: block; white-space: pre-wrap; word-break: break-all;">';
    // Use reflection to get the redirect_uri
    $reflectionClass = new ReflectionClass($google);
    $redirectUriProperty = $reflectionClass->getProperty('redirect_uri');
    $redirectUriProperty->setAccessible(true);
    echo htmlspecialchars($redirectUriProperty->getValue($google));
    echo '</code>';
    
    echo '<p style="margin-top: 20px;"><strong>Steps to verify:</strong></p>';
    echo '<ol>';
    echo '<li>Go to Google Cloud Console (console.cloud.google.com)</li>';
    echo '<li>Select your project: "Hotel_and_Resort"</li>';
    echo '<li>Go to OAuth 2.0 Client IDs</li>';
    echo '<li>Edit the Web client</li>';
    echo '<li>Under "Authorized redirect URIs", make sure the above URI is listed exactly</li>';
    echo '</ol>';
    
    echo '<p style="margin-top: 20px;"><strong>Current settings:</strong></p>';
    echo '<ul>';
    echo '<li>Server: ' . $_SERVER['HTTP_HOST'] . '</li>';
    echo '<li>Protocol: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '</li>';
    echo '<li>Document Root: ' . $_SERVER['DOCUMENT_ROOT'] . '</li>';
    echo '</ul>';
    
    // Check if oauth_error_log.txt exists
    if (file_exists('oauth_error_log.txt')) {
        echo '<p><strong style="color: red;">Recent OAuth Errors:</strong></p>';
        echo '<pre style="background: #ffeeee; padding: 10px; border: 1px solid red;">';
        echo htmlspecialchars(file_get_contents('oauth_error_log.txt'));
        echo '</pre>';
    }
    
} catch (Exception $e) {
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}
?>
