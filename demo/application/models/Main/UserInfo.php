<?php

namespace App\Model\Main;

use ZeroAI\MVC\Model\Db;

class UserInfo extends Db
{
    //指定dataid
    protected $_dataId = "default";

    protected $_readId = 'default_r';

    public $a = ["aaaa"];

    public function main()
    {
        $a = $this->exec('SELECT * FROM :0t WHERE :1', 'user', "user='root'");
        return [$a];
    }
}
?>