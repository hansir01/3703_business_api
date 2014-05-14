<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 数据层基类，提供基本的CRUD操作
 *
 * @author: zg
 * @date: 2013-11-06
 */

require(APPPATH.'/libraries/REST_Controller.php');

class BLL_Controller extends REST_Controller 
{
	function __construct()
	{
		parent::__construct();
        //解决ajax跨域问题
        echo header("Access-Control-Allow-Origin:*");
        $this->rest_client->initialize(array("server"=>"http://dal.api.loc/index.php/"));
        //$this->rest_client->initialize(array("server"=>"http://dal.api.develop/index.php/"));
	}
  
}

