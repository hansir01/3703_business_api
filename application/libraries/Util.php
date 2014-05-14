<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 通用的函数
 *
 * 函数列表: 
 * 1. 格式化货币数字
 * 2. 得到钱的中文大写
 * 3. 处理打折计算出的小数点
 * 4. 返回BLL报错信息
 *
 * @author: zg
 * @date: 2013-11-30
 */

class Util
{
    //全部定义的是static方法，所以不会调用构造方法
    function __construct() { }
    
    /**
     * 格式化货币数字
     *
     * @param float - a double float number
     * @return mixed - string/false
     */
    public static function formatMoney($money)
    {
        if( !is_numeric($money) )
        {
            return false;
        }
        return number_format($money, 2);
    }

    /**
     * 得到钱的中文大写
     *
     * @param float - a double float number
     * @return mixed - string/false
     */
    public static function cnMoney($num)
    {
        $c1 = '零壹贰叁肆伍陆柒捌玖';
        $c2 = '分角元拾佰仟万拾佰仟亿';
        $num = round($num, 2);
        $num = $num * 100;
        if (strlen($num) > 10) {
            return 'in the func cnMoney : the number is too long!';
        } 
        $i = 0;
        $c = '';
        while (1) {
            if ($i == 0) {
                $n = substr($num, strlen($num)-1, 1);
            } else {
                $n = $num % 10;
            } 

            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);

            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            } 

            $i = $i + 1;
            $num = $num / 10;
            $num = (int)$num;
            if ($num == 0) {
                break;
            } 
        } //end of while| here, we got a chinese string with some useless character
        $j = 0;

        $slen = strlen($c);
        while ($j < $slen) {
            $m = substr($c, $j, 6);
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j-3;
                $slen = $slen-3;
            } 

            $j = $j + 3;
        } 

        if (substr($c, strlen($c)-3, 3) == '零') {
            $c = substr($c, 0, strlen($c)-3);
        } // if there is a '0' on the end , chop it out
        return $c . "整";
    }

    /**
     * 处理打折计算出的小数点
     *
     * @param float - a double float number
     * @return mixed - string/false
     */
    public static function dealDecimal($number)
    {
        return round(floatval($number));
    }

    /**
     * 返回错误提示
     *
     * @param string - error message
     * @return json - response string
     */
    public static function errorMsg($msg, $responseData='')
    {
        if( !empty($responseData) ) $responseData = '<br/>responseData : ' . $responseData;
        $errorMsg = $msg . $responseData;
        get_instance()->response(array('status'=>0, 'error'=>$errorMsg), 200);
    }

}

