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

There are two ways to configure the extension:

### 1. Using Private Key (Recommended)

This method allows automatic JWT token generation and renewal:

```php
'components' => [
    'ringcentralFax' => [
        'class' => 'ringcentral\fax\RingCentralFax',
        'clientId' => 'YOUR_CLIENT_ID',
        'clientSecret' => 'YOUR_CLIENT_SECRET',
        'serverUrl' => 'https://platform.ringcentral.com', // Use 'https://platform.devtest.ringcentral.com' for sandbox
        'privateKey' => '-----BEGIN PRIVATE KEY-----\nYour Private Key Here\n-----END PRIVATE KEY-----'
    ],
]
```

### 2. Using JWT Token

If you prefer to manage tokens manually:

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

## Setting Up Private Key Authentication

1. Go to RingCentral Developer Portal
2. Create or select your application
3. Under "Auth & Security":
   - Enable "JWT auth flow"
   - Generate a new private/public key pair
   - Download the private key
4. Copy the private key content into your configuration

With private key authentication:
- Tokens are automatically generated when needed
- Expired tokens are automatically renewed
- No manual token management required

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
    'privateKey' => getenv('RINGCENTRAL_PRIVATE_KEY'),
    'serverUrl' => getenv('RINGCENTRAL_SERVER_URL'),
],
```

2. Use environment-specific URLs:
```php
'serverUrl' => YII_DEBUG 
    ? 'https://platform.devtest.ringcentral.com' 
    : 'https://platform.ringcentral.com'
```

## License

This project is licensed under the MIT License - see the LICENSE file for details.
