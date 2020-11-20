<?php

namespace Vinlon\Laravel\WechatAuth;


use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class WechatAuthServiceProvider extends ServiceProvider
{
    const CONFIG_KEY = 'wechat-auth';
    private $configPath;

    /**
     * WechatAuthServiceProvider constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->configPath = __DIR__ . '/../publishes/config/' . self::CONFIG_KEY . '.php';
    }

    /**
     * @throws WechatAuthException
     * @throws BindingResolutionException
     */
    public function register()
    {
        //发布配置文件
        $this->publishes([
            $this->configPath => config_path(self::CONFIG_KEY . '.php')
        ], 'config');
        $this->mergeConfigFrom($this->configPath, self::CONFIG_KEY);

        // 在 auth config中增加 wxapp guard
        $this->mergeAuthConfig();

        //register routes
        $this->loadRoutesFrom(__DIR__ . '/route/wechat-auth.php');

        //register migration
        $this->loadMigrationsFrom(__DIR__ . '/migration/2020_11_11_082906_create_wx_user.php');
    }

    /**
     * @throws WechatAuthException
     * @throws BindingResolutionException
     */
    private function mergeAuthConfig()
    {
        $authConfigKey = 'auth';
        $providerName = 'wxusers';
        $guardName = 'wxapp';
        $wxUsersProvider = [
            'driver' => 'eloquent',
            'model' => \Vinlon\Laravel\WechatAuth\WxUser::class
        ];
        $wxAppGuard = [
            'driver' => 'jwt',
            'provider' => $providerName,
        ];
        $config = $this->app->make('config');
        $authConfig = $config->get($authConfigKey, []);
        if (array_key_exists($providerName, $authConfig['providers'])) {
            throw new WechatAuthException("the provider name $providerName is used");
        }
        if (array_key_exists($guardName, $authConfig['guards'])) {
            throw new WechatAuthException("the guard name $guardName is used");
        }
        $authConfig['providers'][$providerName] = $wxUsersProvider;
        $authConfig['guards'][$guardName] = $wxAppGuard;
        $config->set($authConfigKey, $authConfig);
    }
}