<?php
// This file is part of Moodle - http://moodle.org/
//
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
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Moodle India Information Solutions
 * @package local_units
 */
namespace local_units\upload;

use core_text;
use core_user;
use DateTime;
use html_writer;
use stdClass;
use context_system;

class syncfunctionality {
	private $data;
    private $errors = array();
    private $mfields = array();
    private $warnings = array();
    private $wmfields = array();
    private $errorcount = 0;
    private $warningscount = 0;
    private $updatesupervisor_warningscount = 0;
    private $errormessage;
    private $insertedcount = 0;
    private $updatedcount = 0;
    private $formdata;

    public function __construct($data=null) {
        $this->data = $data;
    }// end of constructor

    public function main_hrms_frontendform_method($cir, $filecolumns, $formdata) {
    	global $DB, $USER, $CFG;
    	// $systemcontext = context_system::instance();
    	$inserted = 0;
        $updated = 0;
        $linenum = 1;
        while ($line = $cir->next()) {
        	$linenum ++;
        	$obj = new stdClass();
        	foreach ($line as $keynum => $value) {
        		if (!isset($filecolumns[$keynum])) {
                    continue;
                }
                $key = $filecolumns[$keynum];
                $obj->$key = trim($value);
        	}
        	$this->data[] = $obj;
        	$this->errors = [];
        	$this->warnings = [];
        	$this->mfields = [];
        	$this->wmfields = [];
        	$this->excel_line_number = $linenum;

        	if (!empty($obj->goal)) {
                $this->goaltosubject_validation($obj);
            }
        }
        if ($this->data) {
            $upload_info = '<div class="critera_error1"><h3 style="text-decoration: underline;">'.get_string('file_syncstatus', 'local_units').'</h3>';
            $upload_info .= '<div class=local_units_sync_success>'.get_string('inserted_msg', 'local_units', $this->insertedcount).'</div>';
            $upload_info .= '<div class=local_units_sync_error>'.get_string('errorscount_msg', 'local_units', $this->errorcount).'</div>
            </div>';
            $button = html_writer::tag('button', get_string('button','local_units'), array('class' => 'btn btn-primary'));
            $link = html_writer::tag('a', $button, array('href' => $CFG->wwwroot. '/local/units/index.php'));
            $upload_info .='<div class="w-full pull-left text-xs-center">'.$link.'</div>';
            mtrace($upload_info);
        } else {
            echo'<div class="critera_error">'.get_string('filenotavailable', 'local_units').'</div>';
        }
    }

    /**
     * validates the hierarchy.
     * @param $excel
     */
    public function goaltosubject_validation($excel) {
    	global $DB, $USER, $CFG;
    	$strings = new stdClass();
        $strings->goal =  $excel->goal;
        $strings->board =  $excel->board;
        $strings->class =  $excel->class;
        $strings->subject =  $excel->subject;
        $strings->excel_line_number = $this->excel_line_number;
        $this->insertedcount = 0;
        $this->data[] = $excel;
        if ($excel->goal) {
            $goalid = $this->get_hierarchy_ids($excel->goal, 0, 1);
            if ($goalid) {
			    $boardid = $this->get_hierarchy_ids($excel->board, $goalid, 2);
			    if ($boardid) {
					$classid = $this->get_hierarchy_ids($excel->class, $boardid, 3);
					if ($classid) {
						$ssql = "SELECT id
								   FROM {local_subjects}
								  WHERE TRIM(name) LIKE :subject
								    AND classessid = :classid";
						$subjectid = $DB->get_field_sql($ssql, ['subject' => trim($excel->subject), 'classid' => $classid]);
						if ($subjectid) {
							$subjectrec = $DB->get_record('local_subjects', ['id' => $subjectid]);
							$usql = "SELECT *
									   FROM {local_units}
									  WHERE courseid = :cid
										AND TRIM(name) LIKE :unit";
							$unitrec = $DB->get_record_sql($usql, ['cid' => $subjectrec->courseid, 'unit' => trim($excel->unit)]);
							if (empty($unitrec)) {
								$unitobj = new stdClass();
								$unitobj->name = $excel->unit;
								$unitobj->code = $subjectrec->code.'_'.$excel->unit;
								$unitobj->courseid = $subjectrec->courseid;
								$unitobj->timecreated = time();
								$unitobj->timemodified = 0;
								$unitobj->usercreated = $USER->id;
								$unitobj->usermodified = $USER->id;
								$insert = $DB->insert_record('local_units', $unitobj);
							} else {
								// continue;
								// throw new Exception("Error inserting units", 1);
							}
							$usql = "SELECT *
									   FROM {local_units}
									  WHERE courseid = :cid
										AND TRIM(name) LIKE :unit";
							$recunit = $DB->get_record_sql($usql, ['cid' => $subjectrec->courseid, 'unit' => trim($excel->unit)]);
							$chsql = "SELECT *
									    FROM {local_chapters}
									   WHERE TRIM(name) LIKE :chapter
									     AND courseid = :courseid
									     AND unitid = :unitid";
							$chapterrec = $DB->get_record_sql($chsql, ['chapter' => trim($excel->chapter), 'courseid' => $subjectrec->courseid, 'unitid' => $recunit->id]);
							if (empty($chapterrec)) {
								$chapterobj = new stdClass();
								$chapterobj->name = $excel->chapter;
								$chapterobj->code = $subjectrec->code.'_'.$excel->unit.'_'.$excel->chapter;
								$chapterobj->courseid = $subjectrec->courseid;
								$chapterobj->unitid = $recunit->id;
								$chapterobj->timecreated = time();
								$chapterobj->timemodified = 0;
								$chapterobj->usercreated = $USER->id;
								$chapterobj->usermodified = $USER->id;
								$insert = $DB->insert_record('local_chapters', $chapterobj);
							} else {
								// continue;
								// throw new Exception("Error inserting chapters", 1);
							}
							$chsql = "SELECT *
									    FROM {local_chapters}
									   WHERE TRIM(name) LIKE :chapter
									     AND courseid = :courseid
									     AND unitid = :unitid";
							$recchapter = $DB->get_record_sql($chsql, ['chapter' => trim($excel->chapter), 'courseid' => $subjectrec->courseid, 'unitid' => $recunit->id]);
							$tpcsql = "SELECT *
										 FROM {local_topics}
										WHERE TRIM(name) LIKE :topic
										  AND courseid = :courseid
										  AND unitid = :unitid
										  AND chapterid = :chapterid";
							$topicrec = $DB->get_record_sql($tpcsql, ['topic' => trim($excel->topic), 'courseid' => $subjectrec->courseid, 'unitid' => $recunit->id, 'chapterid' => $recchapter->id]);

							if (empty($topicrec)) {
								$topicobj = new stdClass();
								$topicobj->name = $excel->topic;
								$topicobj->code = $recchapter->code.'_'.$excel->topic;
								$topicobj->courseid = $subjectrec->courseid;
								$topicobj->unitid = $recunit->id;
								$topicobj->chapterid = $recchapter->id;
								$topicobj->timecreated = time();
								$topicobj->timemodified = 0;
								$topicobj->usercreated = $USER->id;
								$topicobj->usermodified = $USER->id;
								$insert = $DB->insert_record('local_topics', $topicobj);

								$this->insertedcount ++;
							} else {
								// continue;
								// throw new Exception("Error inserting topics", 1);
							}
						} 
						else {
							echo '<div class="local_units_sync_error">'.get_string('invalidsubject', 'local_units', $strings).'</div>';
				            $this->errors[] = get_string('invalidsubject', 'local_units', $strings);
				            $this->mfields[] = $excel->subject;
				            $this->errorcount ++;
						}
						// $this->unit_validation($excel);
					} 
					else {
						// continue;
			            echo '<div class="local_units_sync_error">'.get_string('invalidclass', 'local_units', $strings).'</div>';
			            $this->errors[] = get_string('invalidclass', 'local_units', $strings);
			            $this->mfields[] = $excel->class;
			            $this->errorcount ++;
					}
			    }
			     else {
			    	// continue;
		            echo '<div class="local_units_sync_error">'.get_string('invalidboard', 'local_units',$strings).'</div>';
		            $this->errors[] = get_string('invalidboard', 'local_units', $strings);
		            $this->mfields[] = $excel->board;
		            $this->errorcount ++;
			    }
            } 
            else {
            	// continue;
	            echo '<div class="local_units_sync_error">'.get_string('invalidgoal', 'local_units', $strings).'</div>';
	            $this->errors[] = get_string('invalidgoal', 'local_units', $strings);
	            $this->mfields[] = $excel->goal;
	            $this->errorcount ++;
            }
        }
         else {
        	// continue;
        	echo '<div class="local_units_sync_error">'.get_string('invalidgoal', 'local_units', $strings).'</div>';
            $this->errors[] = get_string('invalidgoal', 'local_units', $strings);
            $this->mfields[] = $excel->goal;
            $this->errorcount ++;
        }
    }

    /**
     * get hierarchy ids.
     * @param $excel
     */
    public function get_hierarchy_ids($name, $parent, $depth) {
    	global $DB;
    	$ssql = "SELECT id ";
    	$ssql .= " FROM {local_hierarchy} ";
    	$wheresql = " WHERE TRIM(name) LIKE :name
				    	AND parent = :parent
				    	AND depth = :depth ";
		$sql = $ssql . $wheresql;		    	
		$id = $DB->get_field_sql($sql, ['name' => trim($name), 'parent' => $parent, 'depth' => $depth]);
		return $id;
    }
    
    /**
     * get hierarchy records.
     * @param $excel
     */
    public function get_hierarchy_record($name, $parent, $depth) {
    	global $DB;
    	$ssql = "SELECT * ";
    	$ssql .= " FROM {local_hierarchy} ";
    	$wheresql = " WHERE TRIM(name) LIKE :name
				    	AND parent = :parent
				    	AND depth = :depth ";
		$sql = $ssql . $wheresql;		    	
		$record = $DB->get_record_sql($sql, ['name' => trim($name), 'parent' => $parent, 'depth' => $depth]);
		return $record;
    }
}
