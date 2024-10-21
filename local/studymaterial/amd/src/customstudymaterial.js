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
 * AMD module for the hirarchy in studymaterial page.
 *
 * @module     local_studymaterial/customstudymaterial
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import ModalForm from 'core_form/modalform';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import { get_string as getString } from 'core/str';
import homepage from 'theme_horizon/homepage';

/**
 * Selectors
 */
const Selectors = {
    actions: {
        createstudymaterial: '[data-action = "createstudymaterial"]',
        deletestudymaterial: '[data-action="deletestudymaterial"]',
    },
};

/**
 * Initialize module
 */
let HomePage = new homepage();
export const init = () => {
    document.addEventListener('click', function (e) {
        let createstudymaterial = e.target.closest(Selectors.actions.createstudymaterial);
        if (createstudymaterial) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = createstudymaterial.getAttribute('data-id') ?
                getString('edit_studymaterial', 'local_studymaterial', createstudymaterial.getAttribute('data-name')) :
                getString('add_studymaterial', 'local_studymaterial');
            const form = new ModalForm({
                formClass: 'local_studymaterial\\form\\customstudymaterial',
                args: { id: createstudymaterial.getAttribute('data-id'),courseid: createstudymaterial.getAttribute('data-courseid') },
                modalConfig: { title },
                returnFocus: createstudymaterial,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        deletestudymaterial = e.target.closest(Selectors.actions.deletestudymaterial);
        if (deletestudymaterial) {
            const component = deletestudymaterial.getAttribute('data-component');
            const id = deletestudymaterial.getAttribute('data-id');
            const name = deletestudymaterial.getAttribute('data-name');
            const code = deletestudymaterial.getAttribute('data-code');
            var displayparams = {};
            displayparams.component = component;
            displayparams.name = name;
            displayparams.code = code;
            ModalFactory.create({
                title: getString('deletestudymaterialconfirm', 'local_studymaterial', component),
                type: ModalFactory.types.SAVE_CANCEL,
                body: getString('deletestudymaterialbodymessage', 'local_studymaterial', displayparams)
            }).done(function (modal) {
                this.modal = modal;
                modal.setSaveButtonText(getString('deletetext', 'local_studymaterial'));
                modal.getRoot().on(ModalEvents.save, function (e) {
                    e.preventDefault();
                    var params = {};
                    params.id = id;
                    var promise = Ajax.call([{
                        methodname: 'local_studymaterial_deletestudymaterial',
                        args: params
                    }]);
                    promise[0].done(function (resp) {
                        window.location.reload(true);
                    }).fail(function () {
                        // do something with the exception
                        console.log('exception');
                    });


                }.bind(this));
                modal.show();
            }.bind(this));
        }
    });
};
