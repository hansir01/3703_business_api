<?php
/**
 * 下单系统,计算卖场折扣
 *
 * @author: yangzhen
 * @date: 2013-11-23
 */
include_once("Deduction.php");
class MartDeduction extends Deduction
{
    private $orderBasic;//订单基本信息
	private $orderPro;//商品信息
	private $areaAgio;//卖场折扣
	private $proPrice;//全额付款时商品主键和折扣价钱
	private $proPrices;//全额付款时商品折后价
	private $payable;//应付额
    private $_ci;

    public function __construct()
    {
        parent::__construct();
        $this->_ci = &get_instance();
        //获取蓝景编号
		$this->_ljyunId = $this->_ci->common->getLjyunIdByForm("ljyunId");
    }
	
	/**
     * 计算卖场折扣
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
		$now = date('Y-m-d 00:00:00');
        //获取交款历史记录
		$orderHistory = $this->CI->rest_client->post('orderHistory/getList',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('order_real_id'=>$id),'order'=>'history_pay_id desc','limit'=>1)));
		if(isset($orderHistory->status) && $orderHistory->status == 0)
		{
			return '获取交款历史记录失败!';
		}
		//获取卖场直接打折活动
		$martDiscounts = $this->CI->rest_client->post('martCurrent/getList',array('ljyunId'=>$this->_ljyunId, 'data'=>array('where'=>array('activity_start_time <='=>$now,'activity_end_time >='=>$now))));
		if(isset($martDiscounts->status) && $martDiscounts->status == 2)
		{
			//当前没有卖场折扣，返回true
			return true;
		}
		if(isset($martDiscounts->status) && $martDiscounts->status == 0)
		{
			return '获取区域卖场直接打折活动失败';
		}
		//获取订单基本信息
		$this->orderBasic = $this->CI->rest_client->get('orderBasic/getItem',array('ljyunId'=>$this->_ljyunId, 'id'=>$id));
		if(isset($this->orderBasic->status) && $this->orderBasic->status != 1)
		{
			return '获取订单基本信息';
		}
        //这里的应付额应该是交款历史记录的应付额
        $this->orderBasic->base->or_payable_value = $orderHistory[0]->pay_value;
        unset($orderHistory);
		//商品
		$this->orderPro = $this->CI->rest_client->post('orderProduct/getList',array('ljyunId'=>$this->_ljyunId, 'data'=>array('where'=>array('orp_tmp_id'=>$id))));
		if(isset($this->orderPro->status) && $this->orderPro->status != 1)
		{
			return '获取订单商品信息';
		}
		//获取商户信息
		$merchantInfo = $this->CI->rest_client->post('merchantInfo/getList',array('ljyunId'=>$this->_ljyunId, 'data'=>array('platform'=>array('where'=>array('merchant_id'=>$this->orderBasic->base->or_merchant_id),'limit'=>1))));
		if(isset($merchantInfo->status) && $merchantInfo->status != 1)
		{
			return '获取商户信息失败';
		}
		//展位号
		$merchantShowId = $merchantInfo[0]->lease[0]->con_resource;
		//展位号首字母
		$areaWord = substr($merchantShowId,0,1);
		foreach($martDiscounts as $martDis)
		{
			//当前活动id
			$activityId = $martDis->activity_id;
			//获取黑名单列表
			$blackList = $this->CI->rest_client->post('martBlack/getList',array('data'=>array('where'=>array('blacklist_company_show'=>$merchantShowId,'blacklist_activity_id'=>$activityId))));
            //如果商户不是在租
            if($merchantInfo[0]->lease[0]->contract_state != 1)
            {
                return '该商户不是在租商户！';
            }
			//商户不在黑名单内,是在租商户,
			if(isset($blackList->status) && $blackList->status == 2)
			{
				unset($blackList);
				//区域卖场折扣信息
				$areaInfo = $this->CI->rest_client->post('directArea/getList',array('data'=>array('where'=>array('area_word'=>$areaWord,'area_activity_id'=>$activityId),'limit'=>1,'order'=>'area_id desc')));
				if(isset($areaInfo->status) && $areaInfo->status == 2)
				{
					return true;//'该商户不在分摊设置的区域中
				}
				//卖场折扣
				$this->areaAgio = 1 - $areaInfo[0]->area_agio;

                //liangxifeng modify 2014-01-06 如果是全额付款，判断是否满足打折最低金额的值应该是商品总价,不包含另行支付
                $compareValue = $this->orderBasic->base->or_payable_value;
                if($this->orderBasic->base->or_full_payment == 1)
                {
                    $compareValue = $this->orderBasic->base->or_product_sale_value; 
                }
				//达到满足打折最低金额条件
				if($compareValue >= $areaInfo[0]->area_min_value)
				{
					//卖场封顶额
					$coping = $this->CI->rest_client->post('martCoping/getList',array('data'=>array('where'=>array('coping_merchant_id'=>$merchantShowId,'coping_activity_id'=>$activityId),'limit'=>1,'order'=>'coping_id desc')));
					if(isset($coping->status) && $coping->status != 1)
					{
						return '获取卖场封顶额信息失败';
					}
					//消费记录表信息,查询该商户累计使用的卖场承担金额,用来记录该商户是否达到卖场承担最大金额
					$companyValue = $this->CI->rest_client->post('contract/getList',array('data'=>array('where'=>array('consume_merchant_show'=>$merchantShowId,'consume_activity_id'=>$activityId),'field'=>'consume_company_value')));
					if(isset($companyValue->status) && $companyValue->status == 0)
					{
						return '获取消费记录表信息失败';
					}
					$totalCompanyValue = 0;
					if(!(isset($companyValue->status) && $companyValue->status == 2))
					{
                        //卖场已承担金额
						foreach($companyValue as $v)
						{
							$totalCompanyValue += $v->consume_company_value;
						}
                        unset($companyValue);
					}
					//卖场剩余承担的额度 = 卖场封顶额 - 卖场已承担金额
					$balance = intval($coping[0]->coping_company_value)-intval($totalCompanyValue);
					//判断是否全额付款,计算促销后最终应付款,$this->payable
					if($this->orderBasic->base->or_full_payment == 1)
					{
						self::fullPayment();
					}else
					{
						self::partPayment();
					}
					//打折省掉的钱 = 订单应付额 - 折后额
					$agioPrice = $this->orderBasic->base->or_payable_value - $this->payable;
                    //判断折后省掉的钱是否达到单笔最高优惠上限，如果达到則打折省掉的钱=单笔最高优惠额
                    if($agioPrice >= $areaInfo[0]->area_once_max_value)
                    {
                        $agioPrice = $areaInfo[0]->area_once_max_value;
                        //最终促销后应付额重新赋值 = 订单原始应付额 - 单笔最高优惠上限
                        $this->payable = Util::dealDecimal($this->orderBasic->base->or_payable_value-$agioPrice);
                        //下面操作数据库的时候，只修改order_real表的or_payable_value应付额
                        $this->orderBasic->base->or_full_payment = 0;
                    }                    
					//商户承担额 = 打折省掉的钱 * (商户分摊比例 / 区域分摊比例)
					$merchantAgio = Util::dealDecimal($agioPrice * ($areaInfo[0]->area_merchant_value/$areaInfo[0]->area_agio));

					//卖场承担额 = 打折省掉的钱 - 商户分摊额 (注意:区域分摊比例为0.20 = 卖场分摊比例0.15 + 商户分摊比例0.05)
					$companyPrice = $agioPrice - $merchantAgio;
					//存在可以使用的卖场剩余承担额度
					if($balance > 0)
					{
						//超出卖场剩余承担额度
						if($companyPrice > $balance)
						{
							//最终卖场承担
							$martAgio = $balance;
							//最终商户承担
							$merchantAgio = $agioPrice - $balance;
						}else
						{
							$martAgio = $companyPrice;
						}
					}else
					{
						$martAgio = 0;
						$merchantAgio = $agioPrice;
					}
					//新增一条卖场会员卡分摊记录
					$martCardApportion = $this->CI->rest_client->post('martApportion/addItem',array('ljyunId'=>$this->_ljyunId, 'data'=>array('app_order_id'=>$id,'app_deduction_price'=>$agioPrice,'app_merchant_undertake'=>$merchantAgio,'app_mart_undertake'=>$martAgio,'app_type'=>2,'app_promotion_id'=>$activityId,'app_discount'=>$this->areaAgio)));
					if(isset($martCardApportion->status) && $martCardApportion->status == 0)
					{
						return '卖场会员卡分摊记录添加失败';
					}
					if($this->orderBasic->base->or_full_payment == 1)
					{
						//1.更新订单主表
						//商品总促销价
						$orProductSaleValue = $this->proPrices;						
						//应付额 = 商品总促销价 + 另行支付方式
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
                }else
                {
                    return true;//如果不符合打折最低金额，則返回True，可以参加会员卡促销
                }
			}else
            {
                return true;//如果该商户在黑名单中則返回True，可以参加会员卡促销
            }
		}
		return 'success';
    }
	
	/**
     * 全额付款
     *
     * @return response
     */
    public function fullPayment()
    {
		$data = array();//存放每件商品折后价钱
        $count = 0;//折后总价
		//另行支付方式
		$orOtherValue = $this->orderBasic->base->or_other_value;
        foreach($this->orderPro as $pro)
        {
			$price = 0;//单品折后价
			$priceAll = 0;
			$proPrice = $pro->orp_pro_sale_price == 0.00 ? $pro->orp_pro_price : $pro->orp_pro_sale_price;
            $price = Util::dealDecimal($proPrice * $this->areaAgio);
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
		$this->payable = Util::dealDecimal($this->orderBasic->base->or_payable_value * $this->areaAgio);
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
            $this->payable = $this->orderBasic->base->or_payable_value - Util::dealDecimal($this->orderBasic->base->or_product_sale_value * (1-$this->areaAgio));
        }
    }
}
