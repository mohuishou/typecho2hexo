<?php
/**
 * Created by PhpStorm.
 * User: lxl
 * Date: 16-10-13
 * Time: 上午9:07
 */
namespace Mohuishou\Lib;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Attachment{

    protected $filename;
    protected $_config;
    protected $_uploadMgr;
    protected $_token;

    /**
     * Attachment constructor.
     */
    public function __construct()
    {
        $this->_config=Config::getInstance();
    }

    /**
     * @param $filename
     * @param $content
     * @return mixed
     */
    public function save($filename,$content){
        //匹配链接地址
        $pattern="/!\[.*\]\(([a-zA-z]+:\/\/[^\s]*\/([^\s]*\.[^\s]*)).*\)/";
        preg_match_all($pattern,$content,$res);
        $pattern2="/\[\d\]:\s([a-zA-z]+:\/\/[^\s]*\/([^\s]*\.[^\s]*))/";
        preg_match_all($pattern2,$content,$res2);
        $res_all[0]=array_merge($res[1],$res2[1]);
        $res_all[1]=array_merge($res[2],$res2[2]);
        if(empty($res_all[0][0])) return $content;

        $type=$this->_config->get("attachment")["type"];
        if($type=="qiniu"){
            $this->initQiniu();
            $content=$this->saveQiniu($res_all,$content);
        }else{
            $content=$this->saveFile($res_all,$content,$filename);
        }
        return $content;

    }

    /**
     * @param $res
     * @param $content
     * @param $filename
     * @return mixed
     */
    protected function saveFile($res,$content,$filename){
        foreach ($res[0] as $key=> $value){
            //下载图片
            $this->download($value,$res[1][$key],__DIR__."/../FILE/".$filename);
            //替换图片链接
            $content=str_replace($value,$res[1][$key],$content);
        }
        return $content;
    }

    /**
     * @param $res
     * @param $content
     * @return mixed
     */
    public function saveQiniu($res,$content){
        $domain=$this->_config->get("qiniu")["domain"];
        foreach ($res[0] as $key=> $value){
            //下载图片
            $path=$this->download($value,$res[1][$key],__DIR__."/../FILE/tmp");
            $key="blog/old/".$res[1][$key];
            list($ret, $err) = $this->_uploadMgr->putFile($this->_token, $key, $path);
            if($err!==null){
                continue;
            }
            $link=$domain."/".$key;
            //替换图片链接
            $content=str_replace($value,$link,$content);
        }
        return $content;
    }

    /**
     *
     */
    protected function initQiniu(){
        $qiniu=$this->_config->get("qiniu");
        $auth=new Auth($qiniu['access_key'],$qiniu["secret_key"]);
        $this->_token=$auth->uploadToken($qiniu["bucket_name"]);
        // 初始化 UploadManager 对象并进行文件的上传
        $this->_uploadMgr = new UploadManager();
    }

    /**
     * @param $url
     * @param $filename
     * @param $dir
     * @return string
     */
    protected function download($url,$filename,$dir){
        $path=$dir."/".$filename;
        $ch=curl_init();
        $timeout=3;
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        $res=curl_exec($ch);
        curl_close($ch);
        //检查文件夹是否存在
        if(!file_exists($dir)) mkdir($dir);
        file_put_contents($path,$res);
        return $path;
    }
}