<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *业务逻辑层 关于订单修改时需要删除的数据
 *
 *@author:hanshaobo
 *@date:2014-01-26
 */

class OrderDelete extends BLL_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->_sellNumber = $this->post('sellNumber');
		$this->_ljyunId = $this->common->getLjyunIdByContract($this->_sellNumber);
	}
	/**
     * 维护报错信息数据
     *
	 * @param int - msgType 报错类型
     * @return json - rest response data
     */
    public static function error($msgType)
    {
		$msg = array(
                1=>'OrderBasic delete failed!',
                2=>'OrderVirtual update failed!',
                3=>'OrderProduct delete failed!',
                4=>'OrderRealPromotionContract delete failed!',
                5=>'OrderHistory delete failed!',
                7=>'martApportion delete failed!',
                8=>'spending delete failed!',
                9=>'orderId get failed!',
                10=>'sellNumber get failed!',
                11=>'Payment has been made!',
				12=>'OrderSale delete failed!'
                );
        Util::errorMsg($msg[$msgType]);
    }
	/**
	 *
	 *订单修改删除数据借口
	 *
	 *@return array -status
	 */
	public function endSubmit_post()
    {
		$data = array();
		$orderId = intval($this->post('orderId'));
		if(empty($orderId))
		{
			self::error(9);exit;
		}
		if(empty($this->_sellNumber))
		{
			self::error(10);exit;
		}
		//判断合同是否已经付款	
		$isexist = $this->rest_client->post('contract/getListNum',array('where'=>array('consume_pact_id' => $this->_sellNumber)));
		if($isexist > 0)
		{
			self::error(11);exit;
		}
		//删除正式订单以及相关信息
		$bdata['ljyunId'] = $this->_ljyunId;
		$bdata['data']['base']['or_id'] = intval($orderId);
		$orderBasic = $this->rest_client->delete('orderBasic/delItem',$bdata);
		if($orderBasic->status != 1)
		{
			self::error(1);exit;
		}
		//删除虚拟商品表
		$orderVir = $this->rest_client->post('orderVirtual/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('or_id'=>$orderId))));
		if(!empty($orderVir) && !isset($orderVir->status))
		{
			foreach($orderVir as $key => $val)
			{
				$orderVirtual = $this->rest_client->put('orderVirtual/setItem',array("ljyunId"=>$this->_ljyunId,"data"=>array("id"=>$val->vir_id,"data"=>array("or_id"=>0))));
				if($orderVirtual->status != 1)
				{
					self::error(2);exit;
				}
			}
		}
		//删除正式订单商品表
		$orderPro = $this->rest_client->post('orderProduct/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('orp_tmp_id'=>$orderId))));
		if(!empty($orderPro) && !isset($orderPro->status))
		{
			foreach($orderPro as $key => $val)
			{
				$pdata['ljyunId'] = $this->_ljyunId;
				$pdata['id'] = $val->orp_id;
				$orderProduct = $this->rest_client->delete('orderProduct/delItem',$pdata);
				if($orderProduct->status != 1)
				{
					self::error(3);exit;
				}
			}
		}
		//删除合同与商户促销关系表
		$orderCon = $this->rest_client->post('orderRealPromotionContract/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('orsc_real_id'=>$orderId))));
		if(!empty($orderCon) && !isset($orderCon->status))
		{
			foreach($orderCon as $key => $val)
			{
				$cdata['ljyunId'] = $this->_ljyunId;
				$cdata['id'] = $val->orsc_id;
				$orderContract = $this->rest_client->delete('orderRealPromotionContract/delItem',$cdata);
				if($orderContracti->status !=1)
				{
					self::error(4);exit;
				}
			}	
		}
		//删除历史交款记录表
		$orderHis = $this->rest_client->post('orderHistory/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('order_real_id'=>$orderId))));
		if(!empty($orderHis) && !isset($orderHis->status))
		{
			foreach($orderHis as $key => $val)
			{
				$hdata['ljyunId'] = $this->_ljyunId;
				$hdata['id'] = $val->history_pay_id;
				$orderHistory = $this->rest_client->delete('orderHistory/delItem',$hdata);
				if($orderHistory->status != 1)
				{
					self::error(5);exit;
				}
			}
		}
		//删除卖场促销活动会员卡分摊记录
		$martApp = $this->rest_client->post('martApportion/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('app_order_id'=>$orderId))));
		if(!empty($martApp) && !isset($martApp->status))
		{
			foreach($martApp as $key => $val)
			{
				$mdata['ljyunId'] = $this->_ljyunId;
				$mdata['id'] = $val->app_id;
				$martApportion = $this->rest_client->delete('martApportion/delItem',$mdata);
				if($martApportion->status !=1 )
				{
					self::error(7);exit;
				}
			}
		}
		//删除扣款明细表
		$conNumber = $this->rest_client->post('contractNumber/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('con_id'=>$this->_sellNumber))));
		if(!empty($conNumber) && !isset($conNumber->status))
		{
			foreach($conNumber as $k =>$v)
			{
				$spend = $this->rest_client->post('spending/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('deduct_cause_id'=>$v->pact_id))));
				if(!empty($spend) && !isset($spend->status))
				{
					foreach($spend as $key => $val)
					{
						$sdata['ljyunId'] = $this->_ljyunId;
						$sdata['id'] = $val->id;
						$spending = $this->rest_client->delete('spending/delItem',$sdata);
						if($spending->status !=1 )
						{
							self::error(8);exit;
						}
					}
				}
			}
		}

		/*
		* 合同与商户促销关系表sale_contract 删除数据
		*
		* author: yangzhen
		* date: 2014-2-8
		*/
		$orderSale = $this->rest_client->post('orderSale/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('order_real_id'=>$orderId))));
		if(!empty($orderSale) && !isset($orderSale->status))
		{
			$odata['ljyunId'] = $this->_ljyunId;
			$odata['id'] = $orderSale[0]->order_real_id;
			$orderSaleInfo = $this->rest_client->delete('orderSale/delItem',$odata);
			if($orderSaleInfo < 1 )
			{
				self::error(12);exit;
			}
		}	
		$this->response(array('status'=>1,'success'=>'process success'),200);
	}

}
