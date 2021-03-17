<?php

return [

    /**
     * 微信小程序 APP ID
     */
    'wxapp_app_id' => env('WECHAT_AUTH_WXAPP_APP_ID'),

    /**
     * 微信小程序 APP SECRET
     */
    'wxapp_app_secret' => env('WECHAT_AUTH_WXAPP_APP_SECRET'),

    /**
     * TEST_CODE, 只在开发环境生效，无需通过小程序获取真实的code即可测试登录接口
     */
    'test_code' => env('WECHAT_AUTH_TEST_CODE'),

];
