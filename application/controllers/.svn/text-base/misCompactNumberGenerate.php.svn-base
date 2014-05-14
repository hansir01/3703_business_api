<?php
/**
 * 业务系统,销售合同号预置
 *
 * @author: yangzhen
 * @date: 2013-12-03
 */
class MisCompactNumberGenerate extends BLL_Controller
{
	function __construct()
    {
        parent::__construct();
    }

	/**
     * 销售合同号预置初始
     *
     * @return response
     */
	function startInit_get()
	{
		//销售合同表
		$compactInfo = $this->rest_client->post('contractNumber/getList',array('data'=>array('order'=>'pact_id desc','limit'=>1,'field'=>array('con_id'))));
		if(isset($compactInfo->status) && $compactInfo->status == 0)
		{
			$this->response(array('status'=>0,'error'=>'获取销售合同信息失败'),500);
		}
		if(isset($compactInfo->status) && $compactInfo->status == 2)
		{
			$this->response(array(),200);
		}
		$this->response($compactInfo[0]->con_id,200);
	}

	/**
     * 销售合同号预置
     *
     * @return response
     */
	function endSubmit_put()
	{
		$tmpInfo = array();//存放在租商户的云编号
		$presetContract = array();//存放合同号后5位
		//预设数量
		$presetNumber = $this->put('presetNumber');
		if(empty($presetNumber))
		{
			$this->response(array('status'=>0,'error'=>'获取预设数量信息失败'),500);
		}
		//销售合同表
		$compactInfo = $this->rest_client->post('contractNumber/getList',array('data'=>array('order'=>'pact_id desc','limit'=>1,'field'=>array('con_id'))));
		if(isset($merchantInfo->status) && $merchantInfo->status == 0)
		{
			$this->response(array('status'=>0,'error'=>'获取销售合同信息失败'),500);
		}
		if(isset($compactInfo->status) && $compactInfo->status == 2)
		{
			$lastCompact = '00000';
		}else
		{
			$lastCompact = substr($compactInfo[0]->con_id,6,5);
		}
		//获取商户基本信息
		$merchantInfo = $this->rest_client->post('merchantInfomation/getList',array('data'=>array('limit'=>100,'field'=>array('merchant_id','merchant_kid'))));
		if(isset($merchantInfo->status) && $merchantInfo->status != 1)
		{
			$this->response(array('status'=>0,'error'=>'获取商户信息失败'),500);
		}
		foreach($merchantInfo as $k=>$o)
		{
			//获取展位号
			$showIdInfo = $this->rest_client->post('merchantContractSigned/getList',array('data'=>array('where'=>array('contract_state'=>1,'con_merchant_id'=>$o->merchant_kid),'order'=>'contract_cid desc','limit'=>1,'field'=>array('con_resource'))));
			if(isset($showIdInfo->status) && $showIdInfo->status == 0)
			{
				$this->response(array('status'=>0,'error'=>'获取展位号失败'),500);
			}
			//获取云编号
			$yunIdInfo = $this->rest_client->post('merchantPlatform/getList',array('data'=>array('where'=>array('lj_kid'=>$o->merchant_kid),'order'=>'online_id desc','limit'=>1,'field'=>array('merchant_id'))));
			if(isset($yunIdInfo->status) && $yunIdInfo->status == 0)
			{
				$this->response(array('status'=>0,'error'=>'获取云编号失败'),500);
			}
			if(!isset($showIdInfo->status) && !isset($yunIdInfo->status))
			{
				$tmpInfo[$k]['merchantId'] = $o->merchant_id;
				$tmpInfo[$k]['showId'] = $showIdInfo[0]->con_resource;
				$tmpInfo[$k]['yunId'] = $yunIdInfo[0]->merchant_id;
			}
		}
		if(empty($tmpInfo))
		{
			$this->response(array('status'=>0,'error'=>'获取商户展位号和云编号失败'),500);
		}
		//计算后5位
		for($i=1;$i<=$presetNumber;$i++)
		{
			$num = $lastCompact + $i;
			$presetContract[] = str_pad($num,5,'0',STR_PAD_LEFT);
		}
		//拼接合同号120+01234
		foreach($tmpInfo as $v)
		{
			$yunId = str_pad($v['yunId'],3,'0',STR_PAD_LEFT);
			foreach($presetContract as $lastFive)
			{
				$contractTmp = $yunId.$lastFive;
				//销售合同表添加记录
				$compactInsertData = array(
					'con_id'=>$contractTmp,
					'merchant_id'=>$v['merchantId'],
					'merchant_resource'=>$v['showId'],
					'contract_id'=>'',
					'state'=>0,
					'is_use'=>0,
				);
				$compactInfo = $this->rest_client->post('contractNumber/addItem',array('data'=>$compactInsertData));
				if(isset($compactInfo->status) && $compactInfo->status == 0)
				{
					$this->response(array('status'=>0,'error'=>'销售合同表添加记录失败'),500);
				}
			}
		}
		$this->response(array('status'=>1),200);
	}
}
