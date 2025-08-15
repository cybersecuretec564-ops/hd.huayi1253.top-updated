<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WalletLog extends Model
{
    //
    protected $table = "wallet_log";

    public $timestamps = false;

    /**
     * 模型日期的存储格式
     *
     * @var string
     */
    protected $dateFormat = 'U';
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    
    protected $appends = [
        'to_user_name',
        'from_user_name',
        'timer'
    ];

    /**
     * 一对一关联account_log模型
     */
    public function accountLog()
    {
        return $this->belongsTo('App\Accountlog','account_log_id','id')->withDefault();
    }
    /**
     * 一对一关联users_wallet模型
     */
    public function UsersWallet(){
        return $this->belongsTo('App\UsersWallet','wallet_id','id')->withDefault();
    }
    
    public function toUser()
    {
        return $this->belongsTo('App\Users', 'user_id', 'id')->withDefault();
    }
    
    public function fromUser()
    {
        return $this->belongsTo('App\Users', 'from_user_id', 'id')->withDefault();
    }
    
    public function getToUserNameAttribute()
    {
        return $this->toUser()->value('account_number');
    }

    public function getFromUserNameAttribute()
    {
        return $this->fromUser()->value('account_number');
    }
    
    public function getTimerAttribute()
    {
        $timer = $this->attributes['create_time'];
        if($timer){
           return date("Y-m-d H:i:s",$timer);
        }
        return $timer;
        // return $this->fromUser()->value('account_number');
    }
}
