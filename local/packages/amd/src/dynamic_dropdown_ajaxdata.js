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
 * TODO describe module dynamic_dropdown_ajaxdata
 *
 * @module     local_packages/dynamic_dropdown_ajaxdata
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    return /** @alias module:tool_lpmigrate/frameworks_datasource */ {
        /**
         * Process the results for auto complete elements.
         * @param {String} selector The selector of the auto complete element.
         * @param {Array} results An array or results.
         * @return {Array} New array of results.
         */
        processResults: function(selector, results) {
            var options = [];

            $.each(results.data, function(index, response) {
                options.push({
                    value: response.id,
                    label: response.fullname
                });
            });
            return options;
        },
        /**
         * Source of data for Ajax element.
         *
         * @param {String} selector The selector of the auto complete element.
         * @param {String} query The query string.
         * @param {Function} callback A callback function receiving an array of results.
         */
        /* eslint-disable promise/no-callback-in-promise */
        transport: function(selector, query, callback) {
            var el = $(selector),
            packageid = 0;
            courseid = 0;
            type = el.data('type');
        
            switch(type){
                case 'batches':
                    packageid = el.data('packageid');
                    courseid = el.data('courseid');
                break;

                case 'teachers':
                    packageid = el.data('packageid');
                    courseid = el.data('courseid');
                break;
            }
            Ajax.call([{
                methodname: 'local_packages_ajaxdatalist',
                args: {query:query,type: type,packageid: packageid, courseid: courseid}
            }])[0].then(callback).catch(Notification.exception);
        },
    };
});
