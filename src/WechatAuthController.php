<?php


namespace Vinlon\Laravel\WechatAuth;


use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Tymon\JWTAuth\JWTGuard;
use Vinlon\Laravel\WechatAuth\Events\UserAdded;
use Vinlon\Laravel\WechatAuth\Events\UserLoggedIn;
use Vinlon\Laravel\WechatAuth\Events\UserUpdated;

class WechatAuthController extends Controller
{
    use ValidatesRequests;

    const WECHAT_AUTH_GUARD_NAME = 'wxapp';

    /**
     * @var JWTGuard
     */
    private $auth;

    /**
     * @var WechatApplication
     */
    private $wechatApp;

    /**
     * AuthController constructor.
     * @param WechatApplication $wechatApp
     */
    public function __construct(WechatApplication $wechatApp)
    {
        $this->auth = auth()->guard(self::WECHAT_AUTH_GUARD_NAME);
        $this->wechatApp = $wechatApp;
    }


    /**
     * 快速登录，只记录用户的openid,因此小程序端不需要进行授权
     * @throws WechatAuthException
     * @throws AuthenticationException
     */
    public function fastLogin()
    {
        $param = request()->validate([
            'code' => 'required',
        ]);
        $code = $param['code'];

        $testCodePrefix = config('wechat-auth.test_code_prefix');
        $debug = config('app.debug', false);
        if ($debug && Str::startsWith($code, $testCodePrefix)) {
            $appId = config('wechat-auth.wxapp_app_id') ?: 'app_id_not_set';
            $openid = $code;
            $unionId = '';
        } else {
            $session = $this->wechatApp->code2Session($code);
            $appId = data_get($session, 'appid');
            $openid = data_get($session, 'openid');
            $unionId = data_get($session, 'unionid', '');
        }

        /** @var WxUser $user */
        $user = WxUser::findByAppOpenid($appId, $openid);
        if (!$user) {
            //保存用户信息，除app_id和openid外，其它字段设为空
            $user = new WxUser();
            $user->app_id = $appId;
            $user->openid = $openid;
            $user->unionid = $unionId;
            $this->saveWxUser($user);
        } else {
            $user->unionid = $unionId;
        }
        //登录
        return $this->processLogin($user);
    }


    /**
     * 绑定手机号
     */
    public function bindMobile()
    {
        $param = request()->validate([
            'code' => 'required',
            'encrypted_data' => 'required',
            'iv' => 'required'
        ]);

        $session = $this->wechatApp->code2Session($param['code']);
        $appId = data_get($session, 'appid');
        $openid = data_get($session, 'openid');
        $sessionKey = data_get($session, 'session_key');
        $mobileInfo = WxDataDecrypt::decrypt($appId, $sessionKey, $param['encrypted_data'], $param['iv']);
        $user = WxUser::findByAppOpenid($appId, $openid);
        $user->mobile = data_get($mobileInfo, 'purePhoneNumber');
        $user->save();

        return [];
    }

    /**
     * 获取用户信息
     * @return array
     */
    public function getProfile()
    {
        /** @var WxUser $user */
        $user = $this->auth->user();

        return [
            'nickName' => $user->nickname,
            'country' => $user->country,
            'province' => $user->province,
            'city' => $user->city,
            'gender' => $user->gender,
            'avatarUrl' => $user->avatar_url,
            'mobile' => $user->mobile,
        ];
    }

    /**
     * 保存用户信息
     * @return array
     */
    public function updateProfile()
    {
        /** @var WxUser $user */
        $user = $this->auth->user();
        $param = request()->validate([
            'nickName' => 'required',
            'country' => 'present',
            'province' => 'present',
            'city' => 'present',
            'gender' => 'present',
            'avatarUrl' => 'required'
        ]);
        $user = $this->saveWxUser($user, $param);
        return [];
    }

    /**
     * 保存用户信息,如果是新增用户会触发 UserAdded 事件, 否则触发 UserUpdated 事件
     * @param WxUser $wxUser
     * @param array | null $userInfo
     * @return WxUser
     */
    private function saveWxUser(WxUser $wxUser, $userInfo = null)
    {
        $isNewUser = is_null($wxUser->id);
        if ($userInfo) {
            $wxUser->nickname = $userInfo['nickName'] ?? '';
            $wxUser->country = $userInfo['country'] ?? '';
            $wxUser->province = $userInfo['province'] ?? '';
            $wxUser->city = $userInfo['city'] ?? '';
            $wxUser->gender = $userInfo['gender'] ?? 0;
            $wxUser->avatar_url = $userInfo['avatarUrl'] ?? '';
        }
        $wxUser->save();

        if ($isNewUser) {
            UserAdded::dispatch($wxUser);
        } else {
            UserUpdated::dispatch($wxUser);
        }
        return $wxUser;
    }


    /**
     * 登录并触发 UserLoggedIn 事件
     * @param WxUser $user
     * @return array
     */
    private function processLogin(WxUser $user)
    {
        $token = $this->auth->login($user);
        UserLoggedIn::dispatch($user);
        return $this->respondWithJwtToken($token, $user);
    }

    private function respondWithJwtToken($token, WxUser $user)
    {
        $result = [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];
        if (!empty($user->nickname)) {
            $result['user_info'] = [
                'nickName' => $user->nickname ?? '',
                'gender' => $user->gender ?? 0,
                'country' => $user->country ?? '',
                'city' => $user->city ?? '',
                'province' => $user->province ?? '',
                'avatarUrl' => $user->avatar_url ?? '',
                'mobile' => $user->mobile ?? '',
            ];
        }
        return $result;
    }
}