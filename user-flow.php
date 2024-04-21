<?php

/** 
 * Plugin Name: My User Flow 
 * Description: A plugin that replaces the WordPress login flow with a custom page. 
 * Version: 1.0.0 
 * Author: bitpatrick 
 * License: GPL-2.0+ 
 * Text Domain: my-user-flow
 */

// Create the custom pages at plugin activation 
register_activation_hook(__FILE__, 'plugin_activated');

/** 
 * Plugin activation hook. 
 * 
 * Creates all WordPress pages needed by the plugin. 
 */
function plugin_activated()
{
    // Information needed for creating the plugin's pages 
    $page_definitions = array(
        'member-login' => array(
            'title' => __('Sign In', 'personalize-login'),
            'content' => '[custom-login-form]'
        ),
        'member-account' => array(
            'title' => __('Your Account', 'personalize-login'),
            'content' => '[account-info]'
        ),
        'member-register' => array(
            'title' => __('Register', 'personalize-login'),
            'content' => '[custom-register-form]'
        ),
        'member-password-lost' => array(
            'title' => __('Forgot Your Password?', 'personalize-login'),
            'content' => '[custom-password-lost-form]'
        ),
        'member-password-reset' => array(
            'title' => __('Pick a New Password', 'personalize-login'),
            'content' => '[custom-password-reset-form]'
        )
    );
    foreach ($page_definitions as $slug => $page) {
        // Check that the page doesn't exist already 
        $query = new WP_Query('pagename=' . $slug);
        if (!$query->have_posts()) {
            // Add the page using the data from the array above 
            wp_insert_post(
                array(
                    'post_content'   => $page['content'],
                    'post_name'      => $slug,
                    'post_title'     => $page['title'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'ping_status'    => 'closed',
                    'comment_status' => 'closed',
                )
            );
        }
    }
}

// Initialize Login Manager
require_once 'my-dispatcher.php';
$my_dispatcher = new Dispatcher();

// Initialize Template Engine
require_once 'my-template-engine.php';
$my_template_engine = new Template_Engine();

// Initialize Administration Panel
require_once 'my-administration-panel.php';
$my_administration_panel = new Administration_Panel();

// function mailtrap($phpmailer) {
//     $phpmailer->isSMTP();
//     $phpmailer->Host = 'sandbox.smtp.mailtrap.io';
//     $phpmailer->SMTPAuth = true;
//     $phpmailer->Port = 2525;
//     $phpmailer->Username = '7e6787bcb10937';
//     $phpmailer->Password = '07e3b9a8d5037e';
//   }

//   // setting provider email
// add_action('phpmailer_init', 'mailtrap');
