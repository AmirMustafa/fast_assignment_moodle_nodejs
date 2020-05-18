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
 * This file contains the version information for the comments feedback plugin
 *
 * @package fastassignfeedback_editpdf
 * @copyright  2012 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serves assignment feedback and other files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options - List of options affecting file serving.
 * @return bool false if file not found, does not return if found - just send the file
 */
function fastassignfeedback_editpdf_pluginfile($course,
                                           $cm,
                                           context $context,
                                           $filearea,
                                           $args,
                                           $forcedownload,
                                           array $options=array()) {
    global $USER, $DB, $CFG;

    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    if ($context->contextlevel == CONTEXT_MODULE) {

        require_login($course, false, $cm);
        $itemid = (int)array_shift($args);

        $fastassignment = new fastassignment($context, $cm, $course);

        $record = $DB->get_record('fastassignment_grades', array('id' => $itemid), 'userid,assignment', MUST_EXIST);
        $userid = $record->userid;
        if ($fastassignment->get_instance()->id != $record->assignment) {
            return false;
        }

        // Rely on mod_fastassignment checking permissions.
        if (!$fastassignment->can_view_submission($userid)) {
            return false;
        }

        $relativepath = implode('/', $args);

        $fullpath = "/{$context->id}/fastassignfeedback_editpdf/$filearea/$itemid/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        // Download MUST be forced - security!
        send_stored_file($file, 0, 0, true, $options);// Check if we want to retrieve the stamps.
    }

}
