<?php
/**
 * 下单系统,会员卡/卖场促销计算
 *
 * @author: yangzhen
 * @date: 2013-11-19
 */
class OrderCardAndPlazaSaleCalculate extends BLL_Controller
{
    function __construct()
	{
        parent::__construct();
        //获取蓝景编号
		$this->_ljyunId = $this->common->getLjyunIdByForm("ljyunId");
	}

	/**
     * 会员卡卖场促销折扣计算
     *
     * @return response
     */
    public function endSubmit_get()
    {
		//获取订单id
        $id = $this->get('id');
		if(empty($id))
		{
			$this->response(array('status'=>0,'error'=>'获取订单id失败'),500);
		}
        $this->load->library('OrderDeduction');
		//mart：卖场折扣
		$handle = $this->orderdeduction->getHandle('mart');
		$msg = $handle->payment($id);
		if($msg !== true && $msg !== 'success')
		{
			$this->response(array('status'=>0,'error'=>$msg),500);
		}
		//member：会员卡折扣
		if($msg !== 'success')
		{
			$handle = $this->orderdeduction->getHandle('member');
			$msg = $handle->payment($id);
			if($msg !== true)
			{
				$this->response(array('status'=>0,'error'=>$msg),500);
			}
		}
		$this->response(array('status'=>1),200);
    }
}
