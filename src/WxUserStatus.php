<?php


namespace Vinlon\Laravel\WechatAuth;


use BenSampo\Enum\Enum;

/**
 * @method static static ENABLED()
 * @method static static DISABLED()
 */
class WxUserStatus extends Enum
{
    const ENABLED = 'enabled';
    const DISABLED = 'disabled';

    public static function getDescription($value): string
    {
        if ($value === self::ENABLED) {
            return '启用';
        }
        if ($value === self::DISABLED) {
            return '禁用';
        }
        return parent::getDescription($value);
    }

    public function toArray()
    {
        return $this;
    }
}