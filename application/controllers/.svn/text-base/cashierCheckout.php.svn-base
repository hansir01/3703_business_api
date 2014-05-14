<?php
/**
 * 收银系统,当班结算
 *
 * @author: yangzhen
 * @date: 2013-11-28
 */
class CashierCheckout extends BLL_Controller
{
	/**
     * 当班结算初始信息
     *
     * @return response
     */
    public function startInit_get()
    {
		//款台id
		$deskId = intval($this->get('deskId'));
		if(empty($deskId))
		{
			$this->response(array('status'=>0,'error'=>'款台id丢失'),500);
		}
		//收银员id
		$userId = intval($this->get('userId'));
		if(empty($userId))
		{
			$this->response(array('status'=>0,'error'=>'收银员id丢失'),500);
		}
		//当班是否存在未提交消费记录
		$contractInfo = $this->rest_client->post('contract/getList',array('data'=>array('where'=>array('consume_operator_id'=>$userId,'consume_client_id'=>$deskId,'consume_state'=>1),'limit'=>1,'field'=>array('consume_id'))));
		//content = 1 表示存在未提交记录
		$data['content'] = 1;
		if(isset($contractInfo->status) && $contractInfo->status == 2)
		{
			$data['content'] = 0;
		}
		//当班提交结账单表
		$operaterFormInfo = $this->rest_client->post('operatorForm/getList',array('data'=>array('where'=>array('operator_operator_id'=>$userId,'operator_client_id'=>$deskId,'operator_state'=>0),'order'=>'operator_id desc','limit'=>1,'field'=>array('operator_chip'))));
		if(isset($operaterFormInfo->status) && $operaterFormInfo->status == 2)
		{
			$data['content'] = 0;
		}
		//当班结算时间限制
		$cashierInfo = $this->rest_client->post('cashier/getList',array('data'=>array('where'=>array('cashier_id'=>$deskId),'order'=>'id desc','field'=>array('cashier_commit_time','cashier_type'))));
		if(isset($cashierInfo->status))
		{
			$this->response(array('status'=>0,'error'=>'当班结算时间限制表无数据'),500);
		}
		$data['holiday'] = '';
		$data['workday'] = '';
		foreach($cashierInfo as $o)
		{
			if($o->cashier_type ==0)
			{
				//工作日提交时间
				$data['workday'] = $o->cashier_commit_time;
			}else
			{
				//节假日提交时间
				$data['holiday'] = $o->cashier_commit_time;
			}
		}
		//时间限制标示符，1为可以结算
		$data['limit'] = 0;
		//星期
		$state = date('w') > 5 ? $data['holiday'] : $data['workday'];
		$data['state'] = $cashierInfo;
		if($state < date('H:i:s'))
		{
			//$data['limit'] = $state.'--'.date('H:i:s');
			$data['limit'] = 1;
		}
		$checkNum = '';
		if($data['content'] == 1)
		{
			//操作人员姓名
			$operaterInfo = $this->rest_client->get('operater/getItem',array('id'=>$userId,'field'=>array('username')));
			//字段补全操作人员id，4位字符串。如：0001
			$userNum = str_pad($operaterFormInfo[0]->operator_chip,4,'0',STR_PAD_LEFT);
			//结账单号
			$checkNum = date("ymd")."_".$userNum."_".$operaterInfo->username;
		}
		$data['checkNum'] = $checkNum;
		$this->response($data,200);
    }

	/**
     * 当班结算更新状态
     *
     * @return response
     */
	function endSubmit_put()
	{
		//款台id
		$deskId = intval($this->put('deskId'));
		if(empty($deskId))
		{
			$this->response(array('status'=>0,'error'=>'款台id丢失'),500);
		}
		//收银员id
		$userId = intval($this->put('userId'));
		if(empty($userId))
		{
			$this->response(array('status'=>0,'error'=>'收银员id丢失'),500);
		}
		//获取合同号 consume_state=1 表示当班未提交
		$contractInfo = $this->rest_client->post('contract/getList',array('data'=>array('where'=>array('consume_operator_id'=>$userId,'consume_client_id'=>$deskId,'consume_state'=>1),'field'=>array('consume_id'))));
		if(isset($contractInfo->status))
		{
			$this->response(array('status'=>0,'error'=>'获取合同号信息失败'),500);
		}
		//当班提交结账单表
		$operaterFormInfo = $this->rest_client->post('operatorForm/getList',array('data'=>array('where'=>array('operator_operator_id'=>$userId,'operator_client_id'=>$deskId,'operator_state'=>0),'order'=>'operator_id desc','limit'=>1,'field'=>array('operator_id'))));
		if(isset($operaterFormInfo->status))
		{
			$this->response(array('status'=>0,'error'=>'获取当班提交结账单表信息失败'),500);
		}
		
		foreach($contractInfo as $v)
		{
			$contractUpd = $this->rest_client->put('contract/setItem',array('data'=>array('id'=>$v->consume_id,'data'=>array('consume_state'=>2))));
			if(isset($contractUpd->status) && $contractUpd->status != 1)
			{
				$this->response(array('status'=>0,'error'=>"{$v->consume_id}号合同状态更新失败，请联系技术支持"));
			}
		}			
		$operaterFormUpd = $this->rest_client->put('operatorForm/setItem',array('data'=>array('id'=>$operaterFormInfo[0]->operator_id,'data'=>array('operator_state'=>1))));	
		if(isset($operaterFormUpd->status) && $operaterFormUpd->status == 1)
		{
			$this->response(array('status'=>1),200);
		}else
		{
			$this->response(array('status'=>0,'error'=>'当班提交单号更新出错'),500);
		}
	}
}
