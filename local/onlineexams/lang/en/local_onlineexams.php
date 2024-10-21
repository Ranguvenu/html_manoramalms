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
 * @subpackage local_courses
 */
$string['pluginname'] = 'Online Exams';
$string['course'] = 'Course';
$string['organization']='Company';
$string['mooc'] = 'MOOC';
$string['classroom'] = 'Classroom';
$string['elearning'] = 'E-Learning';
$string['learningplan'] = 'Learning Path';
$string['type'] = 'Type';
$string['category'] = 'Category';
$string['enrolled'] = 'Enrollments';
$string['completed'] = 'Completions';
$string['manual_enrolment'] = 'Manual Enrollment';
$string['add_users']='<< Add Users';
$string['remove_users']='Remove Users >>';
$string['employeesearch']='Filter';
$string['agentsearch']='Agent Search';
$string['empnumber']='Learner ID';
$string['email']='Email';
$string['band'] = 'Band';
$string['departments']='Countries';
$string['sub_departments']='Commercial Unit';
$string['sub-sub-departments']='Sub Commercial Units';
$string['designation'] = 'Designation';
$string['im:already_in'] = 'The user "{$a}" was already enroled to this onlineexam';
$string['im:enrolled_ok'] = 'The user "{$a}" has successfully enroled to this onlineexam ';
$string['im:error_addg'] = 'Error in adding group {$a->groupe}  to onlineexam {$a->courseid} ';
$string['im:error_g_unknown'] = 'Error, unkown group {$a} ';
$string['im:error_add_grp'] = 'Error in adding grouping {$a->groupe} to onlineexam {$a->courseid}';
$string['im:error_add_g_grp'] = 'Error in adding group {$a->groupe} to grouping {$a->groupe}';
$string['im:and_added_g'] = ' and added to Moodle\'s  group  {$a}';
$string['im:error_adding_u_g'] = 'Error in adding to group  {$a}';
$string['im:already_in_g'] = ' already in group {$a}';
$string['im:stats_i'] = '{$a} enroled &nbsp&nbsp';
$string['im:stats_g'] = '{$a->nb} group(s) created : {$a->what} &nbsp&nbsp';
$string['im:stats_grp'] = '{$a->nb} grouping(s) created : {$a->what} &nbsp&nbsp';
$string['im:err_opening_file'] = 'error opening file {$a}';
$string['im:user_notcostcenter'] = '{$a->user} not assigned to {$a->csname} costcenter';
$string['mass_enroll'] = 'Bulk enrolments';
$string['mass_enroll_info'] =
"<p>
With this option you are going to enrol a list of known users from a file with one account per line
</p>
<p>
<b> The firstline </b> the empty lines or unknown accounts will be skipped. </p>
<p>
<b>The first one must contains a unique email of the target user </b>
</p>";
$string['firstcolumn'] = 'First column contains';
$string['creategroups'] = 'Create group(s) if needed';
$string['creategroupings'] = 'Create  grouping(s) if needed';
$string['enroll'] = 'Enrol them to my onlineexam';
$string['im:user_unknown'] = 'The user with an username "{$a}" doesn\'t exists in the System';
$string['points'] = 'Points';
$string['createonlineexam'] = '<i class="icon popupstringicon fa fa-desktop" aria-hidden="true"></i>Create Test <div class="popupstring">Here you can create test</div>';
$string['editonlineexam'] = '<i class="icon popupstringicon fa fa-desktop" aria-hidden="true"></i>Update Test <div class="popupstring">Here you can update test</div>';
$string['description']   = 'User with Username "{$a->userid}"  created the onlineexam  "{$a->courseid}"';
$string['desc']   = 'User with Username "{$a->userid}" has updated the onlineexam  "{$a->courseid}"';
$string['descptn']   = 'User with Username "{$a->userid}" has deleted the onlineexam with onlineexamid  "{$a->courseid}"';
$string['usr_description']   = 'User with Username "{$a->userid}" has created the user with Username  "{$a->user}"';
$string['usr_desc']   = 'User with Username "{$a->userid}" has updated the user with Username  "{$a->user}"';
$string['usr_descptn']   = 'User with Username "{$a->userid}" has deleted the user with userid  "{$a->user}"';
$string['ilt_description']   = 'User with Username "{$a->userid}"  created the ilt  "{$a->f2fid}"';
$string['ilt_desc']   = 'User with Username "{$a->userid}" has updated the ilt "{$a->f2fid}"';
$string['ilt_descptn']   = 'User with Username "{$a->userid}" has deleted the ilt "{$a->f2fid}"';
$string['onlineexamcompday'] = 'Onlineexam Completion Days';
$string['onlineexamcreator'] = 'Onlineexam Creator';
$string['onlineexamcode'] = 'Code';
$string['addcategory'] = '<i class="fa fa-desktop popupstringicon" aria-hidden="true"></i><i class="fa fa-desktop secbook popupstringicon cat_pop_icon" aria-hidden="true"></i> Create New Category <div class= "popupstring"></div>';
$string['editcategory'] = '<i class="fa fa-desktop popupstringicon" aria-hidden="true"></i><i class="fa fa-desktop secbook popupstringicon cat_pop_icon" aria-hidden="true"></i> Update Category <div class= "popupstring"></div>';
$string['onlineexamcat'] = 'Onlineexam Categories';
$string['deletecategory'] = 'Delete Category';
$string['top'] = 'Top';
$string['parent'] = 'Parent';
$string['actions'] = 'Actions';
$string['count'] = 'Number of Onlineexams';
$string['categorypopup'] = 'Category {$a}';
$string['missingtype'] = 'Missing Type';
$string['catalog'] = 'Catalog';
$string['noonlineexamdesc'] = 'No description provided';
$string['apply'] = 'Apply';
$string['open_path'] = 'Costcenter';
$string['uploadonlineexamspreview'] = 'Upload onlineexams preview';
$string['uploadonlineexamsresult'] = 'Upload onlineexams results';
$string['uploadonlineexams'] = 'Upload onlineexams';
$string['onlineexamfile'] = 'File';
$string['csvdelimiter'] = 'CSV delimiter';
$string['encoding'] = 'Encoding';
$string['rowpreviewnum'] = 'Preview rows';
$string['preview'] = 'Preview';
$string['onlineexamprocess'] = 'Onlineexam process';
$string['shortnametemplate'] = 'Template to generate a shortname';
$string['templatefile'] = 'Restore from this file after upload';
$string['reset'] = 'Reset Onlineexam after upload';
$string['defaultvalues'] = 'Default Onlineexam values';
$string['enrol'] = 'Enrol';
$string['onlineexamexistsanduploadnotallowedwithargs'] = 'Onlineexam is already exists with shortname "{$a}", please choose other unique shortname.';
$string['canonlycreateonlineexamincategoryofsameorganisation'] = 'You can only create the Onlineexam under your assigned organisation';
$string['canonlycreateonlineexamincategoryofsameorganisationwithargs'] = 'Cannot create a Onlineexam under the category \'{$a}\'';
$string['createcategory'] = 'Create New Category';
$string['manage_onlineexam'] = 'Manage Mock Tests';
$string['manage_onlineexams'] = 'Manage Onlineexams';
$string['leftmenu_browsecategories'] = 'Manage Categories';
$string['onlineexamother_details'] = 'Other Details';
$string['view_onlineexams'] = 'view Onlineexams';
$string['deleteconfirm'] = 'Are you sure, you want to delete "<b>{$a->name}</b>" Onlineexam?</br> Once deleted, it can not be reverted.';
$string['department'] = 'Country';
$string['onlineexamcategory'] = 'Category';
$string['fullnameonlineexam'] = 'Fullname';
$string['onlineexamsummary'] = 'Summary';
$string['onlineexamoverviewfiles'] = 'Banner image';
$string['startdate'] = 'Start Date';
$string['enddate'] = 'End Date';
$string['program'] = 'Program';
$string['certification'] = 'Certification';
$string['create_onlineexams'] = 'Create Online Exam';
$string['userenrolments'] = 'User enrollments';
$string['usercategoryenrolments'] = 'User category enrollment';
$string['certificate'] = 'Certificate';
$string['points_positive'] = 'Points must be greater than 0';
$string['onlineexamcompletiondays_positive'] ='Completion days must be greater than 0';
$string['enrolusers'] = 'Enrol Users';
$string['grader'] = 'Grader';
$string['activity'] = 'Activity';
$string['onlineexams'] = 'Mock Tests';
$string['nocategories'] = 'No categories available';
$string['nosameenddate'] = '"Close date" should not be less than "Open date"';
$string['onlineexammanual'] = 'Download sample Excel sheet and fill the field values in the format specified below.';
$string['help_1'] = '<table border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>fullname</td><td>Fullname of the Onlineexam.</td></tr>
<tr><td>onlineexam-code</td><td>onlineexam-code of the Onlineexam.</td></tr>
<tr><td>category_code</td><td>Enter the category code(you can find this code in Manage Categories page).</td></tr>
<tr><td>onlineexamtype</td><td>Type of the onlineexam(Comma seperated)(Ex:classroom,elearning,certification,learningpath,program).</td></tr>
<tr><td>format</td><td>Enter onlineexam format(Ex: singleactivity,social,toggletop,topics,weeks).</td></tr>';

$string['help_2'] = '</td></tr>
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Normal Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>Summary</td><td>Summary of the onlineexam.</td></tr>
<tr><td>Cost</td><td>Cost of the onlineexam.</td></tr>
<tr><td>country_code</td><td>Provide Country code. Country must already exist in system as part of organization hierarchy.</td></tr>
<tr><td>commercial_unit_code</td><td>Enter Commercial Unit Code. Bussiness Unit must already exist under specified Country in system as part of organization hierarchy.</td></tr>
<tr><td>commercial_area_code</td><td>Enter Commercial Unit Code. Commercial Unit must already exist under specified Commercial Unit in system as part of organization hierarchy.</td></tr>
<tr><td>territory_code</td><td>Enter Territory Code. Territory must already exist under specified Commercial Unit in system as part of organization hierarchy.</td></tr>
<tr><td>Points</td><td>Points for the onlineexam.</td></tr>
<tr><td>completiondays</td><td>completiondays should be greater than \'0\'. i.e, 1,2,3..etc</td></tr>
</table>';
$string['back_upload'] = 'Back to upload onlineexams';
$string['manual'] = 'Help manual';
$string['enrolledusers'] = 'Enrolled users';
$string['notenrolledusers'] = 'Not enrolled users';
$string['finishbutton'] = 'Finish';
$string['updateonlineexam'] = 'Update Onlineexam';
$string['onlineexam_name'] = 'Name';
$string['completed_users'] = 'Completed Users';
$string['onlineexam_filters'] = 'onlineexam Filters';
$string['back'] = 'Back';
$string['sample'] = 'Sample';
$string['selectdept'] = '--Select Country--';
$string['selectsubdept'] = '--Select Commercial Unit--';
$string['selectorg'] = '--Select Organization--';
$string['selectcat'] = '--Select Category--';
$string['select_cat'] = '--Select Categories--';
$string['selectonlineexamtype'] = '--Select onlineexam Type--';
$string['reset'] = 'Reset';
$string['err_category'] = 'Please select Category';
$string['availablelist'] = '<b>Available Users ({$a})</b>';
$string['selectedlist'] = 'Selected users';
$string['status'] = 'Status';
$string['select_all'] = 'Select All';
$string['remove_all'] = 'Un Select All';
$string['not_enrolled_users'] = '<b>Not Enrolled Users ({$a})</b>';
$string['enrolled_users'] = '<b> Enrolled Users ({$a})</b>';
$string['remove_selected_users'] = '<b> Un Enroll Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['remove_all_users'] = '<b> Un Enroll All Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['add_selected_users'] = '<i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i><b> Enroll Users</b>';
$string['add_all_users'] = ' <i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i> <b> Enroll All Users </b>';$string['course_status_popup'] = 'Activity status for {$a}';
$string['auto_enrol'] = 'Auto Enroll';
$string['need_manage_approval'] = 'Need Manager Approval';
$string['costcannotbenonnumericwithargs'] ='Cost should be in numeric but given "{$a}"';
$string['pointscannotbenonnumericwithargs'] ='Points should be in numeric but given "{$a}"';
$string['need_self_enrol'] = 'Need Self Enroll';
$string['enrolluserssuccess'] = '<b>{$a->changecount}</b> Employee(s) successfully enrolled to this <b>"{$a->course}"</b> course .';
$string['unenrolluserssuccess'] = '<b>{$a->changecount}</b> Employee(s) successfully un enrolled from this <b>"{$a->course}"</b> course .';

$string['enrollusers'] = 'Onlineexam <b>"{$a}"</b> enrollment is in process...';

$string['un_enrollusers'] = 'Onlineexam <b>"{$a}"</b> un enrollment is in process...';
$string['click_continue'] = 'Click on continue';
$string['bootcamp']= 'XSeeD';
$string['manage_br_onlineexams'] = 'Manage <br/> Onlineexams';
$string['noonlineexamavailiable'] = 'No Online Exams Available';
$string['taskonlineexamnotification'] = 'Onlineexam Notification Task';
$string['taskonlineexamreminder'] = 'Onlineexam Reminder Task';
$string['pleaseselectorganization'] = 'Please Select Organization';
$string['pleaseselectcategory'] = 'Please Select Category';
$string['enableonlineexam'] = 'Are you sure, want Active Onlineexam <b>\'{$a}\'</b>?';
$string['disableonlineexam'] = 'Are you sure, want In-active onlineexam <b>\'{$a}\'</b>?';
$string['onlineexamconfirm'] = 'Confirm';
$string['open_pathonlineexam_help'] = 'Company for the onlineexam';
$string['open_departmentidonlineexam_help'] = 'Country for the onlineexam';
$string['open_identifiedasonlineexam_help'] = 'Type of the onlineexam (multi select)';
$string['open_pointsonlineexam_help'] = 'Points for the onlineexam default (0)';
$string['selfenrolonlineexam_help'] = 'Check yes if required self enrollment to the onlineexam';
$string['approvalrequiredonlineexam_help'] = 'Check yes if required to enable request manager for enrolling to the onlineexam';
$string['open_costonlineexam_help'] = 'Cost of the onlineexam';
$string['open_skillonlineexam_help'] = 'Skill achieved on completion of onlineexam';
$string['open_levelonlineexam_help'] = 'Level achieved on completion of onlineexam';
$string['open_pathonlineexam'] = 'Company';
$string['open_departmentidonlineexam'] = 'Country';
$string['open_identifiedasonlineexam'] = 'Type';
$string['open_pointsonlineexam'] = 'Points';
$string['selfenrolonlineexam'] = 'self enrollment';
$string['approvalrequiredonlineexam'] = 'request manager for enrolling';
$string['open_costonlineexam'] = 'Cost';
$string['open_skillonlineexam'] = 'Skill ';
$string['open_levelonlineexam'] = 'Level';
$string['notyourorg_msg'] = 'You have tried to view this activity is not belongs to your Organization';
$string['notyourdept_msg'] = 'You have tried to view this activity is not belongs to your Country';
$string['notyourorgonlineexam_msg'] = 'You have tried to view this onlineexam is not belongs to your Organization';
$string['notyourdeptonlineexam_msg'] = 'You have tried to view this onlineexam is not belongs to your Country';
$string['notyourorgonlineexamreport_msg'] = 'You have tried to view this Grader report is not your Organization onlineexam, so you cann\'t access this page';
$string['need_manager_approval '] = 'need_manager_approval';
$string['categorycode'] = 'Category Code';
$string['categorycode_help'] = 'The Category Code of a onlineexam category is only used when matching the category against external systems and is not displayed anywhere on the site. If the category has an official code name it may be entered, otherwise the field can be left blank.';

$string['categories'] = 'Sub Categories :  ';
$string['makeactive'] = 'Make Active';
$string['makeinactive'] = 'Make Inactive';
$string['onlineexams:bulkupload'] = 'Bulk upload';
$string['onlineexams:create'] = 'Create onlineexam';
$string['onlineexams:delete'] = 'Delete  onlineexam';
$string['onlineexams:grade_view'] = 'Grade view';
$string['onlineexams:manage'] = 'Manage onlineexams';
$string['onlineexams:report_view'] = 'Report view';
$string['onlineexams:unenrol'] = 'Unenrol onlineexam';
$string['onlineexams:update'] = 'Update onlineexam';
$string['onlineexams:view'] = 'View onlineexam';
$string['onlineexams:visibility'] = 'onlineexam visibility';
$string['onlineexams:enrol'] = 'onlineexam enrol';

$string['reason_linkedtocostcenter'] = 'As this onlineexam category is linked with the Organization/Country, you can not delete this category';
$string['reason_subcategoriesexists'] = 'As we have sub-categories in this onlineexam category, you can not delete this category';
$string['reason_onlineexamsexists'] = 'As we have onlineexams in this onlineexam category, you can not delete this category';
$string['reason'] = 'Reason';
$string['completiondayscannotbeletter'] = 'Cannot create onlineexam with completion days as {$a} ';
$string['completiondayscannotbeempty'] = 'Cannot create onlineexam without completion days.';
$string['tagarea_onlineexams'] = 'onlineexams';
$string['subcategories'] = 'Subcategories';
$string['tag'] = 'Tag';
$string['tag_help'] = 'tag';
$string['open_subdepartmentonlineexam_help'] = 'Commercial Unit of the onlineexam';
$string['open_subdepartmentonlineexam'] = 'Commercial Unit';
$string['suspendconfirm'] = 'Confirmation';
$string['activeconfirm'] = 'Are you sure to make category active ?';
$string['inactiveconfirm'] = 'Are you sure to make category inactive ?';
$string['yes'] = 'Confirm';
$string['no'] = 'Cancel';
$string['add_certificate'] = 'Add Certificate';
$string['add_certificate_help'] = 'If you want to issue a certificate when user completes this onlineexam, please enable here and select the template in next field (Certificate template)';
$string['select_certificate'] = 'Select Certificate';
$string['certificate_template'] = 'Certificate template';
$string['certificate_template_help'] = 'Select Certificate template for this onlineexam';
$string['err_certificate'] = 'Missing Certificate template';
$string['download_certificate'] = 'Download Certificate';
$string['unableto_download_msg'] = "Still this user didn't completed the onlineexam, so you cann't download the certificate";

$string['completionstatus'] = 'Completion Status';
$string['completiondate'] = 'Completion Date';
$string['nousersmsg'] = 'No users Available';
$string['employeename'] = 'Employee Name';
$string['completed'] = 'Completed';
$string['notcompleted'] = 'Not Completed';
$string['messageprovider:onlineexam_complete'] = 'Onlineexam Completion Notification';
$string['messageprovider:onlineexam_enrol'] = 'Onlineexam Enrollment Notification';
$string['messageprovider:onlineexam_notification'] = 'Onlineexam Notification';
$string['messageprovider:onlineexam_reminder'] = 'Onlineexam Reminder';
$string['messageprovider:onlineexam_unenroll'] = 'Onlineexam Unenrollment Notification';
$string['completed_onlineexams'] = 'My Onlineexams';
$string['inprogress_onlineexams'] = 'My Onlineexams';
$string['selectonlineexam'] = 'Select onlineexam';
$string['enrolmethod'] = 'Enrolment Method';
$string['deleteuser'] = 'Delete confirmation';
$string['confirmdelete'] = 'Are you sure,do you want to unenroll this user.';
$string['edit'] = 'Edit';
$string['err_points'] = 'Points cannot be empty';
$string['browseevidences'] = 'Browse Evidence';
$string['onlineexamevidencefiles'] = 'Onlineexam Evidence';
$string['onlineexamevidencefiles_help'] = 'The onlineexam evidence is displayed in the onlineexam overview on the Dashboard. Additional accepted file types and more than one file may be enabled by a site administrator. If so, these files will be displayed next to the onlineexam summary on the list of onlineexams page.';
$string['browseevidencesname'] = '{$a} Evidences';
$string['selfcompletion'] = 'Self Completion';
$string['selfcompletionname'] = '{$a} Self Completion';
$string['selfcompletionconfirm'] = 'Are you sure,do you want to onlineexam "{$a}" self completion.';

// strings added on 23 sept 2020.
$string['saveandcontinue'] = 'Save & Continue';
$string['onlineexamoverview'] = 'Onlineexam Overview';
$string['selectlevel'] = 'Select Level';
$string['errorinrequestprocessing'] = 'Error occured while processing requests';
$string['featuredonlineexams'] = 'Featured Onlineexams';
$string['errorinsubmission'] = 'Error in submission';
$string['recentlyenrolledonlineexams'] = 'Recently Enrolled Onlineexams';
$string['recentlyaccessedonlineexams'] = 'Recently Accessed Onlineexams';
$string['securedonlineexam'] = 'Secured Onlineexam';
$string['open_secureonlineexam_onlineexam'] = 'Secured Onlineexam';
$string['open_secureonlineexam_onlineexam_help'] = 'Once selected as yes this onlineexam will not be displayed over the mobile app.';
$string['parent_category'] = 'Parent Category';
$string['parent_category_code'] = 'Parent Category Code';
$string['select_skill'] = 'Select Skill';
$string['select_level'] = 'Select Level';
$string['what_next'] = "What's next?";
$string['doyouwantto_addthecontent'] = 'Do you want to <b>add the Questions</b>';
$string['doyouwantto_enrolusers'] = 'Do you want to <b>enrol users</b>';
$string['goto'] = 'Go to';
$string['search'] = 'Search';
$string['no_users_enrolled'] = 'No users enrolled to this onlineexam';
$string['missingfullname'] = 'Please Enter Valid Onlineexam Name';
$string['missingshortname'] = 'Please Enter Valid Onlineexam Code';
$string['missingtype'] = 'Please Select Type';
$string['onlineexam_reports'] = 'Onlineexam Reports';
$string['cannotuploadonlineexamwithlob'] = 'With out Country cannot upload a onlineexam with Commercial Unit';
$string['categorycodeshouldbedepcode'] = 'Category Code should be under the Country i.e \'{$a}\'';
$string['categorycodeshouldbesubdepcode'] = 'Category Code should be short name of Commercial Unit i.e \'{$a}\'';
$string['subdeptshouldunderdepcode'] = 'Commercial Unit should be under the Country i.e \'{$a}\'';
$string['onlineexam_name_help'] = 'Name for the Onlineexam';
$string['onlineexamcode_help'] = 'Code for the Onlineexam';
$string['enrolled_onlineexams'] = 'My Onlineexams';
$string['listtype']	='LIST';
$string['cardtype']	='CARD';
$string['listicon'] ='icon fa fa-bars fa-fw';
$string['cardicon'] ='icon fa fa-fw fa-th';
$string['onlineexamtype'] = 'Onlineexam Type';
$string['requestforenroll'] = 'Request';
$string['download_onlineexams'] = 'Download Onlineexams';
$string['subcategory'] = 'Sub Categories';
$string['onlineexamname'] = 'Onlineexam Name';
$string['lastaccess'] = 'Last Access';
$string['progress'] = 'Progress';
$string['enrollments'] = 'Enrolled';
$string['skill'] = 'Skill';
$string['ratings'] = 'Ratings';
$string['tags'] = 'Tags';
$string['subdepartment'] = 'Commercial Unit';
$string['summary'] = 'Summary';
$string['format'] = 'Onlineexam Format';
$string['selfenrol'] = 'Self Enrol';
$string['approvalreqdonlineexam_help'] = 'Select.

* Yes - If you would like to enforce manager or organization head approval while self enrolling to onlineexam
* No - If you would like user to self enroll to onlineexam without an approval from manager or organization head';
$string['onlineexamdescription'] = 'Description';
$string['exportonlineexams'] = 'Export Onlineexams to Excel';
$string['make_inactive'] = 'Make Inactive';
$string['make_active'] = 'Make Active';
$string['departmentnotfound'] ='Country not found i.e \'{$a}\'';
$string['categorycode'] = 'Category Code';
// onlineexam types strings
$string['open_onlineexamtypeonlineexam'] = 'Onlineexam type';
$string['open_onlineexamtypeonlineexam_help'] = 'Select the Onlineexam type';
$string['onlineexam_type'] = 'Onlineexam Type';
$string['onlineexam_type_shortname'] = 'Code';
$string['viewonlineexam_type'] = 'Add/View Onlineexam type';
$string['add_onlineexamtype'] = 'Add Onlineexam type';
$string['edit_onlineexamtype'] = 'Edit Onlineexam type';
$string['listtype']	='LIST';
$string['listicon'] ='icon fa fa-bars fa-fw';
$string['name'] = 'Name';
$string['enableonlineexamtype'] = 'Are you sure to activate onlineexam type <b>{$a}</b>';
$string['disableonlineexamtype'] = 'Are you sure to inactivate onlineexam type <b>{$a}</b>';
$string['statusconfirm'] = 'Are you sure you want to {$a->status} "{$a->name}"';
$string['onlineexamtypeexists'] = 'Onlineexam type already created ({$a})';
$string['deleteonlineexamtypeconfirm'] = 'Are you sure, you want to delete <b>{$a->name}</b> onlineexam type?</br> Once deleted, it can not be reverted.';
$string['err_onlineexamtype'] = 'Please enter Onlineexam type';
$string['err_onlineexamtypeshortname'] = 'Please enter shortname';
$string['add_onlineexam_type'] = 'Add Onlineexam Type';
$string['cannotcreateorupdateonlineexam'] = 'This Onlineexam Type is not Available for this Category i.e \'{$a}\'';
$string['onlineexamcodeexists'] = 'Online Exam code already exists ({$a})';
$string['deleteonlineexamtypenotconfirm'] = 'You cannot delete <b>{$a->name}</b> as it is currently mapped to a onlineexam. Please unmap to delete.';
$string['reason'] = 'Reason';
$string['open_costcenteridlocal_onlineexams'] = 'Company';
$string['open_departmentlocal_onlineexams'] = 'Bussiness Unit';
$string['open_subdepartmentlocal_onlineexams'] = 'Department';
$string['open_level4departmentlocal_onlineexams'] = 'Sub Department';
$string['open_level5departmentlocal_onlineexams'] = 'Territory';
$string['pleaseselectidentifiedtype'] = 'Please Select Type';

$string['open_costcenteridlocal_onlineexams_help'] = 'Company of the onlineexam';
$string['open_departmentlocal_onlineexams_help'] = 'Bussiness Unit of the onlineexam';
$string['open_subdepartmentlocal_onlineexams_help'] = 'Department of the onlineexam';
$string['open_level4departmentlocal_onlineexams_help'] = 'Sub Department of the onlineexam';
$string['open_level5departmentlocal_onlineexams_help'] = 'Territory of the onlineexam';

$string['cannotuploadonlineexamwithsubdepartment'] = 'With out Commercial Unit cannot upload a onlineexam with Commercial Area';
$string['categorycodeshouldbesubdepcode'] = 'Category Code should be under the Commercial Unit i.e \'{$a}\'';
$string['categorycodeshouldbesubsubdepcode'] = 'Category Code should be short name of Commercial Area i.e \'{$a}\'';
$string['subdeptshouldundersubdepcode'] = 'Commercial Area should be under the Commercial Unit i.e \'{$a}\'';
$string['subdepartmentnotfound'] ='Commercial Unit not found i.e \'{$a}\'';

$string['cannotuploadonlineexamwithsubsubdepartment'] = 'With out Commercial Area cannot upload a onlineexam with Territory';
$string['categorycodeshouldbesubsubdepcode'] = 'Category Code should be under the Commercial Area i.e \'{$a}\'';
$string['categorycodeshouldbesubsubsubdepcode'] = 'Category Code should be short name of Territory i.e \'{$a}\'';
$string['subdeptshouldundersubsubdepcode'] = 'Territory should be under the Commercial Area i.e \'{$a}\'';
$string['subsubdepartmentnotfound'] ='Commercial Area not found i.e \'{$a}\'';
$string['open_states_help'] = 'Search and select an available or existing state as target audience';
$string['open_district_help'] = 'Search and select an available or existing district as target audience';
$string['open_subdistrict_help'] = 'Search and select an available or existing subdistrict as target audience';
$string['open_village_help'] = 'Search and select an available or existing village as target audience';
$string['username'] = 'Username';
$string['enablereports'] = 'Onlineexam reports are currently not configured. <a href="{$a}" target="_blank"> <u>Click here </u></a> to configure reports';
$string['onlineexamcompday_atsearch'] = 'Completion Days';
$string['addnewonlineexam'] = 'Add New onlineexam';
$string['manage_onlineexams'] = 'Manage Online Exams';
$string['maxgrade'] = 'Max grade';
$string['gradepass'] = 'Pass grade';
$string['onlineexams'] = 'Online Exams';
$string['view_onlineexams'] = 'View Online Exams';
$string['manage_br_onlineexams'] = 'Manage Online Exams';
$string['entergradepass'] = "Please enter Grade pass";
$string['noonlineexamsavailable']= 'No Online Exams Available';
$string['enrolled_onlineexams'] = "My Online Exams";
$string['inprogress_onlineexams'] = "My Online Exams";
$string['completed_onlineexams'] = "My Online Exams";
$string['shouldbeless'] = 'pass grade shoulb be lessthan Max grade {$a}';
$string['numeric'] = 'Only numeric values';
$string['create_newonlineexams'] = 'Create New Test';
$string['onlineexamoverviewfiles_help'] = 'The Onlineexam image is displayed in the onlineexam overview on the Dashboard. Additional accepted file types and more than one file may be enabled by a site administrator. If so, these files will be displayed next to the onlineexam summary on the list of onlineexams page.';
$string['onlineexamsummary_help'] = 'The onlineexam summary is displayed in the list of onlineexams. A onlineexam search searches onlineexam summary text in addition to onlineexam names.';
$string['shortnametaken'] = 'Short name is already used for another onlineexam ({$a})';
$string['enrolledlist'] = "Enrolled List";
$string['completedlist'] = "Completed List";
$string['enrolledon'] = "Enrolled on";
$string['completedon'] = "Completed on";
$string['grade'] = "Grade";
$string['pending'] = "Pending";
$string['onlineexam'] = 'Online Exam';
$string['incompleted'] = 'In-Complete';
$string['open_department'] = 'Bussiness Unit';
$string['typeonlineexams'] ='Online Exams Event';
$string['onlineexamopen'] = "Online Exam Opens";
$string['onlineexamclose'] = "Online Exam Closes";
$string['onlineexam_open'] = "Onlineexam opens";
$string['onlineexam_close'] = "Onlineexam closes";
$string['enablecourse'] = 'Are you sure, want Active Onlineexam <b>\'{$a}\'</b>?';
$string['disablecourse'] = 'Are you sure, want In-active Onlineexam <b>\'{$a}\'</b>?';
$string['courseconfirm'] = 'Confirm';
$string['remove_users'] = 'Remove Selected Users';
$string['add_users'] = 'Add Selected Users';
$string['online_exams'] = 'Mock Test';
$string['yearbook'] = 'Year Book';
$string['yearicon'] ='fa fa-book-open fa-fw';
$string['onlineicon'] ='fa fa-chalkboard-teacher fa-fw';
$string['gotoonline_exams'] = 'See Mock Test';
$string['gotoyearbook'] = 'See Year Book';
$string['disableconfirm'] = 'Are you sure, you want to {$a->status} "<b>{$a->name}</b>" Onlineexam?</br>';
$string['disableallconfirm'] = 'Confirm';
$string['course_type'] = 'Type';
$string['manage_yearbook'] = 'Manage Yearbooks';
$string['tests'] = 'Tests';
$string['manage_tests'] = 'Manage Tests';
$string['shortname'] = 'Test code';
$string['apply'] = 'Apply';
$string['reset'] = 'Reset';
$string['no_tests_data'] = 'Tests not available';
$string['tests_type'] = 'Test Types';
$string['fullname'] = 'Fullname';
$string['mytests'] = 'My Tests';
$string['launch'] = 'Launch';
$string['is_featured'] = 'Is featured exam ?';
$string['singleactivity'] = 'Single';
$string['multipleactivities'] = 'Multiple';
$string['noofactivities'] = 'Number of activities';
$string['test_type'] = 'Test type';
$string['phone'] = 'Phone';
$string['enrollcourseuserssuccess'] = '<b>{$a->changecount}</b> Employee(s) successfully enrolled to this <b>"{$a->category}"</b> category .';

$string['unenrollcategoryuserssuccess'] = '<b>{$a->changecount}</b> Employee(s) successfully un enrolled from this <b>"{$a->category}"</b> category .';

$string['un_enrollusers'] = 'Onlineexam <b>"{$a}"</b> un enrollment is in process...';
$string['enrollusers'] = 'Onlineexam <b>"{$a}"</b> enrollment is in process...';
$string['categoryidnotempty'] = 'Category ID should not be empty';
$string['invalidcategoryid'] = 'Invalid category ID';
$string['onlineexams:candeleteonlineexams'] = 'Candelete Onlineexams';
$string['noofquestion'] = 'No of questions';
$string['timelimit'] = 'Time Limit';
$string['marks'] = 'Marks';
$string['yearbookmocktest'] = 'Yearbook Mock Test';
