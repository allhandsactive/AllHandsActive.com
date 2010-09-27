<table class="form-table">
    <tr valign="top">
        <td>
            <label for="theme_my_login_mail_from_name"><?php _e('From Name', 'theme-my-login'); ?></label><br />
            <input name="theme_my_login[email][mail_from_name]" type="text" id="theme_my_login_mail_from_name" value="<?php echo $theme_my_login->options['email']['mail_from_name']; ?>" class="regular-text" />
			<p class="description"><?php _e('Enter the name you wish for e-mails to be sent from. If left blank, the default will be used.', 'theme-my-login'); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <td>
            <label for="theme_my_login_mail_from"><?php _e('From E-mail', 'theme-my-login'); ?></label><br />
            <input name="theme_my_login[email][mail_from]" type="text" id="theme_my_login_mail_from" value="<?php echo $theme_my_login->options['email']['mail_from']; ?>" class="regular-text" />
			<p class="description"><?php _e('Enter the e-mail address you wish for e-mails to be sent from. If left blank, the default will be used.', 'theme-my-login'); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <td>
            <label for="theme_my_login_mail_content_type"><?php _e('E-mail Format', 'theme-my-login'); ?></label><br />
            <select name="theme_my_login[email][mail_content_type]" id="theme_my_login_mail_content_type">
            <option value="plain"<?php if ('plain' == $theme_my_login->options['email']['mail_content_type']) echo ' selected="selected"'; ?>>Plain Text</option>
            <option value="html"<?php if ('html' == $theme_my_login->options['email']['mail_content_type']) echo ' selected="selected"'; ?>>HTML</option>
            </select>
			<p class="description"><?php _e('If you are going to use HTML markup in your e-mail messages, select "HTML" for this setting. Otherwise, select "Plain Text".', 'theme-my-login'); ?></p>
        </td>
    </tr>
</table>