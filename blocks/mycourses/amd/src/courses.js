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


const Selectors = {
    actions: {
        showcourse: '[data-action="showcoursediv"]',
    },
};

export const init = () => {
    let block = document.querySelectorAll('.blkcourse_card_wrap .viewcourses_btn');
        block.forEach(addEventListener('click', function(e) {

        e.stopImmediatePropagation();
        
        let element = e.target.closest(Selectors.actions.showcourse);
        if (element) {
            var ccount = element.getAttribute('data-ccount');
            var catid = element.getAttribute('data-id');
            if (ccount > 0) {
                $("#listofcourses_" + catid).toggle();
            }
            else{
                console.log('no course');
            }
        }
    }));
};
