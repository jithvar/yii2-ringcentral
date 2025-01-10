<?php

namespace ringcentral\fax;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use RingCentral\SDK\SDK;
use RingCentral\SDK\Http\ApiException;
use Firebase\JWT\JWT;

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
     * @var string Private Key for JWT generation
     */
    public $privateKey;

    /**
     * @var string JWT Token (will be auto-generated if privateKey is provided)
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
        if (!$this->privateKey && !$this->jwtToken) {
            throw new InvalidConfigException('Either privateKey or jwtToken must be set');
        }

        if ($this->privateKey) {
            $this->generateJwtToken();
        }

        $this->_platform = $this->getPlatform();
    }

    /**
     * Generate a new JWT token using the private key
     */
    protected function generateJwtToken()
    {
        $now = time();
        $payload = [
            'iss' => $this->clientId,
            'sub' => $this->clientId,
            'aud' => $this->serverUrl,
            'exp' => $now + 3600, // Token expires in 1 hour
            'iat' => $now,
        ];

        try {
            $this->jwtToken = JWT::encode($payload, $this->privateKey, 'RS256');
        } catch (\Exception $e) {
            throw new \yii\base\Exception('Failed to generate JWT token: ' . $e->getMessage());
        }
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
                if ($this->privateKey) {
                    // Generate new token and retry
                    $this->generateJwtToken();
                    $this->_platform = $this->getPlatform();
                    return $this->send($params);
                }
                throw new \yii\base\Exception(
                    'JWT token has expired. Please provide a new token or configure privateKey for automatic token generation.'
                );
            }
            throw $e;
        }
    }
}
