<?php
/**
 * 业务系统,合同扣费比例设置
 *
 * @author: yangzhen
 * @date: 2013-12-02
 */
class MisCompactFeeRatioSet extends BLL_Controller
{
	function __construct()
    {
        parent::__construct();
    }

	/**
     * 合同扣费比例设置
     *
     * @return response
     */
	function endSubmit_post()
	{
		//标示符，1为按比例扣费，2为固定扣费
		$flag = $this->post('flag');
		if(empty($flag))
		{
			$this->response(array('status'=>0,'error'=>'获取扣费类型参数失败'),500);
		}
		$percent = 0.00;
		$maxPrice = 0;
		$price = 0.00;
		$deductData = $this->rest_client->post('contractDeduct/getList',array('limit' => 1));
		if(!empty($deductData) && !isset($deductData->status)){
			$data['data']['id'] = $deductData[0]->deduct_id;
		}
		if(isset($deductData->status) && $deductData->status == 2){
			$this->response(array('status'=>0,'error'=>'获取数据失败'),500);
		}
		if($flag == 1)
		{
			//扣费比例
			$percent = $this->post('percent');
			$deduct_type = 2;
			if(empty($percent))
			{
				$this->response(array('status'=>0,'error'=>'获取扣费比例失败'),500);
			}
			//最大扣费金额
			$maxPrice = $this->post('maxPrice');
			if(empty($maxPrice))
			{
				$this->response(array('status'=>0,'error'=>'获取最大扣费金额失败'),500);
			}
		}else
		{
			$deduct_type = 1;
			//固定扣费金额
			$price = $this->post('price');
			if(empty($price))
			{
				$this->response(array('status'=>0,'error'=>'获取固定扣费金额失败'),500);
			}
		}
		//开始时间
		$startTime = $this->post('startTime');
		if(empty($startTime))
		{
			$this->response(array('status'=>0,'error'=>'获取开始时间失败'),500);
		}
		//扣款类型字典表
		/*
		$deductType = $this->rest_client->post('accountInfo/getList',array('data'=>array('where'=>array('type_name'=>'deduct_record'),'limit'=>1)));
		if(isset($deductType->status))
		{
			$this->response(array('status'=>0,'error'=>'扣款类型字典表获取信息失败'),500);
		}*/
		//整理数组
		$deductData = array(
			'deduct_type'=>$deduct_type,
			'deduct_fixed_charge'=>$price,
			'deduct_percent'=>$percent,
			'deduct_percent_max'=>$maxPrice,
			'deduct_start_time'=>$startTime
		);
		//插入数据
		$data['data']['data'] = $deductData;
//		$deductInsert = $this->rest_client->post('contractDeduct/addItem',array('data'=>$deductData));
		$deductInsert = $this->rest_client->put('contractDeduct/setItem',$data);
		if(isset($deductInsert->status) && $deductInsert->status == 0)
		{
			$this->response(array('status'=>0,'error'=>'合同扣费比例添加记录失败'),500);
		}
		$this->response(array('status'=>1),200);
	}
}
