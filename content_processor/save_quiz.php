<?php
require './utils.php';	// Load first!
require_once basePath() . 'config.php';
require_once 'global_constants.php';
global $DB;

$fallbackCategory = $DB->get_field_sql('
    SELECT	min(id)
	FROM	`mdl_question_categories`;
');

/* 
 * Standard instruction for answering is hidden.
 */
define('ANSWER_CHOICE_SHOW_INSTRUCTION'	, 0);

/* 
 * Grading/evaluation of answer options -> correct answer receives 100%,
 * the others 0%.
 */
define('ANSWER_FRACTIONS' , array(
	'correct'	=> 1,
	'wrong'		=> 0
));

/* 
 * Answers are not numbered by default.
 */
define('ANSWER_NUMBERING'				, 'none');

/*
 * Labels of the "true" and "false" answers. Indices emulate boolean.
 */
define('ANSWER_VALUES_TRUE_FALSE'		, array('Falsch', 'Richtig'));

/* 
 * Placeholder representing the gap to be filled correctly.
 */
define('CLOZE_ANSWER_PLACEHOLDER'		, '_________');

/* 
 * RegEx pattern for the answer placeholder.
 */
define('CLOZE_ANSWER_PLACEHOLDER_RE',
	   '/' . CLOZE_ANSWER_PLACEHOLDER . '/');

/* 
 * Index of the correct answer within the `$answers` array.
 */
define('CLOZE_CORRECT_ANSWER_IDX'		, 0);

/* 
 * Default value for any feedback given to the user.
 */
define('DEFAULT_FEEDBACK'				, '');

/* 
 * Version number of newly inserted questions.
 */
define('DEFAULT_QUESTION_VERSION'		, 1);

/* 
 * Mapping of question types to amount of distractors.
 */
define('DISTRACTORS_COUNT' , array(
	QUIZ_TYPES::CLOZE => 1,	// minimum
	QUIZ_TYPES::SINGLE_CHOICE => 3,
	QUIZ_TYPES::TRUE_FALSE => 1
));

/* 
 * Name of the JSON property containing the question category.
 */
define('JSON_PROP_QUESTION_CATEGORY'	, 'questionCategory');

/* 
 * Format/features of the text input for open questions.
 */
define('OPEN_QUESTION_RESPONSE_FORMAT'	, 'plain');

/* 
 * Maximal count of characters for the question name.
 */
define('QUESTION_NAME_MAX'				, 49);

/* 
 * Allow only one correct answer (single-choice model).
 */
define('SINGLE_CHOICE_FLAG'				, 1);

/* 
 * Category the created questions are stored in.
 */
$QUESTION_CATEGORY = $fallbackCategory;

$json_data = json_decode(file_get_contents('php://input'));

if (isset($json_data->{JSON_PROP_QUESTION_CATEGORY})){
	$QUESTION_CATEGORY = $json_data->{JSON_PROP_QUESTION_CATEGORY};
}

/**
 * We're starting off with quizzes only, use only COLLECTION_TYPES.QUIZ.
 */
if ( !isset($json_data->{COLLECTION_TYPES::QUIZ}) )
    die('Error: Missing data. Aborting.');

foreach($json_data->{COLLECTION_TYPES::QUIZ} as $quizItem) {
	// Skip items with no set question type.
	if(!isset($quizItem->type)) continue;

	try {
		switch ($quizItem->type) {
			case QUIZ_TYPES::CLOZE:
				insertClozeQuestion($quizItem->quiz);
				break;
			
			case QUIZ_TYPES::OPEN:
				insertOpenQuestion($quizItem->quiz);
				break;
			
			case QUIZ_TYPES::SINGLE_CHOICE:
				insertSingleChoiceQuestion($quizItem->quiz);
				break;
			
			case QUIZ_TYPES::TRUE_FALSE:
				insertTrueFalseQuestion($quizItem->quiz);
				break;
			
			// Skip unknown question types.
			default: continue;
		}
	} catch (\Throwable $th) {
		var_dump($th);
	}
}

function insertTrueFalseQuestion($data) {
	global $DB, $QUESTION_CATEGORY, $USER;
	$timestamp = time();

	// Skip insertion if the data object is missing essential
	// properties.
	if( !( isset($data->sentence) && isset($data->question)
			&& isset($data->answer) && isset($data->distractors)
			&& count($data->distractors) 
				== DISTRACTORS_COUNT[ QUIZ_TYPES::TRUE_FALSE ] )
	) {
		return;
	}

	# 1. Create question object as stdClass instance.
	$question = (object) array(
		'name'				=> strlen($data->question) > QUESTION_NAME_MAX
								? substr($data->question, 0, QUESTION_NAME_MAX)
									. '…'
								: $data->question,
		'questiontext'		=> $data->question,
		'qtype'				=>
			MOODLE_MAPPING::QUESTION_TYPES[ QUIZ_TYPES::TRUE_FALSE ],

		// other defaults
		'generalfeedback'	=> DEFAULT_FEEDBACK,	// mandatory
		'timecreated'		=> $timestamp,
		'timemodified'		=> $timestamp,
		'createdby'			=> $USER->id,
		'modifiedby'		=> $USER->id
	);

	# 2. Create answer objects as stdClass instances. Place the "false"/
	#	 "true" answers at indices 0/1, respectively
	$answers = array();
	// Evaluates to 0, if the correct answer is "wrong", and to 1, if
	// its "true".
	$correctAnswerIdx = (int) (
		$data->answer == ANSWER_VALUES_TRUE_FALSE[ (int) true ]
	);
	$answerProperties = array(
		$correctAnswerIdx => array(
			'answer'	=> $data->answer,
			'fraction'	=> ANSWER_FRACTIONS['correct']
		),
		(($correctAnswerIdx + 1) % count(ANSWER_VALUES_TRUE_FALSE) ) => array(
			'answer'	=> $data->distractors[0],
			'fraction'	=> ANSWER_FRACTIONS['wrong']
		)
	);

	// Append defaults to each answer.
	foreach( $answerProperties as $type => $properties) {
		$properties = array_merge($properties, array(
			'feedback' => DEFAULT_FEEDBACK
		));
		$answers[ $type ] = (object) $properties;
	}

	# 3. Insert question object and receive ID.
	$question->id = $DB->insert_record('question', $question);

	# 4. Update answer objects with question ID, insert into DB and
	#	 receive IDs.
	foreach( $answers as &$answer ) {
		$answer->question = $question->id;
		$answer->id = $DB->insert_record('question_answers', $answer);
	}

	# 5. Create and insert true/false question answers as stdClass
	#	 instances.
	$tfAnswer = (object) array(
		'question'		=> $question->id,
		'trueanswer'	=> $answers[ (int) true ]->id,
		'falseanswer'	=> $answers[ (int) false ]->id
	);
	$tfAnswer->id = $DB->insert_record('question_truefalse', $tfAnswer);

	# 6. Publish question to the Question Bank.
	$qbEntry = (object) array(
		'questioncategoryid'	=> $QUESTION_CATEGORY,
		'ownerid'				=> $USER->id
	);
	$qbEntry->id = $DB->insert_record('question_bank_entries', $qbEntry);

	# 7. Establish table connections via question version.
	$qVersion = (object) array(
		'questionbankentryid'	=> $qbEntry->id,
		'version'				=> DEFAULT_QUESTION_VERSION,
		'questionid'			=> $question->id
	);
	$qVersion->id = $DB->insert_record('question_versions', $qVersion);
}

function insertOpenQuestion($data) {
	global $DB, $QUESTION_CATEGORY, $USER;
	$timestamp = time();

	// Skip insertion the question is missing.
	if( !isset($data->question) ) return;

	# 1. Create question object as stdClass instance.
	$question = (object) array(
		'name'				=> strlen($data->question) > QUESTION_NAME_MAX
								? substr($data->question, 0, QUESTION_NAME_MAX)
								: $data->question,
		'questiontext'		=> $data->question,
		'qtype'				=>
			MOODLE_MAPPING::QUESTION_TYPES[ QUIZ_TYPES::OPEN ],

		// other defaults
		'generalfeedback'	=> DEFAULT_FEEDBACK,	// mandatory
		'timecreated'		=> $timestamp,
		'timemodified'		=> $timestamp,
		'createdby'			=> $USER->id,
		'modifiedby'		=> $USER->id
	);

	# 2. Create extra options as stdClass instances.
	$essayOptions = (object) array(
		'responseformat' => OPEN_QUESTION_RESPONSE_FORMAT
	);

	# 3. Insert question object and receive ID.
	$question->id = $DB->insert_record('question', $question);

	# 4. Update options object with question ID, insert into DB and
	#	 receive IDs.
	$essayOptions->questionid = $question->id;
	$essayOptions->id = $DB->insert_record(
		'qtype_essay_options',
		$essayOptions
	);

	# 5. Publish question to the Question Bank.
	$qbEntry = (object) array(
		'questioncategoryid'	=> $QUESTION_CATEGORY,
		'ownerid'				=> $USER->id
	);
	$qbEntry->id = $DB->insert_record('question_bank_entries', $qbEntry);

	# 6. Establish table connections via question version.
	$qVersion = (object) array(
		'questionbankentryid'	=> $qbEntry->id,
		'version'				=> DEFAULT_QUESTION_VERSION,
		'questionid'			=> $question->id
	);
	$qVersion->id = $DB->insert_record('question_versions', $qVersion);
}

function insertSingleChoiceQuestion($data) {
	global $DB, $QUESTION_CATEGORY, $USER;
	$timestamp = time();

	// Skip insertion if the data object is missing essential
	// properties.
	if( !( isset($data->sentence) && isset($data->question)
			&& isset($data->answer) && isset($data->distractors)
			&& count($data->distractors)
				== DISTRACTORS_COUNT[ QUIZ_TYPES::SINGLE_CHOICE ] )
	) {
		return;
	}

	# 1. Create question object as stdClass instance.
	$question = (object) array(
		'name'				=> strlen($data->sentence) > QUESTION_NAME_MAX
								? substr($data->sentence, 0, QUESTION_NAME_MAX)
								: $data->sentence,
		'questiontext'		=> $data->question,
		'qtype'				=>
			MOODLE_MAPPING::QUESTION_TYPES[ QUIZ_TYPES::SINGLE_CHOICE ],

		// other defaults
		'generalfeedback'	=> DEFAULT_FEEDBACK,	// mandatory
		'timecreated'		=> $timestamp,
		'timemodified'		=> $timestamp,
		'createdby'			=> $USER->id,
		'modifiedby'		=> $USER->id
	);

	# 2. Create answer objects as stdClass instances.
	$answers = array(
		array( 'answer'		=> $data->answer,
			   'fraction'	=> ANSWER_FRACTIONS['correct'] )
	);

	foreach( $data->distractors as $distractor ){
		array_push( $answers, array(
			'answer'	=> $distractor,
			'fraction'	=> ANSWER_FRACTIONS['wrong']
		) );
	}

	// Append defaults to each answer.
	foreach ($answers as &$answer) {
		$answer = (object) array_merge( $answer, array(
			'feedback' => DEFAULT_FEEDBACK
		));
	}

	# 3. Create multiple choice options as stdClass instances.
	$multiChoiceOptions = (object) array(
		'single'					=> SINGLE_CHOICE_FLAG,
		'answernumbering'			=> ANSWER_NUMBERING,
		'showstandardinstruction'	=> ANSWER_CHOICE_SHOW_INSTRUCTION,

		// mandatory
		'correctfeedback'			=> DEFAULT_FEEDBACK,
		'incorrectfeedback'			=> DEFAULT_FEEDBACK,
		'partiallycorrectfeedback'	=> DEFAULT_FEEDBACK
	);

	# 4. Insert question object and receive ID.
	$question->id = $DB->insert_record('question', $question);

	# 5. Update answer objects with question ID, insert into DB, and
	#	 receive IDs.
	foreach ($answers as &$answer) {
		$answer->question = $question->id;
		$answer->id = $DB->insert_record('question_answers', $answer);
	}

	# 6. Update options object with question ID, insert into DB, and
	#	 receive IDs.
	$multiChoiceOptions->questionid = $question->id;
	$multiChoiceOptions->id = $DB->insert_record(
		'qtype_multichoice_options',
		$multiChoiceOptions
	);

	# 7. Publish question to the Question Bank.
	$qbEntry = (object) array(
		'questioncategoryid'	=> $QUESTION_CATEGORY,
		'ownerid'				=> $USER->id
	);
	$qbEntry->id = $DB->insert_record('question_bank_entries', $qbEntry);

	# 8. Establish table connections via question version.
	$qVersion = (object) array(
		'questionbankentryid'	=> $qbEntry->id,
		'version'				=> DEFAULT_QUESTION_VERSION,
		'questionid'			=> $question->id
	);
	$qVersion->id = $DB->insert_record('question_versions', $qVersion);
}

function insertClozeQuestion($data) {
	global $DB, $QUESTION_CATEGORY, $USER;
	$timestamp = time();

	// Skip insertion if the data object is missing essential
	// properties or the question lacks the answer placeholder.
	if( !( isset($data->sentence) && isset($data->question)
			&& preg_match( CLOZE_ANSWER_PLACEHOLDER_RE, $data->question ) !== false
			&& isset($data->answer) && isset($data->distractors)
			&& count($data->distractors)
				>= DISTRACTORS_COUNT[ QUIZ_TYPES::CLOZE ] )
	) {
		return;
	}

	# 1. Create question object as stdClass instance.
	$question = (object) array(
		'name'				=> strlen($data->sentence) > QUESTION_NAME_MAX
								? substr($data->sentence, 0, QUESTION_NAME_MAX)
								: $data->sentence,
		'questiontext'		=> preg_replace(
			CLOZE_ANSWER_PLACEHOLDER_RE,
			'[[' . (CLOZE_CORRECT_ANSWER_IDX + 1) . ']]',
			$data->question
		),
		'qtype'				=>
			MOODLE_MAPPING::QUESTION_TYPES[ QUIZ_TYPES::CLOZE ],

		// other defaults
		'generalfeedback'	=> DEFAULT_FEEDBACK,	// mandatory
		'timecreated'		=> $timestamp,
		'timemodified'		=> $timestamp,
		'createdby'			=> $USER->id,
		'modifiedby'		=> $USER->id
	);

	# 2. Create answer objects as stdClass instances. The correct answer
	#	 is always the first element (index 0).
	$answers = array( array( 'answer' => $data->answer ) );

	foreach( $data->distractors as $distractor ) {
		array_push( $answers, array( 'answer'	=> $distractor ) );
	}

	// Append defaults to each answer.
	foreach ($answers as &$answer) {
		$answer = (object) array_merge($answer, array(
			'feedback' => DEFAULT_FEEDBACK 
		));
	}

	# 3. Create multiple choice options as stdClass instances.
	$clozeOptions = (object) array(
		// mandatory
		'correctfeedback'			=> DEFAULT_FEEDBACK,
		'incorrectfeedback'			=> DEFAULT_FEEDBACK,
		'partiallycorrectfeedback'	=> DEFAULT_FEEDBACK
	);

	# 4. Insert question object and receive ID.
	$question->id = $DB->insert_record('question', $question);

	# 5. Update answer objects with question ID, insert into DB, and
	#	 receive IDs.
	foreach ($answers as &$answer) {
		$answer->question = $question->id;
		$answer->id = $DB->insert_record('question_answers', $answer);
	}

	# 6. Update options object with question ID, insert into DB, and
	#	 receive IDs.
	$clozeOptions->questionid = $question->id;
	$clozeOptions->id = $DB->insert_record(
		'question_gapselect',
		$clozeOptions
	);

	# 7. Publish question to the Question Bank.
	$qbEntry = (object) array(
		'questioncategoryid'	=> $QUESTION_CATEGORY,
		'ownerid'				=> $USER->id
	);
	$qbEntry->id = $DB->insert_record('question_bank_entries', $qbEntry);

	# 8. Establish table connections via question version.
	$qVersion = (object) array(
		'questionbankentryid'	=> $qbEntry->id,
		'version'				=> DEFAULT_QUESTION_VERSION,
		'questionid'			=> $question->id
	);
	$qVersion->id = $DB->insert_record('question_versions', $qVersion);
}