<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *业务逻辑层，会员查询页面
 *
 * @author: wxh
 * @date: 2013-12-4
 */

class MisMemberSearchList extends BLL_Controller {

	function __construct()
	{
		parent::__construct();
	}

	/**
     * 会员信息列表
     *
     * @return array -$data/error 
     */
	public function startInit_get()
	{
		$data =array();
		$memberName = $memberPhone = $currentPage = '';
		$perPage = 0 ;
		$where = array();
		//会员名称
		$memberName = $this->get("memberName");
		//会员电话
		$memberPhone = $this->get("memberPhone");	
		//分页
		$currentPage = $this->get("page");
		$currentPage = empty($currentPage) ? 1 : $currentPage;
		$perPage = $this->get("perPage");//每页显示多少条
		$perPage = empty($perPage) ? 15 : $perPage;
		if(!empty($currentPage))
		{	
			if(!empty($memberPhone))
			{
				$where['member_phone'] = $memberPhone;
			}
			if(!empty($memberName))
			{
				$where['member_name'] = $memberName;
			}
			$memberTotal = $this->rest_client->post("memberInfo/getListNum",array("data"=>array("table"=>"base","where"=>$where)));//获取总记录数
			$offset =($currentPage-1)*$perPage;//偏移量
			$totalPage = ceil($memberTotal/$perPage);//总页数
			$memberInfo = $this->rest_client->post("memberInfo/getList",array("data"=>array("base"=>array("where"=>$where,"offset"=>$offset,"limit"=>$perPage,"field"=>array("member_name","member_phone","member_id")),"attr"=>array("field"=>"--"))));
			if(isset($memberInfo->status) && $memberInfo->status == 2)
			{
				$this->response(array("status"=>2,"error"=>"No record"),200);
			}
			$data=array("memberInfo"=>$memberInfo,"pageInfo"=>$totalPage,"condition"=>array("memberPhone"=>$memberPhone,"memberName"=>$memberName));
			$this->response($data,200);
		}else
		{
			$this->response(array("status"=>0,"error"=>"Bad param"),500);
		}
	}
}
?>
