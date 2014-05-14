<?php
/**
 * 收银系统,整单退款
 *
 * @author: yangzhen
 * @date: 2013-11-30
 */

class CashierReturnAllProduct extends BLL_Controller
{
	private $result = array();//存放各种支付方式的名称和钱数
    private $stepInfo = array();//用这个数组记录退款所做的操作

	function __construct()
    {
        parent::__construct();
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
                //startInit错误提示信息
                0=>'消费记录表主键id丢失',
                1=>'获取合同信息失败',
                2=>'该合同存在退款冲红记录，无法进行整单退款',
                3=>'该合同存在deduct_record扣款明细记录，无法进行整单退款',
                4=>'该合同存在charge_back记录，无法进行整单退款',
                5=>'获取商户信息失败',
                6=>'取会员卡黑名单信息失败',
                11=>'Rest error or no return',
                //endSubmit错误提示信息
                20=>'consumeBack is empty',
                22=>'获取合同退款信息失败',
                24=>'消费记录表添加记录失败',
                25=>'扣款类型字典表获取信息失败',
                26=>'扣款明细表添加记录失败',
                27=>'退货记录表添加记录失败',
                28=>'会员等级字典表获取记录失败',
                29=>'会员卡数据还原失败',
                30=>'原有会员信息获取失败',
                31=>'原有消费记录表退款状态更新失败',
				//wxh添加错误信息
				32=>'银行卡冲红失败',
				33=>'现金冲红失败',
				34=>'支票冲红失败',
				35=>'福卡冲红失败',
				36=>'附加卡冲红失败'
                );
        $errorMsg = ' errorId : '.$msgType.', '.$msg[$msgType];
        Util::errorMsg($errorMsg, $responseData);
    }

	/**
     * 整单退款初始信息
     *
     * @return response
     */
    public function startInit_get()
    {
		//消费记录表主键id
		$consumeId = intval($this->get('consumeId'));
		if(empty($consumeId)) self::error(0);
		$msg['consumeId'] = $consumeId;
		//获取合同信息
		$contractInfo = $this->_getContractInfo($consumeId);
		//合同号
		$msg['contract'] = $contractInfo->consume_pact_id;
		//查询该合同号是否有退款信息，有则不能整单退款
        if(1!=$this->_chkIfCanReturn($contractInfo->consume_pact_id, $consumeId))
        {
            self::error(2);
        }
        
		//实收额 = 应退额
		$msg['factPrice'] = $msg['backPrice'] = Util::formatMoney($contractInfo->consume_fact_value);
		//已退额
		$msg['backedPrice'] = 0.00;
		//获取支付方式名称
		$payTypes = $this->rest_client->post('paymentType/getList',array('data'=>array('field'=>array('pay_name','s_name'))));
		foreach($payTypes as $p)
		{
			$payName[$p->s_name] = $p->pay_name;
		}
		//现金
		$cash = $this->rest_client->post('cashCoupon/getList',array('data'=>array('where'=>array('payform_cash >'=>0,'payform_consume_id'=>$consumeId),'field'=>array('payform_cash as value'))));
		$this->_check($cash,$payName['cash']);
		//购物券
		$cash = $this->rest_client->post('cashCoupon/getList',array('data'=>array('where'=>array('payform_trade >'=>0,'payform_consume_id'=>$consumeId),'field'=>array('payform_trade as value'))));
		$this->_check($cash,'购物券');
		//支票
		$trade = $this->rest_client->post('check/getList',array('data'=>array('where'=>array('cheque_value >'=>0,'cheque_consume_id'=>$consumeId),'field'=>array('cheque_value as value','cheque_fact_id as card'))));
		$this->_check($trade,$payName['cheque']);
		//银行卡
		$bank = $this->rest_client->post('bankCard/getList',array('data'=>array('where'=>array('value >'=>0,'consume_id'=>$consumeId),'field'=>array('value','fact_id as card'))));
		$this->_check($bank,$payName['bank']);
		//福卡
		$fCard = $this->rest_client->post('fCard/getList',array('data'=>array('where'=>array('value >'=>0,'consume_id'=>$consumeId),'field'=>array('value','fact_id as card'))));
		$this->_check($fCard,$payName['fu_card']);
		//附加卡
		$addCard = $this->rest_client->post('additionalCard/getList',array('data'=>array('where'=>array('way_value >'=>0,'way_fid'=>$consumeId),'field'=>array('way_value as value','way_cardid as card','way_type'))));
		if(!isset($addCard->status))
		{
			foreach($addCard as $add)
			{
				$data = array();
				//附加卡名称
				$addCardName = $this->rest_client->get('blessPaymentType/getItem',array('id'=>$add->way_type,'field'=>array('type_name')));
				//将一条附加卡记录变为二维数组才能调用check方法
				$data[] = $add;
				$this->_check($data,$addCardName->type_name);
			}
		}
		$msg['main'] = $this->result;		
        //得到商户信息
        $merchantInfo = $this->_getMerchantInfo($contractInfo->consume_merchant_id);
        //展位号
        $merchantShowId = '';
        if( !empty($merchantInfo[0]) && !empty($merchantInfo[0]->lease[0]) )
        {
            $merchantShowId = $merchantInfo[0]->lease[0]->con_resource;
        }
		//会员卡黑名单记录
		$blackList = $this->rest_client->post('memberBlack/getList',array('data'=>array('where'=>array('black_merchant_show'=>$merchantShowId))));
		if(isset($blackList->status) && $blackList->status == 0)
		{
			self::error(6);
		}
		//商户不在黑名单内
		if(isset($blackList->status) && $blackList->status == 2)
		{
			//会员卡信息
			$memberInfo = $this->rest_client->post('memberInfo/getList',array('data'=>array('base'=>array('where'=>array('member_card_fid'=>$contractInfo->consume_member_id),'order'=>'member_id desc','limit'=>1))));
			if(isset($memberInfo->status) && $memberInfo->status == 0)
			{
				$this->response(array('status'=>0,'error'=>'获取会员卡信息失败'),500);
			}
			if(isset($memberInfo->status) && $memberInfo->status == 2)
			{
				//没有会员卡信息
				$msg['card'] = 0;
			}else{
				$msg['card'] = 1;			
				//判断会员卡是否激活
				if($memberInfo[0]->attr->consume_is_use == 1)
				{
					//查询会员卡折扣
					$agioInfo = $this->rest_client->get('memberInfoAgio/getItem',array('id'=>$memberInfo[0]->attr->consume_grade));
					//会员等级，普通，银卡，金卡
					$msg['level'] = $agioInfo->agio_grade;
					//卡号
					$msg['cardId'] = $contractInfo->consume_member_id;
					//会员当前积分
					$msg['nowScore'] = $memberInfo[0]->attr->consume_integral;
					//扣除积分
					$msg['cutScore'] = (int)$contractInfo->consume_fact_value;
					//剩余积分
					$msg['leftScore'] = $msg['nowScore'] - $msg['cutScore'];
				}else
				{
                    /* modify 2013-12-13 这种情况也得能退款 */
                    $msg['card'] = 0;
                    $msg['cardMsg'] = '会员卡未激活';
					//$this->response(array('status'=>0,'error'=>'会员卡未激活'),200);
				}
			}
		}else
		{
            /* modify 2013-12-13 这种情况也得能退款 */
            $msg['card'] = 0;
            $msg['cardMsg'] = '会员卡在黑名单内';
		}
		$this->response($msg,200);
    }

    /**
     * 整单退款
     * 1. 冲红product_conume
     * 2. 写入扣款记录和退货记录
     * 3. 会员卡积分减少，等级变化;消费总额减少
     *
     * @return response
     */
	function endSubmit_put()
	{
		//消费记录表主键id
		$consumeId = intval($this->put('consumeId'));
        //从收银退的，还是财务退的
        $consumeBack = intval($this->put('consumeBack'));
        if(empty($consumeBack) || 0>=$consumeBack)
		{
            self::error(20);
		}
		//获取合同信息，通过主键得到一条记录
		$pactInfo = $this->_getContractInfo($consumeId);
		//查询该合同号是否有退款信息，有则不能整单退款
        if(1!=$this->_chkIfCanReturn($pactInfo->consume_pact_id, $consumeId))
        {
            self::error(2);
        }
        //还原会员卡数据，新积分 = 原有积分-(现金+银行卡);消费总额减少
        $this->_resetCardData($consumeId, $pactInfo->consume_member_id, $pactInfo->consume_fact_value);
        $nowTime = date('Y-m-d H:i:s'); //当前时间
		//扣款明细表添加记录
        $this->_setDeductRecord($consumeId, $pactInfo->consume_fact_value, $nowTime);
		//退货记录表添加记录
        $this->_setBackRecord($consumeId, $pactInfo->consume_pact_id, $pactInfo->consume_fact_value, $nowTime);

        //组织冲红数据
        $contractData = $this->_getContractData($pactInfo, $consumeBack);
        $contractInsert = $this->rest_client->post('contract/addItem',array('data'=>$contractData));
		if(isset($contractInsert->status) && $contractInsert->status == 0)
		{
            self:error(24);
		}
        //原有记录更新consume_back
        $tmpData = array('id'=>$consumeId,'data'=>array('consume_back'=>$consumeBack));
        $affectedRows = $this->rest_client->put('contract/setItem', array('data'=>$tmpData));
        if(empty($affectedRows->status) || 1!=$affectedRows->status)
        {
            self::error(31);
		}
		/*wxh2014-01-26增加需要冲红的数据表****start*****/
		//冲红银行卡，根据product_conume表中的主键id查询信息
		$bankInfo = $this->rest_client->post('bankCard/getList',array('data'=>array('where'=>array('consume_id'=>$consumeId))));
		if(!isset($bankInfo->status))
		{
			foreach($bankInfo as $bankVal)
			{
				$bankData = $this->_bankInfo($bankVal,$pactInfo->consume_date);
				$bankInsert = $this->rest_client->post('bankCard/addItem',array('data'=>$bankData));
				if(isset($bankInsert->status) && $bankInsert->status == 0)
				{
					self::error(32);
				}
			}
		}
		//冲红交易组成方式表payform_list,根据product_conume表中的主键ID
		$payformInfo = $this->rest_client->post('cashCoupon/getList',array('data'=>array('where'=>array('payform_consume_id'=>$consumeId))));
		if(!isset($payformInfo->status))
		{
			$payformData = $this->_payformInfo($payformInfo[0]);
			$payformInsert = $this->rest_client->post('cashCoupon/addItem',array('data'=>$payformData));
			if(isset($payformInsert->status) && $payformInsert->status == 0)
			{
				self::error(33);
			}
		}
		//冲红支票记录,根据product_conume表中的主键ID
		$chequeInfo = $this->rest_client->post('check/getList',array('data'=>array('where'=>array('cheque_consume_id'=>$consumeId))));
		if(!isset($chequeInfo->status))
		{
			foreach($chequeInfo as $chequeVal)
			{
				$chequeData = $this->_chequeInfo($chequeVal);
				$chequeInsert = $this->rest_client->post('check/addItem',array('data'=>$chequeData));
				if(isset($chequeInsert->status) && $chequeInsert->status == 0)
				{
					self::error(34);
				}
			}
		}
		//冲红福卡记录，根据product_conume表中的主键ID
		$blessInfo = $this->rest_client->post('fCard/getList',array('data'=>array('where'=>array('consume_id'=>$consumeId))));
		if(!isset($blessInfo->status))
		{
			foreach($blessInfo as $blessVal)
			{
				$blessData = $this->_blessInfo($blessVal);
				$blessInsert = $this->rest_client->post('fCard/addItem',array('data'=>$blessData));
				if(isset($blessInsert->status) && $blessInsert->status == 0)
				{
					self::error(35);
				}
			}
		}
		//冲红其他附加卡记录,根据product_conume表中的主键ID
		$paymentInfo = $this->rest_client->post('additionalCard/getList',array('data'=>array('where'=>array('way_fid'=>$consumeId))));
		if(!isset($paymentInfo->status))
		{
			foreach($paymentInfo as $paymentVal)
			{
				$paymentData = $this->_paymentInfo($paymentVal);	
				$paymentInsert = $this->rest_client->post('additionalCard/addItem',array('data'=>$paymentData));
				if(isset($paymentInsert->status) && $paymentInsert->status == 0)
				{
					self::error(36);
				}
			}
		}
		/*wxh2014-01-26增加需要冲红的数据表****end*****/
        unset($contractData,$pactInfo);
        $this->stepInfo[] = '消费记录表，冲红操作成功';
        $stepStr = implode($this->stepInfo,'<br/>');
		$this->response(array('status'=>1,'stepInfo'=>$stepStr),200);
	}

    //扣款明细表添加记录
    private function _setDeductRecord($consumeId, $consumeFactValue, $nowTime)
    {
		$deductType = $this->rest_client->post('accountInfo/getList',array('data'=>array('where'=>array('type_name'=>'product_conume'),'limit'=>1)));
		if(isset($deductType->status))
		{
            self::error(25);
		}
		$deductData = array(
			'deduct_type'=>$deductType[0]->id,
			'deduct_cause_id'=>$consumeId,
			'deduct_charge'=>$consumeFactValue,//扣费金额，即应退额
			'deduct_add_time'=>$nowTime
		);
		$deductInsert = $this->rest_client->post('spending/addItem',array('data'=>$deductData));
		if(isset($deductInsert->status) && $deductInsert->status == 0)
		{
            self::error(26);
		}
        $stepInfo[] = '扣款明细表deduct_record，写入数据成功';
    }

    //退货记录表添加记录
    private function _setBackRecord($consumeId, $pactId, $consumeFactValue, $nowTime)
    {
		//退货记录表添加记录
		$chargeData = array(
			'back_pact_number'=>$pactId, //退款的合同号
			'back_type'=>0, //0为整单退
			'back_charge'=>$consumeFactValue,
			'back_time'=>$nowTime,
			'back_pact_id'=>$consumeId,
			'back_negotiate'=>0.00 //整单退的协商退款金额为0.00
		);
		$chargeInsert = $this->rest_client->post('chargeBack/addItem',array('data'=>$chargeData));
		if(isset($chargeInsert->status) && $chargeInsert->status == 0)
		{
            self::error(27);
		}
        $stepInfo[] = '退款记录表charge_back，写入数据成功';
    }

    //由消费记录表主键id,获取合同信息
    private function _getContractInfo($consumeId)
    {
        $contractInfo = $this->rest_client->get('contract/getItem',array('id'=>$consumeId));
        //$this->response($contractInfo);
        if(isset($contractInfo->status))
        {
            self::error(1);
        }
        return $contractInfo;
    }

    //查询该合同号是否有退款信息，有则不能整单退款
    private function _chkIfCanReturn($pactId, $consumeId)
    {
        //检查有无冲红
		$contractBackInfo = $this->rest_client->post('contract/getList',array('data'=>array('where'=>array('consume_pact_id'=>$pactId,'consume_cope_value <'=>0, 'consume_back <>'=>0),'limit'=>1,'field'=>array('consume_id'))));
		if(isset($contractBackInfo->status) && $contractBackInfo->status == 0)
		{
            return 0;
		}
		if(!isset($contractBackInfo->status) && isset($contractBackInfo[0]->consume_id))
		{
            return 0;
		}
        //检查有无扣款明细
		$deductInfo = $this->rest_client->post('spending/getList',array('data'=>array('where'=>array('deduct_cause_id'=>$consumeId), 'limit'=>1, 'field'=>array('id'))));
		if(isset($deductInfo->status) && $deductInfo->status == 0)
		{
            self::error(3);
        }
        if(!isset($deductInfo->status) && isset($deductInfo[0]->id))
		{
            self::error(3);
		}
        //检查有无退货记录
		$chargeBackInfo = $this->rest_client->post('chargeBack/getList',array('data'=>array('where'=>array('back_pact_number'=>$pactId), 'limit'=>1, 'field'=>array('id'))));
        if(isset($chargeBackInfo->status) && $chargeBackInfo->status == 0)
		{
            self::error(4);
        }
        if(!isset($chargeBackInfo->status) && isset($chargeBackInfo[0]->id))
		{
            self::error(4);
		}
        return 1;
    }
    
    //获取商户信息
    private function _getMerchantInfo($merchantId)
    {
        $merchantInfo = $this->rest_client->post('merchantInfo/getList',array('data'=>array('base'=>array('where'=>array('merchant_id'=>$merchantId),'limit'=>1))));
        if(isset($merchantInfo->status) && $merchantInfo->status != 1)
        {
            self::error(5);
        }
        return $merchantInfo;
    }

	/**
     * 记录每一种支付方式的钱数
     *
     * @param item 支付信息
	 * @param name 支付方式名称
     * @return array 数组
     */
	private function _check($item,$name)
	{
		if(!isset($item->status))
		{
			foreach($item as $o)
			{
				$card = isset($o->card) ? $o->card : '';
				$this->result[] = array('name'=>$name,'carId'=>$card,'price'=>Util::formatMoney($o->value));
			}
		}
	}

    /**
     * 还原会员卡数据：积分、等级、消费总额
     *
     * @param int - $consumeId 消费记录表(product_conume)逐渐
     * @param int - $memberCardId 会员卡号
     * @param int - $contractFactValue 实收额
     * @return int/json - 会员等级/错误信息
     */
    private function _resetCardData($consumeId, $memberCardId, $contractFactValue)
    {
        //本次积分=(现金+银行卡)
        $thisTimePoints = $this->_getThisTimePoints($consumeId);
        if(0>=intval($thisTimePoints))
        {
            $this->stepInfo[] = '没有用现金和银行卡支付，不用进行还原积分数据';
            return ;
        }
        //获得原有会员信息,会员卡号前0
        $member = $this->rest_client->post('memberInfo/getList',array('data'=>array('base'=>array('where'=>array('member_card_fid'=>'0'.$memberCardId),'limit'=>1))));
        if( !empty($member->status) && 2==$member->status )
        {
            return array('is_member'=>0);
        }
        if(!empty($member) && count($member)>0 && !empty($member[0]->attr))
        {
            $attr = $member[0]->attr;
            $tmpArr = array(); //临时数组，存储退款后的会员数据
            //计算积分 = 原有积分-本次积分
            $tmpArr['consume_integral'] = intval($attr->consume_integral)-$thisTimePoints;
            if( 0>$tmpArr['consume_integral'] ) $tmpArr['consume_integral'] = 0;
            //计算消费总额 = 原有-实付额 liangxifeng modify 2014-01-11
            $tmpArr['consume_sum_value'] = intval($attr->consume_sum_value)-intval($contractFactValue);
            //计算新的会员等级
            $tmpArr['consume_grade'] = $this->_getReturnedMemberGrade($tmpArr['consume_sum_value']);
            //执行数据更新操作
            $tmpData = array('attr'=>array('consume_id'=>$attr->consume_id, 'data'=>$tmpArr,'limit'=>1));
            //$this->response(array('实付额：', $contractFactValue,'原有消费总额：',$attr->consume_sum_value,'计算后消费总额',$tmpArr['consume_sum_value']));
            $res = '';
            $res = $this->rest_client->put('memberInfo/setItem', array('data'=>$tmpData));
            if(!empty($res->error)) $this->response(array('status'=>0,'error'=>'error from dal : '.$res->error));
            if(empty($res->status) || 0>=intval($res->status))
            {
                self::error(29);
            }
            $this->stepInfo[] = '还原会员卡数据：积分、等级、消费总额，修改成功';
        } else {
            self::error(30);
        }
    }

    /**
     * 计算退款后的会员等级
     *
	 * @param int - returnedSumValue 退款后的会员消费总额
     * @return int/json - 会员等级/错误信息
     */
    private function _getReturnedMemberGrade($returnedSumValue)
	{
        /* 会员等级字典 */
		$memberGradeDict = $this->rest_client->post('memberInfoAgio/getList',array());
		if(!empty($memberGradeDict) && count($memberGradeDict)>0)
		{
            $tmp = 0; //临时变量，存储会员等级
            foreach( $memberGradeDict as $val )
            {
                if( intval($returnedSumValue) >= intval($val->agio_consume_value) )
                {
                    $tmp = $val->agio_id;
                }
            }
			return $tmp;
		}else
		{
			self::error(28);
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

    //计算本期积分(现金+银行卡)，退款时还原
    private function _getThisTimePoints($id)
    {
        $thisTimePoints = 0;
		$cash = $this->_sendRest('cashCoupon/getList', array('payform_cash'), array('payform_consume_id'=>$id));
        $bank = $this->_sendRest('bankCard/getList', array('fact_id','value'), array('consume_id'=>$id));
        if( $this->_checkRes($cash) )
        {
            foreach($cash as $val)
            {
                $thisTimePoints += intval($val->payform_cash); //计算本次积分
            }
        }

        if( $this->_checkRes($bank) )
        {
            foreach($bank as $val)
            {
                $thisTimePoints += intval($val->value); //计算本次积分
            }
        }
        
        return $thisTimePoints;
    }


    //冲红主表
    private function _getContractData($contractInfo, $consumeBack)
    {
        return array(
            'consume_pact_id'			=>$contractInfo->consume_pact_id,
            'consume_merchant_id'		=>$contractInfo->consume_merchant_id,
            'consume_pact_value'		=>$contractInfo->consume_pact_value*-1,
            'consume_cope_value'		=>$contractInfo->consume_cope_value*-1,
            'consume_fact_value'		=>$contractInfo->consume_fact_value*-1,
            'consume_date'				=>$contractInfo->consume_date,
            'consume_measure_date'		=>$contractInfo->consume_measure_date,
            'consume_build_date'		=>$contractInfo->consume_build_date,
            'consume_pay_type'			=>$contractInfo->consume_pay_type,
            'consume_payform'			=>$contractInfo->consume_payform,
            'consume_affrim'			=>$contractInfo->consume_affrim,
            'consume_favourable_value'	=>$contractInfo->consume_favourable_value,
            'consume_activity_id'		=>$contractInfo->consume_activity_id,
            'consume_sum_value'			=>$contractInfo->consume_sum_value*-1,
            'consume_merchant_value'	=>$contractInfo->consume_merchant_value*-1,
            'consume_company_value'		=>$contractInfo->consume_company_value*-1,
            'consume_poundage'			=>$contractInfo->consume_poundage*-1,
            'consume_operator_id'		=>$contractInfo->consume_operator_id,
            'consume_client_id'			=>$contractInfo->consume_client_id,
            'consume_form_id'			=>$contractInfo->consume_form_id,
            'consume_shop_id'			=>$contractInfo->consume_shop_id,
            'consume_state'				=>$contractInfo->consume_state,
            'consume_back'				=>$consumeBack, //1:收银退 or 2:财务退
            'consume_remark'			=>$contractInfo->consume_remark,
            'consume_member_id'			=>$contractInfo->consume_member_id,
            'consume_waste_id'			=>$contractInfo->consume_waste_id,
            'consume_chip_id'			=>$contractInfo->consume_chip_id,
            'consume_order_back'		=>$contractInfo->consume_order_back,
            'consume_merchant_show'		=>$contractInfo->consume_merchant_show,
            'consume_checkout_form'		=>$contractInfo->consume_checkout_form,
            'consume_cw_back'			=>$contractInfo->consume_cw_back,
            'confirm_sign'				=>$contractInfo->confirm_sign,
            'comfirm_date'				=>$contractInfo->comfirm_date,
            'consume_card_type'			=>$contractInfo->consume_card_type,
            'consume_card_present'		=>$contractInfo->consume_card_present*-1,
            'consume_card_cancel_vale'	=>$contractInfo->consume_card_cancel_vale*-1
        );		
	}
	//wxh添加冲红银行卡2014-01-26
	private function _bankInfo($bankInfo,$consume_date)
	{
		$date = strtotime(date('Y-m-d 00:00:00'));
		$consume_date = strtotime($consume_date);
		if($bankInfo->size == 1 && ($date < $consume_date+31536000))
		{
			$data = array(
				'fact_id'       =>$bankInfo->fact_id,
				'value'         =>$bankInfo->value*-1,
				'charge'        =>$bankInfo->charge*-1,
				'size'          =>$bankInfo->size,
				'autonum'       =>$bankInfo->autonum,
				'consume_id'    =>$bankInfo->consume_id,
				'cw_back_charge'=>0.00
			);
		}else
		{
			$data = array(
				'fact_id'       =>$bankInfo->fact_id,
				'value'         =>$bankInfo->value*-1,
				'charge'        =>'0.00',
				'size'          =>$bankInfo->size,
				'autonum'       =>$bankInfo->autonum,
				'consume_id'    =>$bankInfo->consume_id,
				'cw_back_charge'=>$bankInfo->charge*-1
			);
		}
		return $data;
	}
	//wxh添加冲红交易组成方式表数据2014-01-26
	private function _payformInfo($payformInfo)
	{
		return array(
				'payform_type'        =>$payformInfo->payform_type,
				'payform_money'       =>$payformInfo->payform_money,
				'payform_handling_fee'=>$payformInfo->payform_handling_fee,
				'payform_pact_id'     =>$payformInfo->payform_pact_id,
				'payform_cash'        =>$payformInfo->payform_cash*-1,
				'payform_bankcard'    =>$payformInfo->payform_bankcard*-1,
				'payform_vip'         =>$payformInfo->payform_vip*-1,
				'payform_fu_card'     =>$payformInfo->payform_fu_card*-1,
				'payform_trade'       =>$payformInfo->payform_trade*-1,
				'payform_trade_fee'   =>$payformInfo->payform_trade_fee*-1,
				'payform_check'       =>$payformInfo->payform_check*-1,
				'payform_other'       =>$payformInfo->payform_other,
				'payform_operator_id' =>$payformInfo->payform_operator_id,
				'payform_remark'      =>$payformInfo->payform_remark,
				'payform_card_number' =>$payformInfo->payform_card_number,
				'payform_bankcardid'  =>$payformInfo->payform_bankcardid,
				'payform_fu_card_id'  =>$payformInfo->payform_fu_card_id,
				'payform_vip_id'      =>$payformInfo->payform_vip_id,
				'payform_consume_id'  =>$payformInfo->payform_consume_id,
				'id_fee'              =>$payformInfo->id_fee,
				'id_apportion'        =>$payformInfo->id_apportion
		);
	}
	//wxh添加冲红支票数据2014-01-26
	private function _chequeInfo($chequeInfo)
	{
		return array(
			'cheque_fact_id'   =>$chequeInfo->cheque_fact_id,
			'cheque_value'     =>$chequeInfo->cheque_value*-1,
			'cheque_state'     =>$chequeInfo->cheque_state,
			'cheque_pact_id'   =>$chequeInfo->cheque_pact_id,
			'cheque_date'      =>$chequeInfo->cheque_date,
			'cheque_remark'    =>$chequeInfo->cheque_remark,
			'cheque_consume_id'=>$chequeInfo->cheque_consume_id
		);
	}
	//wxh添加冲红福卡数据2013-01-26
	private function _blessInfo($blessInfo)
	{
		return array(
			'fact_id'=>$blessInfo->fact_id,
			'value'=>$blessInfo->value*-1,
			'charge'=>$blessInfo->charge*-1,
			'invoice'=>$blessInfo->invoice,
			'consume_id'=>$blessInfo->consume_id,
			'cw_back'=>$blessInfo->cw_back
		);
	}
	//wxh添加冲红附加卡数据2013-01-26
	private function _paymentInfo($paymentInfo)
	{
		return array(
			'way_fid'=>$paymentInfo->way_fid,
			'way_type'=>$paymentInfo->way_type,
			'way_value'=>$paymentInfo->way_value*-1,
			'way_fee'=>$paymentInfo->way_fee*-1,
			'way_rade'=>$paymentInfo->way_rade,
			'way_netvalue'=>$paymentInfo->way_netvalue*-1,
			'way_cardid'=>$paymentInfo->way_cardid,
			'way_date'=>date('Y-m-d H:i:s'),
			'way_state'=>$paymentInfo->way_state,
			'way_checkdate'=>$paymentInfo->way_checkdate,
			'way_operator'=>$paymentInfo->way_operator,
			'way_invoice'=>$paymentInfo->way_invoice
		);
	}
}
