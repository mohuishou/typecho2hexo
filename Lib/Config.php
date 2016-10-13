<?php
/**
 * config设置
 */
namespace Mohuishou\Lib;
class Config
{
    protected $_configs;

    public static $_instance;

    private function __construct()
    {
        $this->_configs=require_once __DIR__."/../config.php";
        $db=$this->_configs["db"];
        $this->_configs['db']['dsn']="mysql:host=".$db["host"].";dbname=".$db["name"];
    }

    //创建__clone方法防止对象被复制克隆
    public function __clone(){
        trigger_error('Clone is not allow!',E_USER_ERROR);
    }

    //单例方法,用于访问实例的公共的静态方法
    public static function getInstance(){
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function get($a){
        return $this->_configs[$a];
    }


}
