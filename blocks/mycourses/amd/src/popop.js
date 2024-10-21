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
 * Add a popop to the page.
 *
 * @module     block_mycourses
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
        'core/str',
        'core/modal_factory',
        'core/modal_events',
        'core/fragment'],
    function (str, ModalFactory, ModalEvents, Fragment) {

        /**
         * Constructor
         *
         * @param {String} selector used to find triggers for the popop.
         * @param {int} contextid
         *
         * Each call to init gets it's own instance of this class.
         */
        var NewPopup = function(firstlogin) {
            var self = this;
            self.init(firstlogin);
        };

        /**
         * Initialise the class.
         *
         * @param {String} selector used to find triggers for the new popop.
         * @private
         * @return {Promise}
         */
        NewPopup.prototype.init = function(firstlogin) {
            var self = this;
            var flogin = firstlogin;
            if (flogin) {
                ModalFactory.create({
                    title: 'User Guide',
                    body: self.getBody()
                }).done(function(modal) {
                    // Keep a reference to the modal.
                    self.modal = modal;
                    // Forms are big, we want a big modal.
                    self.modal.setLarge();
         
                    // We want to reset the form every time it is opened.
                    self.modal.getRoot().on(ModalEvents.hidden, function() {
                        self.modal.destroy();
                    }.bind(this));

                    // We want to reset the form every time it is opened.
                    self.modal.getRoot().on(ModalEvents.cancel, function() {
                        self.modal.destroy();
                    }.bind(this));
                    self.modal.show();
                });
            }
        };

        /**
         * @method getBody
         * @private
         * @return {Promise}
         */
        NewPopup.prototype.getBody = function(formdata) {

            if (typeof formdata === "undefined") {
                formdata = {};
            }
            var params = {};
            // Get the content of the modal.
            if (typeof this.id != 'undefined') {
                var params = {jsonformdata: JSON.stringify(formdata)};
            }
            return Fragment.loadFragment('block_mycourses', 'userguide_popop', 1, params);
        };

        return {
            // Public variables and functions.
            /**
             * Attach event listeners to initialise this module.
             *
             * @method init
             * @param {string} selector The CSS selector used to find nodes that will trigger this module.
             * @param {int} contextid The contextid for the course.
             * @return {Promise}
             */
            init: function(firstlogin) {
                return new NewPopup(firstlogin);
            },
        };
});
