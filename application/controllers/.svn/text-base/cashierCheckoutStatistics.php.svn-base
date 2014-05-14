<?php
/**
 * 收银系统,当班结算
 *
 * @author: yangzhen
 * @date: 2013-11-27
 */
class CashierCheckoutStatistics extends BLL_Controller
{
	private $result = array();//存放各种支付方式的数量和总钱数
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
		//操作人员姓名
		$operaterInfo = $this->rest_client->get('operater/getItem',array('id'=>$userId,'field'=>array('truename')));
		//获取合同号 consume_state=1 表示当班未提交
		$contractInfo = $this->rest_client->post('contract/getList',array('data'=>array('where'=>array('consume_operator_id'=>$userId,'consume_client_id'=>$deskId,'consume_state'=>1),'field'=>array('consume_id'))));		
		//获取支付方式名称
		$payTypes = $this->rest_client->post('paymentType/getList',array('data'=>array('field'=>array('pay_name','s_name'))));
		foreach($payTypes as $p)
		{
			$payName[$p->s_name] = $p->pay_name;
		}
		if(!isset($contractInfo->status))
		{
			foreach($contractInfo as $v)
			{
				//消费记录表主键
				$consumeId = $v->consume_id;
				//现金
				$cash = $this->rest_client->post('cashCoupon/getList',array('data'=>array('where'=>array('payform_cash >'=>0,'payform_consume_id'=>$consumeId),'field'=>array('payform_cash as value'))));
				$this->_check($cash,$payName['cash']);
				//支票
				$trade = $this->rest_client->post('check/getList',array('data'=>array('where'=>array('cheque_value >'=>0,'cheque_consume_id'=>$consumeId),'field'=>array('cheque_value as value'))));
				$this->_check($trade,$payName['cheque']);
				//银行卡
				$bank = $this->rest_client->post('bankCard/getList',array('data'=>array('where'=>array('value >'=>0,'consume_id'=>$consumeId),'field'=>array('value'))));
				$this->_check($bank,$payName['bank']);
				//福卡
				$fCard = $this->rest_client->post('fCard/getList',array('data'=>array('where'=>array('value >'=>0,'consume_id'=>$consumeId),'field'=>array('value'))));
				$this->_check($fCard,$payName['fu_card']);
				//附加卡
				$addCard = $this->rest_client->post('additionalCard/getList',array('data'=>array('where'=>array('way_value >'=>0,'way_fid'=>$consumeId),'field'=>array('way_value as value','way_type'))));
				if(!isset($addCard->status))
				{
					foreach($addCard as $add)
					{
						$data = array();
						//附加卡名称
						$addCardName = $this->rest_client->get('blessPaymentType/getItem',array('id'=>$add->way_type,'field'=>array('type_name')));
						//将一条附加卡记录变为二维数组才能调用check方法
						$data[] = $add;
						$this->_check($data,$addCardName->type_name);
					}
				}
			}
		}
		$msg['name'] = $operaterInfo->truename;
		$msg['time'] = date('Y-m-d');
		foreach($this->result as $k=>$r)
		{
			$this->result[$k]['price'] = Util::formatMoney($r['price']);
		}
		$msg['main'] = $this->result;
		$this->response($msg,200);
    }

	/**
     * 计算一种支付方式的数量和总钱数
     *
     * @param item 支付信息
	 * @param name 支付方式名称
     * @return array 数组
     */
	private function _check($item,$name)
	{
		$sum = 0;//总记录数
		$price = 0;//总钱数
		$data = array();
		if(!isset($item->status))
		{
			if(!isset($this->result[$name]['sum']))
			{
				$this->result[$name]['sum'] = 0;
				$this->result[$name]['price'] = 0;
			}
			foreach($item as $o)
			{
				$sum ++;
				$price += $o->value;
			}
            if('现金' == $name)
            {
                $this->result[$name]['sum'] = 'casher' ;
            }else
            {
                $this->result[$name]['sum'] += $sum;
            }
			$this->result[$name]['price'] += $price;
		}
	}
}
