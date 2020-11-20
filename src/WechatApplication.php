<?php


namespace Vinlon\Laravel\WechatAuth;


use Illuminate\Support\Facades\Http;

class WechatApplication
{
    private $appId;
    private $appSecret;
    private $apiHost = 'https://api.weixin.qq.com/';

    /**
     * WechatApplication constructor.
     */
    public function __construct()
    {
        $wechatAuthConfig = config('wechat-auth');
        $this->appId = $wechatAuthConfig['wxapp_app_id'] ?? '';
        $this->appSecret = $wechatAuthConfig['wxapp_app_secret'] ?? '';
    }

    public function code2Session($code)
    {
        $params = [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code',
        ];
        $responseData = Http::get(
            $this->apiHost . 'sns/jscode2session',
            $params
        )->json();
        if (isset($responseData['errcode']) && $responseData['errcode'] != 0) {
            $msg = $responseData['errmsg'] ?? 'unknown error';
            throw new WechatAuthException($msg);
        }
        $responseData['appid'] = $this->appId;
        return $responseData;
    }
}