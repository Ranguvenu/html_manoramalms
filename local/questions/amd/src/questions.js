import ModalForm from 'core_form/modalform';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';
const Selectors = {
    actions: {
        createquestion: '[data-action="createquestion"]',
    },
};
export const init = () => {
    document.addEventListener('click', function(e) {
        e.stopImmediatePropagation()
        let element = e.target.closest(Selectors.actions.createquestion);
        if (element) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const title = element.getAttribute('data-id') ?
                getString('updatequestion', 'local_questions') :
                getString('addquestion', 'local_questions');
            const form = new ModalForm({
                formClass: 'local_questions\\form\\question_form',
                args: {id: element.getAttribute('data-id')},
                modalConfig: {title},
                returnFocus: element,
            });
            form.addEventListener(form.events.ERROR, event => {
                form.enableButtons();
            });
            form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
            form.show();
        }
        
    });
};
