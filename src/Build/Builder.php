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

/**
 * 打包实例
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
    protected $_config;

    /**
     * 打包数据
     *
     * @var array
     */
    protected $_data = [];

    /**
     * pharhandler句柄
     *
     * @var \Phar
     */
    protected $_pharHandler;

    /**
     * 打包文件里面的properties配置数据
     *
     * @var array
     */
    protected $_properties;

    /**
     * 运行打包后程序的初始化
     * 
     * @return void
     */
    public static function init()
    {
        /*解压附件在应用实例当前目录*/
        $attachmentPath = TINY_PHAR_FILE . '/attachments/';
        if (file_exists($attachmentPath))
        {
            self::_unpackage($attachmentPath, '', TINY_PHAR_DIR);
        }
        
        /*解压附件在用户工作目录*/
        $homeAttachmentPath = TINY_PHAR_FILE . '/home_attachments/';
        if (file_exists($homeAttachmentPath))
        {
            self::_unpackage($homeAttachmentPath, '', TINY_HOME_DIR);
        }   
    }

    /**
     * 解压phar文件里面的文件到指定路径
     *
     * @param string $dpath
     *        解压路径
     * @param string $dname
     *        本地相对路径
     * @param string $rootPath
     *        本地根路径
     */
    protected static function _unpackage($pharDir, $localDir = '', $rootDir = '')
    {
        if (!is_dir($pharDir))
        {
            return;
        }

        $pharNames = scandir($pharDir);
        foreach ($pharNames as $pname)
        {
            if ($pname == '.' || $pname == '..')
            {
                continue;
            }
            $pharPath = $pharDir . $pname;
            $localPath = $localDir . $pname;
            $destPath = $rootDir . '/' . $localPath;
            
            // 创建文件夹
            if (is_dir($pharPath))
            {
                if (!file_exists($destPath))
                {
                    mkdir($destPath, 0777, TRUE);
                }
                return self::_unpackage($pharPath . '/', $localPath . '/', $rootDir);
            }
            if (!file_exists($destPath) || filemtime($destPath) < filemtime($pharPath))
            {
                copy($pharPath, $destPath);
            }
        }
    }

    /**
     * 初始化构建器
     *
     * @param array $config 配置数组 由Builder插件传入
     */
    public function __construct(array $config)
    {
        if (ini_get('phar.readonly'))
        {
            throw new BuilderException('creating archive "tiny-demo.phar" disabled by the php.ini setting phar.readonly,This setting can only be unset in php.ini due to security reasons.');
        }
        $this->_config = $config;
        
        // 应用实例的properties数组
        $this->_properties = $this->_config['properties'];
        
        //关闭phar应用程序打包phar
        $this->_properties['build']['enabled'] = FALSE;
    }

    /**
     * 执行phar打包
     *
     * @return boolean
     */
    public function run()
    {
        $name = $this->_config['name'] ?: 'tinyd';
        $this->_createrPhar($name);
        $this->_addHomeAttachments();
        $this->_addAttachments();
        $this->_addApplication();
        $this->_addConfig();
        $this->_addImprot();
        $this->_addFrameWork();
        $this->_createrPharFile();
        return TRUE;
    }

    /**
     * 添加 解压在用户目录的附件
     */
    protected function _addHomeAttachments()
    {
        printf("add home_attachments:\n");
        
        // runtime
        $homefiles = $this->_config['home_attachments'] ?: [];
        $this->_pharHandler->addEmptyDir('home_attachments/runtime');
        $this->_properties['app']['runtime'] = 'TINY_HOME_DIR/runtime/';
        
        // config 
        if ($homefiles['config'])
        {
            $this->_pharHandler->addEmptyDir('home_attachments/config');
            $this->_data['config_path'] = 'TINY_HOME_DIR/config/';
        }
        
        // profile
        if ($homefiles['profile'])
        {
            $this->_pharHandler->addEmptyDir('home_attachments/profile');
            
            // 添加的配置路径
            $this->_data['profile_path'] = 'TINY_HOME_DIR/profile/';
        }
        
        // 循环添加附件
        foreach ($homefiles as $atts)
        {
            $name = $atts[0];
            $path = $atts[1];
            $isNoFile = (bool)$atts[2];
            $aname = 'home_attachments/' . $name;
            $this->_addDir($path, $aname, $isNoFile);
            printf("    %s => %s\n", $aname, $path);
        }
    }
    
    /**
     * 添加解压在phar所在目录的附件
     *
     */
    protected function _addAttachments()
    {
        printf("add attachments:\n");
        $attachments = $this->_config['attachments'] ?: [];
        
        // 循环添加附件
        foreach ($attachments as $atts)
        {
            $name = $atts[0];
            $path = $atts[1];
            $isNoFile = (bool)$atts[2];
            $aname = 'attachments/' . $name;
            $this->_addDir($path, $aname, $isNoFile);
            printf("    %s => %s\n", $aname, $path);
        }
    }

    /**
     * 添加应用实例的文件夹
     *
     */
    protected function _addApplication()
    {
        $apppath = realpath($this->_config['application_path']);
        printf("add application:\n  %s\n", $apppath);
        $this->_addDir($apppath, 'application');
    }

    /**
     * 缓存application的配置文件数据到打包程序中
     */
    protected function _addConfig()
    {
        printf("add config\n    application/.config.php\n");
        $config = $this->_config['config'];
        $contents = "<?php\n return " . var_export($config, true) . ";\n?>";
        $this->_pharHandler->addFromString('application/.config.php', $contents);
        
        $this->_properties['config']['cache']['enabled'] = FALSE;
        $this->_properties['config']['lang']['enabled'] = FALSE;
        $this->_properties['config']['path'] = [];
        $this->_properties['config']['path'][] = 'TINY_PHAR_FILE/application/.config.php';
        if ($this->_data['config_path'])
        {
            $this->_properties['config']['path'][] = $this->_data['config_path'];
        }
        $cindex = array_search('config.path', $this->_properties['path']);
        if (FALSE !== $cindex)
        {
            unset($this->_properties['path'][$cindex]);
        }
    }

    /**
     * 添加引用库
     *
     */
    protected function _addImprot()
    {
        printf("add imports library:\n");
        $imports = $this->_config['imports'];
        $idata = [];
        foreach ($imports as $ns => $path)
        {
            $name = 'application/.import/' . md5($ns);
            if (file_exists($path))
            {
                $this->_addDir($path, $name);
            }
            else
            {
                $this->_pharHandler->addEmptyDir($name);
            }
            $idata[$ns] = 'TINY_PHAR_FILE' . '/' . $name . '/';
            printf("    %s => %s\n", $ns, $path);
        }
        $this->_properties['autoloader']['librarys'] = $idata;
        $this->_properties['autoloader']['no_realpath'] = TRUE;
    }

    /**
     * 处理并添加application配置文件
     */
    protected function _addProperties()
    {
        if ($this->_config['controller'])
        {
            $this->_properties['controller']['default'] = trim($this->_config['controller']);
        }
        if ($this->_config['action'])
        {
            $this->_properties['action']['default'] = trim($this->_config['action']);
        }
        $contents = "<?php\n return " . var_export($this->_properties, TRUE) . ";\n?>";
        $contents = strtr($contents, [
            "'TINY_PHAR_FILE/application" => "TINY_PHAR_FILE . '/application",
            "'TINY_HOME_DIR/runtime/'" => "TINY_HOME_DIR . '/runtime/'",
            "'TINY_HOME_DIR/config/'" => "TINY_HOME_DIR . '/config/'",
            "'TINY_HOME_DIR/profile/'" => "TINY_HOME_DIR . '/profile/'"
        ]);
        $this->_pharHandler->addFromString('application/.properties.php', $contents);
        printf("add properties:\n   application/.properties.php\n");
    }

    /**
     * 添加文件或者文件夹进入打包文件
     *
     * @param string $path 打包路径
     * @param string $name 打包名
     * @param boolean $noFile 是否不添加文件
     */
    protected function _addDir($path, $name, $noFile = FALSE)
    {
        if (is_dir($path))
        {
            $files = scandir($path);
            $this->_pharHandler->addEmptyDir($name);
            foreach ($files as $file)
            {
                if ($file == '.' || $file == '..' || $file[0] == '.')
                {
                    continue;
                }

                $fpath = $path . '/' . $file;
                if ($this->_isExclude($fpath))
                {
                    continue;
                }
                $fname = $name ? $name . '/' . $file : $file;
                echo $fpath;
                $this->_addDir($fpath, $fname, $noFile);
            }
            return;
        }
        if ($noFile)
        {
            return;
        }
        $this->_pharHandler->addFile($path, $name);
    }

    /**
     *  是否排除路径
     *  
     * @param string $fpath 文件名
     * @return boolean
     */
    protected function _isExclude($fpath)
    {
        foreach ((array)$this->_config['exclude'] as $exclude)
        {
            if (preg_match($exclude, $fpath)) 
            {
                return TRUE;
            }
        }
        return FALSE;   
    }
    
    /**
     * 添加框架到打包文件
     * 
     */
    protected function _addFrameWork()
    {
        $vendorPath = $this->_config['vendor_path'];
        if (is_dir($vendorPath))
        {
            printf("add vendor path:\n   %s\n", $vendorPath);
            $this->_addDir($vendorPath, 'vendor');
            return;
        }
        
        $fwpath = realpath($this->_config['framework_path']);
        printf("add framework path:\n   %s\n", $fwpath);
        $this->_addDir($fwpath, 'tiny-framework');
    }

    /**
     * 创建打包实例
     *
     * @param string $name 打包文件名
     * @return \Phar
     */
    protected function _createrPhar($name)
    {
        if (!$this->_pharHandler)
        {
            $filename = $name . '.phar';
            if (file_exists($filename))
            {
                unlink($filename);
            }
            $this->_pharHandler = new \Phar($filename);
            printf("builder starting:  \n    phar file[%s]  creating\n\n", $filename);
        }
        return $this->_pharHandler;
    }

    /**
     * 创建打包文件
     */
    protected function _createrPharFile()
    {
        //properties
        $this->_addProperties();
        
        //profile
        $profilestr = "APPLICATION_PATH . '.properties.php'";
        
        if ($this->_data['profile_path'])
        {
            $profilestr = "[APPLICATION_PATH . '.properties.php',TINY_HOME_DIR . " . "'/profile/']";
        }

        //index.php
        $id = $this->_config['name'];
        $indexstring = <<<EOT
        <?php
        define('TINY_PHAR_ID', '$id');
        define('TINY_PHAR_FILE', dirname(__DIR__));
        define('TINY_HOME_DIR', \$_SERVER['HOME'] . '/.' . TINY_PHAR_ID);
        define('TINY_PHAR_DIR', str_replace('phar://', '', dirname(dirname(__DIR__))));
        define('APPLICATION_PATH', dirname(__DIR__) . '/application/');
        define('TINY_COMPOSER_FILE', TINY_PHAR_FILE . '/vendor/autoload.php');
        if (is_file(TINY_COMPOSER_FILE))
        {
            require(TINY_COMPOSER_FILE);
        }
        else
        {
            require(TINY_PHAR_FILE . '/tiny-framework/Tiny.php');
        }
        // \Tiny\Runtime\Runtime::getInstance();
        \Tiny\Build\Builder::init([]);
        \Tiny\Tiny::createApplication(APPLICATION_PATH, $profilestr)->run();
        ?>
        EOT;

        //index.php stup
        printf("add default stub file:\n    application/index.php\n");
        $this->_pharHandler->addFromString('application/index.php', $indexstring);
        $stub = $this->_pharHandler->createDefaultStub('application/index.php', 'application/index.php');
        if ($_SERVER['_'])
        {
            $stub = "#!" . $_SERVER['_'] . "\n" . $stub;
        }
        $this->_pharHandler->setStub($stub);
    }
}
?>