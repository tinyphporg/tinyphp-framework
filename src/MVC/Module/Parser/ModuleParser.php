<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ModuleParser.php
 * @author King
 * @version stable 2.0
 * @Date 2022年8月16日下午6:38:13
 * @Class List class
 * @Function List function_container
 * @History King 2022年8月16日下午6:38:13 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Module\Parser;

use Tiny\Config\Configuration;
use Tiny\MVC\Application\WebApplication;
use Tiny\MVC\Module\ModuleManager;
use Tiny\MVC\Application\ApplicationBase;
use Tiny\MVC\Application\Properties;

class ModuleParser
{
    
    /**
     * 默认配置
     *
     * @var array
     */
    const DEFAULT_SETTINGS = [
        'NAMESPACE_CONTROLLER' => '\\Controller',
        'NAMESPACE_CONTROLLER_CONSOLE' => '\\Controller\\Console',
        'NAMESPACE_MODEL' => '\\Model',
        'NAMESPACE_EVENT' => '\\Event',
        'ACTION_SUFFIX' => 'Action',
        'CONFIG_PATH' => 'config/',
        'LANG_PATH' => 'lang/',
        'CONTROLLER_PATH' => 'controllers/web/',
        'CONTROLLER_CONSOLE_PATH' => 'controllers/console/',
        'MODEL_PATH' => 'models/',
        'EVENT_PATH' => 'events/',
        'LIBRARY_PATH' => 'librarys/',
        'LIBRARY_GLOBAL_PATH' => '/librarys/global',
        'VIEW_PATH' => 'views/',
    ];
    
    /**
     *
     * @var ModuleManager
     */
    protected $moduleManager;
    
    /**
     * 当前应用实例
     *
     * @var ApplicationBase
     */
    protected $app;
    
    /**
     * 属性实例
     *
     * @var Properties
     */
    protected $properties;
    
    /**
     * 已解析的命名空间
     *
     * @var array
     */
    protected $parsedNamespaces = [];
    
    /**
     * 正在解析依赖树的模块列表
     * 
     * @var array
     */
    protected $requiringModuleNames = [];
    
    /**
     *
     * @param ModuleManager $moduleManager
     * @param ApplicationBase $app
     */
    public function __construct(ModuleManager $moduleManager, ApplicationBase $app)
    {
        $this->app = $app;
        $this->properties = $app->properties;
        $this->moduleManager = $moduleManager;
    }
    
    /**
     * 解析每个模块的模块依赖树
     * 
     * @param array $reqs
     * @param array $modules
     * @return array|boolean|array
     */
    public function parseModuleRequires($moduleName, $requires, &$modules)
    {
        if (!$requires) {
            return [];
        }
        
        $mrequires = [];
        $this->requiringModuleNames[$moduleName] = true;
        foreach($requires as $require) {
            $mname = $require['module'];
            
            // 防止不存在的包和循环依赖
            if (!key_exists($mname, $modules) || key_exists($mname, $this->requiringModuleNames)) {
                $require['status'] = 0;
                $mrequires[] = $require;
                unset($this->requiringModuleNames[$moduleName]);
                return $mrequires;
            }
            
            // 向下解析依赖
            $mrequires[] = $require;
            $sourceRequires = $this->parseModuleRequires($mname, $modules[$mname]['requires'], $modules);
            foreach($sourceRequires as $srequire) {
                $mrequires[] = $srequire;
            }
        }
        
        unset($this->requiringModuleNames[$moduleName]);
        return $mrequires;
    }
    
    /**
     * 解析该路径的配置文件
     *
     * @param string $path 配置文件路径
     * @return void|array
     */
    public function parse($path)
    {
        $module = [];
        $profile = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }
        
        // module name
        $name = (string)$profile['name'];
        if (!preg_match('/^[a-z][a-z0-9\-_]*$/', $name)) {
            return;
        }

        // 定义状态数组
        $module['status'] = [
            'activated' => false, 
            'inited' => false,
            'errno' => 0,   // 错误代码
            'errmsg' => '', // 错误信息
        ];
        
        $module['profile_path'] = $path;
        $module['profile'] = $profile;
        $module['desc'] = (string)$profile['desc'];
        
        // module namespace;
        $namespace = (string)$profile['namespace'];
        if (!preg_match('/[A-Z][a-z]+/', $namespace)) {
            return;
        }
        
        // profile setting
        $mconfig = (array)$this->properties['module'];
        if (key_exists($name, $mconfig)) {
            $mconfig = (array)$mconfig[$name];
            if ($mconfig) {
                $module['profile']['setting'] = array_replace_recursive((array)$profile['setting'], $mconfig);
            }
        }
        
        // module version
        $module['name'] = $name;
        $module['basedir'] = dirname($path) . DIRECTORY_SEPARATOR;
        $module['index'] = (string)$profile['index'];
        
        $module['eventlistener'] = $profile['eventlistener'];
        $module['version'] = (string)$profile['version'];
        
        // 是否在模块管理器初始化时加载模块
        $module['inited'] = (bool)$profile['init'];
        
        // 默认启用
        $module['activated'] = true;
        
        // 默认非禁用
        $module['disabled'] = (bool)$profile['disabled'];
        
        // 默认的命名空间配置
        $module['namespace']['root'] = $namespace;
        $module['namespace']['controller'] = '';
        $module['namespace']['namespaces'] = [];
        
        // 屏蔽某些可能重复的命名空间自动注入
        $module['namespace']['ignores'] = (array)$profile['autoloader']['ignores'];
        $module['lang'] = [];
        
        // requires
        $this->parseRequires($module);
        
        // router
        $this->parseRoutes($module);
        
        // module path
        $this->parsePaths($module);
        
        // config
        $this->parseConfig($module);
        
        // 解析语言包
        $this->parseLang($module);
        
        // namespaces
        $this->parseNamespaces($module);
        
        // static
        $this->parseStatic($module);
        
        $this->modules[$name] = $module;
        return $module;
    }
    
    /**
     * 解析模块路径
     *
     * @param array $moduleConfig
     * @param string $basedir
     */
    protected function parsePaths(array &$moduleConfig)
    {
        $name = $moduleConfig['name'];
        $basedir = $moduleConfig['basedir'];
        $index = $moduleConfig['index'];
        $indexdir = $index ? $this->app->properties->path($index, [], $basedir) : $basedir;
        $paths = [
            'config' => self::DEFAULT_SETTINGS['CONFIG_PATH'],
            'lang' => self::DEFAULT_SETTINGS['LANG_PATH'],
            'controller' => self::DEFAULT_SETTINGS['CONTROLLER_PATH'],
            'controller_console' => self::DEFAULT_SETTINGS['CONTROLLER_CONSOLE_PATH'],
            'model' => self::DEFAULT_SETTINGS['MODEL_PATH'],
            'event' => self::DEFAULT_SETTINGS['EVENT_PATH'],
            'view' => self::DEFAULT_SETTINGS['VIEW_PATH'],
            'library' => self::DEFAULT_SETTINGS['LIBRARY_PATH'],
            'global' => self::DEFAULT_SETTINGS['LIBRARY_GLOBAL_PATH']
        ];
        
        $parsedPaths = [];
        foreach ($paths as $key => &$value) {
            $value = $indexdir . $value;
            $parsedPaths['module.' . $name . '.' . $key] = $value;
        }
        $paths['profile'] = $basedir;
        $paths['basedir'] = $basedir;
        $paths['indexdir'] = $indexdir;
        
        // view
        if (!is_dir($paths['view'])) {
            $paths['view'] = null;
        }
        
        $moduleConfig['parsedPaths'] = $parsedPaths;
        $moduleConfig['path'] = $paths;
    }
    
    /**
     * 解析路由
     *
     * @param array $moduleConfig
     */
    protected function parseRoutes(&$moduleConfig)
    {
        $routes = (array)$moduleConfig['profile']['routes'];
        foreach ($routes as &$route) {
            $route['rule'] = (array)$route['rule'];
            if (!key_exists('module', $route['rule'])) {
                $route['rule']['module'] = $moduleConfig['name'];
            }
        }
        $moduleConfig['routes'] = $routes;
    }
    
    /**
     * 解析模块配置
     *
     * @param array $moduleConfig
     */
    protected function parseConfig(array &$moduleConfig)
    {
        $profile = $moduleConfig['profile'];
        $paths = $moduleConfig['path'];
        $moduleConfig['config'] = [];
        if ($profile['config'] && is_dir($paths['config'])) {
            $configData = is_array($profile['config']) ? $profile['config'] : [];
            $configInstance = new Configuration($paths['config']);
            $configData = array_merge($configData, $configInstance->get());
            $moduleConfig['config'] = $configData;
        }
    }
    
    /**
     * 解析语言包
     *
     * @param array $moduleConfig
     */
    protected function parseLang(array &$moduleConfig)
    {
        $profile = $moduleConfig['profile'];
        $paths = $moduleConfig['path'];
        if ($profile['lang'] && is_dir($paths['lang'])) {
            $configData = is_array($profile['lang']) ? $profile['lang'] : [];
            $configInstance = new Configuration($paths['lang']);
            $configData = array_merge($configData, (array)$configInstance->get());
            $moduleConfig['lang'] = $configData;
        } else {
            $moduleConfig['lang'] = [];
        }
    }
    
    /**
     * 解析requires
     *
     * @param array $moduleConfig
     * @param array $requires
     */
    protected function parseRequires(& $module)
    {
        $reqs = [];
        $matchs = [];
        $requires = (array)$module['profile']['require'];
        foreach ($requires as $mname => $req) {
            if (preg_match("/^\s*(>=|>|<|<=|=|)\s*([a-z0-9][a-z0-9\.]*)\s*$/i", $req, $matchs)) {
                $reqs[] = [
                    'module' => $mname,
                    'status' => 1,
                    'operator' => $matchs[1],
                    'version' => $matchs[2]
                ];
            }
        }
        $module['requires'] = $reqs;
    }
    
    /**
     * 解析命名空间
     *
     * @param array $moduleConfig
     * @param string $name
     * @param string $profile
     * @param array $paths
     * @param array $parsedPaths
     * @param string $basedir
     */
    protected function parseNamespaces(&$module)
    {
        $name = $module['name'];
        $profile = $module['profile'];
        
        $paths = $module['path'];
        $indexdir = $paths['indexdir'];
        $parsedPaths = $module['parsedPaths'];
        $config = $module['namespace'];
        // root namespace
        $namespace = $config['root'] ?: ucfirst($name);
        $namespace = rtrim($namespace, '\\');
        
        // 命名空间
        $controllerNamespace = $namespace . self::DEFAULT_SETTINGS['NAMESPACE_CONTROLLER'];
        $consoleControllerNamespace = $namespace . self::DEFAULT_SETTINGS['NAMESPACE_CONTROLLER_CONSOLE'];
        $modelNamespace = $namespace . self::DEFAULT_SETTINGS['NAMESPACE_MODEL'];
        $eventNamespace = $namespace . self::DEFAULT_SETTINGS['NAMESPACE_EVENT'];
        
        $defaultNamespaces = [
            $namespace => $paths['library'],
            $controllerNamespace => $paths['controller'],
            $consoleControllerNamespace => $paths['controller_console'],
            $modelNamespace => $paths['model'],
            $eventNamespace => $paths['event'],
        ];
        
        // 控制器的命名空间
        $module['namespace']['controller'] = $this->app instanceof WebApplication ? $controllerNamespace : $consoleControllerNamespace;
        
        // 命名空间列表
        $namespaces = array_merge($defaultNamespaces, (array)$profile['autoloader']['namespaces']);
        $module['namespace']['namespaces'] = $this->formatNamespaces($namespace, $namespaces);
        foreach ($module['namespace']['namespaces'] as &$npath) {
            $npath = $this->app->properties->path($npath, $parsedPaths, $indexdir);
        }
        
        // 全局命名空间
        $globalPath = (string)$profile['autoloader']['global'];
        $globalPath = $this->app->properties->path($globalPath ?: $paths['global'], $parsedPaths, $indexdir);
        if ($globalPath) {
            $module['namespace']['namespaces']['*'] = $globalPath;
        }
    }
    
    /**
     * 解析配置的静态文件复制信息
     *
     * @param array $moduleConfig
     */
    protected function parseStatic(&$moduleConfig)
    {
        $name = $moduleConfig['name'];
        $profile = $moduleConfig['profile'];
        $basedir = $moduleConfig['basedir'];
        $paths = $moduleConfig['path'];
        $parsedPaths = $moduleConfig['parsedPaths'];
        
        // 静态资源存放目录
        $toStaticDir = $this->app->properties['view.static.basedir'] . $name . DIRECTORY_SEPARATOR;
        $toPublicPath = $this->app->properties['view.static.public_path'] . $name . DIRECTORY_SEPARATOR;
        // 格式化静态资源配置
        $staticConfig = (array)$profile['autoloader']['static'];
        $static = [
            'enabled' => true,
            'completed' => false,
            'web' => true
        ];
        if (array_key_exists('web', $staticConfig) && !$staticConfig['web']) {
            $static['web'] = false;
        }
        
        $staticPaths = [];
        $paths = (array)$staticConfig['paths'];
        foreach ($paths as $path) {
            if (is_string($path)) {
                $from = $this->app->properties->path($path, $parsedPaths, $basedir);
                $to = $toStaticDir;
                $staticPaths[] = [
                    'from' => $from,
                    'to' => $to,
                    'exclude' => false,
                    'replace' => false
                ];
                continue;
            }
            if (is_array($path)) {
                if (!key_exists('from', $path)) {
                    continue;
                }
                $from = $this->app->properties->path($path['from'], $parsedPaths, $basedir);
                $to = key_exists('to', $path) ? $this->app->properties->path($path['to'], [], $toStaticDir) : $toStaticDir;
                $exclude = (string)$path['exclude'] ?? false;
                $replace = (array)$path['replace'] ?? false;
                $staticPaths[] = [
                    'from' => $from,
                    'to' => $to,
                    'exclude' => $exclude,
                    'replace' => $replace
                ];
            }
        }
        
        $static['paths'] = $staticPaths;
        
        // replace
        foreach ($static['paths'] as &$pathinfo) {
            if (!$pathinfo['replace'] || !is_array($pathinfo['replace'])) {
                continue;
            }
            
            foreach ($replace as $index => $replaceArr) {
                $pathinfo['replace'][$index] = $this->formatReplace($replaceArr);
            }
        }
        if (!$staticPaths) {
            $static['enabled'] = false;
            $static['completed'] = true;
        }
        
        // update moduleconfig
        $moduleConfig['static'] = $static;
        $moduleConfig['profile']['setting']['public_path'] = $toPublicPath;
    }
    
    /**
     * 格式化替换的字符串设置
     *
     * @param array $replaceArr
     */
    protected function formatReplace($replaceArr)
    {
        $regex = trim($replaceArr['regex']);
        if (!$regex) {
            return;
        }
        
        $source = trim($replaceArr['source']);
        if (!$source) {
            return;
        }
        
        $replace = trim($replaceArr['replace']);
        if (!$replace) {
            return;
        }
        $replace = preg_replace_callback('/\{properties\.(.*?)\}/', function ($matchs) {
            $nodeKey = $matchs[1];
            return $this->app->properties[$nodeKey];
        }, $replace);
        
        $replace = preg_replace('/\/+/', '/', $replace);
        return [
            'regex' => $regex,
            'source' => $source,
            'replace' => $replace
        ];
    }
    
    /**
     * 格式化命名空间
     *
     * @param string $namespace
     * @param array $namespaces
     * @return []
     */
    protected function formatNamespaces($namespace, $namespaces)
    {
        $rnamespaces = [];
        foreach ($namespaces as $childNamespace => $path) {
            $childNamespace = rtrim($childNamespace, '\\');
            if ($childNamespace !== $namespace && strpos($childNamespace, $namespace . '\\') !== 0) {
                continue;
            }
            $rnamespaces[$childNamespace] = $path;
        }
        return $rnamespaces;
    }
}

?>