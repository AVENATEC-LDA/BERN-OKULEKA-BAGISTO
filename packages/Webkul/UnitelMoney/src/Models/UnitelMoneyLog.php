<?php

namespace Webkul\UnitelMoney\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\UnitelMoney\Contracts\UnitelMoneyLog as UnitelMoneyLogContract;

class UnitelMoneyLog extends Model implements UnitelMoneyLogContract
{
    protected $table = 'unitel_money_logs';

    protected $fillable = [
        'event_uid',
        'order_id',
        'cart_id',
        'originator_conversation_id',
        'conversation_id',
        'transaction_id',
        'status',
        'title',
        'message',
        'payload',
        'context',
    ];

    protected $casts = [
        'payload' => 'array',
        'context' => 'array',
    ];
}
