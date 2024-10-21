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
 * Capability definitions.
 *
 * @package    local
 * @subpackage Questions

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$capabilities = array(
  
   'local/questions:qcreater' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),

   'local/questions:qreviewer' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
    'local/questions:qmanager' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:contentcreator' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:testcenterexpert' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:viewquestion' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:createquestion' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:editquestion' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),

   'local/questions:deletequestion' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
    'local/questions:publishunpublishquestion' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:questionallow' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:publishedquestions' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:questionhierarchy' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:canreviewself' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:qhdeleteaction' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:qheditaction' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:adminreports' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:studentreports' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:caneditanystatus' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:candeleteanystatus' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
   'local/questions:canchangestatus' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
    'local/questions:allowallsources' => array(
      'riskbitmask' => RISK_SPAM,
      'captype' => 'write',
      'contextlevel' => CONTEXT_SYSTEM,
      'archetypes' => array()
   ),
);
