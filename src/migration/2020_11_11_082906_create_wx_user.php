<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWxUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * 小程序端调用wx.login后再调用login接口即可实现登录，此时只有app_id和openid
         * 如果需要记录用户的详细信息，可以调用wx.getUserInfo获取用户信息后提交
         */
        Schema::create('wx_users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('app_id', 32)->comment('微信公众号appid');
            $table->string('openid', 64)->comment('用户openid');
            $table->string('nickname', 32)->default('')->comment('用户昵称');
            $table->tinyInteger('gender')->default(0)->comment('性别，0:未知，1:男，2:女');
            $table->string('country', 64)->default('')->comment('所在国家');
            $table->string('province', 64)->default('')->comment('所在省份');
            $table->string('city', 64)->default('')->comment('所在城市');
            $table->string('avatar_url')->default('')->comment('头像链接');

            $table->index(['app_id', 'openid'], 'app_openid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wx_user');
    }
}
