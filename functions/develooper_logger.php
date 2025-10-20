<?php 
/**
 * Logging functions so that all non specifics can be changed simultaneaously
 *
 * Common logging functions for the LOOPIS plugin.
 * Originally from LOOPIS_Config plugin.
 * Original file name: loopis_logger.php
 * Credits: Johan Linger, Hubert Hilton and Johan Hagvil.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Dev-tools
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

function loopis_elog_function_start($function_handle){
    error_log("Running: function {$function_handle} ...");
}

function loopis_elog_function_end_success($function_handle){
    error_log("End: function {$function_handle} completed successfully!");
    error_log("");
}

function loopis_elog_function_end_failure($function_handle){
    error_log("End: function {$function_handle} fatal failure!");
    error_log("");
}

function loopis_elog_first_level($message){
    error_log("     {$message}");
}
function loopis_elog_second_level($message){
    error_log("         {$message}");
}