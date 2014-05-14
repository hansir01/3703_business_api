<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 业务逻辑层，合同查询页面
 *
 * @author: wxh
 * @date: 2013-11-27
 */

class CashierCompactSearchList extends BLL_Controller {

	function __construct()
	{
		parent::__construct();
	}

	/**
     * 对外接口得到订单相关所有数据，调用数据资源接口进行数据保存
     *
	 * @param int - $deskId 台子号
	 * @param int - $userId 操作人员
	 * @param string - $contractNumber 合同号
	 * @param string - $merchantShow 展位号
	 * @param int - $currentPage 分页
     * @return array -$data/error 
     */
	public function startInit_get()
	{
		$deskId = $userId = $contractNum = $merchantShow = '';
		$perPage = 0;
		$where = array();
		$deskId = $this->get("deskId");
		$userId = $this->get("userId");
		$contractNum = $this->get("contractNumber");
		$merchantShow = $this->get("merchantShow");
		$currentPage = $this->get("page");
		$currentpage = empty($currentPage) ? 1 : $currentPage;
		$perPage = $this->get("perPage");//每页显示多少条
		$perPage = empty($perPage) ? 15 : $perPage;
		//根据获取到的收银台号和操作人员id去product_conume表中取数据
		if(!empty($deskId) && !empty($userId))
		{
			$where = array('consume_state '=>1,"consume_operator_id"=>"$userId","consume_client_id"=>"$deskId");
			if(!empty($contractNum))
			{
				$where['consume_pact_id'] = $contractNum;
			}
			if(!empty($merchantShow))
			{
				$where['consume_merchant_show'] = $merchantShow;
			}
			$contractTotal = $this->rest_client->post("contract/getListNum",array("where"=>$where));//获取总记录数
			$sumValue = 0;
			$offset =($currentPage-1)*$perPage;//偏移量
			$totalPage = ceil($contractTotal/$perPage);//总页数
			$contractInfo = $this->rest_client->post("contract/getList",array("data"=>array("where"=>$where,"offset"=>$offset,"limit"=>$perPage)));
			if(isset($contractInfo->status) && $contractInfo->status == 2)
			{
				$this->response(array("status"=>2,"error"=>"No record"),200);
			}else
			{
				foreach($contractInfo as $val)
				{
					$sumValue += $val->consume_fact_value;
				}
			}
			$data=array("statistics"=>array("count"=>$contractTotal,"sumValue"=>$sumValue),"contarctInfo"=>$contractInfo,"pageInfo"=>$totalPage,"condition"=>array("contractNumber"=>$contractNum,"merchantShow"=>$merchantShow));
			$this->response($data,200);
		}else
		{
			$this->response(array("status"=>0,"error"=>"Bad param"),500);
		}
	}
}
?>
