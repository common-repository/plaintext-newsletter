<?php
class PlaintextMailer extends PlaintextMailerBase { 
    function get_description() {
        return 'Plaintext Generator for ' . parent::get_description();
    }
    
    function send($message) {
        // In NewsletterMailer base class, this is called from send_chunk(), which would cause us to repeat the plaintext generation.
        // However, all Mailers that have batch_size > 1 override send_chunk(), so we dont need to worry about duplicate plaintext generation. 
        // Also, this Mailer is used only for transactional messages.
        if ( !isset($message->headers['X-Newsletter-Email-Id']) && PlaintextNewsletterAddon::$instance->options['transactional'] ) {
            //$logger = PlaintextNewsletterAddon::$instance->get_logger();
            //$logger->debug('text="' . $message->body_text . '"');                
            $text = TNP_Composer::convert_to_text($message->body);
            if ($text) {
                $message->body_text = $text;
            }
            //$logger->debug('new text="' . $message->body_text . '"');
        }
        return parent::send($message);
    }
    
    // send_chunk() is never called for transactional messages
}