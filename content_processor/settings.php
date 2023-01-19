<?php
/**
 * Strings for component 'block_content_processor', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   block_content_processor
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once("global_constants.php");

if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configtext(
            CP_PLUGIN::ID . '/apikey',
            get_string('apikey', CP_PLUGIN::ID),
            get_string(
                'settings.apiKey.info',
                CP_PLUGIN::ID,
                'https://quizco.de/kontakt/'
            ),
            '',
            PARAM_RAW_TRIMMED,
            API_KEY_LENGTH
        )
    );
}