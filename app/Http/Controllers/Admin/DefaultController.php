<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use App\Admin;
use App\AdminRole;
use App\AdminRolePermission;
use App\Users;

class DefaultController extends Controller
{

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
        if (empty($vercode) || empty($uniqid)) {
            return ['code'=>1,'msg'=>'验证码必须填写'];
        }
        if(!CaptchaService::check($vercode, $uniqid)){
            return ['code'=>1,'msg'=>'验证码错误'];
        }
        $two_admin_password = config('app.two_admin_password');
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

    public function login1()
    {
        return view('admin.login1');
    }

    public function index()
    {
        $admin_role = AdminRolePermission::where("role_id", session()->get('admin_role_id'))->get();
        $admin_role_data = array();
        foreach ($admin_role as $r) {
            array_push($admin_role_data, $r->action);
        }
        return view('admin.indexnew')->with("admin_role_data", $admin_role_data);;
    }

    public function indexnew()
    {
        $admin_role = AdminRolePermission::where("role_id", session()->get('admin_role_id'))->get();
        $admin_role_data = array();
        foreach ($admin_role as $r) {
            array_push($admin_role_data, $r->action);
        }
        return view('admin.index')->with("admin_role_data", $admin_role_data);;
    }



    public function getVerificationCode(Request $request)
    {
        $http_client = app('LbxChainServer');

        $uri = '/v3/wallet/verification';

        $response = $http_client->request('post', $uri, [
            'form_params' => [
                'projectname' => config('app.name'),
            ],
        ]);
        $result = json_decode($response->getBody()->getContents(), true); 
        if (isset($result['code']) && $result['code'] == 0) {
            return $this->success('发送成功');
        } else {
            return $this->error($result['msg']);
        }
    }
}
