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
 * Initialise the repaginate dialogue on quiz editing page.
 *
 * @module    mod_quiz/repaginate
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/modal_factory', 'core/modal_events', 'core/ajax', 'core/templates'], function($, ModalFactory, ModalEvents, Ajax, Templates) {

    var SELECTORS = {
        REGRADEID: '#regradequestions_quiz',
        HEADER: 'header',
        BODY: 'body'
    };

    /**
     * Initialise the repaginate button and add the event listener.
     */
    var init = function() {
        $(document).on('click', SELECTORS.REGRADEID, function(){
            ModalFactory.create(
                {
                    title: $(SELECTORS.REGRADEID).data(SELECTORS.HEADER),
                    body: $(SELECTORS.REGRADEID).data(SELECTORS.BODY),
                    type: ModalFactory.types.SAVE_CANCEL,
                    large: false,
                }
            ).done(
                function (modal) {
                  self.modal = modal;
                  modal.setSaveButtonText($(SELECTORS.REGRADEID).data(SELECTORS.HEADER));
                  console.log('here');
                  modal.getRoot().on(
                    ModalEvents.save,
                    function (e) {
                        e.preventDefault();
                        self.modal.setBody(Templates.render('core/loading', {}));
                        var promise = Ajax.call([
                            {
                                methodname: "local_questions_regrade_all_questions",
                                args: {quizid: $(SELECTORS.REGRADEID).data('quizid')},
                            },
                        ]);
                        promise[0].done(function(resp){
                            window.location.reload();
                        });
                    }.bind(this));
                  modal.show();
                }.bind(this)
            );
        });
    };
    return {
        init: init
    };
});