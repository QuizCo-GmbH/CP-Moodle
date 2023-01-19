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
 * Content-Processor
 *
 * @package    block_content_processor
 * @copyright  2022 QuizCo GmbH (http://quizco.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require './utils.php';	// Load first!
require_once basePath() . 'config.php';
require_once 'global_constants.php';

function getCategories($skipTop = true) {
	global $DB;
	$categories = array();

	if ( $skipTop ) {
		$categories = $DB->get_records_select(
			'question_categories', '`parent` <> 0'
		);
	} else {
		$categories = $DB->get_records('question_categories');
	}

	return $categories;
}

$categories = getCategories();

$PAGE->set_url(new moodle_url(
    '/' . CP_PLUGIN::TYPEDIR
    . '/' . CP_PLUGIN::NAME
    . '/' . basename($_SERVER['SCRIPT_FILENAME'])
));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', CP_PLUGIN::ID));

echo $OUTPUT->header();

define( 'CP_APP_URL',
		CP_APP::HOSTNAME . '/' . CP_APP::DELIMITERS['PARAM_BEGIN'] 
		. CP_APP::PARAM_ORIGIN . CP_APP::DELIMITERS['PARAM_KEY_VALUE'] );
define('ID_BUTTON_CANCEL'			, 'buttonCancel');
define('ID_BUTTON_SUBMIT'			, 'buttonSubmit');
define('ID_CATEGORY_SELECTION'		, 'targetCategory');
define('ID_IFRAME'					, 'app_frame');
define('ID_IFRAME_CONTAINER'		, 'app_container');
define('ID_OVERLAY'					, 'overlay');
define('ID_POPUP'					, 'ui-popup');
define('JSON_PROP_QUESTION_CATEGORY', 'questionCategory');

$backLink = html_writer::link(
    (new moodle_url('/'))->__toString(),
    get_string('general.back', CP_PLUGIN::ID)
);

?>

<div style="margin: 0 auto; width: 90%; min-width: 600px; max-width: 1200px;">
    <?php echo $backLink; ?>

    <h1><?php echo get_string('pluginname', CP_PLUGIN::ID); ?></h1>

    <?php # Texts remain static as long as we don't really go full I18N.
    ?>
    <p>
        Mit dem <?php echo CP_NAME; ?> erstellst du im Handumdrehen Quizfragen aus deinen Texten. Gib hierzu deinen Text ein und wähle aus, welche Arten von Fragen du generieren möchtest. Mit dem Button „Fragen generieren“ sendest du deinen Text zur Verarbeitung an den <?php echo CP_NAME; ?>.<br/>
        Derzeit werden höchstens vier Fragen pro Fragentyp generiert. Die Fragen und Antwortmöglichkeiten kannst du auf Wunsch noch überarbeiten.
    </p>
    <p>Die fertigen Fragen kannst du dann drucken (das heißt, mit oder ohne Lösung in eine druckbare Textdatei ausgeben) und in Moodle speichern. Wenn du sie speicherst, wählst du eine Kategorie aus, unter der sie in die Fragensammlung eingeordnet werden.</p>

<?php
# Show the plugin content only for logged-in users.
if( isloggedin() ): ?>

    <script type="text/JavaScript">
        const COLLECTION_TYPES = {
            QUIZ: 'quiz',
            KEY_SENTENCE: 'key-sentence',
            VARIATION: 'variation',
            SUMMARY: 'summary',
        };

        const QUIZ_TYPES = {
            CLOZE: 1,
            OPEN: 2,
            SINGLE_CHOICE: 3,
            TRUE_FALSE: 6,
        };

        const TRANSMISSION_PHASES = {
            OFF: 0,
            HANDSHAKE: 1,
            DATA: 2,
        };

        const HANDSHAKE_STEPS = {
            DENY_ACCESS: 0,
            REQUEST_TOKEN: 1,
            GRANT_ACCESS: 2,
        };

        const Styles = {
            buttonCancel: {
                float: 'right',
                'margin-left': '1em',
                'font-size': '1.2em',
                border: 'none',
                background: '#ddd',
                color: '#495057',
                padding: '0.5em 1.2em',
                'text-transform': 'uppercase',
                'border-radius': '5px'
            },
            
            buttonSubmit: {
                float: 'right',
                'margin-left': '1em',
                'font-size': '1.2em',
                border: 'none',
                background: '#1976d2',
                color: '#fff',
                padding: '0.5em 1.2em',
                'text-transform': 'uppercase',
                'font-weight': 'bold',
                'border-radius': '5px'
            },
            
            overlay: {
                position: 'absolute',
                width: '100%',
                height: '100%',
                'z-index': 200,
                background: 'rgba(0, 0, 0, 0.1)',
                left: 0,
                top: 0,
                overflow: 'hidden',
                display: 'none',
                visibility: 'hidden',
            },
            
            overlayVisible: {
                display: 'block',
                visibility: 'visible',
            },

            popup: {
                position: 'relative',
                width: '500px',
                height: '250px',
                background: '#fff',
                'border-radius': '5px',
                left: '50%',
                top: '50%',
                transform: 'translate(-50%, -50%)',
                padding: '2em',
            },
            
            select: {
                margin: '1em 0',
                width: '100%',
                height: '2.5em',
                'font-size': '16px',
                border: '1px solid rgba(0, 0, 0, 0.2)',
                'border-radius': '5px',
                padding: '6px',
            }
        };

        // Define globally for access across function scopes.
        let transmissionData = {};

        const getIFrameSrc = () => '<?php echo CP_APP_URL; ?>'
            + encodeURIComponent(window.location.origin);
        
        const createIFrame = () => {
            const ATTRIBUTES = {
                id: '<?php echo ID_IFRAME; ?>',
                loading: 'eager',
                src: getIFrameSrc(),
                style: 'border: none; width: 100%; height: 800px',
            };

            let iFrame = document.createElement('iframe');
            for (const ATTR in ATTRIBUTES) {
                iFrame.setAttribute(ATTR, ATTRIBUTES[ATTR]);
            }

            return iFrame;
        };

        const sendSaveRequest = (inputData) => {
            const CATEGORY_KEY = 'questionCategory';

            inputData[ CATEGORY_KEY ] = 
                document.getElementById('<?php echo ID_CATEGORY_SELECTION; ?>')
                .value;

            fetch("save_quiz.php", {
                method: "POST",
                headers: {"Content-type": "application/json; charset=UTF-8"},
                body: JSON.stringify(inputData),
            })
            .catch(error => console.error({error}));
        };

        window.addEventListener('message', (e) => { fileMessage(e)});
        const fileMessage = (event) => {
            const
                HOST_ORIGIN = '<?php echo CP_APP::HOSTNAME; ?>',
                MESSAGE = event.data;

            if( !( MESSAGE.hasOwnProperty('header')
                    && MESSAGE.header.hasOwnProperty('origin')
                    && MESSAGE.header.origin === HOST_ORIGIN ) ) {
                return;
            }

            switch (MESSAGE.header.phase) {
                case TRANSMISSION_PHASES.HANDSHAKE:
                    // auth info
                    advanceAuth(MESSAGE);
                    break;
                case TRANSMISSION_PHASES.DATA:
                    // data transmission
                    processDataTransmission(MESSAGE);
                    break;
                default: return;
            }
        };

        const advanceAuth = (message) => {
            const
                CLIENT_ORIGIN = window.location.origin,
                HOST_ORIGIN = '<?php echo CP_APP::HOSTNAME; ?>',
                HOST_WINDOW = document.getElementById('<?php echo ID_IFRAME; ?>')
                            .contentWindow,
                TOKEN = '<?php echo get_config( 'block_content_processor',
                                                'apikey' ); ?>';

            let authMessage = {
                header: {
                    phase: TRANSMISSION_PHASES.HANDSHAKE,
                    handshakeStep: HANDSHAKE_STEPS.REQUEST_TOKEN,
                    origin: CLIENT_ORIGIN,
                },
                payload: {},
            };

            if( !( message.header.hasOwnProperty('phase')
                    && message.header.phase === TRANSMISSION_PHASES.HANDSHAKE
                    && message.header.hasOwnProperty('handshakeStep') ) ) {
                return;
            }
            
            if ( message.header.handshakeStep === HANDSHAKE_STEPS.REQUEST_TOKEN ) {
                authMessage.payload['token'] = TOKEN;

                try {
                    HOST_WINDOW.postMessage(authMessage, HOST_ORIGIN);
                } catch (error) {
                    console.error({error});
                }
            }
        };

        const processDataTransmission = (message) => {
            const
                CLIENT_ORIGIN = window.location.origin,
                HOST_ORIGIN = '<?php echo CP_APP::HOSTNAME; ?>',
                HOST_WINDOW = document.getElementById('<?php echo ID_IFRAME; ?>')
                            .contentWindow;

            if( !(message.header.hasOwnProperty('phase')
                    && message.header.phase === TRANSMISSION_PHASES.DATA
                    && message.header.hasOwnProperty('origin')
                    && message.header.origin === HOST_ORIGIN) ) {
                return;
            }

            transmissionData = message.payload.data;
            showModal();
        };

        const applyStyling = (styles, id) => {
            let styleArr = [];

            if (!(styles && id)) return;

            el = document.getElementById(id);
            if (!el) return;

            for (const prop in styles) {
                styleArr.push(prop + ': ' + styles[prop]);
            }

            el.setAttribute('style', styleArr.join('; '));
        };

        const closeCategoryModal = () => {
            // Return to default (= hidden) style.
            applyStyling(Styles.overlay, '<?php echo ID_OVERLAY; ?>');
        };

        const saveToMoodle = (event) => {
            const SELECTED_CATEGORY = event.target
                    .closest('#<?php echo ID_POPUP;?>')
                    .querySelector('#<?php echo ID_CATEGORY_SELECTION;?>')
                    .value,
                QUESTION_CATEGORY = '<?php echo JSON_PROP_QUESTION_CATEGORY;?>';

            transmissionData[QUESTION_CATEGORY] = SELECTED_CATEGORY;
            sendSaveRequest(transmissionData);
            closeCategoryModal();
        };

        const showModal = () => {
            // Override style properties controlling the visibility.
            const STYLES = {
                ...(Styles.overlay),
                ...(Styles.overlayVisible)
            };

            applyStyling(STYLES, '<?php echo ID_OVERLAY; ?>');
        };
    </script>

    <div id="<?php echo ID_IFRAME_CONTAINER; ?>"><div>
    <script type="text/javascript">
        document.getElementById('<?php echo ID_IFRAME_CONTAINER; ?>')
            .append(createIFrame());
    </script>

    <div id="<?php echo ID_OVERLAY; ?>">
        <div id="<?php echo ID_POPUP; ?>">
            <h2>Kategorie wählen</h2>

            <p>In welcher Kategorie sollen die Fragen gespeichert werden?</p>

            <select id="<?php echo ID_CATEGORY_SELECTION; ?>">
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category->id; ?>"><?php echo $category->name; ?></option>
            <?php endforeach; ?>
            </select>

            <button id="<?php echo ID_BUTTON_SUBMIT; ?>">Speichern</button>
            <button id="<?php echo ID_BUTTON_CANCEL; ?>">Abbrechen</button>
        </div>
    </div>

    <script type="text/javascript">

    applyStyling(Styles.overlay, '<?php echo ID_OVERLAY; ?>');
    applyStyling(Styles.popup, '<?php echo ID_POPUP; ?>');
    applyStyling(Styles.select, '<?php echo ID_CATEGORY_SELECTION; ?>');
    applyStyling(Styles.buttonCancel, '<?php echo ID_BUTTON_CANCEL; ?>');
    document.getElementById('<?php echo ID_BUTTON_CANCEL; ?>').addEventListener(
        'click', closeCategoryModal
    );
    applyStyling(Styles.buttonSubmit, '<?php echo ID_BUTTON_SUBMIT; ?>');
    document.getElementById('<?php echo ID_BUTTON_SUBMIT; ?>').addEventListener(
        'click', saveToMoodle
    );

    </script>

<?php else: ?>

    <p>Bitte logge dich ein, um das <?php echo CP_PLUGIN::TITLE; ?>-Plugin zu benutzen.</p>

<?php endif; ?>

</div>

<?php echo $OUTPUT->footer();