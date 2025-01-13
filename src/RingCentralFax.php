<?php

namespace ringcentral\fax;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use RingCentral\SDK\SDK;
use RingCentral\SDK\Http\ApiException;

class RingCentralFax extends Component
{
    /**
     * @var string RingCentral Client ID
     */
    public $clientId;

    /**
     * @var string RingCentral Client Secret
     */
    public $clientSecret;

    /**
     * @var string RingCentral Server URL
     */
    public $serverUrl = 'https://platform.ringcentral.com';

    /**
     * @var string OAuth Redirect URL
     */
    public $redirectUrl;

    /**
     * @var string Access Token
     */
    public $accessToken;

    /**
     * @var string Refresh Token
     */
    public $refreshToken;

    /**
     * @var callable Optional callback when tokens are refreshed
     */
    public $tokenRefreshCallback;

    /**
     * @var SDK RingCentral SDK instance
     */
    private $_platform;

    /**
     * @var SDK RingCentral SDK instance
     */
    private $_rcsdk;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (!$this->clientId) {
            throw new InvalidConfigException('RingCentral Client ID must be set');
        }
        if (!$this->clientSecret) {
            throw new InvalidConfigException('RingCentral Client Secret must be set');
        }
        if (!$this->redirectUrl) {
            throw new InvalidConfigException('OAuth Redirect URL must be set');
        }

        $this->_rcsdk = new SDK($this->clientId, $this->clientSecret, $this->serverUrl, 'Yii2RingCentralFax/1.0.0');
        
        // Only initialize platform if we have tokens
        if ($this->accessToken && $this->refreshToken) {
            $this->_platform = $this->getPlatform();
        }
    }

    /**
     * Get the OAuth authorization URL
     * @param string $state Optional state parameter for CSRF protection
     * @return string Authorization URL
     */
    public function getAuthorizationUrl($state = null)
    {
        return $this->_rcsdk->platform()->authUrl([
            'redirectUri' => $this->redirectUrl,
            'state' => $state
        ]);
    }

    /**
     * Handle OAuth callback and get tokens
     * @param string $code Authorization code from callback
     * @return array Array containing access_token and refresh_token
     * @throws \Exception if token exchange fails
     */
    public function handleOAuthCallback($code)
    {
        try {
            $response = $this->_rcsdk->platform()->login([
                'code' => $code,
                'redirect_uri' => $this->redirectUrl
            ]);

            $tokenData = $response->json();

            // Update tokens
            $this->accessToken = $tokenData['access_token'];
            $this->refreshToken = $tokenData['refresh_token'];

            // Initialize platform with new tokens
            $this->_platform = $this->getPlatform();

            // Notify about token refresh if callback is set
            if (is_callable($this->tokenRefreshCallback)) {
                call_user_func($this->tokenRefreshCallback, [
                    'access_token' => $this->accessToken,
                    'refresh_token' => $this->refreshToken
                ]);
            }

            return [
                'access_token' => $this->accessToken,
                'refresh_token' => $this->refreshToken
            ];

        } catch (\Exception $e) {
            throw new \yii\base\Exception('Failed to exchange authorization code: ' . $e->getMessage());
        }
    }

    /**
     * Initialize RingCentral SDK
     * @return \RingCentral\SDK\Platform\Platform
     */
    protected function getPlatform()
    {
        $platform = $this->_rcsdk->platform();
        
        try {
            $platform->auth()->setData([
                'token_type' => 'Bearer',
                'access_token' => $this->accessToken,
                'refresh_token' => $this->refreshToken
            ]);
            
        } catch (\Exception $e) {
            throw new \yii\base\Exception('RingCentral authentication failed: ' . $e->getMessage());
        }
        
        return $platform;
    }

    /**
     * Refresh the access token using refresh token
     * @throws \Exception if refresh fails
     */
    protected function refreshAccessToken()
    {
        try {
            $response = $this->_platform->refresh();
            $tokenData = $response->json();

            // Update tokens
            $this->accessToken = $tokenData['access_token'];
            $this->refreshToken = $tokenData['refresh_token'];

            // Notify about token refresh if callback is set
            if (is_callable($this->tokenRefreshCallback)) {
                call_user_func($this->tokenRefreshCallback, [
                    'access_token' => $this->accessToken,
                    'refresh_token' => $this->refreshToken
                ]);
            }

            // Reinitialize platform with new tokens
            $this->_platform = $this->getPlatform();

        } catch (\Exception $e) {
            throw new \yii\base\Exception('Failed to refresh token: ' . $e->getMessage());
        }
    }

    /**
     * Send fax
     * @param array $params Parameters for sending fax
     * @return array Response from RingCentral API
     * @throws ApiException
     */
    public function send($params)
    {
        if (!isset($params['to']) || !isset($params['files'])) {
            throw new InvalidConfigException('Both "to" and "files" parameters are required');
        }

        if (!$this->_platform) {
            throw new InvalidConfigException('Authentication required. Please obtain tokens first.');
        }

        try {
            $request = $this->_platform->post('/restapi/v1.0/account/~/extension/~/fax', [
                'to' => [['phoneNumber' => $params['to']]],
                'faxResolution' => 'High',
            ]);

            // Add files to the request
            foreach ($params['files'] as $file) {
                $request->addFile($file);
            }

            // Add cover page text if provided
            if (isset($params['text'])) {
                $request->addText($params['text']);
            }

            $response = $request->send();
            return $response->json();
            
        } catch (ApiException $e) {
            if (strpos($e->getMessage(), 'token_expired') !== false) {
                // Try to refresh token and retry the request
                $this->refreshAccessToken();
                return $this->send($params);
            }
            
            if (strpos($e->getMessage(), 'refresh_token_expired') !== false) {
                throw new \yii\base\Exception(
                    'Refresh token has expired. Please re-authenticate.'
                );
            }
            
            throw $e;
        }
    }
}
