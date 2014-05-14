<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 帮助中心详情页面
 *
 * author:王同猛
 * date:2013-12-3
 */

class MisHelpCenterDetail extends BLL_Controller
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
                1=>'Read help_infomation table error',
                2=>'parameter id not found'
                );
        Util::errorMsg($msg[$msgType]);
    }
	
	/**
     * 帮助中心详情数据显示
     *
     * @return json - 详情页面数据或错误信息
     */
	public function startInit_get()
	{
		$id = $this->get('id');
		if(!empty($id) && intval($id)>0)
		{
			$articleRead = $this->rest_client->get('helpPaymentWord/getItem',array('id'=>intval($id)));
			if(!empty($articleRead) && empty($articleRead->error))
			{
				$this->response($articleRead);
			}
			else
			{
				self::error(1);
			}
		}
		else
		{
			self::error(2);
		}
	}

}
