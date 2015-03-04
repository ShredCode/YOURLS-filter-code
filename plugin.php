<?php
/*
Plugin Name: Status Code by Keyword 
Plugin URI: https://www.github.com/ShredCode/YOURLS-filter-code 
Description: Use 3XX redirects to prevent browser caching keyword redirects. 
Version: 1.0
Author: ShredCode <http://www.shredcode.com>
Author URI: https://www.github.com/ShredCode 
*/

if (!defined('SHRED_CODE_PLUGIN_NAME')){
    DEFINE("SHRED_CODE_PLUGIN_NAME", basename(__DIR__));
    DEFINE("SHRED_CODE_TABLE_NAME", 'shred_code_filter_code');
}

yourls_add_action('activated_' . SHRED_CODE_PLUGIN_NAME . '/plugin.php', 'shred_code_create_code_table');

//create table if not created
function shred_code_create_code_table($args) {
    global $ydb;
    $sql = 'CREATE TABLE IF NOT EXISTS ' . SHRED_CODE_TABLE_NAME . ' (keyword VARCHAR(200) NOT NULL, PRIMARY KEY(keyword),code VARCHAR(3));';
    $ydb->query($sql);
}

yourls_add_action('delete_link', 'shred_code_delete_status_code');

//Remove entry for any deleted keywords
function shred_code_delete_status_code($args) {
    global $ydb;
    $keyword = $args[0];
    $keyword = yourls_escape(yourls_sanitize_string($keyword));
    $delete  = $ydb->query("DELETE FROM ". SHRED_CODE_TABLE_NAME ." WHERE keyword = '$keyword';");
}

yourls_add_filter('redirect_code', 'shred_code_find_status_code');

//lookup status for the keyword - default if not found in table
function shred_code_find_status_code($code, $location) {
    global $ydb;
    global $keyword;
    $keyword = yourls_escape( yourls_sanitize_string( $keyword ) );
    $status_code = $ydb->get_var("SELECT sc.code FROM ". SHRED_CODE_TABLE_NAME ." AS sc INNER JOIN yourls_url AS y ON y.keyword = sc.keyword WHERE y.keyword='$keyword';");
    
    if (!$status_code) {
        $status_code = $code;
    }
    return $status_code;
}


// Register plugin admin page
yourls_add_action('plugins_loaded', 'shred_code_status_add_page');

function shred_code_status_add_page() {
    yourls_register_plugin_page(SHRED_CODE_PLUGIN_NAME, 'Status Code Page', 'shred_code_status_code_do_page');
}


// Display admin page
function shred_code_status_code_do_page() {
    // Check if a form was submitted
    if (isset($_POST['input_code']) && isset($_POST['input_keyword'])) {
        // Check nonce
        yourls_verify_nonce( SHRED_CODE_PLUGIN_NAME );
        
        // Process form
        shred_code_page_update_code();
    }
    
    // Create nonce
    $nonce = yourls_create_nonce( SHRED_CODE_PLUGIN_NAME );
    
    echo <<<HTML
		<h2>Status Code Plugin Administration Page</h2>
		<p>This plugin stores selected status codes per keyword.
		Defaults to status_code 301 if not set
                </p>
HTML;
    
    global $ydb;
    $codes_results = $ydb->get_results("SELECT keyword,code FROM ". SHRED_CODE_TABLE_NAME ." ORDER BY keyword;");
    if ($codes_results) {
        echo "<table><th>Keyword</th><th>Status Code</th>";
        foreach ($codes_results as $code_result) {
            $keyword = yourls_sanitize_string($code_result->keyword);
            echo "<tr><td>$keyword</td><td>$code_result->code</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<h3>No filters found</h3>";
    }
    echo '<form method="post">';
    $keywords_results = $ydb->get_results("SELECT keyword FROM yourls_url ORDER BY keyword;");
    
    if ($keywords_results) {
        $dd = '<select name="input_keyword" id="input_keyword">' . "\n";
        foreach ($keywords_results as $keyword_result) {
            $dd .= '<option value=' . $keyword_result->keyword . '>' . $keyword_result->keyword . '</option>' . "\n";
        }
        $dd .= '</select>' . "\n";
        echo $dd;
    } else {
        echo "<h3>No keywords found</h3>";
    }
    $code_options = array(
        '301',
        '302',
        '303',
        '307',
        '308',
        '309'
    );
    $dropdown     = '<select name="input_code" id="input_code">' . "\n";
    foreach ($code_options as $option) {
        $dropdown .= '<option value=' . $option . '>' . $option . '</option>' . "\n";
    }
    $dropdown .= '</select>' . "\n";
    echo $dropdown;
    echo '<input type="hidden" name="nonce" value=' . $nonce . ' />';
    echo '<p><input type="submit" value="Update Status Code" /></p>';
    echo "</form>";
    
}

// Update option in database
function shred_code_page_update_code() {
    $input_keyword = $_POST['input_keyword'];
    $input_code    = $_POST['input_code'];
    
    if ($input_keyword && $input_code) {
        // validate and sanitize input
        $keyword = yourls_sanitize_string($input_keyword);
        $code    = yourls_sanitize_string($input_code);
        
        global $ydb;
        // Update value in database
        $ydb->query("INSERT into ". SHRED_CODE_TABLE_NAME ." (keyword,code) VALUES('$keyword','$code') ON DUPLICATE KEY UPDATE code='$code';");
    }
}
