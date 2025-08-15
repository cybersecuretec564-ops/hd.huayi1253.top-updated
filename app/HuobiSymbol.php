<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HuobiSymbol extends Model
{
    public $timestamps = false;

    public static function getSymbolsData($symbols)
    {
        self::unguard();
        foreach ($symbols as $key => $value) {
            $huobi_symbol = new self();
             $fill = [
                'base-currency'=>$value['base-currency'],
                'quote-currency'=>$value['quote-currency'],
                'price-precision'=>$value['price-precision'],
                'symbol-partition'=>$value['symbol-partition'],
                'amount-precision'=>$value['amount-precision'],
                'symbol'=>$value['symbol'],
            ];
            $huobi_symbol->fill($fill)->save();
        }
        self::reguard();
        return true;
    }
}
