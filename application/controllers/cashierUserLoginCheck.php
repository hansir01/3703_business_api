<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 用户登录验证
 *
 * author:王同猛
 * date:2013-12-2
 */

class CashierUserLoginCheck extends BLL_Controller
{
	/*收银员和收银组长id*/
	private $_cashierArr=array(40,186,188,14);	
	
	/**
     * 维护报错信息数据
     *
	 * @param int - msgType 报错类型
     * @return json - rest response data
     */
    public static function error($msgType)
    {
        $msg = array(
                1=>'banking_terminal error',
                2=>'role table error',
                3=>'admin table error',
                4=>'admin table error',
                5=>'parameter descmac or username or passwd not found'
                );
        Util::errorMsg($msg[$msgType]);
    }
    
	/**
     * 用户登录验证
     *
     * @return json - 款台号、真名或错误信息
     */
	public function endSubmit_post()
    {
		$deskmac = $this->post('deskmac');
		$username = $this->post('username');
		$passwd = $this->post('passwd');
		if(!empty($deskmac) && !empty($username) && !empty($passwd))
		{
			$member = $this->rest_client->post('operater/getList',array('data'=>array('where'=>array('username'=>$username),'limit'=>1)));
			if(!empty($member) && empty($member->error))
			{
				$pw = $this->rest_client->post('operater/getList',array('data'=>array('where'=>array('username'=>$username,'passwd'=>md5($passwd)),'limit'=>1)));
				if(!empty($pw) && empty($pw->error) && !empty($pw[0]->rid))
				{
					if(in_array($pw[0]->rid,$this->_cashierArr))
					{
						$deskNum = $this->rest_client->post('bankTerminal/getList',array('data'=>array('where'=>array('terminal_card'=>$deskmac))));
						if(!empty($deskNum) && empty($deskNum->error))
						{
							$loginTime=time();
							$this->rest_client->put('operater/setItem',array('data'=>array('id'=>$pw[0]->userid,'data'=>array('last_login'=>$loginTime))));
							$this->response(array('truename'=>$pw[0]->truename,'terminal_desk_id'=>$deskNum[0]->terminal_desk_id,'userid'=>$pw[0]->userid),200);
						}
						else
						{
							self::error(1);
						}
					}
					else if($pw->status==2)
					{
						$this->response(array('status'=>4,'error'=>'admin table rid not cashier'),200);
					}
					else
					{
						self::error(2);
					}
				}
				else if($pw->status==2)
				{
					$this->response(array('status'=>3,'error'=>'no record'),200);
				}
				else
				{
					self::error(3);
				}
			}
			else if($member->status==2)
			{
				$this->response(array('status'=>2,'error'=>'no record'),200);
			}
			else
			{
				self::error(4);
			}

		}
		else
		{
			self::error(5);
		}
	}

}
