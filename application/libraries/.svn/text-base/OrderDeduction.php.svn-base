<?php
/*************************************************************************
> File Name: OrderDeduction.php
> Author: arkulo
> Mail: arkulo@163.com 
> Created Time: 2013年11月19日 星期二 16时54分48秒
*************************************************************************/

class OrderDeduction 
{
    private $dictTarget = array(
                "member" => "MemberDeduction",
				"mart"   => "MartDeduction"
            );
    public function getHandle($target)
    {
        require($this->dictTarget[$target].".php");
        return new $this->dictTarget[$target]();
    }
}
