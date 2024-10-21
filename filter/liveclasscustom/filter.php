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
 * liveclasscustom filter
 *
 * Documentation: {@link https://moodledev.io/docs/apis/plugintypes/filter}
 *
 * @package    filter_liveclasscustom
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class filter_liveclasscustom extends moodle_text_filter {

  /**
   * Filter text
   *
   * @param string $text some HTML content to process.
   * @param array $options options passed to the filters
   * @return string the HTML content after the filtering has been applied.
   */
  public function filter($text, array $options = []) {
    $replacingurl = get_config('local_masterdata','sthreerootpath');
    if($replacingurl) {
      $text =   str_replace("https://prod-horizon-videos.s3.amazonaws.com/",$replacingurl,$text);
    }
    return $text;
  }
}
