<?php

/*
 Plugin Name: Plaintext for the Newsletter plugin
 Plugin URI: http://howfrankdidit.com/plain-text-generator-for-the-newsletter-plugin
 Description: Plain text generator for The Newsletter Plugin
 Version: 3.1
 Author: franciscus
 Author URI: http://howfrankdidit.com
 License: GPLv2 or later
 License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

 Copyright 2021  Frank Meijer  (email : franciscus@howfrankdidit.com)

 Plaintext for the Newsletter plugin is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 2 of the License, or
 any later version.

 Plaintext for the Newsletter plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Plaintext for the Newsletter plugin. 
 If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 
*/

add_action('newsletter_loaded', function ($version) {
    if ($version < '7.8.0') {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>Newsletter plugin upgrade required for Plaintext Addon.</p></div>';
        });
    } else {
        include __DIR__ . '/plugin.php';
        new PlaintextNewsletterAddon('3.1');
    }
});