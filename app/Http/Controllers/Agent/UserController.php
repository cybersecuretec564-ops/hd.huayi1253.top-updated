<?php

/**
 * Created by PhpStorm.
 * User: YSX
 * Date: 2018/12/4
 * Time: 16:36
 */

namespace App\Http\Controllers\Agent;

use Illuminate\Http\Request;
use App\{AccountLog, Agent, Currency, Users, UsersWalletOut, Setting,UserReal};
use App\Events\RealNameEvent;

class UserController extends Controller
{

    //用户管理
    public function index()
    {
        //某代理商下用户时
        $parent_id = request()->get('parent_id', 0);
        //币币  
        $legal_currencies = Currency::get();
        return view("agent.user.index", ['parent_id' => $parent_id, 'legal_currencies' => $legal_currencies]);
    }

    //用户列表
    public function lists(Request $request)
    {
        $limit = $request->get('limit', 10);
        $id = request()->input('id', 0);
        $parent_id = request()->input('parent_id', 0);
        $account_number = request()->input('account_number', '');
        $start = request()->input('start', '');
        $end = request()->input('end', '');

        $users = new Users();

        $users = $users->leftjoin("user_real", "users.id", "=", "user_real.user_id");

        if ($id) {
            $users = $users->where('users.id', $id);
        }
        if ($parent_id > 0) {
            $users = $users->where('users.agent_note_id', $parent_id);
        }
        if ($account_number) {
            $users = $users->where('users.account_number', $account_number);
        }
        if (!empty($start) && !empty($end)) {
            $users->whereBetween('users.time', [strtotime($start . ' 0:0:0'), strtotime($end . ' 23:59:59')]);
        }

        $agent_id = Agent::getAgentId();
        
        // var_dump($agent_id);
        $users = $users->whereRaw("FIND_IN_SET($agent_id,users.agent_path)");

        $list = $users->select("users.*", "user_real.card_id")->paginate($limit);

        return $this->layuiData($list);
    }

    /**
     * 获取用户管理的统计
     * @param Request $r
     */
    public function get_user_num(Request $request)
    {

        $id             = request()->input('id', 0);
        $account_number = request()->input('account_number', '');
        $parent_id            = request()->input('parent_id', 0);//代理商id
        $start = request()->input('start', '');
        $end = request()->input('end', '');
        $currency_id = request()->input('currency_id', '');

        $users = new Users();

        if ($id) {
            $users = $users->where('id', $id);
        }
        if ($parent_id > 0) {
            $users = $users->where('agent_note_id', $parent_id);
        }
        if ($account_number) {
            $users = $users->where('account_number', $account_number);
        }
        if (!empty($start) && !empty($end)) {
            $users->whereBetween('time', [strtotime($start . ' 0:0:0'), strtotime($end . ' 23:59:59')]);
        }

        $agent_id = Agent::getAgentId();
        
        // var_dump($agent_id);
        $users = $users->whereRaw("FIND_IN_SET($agent_id,`agent_path`)");
        $users_id = $users->get()->pluck('id')->all();
        $_daili = 0;
        $_ru = 0.00;
        $_chu = 0.00;
        $_num = 0;

        $_num = $users->count();

        $_daili = $users->where('agent_id', '>', 0)->count();


        $_ru = AccountLog::where('type', AccountLog::CHAIN_RECHARGE)
            ->whereIn('user_id', $users_id)
            ->when($currency_id > 0, function ($query) use ($currency_id) {
                $query->where('currency', $currency_id);
            })->sum('value');

        $_chu = UsersWalletOut::where('status', 2)
            ->whereIn('user_id', $users_id)
            ->when($currency_id > 0, function ($query) use ($currency_id) {
                $query->where('currency', $currency_id);
            })->sum('real_number');

        $data = [];
        $data['_num'] = $_num;
        $data['_daili'] = $_daili;
        $data['_ru'] = $_ru;
        $data['_chu'] = $_chu;


        return $this->ajaxReturn($data);
    }

    //我的邀请二维码
    public function get_my_invite_code()
    {

        $_self = Agent::getAgent();

        if ($_self == null) {
            $this->outmsg('超时');
        }

        $use = Users::getById($_self->user_id);
        $moblie_h5_url = Setting::getValueByKey('moblie_h5_url', 0);

        return $this->ajaxReturn(['invite_code' => $use->extension_code, 'is_admin' => $_self->is_admin,'moblie_h5_url' => $moblie_h5_url]);
    }

    //代理商管理
    public function salesmenIndex()
    {
        return view("agent.salesmen.index");
    }

    //添加代理商页面
    public function salesmenAdd()
    {
        $data = request()->all();

        return view("agent.salesmen.add", ['d' => $data]);
    }

    public function salesmenEdit()
    {
        $data = request()->all();
        return view("agent.salesmen.add", ['d' => $data]);
    }
    //出入金管理
    public function transferIndex()
    {
        return view("agent.user.transfer");
    }

     //用户点控
     public function risk()
     {
         
         $user_id = request()->get('id', 0);
         $user=Users::find($user_id);
         
         return view("agent.user.risk", ['result' => $user]);
     }
     
     public function agent(){
         $user_id = request()->get('id', 0);
         $user=Users::find($user_id);
         $agent_id = Agent::getAgentId();
         $list = Agent::where('parent_agent_id',$agent_id) -> get();
         return view("agent.user.agent", ['result' => $user,'list' => $list]);
     }
     
     public function update_agent(){
        $user_id = request()->get('id', 0);
        $risk = request()->get('risk', 0);
        $user=Users::find($user_id);
        $agent_id = Agent::getAgentId();
        $parent_agent = explode(',', $user->agent_path);

        if($risk == 0){
            return $this->success("操作成功");
        }
        if($agent_id != 1){
            if (!in_array($agent_id, $parent_agent)) {
                return $this->error('不是您的伞下用户，不可操作');
            }
        }
        
        if($risk == $user -> id){
            return $this->error('此账户已经是代理,不能自己分配自己');
        }
        
        
        try {
            $user->agent_note_id = Agent::reg_get_agent_id_by_parentid($risk);
            // 代理商节点关系
            $user->agent_path = Agent::agentPath($risk);
            $user->save();
            return $this->success("操作成功");
        } catch (\Throwable $th) {
            //throw $th;
            return $this->error($th->getMessage()); 
        }
        
     }

     public function postRisk()
     {
         
        $user_id = request()->get('id', 0);
        $risk = request()->get('risk', 0);
        $user=Users::find($user_id);
        $agent_id = Agent::getAgentId();
        $parent_agent = explode(',', $user->agent_path);
        
        if($agent_id != 1){
            if (!in_array($agent_id, $parent_agent)) {
                return $this->error('不是您的伞下用户，不可操作');
            }
        }
        
        try {
            //code...
            $user->risk=$risk;
            $user->save();
            return $this->success("操作成功");

        } catch (\Throwable $th) {
            //throw $th;
            return $this->error($th->getMessage()); 
        }
        
        
     }
    
    public function lockUser(Request $request)
    {
        $id = $request->get('id', 0);
        if (empty($id)) {
            return $this->error("参数错误");
        }
        $result = Users::find($id);
        //
        // $res=UserCashInfo::where('user_id',$id)->first();
        return view('agent.user.lock', ['result' => $result]);
    }
    
    public function update_lock(Request $request){
        $id = $request->get('id', 0);
        $date = $request->get('date', 0);
        $status = $request->get('status', 0);
        $frozen_funds = $request->get('frozen_funds', 0);

        if (empty($id)) {
            return $this->error('参数错误');
        }
        $user = Users::find($id);
        if (empty($user)) {
            return $this->error('参数错误');
        }
        $agent_id = Agent::getAgentId();
        $parent_agent = explode(',', $user->agent_path);
        
        if($agent_id != 1){
            if (!in_array($agent_id, $parent_agent)) {
                return $this->error('不是您的伞下用户，不可操作');
            }
        }
        if (empty($date)) {
            return $this->error('缺少时间！');
        }
        $users = new Users();
        $result = $users->lockUser($user, $status, $date, $frozen_funds);
        if (!$result) {
            return $this->error('锁定失败');
        }
        return $this->success('操作成功');
    }

    public function realIndex(){
        return view("agent.user.realIndex");
    }
    
    public function real_list(Request $request)
    {
         //当前代理商信息
        $agent_id = Agent::getAgentId();
        $node_users = Users::whereRaw("FIND_IN_SET($agent_id,`agent_path`)")->pluck('id')->all();
        $limit = $request->get('limit', 10);
        $account = $request->get('account', '');
        $review_status_s = $request->get('review_status_s', 0);

        $list = new UserReal();
        if (!empty($account)) {
            $list = $list->whereHas('user', function ($query) use ($account) {
                $query->where("phone", 'like', '%' . $account . '%')->orwhere('email', 'like', '%' . $account . '%');
            });
        }
        if(!empty($review_status_s)){
            $list = $list->where('review_status',$review_status_s);
        }
        $list = $list->whereIn('user_id', $node_users);
        $list = $list->orderBy('id', 'desc')->paginate($limit);
        return response()->json(['code' => 0, 'data' => $list->items(), 'count' => $list->total()]);
    }
    
    public function auth(Request $request)
    {
        $id = $request->get('id', 0);
        $userreal = UserReal::find($id);
        if (empty($userreal)) {
            return $this->error('参数错误');
        }
        $user = Users::find($userreal->user_id);
        if (!$user) {
            return $this->error('用户不存在');
        }
        if ($userreal->review_status == 1) {
            //从未认证到认证
            //查询users表判断是否为第一次实名认证
            $is_realname = $user->is_realname;
            if ($is_realname != 2) {
                //1:未实名认证过  2：实名认证过
             
                $user->is_realname = 2;

                $user->save();//自己实名认证获取通证结束
                //判断自己上级的的触发奖励
                //UserDAO::addCandyNumber($user);
            }
            $userreal->review_status = 2;
        } else if ($userreal->review_status == 2) {
            $userreal->review_status = 1;
        } else {
            $userreal->review_status = 1;
        }
        try {
            $userreal->save();
            //用户实名事件
            if ($userreal->review_status == 2) {
                event(new RealNameEvent($user, $userreal));
            }
            return $this->success('操作成功');
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
    }
    
    
    public function detail(Request $request)
    {

        $id = $request->get('id', 0);
        if (empty($id)) {
            return $this->error("参数错误");
        }

        $result = UserReal::find($id);
        
        $imageServerUrl = Setting::getValueByKey('image_server_url', '');
        
        if(!empty($result -> front_pic)){
            $result -> front_pic =  $imageServerUrl.$result ->front_pic;
        }
        
        if(!empty($result -> reverse_pic)){
            $result -> reverse_pic =  $imageServerUrl.$result ->reverse_pic;
        }

        return view('agent.user.real_info', ['result' => $result,'imageServerUrl' => $imageServerUrl]);
    }
    
     public function del(Request $request)
    {
        $id = $request->get('id');
        $userreal = UserReal::find($id);
        if (empty($userreal)) {
            $this->error("认证信息未找到");
        }
        try {

            $userreal->delete();
            return $this->success('删除成功');
        } catch (\Exception $ex) {
            return $this->error($ex->getMessage());
        }
    }
    
    public function setAdvancedUser(Request $request)
    {
        $id = $request->get('id', 0);
        $userreal = UserReal::find($id);
        if (empty($userreal)) {
            return $this->error('参数错误');
        }
        $user = Users::find($userreal->user_id);
        if (!$user) {
            return $this->error('用户不存在');
        }
        
        if($userreal->review_status == 2 && $userreal->advanced_user==1){
            $userreal->advanced_user = 2;//null还没有提交过高级审核 1 等待审核的 2 已通过高级审核
        }else if($userreal->review_status != 2){
            return $this->success('请先审核用户信息');
        }else if($userreal->advanced_user == 2){
            return $this->success('已经审核过');
        }else if(!$userreal->advanced_user){
            return $this->error('该用户还未提交高级审核');
        }
        
        try {
            $userreal->save();
            return $this->success('操作成功');
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
        
    }
}
