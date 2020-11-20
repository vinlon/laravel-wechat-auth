# Laravel Wechat Auth

## 小程序登录
### 路由定义

- wxapp/fast_login

- wxapp/fresh_login

- wxapp/refresh_login

### 用法

1. 引用package 

2. 发布配置国文件

3. 添加配置

4. 使用auth middleware

```
Route::group(['middleware' => ['auth:wechat']], function () {
    //这里放置你的需要登录的 api 路由，如用户资料API、修改资料API...
});

```

5. JWT Token 传递方式

```
# Authorization header
Authorization: Bearer eyJhbGciOiJIUzI1NiI...

# Query String
http://example.dev/me?token=eyJhbGciOiJIUzI1NiI...

```

### 环境变量

```
WXCHAT_AUTH_WXAPP_APPID:
WXCHAT_AUTH__WXAPP_APPSECRET:
```

### 异常

WechatAuthException 

### 事件

- UserAdded

由于用户记录是在用户第一次调用login接口时创建的，此时记录中并不包含除app_id和openid以外的其它信息

- UserUpdated

当用户调用refresh_login接口时，如果用户信息发生变化，则会触发该事件

- UserLoggedIn

用户登录成功后会触发该事件


### WxAppGuard 配置

下面的配置不需要手动设置，已经通过WechatAuthServiceProvider自动将配置添加到auth config中

```
# guards
'guards' => [
	'wxapp' => [
	    'driver' => 'jwt',
	    'provider' => 'wxusers',
	],
],

# providers
'providers' => [
    'wxusers' => [
        'driver' => 'eloquent',
        'model' => vinlon\Laravel\WechatAuth\Models\WxUser::class,
    ],
]
```

## 引用
https://github.com/tymondesigns/jwt-auth
https://github.com/overtrue/laravel-wechat


## 参考
[xiaohuilam/laravel-wxapp-login](https://github.com/xiaohuilam/laravel-wxapp-login)
[为什么用jwt-auth而不是laravel/passport](https://stackoverflow.com/questions/45532514/laravel-passport-vs-jwt)