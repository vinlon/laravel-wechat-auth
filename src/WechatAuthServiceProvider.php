<?php

namespace Vinlon\Laravel\WechatAuth;


use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class WechatAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        //发布配置文件
        $this->publishes([
            $this->getConfigPath() => config_path('wechat-auth.php')
        ], 'config');
        $this->mergeConfigFrom($this->getConfigPath(), 'wechat-auth');

        // 在 auth config中增加 wxapp guard
        $this->mergeAuthConfig();

        //register routes
        $this->loadRoutesFrom(__DIR__ . '/route/wechat-auth.php');

        //register migration
        $this->loadMigrationsFrom(__DIR__ . '/migration/2020_11_11_082906_create_wx_user.php');
    }

    private function mergeAuthConfig()
    {
        $config = $this->app->make('config');
        $authConfig = $config->get('auth');
        $authConfig['guards']['wxapp'] = [
            'driver' => 'jwt',
            'provider' => 'wxusers',
        ];
        $authConfig['providers']['wxusers'] = [
            'driver' => 'eloquent',
            'model' => \Vinlon\Laravel\WechatAuth\WxUser::class,
        ];
        $config->set('auth', $authConfig);
    }

    private function getConfigPath()
    {
        return __DIR__ . '/../publishes/config/wechat-auth.php';
    }
}