# Yii2 RingCentral Fax Extension

This extension provides RingCentral Fax integration for Yii2 framework using JWT authentication.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist jithvar/yii2-ringcentral
```

or add

```
"jithvar/yii2-ringcentral": "*"
```

to the require section of your `composer.json` file.

## Configuration

Add the following to your application configuration:

```php
'components' => [
    'ringcentralFax' => [
        'class' => 'ringcentral\fax\RingCentralFax',
        'clientId' => 'YOUR_CLIENT_ID',
        'clientSecret' => 'YOUR_CLIENT_SECRET',
        'serverUrl' => 'https://platform.ringcentral.com', // Use 'https://platform.devtest.ringcentral.com' for sandbox
        'jwtToken' => 'YOUR_JWT_TOKEN',
        // Optional: Callback for token refresh
        'tokenRefreshCallback' => function() {
            // Get and return a new token
            $newToken = Yii::$app->cache->get('ringcentral_token');
            // or from database, API call, etc.
            return $newToken;
        }
    ],
]
```

## Token Management

The extension will attempt to use the token refresh callback when a token expires. Your callback should return a new valid token:

```php
'tokenRefreshCallback' => function() {
    // Example: Get new token from your token management service
    $newToken = MyTokenService::getNewToken();
    
    // Or from database
    $newToken = Yii::$app->db->createCommand('SELECT value FROM settings WHERE key = :key')
        ->bindValue(':key', 'ringcentral_token')
        ->queryScalar();
    
    return $newToken;
}
```

If no callback is provided or the callback returns null, the extension will throw an exception when the token expires.

## Usage

```php
try {
    // Send a fax
    $result = Yii::$app->ringcentralFax->send([
        'to' => '+1234567890',
        'files' => ['/path/to/file.pdf'],
        'text' => 'Optional cover page text'
    ]);
} catch (\yii\base\Exception $e) {
    // Handle token expiration or other errors
    if (strpos($e->getMessage(), 'token has expired') !== false) {
        // Get new token from your token management system
        $newToken = MyTokenService::getNewToken();
        // Update the component
        Yii::$app->ringcentralFax->refreshToken($newToken);
    }
}
```

## Getting JWT Token

1. Go to RingCentral Developer Portal
2. Create or select your application
3. Under "Auth & Security":
   - Enable "JWT auth flow"
   - Generate a JWT token with the required permissions (especially "Fax")
4. Copy the generated JWT token and use it in your configuration

## License

This project is licensed under the MIT License - see the LICENSE file for details.
