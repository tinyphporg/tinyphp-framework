<?php
namespace App\Controller\Console;


class Main extends \ZeroAI\MVC\Controller\ConsoleController
{
    public function onstart()
    {
        echo "\nonstart\n";
    }

    public function onstop()
    {
        echo "\nonstop\n";
    }

    public function indexAction()
    {
        echo $this->config['status.index'];
        echo "aaaa";
        return;
        //配置使用
        $this->config;

        //缓存使用
        echo "------<br>";
        $this->cache->set('a', "32342sfdds");
        echo $this->cache['default']->get('a');

        echo $this->cache->get('a'). "\n";    //使用缓存 默认使用id为default的缓存实例
        echo $this->cache->default->get('a'). "\n";
        echo $this->cache->default['a']. "\n";
        echo "------<br>";

        //配置使用
        print_r($this->config['def.a.b']);

        //模型使用
        print_r($this->mainUserInfoModel->main());

        //
        print_r($this->request->param->get('ax'));



        //
        return;
        //request使用
        $user = new \ZP\User();
        print_r($user->main());

        //get post 只读 files基本不在PHP做处理，后续再加上
        echo $this->request->get->get('aaa');
        echo $this->request->get['aaa'];

        //session
        $this->session['aaa'] = "aaaaa1";

        echo $this->session['aaa'];

        //cookie
        echo $this->request->cookie;

        //配置使用
        print_r($this->config['def.a.b']);

        //缓存使用
        echo "------<br>";
        $this->cache->set('a', "32342sfdds");
        echo $this->cache->get('a');    //使用缓存 默认使用id为default的缓存实例
        echo "------<br>";

        $this->cache['default']->get('a'); //使用指定id的缓存实例

        //模型使用
       // print_r($this->MainUserInfoModel->main());

        //JSON输出 int code 位于config.status
        //$this->outFormatJSON(1, "bbbb", "cccc",["aaa" => "aaaa"]);
    }

}
?>