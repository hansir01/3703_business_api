<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 自动分配合同号
 *
 * author:王同猛
 * date:2013-11-20
 */

class OrderAutoContract extends BLL_Controller
{
	private $mId;
	private $orderId;
	private $tname='merchant_pact_list';/*$tname是退款类型字典表deduct_type中的type_name的值，merchant_pact_deduct代表是合同退款*/
	function __construct()
	{
		parent::__construct();
		/*获取蓝景编号*/
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
                1=>'merchant_pact_deduct no data',
                2=>'deduct_type no data',
                3=>'Updata deduct_record error',
                4=>'Table deduct_type not found value',
                5=>'Table order field or_ payable_value <=0',
                6=>'deduct_type is not 1 or 2',
                7=>'update table merchant_pact_list state fail',
                8=>'update table order or_sale_number fail',
                9=>'order_real no data',
                10=>'Do not find enable contract or table merchant_pact_deduct not data',
                11=>'parameter ljyunId or orderRealId not found',
                12=>'order_real info get failed!'
                );
        Util::errorMsg($msg[$msgType]);
    }

	/**
     * 自动分配合同号并扣除合同费用
     *
	 * @param PUT方式传参
	 * @param string - ljyunId 商户云编号
	 * @param string - orderRealId 真实订单主键ID
     * @return json - 操作成功或失败
     */
	public function endSubmit_put()
	{
		$this->orderId = $this->put('orderRealId');
		$coses='';
		if($this->_ljyunId>0 && $this->orderId>0)
		{
            //Liangxifeng modify 2013-12-16 获取正式订单表信息,
            $orderData = $orderCon = $this->rest_client->get('orderBasic/getItem',array('ljyunId'=>$this->_ljyunId, 'id'=>$this->orderId));
            if(isset($orderData->status))
            {
                self::error(12);
            }
            //如果已经分配过合同号,直接返回数据
            if(!empty($orderData->base->or_sale_number))
            {
                $this->response(array('contractNumber'=>$orderData->base->or_sale_number,'addTime'=>$orderData->base->or_addtime,'payAble'=>$orderData->base->or_payable_value),201);	
            }
			/*获得该商户没使用的合同号*/	
			$contractFree=$this->rest_client->post('contractNumber/getList',array('data'=>array('where'=>array('is_new'=>1,'state'=>0,'ljyun_id'=>$this->_ljyunId),'limit'=>1, 'order'=>'pact_id asc')));
			if(!empty($contractFree) && empty($contractFree->error) && !empty($contractFree[0]->con_id))
			{
				if(!empty($orderCon) && empty($orderCon->error))
				{
					/*为订单绑定未使用的合同号*/	
					$addCon=$this->rest_client->put('orderBasic/setItem',array('ljyunId'=>$this->_ljyunId,'data'=>array('base'=>array('or_id'=>$this->orderId,'data'=>array('or_sale_number'=>$contractFree[0]->con_id)))));
					if(!empty($addCon) && empty($addCon->error) && $addCon->status>0)
					{
						/*修改合同号详情表合同号状态，改为已使用*/	
						$changeState=$this->rest_client->put('contractNumber/setItem',array('data'=>array('id'=>$contractFree[0]->pact_id,'data'=>array('state'=>1))));
						/*更新sale_contract*/
						$setSale = $this->rest_client->put('orderSale/setItem',array('ljyunId'=>$this->_ljyunId,'data'=>array('where'=>array('order_real_id'=>$this->orderId),'data'=>array('or_sale_number'=>$contractFree[0]->con_id))));
						if(!empty($changeState) && empty($changeState->error) && $changeState->status>0)
						{
							/*获取合同扣费比例表记录，记录只有一条，deduct_type=1取固定值,deduct_type=2取扣费比例*/
							$deduct=$this->rest_client->get('contractDeduct/getItem',array('id'=>2));
							$tdate=date('Y-m-d H:i:s',time());
                            if(isset($deduct->error))
                            {
                                self::error(1);	
                            }

                            /*获得退款字典表中合同退款记录的主键ID*/
                            $dictionary=$this->rest_client->post('accountInfo/getList',array('data'=>array('where'=>array('type_name'=>$this->tname),'limit'=>1)));
                            if(isset($dictionary->error))
                            {
                                self::error(2);	
                            }

							if($deduct->deduct_type==1)
							{
								$coses=$deduct->deduct_fixed_charge;
								if(!empty($dictionary[0]->type_name))
								{
									/*扣费明细记录表中添加具体扣费记录*/
									$spending=$this->rest_client->post('spending/addItem',array('data'=>array('deduct_type'=>$dictionary[0]->id,'deduct_cause_id'=>$contractFree[0]->pact_id,'deduct_charge'=>$coses,'deduct_add_time'=>$tdate)));
									if(!empty($spending) && $spending->status==1)
									{
										$this->response(array('contractNumber'=>$contractFree[0]->con_id,'addTime'=>$orderCon->base->or_addtime,'payAble'=>$orderCon->base->or_payable_value),201);	
									}
									else
									{
										self::error(3);	
									}
								}
								else
								{
									self::error(4);
								}
							}
							else if($deduct->deduct_type==2)
							{
								if($orderData->base->or_payable_value>0)
								{  
									//$contractPayment = $orderData->base->or_pact_value*$contractFree['m_pact_deduct']['deduct_percent'];
                                    //Liangxifeng modify
									$contractPayment = $orderData->base->or_pact_value*$deduct->deduct_percent;
									$coses=$contractPayment<$deduct->deduct_percent_max ? $contractPayment : $deduct->deduct_percent_max;
									if(!empty($dictionary[0]->type_name))
									{
										$spending=$this->rest_client->post('spending/addItem',array('data'=>array('deduct_type'=>$dictionary[0]->id,'deduct_cause_id'=>$contractFree[0]->pact_id,'deduct_charge'=>$coses,'deduct_add_time'=>$tdate)));
										if(!empty($spending) && $spending->status==1)
										{
											$this->response(array('contractNumber'=>$contractFree[0]->con_id,'addTime'=>$orderCon->base->or_addtime,'payAble'=>$orderCon->base->or_payable_value),201);	
										}
										else
										{
											self::error(3);	
										}
									}
									else
									{
										self::error(4);
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
					else
					{
						self::error(8);
					}
				}
				else
				{
					self::error(9);
				}
			}
			else
			{
				self::error(10);
			}
		}
		else
		{
			self::error(11);
		}
	}

}
