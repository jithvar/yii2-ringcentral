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
     * @var string RingCentral Username
     */
    public $username;

    /**
     * @var string RingCentral Extension
     */
    public $extension;

    /**
     * @var string RingCentral Password
     */
    public $password;

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
        if (!$this->username) {
            throw new InvalidConfigException('RingCentral Username must be set');
        }
        if (!$this->password) {
            throw new InvalidConfigException('RingCentral Password must be set');
        }

        $this->_platform = $this->getPlatform();
    }

    /**
     * Initialize RingCentral SDK
     * @return \RingCentral\SDK\Platform\Platform
     */
    protected function getPlatform()
    {
        $rcsdk = new SDK($this->clientId, $this->clientSecret, $this->serverUrl);
        
        $platform = $rcsdk->platform();
        $platform->login($this->username, $this->extension, $this->password);
        
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

        try {
            $response = $request->send();
            return $response->json();
        } catch (ApiException $e) {
            throw $e;
        }
    }
}
