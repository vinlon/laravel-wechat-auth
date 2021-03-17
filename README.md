# Laravel Wechat Auth

## 小程序登录

### 使用指引

1. #### 引入package 

    ```shell script
    composer require vinlon/laravel-wechat-auth
    ```

2. #### 发布config文件 (Laravel 版本 > 5.5)

    先执行如下命令
    
    ```shell script
    php artisan vendor:publish --provider="Vinlon\Laravel\WechatAuth\WechatAuthServiceProvider"
    ```
    
    在应用程序的config目录下，将生成wechat-auth.php文件（注：一般情况下，此文件不需要做任何修改，配置的调整通过环境变量实现）

3. 环境变量

    配置小程序的APPID 和 APPSECRET
    
    ```
    WECHAT_AUTH_WXAPP_APP_ID=
    WECHAT_AUTH_WXAPP_APP_SECRET=
    ```

4. 创建数据库表

    ```
    php artisan migrate
    ```
   
5. 生成JWT_SECRET
    
   生成JWT_SECRET, 并自动写入根目录下的.env文件
    
   ```
   php artisan jwt:secret
   ```

6. 根据实际情况调用 fast_login , fresh_login 或 profile 进行登录

    详见[接口说明](#接口说明)

7. 使用 auth middleware 对请求的登录状态进行验证

    ```
    Route::group(['middleware' => ['auth:wxapp']], function () {
        //这里放置你的需要登录的 api 路由
    });
    ```

### 接口说明

#### fast_login

快速登录，小程序端不需要进行授权, 但对于新用户来说，用户表中只会记录openid, 如果需要用户昵称、头像等，需要调用 profile 接口提交用户信息 

接口地址: wxapp/fast_login  
请求方式：POST  
INPUT:  

| 参数 | 说明                             |
| ---- | -------------------------------- |
| code | 微信客户端调用wx.login得到的code |

OUTPUT:

| 参数         | 说明                                                         |
| ------------ | ------------------------------------------------------------ |
| access_token | JWT Token, 具体使用方式见 [JWT Token 使用说明](#JWT Token 使用说明) |
| token_type   | Bearer                                                       |
| expires_in   | access_token有效期                                           |
| user_info    | 字段命令和微信getUserInfo的返回值保持一致，如果<br/>如果数据中未保存用户信息，则不包含此字段 |

注： 如果用户被禁用，则该接口将返回 `401:Unauthenticated`

示例

```js
{
  "access_token": "eyJ0eXAi...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "user_info": {
    "nickName": "测试数据",
    "gender": 1,
    "country": "中国",
    "province": "北京",
    "city": "北京",
    "avatarUrl": "https://thirdwx.qlogo.cn/mm..."
  }
}
```

#### fresh_login

对于新用户，此接口会在创建用户的同时完善用户信息，但对于老用户，仍然需要每次登录时进行授权，无法完成静默登录。

接口地址：wxapp/fresh_login

请求方式： POST

| 参数           | 说明                                                         |
| -------------- | ------------------------------------------------------------ |
| code           | 微信客户端调用wx.login得到的code                             |
| encrypted_data | wx.getUserInfo返回的数据，包括敏感数据在内的完整用户信息的加密数据 |
| iv             | wx.getUserInfo返回的数据，加密算法的初始向量                 |

OUTPUT: 返回结果与fast_login相同，不存在user_info为空的情况

#### wxapp/profile

此接口一般在fast_login请求成功的情况下调用，直接将wx.getUserInfo中返回的用户信息(未加密)保存到服务器

| 参数      | 说明                      |
| --------- | ------------------------- |
| nickName  | 用户昵称                  |
| country   | 国家                      |
| province  | 省份                      |
| city      | 城市                      |
| gender    | 性别，0:未知，1:男，2: 女 |
| avatarUrl | 用户头像地址              |

OUTPUT: []

### JWT Token 使用说明

使用jwt-auth的验证方式， [详见](https://jwt-auth.readthedocs.io/en/develop/quick-start/#authenticated-requests)

```
# Authorization header
Authorization: Bearer eyJhbGciOiJIUzI1NiI...

# Query String
http://example.dev/me?token=eyJhbGciOiJIUzI1NiI...
```

### 异常

WechatAuthException 

### 事件

- UserAdded

    由于用户记录是在用户第一次调用login接口时创建的，此时记录中并不包含除app_id和openid以外的其它信息

- UserUpdated

    当用户调用 profile 接口时，如果用户信息发生变化，则会触发该事件

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