<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 收银监管登录验证
 *
 * author:王同猛
 * date:2013-12-3
 */

class CashierMonitorValidate extends BLL_Controller
{
	/*收银组长与admin的id*/
	private $_cashierArr=array(40,186);	
	
	/**
     * 维护报错信息数据
     *
	 * @param int - msgType 报错类型
     * @return json - rest response data
     */
    public static function error($msgType)
    {
        $msg = array(
                1=>'admin table error',
                2=>'parameter username or passwd not found'
                );
        Util::errorMsg($msg[$msgType]);
    }
    
	/**
     * 收银监管登录验证
     *
     * @return json - 验证成功或错误信息
     */
	public function startInit_post()
    {
		$username = $this->post('username');
		$passwd = $this->post('passwd');
		if(!empty($username) && !empty($passwd))
		{
			$member = $this->rest_client->post('operater/getList',array('data'=>array('where'=>array('username'=>$username),'limit'=>1)));
			if(!empty($member) && empty($member->error))
			{
				$pw = $this->rest_client->post('operater/getList',array('data'=>array('where'=>array('username'=>$username,'passwd'=>md5($passwd)),'limit'=>1)));
				if(!empty($pw) && empty($pw->error) && !empty($pw[0]->rid))
				{
					if(in_array($pw[0]->rid,$this->_cashierArr))
					{
						$loginTime=time();
						$this->rest_client->put('operater/setItem',array('data'=>array('id'=>$pw[0]->userid,'data'=>array('last_login'=>$loginTime))));
						$this->response(array('status'=>1,'success'=>'process success','userid'=>$pw[0]->userid,'truename'=>$pw[0]->truename),200);
					}
					else
					{
						$this->response(array('status'=>4,'error'=>'admin table rid not cashier'),200);
					}
				}
				else if($pw->status==2)
				{
					$this->response(array('status'=>3,'error'=>'no record'),200);
				}
				else
				{
					self::error(1);
				}
			}
			else if($member->status==2)
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
