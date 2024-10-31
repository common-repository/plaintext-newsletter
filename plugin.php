<?php

// TNP plaintext modes:
//    $email->options['text_message_mode'] == '' means "Autogenerate" plaintext
//    $email->options['text_message_mode'] == '1' means "Hand coded" plaintext
//    TNP always tests for empty($email->options['text_message_mode'])
// TNP editors are defined in class NewsletterEmails
//    EDITOR_COMPOSER = 2; const EDITOR_HTML = 1; 

class PlaintextNewsletterAddon extends NewsletterAddon {
		
	static $instance;
   
	function __construct($version) {
	    parent::__construct('plaintext', $version);	
	    self::$instance = $this;
	    $this->setup_options();
	    if (is_admin()) {
            add_action('plugins_loaded', array($this, 'hook_load_textdomain'));
        }
    }

    function setup_options() {
        parent::setup_options();
        if (!$this->options) {
            $this->options = array( 'newsletters' => 0, 'transactional' => 0 );
            // Note: the 'newsletters' option is no longer used
            $this->save_options( $this->options );
        }
     }

    function hook_load_textdomain() {
        load_plugin_textdomain( 'plaintext-newsletter', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
    }
    
    function init() {
        parent::init();
        if (is_admin()) {
            if (current_user_can('administrator')) {
                add_action('admin_menu', array($this, 'hook_admin_menu'), 100);
                add_filter('newsletter_menu_settings', array($this, 'hook_newsletter_menu_settings'));
                // For HTML newsletters, hook into newsletter edit page and message_text filter
                add_action('newsletter_emails_edit_other', array($this, 'hook_newsletter_emails_edit_other'), 10, 2);
                add_filter('newsletter_message_text', array($this, 'hook_newsletter_message_text'), 10, 4);
            }
        }
        // For transactional emails: Hook into mailer registration after the current MailerAddon
        if ( $this->options['transactional'] ) {
            add_action('newsletter_register_mailer', array($this, 'hook_register_mailer'), 20);
        }
    }

    // Configuration menu
    
    function hook_newsletter_menu_settings($entries) {
        // Note: url must start with 'newsletter'
        $entries[] = array('label' => '<i class="fas fa-pen-nib"></i> Plaintext', 'url' => '?page=newsletter_plaintext_index', 'description' => 'Auto-generate plain text version of emails');
        return $entries;
    }
    
    function hook_admin_menu() {
        add_submenu_page('newsletter_main_index', 'Plaintext', '<span class="tnp-side-menu">Plaintext</span>', 'manage_options', 'newsletter_plaintext_index', array($this, 'menu_page_index'));
    }
    
    function menu_page_index() {
        require dirname(__FILE__) . '/index.php';
    }

    // Automatic plaintext generation for newslettters

    // This handler runs on the 'newsletter_emails_edit Admin page, inside the "Advanced section"
    // It only handles HTML emails, because Composer emails are handled correctly by TNP
    function hook_newsletter_emails_edit_other($email, $controls) {

        if (NewsletterEmails::instance()->get_editor_type($email) != NewsletterEmails::EDITOR_COMPOSER) {

            // Insert the dropdown, which TNP only shows for Composer emails
            // Code copied from /newsletter/emails/edit.php
            ob_start();
            $controls->select('options_text_message_mode', ['' => __('Autogenerate', 'newsletter'), '1' => __('Hand edited', 'newsletter')]);
            echo '<p class="description"></p>';
            $dropdown = ob_get_clean();
            // Enqueue script in footer
            wp_enqueue_script( 'plaintext-newsletter', plugins_url('plaintext.js', __FILE__), array(), $this->version, true );
            $vars = array( 'dropdown' => $dropdown );
            wp_localize_script( 'plaintext-newsletter', 'nlpt', $vars );

            // Generate the plaintext version so that it can be displayed
            if (empty($email->options['text_message_mode']) ) {
                $text = TNP_Composer::convert_to_text($email->message);
                if ($text) {
                    $email->message_text = $text;
                    $email = Newsletter::instance()->save_email($email);
                }
            }
        }        
    }

    // This handler is invoked when the message is sent
    // It is only really necessary for test messages
    function hook_newsletter_message_text($body_text, $email, $user) {
        // For raw HTML newsletters, if the dropdown option is set to Automatic, generate the plaintext version
        // For Composer newsletters, the Newsletter plugin takes care of that
        if ( (NewsletterEmails::instance()->get_editor_type($email) != NewsletterEmails::EDITOR_COMPOSER) &&
              empty($email->options['text_message_mode']) ) {
            $text = TNP_Composer::convert_to_text($email->message);
            if ($text) {
                // This filter is called after replace() in TNP build_message(), so we have to run replace() again
                $body_text = Newsletter::instance()->replace($text, $user, $email);
           }
         }
        return $body_text;
    }

    // Automatic plaintext generation for transactional emails
        
    function hook_register_mailer() {
        
        $newsletter = Newsletter::instance();
        $mailer_class = '';        
        $currentmailer = $newsletter->mailer;
        
        if (!$currentmailer) {
            // Simulate what Newsletter::get_mailer() does if no add-on mailer is defined
            $mailer_class = 'NewsletterDefaultMailer';           
        } 
        elseif ($currentmailer instanceof NewsletterMailer) {
            $mailer_class = get_class($currentmailer);
        }
        
        if ($mailer_class) {
            // Define our mailer as a subclass of the current mailer class
            //eval("class PlaintextMailerBase extends $mailer_class {}");
            class_alias( $mailer_class, 'PlaintextMailerBase' );
            include __DIR__ . '/mailer.php';
            // Instantiate our mailer (constructor is inherited from current mailer)
            if ($mailer_class == 'NewsletterSmtpMailer') {
                // Free SMTP mailer add-on (Newsletter >= 7.0.9)
                $mailer = new PlaintextMailer( $currentmailer->options );
            }
            elseif ($mailer_class == 'NewsletterDefaultMailer') {
                $mailer = new PlaintextMailer();
            }
            elseif ( ($mailer_class == 'NewsletterMailgunMailer') || ($mailer_class == 'NewsletterMailjetMailer') || 
                     ($mailer_class == 'NewsletterElasticEmailMailer') || ($mailer_class == 'SuperfastMailgunMailer')) {
                 $mailer = new PlaintextMailer( $currentmailer->name, $currentmailer->options );
            }
            elseif ($mailer_class == 'NewsletterSendgridMailer') {
                $mailer = new PlaintextMailer( $currentmailer->options );
            }
            elseif ($mailer_class == 'NewsletterSendinblueMailer') {
                $mailer = new PlaintextMailer( $currentmailer->module );
            }
            elseif ( ($mailer_class == 'NewsletterAmazonMailer') || ($mailer_class == 'NewsletterSparkPostMailer') ) {
                $mailer = new PlaintextMailer( $currentmailer->addon );
            }
            else {
                $this->get_logger()->error("Unknown mailer class $mailer_class");
                $mailer = null;
            }
            // Replace current mailer with ours
            if ($mailer) {
                $newsletter->register_mailer($mailer);
            }
        }
    }
}