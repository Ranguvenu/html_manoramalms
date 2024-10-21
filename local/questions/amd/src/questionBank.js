import ModalForm from 'core_form/modalform';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import {get_string as getString} from 'core/str';
import $ from 'jquery';
import Templates from 'core/templates';
import cfg from 'core/config';


const Selectors = {
    actions: {
        changestatus: '[data-action="changestatus"]',
    },
};
export const init = (caneditordeleteatanystatus) => {
    document.addEventListener('click', function(e) {
      let changestatus = e.target.closest(Selectors.actions.changestatus);
      if (changestatus) {
        e.preventDefault();
        const questionid = changestatus.getAttribute('data-id');
        const status = changestatus.getAttribute('data-value');
        const statusText = changestatus.getAttribute('data-statusText');
        const adminstatus = changestatus.getAttribute('data-adminstatus');
        const canreviewselfstatus = changestatus.getAttribute('data-canreviewselfstatus');
        const hidestatus  = changestatus.getAttribute('data-hidestatus');    
        const workshopid = changestatus.getAttribute('data-wid');
        var params = {};
        params.questionid = questionid;
        params.workshopid = workshopid;
        params.status = status;
        switch(status){
         case "readytoreview":
         displaystatus = "Ready to Review";
         break;
         case "underreview":
         displaystatus = "Under Review";
         break;
         case "draft":
         displaystatus = "Draft";
         break;
         case "reject":
         displaystatus = "Reject";
         break;
         case "publish":
         displaystatus = "Publish";
         break;
        }
        ModalFactory.create({
            title: getString('statusconfirm', 'local_questions'),
            type: ModalFactory.types.SAVE_CANCEL,
            body: getString('confirmationbodymessage', 'local_questions', displaystatus)
        }).done(function(modal) {
            this.modal = modal;
            modal.setSaveButtonText(getString('statusupdate', 'local_questions'));
            modal.getRoot().on(ModalEvents.save, function(e) {
                e.preventDefault();
                var promise = Ajax.call([{
                    methodname: 'local_questions_changequestionstatus',
                    args: params
                }]);
                promise[0].done(function(resp) {
                    $('.questioncreation_page #status_span'+questionid).text(statusText);
                    //&& adminstatus !=1
                    if(status == 'publish' && adminstatus !=1){
                        $('#understatus'+questionid).hide();
                        $('#rejectstatus'+questionid).hide();
                        $('#publishstatus'+questionid).hide();
                        $('#raedystatus'+questionid).hide();
                    }
                    if(status == 'readytoreview' && adminstatus !=1){
                        $('#raedystatus'+questionid).hide();
                        $('#hideeditdelete'+questionid).hide();
                        $('#understatus'+questionid).hide();
                    }
                    if(status == 'readytoreview' && adminstatus ==1){
                        $('#raedystatus'+questionid).hide();
                        $('#understatus'+questionid).show();
                        $('#hideeditdelete'+questionid).show();
                    }
                    if(status == 'reject' && adminstatus !=1){
                        $('#understatus'+questionid).hide();
                        $('#rejectstatus'+questionid).hide();
                        $('#publishstatus'+questionid).hide();
                        $('#draft'+questionid).show();
                    }
                    if(status == 'underreview' && adminstatus !=1){
                        $('#understatus'+questionid).hide();
                        $('#rejectstatus'+questionid).show();
                        $('#publishstatus'+questionid).show();
                    }
                    if(status == 'readytoreview' && adminstatus == 1 && canreviewselfstatus == 1){
                        $('#understatus'+questionid).show();
                        $('#raedystatus'+questionid).hide();
                        $('#hideeditdelete'+questionid).show();
                        $('#draft'+questionid).hide();
                    }
                    if(status == 'readytoreview' && adminstatus == 1 && canreviewselfstatus != 1){
                        $('#understatus'+questionid).show();
                        $('#raedystatus'+questionid).hide();
                        $('#hideeditdelete'+questionid).show();
                        $('#draft'+questionid).hide();
                    }
                    if(status == 'underreview' && adminstatus == 1){
                        $('#understatus'+questionid).hide();
                        $('#rejectstatus'+questionid).show();
                        $('#publishstatus'+questionid).show();
                    }
                    if(status == 'publish' && adminstatus ==1){
                        $('#understatus'+questionid).hide();
                        $('#rejectstatus'+questionid).hide();
                        $('#publishstatus'+questionid).hide();
                        $('#raedystatus'+questionid).hide();
                        $('#draftstatus'+questionid).show();
                    }
                    if(status == 'reject' && adminstatus ==1){
                        $('#understatus'+questionid).hide();
                        $('#rejectstatus'+questionid).hide();
                        $('#publishstatus'+questionid).hide();
                        $('#raedystatus'+questionid).hide();
                        $('#draft'+questionid).show();
                        $('#hideeditdelete'+questionid).show();
                    }
                    if(status == 'draft' && adminstatus ==1){
                        $('#hideeditdelete'+questionid).show();
                        $('#raedystatus'+questionid).show();
                        $('#draftstatus'+questionid).hide();
                        $('#draft'+questionid).hide();
                       
                    }
                    modal.hide();
                }).fail(function() {
                    console.log('exception');
                });
            }.bind(this));
            modal.show();
        }.bind(this));
    }       
    });
}

