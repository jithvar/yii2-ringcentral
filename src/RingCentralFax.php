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
     * @var string JWT Token
     */
    public $jwtToken;

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
        if (!$this->jwtToken) {
            throw new InvalidConfigException('RingCentral JWT Token must be set');
        }

        $this->_platform = $this->getPlatform();
    }

    /**
     * Initialize RingCentral SDK with JWT authentication
     * @return \RingCentral\SDK\Platform\Platform
     */
    protected function getPlatform()
    {
        $rcsdk = new SDK($this->clientId, $this->clientSecret, $this->serverUrl, 'Yii2RingCentralFax/1.0.0');
        
        $platform = $rcsdk->platform();
        
        try {
            $platform->auth()->setData([
                'token_type' => 'Bearer',
                'access_token' => $this->jwtToken,
            ]);
            
        } catch (\Exception $e) {
            throw new \yii\base\Exception('RingCentral authentication failed: ' . $e->getMessage());
        }
        
        return $platform;
    }

    /**
     * Update the JWT token
     * @param string $newToken New JWT token from RingCentral Developer Portal
     */
    public function updateToken($newToken)
    {
        $this->jwtToken = $newToken;
        $this->_platform = $this->getPlatform();
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
            if (strpos($e->getMessage(), 'token_expired') !== false || 
                strpos($e->getMessage(), 'Refresh token has expired') !== false) {
                throw new \yii\base\Exception(
                    'JWT token has expired. Please generate a new token from RingCentral Developer Portal ' .
                    'and update it using updateToken() method.'
                );
            }
            throw $e;
        }
    }
}
