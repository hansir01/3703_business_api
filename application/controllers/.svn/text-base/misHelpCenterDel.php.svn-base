<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 帮助中心文章删除
 *
 * author:王同猛
 * date:2013-12-4
 */

class misHelpCenterDel extends BLL_Controller
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
                1=>'Delete help_infomation table error',
                2=>'parameter id not found'
                );
        Util::errorMsg($msg[$msgType]);
    }
	
	/**
     * 帮助中心文章删除
     *
     * @return json - 删除成功或错误信息
     */
	public function endSubmit_delete()
    {
		$id = $this->delete('id');
		if(!empty($id) && intval($id)>0)
		{
			$articleDel = $this->rest_client->delete('helpPaymentWord/delItem',array('id'=>intval($id)));
			if(!empty($articleDel) && $articleDel->status==1)
			{
				$this->response(array('status'=>1,'success'=>'process success'),200);
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
