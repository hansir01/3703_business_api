<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 收银系统订单确认
 *
 * author:王同猛
 * date:2013-11-26
 */

class CashierOrderConfirm extends BLL_Controller
{
	private $_contractNumber;
	private $_salesNumber;
	/*促销编号对应的名称*/
	private $_sName=array('ZT'=>'整体优惠','TJ'=>'特价','TC'=>'套餐','JT'=>'阶梯');
	/*所有促销编号*/
	private $_sNames=array('1'=>'整体优惠','2'=>'特价','3'=>'套餐','4'=>'阶梯','5'=>'Vip','6'=>'会员卡','7'=>'卖场促销','8'=>'以旧换新');
	private $_salesFotn;
	private $_salesMart;
	/*一天等于的秒数*/
	private $_oneDay=86400;
	private $_salesName=array();
	public function __construct()
	{
		parent::__construct();
		$this->_contractNumber = $this->get('contractNumber');
		/*获取蓝景编号*/
		$this->_ljyunId = $this->common->getLjyunIdByContract($this->_contractNumber);
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
                1=>'Order has been overdue',
                2=>'Order_real_product no data',
                3=>'Virtual table no data',
                4=>'merchant_info table no record',
                5=>'Order table lack the necessary data',
                6=>'Parameter contractNumber can not use',
                7=>'Parameter contractNumber is not found or illegal',
                8=>'Get fotn_new_deal_real table error'
                );
        Util::errorMsg($msg[$msgType]);
    }

	/**
     * 订单确认页面数据显示
     *
     * @return json - 订单确认页面所需信息或错误信息
     */
	public function startInit_get()
    {
		$data=array();
		if(!empty($this->_contractNumber) && is_numeric($this->_contractNumber) )//&& strlen($this->_contractNumber)==8
		{
            //判断是否收款
            $consume = $this->_getConsume($this->_contractNumber); 
            if('noRecord'!=$consume)
            {
                $this->response(array('status'=>1, 'error'=>'The order has been consume'));  
            }
			/*合同号有效*/
			$contract=$this->rest_client->post('contractNumber/getList',array('data'=>array('where'=>array('state'=>1,'con_id'=>$this->_contractNumber),'limit'=>1)));
			if(!empty($contract) && empty($contract->error) && !empty($contract[0]->merchant_id))
			{
				/*订单信息，有效期为一天*/
				$orderData = $this->rest_client->post('orderBasic/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('base'=>array('where'=>array('or_sale_number'=>$this->_contractNumber),'limit'=>1))));
				if(!empty($orderData) && empty($orderData->error) && count($orderData[0]->base)>0 && !empty($orderData[0]->base->or_pact_value) && !empty($orderData[0]->base->or_payable_value) && !empty($orderData[0]->base->or_addtime) && !empty($orderData[0]->base->or_customer_phone) && !empty($orderData[0]->base->or_merchant_id))
				{	
					if(!empty($orderData[0]->other_attr) && empty($orderData[0]->other_attr->error))
					{
						$orderData[0]->other_attr->other_transport_price=Util::formatMoney($orderData[0]->other_attr->other_transport_price);
						$orderData[0]->other_attr->other_install_price=Util::formatMoney($orderData[0]->other_attr->other_install_price);
						$orderData[0]->other_attr->other_material_price=Util::formatMoney($orderData[0]->other_attr->other_material_price);
					}
					$orderData[0]->base->or_payable_points=$orderData[0]->base->or_payable_value;/*本期积分*/	
					$orderData[0]->base->or_other_value=Util::formatMoney($orderData[0]->base->or_other_value);/*另行支付金额*/	
					$orderData[0]->base->or_payable_value=Util::formatMoney($orderData[0]->base->or_payable_value);
					$orderData[0]->base->or_pact_value=Util::formatMoney($orderData[0]->base->or_pact_value);
					$orderData[0]->base->or_product_value=Util::formatMoney($orderData[0]->base->or_product_value);
					$orderData[0]->base->or_product_sale_value=Util::formatMoney($orderData[0]->base->or_product_sale_value);
					$data['order']	= $orderData[0];
					if(time()-strtotime($orderData[0]->base->or_addtime)>$this->_oneDay)
					{
						self::error(1);
					}
					else
					{
						$this->_salesMember=$this->_getSaleMember($orderData[0]->base->or_id);
						/*验证是否是卖场促销*/
						$this->_salesMart=$this->_getMart($orderData[0]->base->or_id);
						/*取虚拟商品*/	
						$virtual = $this->_getVirtual($orderData[0]->base->or_id);
						if(!empty($virtual) && empty($virtual->error) && count($virtual)>0)
						{
							foreach($virtual as &$val)
							{
								$val->vir_price=Util::formatMoney($val->vir_price);
								$product=array();	
								$product = $this->_getGoods($val->vir_id);
								if(!empty($product) && empty($product->error) && count($product)>0)
								{
									foreach($product as &$value)
									{
										$value->orp_pro_price=Util::formatMoney($value->orp_pro_price);
										$value->orp_pro_sale_price=Util::formatMoney($value->orp_pro_sale_price);
										$value->orp_total_sale_price=Util::formatMoney($value->orp_total_sale_price);
										$value->orp_total_price=Util::formatMoney($value->orp_total_price);
										/*取商品信息*/
										if($value->orp_pro_type==1)
										{
											$isFotn = $this->_getFotn($orderData[0]->base->or_id,$value->orp_model);
											if(!empty($isFotn) && empty($isFotn->error))
											{
												$this->_salesFotn=1;
											}
										}
										/*促销编号*/
										$this->_getSaleName($value);
									}
									$data['product'][$val->vir_id]=$product;
								}
								else
								{
									self::error(2);
								}
							}
							$data['virtual'] = $virtual;	
						}
						else
						{
							self::error(3);
						}
						/*取促销编号*/
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
						$shop = $this->_getShop($orderData[0]->base->or_merchant_id);
						if(!empty($shop) && empty($shop->error) && count($shop)>0)
						{
							$data['merchant']=$shop[0];
						}
						else
						{
							self::error(4);
						}

						/*取会员信息*/
                        if(0==$orderData[0]->base->or_mc_id)
                        {
                            //会员等级
                            $data['member'] = '';
                            $data['memberInfoAgio'] = '';
                        }else
                        {
                            $member = $this->_getMember($orderData[0]->base->or_mc_id);
                            if('noRecord' == $member)
                            {
                                $data['member'] = '';
                                $data['memberInfoAgio'] = '';
                            }else
                            {
                                $data['member'] = $member;
                                //累计积分等于 = 原有积分+本期积分（应付额）
                                $member->base->total_points=intval($member->attr->consume_integral) + intval($orderData[0]->base->or_payable_points);

                                /*取会员等级*/
                                $grade = $this->_getMemberGrade($member->attr->consume_grade);
                                $data['memberInfoAgio'] = $grade;
                                unset($member,$grade);
                            }
                        }
						$this->response($data);
					}				
				}
				else
				{
					self::error(5);
				}
			}
			else
			{
				self::error(6);
			}
		}
		else
		{
			self::error(7);
		}
	}
	/**
	 * 付款记录数据显示
	 * 
	 * @param string - contractNumber 合同号
	 * @return json - 合同号信息或错误信息
	 */
	private function _getConsume($contractNumber)
	{
		$conume = $this->rest_client->post('contract/getList',array('data'=>array('where'=>array('consume_pact_id'=>$contractNumber),'limit'=>1,'field'=>'consume_id',  'order'=>'consume_id desc')));
        if(isset($conume->status) && 2 == $conume->status)
        {
            return 'noRecord'; 
        }
        return $conume;
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
				$this->_salesName[1]='整体优惠';
				$value->orp_sale.='1、';
			}
			else if($salesArr[1]='TJ')
			{
				$this->_salesName[2]='特价';	
				$value->orp_sale.='2、';
			}
			else if($salesArr[1]='TC')
			{
				$this->_salesName[3]='套餐';	
				$value->orp_sale.='3、';
			}
			else if($salesArr[1]='JT')
			{
				$this->_salesName[4]='阶梯';	
				$value->orp_sale.='4、';
			}
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
		if(!empty($this->_salesMart))
		{
			$this->_salesName[7]='卖场促销';
			$value->orp_sale.='7、';
		}
		if(!empty($this->_salesFotn))
		{
			$this->_salesName[8]='以旧换新';
			$value->orp_sale.='8、';
		}
		$value->orp_sale = !empty($value->orp_sale) ? trim($value->orp_sale,'、') : '';
	}
	
	/**
     * 获得以旧换新商品数据
     *
	 * @param string - orderId 正式订单ID
	 * @param string - gmodel 商品型号
     * @return json - 是否是以旧换新商品或错误信息
     */
	private function _getFotn($orderId,$gModel)
	{
		$fotnReal = $this->rest_client->post('oldnewReal/getList',array('data'=>array('where'=>array('record_real_order_id'=>$orderId),'limit'=>1)));
		if(!empty($fotnReal) && empty($fotnReal->error) && count($fotnReal)>0)
		{
			$fotnPro = $this->rest_client->post('oldnewRealProduct/getListNum',array('where'=>array('fdp_record_id'=>$fotnReal[0]->record_id,'fdp_prod_model'=>$gModel)));
			if(!empty($fotnPro) && empty($fotnPro->error) && $fotnPro>0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}
											
	/**
     * 是否是卖场促销
     *
	 * @param string - orderId 正式订单id
     * @return json - 是否是卖场促销或错误信息
     */
	private function _getMart($orderId)
	{
		$mart = $this->rest_client->post('martApportion/getListNum',array('ljyunId'=>$this->_ljyunId,'where'=>array('app_order_id'=>$orderId, 'app_type'=>2)));
		if(!empty($mart) && $mart>0)
		{
			return true;
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
		$merchant = $this->rest_client->post('merchantInfo/getList',array('data'=>array('platform'=>array('where'=>array('merchant_id'=>$mid)))));
		if(!empty($merchant) && empty($merchant->error) && count($merchant)>0)
		{
			return $merchant;
		}else
		{
			return false;
		}
	}

	/**
     * 查询是否有会员卡打折
     *
	 * @param string - id 商户云编号
     * @return json - 是否有会员卡打折或错误信息
     */
	private function _getSaleMember($id)
	{
		$saleM = $this->rest_client->post('martApportion/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('app_order_id'=>$id,'app_type'=>1))));
		if(!empty($saleM) && empty($saleM->error))
		{
			return 1;
		}
		else
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
		$merchant = $this->rest_client->post('orderVirtual/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('or_id'=>$orderRealId))));
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
		$goods = $this->rest_client->post('orderProduct/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('vir_id'=>$virId))));
		if(!empty($goods) && count($goods)>0)
		{
			return $goods;
		}else
		{
			return false;
		}
	}

	/**
     * 商品数据显示
     *
	 * @param string - id 商品表主键id
     * @return json - 商品信息或错误信息
     */
	private function _getRealGoods($id)
	{
		$realGoods = $this->rest_client->get('product/getItem',array('ljyunId'=>$this->_ljyunId,'id'=>$id));
		if(!empty($realGoods) && count($realGoods)>0)
		{
			return $realGoods;
		}else
		{
			return false;
		}
	}

	/**
     * 会员数据显示
     *
	 * @param string - memberID 会员表m_member_information主键
     * @return json - 会员数据或错误信息
     */
	private function _getMember($memberId)
	{
		//$member = $this->rest_client->post('memberInfo/getList',array('data'=>array('base'=>array('where'=>array('member_phone'=>$phone),'limit'=>1))));
        $member = $this->rest_client->get('memberInfo/getItem',array('id'=>intval($memberId)));
		if(isset($member->status) && 2==$member->status)
		{
			return 'noRecord';
        }
        else
		{
            return $member;
		}
	}

	/**
     * 会员卡级别数据显示
     *
	 * @param string - id 会员折扣表主键ID
     * @return json - 会员卡信息或错误信息
     */
	private function _getMemberGrade($id)
	{
		$memberGrade = $this->rest_client->get('memberInfoAgio/getItem',array('id'=>$id));
		if(!empty($memberGrade) && count($memberGrade)>0)
		{
			return $memberGrade;
		}else
		{
			return false;
		}
	}
	
}
?>
