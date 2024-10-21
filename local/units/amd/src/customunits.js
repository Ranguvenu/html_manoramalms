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
 * AMD module for the hirarchy in units page.
 *
 * @module     local_units/customunits
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
        createunit: '[data-action = "createunit"]',
        createchapter: '[data-action="createchapter"]',
        createtopic: '[data-action="createtopic"]',
        createconcept: '[data-action="createconcept"]',
        deletecomponent: '[data-action="deletecomponent"]',
    },
};

/**
 * Initialize module
 */
let HomePage = new homepage();
export const init = () => {
    document.addEventListener('click', function(e) {
        let createunit = e.target.closest(Selectors.actions.createunit);
        if (createunit) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = createunit.getAttribute('data-id') ?
                getString('edit_unit', 'local_units', createunit.getAttribute('data-name')) :
                getString('add_unit', 'local_units');
            const form = new ModalForm({
                formClass: 'local_units\\form\\customunit',
                args: {id: createunit.getAttribute('data-id')},
                modalConfig: {title},
                returnFocus: createunit,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        createchapter = e.target.closest(Selectors.actions.createchapter);
        if (createchapter) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = createchapter.getAttribute('data-id') ?
                getString('edit_chapter', 'local_units', createchapter.getAttribute('data-name')) :
                getString('add_chapter', 'local_units', createchapter.getAttribute('data-unitname'));
            const form = new ModalForm({
                formClass: 'local_units\\form\\customchapter',
                args: {id: createchapter.getAttribute('data-id'),unitid: createchapter.getAttribute('data-unitid')},
                modalConfig: {title},
                returnFocus: createchapter,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        createtopic = e.target.closest(Selectors.actions.createtopic);
        if (createtopic) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = createtopic.getAttribute('data-id') ?
                getString('edit_topic', 'local_units', createtopic.getAttribute('data-name')) :
                getString('add_topic', 'local_units', createtopic.getAttribute('data-chaptername'));
        
            const form = new ModalForm({
                formClass: 'local_units\\form\\customtopic',
                args: {id: createtopic.getAttribute('data-id'),chapterid: createtopic.getAttribute('data-chapterid'),unitid: createtopic.getAttribute('data-unitid')},
                modalConfig: {title},
                returnFocus: createtopic,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        createconcept = e.target.closest(Selectors.actions.createconcept);
        if (createconcept) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = createconcept.getAttribute('data-id') ?
                getString('edit_concept', 'local_units', createconcept.getAttribute('data-name')) :
                getString('add_concept', 'local_units', createconcept.getAttribute('data-conceptname'));
        
            const form = new ModalForm({
                formClass: 'local_units\\form\\customconcept',
                args: {id: createconcept.getAttribute('data-id'),chapterid: createconcept.getAttribute('data-chapterid'),unitid: createconcept.getAttribute('data-unitid'),topicid: createconcept.getAttribute('data-topicid')},
                modalConfig: {title},
                returnFocus: createconcept,
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
                title: getString('deletecomponentconfirm', 'local_units',component),
                type: ModalFactory.types.SAVE_CANCEL,
                body: getString('deletecomponentbodymessage', 'local_units', displayparams)
            }).done(function(modal) {
                this.modal = modal;
                modal.setSaveButtonText(getString('deletetext', 'local_units'));
                modal.getRoot().on(ModalEvents.save, function(e) {
                    e.preventDefault();
                    var sparams = {};
                    sparams.id = id;
                    sparams.component = component;
                    var promise = Ajax.call([{
                        methodname: 'local_units_candeletecomponent',
                        args: sparams
                    }]);
                    promise[0].done(function(resp) {
                        if(resp.candelete == 1) {
                            modal.hide();
                            HomePage.confirmbox(getString('remove_dependency', 'local_units'));
                        } else {
                            var params = {};
                            params.id = id;
                            params.component = component;
                            var promise = Ajax.call([{
                                methodname: 'local_units_deletecomponent',
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
