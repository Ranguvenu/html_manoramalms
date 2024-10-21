<?php
namespace local_onlineexams\local;
class userdashboard_content extends \block_userdashboard\content{
	public function userdashboard_menu_content(){
		$returndata = array();
		$returndata['id'] = 'onlineexams';
		$returndata['order'] = 5;
		$returndata['pluginname'] = 'local_onlineexams';
		$returndata['tabname'] = 'inprogress';
		$returndata['status'] = 'inprogress';
		$returndata['class'] = 'userdashboard_menu_link';
		$returndata['iconclass'] = 'fa fa-desktop ot_icon';
		$returndata['label'] = get_string('onlineexams', 'block_userdashboard');
		$returndata['templatename'] = 'local_onlineexams/userdashboard_content';
		return $returndata;
	}
}