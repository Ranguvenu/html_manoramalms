/**
 * Add a create new group modal to the page.
 *
 * @module     local_batch/Batch
 * @class      Batch
 * @copyright  2023 Dipanshu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events',
    'core/fragment', 'core/ajax', 'core/yui'], 
    function($, str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {

    /**
     * Constructor
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @param {int} contextid
     *
     * Each call to init gets it's own instance of this class.
     */
    var NewPopup = function(args) {
        this.contextid = args.contextid;
        this.userid = args.userid;
        var self = this;
        self.init(args.selector);
    };
    /**
     * Initialise the class.
     *
     * @param {String} selector used to find triggers for the new group modal.
     * @private
     * @return {Promise}
     */
    NewPopup.prototype.init = function(selector) {
        var self = this;

        // program popup.
    $(document).ready(function () {
        $(document).on('click', '#viewactivity', function(){
        
            str.get_string('dueacivity', 'block_notification').then(function(title) { ModalFactory.create({
                    title: title,
                    body: self.getBody()
                }).done(function(modal) {
                    // Keep a reference to the modal.
                    self.modal = modal;
                    self.modal.getRoot().addClass('dueactivitymodal');
                    // Forms are big, we want a big modal.
                    self.modal.setLarge();
         
                    // We want to reset the form every time it is opened.
                    self.modal.getRoot().on(ModalEvents.hidden, function() {
                        // self.modal.setBody('');
                        self.modal.hide();
                        self.modal.destroy();
                    }.bind(this));

                    // We want to reset the form every time it is opened.
                    self.modal.getRoot().on(ModalEvents.cancel, function() {
                        // self.modal.setBody('');
                        self.modal.hide();
                        self.modal.destroy();
                    }.bind(this));
                    self.modal.show();
                });

            });
        });
    });
    }

    
    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    NewPopup.prototype.getBody = function(formdata) {

        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // Get the content of the modal.
        if(typeof this.userid != 'undefined'){
            var params = {userid:this.userid, jsonformdata: JSON.stringify(formdata)};
        }else{
            var params = {};
        }
        
        return Fragment.loadFragment('block_notification', 'due_activities_list', this.contextid, params);
    };

    return /** @alias module:local_evaluation/newevaluation */ {
        // Public variables and functions.
        /**
         * Attach event listeners to initialise this module.
         *
         * @method init
         * @param {string} selector The CSS selector used to find nodes that will trigger this module.
         * @param {int} contextid The contextid for the course.
         * @return {Promise}
         */
        init: function(args) {
           
            this.Datatable();
            return new NewPopup(args);
        },
        Datatable: function() {
            
        },
        
    };
});
