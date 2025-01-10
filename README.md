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
        'jwtToken' => 'YOUR_JWT_TOKEN'
    ],
]
```

## JWT Token Management

This extension uses JWT authentication. JWT tokens have a fixed expiration time and cannot be automatically refreshed. When a token expires, you'll need to:

1. Generate a new token from the RingCentral Developer Portal
2. Update the token in your application using the updateToken method:

```php
// Update token when it expires
Yii::$app->ringcentralFax->updateToken('YOUR_NEW_JWT_TOKEN');
```

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
    if (strpos($e->getMessage(), 'JWT token has expired') !== false) {
        // Generate new token from RingCentral Developer Portal
        // and update it in your application
        $newToken = 'YOUR_NEW_JWT_TOKEN';
        Yii::$app->ringcentralFax->updateToken($newToken);
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

Note: JWT tokens have a fixed expiration time (typically 1 hour). When a token expires, you'll need to generate a new one from the Developer Portal.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
