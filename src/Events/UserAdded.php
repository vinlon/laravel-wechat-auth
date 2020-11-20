<?php


namespace Vinlon\Laravel\WechatAuth\Events;


use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vinlon\Laravel\WechatAuth\WxUser;

class UserAdded
{
    use Dispatchable, SerializesModels;

    /**
     * @var WxUser
     */
    public $wxUser;

    /**
     * UserAdded constructor.
     * @param WxUser $wxUser
     */
    public function __construct(WxUser $wxUser)
    {
        $this->wxUser = $wxUser;
    }


}