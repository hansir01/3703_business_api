<?php
/*************************************************************************
> File Name: tool_helper.php
> Author: arkulo
> Mail: arkulo@163.com 
> Created Time: 2013年12月08日 星期日 09时54分40秒
*************************************************************************/
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 将response返回的数组转化为对象，如果遇到下标为0～9的数字，则
 * 转换为sub0~sub9的形式
 *
 * @param [array] 准备返回的数组
 * @return [stdClass] 转化后的对象
 * @author arkulo@163.com
 */
if (!function_exists('_arrayToObject'))
{
    function _arrayToObject($array) 
    {
        if (!is_array($array)) {
            return $array;
        } 
        $object = new stdClass();
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $name=>$value) {
                if(is_int($name)) $name = "sub".$name;
                if (!empty($name)) {
                    $object->$name = _arrayToObject($value);
                }
            }
            return $object;
        }
        else {
            return FALSE;
        }
    }
}


/**
 * 流水号补零操作
 *
 * @return [string]
 */
if (!function_exists('padLeft'))
{
    function padLeft($str,$lenght,$para)
    {   
        if(strlen($str) >= $lenght)
        {
            return $str;
        }else
        {
            return padLeft($para.$str,$lenght,$para);
        }
    }
}



/**
 * 手续费进位函数 小数点后第三位向第二位四舍五入
 *
 * @return 
 */
if (!function_exists('floatsub'))
{
    function floatSub($value)
    {
        $value = $value*1000;
        $value = floor($value);
        $tmp = substr($value,-1);
        if($tmp>=5)
        {
            $value = $value+10;
        }
        $value = substr($value,0,-1);
        $value = $value/100;
        return $value;	
    }
}


/**
 * 小数点后第三位舍掉，如:100.999 经过本函数处理后100.99
 * @param float
 * @author: Liangxifeng 2014-01-09
 *
 * @return  float
 */
if(!function_exists('floatOff'))
{
    function floatOff($value)
    {
        $valArr = explode('.',$value);
        if(isset($valArr[1]) && !empty($valArr[1]))
        {
            $value = $valArr[0].'.'.substr($valArr[1],0,2);
        }
        return sprintf("%.2f", $value);;
    }
}
    
