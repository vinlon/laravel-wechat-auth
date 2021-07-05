<?php


namespace Vinlon\Laravel\WechatAuth;


use BenSampo\Enum\Traits\CastsEnums;
use DateTimeInterface;
use Illuminate\Foundation\Auth\User as AuthUser;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $app_id 微信公众号appid
 * @property string $openid 用户openid
 * @property string $nickname 用户昵称
 * @property string $mobile 用户手机号
 * @property int $gender 性别，0:未知，1:男，2:女
 * @property string $country 所在国家
 * @property string $province 所在省份
 * @property string $city 所在城市
 * @property string $avatar_url 头像链接
 * @property WxUserStatus $status 用户状态
 * @method static \Illuminate\Database\Eloquent\Builder|WxUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WxUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WxUser query()
 */
class WxUser extends AuthUser implements JWTSubject
{
    use CastsEnums;

    protected $casts = [
        'status' => WxUserStatus::class
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public static function findByAppOpenid($appId, $openid)
    {
        return static::query()->where([
            'app_id' => $appId,
            'openid' => $openid
        ])->first();
    }
}
