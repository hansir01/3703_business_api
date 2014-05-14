<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * demo : 为了使便于调试，需要在每个BLL调用DAL
 * 或逻辑判断处返回报错信息
 *
 * @author:zg
 * @date:2013-12-08
 */

class errorMsg extends BLL_Controller implements BLL_REQUIRE
{
    function index_get()
    {
        var_dump(self::error(1));
    }

	/**
     * 维护报错信息数据，这个方法需要在每个BLL的
     * controller中定义
     *
	 * @param int - msgType 报错类型
     * @return json - rest response data
     */
    public static function error($msgType)
    {
        $msg = array(
                1=>'查询数据出错',
                2=>'合同主表生成数据出错',
                3=>'现金与购物券表生成数据失败',
                4=>'银行卡数据生成错误',
                5=>'福卡数据生成错误',
                6=>'支票数据生成错误',
                7=>'第三方卡数据生成错误'
                );
        Util::errorMsg($msg[$msgType]);
    }


}
