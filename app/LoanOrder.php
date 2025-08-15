<?php

/**
 * Created by PhpStorm.
 * User: swl
 * Date: 2018/7/3
 * Time: 10:23
 */

namespace App;

use App\Users;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class LoanOrder extends Model
{
    protected $table = 'loan_order';
    public $timestamps = false;
    
    public function getCreateTimeAttribute()
    {
        $value = $this->attributes['create_time'];
        return $value ? date('Y-m-d H:i:s', $value ) : '';
    }
    
    public function getUpdateTimeAttribute()
    {
        $value = $this->attributes['update_time'];
        return $value ? date('Y-m-d H:i:s', $value ) : '';
    }
}