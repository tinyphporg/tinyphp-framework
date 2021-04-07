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
     * 运行打包后程序的初始化程序
     */
    public static function init()
    {
        /**
         * 解压附件
         *
         * @var Ambiguous $attachmentPath
         */
        $attachmentPath = ZEROAI_PHAR_FILE . '/attachments/';
        if (file_exists($attachmentPath))
        {
            self::_unpackage($attachmentPath, '', ZEROAI_PHAR_DIR);
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
                self::_unpackage($pharPath . '/', $localPath . '/', $rootDir);
            }

            // 复制文件到本地
            if (is_file($pharPath))
            {
                if (!file_exists($destPath) || filemtime($destPath) < filemtime($pharPath))
                {
                    copy($pharPath, $destPath);
                }
            }
        }
    }

    /**
     * 初始化构建器
     *
     * @param array $config
     *        配置
     */
    public function __construct(array $config)
    {
        if (ini_get('phar.readonly'))
        {
            throw new BuilderException('creating archive "tiny-demo.phar" disabled by the php.ini setting phar.readonly,This setting can only be unset in php.ini due to security reasons.');
        }
        $this->_config = $config;
        // 应用程序的properties
        $this->_properties = $this->_config['properties'];
        $this->_properties['build']['enable'] = FALSE;
    }

    /**
     * 运行
     *
     * @return void
     */
    public function run()
    {
        $name = $this->_config['name'] ?: 'tinyd';
        $this->_createrPhar($name);
        $this->_addAttachments();
        $this->_addApplication();
        $this->_addConfig();
        $this->_addImprot();
        $this->_addFrameWork();
        $this->_createrPharFile();
        return TRUE;
    }

    /**
     * 添加附件
     *
     * @return void
     */
    protected function _addAttachments()
    {
        printf("add attachments:\n");
        $attachments = $this->_config['attachments'];

        // runtime
        $this->_pharHandler->addEmptyDir('attachments/runtime');
        $this->_properties['app']['runtime'] = 'ZEROAI_PHAR_DIR/runtime/';

        // setting
        if ($attachments['setting'])
        {
            $this->_pharHandler->addEmptyDir('attachments/setting');
            $this->_data['config_path'] = 'ZEROAI_PHAR_DIR/setting/';
        }

        // profile
        if ($attachments['profile'])
        {
            $this->_pharHandler->addEmptyDir('attachments/profile');
            // 添加的配置路径
            $this->_data['profile_path'] = 'ZEROAI_PHAR_DIR/profile/';
        }

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
     * 添加应用文件夹
     *
     * @return void
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
        printf("add config\n");
        $config = $this->_config['config'];
        $contents = "<?php\n return " . var_export($config, true) . ";\n?>";
        $this->_pharHandler->addFromString('application/.config.php', $contents);

        $this->_properties['config']['cache']['enable'] = FALSE;

        $this->_properties['config']['path'] = [];
        $this->_properties['config']['path'][] = 'ZEROAI_PHAR_FILE/application/.config.php';
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
     * @return void
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
            $idata[$ns] = 'ZEROAI_PHAR_FILE' . '/' . $name . '/';
            printf("    %s => %s\n", $ns, $path);
        }
        $this->_properties['imports'] = $idata;
        $this->_properties['import_no_replacepath'] = TRUE;
    }

    /**
     * 处理并添加application配置文件
     */
    protected function _addProperties()
    {
        $contents = "<?php\n return " . var_export($this->_properties, TRUE) . ";\n?>";
        $contents = strtr($contents, [
            "'ZEROAI_PHAR_FILE/application" => "ZEROAI_PHAR_FILE . '/application",
            "'ZEROAI_PHAR_DIR/runtime/'" => "ZEROAI_PHAR_DIR . '/runtime/'",
            "'ZEROAI_PHAR_DIR/setting/'" => "ZEROAI_PHAR_DIR . '/setting/'",
            "'ZEROAI_PHAR_DIR/profile/'" => "ZEROAI_PHAR_DIR . '/profile/'"
        ]);
        $this->_pharHandler->addFromString('application/.properties.php', $contents);
        printf("add properties:\n   application/.properties.php\n");
    }

    /**
     * 添加文件或者文件夹进入打包文件
     *
     * @param string $path
     *        打包路径
     * @param string $name
     *        打包名
     * @param boolean $noFile
     *        是否不添加文件
     */
    protected function _addDir($path, $name, $noFile = FALSE)
    {
        if (is_dir($path))
        {
            $files = scandir($path);
            $this->_pharHandler->addEmptyDir($name);
            foreach ($files as $file)
            {
                if ($file == '.' || $file == '..' || $file[0] == '.' || preg_match("/.phar$/i", $file))
                {
                    continue;
                }
                $fpath = $path . '/' . $file;
                $fname = $name ? $name . '/' . $file : $file;
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
     * 添加框架到打包文件
     */
    protected function _addFrameWork()
    {
        printf("add framework path:\n   %s\n", $fwpath);
        $fwpath = realpath(FRAMEWORK_PATH);
        $this->_addDir($fwpath, 'tiny-framework');
    }

    /**
     * 创建打包实例
     *
     * @param string $name
     *        打包文件名
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
            printf("builder starting:  phar file[%s]creating\n", $filename);
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

        //setting
        /*$contents = "<?php\n return " . var_export($this->_data, TRUE) . ";\n?>";
          $this->_pharHandler->addFromString('.setting.php', $contents);
        */

        //profile
        $profilestr = "APPLICATION_PATH . '.properties.php'";
        if ($this->_data['profile_path'])
        {
            $profilestr = "[APPLICATION_PATH . '.properties.php',ZEROAI_PHAR_DIR . " . "'/profile/']";
        }

        //index.php
        $indexstring = <<<EOT
        <?php
        define('ZEROAI_PHAR_FILE', dirname(__DIR__));
        define('ZEROAI_HOME_DIR', '/root/.' . str_replace('.phar', '', basename(ZEROAI_PHAR_FILE)));
        define('ZEROAI_PHAR_DIR', str_replace('phar://', '', dirname(dirname(__DIR__))));
        define('APPLICATION_PATH', dirname(__DIR__) . '/application/');
        require(ZEROAI_PHAR_FILE . '/tiny-framework/Tiny.php');
        \Tiny\Runtime\Runtime::getInstance();
        \Tiny\Build\Builder::init([]);
        \Tiny\Tiny::createApplication(APPLICATION_PATH, $profilestr)->run();
        ?>
        EOT;

        //index.php stup
        printf("add default stub file:\n    application/index.php\n");
        $this->_pharHandler->addFromString('application/index.php', $indexstring);
        $stub = $this->_pharHandler->createDefaultStub('application/index.php', 'application/index.php');
        if ($this->_config['header_php_env'])
        {
            $stub = "#!" . $this->_config['php_env_path'] . "\n" . $stub;
        }
        $this->_pharHandler->setStub($stub);
    }
}
?>