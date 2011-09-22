<?php
//Make sure the person viewing this page is logged in.
$userData = wp_get_current_user();
if(!$userData->ID):
?>
<h2><?php _e('You must be logged in to view user profiles!', 'aha2010'); ?></h2>
<?php
else:
//For the getSKillFields function.
include('include/membersearch.inc.php');
$authorData = get_userdata(intval($author));
//Get all extra fields for this user.
$showEmail = get_cimyFieldValue($author, 'EMAIL_PROFILE', 'YES');
$skillArr = getSkillFields();

echo get_avatar($authorData->user_email);
?>
<h2>
<?php
  if($authorData->first_name || $authorData->last_name) {
     $realName = ' (' . trim($authorData->first_name . ' ' . $authorData->last_name) . ')';
  }
  printf(__('User profile for %s', 'aha2010'), $authorData->display_name . $realName);
?>
</h2>
<?php if($showEmail && $authorData->user_email) { printf(__('<strong>Email:</strong> %s', 'aha2010'), '<a href="mailto:' . $authorData->user_email . '">' . $authorData->user_email . '</a><br />'); } ?>
<?php if($authorData->user_url) { printf(__('<strong>Website:</strong> %s', 'aha2010'), '<a href="' . $authorData->user_url . '">' . $authorData->user_url . '</a><br />'); } ?>
<?php if($authorData->aim) { printf(__('<strong>AIM:</strong> %s', 'aha2010'), $authorData->aim . '<br />'); } ?>
<?php if($authorData->yim) { printf(__('<strong>YIM:</strong> %s', 'aha2010'), $authorData->yim . '<br />'); } ?>
<?php if($authorData->jabber) { printf(__('<strong>XMPP:</strong> %s', 'aha2010'), $authorData->jabber . '<br />'); } ?>
<?php if($authorData->description) { echo '<br />' . $authorData->description; } ?>
<?php
  $hasSkills = '';
  $wantSkills = '';
  //Here's where we will create the HTML output for the skills data on this member.
  if(sizeof($skillArr)) {
    foreach($skillArr as $field => $label) {
      //Get the "have" skills.
      if(get_cimyFieldValue($author, $field, 'YES')) {
        //Join skills together with CommaSpace(TM).
        if($hasSkills) {
          $hasSkills .= ', ';
        }
        $hasSkills .= $label;
      }
      //Get the "want" skills.
      if(get_cimyFieldValue($author, $field . '_', 'YES')) {
        if($wantSkills) {
          $wantSkills .= ', ';
        }
        $wantSkills .= $label;
      }
    }
  }
  
  if($hasSkills || $wantSkills):
?>
<hr />
<h3>Skills</h3>
<?php
    if($hasSkills) { echo "<p>Skilled in: $hasSkills</p>"; }
    if($wantSkills) { echo "<p>Wants skills in: $wantSkills</p>"; }
  endif;
?>
<hr />
<p><a href="<?php echo get_author_posts_url($author); ?>">See other comments and posts by this user.</a></p>
<?php endif; /* End logged-in user check. */ ?>
