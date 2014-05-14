<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 收银系统合同退款详情
 *
 * author:王同猛
 * date:2013-11-30
 */

class CashierReturnShowCompactInfo extends BLL_Controller
{
    /* 销售合同号 */
	private $_contractNumber;
    /* 蓝景云编号,用到order_real, order_real_product */
    private $_ljyunId;
    /* 最新的支付id，用在有多条product_conume时 */
    private $_lastConsumeId;
    /* 商户id，区别于ljyunId */
    private $_merchantId;
    /* 能否单品退款 */
    private $_returnSingle=0;

    /* 现金支付额，用于判断是否为全额现金付款 */
    private $_pay_cash_value = 0;
    /* 支付名称字典 */
    private $_payNameArr = array();

    /* 去掉  合同基本信息：合同号，状态，生成日期 */
    //private $_baseInfo=array();
    /* 本次积分 */
    private $_this_time_points = 0;

	private $_salesNumber;
	/*促销编号对应的名称*/
	private $_sName=array('ZT'=>'整体优惠','TJ'=>'特价','TC'=>'套餐','JT'=>'阶梯');
	/*所有促销编号*/
	private $_sNames=array('1'=>'整体优惠','2'=>'特价','3'=>'套餐','4'=>'阶梯','5'=>'Vip','6'=>'会员卡','7'=>'卖场促销','8'=>'以旧换新');
	private $_salesFotn;
	private $_salesMart;
	private $_salesName=array();	

	public function __construct()
	{
		parent::__construct();
		$this->_contractNumber = $this->_getConstract();
		/*获取蓝景编号*/
		$this->_ljyunId = $this->common->getLjyunIdByContract($this->_contractNumber);
	}

	/**
     * 维护报错信息数据
     *
	 * @param int - msgType 报错类型
	 * @param string - responseData DAL的返回
     * @return json - rest response data
     */
    public static function error($msgType, $responseData='')
    {
        $msg = array(
                0=>'Parameter contractNumber is not found or illegal',
                1=>'Order_real_product no data',
                2=>'Virtual table no data',
                3=>'merchant_info table no record',
                4=>'Order table lack the necessary data',
                5=>'product_conume no data',
                6=>'Parameter contractNumber is not found or illegal',
                7=>'Get fotn_new_deal_real table error',
                8=>'Payment_type table no data',
                9=>'the product in order_real_product have no orp_model',
                10=>'Get payway failed',
                11=>'Rest error or no return',
                12=>'member grade error or no return',
                13=>'该合同已进行了当班结算，不能通过收银退款',
                14=>'获取会员信息失败，_getMember() error, rest hove no return',
                15=>'获取支付方式字典数据失败，_getPaynameArr() error, rest hove no return',
                16=>'商户展位号为空，请检查数据',
                );
        Util::errorMsg($msg[$msgType], $responseData);
    }

	/**
     * 查看合同退款内容
     *
     * @return json - 页面所需信息或错误信息
     */
	public function startInit_get()
    {
		$data = $payHistory = array();
        /*消费记录，可能有多条*/
        $consume = $this->_getConsume();
        /*取交易方式*/
        foreach($consume as $key=>$val)
        {
            $id = $val->consume_id;
            $payHistory[$key]['consume_id'] = $id;
            //是否已退
            if(1==$val->consume_back || 2==$val->consume_back) 
            {
                $payHistory[$key]['isReturn'] = 1;
            } else {
                $payHistory[$key]['isReturn'] = 0;
            }
            //支付日期
            $payHistory[$key]['payDate'] = mb_substr($val->consume_date,0,10);
            //实收额
            $payHistory[$key]['factVal'] = $val->consume_fact_value;
            //本次支付对应的普通支付方式，现金、银行卡、支票等
            $payHistory[$key]['paywayNormal'] = $this->_getPayway($id);
            //本次支付对应的附加支付方式，汇京卡等
            $payHistory[$key]['paywayOther'] = $this->_getPayotherway($id);
        }
        /* 最新的支付id，用在有多条product_conume时 */
        $this->_lastConsumeId = $consume[0]->consume_id;
        /* 支付历史 */
        $data['payHistory'] = $payHistory;
        /* 由展位号读取商户信息：展位，名称，电话 */
        $data['merchant'] = $this->_getShop($consume[0]->consume_merchant_show);
        /* 商户展位号 */
        $data['merchant']['showId'] = $consume[0]->consume_merchant_show;
        unset($consume, $id, $val, $payHistory);
        /* 订单信息 */
        $postData = array('ljyunId'=>$this->_ljyunId,'data'=>array('base'=>array('where'=>array('or_sale_number'=>$this->_contractNumber),'limit'=>1)));
        $orderData = $this->rest_client->post('orderBasic/getList', $postData);
        if(!empty($orderData) && empty($orderData->error) && count($orderData[0]->base)>0 && !empty($orderData[0]->base->or_pact_value) && !empty($orderData[0]->base->or_payable_value) && !empty($orderData[0]->base->or_addtime) && !empty($orderData[0]->base->or_customer_phone) && !empty($orderData[0]->base->or_merchant_id))
        {
            $base = $orderData[0]->base;
            $attr = $orderData[0]->other_attr;
            /* 顾客信息：姓名，手机，证件... ，读取order_real中的顾客信息*/
            $data['customer'] = $this->_getCustomerInfo($base);
            /* 会员信息：等级，原有积分，本期积分... */
            $data['member'] = $this->_getMember($base->or_customer_phone, $base);

            /* 交货信息：交货方式，测量日期，送货日期... */
            $data['delivery'] = $this->_getDeliveryInfo($base);
            /*验证是否可以单品退款*/
            $data['canReturnSingle'] = $this->_ifCanReturnSingle($base);

            /*另行支付总金额*/	
            $data['otherpayTotalPrice'] = Util::formatMoney($base->or_other_value);
            if( 0<intval($data['otherpayTotalPrice']) )
            {
                /* 另行支付相关金额 */
                $data['otherpay'] = $this->_getOtherpayPrice($attr);
            } else {
                $data['otherpay'] = array();
            }

            $data['order']	= $orderData[0];
            /*查询是否有会员卡打折*/
            $this->_salesMember=$this->_getSaleMember($base->or_id);
            /*验证是否是卖场促销*/
            $this->_salesMart=$this->_getMart($base->or_id);
            /*取虚拟商品*/	
            $virtual = $this->_getVirtual($base->or_id);
            if(!empty($virtual) && empty($virtual->error) && count($virtual)>0)
            {
                foreach($virtual as &$val)
                {
                    //echo '<pre>';var_dump($val);exit;
                    $val->vir_price=Util::formatMoney($val->vir_price);
                    $product=array();	
                    $product = $this->_getGoods($val->vir_id);
                    if(!empty($product) && empty($product->error) && count($product)>0)
                    {
                        foreach($product as $value)
                        {
                            $value->orp_pro_price=Util::formatMoney($value->orp_pro_price);
                            $value->orp_pro_sale_price=Util::formatMoney($value->orp_pro_sale_price);
                            $value->orp_total_sale_price=Util::formatMoney($value->orp_total_sale_price);
                            $value->orp_total_price=Util::formatMoney($value->orp_total_price);
                            /*取商品信息*/
                            if($value->orp_pro_type==1)
                            {
                                if(empty($value->orp_model)) self::error(9);
                                $isFotn = $this->_getFotn($base->or_id,$value->orp_model);
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
                        self::error(1);
                    }
                }
                $data['virtual'] = $virtual;	
            }
            else
            {
                self::error(2);
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
            
            $this->response($data);
        }
        else
        {
            self::error(4);
        }
	}

    /* 条件为全额+现金，全额查order_real */
    private function _ifCanReturnSingle($orderBaseInfo)
    {
        $can = 0; //临时变量
        //是否为全额
        if( !empty($orderBaseInfo->or_full_payment) && 1==$orderBaseInfo->or_full_payment )
        {
            //是否为全额现金付款，现金总额=实收额
            if( $this->_pay_cash_value == intval($orderBaseInfo->or_payable_value) ) $can = 1;
        }
        //$this->response(array($can,$this->_pay_cash_value,$orderBaseInfo,$orderBaseInfo->or_full_payment));
        return $can;
    }

    /* 获取合同号 */
    private function _getConstract()
    {
		$contractNumber = intval($this->get('contractNumber'));
        if( empty($contractNumber) ) self::error(0);
        return $contractNumber;
    }

    /* 顾客信息 */
	private function _getCustomerInfo($base)
    {
        $c = array(); //临时变量
        $c['name'] = $base->or_customer_name;
        $c['phone'] = $base->or_customer_phone;
        $c['idcard_type'] = $base->or_customer_idcard_type; //证件类型
        $c['idcard'] = $base->or_customer_idcard;
		$c['addr'] = $base->or_customer_addr; //送货地址
		//weixiaohua 2014-1-10 添加商户电话
        $c['store_tel'] = $base->or_store_tel;
        return $c;
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
		$mart = $this->rest_client->post('martApportion/getListNum',array('ljyunId'=>$this->_ljyunId,'where'=>array('app_order_id'=>$orderId,'app_type'=>2)));
		if(!empty($mart) && $mart>0)
		{
			return true;
		}else
		{
			return false;
		}
	}

	/**
	 * 付款记录数据显示,只取当班未提交的
	 * 
	 * @param string - contractNumber 合同号
	 * @return json - 合同号信息或错误信息
	 */
	private function _getConsume()
	{
		$consume = $this->rest_client->post('contract/getList',array('data'=>array('where'=>array('consume_pact_id'=>$this->_contractNumber),'order'=>'consume_id desc')));
        if(empty($consume[0]->consume_merchant_show)) self::error(16);
        if(!empty($consume->status) && 2==$consume->status)
        {
            self::error(5);
        }
		if(!empty($consume) && count($consume)>0 )
		{
            if(1!=$consume[0]->consume_state) self::error(13);
			return $consume;
		}else
		{
            self::error(5);
		}
	}

    //发送REST请求
    private function _sendRest($resource, $fieldArr=array(), $whereArr=array())
    {
        $data = array();
        if( !empty($fieldArr) )  $data['field'] = $fieldArr;
        if( !empty($whereArr) ) $data['where'] = $whereArr;
        return $this->rest_client->post($resource, array('data'=>$data));
    }

    //检查REST返回结果
    private function _checkRes($res)
    {
        if( !empty($res) )
        {
           if( empty($res->status) ) return true;
           if( 2==$res->status ) return false;
           if( 1==$res->status ) $this->response($res);
        }
        self::error(11);
    }

	/**
	 * 支付方式数据显示
	 * 
	 * @param string - id product_conume消费记录表主键id
	 * @return json - 支付方式信息或错误信息
	 */
	private function _getPayway($id)
    {
        $payway = array();
		$cash = $this->_sendRest('cashCoupon/getList', array('payform_cash'), array('payform_consume_id'=>$id));
        $bank = $this->_sendRest('bankCard/getList', array('fact_id','value'), array('consume_id'=>$id));
		$cheque = $this->_sendRest('check/getList', array('cheque_fact_id','cheque_value'), array('cheque_consume_id'=>$id));
        $fu = $this->_sendRest('fCard/getList', array('fact_id','value'), array('consume_id'=>$id));
        //获得支付名称字典，如：现金、银行卡等
        $this->_getPayNameArr();
        $n = 0; //计数器
        if( $this->_checkRes($cash) )
        {
            foreach($cash as $val)
            {
                $payway[$n]['payName'] = $this->_payNameArr['cash'];
                $payway[$n]['payCard'] = '';
                $payway[$n]['payVal'] = $val->payform_cash;
                $n++;
                $this->_this_time_points += intval($val->payform_cash); //计算本次积分
                $this->_pay_cash_value += intval($val->payform_cash); //计算现金支付额，用于判断是否为全额现金付款
            }
        }

        if( $this->_checkRes($bank) )
        {
            foreach($bank as $val)
            {
                $payway[$n]['payName'] = $this->_payNameArr['bank'];
                $payway[$n]['payCard'] = $val->fact_id;
                $payway[$n]['payVal'] = $val->value;
                $n++;
                $this->_this_time_points += intval($val->value); //计算本次积分
            }
        }

        if( $this->_checkRes($cheque) )
        {
            foreach($cheque as $val)
            {
                $payway[$n]['payName'] = $this->_payNameArr['cheque'];
                $payway[$n]['payCard'] = $val->cheque_fact_id;
                $payway[$n]['payVal'] = $val->cheque_value;
                $n++;
            }
        }

         if( $this->_checkRes($fu) )
        {
            foreach($fu as $val)
            {
                $payway[$n]['payName'] = $this->_payNameArr['fu_card'];
                $payway[$n]['payCard'] = $val->fact_id;
                $payway[$n]['payVal'] = $val->value;
                $n++;
            }
        }

		if(!empty($payway) && count($payway)>0)
		{
			return $payway;
        }
	}

    /*取支付方式名称拼数组，支付方式字典*/	
    private function _getPaynameArr()
    {
        $payname = $this->_sendRest('paymentType/getlist');
        if( empty($payname) ) self::error(15);
        foreach($payname as $val)
        {
            $this->_payNameArr[$val->s_name] = $val->pay_name;
        }
        unset($payname, $val);
    }

    /* 交货信息 */
    private function _getDeliveryInfo($base)
    {
        $d = array(); //临时变量or_get_self
        $d['get_self'] = (1==$base->or_get_self) ? '顾客自提' : '商户配送';
        $d['send_time'] = mb_substr($base->or_send_time,0,10); //送货日期
        $d['measure_time'] = mb_substr($base->or_measure_time,0,10);
        //liangxifeng modify install_time to instal_time 2014-01-10
        $d['instal_time'] = mb_substr($base->or_instal_time,0,10);
        return $d;
    }

    /* 另行支付相关金额 */
    private function _getOtherpayPrice($attr)
    {
        if(!empty($attr) && empty($attr->error))
        {
            $tmpArr = array(); //临时变量
            $tmpArr['transport_price']=Util::formatMoney($attr->other_transport_price); //运费
            $tmpArr['install_price']=Util::formatMoney($attr->other_install_price); //安装费
            $tmpArr['material_price']=Util::formatMoney($attr->other_material_price); //材料费
            return $tmpArr;
        } else {
            return array();
        }
    }

	/**
	 * 附加卡支付方式数据显示
	 * 
	 * @param string - id product_conume消费记录表主键id
	 * @return json - 附加卡支付方式信息或错误信息
	 */
	private function _getPayotherway($id)
	{
		$res = $this->_sendRest('additionalCard/getlist', array('way_type','way_cardid','way_value'), array('way_fid'=>$id));
        $payotherway = array();
		if(!empty($res) && empty($res->error))
		{
			foreach($res as $key=>$val)
			{
                /* 得到附加支付方式字典，如：汇京卡 */
				$payotherwayName = $this->rest_client->get('blessPaymentType/getItem',array('id'=>$val->way_type));
				if(!empty($payotherwayName) && empty($payotherwayName->error))
                {
					$payotherway[$key]['payName']=$payotherwayName->type_name;	
					$payotherway[$key]['payCard']=$val->way_cardid; //附加卡卡号
					$payotherway[$key]['payVal']=Util::formatMoney($val->way_value);
				}
				else
				{
					self::error(8);
				}
			}
		}
		if(!empty($payotherway) && count($payotherway)>0)
		{
			return $payotherway;
		}else
		{
			return array();
		}
	}

	/**
     * 订单确认页面商户数据显示
     *
     * @return json - 商户信息或错误信息
     */
	private function _getShop($showId)
	{
		$merchant = $this->rest_client->post('merchantInfo/getList',array('data'=>array('lease'=>array('where'=>array('con_resource'=>$showId, 'contract_state'=>1), 'limit'=>1, 'order'=>'contract_cid desc'))));
        //废弃：通过merchant_id读取share_merchant_online的商户信息
        //$merchant = $this->rest_client->post('merchantInfo/getList',array('data'=>array('platform'=>array('where'=>array('merchant_id'=>$this->_merchantId)))));
        if(isset($merchant->status) && 2==$merchant->status)
        {
            self::error(3);
        }
		if(!empty($merchant) && empty($merchant->error) && count($merchant)>0 )
        {
            $base = $merchant[0]->base[0];
            $m['name'] = $base->merchant_ch_name;
            //store表的资源没有，暂时先读这个
            $m['tel'] = $base->merchant_dute_tel1;
            return $m;
        } else {
            self::error(3);
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
     * 会员数据显示
     *
	 * @param string - $phone 顾客手机号
	 * @param object - $order 订单信息，为获取本次积分
     * @return json - 会员数据或错误信息
     */
	private function _getMember($phone, $order)
	{
		$member = $this->rest_client->post('memberInfo/getList',array('data'=>array('base'=>array('where'=>array('member_phone'=>$phone),'limit'=>1))));
        if( !empty($member->status) && 2==$member->status )
        {
            return array('is_member'=>0);
        }
		if(!empty($member) && count($member)>0)
		{
            $attr = $member[0]->attr;
            $m = array(); //临时变量
            $m['is_member']=1;
            /* 参考 payment1/payment_orderprint.php(原有的合同打印程序) */
            /* 会员等级 */
            $m['grade'] = $this->_getMemberGrade($attr->consume_grade);
            /* 本次积分 = 现金+银行卡 */
            $m['this_time_points'] = $this->_this_time_points; //在_getPayway()中计算
            /* 原有积分 = 总积分-本次 */
            $m['old_points'] = intval($attr->consume_integral)-$m['this_time_points'];
            $m['total_points'] = intval($attr->consume_integral); //累计积分
			return $m;
		}else
		{
            return array('is_member'=>0);
			//self::error(14);
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
        /* 会员等级字典 */
		$memberGrade = $this->rest_client->get('memberInfoAgio/getItem',array('id'=>$id));
		if(!empty($memberGrade) && count($memberGrade)>0)
		{
			return $memberGrade->agio_grade;
		}else
		{
			self::error(12);
		}
	}
	
}
?>
