<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name CurlWebRequest.php
 * @author King
 * @version Beta 1.0
 * @Date Tue Jan 24 01:56:58 CST 2012
 * @Description
 * @Class List
 *        1.
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Tue Jan 24 01:56:58 CST 2012 Beta 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Net\Http;

/**
 * CURL HTTP实现，可执行多线程同步获取远程链接
 *
 * @package Tiny.Net.Http
 * @since 2012-2-27 上午05:12:12
 * @final 2014-2-06
 */
class WebRequest
{
    
    /**
     * 最后一次请求所耗费的时间
     *
     * @var float
     */
    protected $lastInterval = 0;
    
    /**
     * Socket超时时间
     *
     * @var int
     */
    protected $timeout = 10;
    
    /**
     * 是否保持cookie
     *
     * @var bool
     */
    protected $isKeepCookie = '';
    
    /**
     * 持有的cookie数组
     *
     * @var array
     */
    protected $keepCookies = [];
    
    /**
     * 代理参数
     *
     * @var string
     */
    protected $proxy;
    
    /**
     * 验证信息
     *
     * @var string
     */
    protected $auth;
    
    /**
     * 绑定本地出口IP
     *
     * @var string
     */
    protected $outputIp;
    
    /**
     * 发送的Header数组
     *
     * @var array
     */
    protected $headers = [
        'Accept' => 'text/html, application/xhtml+xml, */*',
        'Accept-Language' => 'zh-cn',
        'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; InfoPath.2)',
        'Expect' => ''
    ];
    
    /**
     * 最大的重定向次数
     *
     * @var int
     */
    protected $maxRedirect = 3;
    
    /**
     * 响应请求的信息数组
     *
     * @var float
     */
    protected $responseData = [];
    
    /**
     * 响应请求的Body
     *
     * @var string
     */
    protected $responseBody = '';
    
    /**
     * 多线程请求句柄
     *
     * @var resource
     */
    protected $mutiHandler;
    
    /**
     * 异步多线程的请求容器
     *
     * @var array
     */
    protected $threads = [];
    
    /**
     * 设置连接超时时间
     *
     * @param float $secords 秒
     * @return void
     */
    public function setTimeout(int $secords)
    {
        if ($secords <= 0) {
            return $this;
        }
        $this->timeout = $secords;
        return $this;
    }
    
    /**
     * 设置是否保持cookie的文件名
     *
     * @param bool $isKeep 是为保持 设置为否会清理已存在的cookie
     * @return WebRequest
     */
    public function setKeepCookie($isKeepCookie)
    {
        if (!$isKeepCookie) {
            $this->keepCookies = [];
        }
        $this->isKeepCookie = $isKeepCookie;
        return $this;
    }
    
    /**
     * 设置简单的proxy
     *
     * @param string $proxy 类似http://域名:端口
     * @return WebRequest
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }
    
    /**
     * 设置传递的用户验证信息
     *
     * @param string $user 用户验证信息
     * @param string $passwd 密码
     * @return WebRequest
     */
    public function setAuth(string $user, string $passwd)
    {
        $this->auth = (!$user) ? '' : "$user:$passwd";
        return $this;
    }
    
    /**
     * 设置绑定哪个网卡的IP访问远程地址
     *
     * @param string $ip 绑定IP地址
     * @return WebRequest
     */
    public function setOutputIp(string $ip)
    {
        $this->outputIp = $ip;
        return $this;
    }
    
    /**
     * 获取响应信息
     *
     * @param string $key 响应信息的键 单个可以获取集合里面对应的值，为false或者null，则获取整个集合数组
     * @return array || string
     */
    public function getResponseData(string $key = null)
    {
        return (!$key) ? $this->responseData : $this->responseData[$key];
    }
    
    /**
     * 进行POST请求
     *
     * @param string $url 带http://的URL连接,可带GET参数 启动multi模式后，则需要exec才能执行
     * @param array $data POST的数据数组
     * @param array $cookie 预设的Cookie数组
     * @param array $headers 附加的header数组
     * @return string || WebRequest
     */
    public function post($url, array $data = [], array $cookie = [], array $headers = [])
    {
        $request = $this->buildRequest($url, 'POST', $data, $cookie, $headers);
        if ($this->multiHandle) {
            $this->multiRequests[] = $request;
            return $this;
        }
        return $this->getResponse($request);
    }
    
    /**
     * 进行GET请求
     *
     * @param string $url 带GET参数的URL
     * @param array $data 附带数据
     * @param string $cookie Cookie数组或者可以为querystring方式
     * @param array $headers 附带头部数组
     * @return string || WebRequest
     */
    public function get(string $url, array $data = [], array $cookie = [], array $headers = [])
    {
        $request = $this->buildRequest($url, 'GET', $data, $cookie, $headers);
        if ($this->multiHandle) {
            $this->multiRequests[] = $request;
            return $this;
        }
        return $this->getResponse($request);
    }
    
    /**
     * 初始化并发访问
     */
    public function multi()
    {
        $this->multiHandle = curl_multi_init();
        $this->multiRequests = [];
        return $this;
    }
    
    /**
     * 取消并发访问
     *
     * @return void
     */
    public function discard()
    {
        if ($this->multiHandle) {
            curl_multi_close($this->multiHandle);
        }
        $this->multiRequests = [];
        return $this;
    }
    
    /**
     * 执行并发请求并返回body
     *
     * @return array
     */
    public function exec()
    {
        foreach ($this->multiRequests as &$request) {
            $request = $this->initRequest($request);
            curl_multi_add_handle($this->multiHandle, $request);
        }
        
        $active = null;
        do {
            $mrc = curl_multi_exec($this->multiHandle, $active);
        } while (CURLM_CALL_MULTI_PERFORM == $mrc);
        
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($this->multiHandle) == -1) {
                continue;
            }
            do {
                $mrc = curl_multi_exec($this->multiHandle, $active);
            } while (CURLM_CALL_MULTI_PERFORM == $mrc);
        }
        
        $contents = [];
        foreach ($this->multiRequests as $ch) {
            $contents[] = curl_multi_getcontent($ch);
            curl_close($ch);
        }
        curl_multi_close($this->multiHandle);
        return $contents;
    }
    
    /**
     * 生成Cookie
     *
     * @param array $cookie Cookie数组
     * @return string
     */
    protected function buildCookie($cookie)
    {
        if (!is_array($cookie)) {
            return $cookie;
        }
        $cs = [];
        foreach ($cookie as $k => $v) {
            $v = rawurlencode($v);
            $cs[] = "$k=$v";
        }
        return join(';', $cs);
    }
    
    /**
     * 生成HEADER
     *
     * @param array $headers 默认的headers
     * @return array
     *
     */
    protected function buildHeader(array $headers = [])
    {
        return array_merge($this->headers, $headers);
    }
    
    /**
     * 构建request
     *
     * @param string $url 网址
     * @param string $method 请求方式
     * @param array $data 请求数据
     * @param array $cookie 请求的cookie
     * @param array $headers 附加的header
     * @param bool $isNobody 是否不需要获得正文即可返回
     * @return array
     */
    protected function buildRequest($url, $method, array $data = [], $cookie = [], $headers = [], $isNobody = false)
    {
        if ($this->isKeepCookie && !empty($this->keepCookies)) {
            $cookie = array_merge($this->keepCookies, $cookie);
        }
        $isPost = strtoupper($method) == 'POST' ? true : false;
        if (!($isPost || empty($data))) {
            $url .= (!strpos($url, '?') ? '?' : '') . http_build_query($data);
            $data = '';
        }
        return [
            'url' => $url,
            'headers' => $this->buildHeader($cookie, $headers),
            'isPost' => $isPost,
            'data' => $data,
            'cookie' => $this->buildCookie($cookie),
            'isNobody' => $isNobody
        ];
    }
    
    /**
     * 获取响应
     *
     * @param array $request 请求数组
     * @return string
     */
    protected function getResponse($req)
    {
        $this->maxRedirect = 0;
        $handle = $this->initRequest($req);
        return $this->execRequest($handle, $req);
    }
    
    /**
     * 初始化请求句柄
     *
     * @param array $req 请求信息数组
     * @return mixed handle
     */
    protected function initRequest($req)
    {
        $handle = curl_init($req['url']);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $req['headers']);
        curl_setopt($handle, CURLOPT_TIMEOUT_MS, $this->timeout * 1000);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            'Expect:'
        ));
        curl_setopt($handle, CURLOPT_AUTOREFERER, true);
        if ($this->proxy) {
            curl_setopt($handle, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt($handle, CURLOPT_PROXY, $this->proxy);
        }
        if ($this->auth) {
            curl_setopt($handle, CURLOPT_USERPWD, $this->auth);
        }
        if ($this->interface) {
            curl_setopt($handle, CURLOPT_INTERFACE, $this->interface);
        }
        if (!$this->multiHandle) {
            curl_setopt($handle, CURLOPT_HEADER, true);
        } else {
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handle, CURLOPT_MAXREDIRS, 3);
        }
        if ($req['isPost']) {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $req['data']);
        }
        if (!empty($req['cookie'])) {
            curl_setopt($handle, CURLOPT_COOKIE, $req['cookie']);
        }
        return $handle;
    }
    
    /**
     * 执行请求
     *
     * @param string $handle curl请求句柄
     * @return string || null
     */
    protected function execRequest($handle, $req)
    {
        $resString = curl_exec($handle);
        if (curl_errno($handle)) {
            new \Exception(curl_error($handle) . curl_errno($handle), E_WARNING);
        }
        list($resHeader, $this->resBody) = explode("\r\n\r\n", $resString, 2);
        $resInfo = curl_getinfo($handle);
        $code = $resInfo['http_code'];
        if ($code == 301 || $code == 302 || $code == 303 || $code == 307) {
            $req['url'] = $resInfo['redirect_url'];
            return ($this->maxRedirect > 3) ? '' : $this->getResponse($req);
        }
        $resHeaders = explode("\r\n", $resHeader);
        $cookies = [];
        $rhs = [];
        foreach ($resHeaders as &$h) {
            list($k, $v) = explode(":", $h);
            if (strtolower($k) == "set-cookie") {
                list($c) = explode(";", $v);
                list($k, $v) = explode('=', $c);
                $v = rawurldecode($v);
                $cookies[$k] = $v;
            } else {
                $rhs[$k] = $v;
            }
        }
        $resInfo['res_headers'] = $rhs;
        $resInfo['res_cookies'] = $cookies;
        $resHeaders['cookies'] = $cookies;
        if ($this->isKeepCookie && !empty($cookies)) {
            $this->keepCookies = array_merge($this->keepCookies, $cookies);
        }
        $this->resInfo = $resInfo;
        return $this->resBody;
    }
}
?>