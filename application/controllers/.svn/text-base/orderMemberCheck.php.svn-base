<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * discription:获得会员基础信息
 *
 * author:王同猛
 * date:2013-11-21
 */

class OrderMemberCheck extends BLL_Controller
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
                1=>'Read m_member_information error',
                2=>'parameter phone not found'
                );
        Util::errorMsg($msg[$msgType]);
    }
	
	/**
     * 会员基础信息查询
     *
	 * @param GET方式传参
	 * @param string - phone 电话号码
     * @return json - 会员基础信息或错误信息
     */
	public function startInit_get()
    {
        $phone = $this->get('phone');
		if(!empty($phone))
		{
			$res = $this->rest_client->post('memberInfo/getList',array('data'=>array('base'=>array('where'=>array('member_phone'=>$phone),'limit'=>1))));
			if(!empty($res) && empty($res->error) && count($res[0])>0)
			{
				$this->response($res[0]);
			}
			else if($res->status==2)
			{
				$this->response(array('status'=>2,'error'=>'no record'),200);
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
