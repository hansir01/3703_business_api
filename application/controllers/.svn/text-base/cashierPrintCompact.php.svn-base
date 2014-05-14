<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 合同打印页面
 *
 * author:wxh
 * date:2013-12-3
 */

class CashierPrintCompact extends BLL_Controller
{
	private $_contractNumber;//获取到的合同号
	private $_sNames=array('1'=>'整体优惠','2'=>'特价优惠','3'=>'套餐优惠','4'=>'阶梯优惠','5'=>'Vip','6'=>'会员卡','7'=>'卖场促销','8'=>"以旧换新");
	private $_salesName=array();	
	private $_getLadder;//获取阶梯优惠	
	private $_salesMember;//是否有会员卡折扣
	private $_fotnInfo;//是否有以旧换新
	private $_fotnInfoPhase;//是否有以旧换新第几期
	public function __construct()
	{
		parent::__construct();
		$this->_contractNumber = $this->get('contractNumber');
		/*获取蓝景编号*/
		$this->_ljyunId = $this->common->getLjyunIdByContract($this->_contractNumber);
	}

	/**
     * 查看合同详情内容
     *
     * @return array - 页面所需信息或错误信息
     */
	public function startInit_get()
	{
		$data=array();
		if(!empty($this->_contractNumber) && is_numeric($this->_contractNumber))
		{
			/*消费记录*/
			$consume = $this->_getConume($this->_contractNumber);
            $payment = array(); 
			if(!empty($consume) && $consume != false)
			{
				/*取交易方式*/
				$consume[0]->consume_fact_big_value = Util::cnMoney($consume[0]->consume_fact_value);
				$consume[0]->consume_fact_values = Util::formatMoney($consume[0]->consume_fact_value);
				$payment=$this->_getPayWay($consume[0]->consume_id);
				!empty($payment) ? $consume[0]->payments=$payment : $consume[0]->payments='';
				$data['consume']=$consume[0];
				/*订单信息*/
				$orderData = $this->rest_client->post('orderBasic/getList',array('ljyunId'=>$this->_ljyunId, 'data'=>array('base'=>array('where'=>array('or_sale_number'=>$this->_contractNumber),'limit'=>1))));
				if(!empty($orderData) && empty($orderData->error) && count($orderData[0]->base)>0 && !empty($orderData[0]->base->or_pact_value) && !empty($orderData[0]->base->or_payable_value) && !empty($orderData[0]->base->or_addtime) && !empty($orderData[0]->base->or_customer_phone) && !empty($orderData[0]->base->or_merchant_id))
				{
					$card = $this->_cardType($orderData[0]->base->or_customer_idcard_type);	
					$orderData[0]->base->or_customer_idcard_type = $card;
					if($orderData[0]->base->or_get_self == 1)
					{
						$orderData[0]->base->or_get_self = "顾客自提";//1为自提
					}else
					{
						$orderData[0]->base->or_get_self = "商户配送";
					}
					$data['order']	= $orderData[0];
					/*判断有没有阶梯优惠*/
					if($orderData[0]->base->or_contract_sale_value != 0.00 && $orderData[0]->base->or_contract_sale_price != 0.00)
					{
						$ladderInfo = $this->rest_client->post('orderRealPromotionContract/getList',array('ljyunId'=>$this->_ljyunId, "data"=>array("where"=>array("orsc_real_id"=>$orderData[0]->base->or_id))));
						if(!isset($ladderInfo->status))
						{
							$this->_getLadder = $ladderInfo[0]->orsc_sale_number;
						}
					}
					//判断有没有以旧换新并得出第几期
					$this->_fotnInfoPhase = $this->_getFotnPhase($orderData[0]->base->or_id);//是否有以旧换新
					$this->_salesMember = $this->_getMartPromotion($orderData[0]->base->or_id,1);//是否有会员卡打折
					$this->_martPromotion = $this->_getMartPromotion($orderData[0]->base->or_id,2);//是否有卖场促销
					/*取虚拟商品*/	
					$virtual = $this->_getVirtual($orderData[0]->base->or_id);
					if(!empty($virtual) && empty($virtual->error) && count($virtual)>0)
					{
						$data['virtual'] = $virtual;
						$priceSum = 0 ;	
						foreach($virtual as &$val)
						{
							$product=array();	
							$product = $this->_getGoods($val->vir_id);
							if(!empty($product) && empty($product->error) && count($product)>0)
							{
								foreach($product as $value)
								{
									/*取商品信息*/
									if($value->orp_pro_type==1)
									{
										/*检查是否是以旧换新*/
										$isFotn = $this->_getFotn($value->orp_tmp_id,$value->orp_model);
										if(!empty($isFotn) && empty($isFotn->error))
										{
											$value->applyGiveOld = 1;
											$this->_fotnInfo = 1;	
										}
									}
									$value->orp_pro_price = Util::formatMoney($value->orp_pro_price);//商品单价转换
									$value->orp_pro_sale_price = Util::formatMoney($value->orp_pro_sale_price);//商品促销单价转换
									$value->orp_total_prices = Util::formatMoney($value->orp_total_price);//商品总价转换
									$value->orp_total_sale_prices = Util::formatMoney($value->orp_total_sale_price);//促销商品总价
									empty($value->sale_number) ? $priceSum +=$value->orp_total_price : $priceSum +=$value->orp_total_sale_price;
									/*促销编号*/
									$this->_getSaleName($value);
								}
								$data['priceSum'] = Util::formatMoney($priceSum);
								$data['product'][$val->vir_id]=$product;
							}
							else
							{
								$this->response(array('status'=>2,'error'=>'Order product:No record'),200);
							}
						}
					}
					else
					{
						$this->response(array('status'=>2,'error'=>'Virtual:No record'),200);
					}
					/*促销编号*/
					foreach($this->_sNames as $key=>$vala)
					{
						if(empty($this->_salesName[$key]))
						{
							$salesNames[$key]='';
						}
						else
						{
							$salesNames[$key]=$this->_salesName[$key];
						}
					}
					$data['orderSale']=$salesNames;
					/*商户信息*/
					//$shop = $this->_getShop($orderData[0]->base->or_merchant_id);
                    //Liangxifeng modify 使用展位号获取商户信息
					$shop = $this->_getShop($consume[0]->consume_merchant_show);
					if(!empty($shop) && empty($shop->error) && count($shop)>0)
					{
						$data['merchant']=$shop[0];
					}
					else
					{
						$this->response(array('status'=>2,'error'=>'MerchantInfo:No record'),200);
					}
					if(!empty($consume[0]->consume_member_id))
					{
                        //liangxifeng modify 为了暂时解决前缀少0问题，手动拼接0在首位
                        $consume[0]->consume_member_id = '0'.$consume[0]->consume_member_id; 
						/*取会员信息*/
						$member = $this->_getMember($consume[0]->consume_member_id);
						if(!empty($member) && empty($member->error) && !empty($member[0]->attr->consume_grade))
						{
							/*取会员等级*/
							if($member[0]->attr->consume_grade == 1)
							{
								$member[0]->attr->consume_grade = "普通会员";
							}elseif($member[0]->attr->consume_grade == 2)
							{
								$member[0]->attr->consume_grade = "银卡会员";
							}else
							{
								$member[0]->attr->consume_grade = "金卡会员";
							}
							//weixiaohua2014-1-10修改会员卡积分部分
							$member[0]->attr->consume_period = 0; 
							if(isset($payment['bank']))
							{
								$member[0]->attr->consume_period += intval($payment['bank']['nowPrice']);//本期积分
							}
							if(isset($payment['cash']))
							{
								$member[0]->attr->consume_period += intval($payment['cash']['nowPrice']);//本期积分
							}
							if($member[0]->attr->consume_integral != 0)
							{
								$member[0]->attr->consume_integral_sum = intval($member[0]->attr->consume_integral)-intval($member[0]->attr->consume_period);//原有积分
							}else
							{
								$member[0]->attr->consume_integral_sum = 0;
							}
							$data['member'] = $member[0];
						}
						else
						{
							$this->response(array('status'=>2,'error'=>'MemberInfo:No record'),200);
						}
					}
					$this->response($data);
				}
				else
				{
					$this->response(array('status'=>0,'error'=>'Order table lack the necessary data'),500);
				}
			}
			else
			{
				$this->response(array('status'=>2,'error'=>'ProductConume:No record'),200);
			}
		}
		else
		{
			$this->response(array('status'=>0,'error'=>'Bad prama'),500);
		}
	}
	
	/**
	 * 商品促销编号
	 *
     * param array - value 订单商品数据
     * @return json - 商户信息或错误信息
     */
	private function _getSaleName($value)
	{
		if(!empty($value->sale_number))
		{
			$salesArr=explode('-',$value->sale_number);
			if($salesArr[1]=='ZT')
			{
				$this->_salesName[1]='整体优惠第'.$salesArr[3].'期';
				$value->orp_sale.='1、';
			}
			else if($salesArr[1]='TJ')
			{
				$this->_salesName[2]='特价优惠';	
				$value->orp_sale.='2、';
			}
			else if($salesArr[1]='TC')
			{
				$this->_salesName[3]='套餐优惠';	
				$value->orp_sale.='3、';
			}
		}
		if(!empty($this->_getLadder))
		{
			$saleLadder=explode('-',$this->_getLadder);
			$this->_salesName[4]='阶梯优惠第'.$saleLadder[3].'期';	
			$value->orp_sale.='4、';
		}
		if(!empty($value->orp_vip_price))
		{
			$this->_salesName[5]='Vip';
			$value->orp_sale.='5、';
		}
		if(!empty($this->_salesMember))
		{
			$this->_salesName[6]='会员卡';
			$value->orp_sale.='6、';	
		}
		if(!empty($this->_martPromotion))
		{
			$this->_salesName[7]='卖场促销';
			$value->orp_sale.='7、';
		}
		if(!empty($this->_fotnInfo))
		{
			$this->_salesName[8]='以旧换新第'.$this->_fotnInfoPhase.'期';
			$value->orp_sale.='8、';
		}
		$value->orp_sale = !empty($value->orp_sale) ? trim($value->orp_sale,'、') : '';
	}

	/**
	 * 付款记录数据显示
	 * 
	 * @param string - contractNumber 合同号
	 * @return json - 合同号信息或错误信息
	 */
	private function _getConume($contractNumber)
	{
		$conume = $this->rest_client->post('contract/getList',array('data'=>array('where'=>array('consume_pact_id'=>$contractNumber),'limit'=>1,'order'=>'consume_id desc')));
		if(!isset($conume->status) && count($conume)>0)
		{
			return $conume;
		}else
		{
			return false;
		}
	}

	/**
	 * 支付方式数据显示
	 * 
	 * @param string - id product_conume消费记录表主键id
	 * @return json - 支付方式信息或错误信息
	 */
	private function _getPayWay($id)
	{
		$payWay = array();
		/*现金和购物卷方式*/
		$infoCash = $this->rest_client->post('cashCoupon/getList',array('data'=>array('where'=>array('payform_consume_id'=>$id))));
		if(!isset($infoCash->status))
		{
			if($infoCash[0]->payform_cash != '' && $infoCash[0]->payform_cash != '0.00')
			{
				$payWay['cash']['bigPrice'] = Util::cnMoney($infoCash[0]->payform_cash);
				$payWay['cash']['price'] = Util::formatMoney($infoCash[0]->payform_cash);
				$payWay['cash']['nowPrice'] = $infoCash[0]->payform_cash;
				$payWay['cash']['name'] = '现金';
			}
			if($infoCash[0]->payform_trade != '' && $infoCash[0]->payform_trade != '0.00')
			{
				$payWay['trade'] = array();
				$payWay['trade']['bigPrice'] = Util::cnMoney($infoCash[0]->payform_trade);
				$payWay['trade']['price'] = Util::formatMoney($infoCash[0]->payform_trade);
				$payWay['trade']['name'] = '购物卷';
			}
		}
		/*银行卡*/
		$bank = $this->rest_client->post('bankCard/getList',array('data'=>array('where'=>array('consume_id'=>$id))));
		if(!isset($bank->status))
		{
			$payWay['bank'] = array();
			$payWay['bank']['price'] = 0;
			foreach($bank as $bankVal)
			{
				$payWay['bank']['price'] += $bankVal->value;	
			}
			if(!empty($payWay['bank']))
			{
				$payWay['bank']['bigPrice'] = Util::cnMoney($payWay['bank']['price']);
				$payWay['bank']['nowPrice'] = $payWay['bank']['price'];	
				$payWay['bank']['price'] = Util::formatMoney($payWay['bank']['price']);	
				$payWay['bank']['name'] = '银行卡';	
			}
		}
		/*支票*/
		$cheque = $this->rest_client->post('check/getList',array('data'=>array('where'=>array('cheque_consume_id'=>$id))));
		if(!isset($cheque->status))
		{
			$payWay['cheque'] = array() ;
			$payWay['cheque']['price'] = 0;
			foreach($cheque as $chequeVal)
			{
				$payWay['cheque']['price'] += $chequeVal->cheque_value;
			}
			if(!empty($payWay['cheque']))
			{
				$payWay['cheque']['bigPrice'] = Util::cnMoney($payWay['cheque']['price']);	
				$payWay['cheque']['price'] = Util::formatMoney($payWay['cheque']['price']);
				$payWay['cheque']['name'] = '支票';
			}
		}
		/*福卡*/
		$fuCard = $this->rest_client->post('fCard/getList',array('data'=>array('where'=>array('consume_id'=>$id))));
		if(!isset($fuCard->status))
		{
			$payWay['fu_card'] = array();
			$payWay['fu_card']['price'] = 0;
			foreach($fuCard as $fuVal)
			{
				$payWay['fu_card']['price'] += $fuVal->value;
			}
			if(!empty($payWay['fu_card']))
			{
				$payWay['fu_card']['bigPrice'] = Util::cnMoney($payWay['fu_card']['price']);	
				$payWay['fu_card']['price'] = Util::formatMoney($payWay['fu_card']['price']);
				$payWay['fu_card']['name'] = '福卡';
			}
		}
		/*其他卡*/
		$otherCard = $this->rest_client->post('additionalCard/getList',array('data'=>array('where'=>array('way_fid'=>$id))));
		if(!isset($otherCard->status))
		{
			$payWay['otherCard'] = array(); 
			$payWay['otherCard']['price'] = 0;
			foreach($otherCard as $otherVal)
			{
				$payWay['otherCard']['price'] += $otherVal->way_value; 
			}
			if(!empty($payWay['otherCard']))
			{
				$payWay['otherCard']['bigPrice'] = Util::cnMoney($payWay['otherCard']['price']);	
				$payWay['otherCard']['price'] = Util::formatMoney($payWay['otherCard']['price']); 
				$payWay['otherCard']['name'] = '其他';
			}
		}
		return $payWay;
			
	}
	
	/**
     * 是否有卖场促销数据显示
     *
	 * @param string - orId 商户云编号
	 * @param int - type 促销类型 1为会员卡 2为卖场促销
     * @return  - 是否有商户促销或布尔false
     */
	private function _getMartPromotion($orId,$type)
	{
		$martPromotion = $this->rest_client->post('martApportion/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array("where"=>array("app_order_id"=>$orId,"app_type"=>intval($type)))));
		if(!isset($martPromotion->status) && count($martPromotion)>0)
		{
			return 1;
		}else
		{
			return false;
		}
	}

	/**
     * 订单确认页面商户数据显示
     *
	 * @param string - mid 商户云编号
     * @return json - 商户信息或错误信息
     */
	private function _getShop($mid)
	{
        //Liangxifeng modify getItem无法取出数据，所以在此改为getList
		//$merchant = $this->rest_client->get('merchantInfo/getItem',array('ljyunId'=>$this->_ljyunId,  'id'=>$mid));
		$merchant = $this->rest_client->post('merchantInfo/getList',array('data'=>array('lease'=>array('where'=>array('con_resource'=>$mid, 'contract_state'=>1), 'limit'=>1, 'order'=>'contract_cid desc'))));
		if(!empty($merchant) && count($merchant)>0)
		{
			return $merchant;
		}else
		{
			return false;
		}
	}

	/**
     * 订单虚拟商品数据显示
     *
	 * @param string - orderRealId 商户云编号
     * @return json - 虚拟商品信息或错误信息
     */
	private function _getVirtual($orderRealId)
	{
		$merchant = $this->rest_client->post('orderVirtual/getList',array('ljyunId'=>$this->_ljyunId,  'data'=>array('where'=>array('or_id'=>$orderRealId))));
		if(!empty($merchant) && count($merchant)>0)
		{
			return $merchant;
		}else
		{
			return false;
		}
	}

	/**
     * 订单商品明细数据显示
     *
	 * @param string - virId 商户云编号
     * @return json - 商品信息或错误信息
     */
	private function _getGoods($virId)
	{
		$goods = $this->rest_client->post('orderProduct/getList',array('ljyunId'=>$this->_ljyunId,  'data'=>array('where'=>array('vir_id'=>$virId))));
		if(!empty($goods) && count($goods)>0)
		{
			return $goods;
		}else
		{
			return false;
		}
	}

	/**
     * 标准商品是否是以旧换新数据
     *
	 * @param string - id 商品主键id
     * @return json - 商品报价信息或错误信息
     */

	private function _getFotn($orderId,$gModel)
	{
		$fotnReal = $this->rest_client->post('oldnewReal/getList',array('data'=>array('where'=>array('record_real_order_id'=>$orderId),'limit'=>1)));
        //无记录的情况
        if(isset($fotnReal->status))
        {
            if(2 == $fotnReal->status)
            {
                return false; 
            }else
            {
                $this->response(array('error'=>'get fotn_new_deal_real failed!'));
            }
        }
        $fotnPro = $this->rest_client->post('oldnewRealProduct/getListNum',array('where'=>array('fdp_record_id'=>$fotnReal[0]->record_id,'fdp_prod_model'=>$gModel)));
        if($fotnPro>0)
        {
            return true;
        }
        else
        {
            return false;
        }
	}
	
	/**
     * 得到以旧换新期数
     *
	 * @param string - id 订单主键id
     * @return int - 以旧换新期数/错误信息
     */

	private function _getFotnPhase($orderId)
	{
		$fotnReal = $this->rest_client->post('oldnewReal/getList',array('data'=>array('where'=>array('record_real_order_id'=>$orderId),'limit'=>1)));
        //无记录的情况
        if(isset($fotnReal->status))
        {
            if(2 == $fotnReal->status)
            {
                return false; 
            }else
            {
                $this->response(array('status'=>2, 'error'=>'get fotn_new_deal_real failed!'));
            }
        }
        $fotnPhase = $this->rest_client->get('oldnewSet/getItem',array('id'=>$fotnReal[0]->record_cap_id));
        if(isset($fotnPhase->status))
        {
            return false;
        }
        else
        {
            return $fotnPhase->cap_no;
        }
	}
	/**
     * 会员数据显示
     *
	 * @param string - $member_fid 顾客手机号
     * @return json - 会员数据或错误信息
     */
	private function _getMember($member_fid)
	{
		$member = $this->rest_client->post('memberInfo/getList',array('data'=>array('base'=>array('where'=>array('member_card_fid'=>$member_fid),'limit'=>1))));
		if(!empty($member) && count($member)>0)
		{
			return $member;
		}else
		{
			return false;
		}
	}
	//证件类型的转换
	private function _cardType($type)
	{
		switch (intval($type))
		{
		case 0:case 1:
			$cardType = '身份证';
			break;
		case 2:
			$cardType = '军官证';
			break;
		case 3:
			$cardType = '北京暂住证';
			break;
		case 4:
			$cardType = '北京居住证';
			break;
		case 5:
			$cardType = '港奥华侨';
			break;
		}
		return $cardType;
	}

}
?>
