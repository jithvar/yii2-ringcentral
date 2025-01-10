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
        if (!$this->accessToken) {
            throw new InvalidConfigException('RingCentral Access Token must be set');
        }
        if (!$this->refreshToken) {
            throw new InvalidConfigException('RingCentral Refresh Token must be set');
        }

        $this->_platform = $this->getPlatform();
    }

    /**
     * Initialize RingCentral SDK
     * @return \RingCentral\SDK\Platform\Platform
     */
    protected function getPlatform()
    {
        $rcsdk = new SDK($this->clientId, $this->clientSecret, $this->serverUrl, 'Yii2RingCentralFax/1.0.0');
        
        $platform = $rcsdk->platform();
        
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
                    'Refresh token has expired. Please obtain new access and refresh tokens.'
                );
            }
            
            throw $e;
        }
    }
}
