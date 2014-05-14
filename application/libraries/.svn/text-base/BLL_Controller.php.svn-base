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
    protected $_hostDAL = 'http://dal.api.loc/index.php/';

	function __construct()
	{
		parent::__construct();
        $this->rest->initialize(array("server"=>"http://ljdal.develop/index.php/"));
	}
  
}

