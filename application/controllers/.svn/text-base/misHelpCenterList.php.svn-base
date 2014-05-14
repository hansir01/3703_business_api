<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 帮助中心列表
 *
 * author:王同猛
 * date:2013-12-3
 */

class misHelpCenterList extends BLL_Controller
{
	/**
     * 维护报错信息数据
     *
	 * @param int - msgType 报错类型
     * @return json - rest response data
     */
    public static function error($msgType)
    {
        $msg = array(
                1=>'Read help_infomation table error'
                );
        Util::errorMsg($msg[$msgType]);
    }
	
	/**
     * 获得帮助中心列表数据
     *
     * @return json - 文件数据或错误信息
     */
	public function startInit_get()
	{
		$title = $this->get('title');
		$currentPage = $this->get('currentPage') ? $this->get('currentPage') : 1;
		$perPage = $this->get('perPage') ? $this->get('perPage') : 10;
		$wLike = array();
		if(isset($title) && $title !=='')
		{
			$wLike = array('title'=>$title);
		}
		$articlesTotal = $this->rest_client->post('helpPaymentWord/getListNum',array('whereLike'=>$wLike));
		$offset = ((int)$currentPage-1)*(int)$perPage;/*偏移量*/
		$articlesRead = $this->rest_client->post('helpPaymentWord/getList',array('data'=>array('whereLike'=>$wLike,'offset'=>$offset,'limit'=>$perPage)));
		if(!empty($articlesRead) && empty($articlesRead->error))
		{
			$this->response(array('listInfo'=>$articlesRead,'condition'=>$title,'pageInfo'=>$articlesTotal));
		}
		else if($articlesRead->status==2)
		{
			$this->response(array('status'=>2,'error'=>'no record'),200);
		}
		else
		{
			self::error(1);
		}
	}
	
}
