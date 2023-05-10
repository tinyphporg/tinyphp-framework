<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Builder.php
 * @author King
 * @version Beta 1.0
 * @Date 2020年4月26日下午6:20:31
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2020年4月26日下午6:20:31 第一次建立该文件
 *          King 2020年4月26日下午6:20:31 修改
 *
 */
namespace Tiny\Build;

use Tiny\MVC\Application\ApplicationBase;

/**
 * 打包器
 *
 * @package Tiny.Build
 * @since 2020年5月28日下午5:39:12
 * @final 2020年5月28日下午5:39:12
 */
class Builder
{
    
    /**
     * 配置
     *
     * @var array
     */
    protected $config;
    
    /**
     * 打包数据
     *
     * @var array
     */
    protected $data = [];
    
    /**
     * pharhandler句柄
     *
     * @var \Phar
     */
    protected $pharHandler;
    
    /**
     * 打包文件里面的properties配置数据
     *
     * @var array
     */
    protected $properties;
    
    /**
     * 运行打包后程序的初始化
     */
    public static function init()
    {
        // 解压附件在应用实例当前目录
        $attachmentPath = TINY_PHAR_FILE . '/attachments/';
        if (file_exists($attachmentPath)) {
            self::unpackage($attachmentPath, '', TINY_PHAR_DIR);
        }
        
        // 解压附件在用户工作目录
        $homeAttachmentPath = TINY_PHAR_FILE . '/home_attachments/';
        if (file_exists($homeAttachmentPath)) {
            self::unpackage($homeAttachmentPath, '', TINY_HOME_DIR);
        }
    }
    
    /**
     * 初始化构建器
     *
     * @param array $config 配置数组 由Builder插件传入
     */
    public function __construct(array $config)
    {
        if (ini_get('phar.readonly')) {
            throw new BuilderException('Creating archive "tiny-demo.phar" disabled by the php.ini;phar.readonly must be off');
        }
        $this->config = $config;
        
        // 应用实例的properties数组
        $this->properties = $this->config['properties'];
        
        // 关闭phar应用程序打包phar
        $this->properties['builder']['enabled'] = false;
    }
    
    /**
     * 执行phar打包
     */
    public function run()
    {
        $name = $this->config['name'] ?: 'tinyphp-demo';
        $this->createrPhar($name);
        $this->addHomeAttachments();
        $this->addAttachments();
        $this->addApplication();
        $this->addConfig();
        $this->addLibarays();
        $this->addFrameWork();
        $this->createrPharFile();
        return true;
    }
    
    /**
     * 解压phar文件里面的文件到指定路径
     *
     * @param string $dpath 解压路径
     * @param string $dname 本地相对路径
     * @param string $rootPath 本地根路径
     */
    protected static function unpackage($pharDir, $localDir = '', $rootDir = '')
    {
        if (!is_dir($pharDir)) {
            return;
        }
        
        $pharNames = scandir($pharDir);
        foreach ($pharNames as $pname) {
            if ($pname == '.' || $pname == '..') {
                continue;
            }
            $pharPath = $pharDir . $pname;
            $localPath = $localDir . $pname;
            $destPath = $rootDir . '/' . $localPath;
            
            // 创建文件夹
            if (is_dir($pharPath)) {
                if (!file_exists($destPath)) {
                    mkdir($destPath, 0777, true);
                }
                self::unpackage($pharPath . '/', $localPath . '/', $rootDir);
                continue;
            }
            if (!file_exists($destPath) || filemtime($destPath) < filemtime($pharPath)) {
                copy($pharPath, $destPath);
            }
        }
    }
    
    /**
     * 添加 解压在用户目录的附件
     */
    protected function addHomeAttachments()
    {
        printf("add home_attachments:\n");
        
        // runtime
        $homefiles = $this->config['home_attachments'] ?: [];
        
        $this->pharHandler->addEmptyDir('home_attachments/var');
        $this->properties['path']['var'] = 'TINY_HOME_DIR/var/';
        
        
        // view
        $this->pharHandler->addEmptyDir('home_attachments/view');
        $this->properties['path']['view'] = 'TINY_HOME_DIR/view/';
        $this->properties['view']['basedir'] = '{path.app}view/templates/';
        
        // config
        if ($homefiles['config']) {
            $this->pharHandler->addEmptyDir('home_attachments/config');
            $this->data['config_path'] = 'TINY_HOME_DIR/config/';
        }
        
        // profile
        if ($homefiles['profile']) {
            $this->pharHandler->addEmptyDir('home_attachments/profile');
            
            // 添加的配置路径
            $this->data['profile_path'] = 'TINY_HOME_DIR/profile/';
        }
        
        // 循环添加附件
        foreach ($homefiles as $atts) {
            $name = $atts[0];
            $path = $atts[1];
            $isNoFile = (bool)$atts[2];
            $aname = 'home_attachments/' . $name;
            printf("home_attachments:  %s => %s\n", $aname, $path);
            $this->addDir($path, $aname, $isNoFile);
        }
    }
    
    /**
     * 添加解压在phar所在目录的附件
     */
    protected function addAttachments()
    {
        printf("add attachments:\n");
        $attachments = $this->config['attachments'] ?: [];
        
        // 循环添加附件
        foreach ($attachments as $atts) {
            $name = $atts[0];
            $path = $atts[1];
            $isNoFile = (bool)$atts[2];
            $aname = 'attachments/' . $name;
            printf("attachments    %s => %s\n", $aname, $path);
            $this->addDir($path, $aname, $isNoFile);
        }
    }
    
    /**
     * 添加应用实例的文件夹
     */
    protected function addApplication()
    {
        $apppath = realpath($this->config['application_path']);
        printf("add application:\n  %s\n", $apppath);
        $this->addDir($apppath, 'application');
    }
    
    /**
     * 缓存application的配置文件数据到打包程序中
     */
    protected function addConfig()
    {
        printf("add config\n    application/.config.php\n");
        $config = $this->config['config'];
        $contents = "<?php\n return " . var_export($config, true) . ";\n?>";
        $this->pharHandler->addFromString('application/.config.php', $contents);
        
        $this->properties['config']['cache']['enabled'] = false;
        $this->properties['config']['lang']['enabled'] = false;
        $this->properties['config']['path'] = [];
        $this->properties['config']['path'][] = 'TINY_PHAR_FILE/application/.config.php';
        if ($this->data['config_path']) {
            $this->properties['config']['path'][] = $this->data['config_path'];
        }
    }
    
    /**
     * 添加引用库
     */
    protected function addLibarays()
    {
        printf("add autoloader librarys:\n");
        $namespaces = $this->config['namespaces'];
        $idata = [];
        foreach ($namespaces as $ns => $path) {
            $name = 'application/.namespaces/' . md5($ns);
            if (file_exists($path)) {
                $this->addDir($path, $name);
            } else {
                $this->pharHandler->addEmptyDir($name);
            }
            $idata[$ns] = 'TINY_PHAR_FILE' . '/' . $name . '/';
            printf("    %s => %s\n", $ns, $path);
        }
        
        $classes = $this->config['classes'];
        $this->pharHandler->addEmptyDir('application/.classes/');
        $cdata = [];
        foreach ($classes as $class => $path) {
            $name = 'application/.classes/' . md5($class) . '.php';
            $this->pharHandler->addFile($path, $name);
            $cdata[$class] = 'TINY_PHAR_FILE' . '/' . $name;
            printf("    %s => %s\n", $class, $path);
        }
       $this->properties['autoloader']['namespaces'] = $idata;
       $this->properties['autoloader']['classes'] = $cdata;
       $this->properties['autoloader']['is_realpath'] = true;
    }
    
    /**
     * 处理并添加application配置文件
     */
    protected function addProperties()
    {
        if ($this->config['controller']) {
            $this->properties['controller']['default'] = trim($this->config['controller']);
        }
        if ($this->config['action']) {
            $this->properties['controller']['action_default'] = trim($this->config['action']);
        }
        $this->properties['debug']['enabled'] = true;
        $contents = "<?php\n return " . var_export($this->properties, true) . ";\n?>";
        $contents = strtr($contents, [
            "'TINY_PHAR_FILE/application" => "TINY_PHAR_FILE . '/application",
            "'TINY_HOME_DIR/var/'" => "TINY_HOME_DIR . '/var/'",
            "'TINY_HOME_DIR/view/'" => "TINY_HOME_DIR . '/view/'",
            "'TINY_HOME_DIR/config/'" => "TINY_HOME_DIR . '/config/'",
            "'TINY_HOME_DIR/profile/'" => "TINY_HOME_DIR . '/profile/'"
        ]);
        $this->pharHandler->addFromString('application/.properties.php', $contents);
        printf("add properties:\n   application/.properties.php\n");
    }
    
    /**
     * 添加文件或者文件夹进入打包文件
     *
     * @param string $path 打包路径
     * @param string $name 打包名
     * @param boolean $noFile 是否不添加文件
     */
    protected function addDir($path, $name, $noFile = false)
    {
        // $path = realpath($path);
        $path = preg_replace('#\/+#','/', $path);

        if (is_dir($path)) {
            $files = scandir($path);
            $this->pharHandler->addEmptyDir($name);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..' || $file[0] == '.') {
                    continue;
                }
                
                $fpath = $path . '/' . $file;
               
                if ($this->isExclude($fpath)) {
                    continue;
                }
                $fname = $name ? $name . '/' . $file : $file;
                $this->addDir($fpath, $fname, $noFile);
            }
            return;
        }
        if ($noFile || $this->isExclude($fpath)) {
            return;
        }
        echo "include:" . $path . "\n";
        $this->pharHandler->addFile($path, $name);
    }
    
    /**
     * 是否排除路径
     *
     * @param string $fpath 文件名
     * @return boolean
     */
    protected function isExclude($fpath)
    {
        foreach ((array)$this->config['exclude'] as $exclude) {
            if (preg_match($exclude, $fpath)) {
                echo 'exclude:' .  $fpath . "\n";
                return true;
            }
        }
        return false;
    }
    
    /**
     * 添加框架到打包文件
     */
    protected function addFrameWork()
    {
        $vendorPath = $this->config['vendor_path'];
        if (is_dir($vendorPath)) {
            printf("add vendor path:\n   %s\n", $vendorPath);
            $this->addDir($vendorPath, 'vendor');
            return;
        }
    }
    
    /**
     * 创建打包实例
     *
     * @param string $name 打包文件名
     * @return \Phar
     */
    protected function createrPhar($name)
    {
        if (!$this->pharHandler) {
            $binDir =  rtrim($this->config['bin_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR; 
            if (!$binDir || is_file($binDir)) {
                throw new BuilderException(sprintf('bin_path %s must be an dir !', $binDir));
            }
            if (!file_exists($binDir)) {
                mkdir($binDir, 0777, true);
            }
            
            $pfile = $binDir . $name. '.phar';
            
            $this->config['pfile'] = $pfile;
            $this->config['pname'] = $binDir . $name;
            if (file_exists($pfile)) {
                unlink($pfile);
            }
            $this->pharHandler = new \Phar($pfile);
            printf("builder starting:  \n    phar file[%s]  creating\n\n", $pfile);
        }
        return $this->pharHandler;
    }
    
    /**
     * 创建打包文件
     */
    protected function createrPharFile()
    {
        // properties
        $this->addProperties();
        
        // profile
        $profilestr = "APPLICATION_PATH . '.properties.php'";
        
        if ($this->data['profile_path']) {
            $profilestr = "[APPLICATION_PATH . '.properties.php',TINY_HOME_DIR . " . "'/profile/']";
        }
        
        // index.php
        $name = $this->config['name'];
        $id = md5($name . microtime(true));
        $indexstring = <<<EOT
        <?php
        define('TINY_PHAR_NAME', '$name');
        define('TINY_PHAR_FILE', dirname(__DIR__));
        define('TINY_PHAR_ID', '$id');
        define('TINY_HOME_DIR', \$_SERVER['HOME'] . '/.' . TINY_PHAR_NAME . '/' . TINY_PHAR_ID . '/');
        define('TINY_PHAR_DIR', str_replace('phar://', '', dirname(dirname(__DIR__))));
        define('APPLICATION_PATH', dirname(__DIR__) . '/application/');
        require_once(TINY_PHAR_FILE . '/vendor/autoload.php');
        \Tiny\Build\Builder::init([]);
        \Tiny\Tiny::createApplication(APPLICATION_PATH, $profilestr)->run();
        ?>
        EOT;
        
        // index.php stup
        printf("add default stub file:\n    index.php\n");
        $this->pharHandler->addFromString('application/index.php', $indexstring);
        $stub = $this->pharHandler->createDefaultStub('application/index.php', 'index.php');
        if ($this->config['php_path']) {
            $stub = "#!" . $this->config['php_path'] . "\n" . $stub;
        }
        $this->pharHandler->setStub($stub);
        
        
        $pfile = $this->config['pfile'];
        if (!is_file($pfile)) {
            return;
        }
        
        $extname  = $this->config['extname'];
        $pname = $this->config['pname'];
        chmod($pfile, 0777);
        if (!$extname) {
            if (is_file($pname)) {
                unlink($pname);
            }
            if (copy($pfile, $pname)) {
                chmod($pname, 0777);
                unlink($pfile);
            }
        }
    }
}
?>