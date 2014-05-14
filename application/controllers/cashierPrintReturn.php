<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 业务逻辑层，退货凭证打印
 *
 * @author: wxh
 * @date: 2013-11-27
 */

class CashierPrintReturn extends BLL_Controller {

	function __construct()
	{
		parent::__construct();
	}

	/**
     * 退货凭证打印的对外接口
     *
	 * @param string - $contractNum合同号
     * @return array -$data/error 
     */
	public function startInit_get()
	{
		$contractNum = '';
		$productValue = 0;
		$contractNum = $this->get("contractNumber");//获取合同号
		if(!empty($contractNum))
		{
			//取退货信息
			$chargeInfo = $this->rest_client->post("chargeBack/getList",array("data"=>array("where"=>array("back_pact_number"=>$contractNum))));
			if(isset($chargeInfo->status) && $chargeInfo->status == 2)
			{
				$this->response(array("status"=>2,"error"=>"No record"),200);
			}else
			{
				$chargeInfo[0]->back_negotiates = Util::formatMoney($chargeInfo[0]->back_negotiate);
				$chargeInfo[0]->back_charges = Util::formatMoney($chargeInfo[0]->back_charge);
				//取订单信息
				$orderInfo = $this->rest_client->post("orderBasic/getList",array("data"=>array("base"=>array("where"=>array("or_sale_number"=>$contractNum),"field"=>array("or_delivery_address","or_customer_name","or_customer_phone","or_customer_idcard","or_addtime","or_merchant_id",'or_store_tel')),"other_attr"=>array("field"=>'--'),"attr"=>array("field"=>'--'))));
				if(!isset($orderInfo->status))
				{
					//取商户信息
					$field = array("lease"=>array("field"=>array("A_name","A_dute_tel","con_merchant_id","con_resource")),"base"=>array("field"=>'merchant_kid'),"platform"=>array("field"=>'lj_kid'));
					$merchantInfo = $this->rest_client->get("merchantInfo/getItem",array("id"=>$orderInfo[0]->base->or_merchant_id,"field"=>$field));
					if(!isset($merchantInfo->status))
					{
						//如果是单品退货，则去读取退货商品表
						if($chargeInfo[0]->back_type == 1)
						{
							//读取退货的商品信息
							$chargeProduct = $this->rest_client->post("chargeBackProduct/getList",array("data"=>array("where"=>array("charge_back_id"=>$chargeInfo[0]->id))));
							if(isset($chargeProduct->status) && $chargeProduct->status == 2)
							{
								$this->response(array("status"=>2,"error"=>"No record"),200);
							}else
							{
								//算总计并取商品的名号规格等信息
								foreach($chargeProduct as $val)
								{
									$productValue += $val->product_price;
									$orderProduct = $this->rest_client->get("orderProduct/getItem",array("id"=>$val->order_real_product_id));
									if(isset($orderProduct->status) && $orderProduct->status == 2)
									{
										$this->response(array("status"=>2,"error"=>"No record"),200);
									}else
									{
										$orderProduct->product_fact_value = Util::formatMoney($val->product_price);
										$productInfo[] = $orderProduct;
									}
								}
							}
							$data = array("chargeInfo"=>$chargeInfo[0],"memberInfo"=>$orderInfo[0],"productInfo"=>$productInfo,"sum"=>Util::formatMoney($productValue),"merchantInfo"=>$merchantInfo);
							$this->response($data,200);

						}
					}else
					{
						$this->response(array("status"=>2,"error"=>"MerchantInfo:No record"),200);
					}
				}else
				{
					$this->response(array("status"=>2,"error"=>"OrderInfo:No record"),200);
				}
			}
		}else
		{
			$this->response(array("status"=>0,"error"=>"Bad param"),500);
		}
	}
}
?>
