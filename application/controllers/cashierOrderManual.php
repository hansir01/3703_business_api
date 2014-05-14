<?php
/**
 * 收银系统,手工收款（紧急预案）
 *
 * @author: yangzhen
 * @date: 2013-12-02
 */
class CashierOrderManual extends BLL_Controller
{
	function __construct()
    {
        parent::__construct();
    }

	/**
     * 手工收款
     *
     * @return response
     */
	function endSubmit_post()
	{
		//款台id
		$deskId = intval($this->post('deskId'));
		if(empty($deskId))
		{
			$this->response(array('status'=>0,'error'=>'款台id丢失'),500);
		}
		//收银员id
		$userId = intval($this->post('userId'));
		if(empty($userId))
		{
			$this->response(array('status'=>0,'error'=>'收银员id丢失'),500);
		}
		//合同号
		$contract = $this->post('contract');
		if(empty($contract) || !is_numeric($contract) || strlen($contract) != 9)
		{
			$this->response(array('status'=>0,'error'=>'合同号格式错误'),500);
		}
		//手机号
		$phone = $this->post('phone');
		if(empty($phone) || !is_numeric($phone) || strlen($phone) != 11)
		{
			$this->response(array('status'=>0,'error'=>'手机号格式错误'),500);
		}
		//展位号
		$show1 = $this->post('show1');
		$show2 = $this->post('show2');
		$show3 = $this->post('show3');
		if(empty($show1) || empty($show2) || empty($show3))
		{
			$this->response(array('status'=>0,'error'=>'获取展位号失败'),500);
		}
		$show = $show1.'-'.$show2.'-'.$show3;
		//合同额
		$pactPrice = $this->post('pactPrice');
		if(empty($pactPrice) || !is_numeric($pactPrice))
		{
			$this->response(array('status'=>0,'error'=>'合同额格式错误'),500);
		}
		//应付额
		$fectPrice = $this->post('fectPrice');
		if(empty($fectPrice) || !is_numeric($fectPrice))
		{
			$this->response(array('status'=>0,'error'=>'应付额格式错误'),500);
		}
		//测量日期
		$measure = $this->post('measure');
		//安装日期
		$install = $this->post('install');
		$manualInsert = $this->rest_client->post('manual/addItem',array('data'=>array('pact_id'=>$contract,'phone'=>$phone,'booth'=>$show,'pact_value'=>$pactPrice,'payable_value'=>$fectPrice,'measure_date'=>$measure,'install_date'=>$install,'add_time'=>date('Y-m-d H:i:s'),'operator_id'=>$userId,'desk_id'=>$deskId)));
		if(isset($manualInsert->status) && $manualInsert->status == 0)
		{
			$this->response(array('status'=>0,'error'=>'收银台当班结算时间限制添加失败'),500);
		}
		$this->response(array('status'=>1),200);
	}
}
