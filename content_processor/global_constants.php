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
 * CONTENT PROCESSOR
 *
 * @package    block_content_processor
 * @copyright  2022 QuizCo GmbH (http://quizco.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Types of collections that are distinguished for export.
 */
class COLLECTION_TYPES {
    const
        QUIZ = 'quiz',
        KEY_SENTENCE = 'key-sentence',
        VARIATION = 'variation',
        SUMMARY = 'summary';
}

/**
 * Types of quizzes.
 */
class QUIZ_TYPES {
    const
        CLOZE = 1,
        OPEN = 2,
        SINGLE_CHOICE = 3,
        TRUE_FALSE = 6;
}

/**
 * Phases of a communication relationship.
 */
class TRANSMISSION_PHASES {
    const
        OFF = 1,
        HANDSHAKE = 2,
        DATA = 3;
}

/**
 * Consecutive steps of a handshake procedure.
 */
class HANDSHAKE_STEPS {
    const
        DENY_ACCESS = 0,
        REQUEST_TOKEN = 1,
        GRANT_ACCESS = 2;
}

/**
 * Adressing the Content-Processor app.
 */
class CP_APP {
	const
		HOSTNAME = 'http://localhost:3000',
		// HOSTNAME = 'https://content-processor-api-app.web.app',

		PARAM_ORIGIN = 'src',

		DELIMITERS = array(
			'PARAM_BEGIN' => '#',
			'PARAM_KEY_VALUE' => ':'
		);
}

/**
 * Mapping between CP and Moodle data structures.
 */
class MOODLE_MAPPING {
	// Map `QUIZ_TYPES` indices to Moodle question types.
	const QUESTION_TYPES = array(
		1 => 'gapselect',
		2 => 'essay',
		3 => 'multichoice',
		6 => 'truefalse'
	);
}

/**
 * Plugin formalities. */
class CP_PLUGIN {
    const
        ID      = 'block_content_processor',    // <TYPE>_<NAME>
        NAME    = 'content_processor',
        TITLE   = 'Content-Processor',
        TYPE    = 'block',
        TYPEDIR = 'blocks';
}

/**
 * Length of the API key.
 */
define('API_KEY_LENGTH', 36);

/**
 * Name of our product.
 *
 * Independent of the plugin name.
 */
define('CP_NAME', 'Content-Processor');