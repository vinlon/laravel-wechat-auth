<?php


namespace Vinlon\Laravel\WechatAuth\Events;


use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vinlon\Laravel\WechatAuth\WxUser;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    /**
     * @var WxUser
     */
    public $wxUser;

    /**
     * UserLoggedIn constructor.
     * @param WxUser $wxUser
     */
    public function __construct(WxUser $wxUser)
    {
        $this->wxUser = $wxUser;
    }
}