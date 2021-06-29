<?php

use Illuminate\Support\Facades\Route;

Route::prefix('wxapp')
    ->middleware([\Vinlon\Laravel\WechatAuth\AcceptJson::class])
    ->namespace('Vinlon\Laravel\WechatAuth')->group(function () {
        // 快速登录
        Route::post('fast_login', 'WechatAuthController@fastLogin');

        Route::middleware('auth:wxapp')->group(function () {
            // 获取用户信息
            Route::get('profile', 'WechatAuthController@getProfile');
            // 更新用户信息
            Route::post('profile', 'WechatAuthController@updateProfile');
            // 绑定手机号
            Route::post('mobile', 'WechatAuthController@bindMobile');
        });
    });
