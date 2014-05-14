<?php
/**
 * 下单系统,计算会员卡折扣
 *
 * @author: yangzhen
 * @date: 2013-11-26
 */
include_once("Deduction.php");
class MemberDeduction extends Deduction
{
    private $agio;//折扣
    private $orderPro;//订单商品信息
	private $orderBasic;//订单基本信息
    private $payable;//订单应付额
	private $proPrice;//全额付款时商品主键和折扣价钱
	private $proPrices;//全额付款时商品折后价
    private $_ci;

    public function __construct()
    {
        parent::__construct();
        $this->_ci = &get_instance();
        //获取蓝景编号
		$this->_ljyunId = $this->_ci->common->getLjyunIdByForm("ljyunId");
    }
	
	/**
     * 计算会员卡折扣
     *
	 * @param id 订单id
     * @return response
     */
    public function payment($id)
    {
        $id = intval($id);
		if(empty($id))
		{
			return '获取订单id失败';
		}
        //获取交款历史记录
		$orderHistory = $this->CI->rest_client->post('orderHistory/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('order_real_id'=>$id),'order'=>'history_pay_id desc','limit'=>1)));
		//获取订单基本信息
		$this->orderBasic = $this->CI->rest_client->get('orderBasic/getItem',array('ljyunId'=>$this->_ljyunId, 'id'=>$id));
		if(isset($this->orderBasic->status) && $this->orderBasic->status != 1)
		{
			return '获取订单基本信息失败';
		}
		//没有会员信息
		if(empty($this->orderBasic->base->or_mc_id))
		{
			return true;
		}
		//商品
		$this->orderPro = $this->CI->rest_client->post('orderProduct/getList',array('ljyunId'=>$this->_ljyunId, 'data'=>array('where'=>array('orp_tmp_id'=>$id))));
		if(isset($this->orderPro->status) && $this->orderPro->status != 1)
		{
			return '获取订单商品信息失败';
		}
		//获取商户信息
		$merchantInfo = $this->CI->rest_client->post('merchantInfo/getList',array('ljyunId'=>$this->_ljyunId, 'data'=>array('platform'=>array('where'=>array('merchant_id'=>$this->orderBasic->base->or_merchant_id),'limit'=>1))));
		if(isset($merchantInfo->status) && $merchantInfo->status != 1)
		{
			return '获取商户信息失败';
		}
        //这里的应付额应该是交款历史记录的应付额
        $this->orderBasic->base->or_payable_value = $orderHistory[0]->pay_value;
        unset($orderHistory);
		//展位号
		$merchantShowId = $merchantInfo[0]->lease[0]->con_resource;
		//会员卡黑名单记录
		$blackList = $this->CI->rest_client->post('memberBlack/getList',array('data'=>array('where'=>array('black_merchant_show'=>$merchantShowId))));
		if(isset($blackList->status) && $blackList->status == 0)
		{
			return '获取会员卡黑名单信息失败';
		}
		//商户不在黑名单内,是在租商户
		if(isset($blackList->status) && $blackList->status == 2 && $merchantInfo[0]->lease[0]->contract_state == 1)
		{
			//会员卡信息
			$memberInfo = $this->CI->rest_client->get('memberInfo/getItem',array('ljyunId'=>$this->_ljyunId, 'id'=>$this->orderBasic->base->or_mc_id,'field'=>array('base'=>array('field'=>array('member_card_fid')))));
			if(isset($memberInfo->status) && $memberInfo->status == 0)
			{
				return '获取会员卡信息失败';
			}
			if(isset($memberInfo->status) && $memberInfo->status == 2)
			{
				return true;
			}
			//判断会员卡是否激活
			if($memberInfo->attr->consume_card_state == 1)
			{
				//会员卡类型 1.会员卡 2.金穗卡
				$memberCardType = $memberInfo->attr->consume_card_type;
				//会员卡等级
				$memberCardGrade = $memberInfo->attr->consume_grade;
				//查询会员卡折扣
				$agioInfo = $this->CI->rest_client->get('memberInfoAgio/getItem',array('id'=>$memberCardGrade));
				$this->agio = $agioInfo->agio_discount;
				//会员卡折扣大于等于1则不打折
				if($this->agio >= 1)
				{
					return true;
				}
				//判断是否全额付款
				if($this->orderBasic->base->or_full_payment == 1)
				{
					self::fullPayment();
				}else
				{
					self::partPayment();
				}
				//打折省掉的钱 = 原订单应付额 - 现订单应付额
				$agioPrice = $this->orderBasic->base->or_payable_value - $this->payable;
				//查询卖场和商户的分摊比例
				$apportion = $this->CI->rest_client->post('memberApportion/getList',array('ljyunId'=>$this->_ljyunId, 'data'=>array('where'=>array('apportion_fid'=>$memberCardType),'limit'=>1)));
				if(isset($apportion->status) && $apportion->status == 0)
				{
					return '获取卖场和商户的分摊比例信息失败';
				}
				//商户分摊比例
				$merchantApportion = $apportion[0]->apportion_merchant;
				//卖场分摊比例
				$martApportion = $apportion[0]->apportion_company;
				//商户分摊额
				$merchantAgio = $agioPrice * $merchantApportion;
				//卖场分摊额
				$martAgio = $agioPrice * $martApportion;
				//新增一条卖场会员卡分摊记录
				$martCardApportion = $this->CI->rest_client->post('martApportion/addItem',array('ljyunId'=>$this->_ljyunId, 'data'=>array('app_order_id'=>$id,'app_deduction_price'=>$agioPrice,'app_merchant_undertake'=>$merchantAgio,'app_mart_undertake'=>$martAgio,'app_type'=>1,'app_promotion_id'=>$this->orderBasic->base->or_mc_id,'app_discount'=>$this->agio)));
				if(isset($martCardApportion->status) && $martCardApportion->status == 0)
				{
					return '卖场会员卡分摊记录添加失败';
				}
				if($this->orderBasic->base->or_full_payment == 1)
				{
					//1.更新订单主表
					//商品总促销价
					$orProductSaleValue = $this->proPrices;					
					//应付额
					$orPayableValue = $this->payable;
					//合同总价款
					$orderPactValue = $orPayableValue;
					$orderUpd = $this->CI->rest_client->put('orderBasic/setItem',array('ljyunId'=>$this->_ljyunId, 'data'=>array('base'=>array('or_id'=>$id,'data'=>array('or_pact_value'=>$orderPactValue,'or_payable_value'=>$orPayableValue,'or_product_sale_value'=>$orProductSaleValue)))));
					if(isset($orderUpd->status) && $orderUpd->status == 0)
					{
						return '全额付款订单表更新失败';
					}
					//2.更新订单商品表
					foreach($this->proPrice as $v)
					{
						$proUpd = $this->CI->rest_client->put('orderProduct/setItem',array('ljyunId'=>$this->_ljyunId, 'data'=>array('id'=>$v['id'],'data'=>array('orp_pro_sale_price'=>$v['price'],'orp_total_sale_price'=>$v['prices']))));
						if(isset($proUpd->status) && $proUpd->status == 0)
						{
							return '全额付款订单商品表更新失败';
						}
					}
				}else
				{
					//更新订单主表
					//商品总促销价
					$orPayableValueP = $this->payable;
					$orderUpdP = $this->CI->rest_client->put('orderBasic/setItem',array('ljyunId'=>$this->_ljyunId, 'data'=>array('base'=>array('or_id'=>$id,'data'=>array('or_payable_value'=>$orPayableValueP)))));
					if(isset($orderUpdP->status) && $orderUpdP->status == 0)
					{
						return '部分付款订单表更新失败';
					}
				}
			}
		}
		return true;
    }
	
	/**
     * 全额付款
     *
     * @return response
     */
    public function fullPayment()
    {
		$data = array();//存放每件商品折后价钱
        $count = 0;//折后商品总价
		//另行支付
		$orOtherValue = $this->orderBasic->base->or_other_value;
        foreach($this->orderPro as $pro)
        {
			$price = 0;//单品折后价
			$priceAll = 0;
			$proPrice = $pro->orp_pro_sale_price == 0.00 ? $pro->orp_pro_price : $pro->orp_pro_sale_price;
            /* 2013-12-13 modify round to Util::dealDecimal */
            $price = Util::dealDecimal($proPrice * $this->agio);
			//总价 = 单价 * 数量
			$prices = Util::dealDecimal($price * $pro->orp_pro_account);
            $count += $prices;
            $data[] = array(
                "id"=>$pro->orp_id,
                "price"=>$price,
				"prices"=>$prices
            );
        }
		$this->proPrice = $data;
		$this->proPrices = $count;
		$this->payable = $count + $orOtherValue;
    }

	/**
     * 部分付款
     *
     * @return response
     */
    public function partPayment()
    {
        /* 2013-12-13 modify round to Util::dealDecimal */
		$this->payable = Util::dealDecimal($this->orderBasic->base->or_payable_value * $this->agio);
        //另行支付
		$orOtherValue = $this->orderBasic->base->or_other_value;
        //合同总价
		$orPactValue = $this->orderBasic->base->or_pact_value;
        //尾款
        $endValue = $orPactValue - $this->orderBasic->base->or_payable_value;
        //判断如果有另行支付,并且尾款<另行支付,只对商品总价款进行打折
        if($endValue < $orOtherValue)
        {
            //最终应付额 = 应付额-(商品总价*(1-折扣))
            $this->payable = $this->orderBasic->base->or_payable_value - Util::dealDecimal($this->orderBasic->base->or_product_sale_value * (1-$this->agio));
        }
    }
}
