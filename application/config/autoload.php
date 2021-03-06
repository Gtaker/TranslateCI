<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| 自动加载器
| -------------------------------------------------------------------
| 该文件指定默认加载的系统。
|
| 为了保证框架尽可能的轻量级。默认只会加载最小的绝对资源。
| 例如，数据库不会自动连接，因为我们不假设它会被使用。
| 该文件允许你全局定义一些你会在所有请求中都用得到的系统。
|
| -------------------------------------------------------------------
| 说明
| -------------------------------------------------------------------
|
| 你可以自动加载这些东西：
|
| 1. Packages                   包
| 2. Libraries                  类库
| 3. Drivers                    驱动
| 4. Helper files               帮助文件
| 5. Custom config files        自定义配置文件
| 6. Language files             语言文件
| 7. Models                     模型
|
*/

/*
| -------------------------------------------------------------------
|  自动加载包
| -------------------------------------------------------------------
| 示例：
|
|  $autoload['packages'] = array(APPPATH.'third_party', '/usr/local/shared');
|
*/
$autoload['packages'] = array();

/*
| -------------------------------------------------------------------
|  自动加载类库
| -------------------------------------------------------------------
| 这些类在本地的 system/libraries/ 或你的 application/libraries/ 目录中，
| 并且增加了 'database' 类库，这是一个特例。
|
| 实例：
|
|	$autoload['libraries'] = array('database', 'email', 'session');
|
| 你也可以在指定的控制器中加载不同的类库：
|
|	$autoload['libraries'] = array('user_agent' => 'ua');
*/
$autoload['libraries'] = array();

/*
| -------------------------------------------------------------------
|  自动加载驱动
| -------------------------------------------------------------------
| 这些类在本地的 system/libraries/ 目录或你的 application/libraries/ 目录中，
| 但也可以存在于在它们的子目录中并继承 CI_Driver_Library 类，
| 他们提供多种可互换的驱动设置。
|
| 示例：
|
|	$autoload['drivers'] = array('cache');
|
| 你也可以在指定的控制器中加载不同的驱动：
|
|	$autoload['drivers'] = array('cache' => 'cch');
|
*/
$autoload['drivers'] = array();

/*
| -------------------------------------------------------------------
|  自动加载帮助文件
| -------------------------------------------------------------------
| 示例：
|
|	$autoload['helper'] = array('url', 'file');
*/
$autoload['helper'] = array();

/*
| -------------------------------------------------------------------
|  自动加载配置文件
| -------------------------------------------------------------------
| 示例：
|
|	$autoload['config'] = array('config1', 'config2');
|
| 提示：该选项只在你创建了自定义配置文件时有效。
| 否则，请将其留空。
|
*/
$autoload['config'] = array();

/*
| -------------------------------------------------------------------
|  自动加载语言文件
| -------------------------------------------------------------------
| 示例：
|
|	$autoload['language'] = array('lang1', 'lang2');
|
| 提示：不要在你的文件中保留 "_lang" 的部分。
| 例如，"codeigniter_lang.php" 应该被引用为 array("codeigniter');
|
*/
$autoload['language'] = array();

/*
| -------------------------------------------------------------------
|  自动加载模型
| -------------------------------------------------------------------
| 示例：
|
|	$autoload['model'] = array('first_model', 'second_model');
|
| 你也可以在指定的控制器中加载不同的模型：
|
|	$autoload['model'] = array('first_model' => 'first');
*/
$autoload['model'] = array();
