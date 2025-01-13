# Yii2 RingCentral Fax Extension

This extension provides RingCentral Fax integration for Yii2 framework with OAuth 2.0 support.

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
        'redirectUrl' => 'https://your-app.com/ringcentral/callback',
        'tokenRefreshCallback' => function($tokens) {
            // Save new tokens to your storage
            Yii::$app->cache->set('ringcentral_access_token', $tokens['access_token']);
            Yii::$app->cache->set('ringcentral_refresh_token', $tokens['refresh_token']);
        }
    ],
]
```

## OAuth 2.0 Setup

1. Go to RingCentral Developer Portal
2. Select your application
3. Under "Auth & Security":
   - Enable "OAuth 2.0"
   - Enable "Issue refresh tokens"
   - Add your redirect URI (e.g., `https://your-app.com/ringcentral/callback`)

## Implementing OAuth Flow

1. Create a controller to handle the OAuth flow:

```php
namespace app\controllers;

use Yii;
use yii\web\Controller;

class RingCentralController extends Controller
{
    /**
     * Initiates OAuth flow
     */
    public function actionAuth()
    {
        // Generate a random state for CSRF protection
        $state = Yii::$app->security->generateRandomString();
        Yii::$app->session->set('ringcentral_state', $state);

        // Get authorization URL and redirect
        $authUrl = Yii::$app->ringcentralFax->getAuthorizationUrl($state);
        return $this->redirect($authUrl);
    }

    /**
     * Handles OAuth callback
     */
    public function actionCallback()
    {
        // Verify state parameter
        $state = Yii::$app->request->get('state');
        $savedState = Yii::$app->session->get('ringcentral_state');
        
        if (!$state || $state !== $savedState) {
            throw new \yii\web\BadRequestHttpException('Invalid state parameter');
        }

        // Exchange authorization code for tokens
        $code = Yii::$app->request->get('code');
        try {
            $tokens = Yii::$app->ringcentralFax->handleOAuthCallback($code);
            
            // Tokens are automatically saved via tokenRefreshCallback
            Yii::$app->session->setFlash('success', 'Successfully connected to RingCentral');
            return $this->redirect(['site/index']);
            
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Failed to connect to RingCentral: ' . $e->getMessage());
            return $this->redirect(['site/index']);
        }
    }
}
```

2. Add routes in `config/web.php`:

```php
'urlManager' => [
    'enablePrettyUrl' => true,
    'rules' => [
        'ringcentral/auth' => 'ring-central/auth',
        'ringcentral/callback' => 'ring-central/callback',
    ],
],
```

3. Add a link to start the OAuth flow:

```php
use yii\helpers\Html;

echo Html::a('Connect RingCentral', ['ring-central/auth'], ['class' => 'btn btn-primary']);
```

## Token Management

The extension handles token management automatically:

1. When tokens are first obtained via OAuth:
   - Both access and refresh tokens are saved via your `tokenRefreshCallback`
   - The tokens are used for subsequent API calls

2. When the access token expires:
   - The extension automatically uses the refresh token to get a new access token
   - Your `tokenRefreshCallback` is called with the new tokens
   - The failed request is automatically retried

3. If the refresh token expires:
   - The user will need to re-authenticate via OAuth
   - You can catch this case by checking for the 'refresh_token_expired' error

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
    if (strpos($e->getMessage(), 'refresh_token_expired') !== false) {
        // Redirect user to re-authenticate
        return $this->redirect(['ring-central/auth']);
    }
    // Handle other errors
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
    'redirectUrl' => getenv('RINGCENTRAL_REDIRECT_URL'),
    'serverUrl' => getenv('RINGCENTRAL_SERVER_URL'),
],
```

2. Use environment-specific URLs:
```php
'serverUrl' => YII_DEBUG 
    ? 'https://platform.devtest.ringcentral.com' 
    : 'https://platform.ringcentral.com',
'redirectUrl' => YII_DEBUG
    ? 'http://localhost:8080/ringcentral/callback'
    : 'https://your-app.com/ringcentral/callback',
```

3. Always implement the tokenRefreshCallback to persist new tokens:
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

## License

This project is licensed under the MIT License - see the LICENSE file for details.
