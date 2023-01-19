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

require_once("global_constants.php");

/**
 * Form for editing Content-Processor block instances.
 *
 * @package   block_content_processor
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_content_processor extends block_base {

	private static string $API_KEY;

    function init() {
        $this->title = get_string('pluginname', CP_PLUGIN::ID);
		self::$API_KEY = get_config(CP_PLUGIN::ID, 'apikey');
    }

    function has_config()
    {
        return true;
    }

    function get_content() {
        $footer = '';

        if ($this->content !== NULL) {
            return $this->content;
        }

        $content = '<hr>';

        if ( !empty(self::$API_KEY) ) {
            $footer = html_writer::link(
                (new moodle_url(
                    '/blocks/' . CP_PLUGIN::NAME . '/index.php'
                ))->__toString(),
                get_string('link.openPlugin', CP_PLUGIN::ID)
            );
        } else {
            \core\notification::info(get_string('apikey.prompt', CP_PLUGIN::ID));
            $settingslink = html_writer::link(
				(new moodle_url(
					'/admin/settings.php',
					[ 'section' => 'blocksettingcontent_processor']
				))->__toString(),
				get_string('settings', CP_PLUGIN::ID)
            );
            $content .= get_string(
                'plugin.activatePrompt', CP_PLUGIN::ID, $settingslink
            );
        }

        $this->content = new stdClass;
        $this->content->text = $content;
        $this->content->footer = $footer;
        return $this->content;
    }
}
