<?php
defined('ABSPATH') || exit;

/* @var $this PlaintextNewsletterAddon */

@include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

if (!$controls->is_action()) {
    $controls->data = $this->options;
} else {
    
    if ($controls->is_action('save')) {
        $this->save_options($controls->data);
        $controls->messages = 'Saved.';
    }
    
}

$wp_mail_filename = (new ReflectionFunction('wp_mail'))->getFileName();
if (substr($wp_mail_filename, -strlen('pluggable.php')) != 'pluggable.php') {
    $base_len = strlen(WP_PLUGIN_DIR);
    $pos = strpos($wp_mail_filename, DIRECTORY_SEPARATOR, $base_len+1);
    $plugin_name = '';
    if ($pos !== false) {
        $plugin_dir = substr($wp_mail_filename, $base_len, $pos-$base_len);
        $plugins = get_plugins($plugin_dir);
        if (count($plugins) == 1) {
            foreach ($plugins as $plugin) {
                $plugin_name = $plugin['Name'];
            }
        }
    }
    if ($plugin_name) {
        $controls->warnings[] = $plugin_name . ' has replaced the wp_mail() function. It may not be possible to send plain text versions of your emails.';
    } else {
        $controls->warnings[] = 'Another plugin has replaced the wp_mail() function. It may not be possible to send plain text versions of your emails.';
    }
}
?>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_DIR . '/tnp-header.php'; ?>
	<div id="tnp-heading">
        <h2>Plain text generator</h2>
        <?php $controls->show(); ?>    
    </div>

	<div id="tnp-body">

    <form method="post" action="">
        <?php $controls->init(); ?>

			<div id="tabs">
                <ul>
                    <li><a href="#tabs-settings">Settings</a></li>
                    <li><a href="#tabs-help">Help</a></li>
                </ul>

                <div id="tabs-settings">
                
                    <table class="form-table">
                        <tr>
                            <th>Auto-generate plain text for transactional emails</th>
                            <td>
                            	<?php $controls->yesno('transactional'); ?>
                            	<p class="description">Examples of transactional emails: the Welcome message, the Activation email, the Goodbye email.</p>
                            </td>
                        </tr>
                    </table>
                    
                </div>
                <div id="tabs-help">

                	<p>This add-on allows you to automatically generate plain text versions of all emails sent by the Newsletter plugin.</p>
                
                    <p>It is generally considered good practice to include a “plain text” version of every email in addition to the HTML version. 
					For some background information on this, 
					see <a href="https://litmus.com/blog/best-practices-for-plain-text-emails-a-look-at-why-theyre-important" target="_blank">this article</a>.</p>
					
					<p> If you use Thunderbird as your email client, you can see the plain text version using menu item: View / Message Body As / Plain Text</p>

                    
				</div>
			</div>
        <p>
            <?php $controls->button_save(); ?>
        </p>

    </form>
    </div>

    <?php include NEWSLETTER_DIR . '/tnp-footer.php'; ?>
    
</div>
