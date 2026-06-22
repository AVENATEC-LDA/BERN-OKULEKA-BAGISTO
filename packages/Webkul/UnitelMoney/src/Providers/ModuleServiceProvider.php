<?php

namespace Webkul\UnitelMoney\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Webkul\UnitelMoney\Models\UnitelMoneyLog;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        UnitelMoneyLog::class,
    ];
}
