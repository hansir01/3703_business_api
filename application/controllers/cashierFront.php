<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 收银前台页面
 *
 * author:王同猛
 * date:2013-12-4
 */

class CashierFront extends BLL_Controller
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
                1=>'sys_message no data',
                2=>'font_new_return_voucher no data',
                3=>'parameter deskid not found'
                );
        Util::errorMsg($msg[$msgType]);
    }
	
	/**
     * 凭证号数量查询
     *
     * @return json - 是否小于50条或错误信息
     */
	public function endSubmit_get()
    {
		$deskId = $this->get('deskId');
		if(!empty($deskId))
		{
			$vNum = $this->rest_client->post('oldnewVoucher/getListNum',array('where'=>array('voucher_desk_id'=>$deskId,'voucher_status'=>0)));
			if(!empty($vNum) && empty($vNum->error))
			{
				if($vNum<50)
				{
					$sys = $this->rest_client->post('sysMessage/getList',array('data'=>array('where'=>array('ms_type'=>1))));
					if(!empty($sys) && empty($sys->error))
					{
						$this->response(array('status'=>1,'notice'=>$sys[0]->ms_content),200);
					}
					else
					{
						self::error(1);
					}
				}
				else
				{
					$this->response(array('status'=>1,'success'=>'process success'),200);
				}
			}
			else if($member->status==2)
			{	
				$sys = $this->rest_client->post('sysMessage/getList',array('data'=>array('where'=>array('ms_type'=>1))));
				if(!empty($sys) && empty($sys))
				{
					$this->response(array('status'=>1,'notice'=>$sys[0]->ms_content),200);
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
		else
		{
			self::error(3);
		}
	}

}
