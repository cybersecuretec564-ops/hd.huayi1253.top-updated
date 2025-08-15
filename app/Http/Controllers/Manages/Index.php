<?php

namespace App\Http\Controllers\Manages;

use Illuminate\Support\Facades\DB;

class Index extends Base
{
    public function index()
    {
        if (empty(session('admin_id'))){
            return redirect('/manage');
        }
        $view = [
            'menus'=>AuthModel::getMenus(),
            'username' => session('admin_username')
        ];
        return view('manages.index',$view);
    }

    public function console()
    {
        date_default_timezone_set('America/New_York');
        $start = strtotime(date('Y-m-d'));
        $end = $start + 24 * 60 * 60 - 1;
        $today = [$start,$end];
        $data['today_user'] = DB::table('users')
            ->whereBetween('time',$today)
            ->where("system_flag",0)
            ->count();
        $data['all_user'] = DB::table('users')
            ->where("system_flag",0)
            ->count();

        $data['today_withdraw_count'] = DB::table('users_wallet_out')
            ->join('users', 'users.id', '=', 'users_wallet_out.user_id')
            ->where('users.system_flag',0)
            ->whereBetween('users_wallet_out.create_time',$today)
            ->count();
            
        $data['today_withdraw_amount'] = DB::table('users_wallet_out')
            ->join('users', 'users.id', '=', 'users_wallet_out.user_id')
            ->where('users.system_flag',0)
            ->whereBetween('users_wallet_out.create_time',$today)
            ->sum('users_wallet_out.number');
        if($data['today_withdraw_amount'] <= 0){
            $data['today_withdraw_amount'] = 0.1;
        }

        $today = [date("Y-m-d H:i:s",$start),date("Y-m-d H:i:s",$end)];
        $data['today_charge_count'] = DB::table('charge_req')
            ->join('users', 'users.id', '=', 'charge_req.uid')
            ->where('users.system_flag',0)
            ->whereBetween('charge_req.created_at',$today)
            ->count();
        $data['today_charge_amount'] = DB::table('charge_req')
            ->join('users', 'users.id', '=', 'charge_req.uid')
            ->where('users.system_flag',0)
            ->whereBetween('charge_req.created_at',$today)
            ->sum('charge_req.amount');

        return view('manages.console')->with('data',$data);
    }

}