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
 * AMD module for the hirarchy in goals page.
 *
 * @module     local_goals/customgoals
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import ModalForm from 'core_form/modalform';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';
import homepage from 'theme_horizon/homepage';

/**
 * Selectors
 */
const Selectors = {
    actions: {
        creategoal: '[data-action = "creategoal"]',
        createboard: '[data-action="createboard"]',
        createclass: '[data-action="createclass"]',
        createsubject: '[data-action="createsubject"]',
        deletecomponent: '[data-action="deletecomponent"]',
    },
};

/**
 * Initialize module
 */
let HomePage = new homepage();
export const init = () => {
    document.addEventListener('click', function(e) {
        let creategoal = e.target.closest(Selectors.actions.creategoal);
        if (creategoal) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = creategoal.getAttribute('data-id') ?
                getString('edit_goal', 'local_goals', creategoal.getAttribute('data-name')) :
                getString('add_goal', 'local_goals');
            const form = new ModalForm({
                formClass: 'local_goals\\form\\customgoal',
                args: {id: creategoal.getAttribute('data-id')},
                modalConfig: {title},
                returnFocus: creategoal,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        createboard = e.target.closest(Selectors.actions.createboard);
        if (createboard) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = createboard.getAttribute('data-id') ?
                getString('edit_board', 'local_goals', createboard.getAttribute('data-name')) :
                getString('add_board', 'local_goals', createboard.getAttribute('data-goalname'));
            const form = new ModalForm({
                formClass: 'local_goals\\form\\customboard',
                args: {id: createboard.getAttribute('data-id'),goalid: createboard.getAttribute('data-goalid')},
                modalConfig: {title},
                returnFocus: createboard,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        createclass = e.target.closest(Selectors.actions.createclass);
        if (createclass) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = createclass.getAttribute('data-id') ?
                getString('edit_class', 'local_goals', createclass.getAttribute('data-name')) :
                getString('add_class', 'local_goals', createclass.getAttribute('data-boardname'));
        
            const form = new ModalForm({
                formClass: 'local_goals\\form\\customclass',
                args: {id: createclass.getAttribute('data-id'),boardid: createclass.getAttribute('data-boardid')},
                modalConfig: {title},
                returnFocus: createclass,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        createsubject = e.target.closest(Selectors.actions.createsubject);
        if (createsubject) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = createsubject.getAttribute('data-id') ?
                getString('edit_subject', 'local_goals', createsubject.getAttribute('data-name')) :
                getString('add_subject', 'local_goals', createsubject.getAttribute('data-classessname'));
        
            const form = new ModalForm({
                formClass: 'local_goals\\form\\customsubject',
                args: {id: createsubject.getAttribute('data-id'),classessid: createsubject.getAttribute('data-classessid')},
                modalConfig: {title},
                returnFocus: createsubject,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        deletecomponent = e.target.closest(Selectors.actions.deletecomponent);
        if (deletecomponent) {
            const component = deletecomponent.getAttribute('data-component');
            const id = deletecomponent.getAttribute('data-id');
            const name = deletecomponent.getAttribute('data-name');
            const code = deletecomponent.getAttribute('data-code');
            var displayparams = {};
            displayparams.component = component;
            displayparams.name = name;
            displayparams.code = code;
            ModalFactory.create({
                title: getString('deletecomponentconfirm', 'local_goals',component),
                type: ModalFactory.types.SAVE_CANCEL,
                body: getString('deletecomponentbodymessage', 'local_goals', displayparams)
            }).done(function(modal) {
                this.modal = modal;
                modal.setSaveButtonText(getString('deletetext', 'local_goals'));
                modal.getRoot().on(ModalEvents.save, function(e) {
                    e.preventDefault();
                    var sparams = {};
                    sparams.id = id;
                    sparams.component = component;
                    var promise = Ajax.call([{
                        methodname: 'local_goals_candeletecomponent',
                        args: sparams
                    }]);
                    promise[0].done(function(resp) {
                        if(resp.candelete == 1) {
                            modal.hide();
                            HomePage.confirmbox(getString('remove_dependency', 'local_goals'));
                        } else {
                            var params = {};
                            params.id = id;
                            params.component = component;
                            var promise = Ajax.call([{
                                methodname: 'local_goals_deletecomponent',
                                args: params
                            }]);
                            promise[0].done(function(resp) {
                                window.location.reload(true);
                            }).fail(function() {
                                // do something with the exception
                                console.log('exception');
                            });
                        }
                    }).fail(function() {
                        console.log('exception');
                    });
                    
                }.bind(this));
                modal.show();
            }.bind(this));
        }
    });
};
