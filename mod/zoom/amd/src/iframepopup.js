// This file is part of the Zoom plugin for Moodle - http://moodle.org/
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
 * Populates or de-populates password field based on whether the
 * password is required or not.
 *
 * @copyright  2024 mod_zoom
 * @author     Moodle India Information Solutions Pvt. Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
        'core/modal_factory',
        'core/modal_events',
        'core/fragment',
        ],
    function(ModalFactory, ModalEvents, Fragment) {
        /**
         * Constructor
         *
         * @param {String} used to find triggers for the new modal.
         * @param {int} contextid
         *
         * Each call to init gets it's own instance of this class.
         */
        var IframePopup = function(args) {
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
        IframePopup.prototype.init = function(args) {
            var self = this;
            self.id = args.id;
            self.recordingid = args.recordingid;
            ModalFactory.create({
                // title: str.get_string('iframetitle', 'mod_zoom'),
                body: self.getBody(),
                // type: ModalFactory.types.CANCEL,
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
        };
        /**
         * @method getBody
         * @private
         * @return {Promise}
         */
        IframePopup.prototype.getBody = function(formdata) {
            if (typeof formdata === "undefined") {
                formdata = {};
            }
            var params = {};
            // Get the content of the modal.
            if (typeof this.id != 'undefined') {
                var params = {jsonformdata: JSON.stringify(formdata), id:this.id, recordingid:this.recordingid };
            }
            return Fragment.loadFragment('mod_zoom', 'iframepopup', 1, params);
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
            init: function(args) {
                return new IframePopup(args);
            },
        };
    });