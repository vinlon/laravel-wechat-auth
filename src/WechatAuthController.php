<?php


namespace Vinlon\Laravel\WechatAuth;


use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
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
        $code = request()->input('code');
        if (!$code) {
            throw new WechatAuthException('code is required');
        }
        $session = $this->wechatApp->code2Session($code);
        $appId = data_get($session, 'appid');
        $openid = data_get($session, 'openid');
        /** @var WxUser $user */
        $user = WxUser::findByAppOpenid($appId, $openid);
        if (!$user) {
            //保存用户信息，除app_id和openid外，其它字段设为空
            $user = new WxUser();
            $user->app_id = $appId;
            $user->openid = $openid;
            $this->saveWxUser($user);
        } else {
            //检查用户状态
            if ($user->status->isNot(WxUserStatus::ENABLED())) {
                throw new AuthenticationException();
            }
        }
        //登录
        return $this->processLogin($user);
    }

    /**
     * 小程序端先调用 wx.login 获取 code, 然后调用 wx.getUserInfo 获取到用户信息, 再调用此接口，每次都需要用户进行授权
     * 对于新用户而言，会登录并记录完整的用户信息
     * 对于老用户而言，会登录并更新用户信息
     * @throws WechatAuthException
     */
    public function freshLogin()
    {
        $param = request()->validate([
            'code' => 'required',
            'encrypted_data' => 'required',
            'iv' => 'required'
        ]);
        $session = $this->code2Session($param['code']);
        $appId = data_get($session, 'appid');
        $sessionKey = data_get($session, 'session_key');
        $userInfo = WxDataDecrypt::decrypt($appId, $sessionKey, $param['encrypted_data'], $param['iv']);
        $openid = $userInfo['openId'];
        $user = WxUser::findByAppOpenid($appId, $openid);
        if (!$user) {
            $user = new WxUser();
            $user->app_id = $appId;
            $user->openid = $openid;
        }
        //更新用户信息
        $this->saveWxUser($user, $userInfo);
        UserAdded::dispatch($user);

        //登录
        return $this->processLogin($user);
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
            ];
        }
        return $result;
    }
}