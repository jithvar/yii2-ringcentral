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
        'tokenRefreshCallback' => function($token) {
            // Store the new token
            Yii::$app->settings->set('ringcentral.token', $token['access_token']);
        }
    ],
]
```

## Token Management

The extension automatically handles token refresh when possible. You can provide a callback function to handle the new token when it's refreshed:

```php
'tokenRefreshCallback' => function($token) {
    // Store the new token in your preferred storage
    // Example with database:
    Yii::$app->db->createCommand()
        ->update('settings', ['value' => $token['access_token']], ['key' => 'ringcentral_token'])
        ->execute();
    
    // Or with cache:
    Yii::$app->cache->set('ringcentral_token', $token['access_token']);
}
```

If the token has expired and cannot be refreshed, the extension will throw an exception with a clear message.

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
        // Get new token and update configuration
        $newToken = getNewToken(); // Your token refresh logic
        Yii::$app->ringcentralFax->jwtToken = $newToken;
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
