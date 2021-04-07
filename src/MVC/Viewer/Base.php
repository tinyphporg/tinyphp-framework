<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月13日上午12:16:34
 * @Class List
 * @Function List
 * @History King 2017年3月13日上午12:16:34 0 第一次建立该文件
 *          King 2017年3月13日上午12:16:34 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC\Viewer;

/**
 * 视图基类
 *
 * @package Tiny.Application.Viewer
 * @since 2017年3月12日下午3:29:18
 * @final 2017年3月12日下午3:29:18
 */
abstract class Base implements IViewer
{

    /**
     * 模板目录
     *
     * @var string
     */
    protected $_templateFolder;

    /**
     * 模板解析目录
     *
     * @var string
     */
    protected $_compileFolder;

    /**
     * 预先分配变量
     *
     * @var array
     */
    protected $_variables = [];

    /**
     * 设置模板引擎的模板文件夹
     *
     * @param string $path
     *        文件夹路径
     * @return void
     */
    public function setTemplateFolder($path)
    {
        $this->_templateFolder = $path;
    }

    /**
     * 获取模板引擎的模板文件夹
     *
     * @param string $path
     *        文件夹路径
     * @return string 视图文件夹路径
     */
    public function getTemplateFolder()
    {
        return $this->_templateFolder;
    }

    /**
     * 设置模板引擎的编译文件夹
     *
     * @param string $path
     *        文件夹路径
     * @return void
     */
    public function setCompileFolder($path)
    {
        $this->_compileFolder = $path;
    }

    /**
     * 获取模板引擎的编译文件夹
     *
     * @param string $path
     *        文件夹路径
     * @return string 编译存放路径
     */
    public function getCompileFolder()
    {
        return $this->_compileFolder;
    }

    /**
     * 分配变量
     *
     * @param string $key
     *        变量分配的键
     * @param mixed $value
     *        分配的值
     * @return void
     *
     */
    public function assign($key, $value = NULL)
    {
        if (is_array($key))
        {
            $this->_variables = array_merge($this->_variables, $key);
            return;
        }
        $this->assign($key, $value);
    }

    /**
     * 输出解析内容
     *
     * @param string $file
     *        模板路径
     * @return void
     *
     */
    public function display($file, $isAbsolute = FALSE)
    {
        echo $this->fetch($file, $isAbsolute);
    }
}
?>