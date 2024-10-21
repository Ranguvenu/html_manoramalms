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
 * AMD module for the hirarchy in faqs page.
 *
 * @module     local_faq/faq_categories
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
        cteatecategory: '[data-action = "cteatecategory"]',
        cteatequeries: '[data-action="cteatequeries"]',
        createclass: '[data-action="createclass"]',
        createsubject: '[data-action="createsubject"]',
        deletefaq: '[data-action="deletefaq"]',
        deletequery: '[data-action="deletequery"]',
    },
};

/**
 * Initialize module
 */
let HomePage = new homepage();
export const init = () => {
    document.addEventListener('click', function(e) {
        let cteatecategory = e.target.closest(Selectors.actions.cteatecategory);
        if (cteatecategory) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = cteatecategory.getAttribute('data-id') ?
                getString('edit_faqcategory', 'local_faq', cteatecategory.getAttribute('data-name')) :
                getString('addcategory', 'local_faq');
            const form = new ModalForm({
                formClass: 'local_faq\\form\\faq_categories',
                args: {id: cteatecategory.getAttribute('data-id')},
                modalConfig: {title},
                returnFocus: cteatecategory,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        cteatequeries = e.target.closest(Selectors.actions.cteatequeries);
        if (cteatequeries) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = cteatequeries.getAttribute('data-id') ?
                getString('edit_faqquery', 'local_faq', cteatequeries.getAttribute('data-name')) :
                getString('addquery', 'local_faq', cteatequeries.getAttribute('data-name'));
            const form = new ModalForm({
                formClass: 'local_faq\\form\\faq_queries',
                args: {id: cteatequeries.getAttribute('data-id'),id: cteatequeries.getAttribute('data-id')},
                modalConfig: {title},
                returnFocus: cteatequeries,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        createclass = e.target.closest(Selectors.actions.createclass);
        if (createclass) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = createclass.getAttribute('data-id') ?
                getString('edit_class', 'local_faq', createclass.getAttribute('data-name')) :
                getString('add_class', 'local_faq', createclass.getAttribute('data-boardname'));
        
            const form = new ModalForm({
                formClass: 'local_faq\\form\\customclass',
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
                getString('edit_subject', 'local_faq', createsubject.getAttribute('data-name')) :
                getString('add_subject', 'local_faq', createsubject.getAttribute('data-classessname'));
        
            const form = new ModalForm({
                formClass: 'local_faq\\form\\customsubject',
                args: {id: createsubject.getAttribute('data-id'),classessid: createsubject.getAttribute('data-classessid')},
                modalConfig: {title},
                returnFocus: createsubject,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }

        let deletefaq = e.target.closest(Selectors.actions.deletefaq);
        if (deletefaq) {
          const id = deletefaq.getAttribute("data-id");
          const name = deletefaq.getAttribute("data-name");
          ModalFactory.create({
            title: getString("confirm", "local_faq"),
            type: ModalFactory.types.SAVE_CANCEL,
            body: getString("deletefaqcategory", "local_faq", name),
          }).done(
            function (modal) {
              this.modal = modal;
              modal.setSaveButtonText(getString("yes", "local_faq"));
              modal.getRoot().on(
                ModalEvents.save,
                function (e) {
                  e.preventDefault();
                  var params = {};
                  params.confirm = true;
                  params.id = id;
                  var promise = Ajax.call([
                    {
                      methodname: "local_faq_delete_faqcategory",
                      args: params,
                    },
                  ]);
                  promise[0]
                    .done(function () {
                      window.location.reload(true);
                    })
                    .fail(function () {
                      // do something with the exception
                    });
                }.bind(this)
              );
              modal.show();
            }.bind(this)
          );
        }
        let deletequery = e.target.closest(Selectors.actions.deletequery);
        if (deletequery) {
          const id = deletequery.getAttribute("data-id");
          const name = deletequery.getAttribute("data-name");
          ModalFactory.create({
            title: getString("confirm", "local_faq"),
            type: ModalFactory.types.SAVE_CANCEL,
            body: getString("deletefaqquery", "local_faq", name),
          }).done(
            function (modal) {
              this.modal = modal;
              modal.setSaveButtonText(getString("yes", "local_faq"));
              modal.getRoot().on(
                ModalEvents.save,
                function (e) {
                  e.preventDefault();
                  var params = {};
                  params.confirm = true;
                  params.id = id;
                  var promise = Ajax.call([
                    {
                      methodname: "local_faq_delete_faq_query",
                      args: params,
                    },
                  ]);
                  promise[0]
                    .done(function () {
                      window.location.reload(true);
                    })
                    .fail(function () {
                      // do something with the exception
                    });
                }.bind(this)
              );
              modal.show();
            }.bind(this)
          );
        }
    });
};
