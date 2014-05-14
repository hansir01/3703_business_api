<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 帮助中心添加
 *
 * author:王同猛
 * date:2013-12-3
 */

class OrderDelete extends BLL_Controller
{
	/**
     * 维护报错信息数据
     *
	 * @param int - msgType 报错类型
     * @return json - rest response data
     */
    public static function error($msgType)
    {
        $msg = array(
                1=>'Insert help_infomation table error',
                2=>'parameter type_id or title or content not found'
                );
        Util::errorMsg($msg[$msgType]);
    }
	
	/**
     * 帮助中心添加
     *
     * @return json - 添加成功或错误信息
     */
	public function endSubmit_post()
    {
		$this->response(1);
	}

}
