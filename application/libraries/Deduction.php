<?php
/*************************************************************************
> File Name: Deduction.php
> Author: arkulo
> Mail: arkulo@163.com 
> Created Time: 2013年11月19日 星期二 16时19分10秒
*************************************************************************/
abstract class Deduction
{
    protected $CI;

    public function __construct()
    {
        $this->CI = & get_instance();
    }

    abstract function fullPayment();
    abstract function partPayment();
                
}
