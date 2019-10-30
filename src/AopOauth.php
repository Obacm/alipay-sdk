<?php

namespace Obacm\Alipay;

use GuzzleHttp\Client;

class AopOauth
{
    public $apiName = 'https://openapi.alipay.com/gateway.do?';

    public $apiOauthToken = 'alipay.system.oauth.token';

    public $apiUserInfo = 'alipay.user.info.share';

    public $appId;

    public $signType = 'RSA2';

    public $version = '1.0';

    public $charset = 'UTP-8';

    public $format = 'json';

    public $params;

    protected $privateKey;

    public function __construct()
    {
        $this->setAppId(config('payment.alipay.app_id'));
        $this->setPrivateKey(config('payment.alipay.private_key'));
    }

    public function build($query)
    {
        $sign = $this->sign($this->params, $this->signType);

        return $query . '&sign=' . $sign;
    }

    public function buildUserInfoParams($accessToken)
    {
        $userInfoParams = [
            'auth_token' => $accessToken,
        ];

        $this->buildParams($this->apiUserInfo);

        $query = http_build_query(array_merge($this->params, $userInfoParams));

        return $this->build($query);
    }

    public function buildOauthCodeParams($code, $refreshToken = '')
    {
        $oauthCodeParams = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'refresh_token' => $refreshToken,
        ];

        $this->buildParams($this->apiOauthToken);

        $query = http_build_query(array_merge($this->params, $oauthCodeParams));

        return $this->build($query);
    }

    public function buildParams($method)
    {
        $this->params = [
            'app_id' => $this->appId,
            'method' => $method,
            'format' => $this->format,
            'charset' => $this->charset,
            'sign_type' => $this->signType,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->version,
        ];
    }

    public function getUserInfoByAccessToken($accessToken)
    {
        $param = $this->buildUserInfoParams($accessToken);

        $client = new Client();

        $res = $client->request('GET', $this->apiName . $param);

        $body = json_decode($res->getBody(), false);

        $response = $body->alipay_user_info_share_response;

        if (isset($response->error_response)) {
            return ['code' => $response->code, 'msg' => $response->sub_msg];
        }
        return $response;
    }

    public function getAccessToken($authCode)
    {
        $param = $this->buildOauthCodeParams($authCode);

        $client = new Client();

        $res = $client->request('GET', $this->apiName . $param);

        $body = json_decode($res->getBody(), false);

        $response = $body->alipay_system_oauth_token_response;

        if (isset($response->error_response)) {
            return ['code' => $response->code, 'msg' => $response->sub_msg];
        }
        return $response;
    }

    protected function sign($params, $signType)
    {
        $signer = new Signer($params);
        $signer->setIgnores(['sign']);

        $signType = strtoupper($signType);

        if ($signType === 'RSA') {
            $sign = $signer->signWithRSA($this->getPrivateKey());
        } elseif ($signType === 'RSA2') {
            $sign = $signer->signWithRSA($this->getPrivateKey(), OPENSSL_ALGO_SHA256);
        } else {
            throw new \Exception('The signType is invalid');
        }

        return $sign;
    }

    /**
     * @return mixed
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function setPrivateKey($value)
    {
        $this->privateKey = $value;

        return $this;
    }

    public function setAppId($value)
    {
        $this->appId = $value;

        return $this;
    }
}