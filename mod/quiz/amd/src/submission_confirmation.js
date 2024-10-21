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
 * A javascript module to handle submission confirmation for quiz.
 *
 * @module    mod_quiz/submission_confirmation
 * @copyright 2022 Huong Nguyen <huongnv13@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     4.1
 */

import { saveCancelPromise} from 'core/notification';
import Prefetch from 'core/prefetch';
import Templates from 'core/templates';
import { get_string as getString } from 'core/str';
import Ajax from 'core/ajax';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';


const SELECTOR = {
    attemptSubmitButton: '.path-mod-quiz .btn-finishattempt button',
    attemptSubmitForm: 'form#frm-finishattempt',
};

const TEMPLATES = {
    submissionConfirmation: 'mod_quiz/submission_confirmation',
};

/**
 * Function to handle the attempt submission logic
 * @param {HTMLElement} submitAction - The submit button element
 * @param {int} unAnsweredQuestions - Total number of un-answered questions
 */
// core changes for MAN-1156 added by dhasharath.k.
// start here.
const handleAttemptSubmission = async (submitAction, unAnsweredQuestions) => {
    try {
        // Get form data
        const formData = new FormData(submitAction.closest(SELECTOR.attemptSubmitForm));

        // Convert FormData to an object
        const formDataObject = {};
        formData.forEach((value, key) => {
            formDataObject[key] = value;
        });

        // Send form data to PHP external service
        const params = {
            confirm: true,
            attemptid: formDataObject.attempt,
            cmid: formDataObject.cmid,
        };

        const promise = Ajax.call([
            {
                methodname: "local_packages_get_quiz_attempt_data",
                args: params,
            },
        ]);

promise[0]
    .done(function (response) {
        console.log('Server Response:', response);

        // Check if the response contains the expected data
        if (response && response.result) {
            console.log('Response Result:', response.result);

            const { answeredcount, wrongcount, unansweredcount, totalquetions, remainingtime, notvisited } = response.result;

            try {

            ModalFactory.create({
            title: getString("examsummery", "quiz"),
            type: ModalFactory.types.SAVE_CANCEL,
            body: Templates.render(TEMPLATES.submissionConfirmation, {
                hasunanswered: unansweredcount > 0,
                totalunanswered: unansweredcount,
                answeredcount: answeredcount,
                totalquetions: totalquetions,
                remainingtime: remainingtime, 
                notvisited: notvisited
            }),
          }).done(
            function (modal) {
              this.modal = modal;
              modal.setSaveButtonText(getString("mark", "quiz"));
              modal.getRoot().on(
                ModalEvents.save,
                function (e) {
                    submitAction.closest(SELECTOR.attemptSubmitForm).submit();
                }.bind(this)
              );
              modal.show();
            }.bind(this)
          );

            } catch {
                // Cancel pressed.
                return;
            }
        } else {
            // Handle the case where the response doesn't contain the expected data
            console.log('Error: Invalid response from server', 'error');
        }
    })
    .fail(function () {
        // Handle the failure scenario
        console.error('Error: Failed to submit the quiz', 'error');
    });

    } catch (error) {
        // Handle errors
        console.error(`Error: ${error.message}`, 'error');
    }
};
// end here.
/**
 * Register events for attempt submit button.
 * @param {int} unAnsweredQuestions - Total number of un-answered questions
 */
const registerEventListeners = (unAnsweredQuestions) => {
    const submitAction = document.querySelector(SELECTOR.attemptSubmitButton);
    if (submitAction) {
        submitAction.addEventListener('click', async (e) => {
            e.preventDefault();
            handleAttemptSubmission(submitAction, unAnsweredQuestions);
        });
    }
};

/**
 * Initialises.
 * @param {int} unAnsweredQuestions - Total number of unanswered questions
 */
export const init = (unAnsweredQuestions) => {
    // core changes for MAN-1156.
    // start here.
    Prefetch.prefetchStrings('core', ['submit']);
    Prefetch.prefetchStrings('core_admin', ['confirmation']);
    Prefetch.prefetchStrings('quiz', ['mark', 'examsummery']);
    // end here.
    Prefetch.prefetchTemplate(TEMPLATES.submissionConfirmation);
    registerEventListeners(unAnsweredQuestions);
};
