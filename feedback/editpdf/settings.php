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
 * Settings for fastassignfeedback PDF plugin
 *
 * @package   fastassignfeedback_editpdf
 * @copyright 2013 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Enabled by default.
$settings->add(new admin_setting_configcheckbox('fastassignfeedback_editpdf/default',
                   new lang_string('default', 'fastassignfeedback_editpdf'),
                   new lang_string('default_help', 'fastassignfeedback_editpdf'), 1));

// Stamp files setting.
$name = 'fastassignfeedback_editpdf/stamps';
$title = get_string('stamps','fastassignfeedback_editpdf');
$description = get_string('stampsdesc', 'fastassignfeedback_editpdf');

$setting = new admin_setting_configstoredfile($name, $title, $description, 'stamps', 0,
    array('maxfiles' => 8, 'accepted_types' => array('image')));
$settings->add($setting);

// Ghostscript setting.
$systempathslink = new moodle_url('/admin/settings.php', array('section' => 'systempaths'));
$systempathlink = html_writer::link($systempathslink, get_string('systempaths', 'admin'));
$settings->add(new admin_setting_heading('pathtogs', get_string('pathtogs', 'admin'),
        get_string('pathtogspathdesc', 'fastassignfeedback_editpdf', $systempathlink)));

$url = new moodle_url('/mod/fastassignment/feedback/editpdf/testgs.php');
$link = html_writer::link($url, get_string('testgs', 'fastassignfeedback_editpdf'));
$settings->add(new admin_setting_heading('testgs', '', $link));
