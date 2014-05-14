<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 帮助中心修改
 *
 * author:王同猛
 * date:2013-12-3
 */

class MisHelpCenterEdit extends BLL_Controller
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
                2=>'parameter id not found',
                3=>'Edit help_infomation table error',
                4=>'parameter id or data not found'
                );
        Util::errorMsg($msg[$msgType]);
    }
	
	/**
     * 帮助中心修改
     *
     * @return json - 修改成功或错误信息
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
	
	/**
     * 帮助中心修改
     *
     * @return json - 修改成功或错误信息
     */
	public function endSubmit_post()
    {
		$id = $this->post('id');
		$data = $this->post('data');
		if(!empty($id) && intval($id)>0 && !empty($data) && count($data)>0)
		{
			$articleEdit = $this->rest_client->put('helpPaymentWord/setItem',array('data'=>array('id'=>intval($id),'data'=>$data)));
			if(!empty($articleEdit) && $articleEdit->status==1)
			{
				$this->response(array('status'=>1,'success'=>'process success'),200);
			}
			else
			{
				self::error(3);
			}
		}
		else
		{
			self::error(4);
		}
	}

}
