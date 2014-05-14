<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 共用的方法, 
 * 与Util的区别是，Util里面都是public static func，
 * Util中放的是一些工具方法，
 * Common里面放的是要请求REST的涉及到业务逻辑的。
 *
 * 函数列表: 
 * 1. 通过合同号，得到云编号
 *
 * @author: zg
 * @date: 2013-12-11
 */

class Common
{
    protected $_ci;

    //全部定义的是static方法，所以不会调用构造方法
    function __construct() 
    { 
        $this->_ci = &get_instance();
    }

    /**
     * 得到表单的ljyundId，用于调用DAL私有库资源时切库
     *
     * @return mixed - string/false
     */
    public function getLjyunIdByForm()
    {
		$ljyunId = $this->_ci->get("ljyunId");//获取蓝景编号
        if(empty($ljyunId)) $ljyunId = $this->_ci->post("ljyunId");
        if(empty($ljyunId)) $ljyunId = $this->_ci->put("ljyunId");
        if(empty($ljyunId)) $ljyunId = $this->_ci->delete("ljyunId");
        if(empty($ljyunId))
        {
			$this->_ci->response(array('status'=>1,'error'=>'this BLL api must have a ljyunId with param'));
        }
        return $ljyunId;
    }
    
    /**
     * 通过合同号，得到云编号
     *
     * @param string - contract
     * @return mixed - string/false
     */
    public function getLjyunIdByContract($contract)
    {
        $flag = 1;
        switch($flag)
        {
            case 1: //从合同号前三位获得
                if( 4>=mb_strlen($contract) ) exit('in common lib say: 合同号必须大于4位');
                return ltrim(mb_substr($contract,1,3),0);
                break;

            case 2: //根据合同号去找展位号
                $merchantShow = $this->_ci->rest_client->post('contractNumber/getList',array("data"=>array("where"=>array("con_id"=>$contract),"field"=>"merchant_resource")));
                if(!isset($merchantShow->status))
                {
                    //根据展位号取K代码及蓝景云id
                    $merchantId = $this->_ci->rest_client->post('merchantInfo/getList',array("data"=>array("lease"=>array("where"=>array("con_resource"=>$merchantShow[0]->merchant_resource)))));
                    if(!isset($merchantId->status))
                    {
                        return $merchantId[0]->platform[0]->merchant_id;
                    }else
                    {
                        return "没有商户信息";
                    }
                }else
                {
                    return "没有此合同号相关信息";
                }
                break;
            default:
                break;
        }
    }

}

