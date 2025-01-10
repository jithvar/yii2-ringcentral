# Yii2 RingCentral Fax Extension

This extension provides RingCentral Fax integration for Yii2 framework with OAuth 2.0 refresh token support.

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

### Basic Configuration

```php
'components' => [
    'ringcentralFax' => [
        'class' => 'ringcentral\fax\RingCentralFax',
        'clientId' => 'YOUR_CLIENT_ID',
        'clientSecret' => 'YOUR_CLIENT_SECRET',
        'serverUrl' => 'https://platform.ringcentral.com', // Use 'https://platform.devtest.ringcentral.com' for sandbox
        'accessToken' => 'YOUR_ACCESS_TOKEN',
        'refreshToken' => 'YOUR_REFRESH_TOKEN'
    ],
]
```

### Advanced Configuration with Token Refresh Callback

```php
'components' => [
    'ringcentralFax' => [
        'class' => 'ringcentral\fax\RingCentralFax',
        'clientId' => 'YOUR_CLIENT_ID',
        'clientSecret' => 'YOUR_CLIENT_SECRET',
        'serverUrl' => 'https://platform.ringcentral.com',
        'accessToken' => 'YOUR_ACCESS_TOKEN',
        'refreshToken' => 'YOUR_REFRESH_TOKEN',
        'tokenRefreshCallback' => function($tokens) {
            // Save new tokens to your storage
            Yii::$app->cache->set('ringcentral_access_token', $tokens['access_token']);
            Yii::$app->cache->set('ringcentral_refresh_token', $tokens['refresh_token']);
        }
    ],
]
```

## Token Management

This extension supports automatic token refresh:

1. When the access token expires, the extension will automatically:
   - Use the refresh token to get a new access token
   - Update both tokens internally
   - Retry the failed request
   - Call your tokenRefreshCallback (if configured) with the new tokens

2. If the refresh token expires:
   - You'll need to obtain new tokens from RingCentral
   - Update your configuration with the new tokens

## Getting Access and Refresh Tokens

1. Go to RingCentral Developer Portal
2. Create or select your application
3. Under "Auth & Security":
   - Enable "OAuth 2.0"
   - Enable "Issue refresh tokens"
4. Use the OAuth 2.0 flow to obtain initial access and refresh tokens
5. Store these tokens securely and use them in your configuration

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
    // Handle any errors
    Yii::error('Fax sending failed: ' . $e->getMessage());
}
```

## Best Practices

1. Store sensitive credentials securely:
```php
'ringcentralFax' => [
    'class' => 'ringcentral\fax\RingCentralFax',
    'clientId' => getenv('RINGCENTRAL_CLIENT_ID'),
    'clientSecret' => getenv('RINGCENTRAL_CLIENT_SECRET'),
    'accessToken' => getenv('RINGCENTRAL_ACCESS_TOKEN'),
    'refreshToken' => getenv('RINGCENTRAL_REFRESH_TOKEN'),
    'serverUrl' => getenv('RINGCENTRAL_SERVER_URL'),
],
```

2. Always implement the tokenRefreshCallback to persist new tokens:
```php
'tokenRefreshCallback' => function($tokens) {
    // Save to database
    Yii::$app->db->createCommand()
        ->update('settings', [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token']
        ], ['name' => 'ringcentral'])
        ->execute();
},
```

3. Use environment-specific URLs:
```php
'serverUrl' => YII_DEBUG 
    ? 'https://platform.devtest.ringcentral.com' 
    : 'https://platform.ringcentral.com'
```

## License

This project is licensed under the MIT License - see the LICENSE file for details.
