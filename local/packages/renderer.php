<?php
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
 * Packages renderer file
 *
 * This file defines the current version of the local_goals Moodle code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_packages
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_packages_renderer extends plugin_renderer_base {
    /**
     * [get_goals_view description]
     * @param  [type] $filter [description]
     * @return [type] [description]
     */
    public function get_packages_view($filter = false) {
        $systemcontext = context_system::instance();
        $options = array('targetID' => 'manage_packages', 'perPage' => 6, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'list');
        $options['methodName'] = 'local_packages_view';
        $options['templateName'] = 'local_packages/classes_details';
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
            'targetID'    => 'manage_packages',
            'options'     => $options,
            'dataoptions' => $dataoptions,
            'filterdata'  => $filterdata,
            'widthclass'  => 'col-md-12',
        ];
        if ($filter) {
            return $context;
        } else {
            return $this->render_from_template('theme_horizon/cardPaginate', $context);
        }
    }
}
