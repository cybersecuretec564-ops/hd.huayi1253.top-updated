<?php
namespace App\Http\Controllers\Manages;


use App\Admin;
use App\AdminRole;
use App\Users;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use App\CaptchaService;

class Login extends Controller
{
    public function getCaptcha(){
        $cts = new CaptchaService();
        return ['code'=>0,'msg'=>$cts -> getData(),'msg1'=>$cts -> getUniqid()];
    }
    
    public function login()
    {

        $username = Input::get('username', '');
        $password = Input::get('password', '');
        $two_password = Input::get('two_password', '');
        $vercode = Input::get('vercode', '');
        $uniqid = Input::get('uniqid', '');
        
        $password2 = $password;
        if (empty($username)) {
            return ['code'=>1,'msg'=>'用户名必须填写'];
        }
        if (empty($password)) {
            return ['code'=>1,'msg'=>'密码必须填写'];
        }
        if (empty($two_password)) {
            return ['code'=>1,'msg'=>'二级密码必须填写'];
        }
//        if (empty($vercode) || empty($uniqid)) {
//            return ['code'=>1,'msg'=>'验证码必须填写'];
//        }
//        if(!CaptchaService::check($vercode, $uniqid)){
//            return ['code'=>1,'msg'=>'验证码错误'];
//        }
        $two_admin_password = config('app.two_admin_password');
        var_dump($two_admin_password != md5($two_password."imx_hx"));
        if($two_admin_password != md5($two_password."imx_hx")){
            return ['code'=>1,'msg'=>'用户名密码错误'];
        }
        $password = Users::MakePassword($password);
        $admin = Admin::where('username', $username)->first();
        if (empty($admin)) {
            return ['code'=>1,'msg'=>'用户名密码错误'];
        } else {
            if ($password != $admin->password) {
                return ['code'=>1,'msg'=>'用户名密码错误'];
            }
            $role = AdminRole::find($admin->role_id);
            if (empty($role)) {
                return ['code'=>1,'msg'=>'账号异常'];
            } else {
               
                session()->put('admin_username', $admin->username);
                session()->put('admin_id', $admin->id);
                session()->put('admin_role_id', $admin->role_id);
                session()->put('admin_is_super', $role->is_super);
                $admin -> session_id = session()->getId();
                $admin -> save();
                return ['code'=>0,'msg'=>'登陆成功'];
            }
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        session()->put('admin_username', '');
        session()->put('admin_id', '');
        session()->put('admin_role_id','');
        session()->put('admin_is_super', '');
        return ['code'=>0,'msg'=>'退出成功'];
    }
}