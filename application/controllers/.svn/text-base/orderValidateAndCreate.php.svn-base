<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 业务逻辑层，下单系统，订单验证及生成
 *
 * @author: weixiaohua
 * @date: 2013-11-20
 */

class OrderValidateAndCreate extends BLL_Controller {

	function __construct()
	{
        parent::__construct();
        //获取蓝景编号
		$this->_ljyunId = $this->common->getLjyunIdByForm("ljyunId");
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
                1=>'Order base insert lose',
				2=>'Fotn update lose',
				3=>"Order base array is empty",
				4=>"Order contract info insert lose",
				5=>"Order virtual update lose",
				6=>"Order virtual info is empty",
				7=>"Order product insert lose",
				8=>"Order product info is empty",
				9=>"Order sale contract insert lose",
				10=>"fotn is empty",
				11=>"Order message false",
				12=>"Order prduct false",
				13=>"product info is empty",
				14=>"prduct model is false",
				15=>"parts model is empty",
				16=>"saleDiscount no record",
				17=>"One product sale price not true",
				18=>"Onesale price not true",
				19=>"product total sale price not true",
				21=>"No otherValue contract parts price not true",
				22=>"payable price not true",
				23=>"No otherVale payable price not true",
				24=>"Other pay not true",
				25=>"product no brand",
				26=>"product no area",
				27=>"no productInfo",
				28=>"material no area",
				29=>"on parts Info",
				30=>"productId is empty",
				31=>"brand is empty",
				32=>"wordwide is empty",
				33=>"area is empty",
                34=>"order real history pay data is empty!",
                35=>"orer real history insert failed"
                );
        Util::errorMsg($msg[$msgType]);
	}

    /**
     * 初始化页面数据
     *
     * @return mixed - array/string(错误信息)
     */
	public function startInit_get()
	{
        error_reporting(0);
		$id = $this->get("id");
		$orderTmpSaleInfo ='';
		$orderTmpInfo = $this->rest_client->get("orderTmp/getItem",array('ljyunId'=>$this->_ljyunId,"id"=>$id));//临时订单信息
		if(!isset($orderTmpInfo->status))
		{
			//是不是以旧换新需要全额付款
			$fotnInfo = $this->_getFotn($orderTmpInfo->tmp_id);
			!isset($fotnInfo->status)?$orderTmpInfo->isFullPayment = 1:$orderTmpInfo->isFullPayment = 0;
			//优惠了多少钱
			if(!empty($orderTmpInfo->sale_number))
			{
				$orderTmpInfo->discountValue = $orderTmpInfo->tmp_total_price - $orderTmpInfo->tmp_product_sale_value;
			}else
			{
				$orderTmpInfo->discountValue = 0.00;
			}
			//合同类促销表信息
			if($orderTmpInfo->otp_contract_sale_value != 0.00)
			{
				$orderTmpSaleInfo = $this->rest_client->post("orderTmpSale/getList",array("ljyunId"=>$this->_ljyunId,"data"=>array("where"=>array("otsc_tmp_id"=>$orderTmpInfo->tmp_id),"limit"=>1)));
				if(isset($orderTmpSaleInfo->status) && $orderTmpSaleInfo->status == 2)
				{
					$this->response(array("status"=>2,"error"=>"OrderTmpContractSale:no record"),200);
				}
			}
			//临时订单商品信息
			$orderTmpProduct = $this->rest_client->post("orderTmpProduct/getList",array("ljyunId"=>$this->_ljyunId,"data"=>array("where"=>array("otp_tmp_id"=>$orderTmpInfo->tmp_id))));
			if(!isset($orderTmpProduct->status))
			{
				$product =array();
				//根据临时订单商品表里的商品id取商品信息
				foreach($orderTmpProduct as $val)
				{
					$productInfo = '';
					$productUnit = '';
					$isFont = 0;
					$proExtrapro = $this->_getProduct($val->otp_pro_id,$val->otp_pro_type);
					if(isset($proExtrapro->status))
					{
						$this->response(array("status"=>2,"error"=>"OrderTmpProduct:no record"),200);
					}else
					{
						$val->productInfo = $proExtrapro;
					}
					//单位
					$productUnit = $this->rest_client->get("productProunit/getItem",array("ljyunId"=>$this->_ljyunId,"id"=>$val->otp_pro_unit,"field"=>"prounit_name"));
					if(!isset($productInfo->status))
					{
						$val->pro_unit = $productUnit->prounit_name;
					}else
					{
						$this->response(array("status"=>2,"error"=>"ProductProunit :no record"),200);
					}
					/*检查是否是以旧换新
					$isFotn = $this->_getFotnInfo($val->otp_tmp_id,$val->otp_model);
					!empty($isFotn) ? $val->isFotnNumber = $isFotn:$val->isFotnNumber = 0;*/
					$val->isFotnNumber = 0;
					$product[$val->vir_id][] = $val;
				}
				unset($orderTmpProduct);
			}else
			{
				$this->response(array("status"=>2,"error"=>"orderTmpProduct: No record"),200);
			}
			//虚拟商品信息
			$orderTmpVirtual = $this->rest_client->post("orderTmpVirtual/getList",array("ljyunId"=>$this->_ljyunId,"data"=>array("where"=>array("tmp_id"=>$orderTmpInfo->tmp_id))));
			if(isset($orderTmpVirtual->status) && $orderTmpVirtual->status == 2)
			{
				$this->response(array("status"=>2,"error"=>"orderTmpVirtual: No record"),200);
			}
			//商户信息
			$orderMerchantInfo = $this->rest_client->post("merchantInfo/getList",array("data"=>array("platform"=>array("where"=>array("merchant_id"=>$this->_ljyunId)))));
			if(empty($orderMerchantInfo) || isset($orderMerchantInfo->status) && $orderMerchantInfo->status == 2)
			{
				$this->response(array("status"=>2,"error"=>"merchantInfo: No record"),200);
			}
			//顾客信息
			$orderMemberInfo = $this->rest_client->get("merchantCustomer/getItem",array("ljyunId"=>$this->_ljyunId,"id"=>$orderTmpInfo->tmp_mc_id));
			if(isset($orderMemberInfo->status) && $orderMemberInfo->status == 2)
			{
				$this->response(array("status"=>2,"error"=>"memberInfo: No record"),200);
			}
			$data = array("base"=>$orderTmpInfo,"product"=>$product,"virtual"=>$orderTmpVirtual,"contractSale"=>$orderTmpSaleInfo,"memberInfo"=>$orderMemberInfo,"merchantInfo"=>$orderMerchantInfo[0]);
			$this->response($data,200);
		}
	}

    /**
     * 对外接口得到订单相关所有数据，调用数据资源接口进行数据保存
     *
	 * @param array - $this->orderData 订单所有相关信息
	 * @param func - $this->_integrityVerify 调用类的私有方法，用于验证订单信息的完整性
	 * @param func - $this->_moneyVerify 私有方法，用于验证订单的金额
     * @return boolean - ture/false
     */
	public function endSubmit_post()
	{
		$insertId = '';
		$contractRes = '';
		$this->orderData = $this->post("data");
		$this->_integrityVerify(); //验证订单信息完整性
		$this->_moneyVerify();//验证订单金额是否正确
        //插入正式订单相关数据
		if(!empty($this->orderData['orderInfo']))
		{
			$insertId = $this->rest_client->post("orderBasic/addItem",array("ljyunId"=>$this->_ljyunId,"data"=>$this->orderData['orderInfo']));//订单基本
			if($insertId->status != 1)
			{
				self::error(1);
			}
			//查询有没有以旧换新数据
			$fotnInfo = $this->_getFotn($this->orderData['orderInfo']['base']['or_tmp_id']);
			if(!isset($fotnInfo->status))
			{
				$fotnUp = $this->rest_client->put('oldnewTmp/setItem',array("ljyunId"=>$this->_ljyunId,"data"=>array("id"=>$fotnInfo[0]->record_id,"data"=>array("record_real_order_id"=>$insertId->key))));
				if($fotnUp->status != 1)
				{
					self::error(2);
				}
			}

		}else
		{
			self::error(3);
		}
        //Liangxifeng modify 插入交款历史记录相关数据
        if(!empty($this->orderData['historyPay']))
        {
            $this->orderData['historyPay']['order_real_id'] = $insertId->key;
            $addHistory = $this->rest_client->post("orderHistory/addItem",array('ljyunId'=>$this->_ljyunId,"data"=>$this->orderData['historyPay']));
			if($addHistory->status != 1)
			{
				self::error(35);
			}
            
        }else
        {
            self::error(34);
        }

		if(!empty($this->orderData['contractInfo']))
		{
			$this->orderData['contractInfo']['orsc_real_id'] = $insertId->key;
			$orderContractSale = $this->rest_client->post("orderRealPromotionContract/addItem",array("ljyunId"=>$this->_ljyunId,"data"=>$this->orderData['contractInfo']));//合同类订单信息
			if($orderContractSale->status != 1)
			{
				self::error(4);
			}
		}
		if(!empty($this->orderData['virtual']))
		{
			foreach($this->orderData['virtual'] as $virVal)
			{
				$virInsert ='';
				$virInsert = $this->rest_client->put("orderVirtual/setItem",array("ljyunId"=>$this->_ljyunId,"data"=>array("id"=>$virVal['vir_id'],"data"=>array("or_id"=>$insertId->key))));//虚拟商品信息
				if($virInsert->status != 1)
				{
					self::error(5);
				}
			}
		}else
		{
			self::error(6);
		}
		if(!empty($this->orderData['product']))
		{
			foreach($this->orderData['product'] as $proVal)
			{
				$proInsert = '';
				$proVal['orp_tmp_id'] = $insertId->key;
				$proInsert = $this->rest_client->post("orderProduct/addItem",array("ljyunId"=>$this->_ljyunId,"data"=>$proVal));//订单商品信息
				if($proInsert->status != 1)
				{
					self::error(7);
				}
			}
		}else
		{
			self::error(8);
		}

		if(!empty($this->orderData['oneSaleContract']))
		{
			foreach($this->orderData['oneSaleContract'] as $saleVal)
			{
                $saleVal['order_real_id'] = $insertId->key;
                $saleVal['or_sale_number'] = '';
				$oneSaleContract = $this->rest_client->post("orderSale/addItem",array("ljyunId"=>$this->_ljyunId,"data"=>$saleVal));
				if($oneSaleContract->status != 1)
				{
					self::error(9);
				}
			}
		}
		$this->response(array("status"=>1,"key"=>$insertId->key),200);
	}
	
	/**
     * 标准商品是否是以旧换新数据
     *
	 * @param string - id 商品主键id
     * @return json - 商品报价信息或错误信息
     */
	private function _getFotn($id)
	{
		$fotn = $this->rest_client->post('oldnewTmp/getList',array("ljyunId"=>$this->_ljyunId,"data"=>array('where'=>array('record_tmp_order_id'=>$id),"limit"=>1)));
		if(!empty($fotn) && count($fotn)>0)
		{
			return $fotn;
		}else
		{
			self::error(10);
		}
	}
	
	/**
     * 以旧换新信息
     *
	 * @param string - id 临时订单id
     * @return json - 商品报价信息或错误信息
     */
	private function _getFotnInfo($id,$productModel)
	{
		$number = 0;
		//根据临时订单获取以旧换新交易表信息
		$fotn = $this->rest_client->post('oldnewTmp/getList',array("ljyunId"=>$this->_ljyunId,"data"=>array('where'=>array('record_tmp_order_id'=>$id),"limit"=>1)));
		if(!empty($fotn) && count($fotn)>0)
		{
			$fotnProduct = $this->rest_client->post("oldnewTmpProduct/getList",array("ljyunId"=>$this->_ljyunId,"data"=>array("where"=>array('fdp_record_id'=>$fotn[0]->record_id))));
			foreach($fotnProduct as $val)
			{
				if($val->fdp_prod_model == $productModel)
				{
					$number ++;
				}
			}
		return $number;
		}else
		{
			self::error(10);
		}
	}

    /**
     * 对订单相关信息的完整性验证
     *
	 * @param array - $this->orderData['product'] 订单商品信息
	 * @param array - $this->orderData['base'] 订单相关信息
     * @return boolean - ture/false
     */
	private function _integrityVerify()
	{
		//if验证合同信息及顾客信息，else验证商品信息，返回false/true
		if(empty($this->orderData['orderInfo']['base']['or_customer_name']) || empty($this->orderData['orderInfo']['base']['or_customer_phone']) 
            || empty($this->orderData['orderInfo']['base']['or_pact_value']) || empty($this->orderData['orderInfo']['base']['or_payable_value']) 
            || empty($this->orderData['orderInfo']['base']['or_product_value']))
		{
			self::error(11);
		}else
		{
			if(empty($this->orderData['product']))
			{
				self::error(12);
			}else
			{
				foreach($this->orderData['product'] as $key=>$val)
				{
					//由于商品型号在商品表里，此次未写商品资源,因此先固定写死
					if(empty($val['orp_pro_name']) || empty($val['orp_pro_price']) 
						|| $val['orp_pro_price']<=0 || empty($val['orp_pro_account']) 
						|| $val['orp_pro_account']<=0 || empty($val['orp_pro_id']))
	 
					{
						self::error(13);
					}else
					{
                        //验证标准商品型号
                        if(1==$val['orp_pro_type'])
                        {
                            //根据商品的标识如果是标准商品判断商品型号存不存
                            $field = array("base"=>array("field"=>"product_model"),"other_attr"=>array("field"=>'--'));
                            $productInfo = $this->rest_client->get("product/getItem",array("ljyunId"=>$this->_ljyunId,"id"=>$val['orp_pro_id'],"field"=>$field));
                            if(isset($productInfo->status) || empty($productInfo->base->product_model))
                            {
								self::error(14);
                            }
                        //验证配件型号
                        }elseif( 3==$val['orp_pro_id'] )
                        {
                            //根据商品的标识如果是标准商品判断商品型号存不存
                            $productInfo = $this->rest_client->get("partsLibrary/getItem",array("ljyunId"=>$this->_ljyunId,"id"=>$val['orp_pro_id'],"field"=>"pl_model"));
                            if(isset($productInfo->status) || empty($productInfo->pl_model))
                            {
								self::error(15);
                            }
                        }
					}
	 
				}
			}
		}
	}

	/**
     * 对订单相关金额的验证
     *
	 * @param array - $this->orderData['orderOther'] 另行支付信息
	 * @param array - $this->orderData['product'] 订单商品信息
	 * @param array - $this->orderData['base'] 订单相关信息
	 * @param string - $saleNumber 促销编号
	 * @param float - $salePrice 商品促销后单价  
     * @return boolean - ture/false
     */
	private function _moneyVerify()
	{
		//验证另行支付
		if(!empty($this->orderData['orderInfo']['other_attr']))
		{
			$this->_otherPriceSum();
		}
		//商品促销单价是否正确
		if(!empty($this->orderData['product']))
		{
			$this->_productOneDiscount();
		}
		//将商品促销后总价款相加是否等于订单表里的商品促销后总价款
		$this->_productSumDiscount();
		//验证合同的全额付款
		if($this->orderData['orderInfo']['base']['or_full_payment'] == 1 )
		{
			$this->_contractFullVerify();
		}
		//验证合同的部分付款
		if($this->orderData['orderInfo']['base']['or_full_payment'] == 0 )
		{
			$this->_contractPartVerify();
		}
	}

	/**
     * 验证单件商品的促销价是否正确
     *
	 * @param array - $this->orderData['product'] 订单商品信息
	 * @param array - $this->orderData['base'] 订单相关信息 
     * @return boolean - ture/false
     */
	private function _productOneDiscount()
	{
		foreach($this->orderData['product'] as $proVal)
		{
			if(empty($proVal['sale_number'])) continue;
			//取促销信息,需要知道是整体还是特价
			$saleNumber = '';//促销活动编号
			$salePrice = '';//通过计算折后商品的价钱
			$saleId ='';//促销编号关联表的主键ID
			$saleArr = $this->_saleNumber($proVal['sale_number']);//截取促销编号获取活动编号和主键
			$saleNumber = $saleArr[1];//截取促销编号获取活动编号和主键
			$saleId = $saleArr[2];
			if($saleNumber == 'ZT')
			{
				//调数据资源，取出折扣信息
				$saleDiscountInfo = $this->rest_client->get("saleDiscount/getItem",array('ljyunId'=>$this->_ljyunId,'id'=>$saleId));
				if(isset($saleDiscountInfo->status))
				{
					self::error(16);
				}
				$salePrice = Util::dealDecimal(($proVal['orp_pro_price']* $saleDiscountInfo->sale_discount)/100);
				if($salePrice != $proVal['orp_pro_sale_price'])
				{
					self::error(17);
				}
			}elseif($saleNumber == 'TJ')
			{
				//调数据资源，取出商品单价
				$saleOnePrice = $this->rest_client->get("saleOneprice/getItem",array('ljyunId'=>$this->_ljyunId,"id"=>$saleId));
				if($proVal['orp_pro_sale_price'] != $saleOnePrice->sale_price)
				{
					self::error(18);
				}
			}
		}
	}

	/**
     * 验证商品的促销总价是否正确
     *
	 * @param array - $this->orderData['product'] 订单商品信息
	 * @param array - $this->orderData['base'] 订单相关信息 
     * @return boolean - ture/false
     */
	private function _productSumDiscount()
	{
		$productDiscount = 0; // 促销商品单价累计
		foreach($this->orderData['product'] as $val)
		{
			//如果有促销活动
			$val['sale_number'] != '' ? $productDiscount += $val['orp_total_sale_price'] : $productDiscount += $val['orp_total_price'];
		}
		if($productDiscount != $this->orderData['orderInfo']['base']['or_product_sale_value'])
		{
			self::error(19);
		}
	}

	/**
	 * 合同是全额付款时应付款验证方法
	 *
     * @param array - $this->orderData['product'] 订单商品信息
	 * @param array - $this->orderData['base'] 订单相关信息 
     * @return boolean - ture/false
     */
	private function _contractFullVerify()
	{
		//判断有没有促销活动
		$saleInfo = 0;
		if($this->orderData['orderInfo']['base']['sale_number'] != '')
		{
			$saleInfo = 1;
		}
		//如果有阶梯促销
		if($this->orderData['orderInfo']['base']['or_contract_sale_value'] != 0.00)
		{
			$this->_otherValue($this->orderData['orderInfo']['base']['or_contract_sale_value']);
		}else
		{
			//有没有促销活动
			if($saleInfo != 1)
			{
				$this->_otherValue($this->orderData['orderInfo']['base']['or_product_value']);
			}else
			{
				$this->_otherValue($this->orderData['orderInfo']['base']['or_product_sale_value']);
			}
		}
	}

	/**
	 * 合同是部分付款的时候应付额的算法
	 *
     * @param array - $this->orderData['product'] 订单商品信息
	 * @param array - $this->orderData['base'] 订单相关信息 
     * @return boolean - ture/false
     */
	private function _contractPartVerify()
	{
		//如果是部分付款不需要加另行支付 应付款 = 部分付款的四项之和
		$partPrice = $this->orderData['orderInfo']['attr']['pay_deposit']+$this->orderData['orderInfo']['attr']['pay_install_price']+$this->orderData['orderInfo']['attr']['pay_imprest']+$this->orderData['orderInfo']['attr']['pay_transport_price'];
		if($partPrice != $this->orderData['orderInfo']['base']['or_payable_value'])
		{
			self::error(21);
		}
	}

	/**
	 * 验证合同的应付额时需要用到的另行支付部分
	 *
     * @param array - $this->orderData['product'] 订单商品信息
	 * @param array - $this->orderData['base'] 订单相关信息 
     * @return boolean - ture/false
     */
	private function _otherValue($flag)
	{
		//有没有另行支付
		if(!empty($this->orderData['orderInfo']['other_attr']))
		{
			$payableValue = $this->orderData['orderInfo']['base']['or_other_value']+$flag;
			if($payableValue != $this->orderData['orderInfo']['base']['or_payable_value'])
			{
				self::error(22);
			}
		}else
		{
			if($this->orderData['orderInfo']['base']['or_payable_value'] != $flag)
			{
				self::error(23);
			}
		}
	}
	
	//另行支付的总金额对不对
	private function _otherPriceSum()
	{
		$otherSum = 0 ;
		$otherSum = $this->orderData['orderInfo']['other_attr']['other_transport_price']+$this->orderData['orderInfo']['other_attr']['other_install_price']+$this->orderData['orderInfo']['other_attr']['other_material_price'];
		if($otherSum != $this->orderData['orderInfo']['base']['or_other_value'])
		{
			self::error(24);
		}
	}
	//根据需求通过促销编号得到想要部分
	private function _saleNumber($field)
	{
	       //00321（K代码后5位）-ZT（活动标示）-2(活动主键)-2（期数）
		$number = explode('-',$field);
		return $number;
	}

	//商品信息
	private function _getProduct($proId,$type)
	{
		//型号
		if(!empty($proId))
		{
			if($type == 1)//标准商品
			{
				$productInfo = $this->rest_client->get("product/getItem",array('ljyunId'=>$this->_ljyunId,"id"=>$proId));
				if(!isset($productInfo->status))
				{
					//查询品牌
					$brand = $this->_getBrand($productInfo->base->product_brandid);
					if(isset($brand->status))
					{
						self::error(25);
					}
					//查产地
					$area = $this->_getArea($productInfo->other_attr->extrapro_origin,$productInfo->other_attr->extrapro_importornot);
					if(isset($area->status))
					{
						self::error(26);
					}
					$res = array("product_model"=>$productInfo->base->product_model,"spec"=>$productInfo->base->product_specification,"brand"=>$brand->probrand_name,"area"=>$area);
				}else
				{
					self::error(27);
				}
			}elseif($type == 2)//材料
			{
				$materialInfo = $this->rest_client->get("materialDepot/getItem",array('ljyunId'=>$this->_ljyunId,"id"=>$proId));
				if(!isset($materialInfo->status))
				{
					//查产地
					$area = $this->_getArea($materialInfo->material_area,$materialInfo->material_importornot);
					if(isset($area->status))
					{
						self::error(28);
					}
					$res = array("product_model"=>'',"spec"=>$materialInfo->product_specification,"brand"=>$materialInfo->material_brand,"area"=>$area);
				}
			}else
			{
				$partsInfo = $this->rest_client->get("partsLibrary/getItem",array('ljyunId'=>$this->_ljyunId,"id"=>$proId));
				if(isset($partsInfo->status))
				{
					self::error(29);
				}
				$res = array("product_model"=>$partsInfo->pl_model,"spec"=>$partsInfo->product_specification,"brand"=>$partsInfo->material_brand,"area"=>'');
			}
			return $res;
		}else
		{
			self::error(30);
		}
	}
	//查询品牌
	private function _getBrand($brandId)
	{
		$brandInfo = $this->rest_client->get("merchantBrand/getItem",array("ljyunId"=>$this->_ljyunId,"id"=>$brandId));
		if(!isset($brandInfo->error))
		{
			return $brandInfo;
		}else
		{
			self::error(31);
		}
	}
	//查询产地
	private function _getArea($areaId,$type)
	{
		if($type == 1)//进口产品
		{
			$areaInfo = $this->rest_client->get("productOrigin/getItem",array("data"=>array("foreign"=>array("id"=>$areaId))));
			if(!isset($areaInfo->status))
			{
				return $areaInfo->foreign[0]->worldwide_name;
			}else
			{
				self::error(32);
			}
		}else
		{
			$areaInfo = $this->rest_client->get("productOrigin/getItem",array("data"=>array("domestic"=>array("id"=>$areaId))));
			if(!isset($areaInfo->status))
			{
				return $areaInfo->domestic[0]->area_name;
			}else
			{
				self::error(33);
			}
		}
		
	}	
}
