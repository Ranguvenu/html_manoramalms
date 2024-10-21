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
 * Functionality for questions
 *
 * @module     local_questions/questions
 * @package
 * @copyright  2023 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import ModalForm from 'core_form/modalform';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import {get_string as getString} from 'core/str';

const SELECTORS = {
    REJECTSUBMISSION: '[data-action="statustoreject"]',
};

/**
* Displays a modal form to create question types
*
* @param {Event} e
*/
const rejectReasonSubmission = function(e) {
    e.preventDefault();
    var id = $(e.currentTarget).attr('data-id');
    var headstring = $(e.currentTarget).attr('data-headstring');
    var qbankeid = $(e.currentTarget).attr('data-qbankeid');
    var workshopid = $(e.currentTarget).attr('data-wid');

    var modal = new ModalForm({
        formClass: 'local_questions\\form\\rejectreason_form',
        args: {id: id, qbankeid: qbankeid, workshopid: workshopid},
        modalConfig: {title: headstring},
        saveButtonText: getString('save'),
        returnFocus: $(e.currentTarget),
    });
    modal.addEventListener(modal.events.FORM_SUBMITTED, function() {
    	var qid = $(e.currentTarget).attr('data-id');
	var wid = $(e.currentTarget).attr('data-wid');
	var status = $(e.currentTarget).attr('data-value');
        var statusText = $(e.currentTarget).attr('data-statusText');
        var adminstatus = $(e.currentTarget).attr('data-adminstatus');
    	var params = {};
	    params.questionid = qid;
	    params.workshopid = wid;
	    params.status = status;

        var promise = Ajax.call([{
            methodname: 'local_questions_changequestionstatus',
            args: params
        }]);
        promise[0].done(function(resp) {
            $('.questioncreation_page #status_span'+qid).text(statusText);
            if (status == 'reject' && adminstatus == 1) {
                $('#understatus'+qid).hide();
                $('#rejectstatus'+qid).hide();
                $('#publishstatus'+qid).hide();
                $('#raedystatus'+qid).hide();
                $('#draftstatus'+qid).show();
                $('#hideeditdelete'+qid).show();
            }
            if (status == 'reject' && adminstatus != 1) {
                $('#understatus'+qid).hide();
                $('#rejectstatus'+qid).hide();
                $('#publishstatus'+qid).hide();
                $('#draftstatus'+qid).show();
            }
        	console.log('Status changed successfully');
        }).fail(function() {
        	console.log('Error processing the request');
        });
        // window.location.reload();
    });
    modal.show();
};

/**
 * Initialise organization actions
 */
export const init = () => {
	$(SELECTORS.REJECTSUBMISSION).on('click', function(e) {
        e.preventDefault();
        rejectReasonSubmission(e);
    });
}
