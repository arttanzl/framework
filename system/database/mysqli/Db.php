<?php

namespace System\Database\Mysqli;

/**
 * 数据操作类
 * 
 * @author Ding <beyondye@gmail.com>
 */
class Db
{

    /**
     * 保存数据库连接句柄
     * 
     * @var object
     */
    private $db = null;

    /**
     * 数据库配置信息
     * 
     * @var array
     */
    public $config = [];

    public function __get($name)
    {
        //返回最新插入id
        if ($name == 'insert_id') {
            return $this->db->insert_id;
        }

        //返回影响行数
        if ($name == 'affected_rows') {
            return $this->db->affected_rows;
        }
    }

    /**
     * 构造函数
     * 
     * @param array $config 连接参数数组
     */
    public function __construct($config = [])
    {
        //$config = array('host' => '', 'username' => '', 'password' => '', 'database' => '', 'port' => '', 'charset' => '');
        $this->config = $config;

        $this->db = new \mysqli($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);

        if ($this->db->connect_errno) {
            exit('Database Connection Error :' . $this->db->connect_error);
        }

        $this->db->set_charset($config['charset']);
    }

    /**
     * sql查询
     * 
     * @param string $sql
     * 
     * @return \System\Database\Result
     */
    public function query($sql)
    {
        $result = $this->db->query($sql);

        // var_dump($sql);
        if ($this->db->errno) {
            exit('Database Error : [' . $sql . '] ' . $this->db->error . ' [Code:' . $this->db->errno . ']');
        }

        if ($result === true) {
            return true;
        }

        return new Result($result);
    }

    /**
     * 转义字符串
     * 
     * @param string|array $string
     * 
     * @return string|array
     */
    public function escape($string)
    {
        if (is_array($string)) {
            foreach ($string as $key => $value) {
                $string[$key] = $this->escape($value);
            }
            return $string;
        }

        $string = $this->db->real_escape_string($string);

        return $string;
    }

    /**
     * 添加数据到数据库
     * 
     * @param string $table 表名
     * @param array $data 数组键值对应
     * 
     * @return boolean
     */
    public function insert($table, $data)
    {
        $data = $this->escape($data);

        $keys = implode('`,`', array_keys($data));
        $values = implode("','", array_values($data));

        $sql = "INSERT INTO {$table}(`{$keys}`) VALUES('{$values}')";

        return $this->query($sql);
    }

    /**
     * 更新数据
     * 
     * @param string $table
     * @param array $data
     * @param array|string $where
     * 
     * @return boolean
     */
    public function update($table, $data, $where = [])
    {
        if (is_array($data)) {

            $data = $this->escape($data);
            foreach ($data as $key => $value) {
                $set[] = " `{$key}`='{$value}' ";
            }

            $sql = "UPDATE {$table} SET " . implode(',', $set) . $this->sqlWhere($where);
        } else {
            $sql = "UPDATE {$table} {$data} " . $this->sqlWhere($where);
        }

        return $this->query($sql);
    }

    /**
     *  replace
     * 
     * @param string $table
     * @param array $data
     * 
     * @return boolean
     */
    public function replace($table, $data)
    {
        $data = $this->escape($data);

        $keys = implode('`,`', array_keys($data));
        $values = implode("','", array_values($data));

        $sql = "REPLACE INTO {$table}(`{$keys}`) VALUES('{$values}')";
        return $this->query($sql);
    }

    /**
     * 删除数据
     * 
     * @param string $table
     * @param array|string $where
     * 
     * @return boolean
     */
    public function delete($table, $where = [])
    {
        $sql = "DELETE FROM " . $table . $this->sqlwhere($where);
        return $this->query($sql);
    }

    /**
     * 查询数据
     * 
     * @param string $table
     * @param array $condition=['where' => [], 'fields' => [], 'orderby' => [], 'limit' => []]
     * @param int|array $limit
     * 
     * @return object array
     */
    public function select($table, $condtion = [])
    {
        $default = ['where' => [], 'fields' => [], 'orderby' => [], 'limit' => []];
        $condtion = array_merge($default, $condtion);

        $sql = "SELECT {$this->sqlField($condtion['fields'])} FROM {$table} {$this->sqlwhere($condtion['where'])} {$this->sqlOrderBy($condtion['orderby'])} {$this->sqlLimit($condtion['limit'])} ";
        return $this->query($sql);
    }

    /**
     * 关闭连接
     * 
     * @return void
     */
    public function close()
    {
        $this->db->close();
    }

    /**
     * 构造 sql where 字符串
     * 
     * @param array $where
     * 
     * @return string
     */
    private function sqlWhere($where = [])
    {

        if (is_string($where) && trim($where) != '') {
            return " WHERE " . $where;
        }

        $sql = '';
        if (is_array($where) && count($where) > 0) {
            $where = $this->escape($where);
            $sql .= " WHERE ";
            $i = 0;
            foreach ($where as $key => $value) {
                $compare = $this->sqlCompare($key, $value);
                $sql .= ($i == 0 ? $compare : " AND {$compare} ");
                $i++;
            }
        }

        return $sql;
    }

    /**
     * sql比较运算符号解析
     * 
     * @param string $key
     * 
     * @return string 
     */
    private function sqlCompare($key, $value)
    {
        $key = trim($key);

        if (strpos($key, '>=')) {
            $key = str_replace('>=', '', $key);
            return " `{$key}` >= '{$value}' ";
        }

        if (strpos($key, '<=')) {
            $key = str_replace('<=', '', $key);
            return " `{$key}` <= '{$value}' ";
        }

        if (strpos($key, '!=')) {
            $key = str_replace('!=', '', $key);
            return " `{$key}` != '{$value}' ";
        }

        if (strpos($key, '<>')) {
            $key = str_replace('<>', '', $key);
            return " `{$key}` <> '{$value}' ";
        }

        if (strpos($key, '>')) {
            $key = str_replace('>', '', $key);
            return " `{$key}` > '{$value}' ";
        }

        if (strpos($key, '<')) {
            $key = str_replace('<', '', $key);
            return " `{$key}` < '{$value}' ";
        }

        if (strpos($key, ' like')) {
            $key = str_replace(' like', '', $key);
            return " `{$key}` like '{$value}' ";
        }

        if (strpos($key, ' in')) {
            $key = str_replace(' in', '', $key);
            return " `{$key}` in ({$value}) ";
        }

        return " `{$key}` = '{$value}' ";
    }

    /**
     * 构造sql select字段
     * 
     * @param array $fields
     * 
     * @return string
     */
    private function sqlField($fields)
    {
        if (is_string($fields) && trim($fields) != '') {
            return $fields;
        }

        $sql = ' * ';
        if (is_array($fields) && count($fields) > 0) {
            $sql = " ";
            $i = 0;
            foreach ($fields as $value) {
                $sql .= ($i == 0 ? " `$value` " : " , `$value` ");
                $i++;
            }
        }

        return $sql;
    }

    /**
     * 构造sql order by
     * 
     * @param array|string $fields
     * 
     * @return string
     */
    private function sqlOrderBy($fields)
    {
        if (is_string($fields) && trim($fields) != '') {
            return " ORDER BY " . $fields;
        }

        $sql = '';
        if (is_array($fields) && count($fields) > 0) {
            $sql .= " ORDER BY ";
            $i = 0;
            foreach ($fields as $key => $value) {
                $sql .= ($i == 0 ? " `$key` $value " : " , `$key` $value ");
                $i++;
            }
        }

        return $sql;
    }

    /**
     * 构造sql limit
     * 
     * @param init|array $offset
     * 
     * @return string
     */
    private function sqlLimit($offset)
    {
        if (!$offset) {
            return '';
        }

        if (is_int($offset)) {
            return " LIMIT $offset ";
        }

        if (is_array($offset)) {
            return " LIMIT {$offset[0]},{$offset[1]} ";
        }

        return '';
    }

}
