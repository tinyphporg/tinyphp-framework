<?php
/**
 * @Copyright (C), 2013-, King.
 * @Name index.php
 * @Author King
 * @Version stable 1.0.1
 * @Date 2019年11月18日上午11:18:04
 * @Description 主入口文件
 * @History King 2019年11月18日上午11:18:04 第一次建立该文件
 *          King 2019年11月18日上午11:18:04 修改
 *          King 2020年6月5日16:04 stable 1.0.01 审定
 */

/*tiny根目录*/
define('ZEROAI_ROOT_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

/*加载库*/
define('ZEROAI_COMPOSER_FILE', ZEROAI_ROOT_PATH . '/vendor/autoload.php');
define('ZEROAI_LIBRARY_FILE', ZEROAI_ROOT_PATH . '/src/ZeroAI.php');
include_once (is_file(ZEROAI_COMPOSER_FILE) ? ZEROAI_COMPOSER_FILE : ZEROAI_LIBRARY_FILE);

/*设置application主目录*/
define('APPLICATION_PATH', dirname(__DIR__) . '/application/');

/*application run 自动识别web/console模式*/
\ZeroAI\ZeroAI::createApplication(APPLICATION_PATH, APPLICATION_PATH . 'config/profile.php')->run();