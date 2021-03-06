<?php

namespace System;

/**
 * 框架核心类
 * 
 * @author Ding <beyondye@gmail.com>
 */
class System
{

    /**
     * 加载类并返回类实例
     * 
     * @global array $instances 全局实例数组
     * 
     * @param string $name 类名称
     * @param string $namespace 类所在模块命名空间和目录路径一致
     * @param string $alias 实例别名
     * @param string|array $arguments 类构造函数参数
     * 
     * @return array 类实例数组
     */
    public function load($name, $namespace, $alias = '', $arguments = '')
    {
        global $instances;

        $name = ucfirst($name);
        //$namespace=ucwords(str_replace('/', '\\', $namespace),'\\');
        $namespace = str_replace('/', '\\', $namespace);

        $handler = $alias ? $alias : $name;

        //实例已存在直接返回
        if (isset($instances[$namespace][$handler])) {
            return $instances[$namespace][$handler];
        }

        $class = $namespace . '\\' . $name;

        if ('System\Database' == $namespace) {

            $config = include APP_DIR . 'config/' . ENVIRONMENT . '/database' . EXT;
            if (!isset($config[$alias])) {
                exit("{$name} '{$alias}' Config Not Exist,Please Check Database Config File In '" . ENVIRONMENT . "' Directory.");
            }
         
            $class=$namespace.'\\'.$config[$alias]['driver'].'\\Db';

            $arguments = $config[$alias];
        } else if ('System\Cache' == $namespace) {

            $config = include APP_DIR . 'config/' . ENVIRONMENT . '/redis' . EXT;
            if (!isset($config[$alias])) {
                exit("{$name} '{$alias}' Config Not Exist,Please Check Redis Config File In '" . ENVIRONMENT . "' Directory.");
            }

            $arguments = $config[$alias];
        }

        //实例化并返回
        $instances[$namespace][$handler] = new $class($arguments);

        return $instances[$namespace][$handler];
    }

    /**
     * 覆盖__get
     * 
     * @param string $name 类名
     * 
     * @return object
     */
    public function __get($name)
    {
        if (in_array($name, array('input', 'config', 'output', 'session', 'cookie', 'lang', 'helper'))) {
            return $this->load($name, 'System');
        }

        if ($name == 'vars') {
            global $vars;
            return $vars;
        }

        if ($name == 'db') {
            return $this->load('database', 'System\\Database', 'default');
        }

        if ($name == 'redis') {
            return $this->load('redis', 'System\\Cache', 'default');
        }
    }

    /**
     * 通过方法返回数据库连接实例
     * 
     * @param string $service
     * 
     * @return object
     */
    public function db($service)
    {
        return $this->load('Db', 'System\\Database', $service);
    }

    /**
     * 调用model
     * 
     * @param string $name
     * 
     * @return object
     */
    public function model($name)
    {
        return $this->load(str_replace('/', '\\', $name), 'Model');
    }

    /**
     * 调用redis
     * 
     * @param string $service
     * 
     * @return object
     */
    public function redis($service)
    {
        return $this->load('redis', 'System\Cache', $service);
    }

    /**
     * 加载语言包
     * 
     * @param string $lang
     * 
     * @return object
     */
    public function lang($lang)
    {
        return $this->load('lang', 'System', 'lang_' . $lang, $lang);
    }

}
