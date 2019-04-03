<?php
/** 日志助手类 */
namespace Helper;
use Component\SingleTon;
class Log{
    use SingleTon;
	private static  $info;  //log配置信息

	protected function __construct(){
		self::$info = config('log');
	}
	/**
	 *写日志方法
	 *@param $string 需要写入的信息
	 *@return void
	 */
	public function write($string){
		$fp = fopen(self::$info['log_path'],'a');
		fwrite($fp,$string);
		fclose($fp);
	}
    
    /**
     *错误日志写方法
     *@param $string string 需要记录的错误信息
     *@return void
     */
	public function  error($string){
		$fp = fopen(self::$info['error_path'],'a');
		fwrite($fp,$string);
		fclose($fp);
	}

    /**
     * SQL查询记录记录方法
     * @param $string
     * @return void
     * @author chenlin
     * @date 2019/4/1
     */
	public function record($string){
	    $fp = fopen(self::$info['db_record_path'],'a');
	    fwrite($fp,$string);
	    fclose($fp);
    }
}