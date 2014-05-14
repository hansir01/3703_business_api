<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *业务逻辑层，会员详情页面
 *
 * @author: wxh
 * @date: 2013-12-4
 */

class MisMemberSearchDetail extends BLL_Controller {

	function __construct()
	{
		parent::__construct();
	}

	/**
     * 会员信息的详情页面接口
     *
     * @return array -$data/error 
     */
	public function startInit_get()
	{
		$memberId = '';
		//会员信息主键id
		$memberId = $this->get("memberId");
		if(!empty($memberId))
		{	
			$memberInfo = $this->rest_client->post("memberInfo/getList",array("data"=>array("base"=>array("where"=>array("member_id"=>$memberId),"field"=>array("member_name","member_phone","member_address","member_job","member_email","member_card","member_id")),"attr"=>array("field"=>"--"))));
			if(isset($memberInfo->status) && $memberInfo->status == 2)
			{
				$this->response(array("status"=>2,"error"=>"No record"),200);
			}
			$this->response($memberInfo[0],200);
		}else
		{
			$this->response(array("status"=>0,"error"=>"Bad param"),500);
		}
	}
}
?>
