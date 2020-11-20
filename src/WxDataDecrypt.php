<?php


namespace Vinlon\Laravel\WechatAuth;


class WxDataDecrypt
{
    /**
     * @param string $appId
     * @param string $sessionKey
     * @param string $encryptedData
     * @param string $iv
     * @return mixed
     * @throws WechatAuthException
     */
    public static function decrypt(string $appId, string $sessionKey, string $encryptedData, string $iv)
    {
        $sessionKey = base64_decode($sessionKey);
        $iv = base64_decode($iv);
        $encryptedData = base64_decode($encryptedData);
        self::validateKey($sessionKey);
        self::validateIv($iv);

        $json = openssl_decrypt($encryptedData, self::getMode($sessionKey), $sessionKey, OPENSSL_RAW_DATA, $iv);
        $data = json_decode($json, true);
        if (!$data) {
            throw new WechatAuthException('invalid encrypted_data');
        }
        if ($data['watermark']['appid'] != $appId) {
            throw new WechatAuthException('watermark not match');
        }
        return $data;
    }

    /**
     * @param string $key
     * @return string
     */
    private static function getMode(string $key)
    {
        return 'aes-' . (8 * strlen($key)) . '-cbc';
    }


    /**
     * @param string $key
     * @throws WechatAuthException
     */
    private static function validateKey(string $key)
    {
        if (!in_array(strlen($key), [16, 24, 32], true)) {
            throw new WechatAuthException('invalid sessionKey');
        }
    }


    /**
     * @param string $iv
     * @throws WechatAuthException
     */
    private static function validateIv(string $iv)
    {
        if (!empty($iv) && 16 !== strlen($iv)) {
            throw new WechatAuthException('invalid iv');
        }
    }
}
