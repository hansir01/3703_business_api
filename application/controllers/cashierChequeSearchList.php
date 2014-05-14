<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 业务逻辑层，收银系统，支票查询页面
 *
 * @author: weixxiaohua
 * @date: 2013-11-27
 */

class CashierChequeSearchList extends BLL_Controller {
    private $_contractNum = ''; //合同号
    private $_chequeNum = ''; //支票号
    private $_chequeRes = array();

	function __construct()
	{
		parent::__construct();
		$this->_contractNum = $this->get("contractNumber");
		$this->_chequeNum = $this->get("chequeNumber");
	}

	/**
     * 支票查询的对外接口
     *
     * @return array -$data/error 
     */
	public function startInit_get()
	{
		$deskId = $userId = '';
		$sumValue = $count = 0;
        //操作台ID
		$deskId = $this->get("deskId");
        //操作人员
		$userId = $this->get("userId");
		if(!empty($deskId) && !empty($userId))
		{
			//根据获取到的收银台号和操作人员id去product_conume表中取数据
			$whereProductConsume = array('consume_state '=>1,"consume_operator_id"=>$userId,"consume_client_id"=>$deskId);
			if(!empty($this->_contractNum))
			{
				$whereProductConsume['consume_pact_id'] = $this->_contractNum;
			}	
			$contractInfo = $this->rest_client->post("contract/getList",array("data"=>array("where"=>$whereProductConsume)));
			if(isset($contractInfo->status) && $contractInfo->status == 2)
			{
				$this->response(array("status"=>2,"error"=>"contractInfo: No record"),200);
			}else
			{
                //获取$this->_chequeRes;
                $this->_getChequeInfo($contractInfo);
                unset($contractInfo);
				//合计支票单数和支票的金额总和
				$count = 0; 
				foreach($this->_chequeRes as $v)
				{
					foreach($v as $v1)
					{
						$count ++;
						$sumValue +=$v1->cheque_value;
					}
				}
			}
			$data = array("statistics"=>array("count"=>$count,"sumValue"=>$sumValue),"chequeInfo"=>$this->_chequeRes,"condition"=>array("contractNumber"=>$this->_contractNum,"chequeNumber"=>$this->_chequeNum));
			$this->response($data,200);
			unset($count, $sumValue, $chequeInfo, $contractInfo, $data);
		}else
		{
			$this->response(array("status"=>0,"error"=>"Bad param"),500);
		}
	}

    /**
     * 获取支票信息
     *
     * @param array - $contractInfo
     * @return array - $data/error 
     */
    private function _getChequeInfo($contractInfo)
    {
        //循环product_conume取出所有的支票信息，组成一个数组
        foreach($contractInfo as $val)
        {
            if( empty($val->consume_back))
            {
                $chequeInfo = '';
                $where = array();
                $where['cheque_pact_id'] = $val->consume_pact_id; 
                if(!empty($this->_chequeNum))
                {
                    $where['cheque_fact_id'] = $this->_chequeNum;
                }	
                $chequeInfo = $this->rest_client->post("check/getList",array("data"=>array("where"=>$where)));
                if(!isset($chequeInfo->status))
                {
                    $this->_chequeRes[] = $chequeInfo;
                }
                else
                {
                    continue;
                }
            }
            else
            {
                continue;
            }
        }
    }


}

