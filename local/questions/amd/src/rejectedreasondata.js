/**
 * Add a create new popup rejected reason data to the page.
 *
 * @module     local_questions/rejectedreasondata
 * @class      Questions
 * @copyright  2023 Moodle India Information Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
        'core/str',
        'core/modal_factory',
        'core/modal_events',
        'core/fragment',
        'core/ajax',
        'local_questions/jquery.dataTables'],
    function($, str, ModalFactory, ModalEvents, Fragment, Ajax, dataTable) {
        /**
         * Constructor
         *
         * @param {String} used to find triggers for the new modal.
         * @param {int} contextid
         *
         * Each call to init gets it's own instance of this class.
         */
        var NewPopup = function(args) {
            var self = this;
            self.init(args);
        };
        /**
	     * Initialise the class.
	     *
	     * @param {String}  used to find triggers for the new modal.
	     * @private
	     * @return {Promise}
	     */
	    NewPopup.prototype.init = function() {
	    	var self = this;
	    	$(document).on('click', '#dataofrejected', function() {

            var questionid = $(this).data('questionid');
            var qbentryid = $(this).data('qbentryid');

            if (typeof questionid != 'undefined' && typeof qbentryid != 'undefined') {
	            var params = { questionid : questionid, qbentryid : qbentryid };
	        } else {
	            var params = {};
	        }
            str.get_string('rejectedreviewdata', 'local_questions').then(function(title) {
                ModalFactory.create({
                    type: ModalFactory.types.CANCEL,
                    title: title,
                    body: Fragment.loadFragment('local_questions', 'rejected_reviewdata', 1, params),
                }).done(function(modal) {
                    // Keep a reference to the modal.
                    self.modal = modal;
                    // Forms are big, we want a big modal.
                    self.modal.setLarge();
         
                    // We want to reset the form every time it is opened.
                    self.modal.getRoot().on(ModalEvents.cancel, function() {
                        // self.modal.setBody('');
                        self.modal.hide();
                        self.modal.destroy();
                    }.bind(this));
                    self.modal.show();

                    self.modal.getRoot().on(ModalEvents.bodyRendered, function() {
                        self.dataTableshow();
                    }.bind(this));
	                });
	            });
	        });
	    };
        NewPopup.prototype.dataTableshow = function() {
            $('#page-local-questions-questionbank_view #qrejectedreasonstatus').dataTable({
                'bPaginate': true,
                'bFilter': true,
                'bLengthChange': true,
                'lengthMenu': [
                    [5, 10, 25, 50, 100, -1],
                    [5, 10, 25, 50, 100, 'All']
                ],
                "language": {
                    'emptyTable': 'No Records Found',
                    "paginate": {
                        "next": ">",
                        "previous": "<"
                    },
                    "search": "",
                    "searchPlaceholder": "Search....",
                },

                'bProcessing': true,
            });
        };

		return /** @alias module:local_questions/rejectedreasondata */ {
	        // Public variables and functions.
	        /**
	         * Attach event listeners to initialise this module.
	         *
	         * @method init
	         * @param {string}  The CSS  used to find nodes that will trigger this module.
	         * @return {Promise}
	         */
	        init: function(args) {
	        	return new NewPopup(args);
	        },
	    };
	}
);
