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
 * TODO describe module homepage
 *
 * @module     theme_horizon/homepage
 * @copyright  2023 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import Ajax from 'core/ajax';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';
import ModalFactory from 'core/modal_factory';
import cardPaginate from 'theme_horizon/cardPaginate';
var selector = {
}
export default class HomePage {
    confirmbox(message) {
        ModalFactory.create({
            body: message,
            type: ModalFactory.types.ALERT,
            buttons: {
                cancel: getString('ok'),
            },
            removeOnClose: true,
        })
        .done(function(modal) {
           modal.show();
        });
    }
}

