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
 * Version details
 *
 * @package    block_content_processor
 * @copyright  2023, QuizCo GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2023011901;                // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2022041900.00;             // Moodle 4.0.
$plugin->supported = [400, 400];
$plugin->component = 'block_content_processor'; // Full name of the plugin
$plugin->maturity = MATURITY_BETA;
$plugin->release = '0.1.0';