<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package local_onlineexams
 * @subpackage local_onlineexams
 */
require_once(dirname(__FILE__) . '/../../config.php');
// require_once('lib.php');

class local_onlineexams_renderer extends plugin_renderer_base {

    /*
 *  @method display table for showing repositories
 *  @return skill repository table
 */
    public function get_top_action_buttons_onlineexams(){
        global $CFG;

        $systemcontext =context_system::instance();;
        $data =  "<ul class='course_extended_menu_list'>
                <li>
                    <div class='coursebackup course_extended_menu_itemcontainer'>
                          <a id='extended_menu_syncstats' title='".get_string('addnewonlineexam', 'local_onlineexams')."' class='course_extended_menu_itemlink' href='javascript:void(0)' onclick ='(function(e){ require(\"local_onlineexams/onlineexamsAjaxform\").init({contextid:1, component:\"local_onlineexams\", callback:\"custom_onlineexams_form\", form_status:0, plugintype: \"local\", pluginname: \"onlineexams\"}) })(event)'><i class=\"icon fa fa-plus\" aria-hidden=\"true\"></i>
                          </a>
                      </div>
                </li>
            </ul>";
        return $data;
        }
    // public function onlineexmas_content($filter = false){
    //     global $USER;
    //     $systemcontext =(new \local_onlineexams\lib\accesslib())::get_module_context();
    //     $options = array('targetID' => 'manage_onlineexams','perPage' => 10, 'cardClass' => 'w_oneintwo', 'viewType' => 'table');

        // $options['methodName']='local_onlineexams_onlineexams_view';
        // $options['templateName']='local_onlineexams/onlineexams_view';
    //     $options = json_encode($options);

    //     $dataoptions = json_encode(array('userid' =>$USER->id,'contextid' => $systemcontext->id));
    //     $filterdata = json_encode(array());

    //     $context = [
    //         'targetID' => 'manage_onlineexams',
    //         'options' => $options,
    //         'dataoptions' => $dataoptions,
    //         'filterdata' => $filterdata
    //     ];

    //     if($filter){
    //         return  $context;
    //     }else{
    //         return  $this->render_from_template('local_onlineexams/cardPaginate', $context);
    //     }
    // }
    public function get_catalog_onlineexams($filter = false,$view_type='card') {
      global $USER;
      $categorycontext = 1;
    // $examtype = optional_param('examtype', '', PARAM_RAW);
      if ( empty($examtype) )
        //$examtype = 'online_exams';

      $templateName = 'local_onlineexams/onlineexams_view';
      $cardClass = 'col-md-6 col-12';
      $perpage = 12;
      if($view_type=='table'){
          $templateName = 'local_onlineexams/onlineexams_view_table';
          $cardClass = 'tableformat';
          $perpage = 20;
      } 
      $options = array('targetID' => 'manage_courses','perPage' => $perpage, 'cardClass' => 'col-lg-3 col-md-4 col-12', 'viewType' => $view_type);
      $options['methodName']='local_onlineexams_onlineexams_view';
      $options['templateName']= $templateName;
      $options = json_encode($options);
      $filterdata = json_encode(array('examtype' => $examtype));
      $dataoptions = json_encode(array('userid' => $USER->id, 'contextid' => 1,'examtype' => $examtype));
    
      $context = [
              'targetID' => 'manage_courses',
              'options' => $options,
              'dataoptions' => $dataoptions,
              'filterdata' => $filterdata
      ];
      if($filter){
          return  $context;
      }else{
          return  $this->render_from_template('local_onlineexams/cardPaginate', $context);
      }
  }
  public function render_form_status(\local_onlineexams\output\form_status $page) {
    $data = $page->export_for_template($this);
    return parent::render_from_template('local_onlineexams/form_status', $data);
    }
    public function get_userdashboard_onlineexams($tab, $filter = false,$view_type = 'card') {
        $context = context_system::instance();;
        
        
        $templateName = 'local_onlineexams/userdashboard_paginated';
        $cardClass = 'col-md-6 col-12';
        $perpage = 6;
        if($view_type=='table'){
            $templateName = 'local_onlineexams/userdashboard_paginated_catalog_list';
            $cardClass = 'tableformat';
            $perpage = 20;
        } 

        $options = array('targetID' => 'dashboard_onlineexams', 'perPage' => $perpage, 'cardClass' =>$cardClass, 'viewType' => $view_type);
        $options['methodName']='local_onlineexams_userdashboard_content_paginated';
        $options['templateName']= $templateName;
        $options['filter'] = $tab;
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $context->id));
        $context = [
                'targetID' => 'dashboard_onlineexams',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];
        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_onlineexams/cardPaginate', $context);
        }
    }
};
