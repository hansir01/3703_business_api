<?php
/**
 * 收银系统,单品退款
 *
 * @author: yangzhen
 * @date: 2013-11-30
 */
class CashierReturnSomeProduct extends BLL_Controller
{
	private $result = array();//存放各种支付方式的名称和钱数

	function __construct()
    {
        parent::__construct();
    }
	/**
     * 单品退款初始信息
     *
     * @return response
     */
    public function startInit_get()
    {
		$data = array();
		$proInfo = array();//用于存放商品信息
		//消费记录表主键id
		$consumeId = intval($this->get('consumeId'));
		if(empty($consumeId))
		{
			$this->response(array('status'=>0,'error'=>'消费记录表主键id丢失'),500);
		}
		$data['consumeId'] = $consumeId;
		//获取消费记录信息
		$consumeInfo = $this->rest_client->get('contract/getItem',array('id'=>$consumeId));
		if(isset($consumeInfo->status))
		{
			$this->response(array('status'=>0,'error'=>'获取合同信息失败'),500);
		}
		$contract = $consumeInfo->consume_pact_id;
		//获取订单基本信息
		$orderInfo = $this->rest_client->post('orderBasic/getList',array('data'=>array('base'=>array('where'=>array('or_sale_number'=>$contract),'order'=>'or_id desc','limit'=>1))));
		if(isset($orderInfo->status) && $orderInfo->status != 1)
		{
			$this->response(array('status'=>0,'error'=>'获取订单基本信息失败'),500);
		}
		//判断该合同是否是全额付款
		if($orderInfo[0]->base->or_full_payment == 0)
		{
			$this->response(array('status'=>0,'error'=>'单品退款必须是全额付款'),500);
		}
		//判断该合同是否是现金付款
		$cash = $this->rest_client->post('cashCoupon/getList',array('data'=>array('where'=>array('payform_cash >'=>0,'payform_consume_id'=>$consumeId),'field'=>array('payform_cash as value'))));
		if(isset($cash->status))
		{
			$this->response(array('status'=>0,'error'=>'单品退款必须是现金付款'),500);
		}
		//判断该合同是否有 整单退款 行为
		$checkChargeBackAll = $this->rest_client->post('chargeBack/getList',array('data'=>array('where'=>array('back_pact_id'=>$consumeId,'back_type'=>0),'limit'=>1,'field'=>array('id'))));
		if(isset($checkChargeBackAll->status) && $checkChargeBackAll->status == 2)
		{
			//查询单品退款信息
			$checkChargeBackSome = $this->rest_client->post('chargeBack/getList',array('data'=>array('where'=>array('back_pact_id'=>$consumeId,'back_type'=>1),'field'=>array('id'))));
			$chargeProducts = array();
			if(!(isset($checkChargeBackSome->status) && $checkChargeBackSome->status == 2))
			{
				foreach($checkChargeBackSome as $backPro)
				{
					$chargeProductList = $this->rest_client->post('chargeBackProduct/getList',array('data'=>array('where'=>array('charge_back_id'=>$backPro->id))));
					if(!(isset($chargeProductList->status) && $chargeProductList->status == 2))
					{
						foreach($chargeProductList as $chargeProduct)
						{
							$chargeProducts[] = $chargeProduct;
						}
					}
				}
			}
			unset($checkChargeBackSome);
			//销售合同号
			$data['contract'] = $contract;
			//合同状态
			$data['contractState'] = $consumeInfo->consume_fact_value > 0 ? '已付款' : '未付款';
			//合同生成日期
			$data['contractDate'] = $consumeInfo->consume_date;
			//获取商户信息
			$merchantInfomation = $this->rest_client->post('merchantInfo/getList',array('data'=>array('base'=>array('where'=>array('merchant_id'=>$consumeInfo->consume_merchant_id),'limit'=>1))));
			if(isset($merchantInfomation->status) && $merchantInfomation->status != 1)
			{
				$this->response(array('status'=>0,'error'=>'获取商户信息失败'),500);
			}
			//展位号
			$data['showId'] = $consumeInfo->consume_merchant_show;
			//商户名称
			$data['merchantName'] = $merchantInfomation[0]->base[0]->merchant_ch_name;
			//商户电话
			$data['merchantPhone'] = $merchantInfomation[0]->base[0]->merchant_mphone;
			//获取订单商品信息
			$orderProInfo = $this->rest_client->post('orderProduct/getList',array('data'=>array('where'=>array('orp_tmp_id'=>$orderInfo[0]->base->or_id))));
			if(isset($orderProInfo->status) && $orderProInfo->status != 1)
			{
				$this->response(array('status'=>0,'error'=>'获取订单商品信息失败'),500);
			}
			$proTotalPrice = 0;
			foreach($orderProInfo as $pro)
			{
				$proTmp['id'] = $pro->orp_id;//商品表主键id
				$proTmp['proId'] = $pro->orp_pro_id;//商品id
				$proTmp['proName'] = $pro->orp_pro_name;//商品名称
				$proTmp['proBrand'] = $pro->orp_brand;//商品品牌
				$proTmp['proModel'] = $pro->orp_model;//商品型号
				$proTmp['proSpec'] = $pro->orp_specification;//商品规格
				$proTmp['proPrice'] = $pro->orp_pro_sale_price > 0 ? $pro->orp_pro_sale_price : $pro->orp_pro_price;//商品价钱
				$proTmp['proReturn'] = 0;//默认没有退货
				$proSum = $pro->orp_pro_account;//商品数量
				$proTotalPrice += $proTmp['proPrice'] * $proSum;
				if($proSum > 1)
				{
					for($i=1;$i<=$proSum;$i++)
					{
						$proInfo[] = $proTmp;//将数量大于一的商品转换为多条记录
					}
				}else
				{
					$proInfo[] = $proTmp;
				}
			}
			if(!empty($chargeProducts))
			{
				foreach($proInfo as $key=>$proTmp)
				{
					foreach($chargeProducts as $k=>$chargePro)
					{
						if($proTmp['proId'] == $chargePro->order_real_product_id && $proTmp['proPrice'] == $chargePro->product_price)
						{
							$proInfo[$key]['proReturn'] = 1;//退货
							unset($chargeProducts[$k]);//剔除匹配过的记录
						}
					}
				}
			}
			//商品购物清单
			$data['proInfo'] = $proInfo;
			$data['proTotalPrice'] = $proTotalPrice;
			unset($proInfo,$chargeProducts,$proInfo);
			//会员卡信息
			$memberInfo = $this->rest_client->get('memberInfo/getItem',array('id'=>$orderInfo[0]->base->or_mc_id,'field'=>array('base'=>array('field'=>array('member_card_fid')))));
			if(isset($memberInfo->status) && $memberInfo->status == 0)
			{
				$this->response(array('status'=>0,'error'=>'获取会员卡信息失败'),500);
			}
			//顾客姓名
			$data['customerName'] = $orderInfo[0]->base->or_customer_name;
			//手机号
			$data['customerPhone'] = $orderInfo[0]->base->or_customer_phone;
			//证件类型
			$data['customerIdCardType'] = $orderInfo[0]->base->or_customer_idcard_type;
			//证件号
			$data['customerIdCard'] = $orderInfo[0]->base->or_customer_idcard;
			//送货地址
			$data['customerSendAddr'] = $orderInfo[0]->base->or_delivery_address;			
			//weixiaohua 2014-1-10添加商户电话
			$data['storeTel'] = $orderInfo[0]->base->or_store_tel;			
			if(isset($memberInfo->status) && $memberInfo->status == 2)
			{
				//没有会员卡信息
				$data['card'] = 0;
				$data['customerLevel'] = '';
				$data['nowScore'] = 0;
				$data['orderScore'] = 0;
				$data['newScore'] = 0;
			}else{
				$data['card'] = 1;
				//查询会员卡等级积分
				$memberCardInfo = $this->rest_client->get('memberInfoAgio/getItem',array('id'=>$memberInfo->attr->consume_grade));
				if(isset($memberInfo->status) && $memberInfo->status != 1)
				{
					$this->response(array('status'=>0,'error'=>'查询会员卡等级积分信息失败'),500);
				}
				//会员等级--普通会员，银卡会员，金卡会员
				$data['customerLevel'] = $memberCardInfo->agio_grade;
				//原有积分
				$data['nowScore'] = $memberInfo->attr->consume_integral;
				//本期积分
				$data['orderScore'] = $orderInfo[0]->base->or_payable_value;
				//本期积分
				$data['newScore'] = $data['nowScore'] + $data['orderScore'];
			}
			//交货方式
			$data['sendType'] = $orderInfo[0]->base->or_get_self == 1 ? '自提' : '送货';
			//交货日期
			$data['sendTime'] = $orderInfo[0]->base->or_send_time;
			//测量日期
			$data['measureTime'] = $orderInfo[0]->base->or_measure_time;
			//安装日期
			$data['instalTime'] = $orderInfo[0]->base->or_instal_time;
			//另行支付
			//运费 安装费 材料费 默认为0
			$data['transportPrice'] = 0;
			$data['installPrice'] = 0;
			$data['materialPrice'] = 0;
			$data['otherPrice'] = 0;
			if(!empty($orderInfo[0]->other_attr))
			{
				//运费
				$data['transportPrice'] = $orderInfo[0]->other_attr->other_transport_price;
				//安装费
				$data['installPrice'] = $orderInfo[0]->other_attr->other_install_price;
				//材料费
				$data['materialPrice'] = $orderInfo[0]->other_attr->other_material_price;
				//总额
				$data['otherPrice'] = $data['transportPrice'] + $data['installPrice'] + $data['materialPrice'];
			}
		}else{
			$this->response(array('status'=>0,'error'=>'该合同已有整单退款记录，不能进行单品退款'),500);
		}
		$this->response($data,200);
    }

	/**
     * 单品退款
     *
     * @return response
     */
	function endSubmit_put()
	{
		//消费记录表主键id
		$consumeId = $this->put('consumeId');
		if(empty($consumeId))
		{
			$this->response(array('status'=>0,'error'=>'消费记录表主键id丢失'),500);
		}
		$proIds = $this->put('proIds');
		if(empty($proIds))
		{
			$this->response(array('status'=>0,'error'=>'退款商品信息丢失'),500);
		}
		//另行协商退款
		$ortherPrice = $this->put('ortherPrice');
		if(empty($ortherPrice))
		{
			$ortherPrice = 0.00;
		}		
		//获取合同信息
		$contractInfo = $this->rest_client->get('contract/getItem',array('id'=>$consumeId));
		if(isset($contractInfo->status))
		{
			$this->response(array('status'=>0,'error'=>'获取合同信息失败'),500);
		}
		//获取订单基本信息
		$orderInfo = $this->rest_client->post('orderBasic/getList',array('data'=>array('base'=>array('where'=>array('or_sale_number'=>$contractInfo->consume_pact_id),'order'=>'or_id desc','limit'=>1))));
		if(isset($orderInfo->status) && $orderInfo->status != 1)
		{
			$this->response(array('status'=>0,'error'=>'获取订单基本信息失败'),500);
		}
		//判断该合同是否是全额付款
		if($orderInfo[0]->base->or_full_payment == 0)
		{
			$this->response(array('status'=>0,'error'=>'单品退款必须是全额付款'),500);
		}
		//判断该合同是否是现金付款
		$cash = $this->rest_client->post('cashCoupon/getList',array('data'=>array('where'=>array('payform_cash >'=>0,'payform_consume_id'=>$consumeId),'field'=>array('payform_cash as value'))));
		if(isset($cash->status))
		{
			$this->response(array('status'=>0,'error'=>'单品退款必须是现金付款'),500);
		}
		//判断该合同是否有 整单退款 行为
		$checkChargeBackAll = $this->rest_client->post('chargeBack/getList',array('data'=>array('where'=>array('back_pact_id'=>$consumeId,'back_type'=>0),'limit'=>1,'field'=>array('id'))));
		if(isset($checkChargeBackAll->status) && $checkChargeBackAll->status != 2)
		{
			$this->response(array('status'=>0,'error'=>'该合同已有整单退款记录，不能进行单品退款'),500);
		}
		//退款商品总价
		$totalPrice = 0;
		foreach($proIds as $k=>$o)
		{
			//获取订单商品信息
			$pro = $this->rest_client->get('orderProduct/getItem',array('id'=>$o));
			if(isset($orderProInfo->status) && $orderProInfo->status != 1)
			{
				$this->response(array('status'=>0,'error'=>'获取订单商品信息失败'),500);
			}
			$pro->price = $pro->orp_pro_sale_price > 0 ? $pro->orp_pro_sale_price : $pro->orp_pro_price;//商品价钱
			$totalPrice += $pro->price;
			$proInfo[] = $pro;
		}
		//退货记录表添加记录
		$chargeData = array(
			'back_pact_number'=>$contractInfo->consume_pact_id,
			'back_type'=>1,
			'back_charge'=>$totalPrice,
			'back_time'=>date('Y-m-d H:i:s'),
			'back_pact_id'=>$contractInfo->consume_id,
			'back_negotiate'=>$ortherPrice
		);
		$chargeInsert = $this->rest_client->post('chargeBack/addItem',array('data'=>$chargeData));
		if(isset($chargeInsert->status) && $chargeInsert->status == 0)
		{
			$this->response(array('status'=>0,'error'=>'退货记录表添加记录失败'),500);
		}
		//退货记录商品表添加记录
		foreach($proInfo as $v)
		{
			$chargeProData = array(
				'order_real_product_id'=>$v->orp_id,
				'charge_back_id'=>$chargeInsert->key,
				'product_price'=>$v->price
			);
			$chargeProInsert = $this->rest_client->post('chargeBackProduct/addItem',array('data'=>$chargeProData));
			if(isset($chargeProInsert->status) && $chargeProInsert->status == 0)
			{
				$this->response(array('status'=>0,'error'=>'退货记录商品表添加记录失败'),500);
			}
		}
		//扣款明细表添加记录
		$deductType = $this->rest_client->post('accountInfo/getList',array('data'=>array('where'=>array('type_name'=>'product_conume'),'limit'=>1)));
		if(isset($deductType->status))
		{
			$this->response(array('status'=>0,'error'=>'扣款类型字典表获取信息失败'),500);
		}
		$deductData = array(
			'deduct_type'=>$deductType[0]->id,
			'deduct_cause_id'=>$contractInfo->consume_id,
			'deduct_charge'=>$totalPrice,
			'deduct_add_time'=>date('Y-m-d H:i:s')
		);
		$deductInsert = $this->rest_client->post('spending/addItem',array('data'=>$deductData));
		if(isset($deductInsert->status) && $deductInsert->status == 0)
		{
			$this->response(array('status'=>0,'error'=>'扣款明细表添加记录失败'),500);
		}
		$this->response(array('status'=>1),200);
	}
}
