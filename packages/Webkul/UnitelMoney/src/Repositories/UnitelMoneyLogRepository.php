<?php

namespace Webkul\UnitelMoney\Repositories;

use Webkul\Core\Eloquent\Repository;

class UnitelMoneyLogRepository extends Repository
{
    public function model(): string
    {
        return 'Webkul\UnitelMoney\Contracts\UnitelMoneyLog';
    }
}
