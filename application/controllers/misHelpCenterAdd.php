<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 帮助中心添加
 *
 * author:王同猛
 * date:2013-12-3
 */

class MisHelpCenterAdd extends BLL_Controller
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
		$typeId = $this->post('type_id');/*分类id*/
		$title = $this->post('title');
		$content = $this->post('content');
		if(!empty($typeId) && !empty($title) && !empty($content))
		{
			$articleAdd = $this->rest_client->post('helpPaymentWord/addItem',array('data'=>array('title'=>$title,'content'=>$content,'type_id'=>$typeId)));
			if(!empty($articleAdd) && $articleAdd->status==1)
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
