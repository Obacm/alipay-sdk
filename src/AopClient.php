<?php

namespace Obacm\Alipay;

class AopClient
{
    public $apiName = 'com.alipay.account.auth';

    public $appId;

    public $appName = 'mc';

    public $method = 'alipay.open.auth.sdk.code.get';

    public $authType = 'AUTHACCOUNT';

    public $bizType = 'openservice';

    public $pid;

    public $productId = 'APP_FAST_LOGIN';

    public $scope = 'kuaijie';

    public $targetId = 'kkkkkaaaa091125';

    public $signType = 'RSA2';

    public $sign;

    protected $privateKey;

    public $params;

    public function __construct()
    {
        $this->setAppId(config('payment.alipay.app_id'));
        $this->setPid(config('payment.alipay.pid'));
        $this->setPrivateKey(config('payment.alipay.private_key'));

        $this->params = [
            'apiname' => $this->apiName,
            'app_id' => $this->appId,
            'app_name' => $this->appName,
            'method' => $this->method,
            'auth_type' => $this->authType,
            'biz_type' => $this->bizType,
            'pid' => $this->pid,
            'product_id' => $this->productId,
            'scope' => $this->scope,
            'target_id' => $this->targetId,
            'sign_type' => $this->signType,
        ];

        $this->build();
    }

    public function build()
    {
        $query = http_build_query($this->params);
        $sign = $this->sign($query, $this->signType);

        return $query . '&' . $sign;
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

    public function setPid($value)
    {
        $this->pid = $value;

        return $this;
    }
}