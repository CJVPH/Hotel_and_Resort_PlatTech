<?php
// Google OAuth Configuration
class GoogleOAuth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    
    public function __construct() {
        // Google OAuth credentials
        $this->client_id = '829858353753-5er1pes529q7rugedqvrpjfgekqmf5c5.apps.googleusercontent.com';
        $this->client_secret = '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';
        // Build redirect URI dynamically to handle subdirectories
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Get the project directory from the config file path
        $configDir = dirname(__FILE__); // /path/to/Hotel_and_Resort/config
        $projectDir = dirname($configDir); // /path/to/Hotel_and_Resort
        
        // Get relative path from document root
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        $relativePath = str_replace(str_replace('/', '\\', $docRoot), '', str_replace('/', '\\', $projectDir));
        $relativePath = str_replace('\\', '/', $relativePath);
        
        // Remove trailing slash if present
        $relativePath = rtrim($relativePath, '/');
        
        $this->redirect_uri = $protocol . '://' . $host . $relativePath . '/redirect.php';
    }
    
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'openid email profile',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    public function getAccessToken($code) {
        $url = 'https://oauth2.googleapis.com/token';
        
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->redirect_uri,
            'grant_type' => 'authorization_code',
            'code' => $code
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            throw new Exception('Failed to connect to Google token endpoint');
        }
        
        $tokenData = json_decode($result, true);
        
        // Check for error in response
        if (isset($tokenData['error'])) {
            throw new Exception('Google OAuth Error: ' . $tokenData['error'] . ' - ' . ($tokenData['error_description'] ?? 'No description'));
        }
        
        return $tokenData;
    }
    
    public function getUserInfo($access_token) {
        $url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($access_token);
        
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true
            ]
        ]);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            throw new Exception('Failed to connect to Google user info endpoint');
        }
        
        $userInfo = json_decode($result, true);
        
        // Check for error in response
        if (isset($userInfo['error'])) {
            throw new Exception('Google API Error: ' . $userInfo['error']['message']);
        }
        
        return $userInfo;
    }
    
    public function getClientId() {
        return $this->client_id;
    }
    
    public function isConfigured() {
        return !empty($this->client_id) && 
               !empty($this->client_secret) && 
               $this->client_id !== 'YOUR_GOOGLE_CLIENT_ID';
    }
}
?>