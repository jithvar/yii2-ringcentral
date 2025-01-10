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

## Usage

```php
// Send a fax
Yii::$app->ringcentralFax->send([
    'to' => '+1234567890',
    'files' => ['/path/to/file.pdf'],
    'text' => 'Optional cover page text'
]);
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
