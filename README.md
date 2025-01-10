# Yii2 RingCentral Fax Extension

This extension provides RingCentral Fax integration for Yii2 framework.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist your-vendor/yii2-ringcentral-fax
```

or add

```
"your-vendor/yii2-ringcentral-fax": "*"
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
        'username' => 'YOUR_USERNAME',
        'extension' => 'YOUR_EXTENSION', // Optional
        'password' => 'YOUR_PASSWORD'
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

## License

This project is licensed under the MIT License - see the LICENSE file for details.
