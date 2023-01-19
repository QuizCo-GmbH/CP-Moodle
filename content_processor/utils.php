<?php

function basePath() {
    $path = '';
    $config_file = 'config.php';

    try {
        // Assumption: This file resides in the CP plugin directory
        // within the blocks directory, which itself is located in 
        // the Moodle root directory. Therefore, traverse two levels
        // upwards to find the root.
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            // This approach is compatible with project structures
            // incorporating symlinks.
            // See: https://stackoverflow.com/a/11639401/5099099
            $pathRef = dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])));
        } else {
            // If the server variable cannot be accessed, then directly
            // traverse upwards two levels. This will work with physical
            // file structures but will fail with symlinks.
            $pathRef = './../..';
        }
        $path = $pathRef . '/';

        // Test, whether the path is valid.
        if( !file_exists($path . $config_file) ) {
            throw new Exception("Config not found in base path.");	
        }
    } catch (\Throwable $th) {
        throw new Error(
            'Error: Unable to recreate base path of Moodle instance.'
        );
    }

    return $path;
}