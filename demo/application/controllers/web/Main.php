<?php
namespace App\Controller;

use ZeroAI\MVC\Controller\Controller;

class Main extends Controller
{
    public function indexAction()
    {
        //配置使用
        print_r($this->config['def.a.b.c']);
        return;
        print_r($this->MainUserInfoModel->main());

        echo defined('\Redis::SERIALIZER_IGBINARY');
        echo $this->request->getController();

        //配置使用
        print_r($this->config['def.a.b']);

        //模型使用
        print_r($this->MainUserInfoModel->main());

        //
        print_r($this->request->get->get('ax'));



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
        print_r($this->MainUserInfoModel->main());

        //JSON输出 int code 位于config.status
        //$this->outFormatJSON(1, "bbbb", "cccc",["aaa" => "aaaa"]);
    }

    public function aAction()
    {
        echo "bbbb";
    }

}
?>