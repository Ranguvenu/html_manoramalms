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
 * TODO describe module session
 *
 * @module     local_packages/session
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import ModalForm from 'core_form/modalform';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';
const Selectors = {
    actions: {
        addsessions: '[data-action="addsessions"]',
        viewsessions: '[data-action="viewsessions"]',
        deletesession: '[data-action="deletesession"]',
    },
};
const render_template = (template, selector, params, append = false) => {
	if(!append){
		$(selector).empty();
	}
	Templates.renderForPromise(template, params).then(({html, js}) => {
		Templates.appendNodeContents(selector, html, js);
	});	
}
const loader = (selector, isloading, append = false) => {
	if(isloading){
		render_template('local_packages/loader', selector, {}, append);
	}else{
		$("[data-region='page-loader']").remove();
	}
}
export const init = () => {
    document.addEventListener('click', function(e) {
        let element = e.target.closest(Selectors.actions.addsessions);
        if (element) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = element.getAttribute('data-id') ?
                getString('updatesession', 'local_packages') :
                getString('addsession', 'local_packages');
            const form = new ModalForm({
                formClass: 'local_packages\\form\\session_form',
                args: {id: element.getAttribute('data-id'),packageid: element.getAttribute('data-packageid'),courseid: element.getAttribute('data-courseid')},
                modalConfig: {title},
                returnFocus: element,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, (event) => {
                event.preventDefault();
                e.preventDefault();
                Templates.renderForPromise('local_packages/loader', {}).then(({html, js}) => {
                    Templates.appendNodeContents('.modal-content', html, js);
                });
                window.location.reload();
            });
            form.show();
        }
        let viewsessions = e.target.closest(Selectors.actions.viewsessions);
        if (viewsessions) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const packageid = viewsessions.getAttribute('data-packageid');
            const courseid = viewsessions.getAttribute('data-courseid');
            const coursename = viewsessions.getAttribute('data-coursename');
            var options = {};
            options.packageid = packageid;
            options.courseid = courseid;
            var trigger = $(Selectors.actions.viewsessions);
            ModalFactory.create({
                title: getString('viewcoursesessions', 'local_packages',coursename),
                body: Templates.render('local_packages/sessions_display',options)
            }, trigger)
            .done(function(modal) {
                modal.setLarge();
                modal.show();
                modal.getRoot().on(ModalEvents.hidden, function() {
                modal.destroy();
                }.bind(this));
            });
        }
        
        let deletesession = e.target.closest(Selectors.actions.deletesession);
        if (deletesession) {
            e.preventDefault();
            const id = deletesession.getAttribute('data-id');
            const batchname = deletesession.getAttribute('data-batchname')
            const schedulecode = deletesession.getAttribute('data-schedulecode');
            var displayparams = {};
            displayparams.batchname = batchname;
            displayparams.schedulecode = schedulecode;
            ModalFactory.create({
                title: getString('deleteconfirm', 'local_packages'),
                type: ModalFactory.types.SAVE_CANCEL,
                body: getString('sessiondeletebodymessage', 'local_packages', displayparams)
            }).done(function(modal) {
                this.modal = modal;
                modal.setSaveButtonText(getString('deletetext', 'local_packages'));
                modal.getRoot().on(ModalEvents.save, function(e) {
                    e.preventDefault();
                    Templates.renderForPromise('local_packages/loader', {}).then(({html, js}) => {
                        Templates.appendNodeContents('.modal-content', html, js);
                    });
                    var params = {};
                    params.id = id;
                    var promise = Ajax.call([{
                        methodname: 'local_packages_deletesession',
                        args: params
                    }]);
                    promise[0].done(function(resp) {
                        window.location.reload(true);
                    }).fail(function() {
                        console.log('exception');
                    });
                }.bind(this));
                modal.show();
            }.bind(this));
        }
    });
};
