<?php
/*
 *业务系统,合同扣费比例获取
 *@author : hanshaobo
 *date: 2014-01-08
 */
class MisCompactFeeRatioGet extends BLL_Controller
{
	function __construct()
	{
		parent::__construct();
	}

	/**
	 *合同扣费比例
	 *
	 *@return response
	 *
	 * */
	function startInit_get()
	{
		//获取limit
		$limit = $this->get('limit');
		//获取合同扣费比例数据
		$deductData = $this->rest_client->post('contractDeduct/getList',array('limit' => $limit));
		if(!empty($deductData) && !isset($deductData->status))
		{
			$this->response($deductData,200);
		}else
		{
			$this->response(array('status'=>0,'error'=>'获取数据失败'),500);
		}
	}
}
