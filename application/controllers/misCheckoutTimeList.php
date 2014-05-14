<?php
/**
 * 业务系统,当班结算时间列表
 *
 * @author: yangzhen
 * @date: 2013-12-02
 */
class MisCheckoutTimeList extends BLL_Controller
{
	function __construct()
    {
        parent::__construct();
    }

	/**
     * 当班结算时间列表
     *
     * @return response
     */
	function startInit_get()
	{
		$data = array();
		//收银台当班结算时间限制表
		$cashierLimit = $this->rest_client->post('cashier/getList',array('data'=>array('order'=>'cashier_id')));
		if(!(isset($cashierLimit->status) && $cashierLimit->status == 2))
		{
			foreach($cashierLimit as $o)
			{
				if($o->cashier_type == 0)
				{
					$data[$o->cashier_id]['deskId'] = $o->cashier_id;//款台号
					$data[$o->cashier_id]['workday'] = $o->cashier_commit_time;//工作日结算时间
				}else
				{
					$data[$o->cashier_id]['deskId'] = $o->cashier_id;
					$data[$o->cashier_id]['holiday'] = $o->cashier_commit_time;//节假日结算时间
				}
			}
		}
		$this->response($data,200);
	}
}
