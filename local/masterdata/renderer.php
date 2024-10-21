<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * @package    local_masterdata
 * @copyright  2022 eAbyas Info Solutions<info@eabyas.com>
 * @author     Vinod Kumar  <vinod.p@eabyas.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use context_system;
class local_masterdata_renderer extends plugin_renderer_base {

    public function get_quizattempts($filter = false) {
        $systemcontext = context_system::instance();
        $quizid = optional_param('quizid', 0 , PARAM_INT);
        $options = array('targetID' => 'manage_quizattempts','perPage' => 5, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'card');
        $options['methodName']='local_masterdata_viewquizattempts';
        $options['templateName']='local_masterdata/viewquizattempts';
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id, 'quizid' => $quizid));
        $context = [
            'targetID' => 'manage_quizattempts',
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
        ];
        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('theme_horizon/cardPaginate', $context);
        }
    }
    public function global_filter($filterparams) {
        return $this->render_from_template('theme_horizon/global_filter', $filterparams);
    }
    public function listofquizattempts($filterparams) {
        global $DB,$CFG;
        $quizid = (int)$filterparams['quizid'];
        $quizmoduleid = $DB->get_field('modules','id',['name'=>'quiz']);
        $cmid  = (int)$DB->get_field('course_modules','id',['module'=>$quizmoduleid,'instance'=>$quizid]);
        $filterparams['backtoquizurl'] = $CFG->wwwroot.'/mod/quiz/view.php?id='.$cmid;
        echo $this->render_from_template('local_masterdata/listofquizattempts', $filterparams);
    }
}
