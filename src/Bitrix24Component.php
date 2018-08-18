<?php

namespace valkinaz\bitrix24;

use Yii;
use yii\base\{Component, InvalidConfigException, Configurable};
use yii\httpclient\Client;
use yii\web\HttpException;
use yii\helpers\Json;

class Bitrix24Component extends Component implements Configurable
{
    public $host;
    public $client_id;
    public $client_secret;
    public $application_uri;
    public $access_token;
    public $refresh_token;
    public $config_path;

    protected $request;
    protected $response;
    protected $reactivation = false;

    public function __construct($config = [])
    {
        if(!empty($config))
            Yii::configure($this, $config);

        if(!$this->host)
            throw new InvalidConfigException('The «host» cannot be empty.');

        if(!$this->client_id)
            throw new InvalidConfigException('The «client_id» cannot be empty.');

        if(!$this->client_secret)
            throw new InvalidConfigException('The «client_secret» cannot be empty.');

        if(!$this->application_uri)
            throw new InvalidConfigException('The «application_uri» cannot be empty.');

        if(!$this->access_token)
            throw new InvalidConfigException('The «access_token» cannot be empty.');

        if(!$this->refresh_token)
            throw new InvalidConfigException('The «refresh_token» cannot be empty.');

        if(!$this->config_path)
            throw new InvalidConfigException('The «config_path» cannot be empty.');

        parent::__construct();
    }

    public function CreateRequest($action, $method = 'POST', $data = [])
    {
        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);

        $this->request = $client->createRequest()
            ->setMethod($method)
            ->setUrl($this->host . 'rest/' . $action . '.json')
            ->setData($data);
    }

    public function SendRequest()
    {
        try
        {
            $this->AddAuth();
            $this->response = $this->request->send();
            $this->response->content = Json::decode($this->response->content);

            if(!$this->response->isOk)
            {
                if(!$this->reactivation)
                {
                    $this->reactivation = true;

                    if($this->UpdateAuthToken())
                        return $this->SendRequest();
                }

                $this->ThrowBitrixException();
            }
        }
        catch(Exception $e)
        {
            throw new HttpException(500);
        }
    }

    protected function UpdateAuthToken()
    {
        $url = 'https://oauth.bitrix.info/oauth/token/'
            . '?grant_type=refresh_token' 
            . '&client_id=' . $this->client_id
            . '&client_secret=' . $this->client_secret
            . '&redirect_uri=' . $this->application_uri
            . '&refresh_token=' . $this->refresh_token;

        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->send();

        $response->content = Json::decode($response->content);

        if(!$response->isOk)
            $this->ThrowBitrixException($response);

        return $this->WriteToConfig($response->content);
    }

    protected function AddAuth()
    {
        $this->request->addData(['auth' => $this->access_token]);
    }

    protected function ThrowBitrixException($response = false)
    {
        if(!$response)
            $response = $this->response;

        throw new HttpException(
            $response->getHeaders()->get('http-code'), 
            YII_DEBUG ? $response->content['error_description'] : ''
        );
    }

    protected function WriteToConfig($data)
    {
        $this->refresh_token = $data['refresh_token'];
        $this->access_token = $data['access_token'];

        $config = file_get_contents($this->config_path);
        $config = Json::decode($config);

        $config['refresh_token'] = $data['refresh_token'];
        $config['access_token'] = $data['access_token'];
        $config = Json::encode($config);

        return file_put_contents($this->config_path, $config);
    }
}