<?php

use Illuminate\Support\Facades\Route;

Route::prefix('wxapp')->namespace('Vinlon\Laravel\WechatAuth')->group(function () {
    // 快速登录
    Route::post('fast_login', 'WechatAuthController@fastLogin');

    // 注册/更新用户信息并登录
    Route::post('fresh_login', 'WechatAuthController@refreshLogin');

    Route::middleware('auth:wxapp')->group(function () {
        // 获取用户信息
        Route::get('profile', 'WechatAuthController@getProfile');
        // 更新用户信息
        Route::post('profile', 'WechatAuthController@updateProfile');
        // 绑定手机号
        Route::post('mobile', 'WechatAuthController@bindMobile');
    });
});
