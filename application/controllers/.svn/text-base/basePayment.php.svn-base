<?php
/*************************************************************************
> File Name: basePayment.php 结算收款页面
> Author: arkulo
> Mail: arkulo@163.com 
> Created Time: 2013年12月04日 星期三 13时33分40秒
*************************************************************************/
class basePayment extends BLL_Controller
{
    private $pactId,$copeValue,$factValue,$clientId,$operationId;
    private $cash = 0;
    private $bank = array();
    /**
     * 结算收款页面初始化函数
     * 
     * @param [string] pactId [合同号]
     * @param [string] operationStaff [操作人员中文名称]
     * @param [string] operationId [操作台编号]
     * @param [int] payType [1=>系统下单;2=>手动下单]
     * @return [array] array(应付款，可用结算方式，历史交款信息，[优惠券])
     */
    public function payStartInit_get()
    {
		//$this->rest_client->initialize(array("server"=>"http://ljdal.develop/index.php/"));
        //参数获取
        $param = $this->get("data");
        if(empty($param['pactId']) || empty($param['operationStaff']) || empty($param['operationId']) || empty($param['payType']))
        {
            $this->response(array("status"=>0,"error"=>"bad params"));
        }
        //应付额查询,$payType区分是下单交款还是手动交款
        switch($param['payType'])
        {
            case 1:
                $inParamOne = array("data"=>array(
                                    "base"=>array("where"=>array("or_sale_number"=>$param['pactId']),"field"=>"or_id,or_payable_value","order"=>"or_id desc","limit"=>1),
                                    "attr"=>array("field"=>"--"),
                                    "other_attr"=>array("field"=>"--")),
                                    "ljyunId"=>$this->common->getLjyunIdByContract($param['pactId'])
                                );
                $tmpOne = $this->rest_client->post("orderBasic/getList",$inParamOne);
                (isset($tmpOne->status)) && ($tmpOne->status==2) && $this->response(array("status"=>2,"message"=>"copeValue has no record"));
				//weixiaohua 2014-1-9 添加 应付款第一种情况
				$tmpOne[0]->base->or_payable_value = Util::formatMoney($tmpOne[0]->base->or_payable_value);
                break;
            //手动收款
            case 2:
                $inParamOne = array("data"=>array(
                                    "where"=>array("pact_id"=>$param['pactId']),"field"=>"id,payable_value","order"=>"id desc","limit"=>1
                            ));
                $tmpOneMan = $this->rest_client->post("orderManual/getList",$inParamOne);
//$this->response($tmpOneMan);
				//weixiaohua 2014-1-9 添加 应付款第二种情况
                $tmpOne = array();
				$tmpOne[0]['base']['or_payable_value'] = Util::formatMoney($tmpOneMan[0]->payable_value);
                //为了排错，response status=>0的会记录到errorlog
                //$this->response(array('status'=>0,'error'=>$tmpOne));
                break;
        }
        $copeValue = $tmpOne[0];
        //支付方式
        $inParamTwo = array("data"=>array("field"=>"pay_id,pay_name,s_name"));
        $payType = $this->rest_client->post("paymentType/getList",$inParamTwo);
        empty($payType) && $this->response(array("status"=>2,"error"=>"payType has no record"));
        //此合同历史交款信息
        $inParamThree = array("data"=>array("where"=>array("consume_pact_id"=>$param['pactId']),"field"=>"consume_fact_value,consume_date"));
        $historyPayment = $this->rest_client->post("contract/getList",$inParamThree);
        //组合结果数组
        $result = array("pactId"=>$param['pactId'],"operationId"=>$param['operationId'],"operationStaff"=>$param['operationStaff'],"copeValue"=>$copeValue,"payType"=>$payType,"historyPayment"=>$historyPayment);
        //数据返回 
        $this->response(_arrayToObject($result),200); 
    }


    /** (*_*) <--this's simling face!! ******华丽的分割线（上部分是页面初始化，下面的是页面提交所有用到的函数）*************/


    /**
     * 收款提交主体函数，接受参数，调度多表插入的各个函数
     *   laoda   <---(what's? 嘿嘿，行数太多，上下翻不容易，用特殊的词汇做一下搜索定位)
     * @param [array]  
     * @return [boolean] 
     */
    public function payEndSubmit_post()
    {
        //接收参数,合同号，应付额，实付额不能为空
        $this->pactId = $this->post("pactId");
        $this->copeValue = $this->post("copeValue");
        $this->factValue = $this->post("factValue");
        $this->operationId = $this->post("operationId");
        $this->clientId = $this->post("clientId");
        if(empty($this->pactId) || empty($this->factValue) || empty($this->copeValue) || empty($this->operationId) || empty($this->clientId))
        {
            $this->response(array("status"=>0,"error"=>"pactId/copeValue/pactValue/operationId/clientId must be not null"));
        }
        //获取其他支付方式参数
        $this->cash = $this->post("cash");
        $this->bank = $this->post("bank");
        $this->bless = $this->post("bless");
        $this->cheque = $this->post("cheque");
        $this->trade = $this->post("trade");
        $this->fuCard = $this->post("fu_card");
        $this->thirdPart["huikin"] = $this->post("huikin");
        $this->thirdPart['aosi'] = $this->post("aosi");
        $this->thirdPart['icbc'] = $this->post("icbc");
        $this->thirdPart['rekin'] = $this->post("rekin");
        $this->thirdPart['zkin'] = $this->post("zkin");
        $this->thirdPart['huilian'] = $this->post("huilian");
        $this->trade = $this->trade[0];//暂定为一条
        //调取所需数据
        $this->_getNeedData() || $this->_exception(1);
        //打印准备好的数据供测试使用
        //$this->_printData();exit;
        //插入主表
        $this->_addProductConsume() || $this->_exception(2);
        //插入现金和购物券（旧）
        if(!empty($this->cash) || !empty($this->trade))
        {
            $this->_addPayformList() || $this->_exception(3);
        }
        //插入银行卡数据
        if(!empty($this->bank))
        {
            $this->_addBank() || $this->_exception(4);
        }
        //插入福卡数据
        if(!empty($this->bless))
        {
            $this->_addBless() || $this->_exception(5);
        }
        //插入支票数据
        if(!empty($this->cheque))
        {
            $this->_addCheque() || $this->_exception(6);
        }
        //插入第三方支付卡
        foreach($this->thirdPart as $key=>$tp)
        {
            if(!empty($tp))
            {
                $this->_addThirdPart() || $this->_exception(7);
                break;
            }
        }
        //会员信息更新
        $this->_modifyMember() || $this->_exception(8);
        //返回结果
        $this->response(array("status"=>1,"key"=>$this->_key->key,"success"=>"submit success"));
    }
  
    /**
     * 程序异常处理函数
     */
    private function _exception($msgType)
    {
        $msg = array(
                1=>"查询数据出错，请联系技术支持",
                2=>"合同主表生成数据出错，请联系技术支持",
                3=>"现金与购物券表生成数据失败，请联系技术支持",
                4=>"银行卡数据生成错误，请联系技术支持",
                5=>"福卡数据生成错误，请联系技术支持",
                6=>"支票数据生成错误，请联系技术支持",
                7=>"第三方卡数据生成错误，请联系技术支持",
                8=>"会员信息更新失败，请联系技术支持"
                );
        $this->response(array("status"=>1,"error"=>$msg[$msgType]));        
    }

    /**
     * 打印测试函数
     *
     * @return no
     */
    private function _printData()
    {
        echo "<h1>memberCard(会员卡)</h1>"; 
        print_r($this->memberCard);
        echo "<h1>orderMember(会员分摊)</h1>";
        print_r($this->orderMember);
        echo "<h1>operatorFormId(当班提交)</h1>";
        print_r($this->operatorFormId);
        echo "<h1>promotion(促销打折)</h1>";
        print_r($this->promotion);
        echo "<h1>orderBase</h1>";
        print_r($this->orderBase);
        echo "<h1>merchantInfo</h1>";
        print_r($this->merchantInfo);
        echo "<h1>支付方式</h1>";
        print_r($this->payTypeCommon);
        print_r($this->payTypeThird);
    }


    /**
     * 生成收款记录所需要的各种数据，在这里进行统一查询
     * chacha
     * @return 所有查询到的数据均赋值给类属性
     */
    private function _getNeedData()
    {
		//$this->rest_client->initialize(array("server"=>"http://dal.api.develop/index.php/"));
        //支付方式
        $this->payTypeCommon = $this->rest_client->post("paymentType/getList");
        $this->payTypeThird = $this->rest_client->post("blessPaymentType/getList");
        //商户信息,商户编号
        $this->ljyunId = $this->common->getLjyunIdByContract($this->pactId);//substr($this->pactId,1,3);
        $paramOne['data']['platform'] = array('where'=>array('online_id'=>$this->ljyunId),'limit'=>1,'offset'=>0);
        $this->merchantInfo = $this->rest_client->post("merchantInfo/getList",$paramOne);
        if(empty($this->merchantInfo) || isset($this->merchantInfo->status)) return false;
        //订单合同额,测量日期,安装日期
        $paramTwo['data']['base'] = array('where'=>array('or_sale_number'=>$this->pactId),'limit'=>1,'offset'=>0);
        $paramTwo['ljyunId'] = $this->ljyunId;
        $this->orderBase = $this->rest_client->post("orderBasic/getList",$paramTwo);
        if(empty($this->orderBase) || isset($this->orderBase->status)) return false;
        //促销活动折扣，各自分摊承担
        $paramThree['data'] = array('where'=>array('app_order_id'=>$this->orderBase[0]->base->or_id,"app_type"=>2),'limit'=>1,'offset'=>0);
        $paramThree['ljyunId'] = $this->ljyunId;
        $this->promotion = $this->rest_client->post("martApportion/getList",$paramThree);
        //当班提交表插入并取得主键
        $this->_operatorProcess() || $this->response(array("status"=>0,"error"=>"operator_form_list key is null"));
        //如果有会员，需要会员卡号
        $paramFour['data'] = array('where'=>array('app_order_id'=>$this->orderBase[0]->base->or_id,"app_type"=>1),'limit'=>1,'offset'=>0);
        $paramFour['ljyunId'] = $this->ljyunId;
        $this->orderMember = $this->rest_client->post("martApportion/getList",$paramFour);

        if(!empty($this->orderBase[0]->base->or_mc_id))
        {
            $this->memberCard = $this->rest_client->get("memberInfo/getItem",array("id"=>$this->orderBase[0]->base->or_mc_id));
        }
        return true;
    }
    
    /**
     * 处理当班提交表的数据
     *
     * @return [boolean] 
     */
    private function _operatorProcess()
    {
        //查询当前收银员、收银台是否已经存在一个未提交的结算单
        $param['data'] = array('where'=>array('operator_client_id'=>$this->clientId,'operator_operator_id'=>$this->operationId,'operator_date'=>date("Y-m-d",time())),'limit'=>1,'offset'=>0,'order'=>"operator_id desc");
        $res = $this->rest_client->post("operatorForm/getList",$param);
        if(is_array($res))
        {
            $this->operatorFormId = $res[0]->operator_id;
            $this->pactChipId = $res[0]->operator_chip;
        }
        //如果不存在则插入一条新的数据
        if(isset($res->status))
        {
            $insertData['data'] = array("operator_client_id"=>$this->clientId,"operator_operator_id"=>$this->operationId,"operator_date"=>date("Y-m-d",time()),"operator_state"=>0,"operator_chip"=>1);
            $tmpA = $this->rest_client->post("operatorForm/addItem",$insertData);
            $this->operatorFormId = $tmpA->key;
            $this->pactChipId = 1;
        }
        //如果存在但状态为当班已提交，则重新插入
        else if(!empty($res[0]->operator_id) && $res[0]->operator_state!=0)
        {
            $insertData['data'] = array("operator_client_id"=>$this->clientId,"operator_operator_id"=>$this->operationId,"operator_date"=>date("Y-m-d",time()),"operator_state"=>0,"operator_chip"=>$res[0]->operator_chip+1);
            $tmpA = $this->rest_client->post("operatorForm/addItem",$insertData);
            $this->operatorFormId = $tmpA->key; 
            $this->pactChipId = $res[0]->operator_chip+1;
        }
        return empty($this->operatorFormId)?false:true;
    }

    /**
     * 主表插入函数product_conume
     * 过渡期：会员卡和卖场促销同时存在，屏蔽会员卡
     * 未来版本：会员卡和卖场促销可以同时折上折
     * 11
     * @return [boolean]
     */
    private function _addProductConsume()
    {
        $favourableValue = $junkZhou = (object)array('app_id' => 0,'app_order_id' => 0,'app_promotion_id' => 0,'app_deduction_price' => 0,'app_merchant_undertake' => 0,'app_mart_undertake' => 0,'app_type' => 0,'app_discount' => 1); 
        //先判定卖场促销，没有再判定会员卡 
        if(!isset($this->promotion->status))
        {
            $favourableValue = $this->promotion[0];
        }else if(isset($this->promotion->status) && !isset($this->orderMember->status))
        {
            $favourableValue = $junkZhou = $this->orderMember[0]; 
        }        
        //会员卡号码、卡等级
        $cardNumber = "";
        $cardLevel = 0;
        if(!empty($this->memberCard) && !isset($this->memberCard->status))
        {
            $cardNumber = "0".$this->memberCard->base->member_card_fid; 
            $cardLevel = $this->memberCard->base->member_card_level;
        }
        //流水号
        $waster_id = "DJ-".substr(date("Ymd",time()),2)."-".$this->clientId."-".padLeft($this->pactChipId,5,0);
        $inParam = array('data'=>array(
                    "consume_pact_id"=>$this->pactId,
                    "consume_merchant_id"=>$this->merchantInfo[0]->base[0]->merchant_id,
                    "consume_pact_value"=>$this->orderBase[0]->base->or_pact_value,
                    "consume_cope_value"=>$this->copeValue,
                    "consume_fact_value"=>$this->factValue,
                    "consume_date"=>date("Y-m-d H:i:s",time()),
                    "consume_measure_date"=>$this->orderBase[0]->base->or_measure_time,
                    "consume_build_date"=>$this->orderBase[0]->base->or_instal_time,
                    "consume_pay_type"=>0,
                    "consume_payform"=>0,
                    "consume_affrim"=>0,
                    "consume_favourable_value"=>$favourableValue->app_discount,//优惠率
                    "consume_activity_id"=>$favourableValue->app_promotion_id,
                    "consume_sum_value"=>$favourableValue->app_deduction_price,//共计优惠额度
                    "consume_merchant_value"=>$favourableValue->app_merchant_undertake,
                    "consume_company_value"=>$favourableValue->app_mart_undertake,
                    "consume_poundage"=>0,//手续费
                    "consume_operator_id"=>$this->operationId,
                    "consume_client_id"=>$this->clientId,
                    "consume_form_id"=>$this->operatorFormId,//当班提交表
                    "consume_shop_id"=>1,//门店编号
                    "consume_state"=>1,
                    "consume_back"=>0,
                    "consume_remark"=>"",
                    "consume_member_id"=>$cardNumber,//会员卡号
                    "consume_waste_id"=>$waster_id,
                    "consume_chip_id"=>$this->pactChipId,
                    "consume_order_back"=>null,
                    "consume_merchant_show"=>$this->merchantInfo[0]->lease[0]->con_resource,
                    "consume_checkout_form"=>0,//财务审核后的结算单编号
                    "consume_cw_back"=>0,
                    "confirm_sign"=>null,
                    "comfirm_date"=>null,
                    "consume_card_type"=>$cardLevel,
                    "consume_card_present"=>(1-$junkZhou->app_discount),
                    "consume_card_cancel_vale"=>$junkZhou->app_deduction_price
        ));
        $this->_key = $this->rest_client->post("contract/addItem",$inParam);
        return (1==$this->_key->status)?true:false;
    }

    /**
     * 添加现金和购物券（旧）数据
     *
     * xianjin
     * @return [boolean]
     */
    private function _addPayformList()
    {
        $inParam['data'] = array(    	
                'payform_type'=>'0',        
                'payform_money'=>'0.00',    	
                'payform_handling_fee'=>'0.00',    	
                'payform_pact_id'=>$this->pactId,//合同号        
                'payform_cash'=>$this->cash,    	
                'payform_bankcard'=>'NULL',        
                'payform_vip'=>'0.00',    	
                'payform_fu_card'=>'NULL',        
                'payform_trade'=>$this->trade['value'],//购物券金额     	
                'payform_trade_fee'=>'0.00',     	
                'payform_check'=>'NULL',       	
                'payform_other'=>'0.00',    	
                'payform_operator_id'=>$this->operationId,      	
                'payform_remark'=>'',    	
                'payform_card_number'=>'',    	
                'payform_bankcardid'=>'NULL',    	
                'payform_fu_card_id'=>'NULL',      	
                'payform_vip_id'=>'',    	
                'payform_consume_id'=>$this->_key->key,//product_conume表主键        
                'id_fee'=>' 0',        
                'id_apportion'=>' 0'
        );
        $this->_payformKey = $this->rest_client->post("cashCoupon/addItem",$inParam);
        return (1==$this->_payformKey->status)?true:false;
    }

    /**
     * 银行卡记录生成函数，银行卡可能会有多张卡 
     *
     * longduan
     * @return [boolean]
     */
    private function _addBank()
    {
        foreach($this->bank as $item)
        {
            //手续费
            $charge = floatOff($item['value']*$this->payTypeCommon[2]->pay_value);
            $charge = $charge>$this->payTypeCommon[2]->pay_max?$this->payTypeCommon[2]->pay_max:$charge;
            //大小额标识 1小额 2大额
            $bankSign = $charge>$this->payTypeCommon[2]->pay_max?2:1;
            $inParam['data'] = array(           
                    'fact_id'=>$item['cardNumber'],        
                    'value'=>$item['value'],       
                    'charge'=>$charge,         
                    'size'=>$bankSign,     
                    'autonum'=>$item['autonum'],   
                    'consume_id'=>$this->_key->key,
                    'cw_back_charge'=>'0.00'
                    );
            $this->_bankKey = $this->rest_client->post("bankCard/addItem",$inParam);
            if(1!=$this->_bankKey->status)return false;
        }
        return true;
    }

    /**
     * 福卡记录生成函数，福卡可能会有多张卡 
     *
     * afu
     * @return [boolean]
     */
    private function _addBless()
    {
        foreach($this->bless as $item)
        {
            //手续费
            $charge = floatOff($item['value']*$this->payTypeCommon[6]->pay_value);
            $inParam['data'] = array(           
                    'fact_id'=>$item['cardNumber'],        
                    'value'=>$item['value'],       
                    'charge'=>$charge,         
                    'invoice'=>0,     
                    'consume_id'=>$this->_key->key,
                    'cw_back'=>0
                    );
            $this->_blessKey = $this->rest_client->post("fCard/addItem",$inParam);
            if(1!=$this->_blessKey->status)return false;
        }
        return true;
    }

    /**
     * 支票记录生成函数，支票可能会有多张卡 
     *
     * zhipiao
     * @return [boolean]
     */
    private function _addCheque()
    {
        foreach($this->cheque as $item)
        {
            $inParam['data'] = array(           
                    'cheque_fact_id'=>$item['cardNumber'],        
                    'cheque_value'=>$item['value'],       
                    'cheque_state'=>0,
                    'cheque_date'=>'',         
                    'cheque_consume_id'=>$this->_key->key,
                    'cheque_pact_id'=>$this->pactId,
                    'cheque_remark'=>''
                    );
            $this->_chequeKey = $this->rest_client->post("check/addItem",$inParam);
            if(1!=$this->_chequeKey->status)return false;
        }
        return true;
    }

    /**
     * 第三方附加卡记录生成函数
     * 33
     * 
     * @return [boolean]
     */
    private function _addThirdPart()
    {
        //卡分类的基础数据处理
        $pay = array();
        foreach($this->payTypeThird as $key=>$payType)
        {
             $pay[$payType->type_ename] = $key;
        }
        //循环收款记录
        foreach($this->thirdPart as $k1=>$tmpItem)
        {
            //现在是一种卡，但是有多张
            if(empty($tmpItem))
            {
                continue;
            }
            foreach($tmpItem as $item)
            {
                //手续费利率
                $rade = $this->payTypeThird[$pay[$k1]]->type_rate;
                //手续费
                $charge = floatOff($item['value']*$rade);
                $charge = $charge>$this->payTypeThird[$pay[$k1]]->type_max?$this->payTypeThird[$pay[$k1]]->type_max:$charge;
                $inParam['data'] = array(      
                        'way_fid'=>$this->_key->key,//product_conume的主键    
                        'way_type'=>$this->payTypeThird[$pay[$k1]]->type_id,//payment_type的主键
                        'way_value'=>$item['value'],     
                        'way_fee'=>$charge,
                        'way_rade'=>$rade,
                        'way_netvalue'=>$item['value']-$charge,  
                        'way_cardid'=>$item['cardNumber'], 
                        'way_date'=>date("Y-m-d H:i:s",time()),   
                        'way_state'=>0,
                        'way_checkdate'=>'',
                        'way_operator'=>$this->operationId, 
                        'way_invoice'=>'1'
                        );
                $res = $this->rest_client->post("additionalCard/addItem",$inParam);
                if(1!=$res->status)return false;
            }
        }
        return true;
    }

    /**
     * 更新会员信息、积分和会员等级修改
     *
     * huiyuan
     * @return [boolean]
     */
    private function _modifyMember()
    {
        if(!empty($this->memberCard) && !isset($this->memberCard->status))
        {
            //积分只计算现金和银行卡之和,实行1:1政策
            $bankValue = 0;
            if(!empty($this->bank))
            {
                foreach($this->bank as $item)
                {
                    $bankValue += $item['value'];
                }
            }
            $totalValue = $this->cash+$bankValue;

            //积分有效期
            $limitDate = (date("Y",time())+1)."-12-31";
            //历史总消费金额加当前实际消费额度
            //Liangxifeng modify 2014-01-11,以下一行暂时注销掉
            //$pactValue = $this->memberCard->base->member_pact_value+$this->factValue;

            //Liangxifeng modify 最终会员表m_member_consume_list的消费总额 = 原始消费额+$pactValue
            $finalTotalVal = $this->memberCard->attr->consume_sum_value+$this->factValue;

            //查询总消费金额对应的会员等级
            //$inParamOne['data'] = array("where"=>array("agio_consume_value<="=>$pactValue),"order"=>"agio_id desc","limit"=>1,"field"=>"agio_id");
            //Liangxifeng modify 以下的"agio_consume_value <="=>$finalTotalVal,起初是"agio_consume_value<="=>$finalTotalVal,此处注意<=前边少了一个空格，害我调试了1小时
            $inParamOne['data'] = array("where"=>array("agio_consume_value <="=>$finalTotalVal),"order"=>"agio_id desc","limit"=>1,"field"=>"agio_id");
            $agioSet = $this->rest_client->post("memberInfoAgio/getList",$inParamOne);
            //更新m_member_consume_list表
            $inParam['data'] = array("attr"=>
                    array("consume_id"=>$this->memberCard->attr->consume_id,
                        "data"=>array(
                            //"consume_sum_value"=>$this->memberCard->attr->consume_sum_value+$pactValue,
                            "consume_sum_value"=>$finalTotalVal,
                            "consume_grade"=>$agioSet[0]->agio_id,
                            "consume_integral"=>$this->memberCard->attr->consume_integral+$totalValue,
                            "consume_integral_endtime"=>$limitDate,
                            "consume_date"=>date("Y-m-d H:i:s",time())
                            )
                        )
                    );
           $res = $this->rest_client->put("memberInfo/setItem",$inParam);
           if(1!=$res->status)return false;
        }else
        {
            //这里添加新会员
            return true;
        }
        return true;
    }



}
