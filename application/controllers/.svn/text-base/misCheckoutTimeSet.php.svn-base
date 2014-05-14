<?php
/**
 * 业务系统,当班结算时间设置
 *
 * @author: yangzhen
 * @date: 2013-12-02
 */
class MisCheckoutTimeSet extends BLL_Controller
{
	private $result = array();//存放各种支付方式的名称和钱数

	function __construct()
    {
        parent::__construct();
    }

	/**
     * 当班结算时间设置
     *
     * @return response
     */
	function startInit_get()
	{
		$data = array();
		//款台号
		$deskId = $this->get('deskId');
		if(empty($deskId))
		{
			$this->response(array('status'=>0,'error'=>'获取款台号失败'),500);
		}
		$data['deskId'] = $deskId;
		$data['id'] = '';//主键id
		$data['workday'] = '';//工作日结算时间
		$data['holiday'] = '';//节假日结算时间
		//收银台当班结算时间限制表
		$cashierLimit = $this->rest_client->post('cashier/getList',array('data'=>array('where'=>array('cashier_id'=>$deskId))));
		if(!(isset($cashierLimit->status) && $cashierLimit->status == 2))
		{
			foreach($cashierLimit as $o)
			{
				if($o->cashier_type == 0)
				{
					$data['id'] = $o->id;
					$data['workday'] = $o->cashier_commit_time;
				}else
				{
					$data['id'] = $o->id;
					$data['holiday'] = $o->cashier_commit_time;
				}
			}
		}
		$this->response($data,200);
	}

	/**
     * 当班结算时间设置
     *
     * @return response
     */
	function endSubmit_put()
	{
		//款台号
		$deskId = $this->put('deskId');
		if(empty($deskId))
		{
			$this->response(array('status'=>0,'error'=>'获取款台号失败'),500);
		}
		//工作日
		$day[0] = $this->put('workday');//cashier_type==0
		//节假日
		$day[1] = $this->put('holiday');//cashier_type==1
		for($i=0;$i<2;$i++)
		{
			//查询收银台当班结算时间限制表
			$cashierLimit = $this->rest_client->post('cashier/getList',array('data'=>array('where'=>array('cashier_id'=>$deskId,'cashier_type'=>$i),'order'=>'id desc','limit'=>1)));
			if(isset($cashierLimit->status) && $cashierLimit->status == 2)
			{
				//新增一条记录
				$cashierInsert = $this->rest_client->post('cashier/addItem',array('data'=>array('cashier_id'=>$deskId,'cashier_type'=>$i,'cashier_commit_time'=>$day[$i])));
				if(isset($cashierInsert->status) && $cashierInsert->status == 0)
				{
					$this->response(array('status'=>0,'error'=>'收银台当班结算时间限制添加失败'),500);
				}
			}else
			{
				//更新一条记录
				$cashierUpdate = $this->rest_client->put('cashier/setItem',array('data'=>array('id'=>$cashierLimit[0]->id,'data'=>array('cashier_commit_time'=>$day[$i]))));
				if(isset($cashierInsert->status) && $cashierInsert->status == 0)
				{
					$this->response(array('status'=>0,'error'=>'收银台当班结算时间限制更新失败'),500);
				}
			}
		}
		$this->response(array('status'=>1),200);
	}
}
