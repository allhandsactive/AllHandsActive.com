<?php
//TODO: It would be a good idea to make this more View-like (i.e. of MVC) by putting out data in a more templateable fashion.
/** CONFIGURATION **/
//Defines which fieldset which we will use to populate the list of skills.
//This will need to be updated if the fieldset index ever changes.
define('SKILL_FIELDSET', 1);

/** MISC. CONSTANTS **/
//These are just for the search type selection.
define('SEARCH_TYPE_HAVE', 0);
define('SEARCH_TYPE_WANT', 1);

//Output a search form with a selection of available skills.
function getSearchForm()
{
  $action = htmlspecialchars($SERVER['PHP_SELF']);
  $skillArr = getSkillFields();
  //Generate the select list of skills.
  $skillSelect = '<select size="10" name="skills[]" multiple="multiple"><option value="0">-- Select some skills --</option>';
  foreach($skillArr as $skillName => $skillLabel) {    
    $selected = '';
    
    //Maintain user's multi-select selections.
    if(sizeof($_REQUEST['skills']) && array_search($skillName, $_REQUEST['skills']) !== false) {
      $selected = ' selected="selected"';
    }
    
    $skillSelect .= '<option value="' . $skillName . '"' . $selected . '/> ' . $skillLabel . '</option>';
  }
  $skillSelect .= '</select>';
  
  //Also maintain the user's search type selection.
  //OK, this exercise is silly; but I want to use heredoc, dammit!
  $sth = SEARCH_TYPE_HAVE;
  $sthSel = ($_REQUEST['searchType'] == SEARCH_TYPE_HAVE ? 'selected="selected"' : '');
  $stw = SEARCH_TYPE_WANT;
  $stwSel = ($_REQUEST['searchType'] == SEARCH_TYPE_WANT ? 'selected="selected"' : '');
  
  $output =<<<EOT
<form name="memberSearchForm" action="$action" method="POST">
  <input name="ms" type="hidden" value="1" />
  <p>I'm looking for: 
  <select name="searchType">
    <option value="$sth" $sthSel>Someone who has these skills</option>
    <option value="$stw" $stwSel>Someone who is looking for these skills</option>
  </select>
  </p>
  <p>$skillSelect</p>
  <input name="searchSubmit" type="submit" value="Search" />
</form>
EOT;

return $output;
}

function getSearchResults()
{
  $allSkills = getSkillFields();
  //Check if any of the current list of skills are among the selected elements.
  foreach($allSkills as $name => $label)
  {
    if(array_search($name, $_REQUEST['skills']) !== false)
    {
      //This array will hold a list of the selected skills, which we will use to query the membership.
      $selSkills[] = $name;
    }
  }
  
  //Now we should have a list of all the desired skills.  Based on the search type, query the userbase to see who is a hit.
  foreach($selSkills as $name)
  {
    /*
    If we are using a "want" search type, we must modify the desired field name to have an underscore at the end.
    This is the (sub-par!) way we denote a "want" skill name vs. a "have", other than by looking at its fieldset value -- the more ideal approach.
    Sadly, get_cimyFieldValue doesn't take fieldset as an argument.
    */
    $result = get_cimyFieldValue(false, $name . ($_REQUEST['searchType'] == SEARCH_TYPE_WANT ? '_' : ''), 'YES');
    if(sizeof($result))
    {
      foreach($result as $userData)
      {
        //Skip over private profiles.
        if(get_cimyFieldValue($userData['user_id'], 'PRIVATE_PROFILE', 'YES')) {
          continue;
        }
        /*
        $userArr will be a 2d array with user ID in the first dimension.
        The second dimension is an associative array with skillname => true if they have selected the skill.
        This will make it possible to sync with the table header below.
        */
        $userArr[$userData['user_id']][$name] = true;
      }
    }    
  }
  
  //Now we will have an array of users, and an array of skills associated with them.  Let's print out a pretty table with that data!
  if(!sizeof($userArr)) {
    $output = '<h3>No matching makers were found!</h3>';
  } else {
    //Make a nice table header, and add the selected skills as headings.
    $output = '<div style="overflow: auto;"><table><tr><th>Name</th>';
    foreach($selSkills as $name) {
      $output .= '<th>' . $allSkills[$name] . '</th>';
    }
    $output .= '</tr>';
    //Start putting out the user rows.
    foreach($userArr as $userId => $userSkills) {
      $output .= '<tr>';
      //Let's get some data on this user.
      $wpUserData = get_userdata($userId);
      $profileLink = '<a href="' . get_author_posts_url($userId) . '?profileview" target="_new" />';
      
      $output .= '<td>' . $profileLink . $wpUserData->user_nicename . '</a></td>';
      foreach($selSkills as $name) {
        $output .= '<td><input type="checkbox" ' . ($userSkills[$name] ? 'checked="checked"' : '') . ' disabled="disabled" /></td>';
      }
      $output .= '</tr>';
    }
    $output .= '</table></div>';
  }
  
  
  return $output;
}

/*
Return an associative array full containing a list of skills.  Key is input name, value is its label.
Only considers the list of "have" skills, so they should be exactly the same as the list "want" skills.
This returns the skills in the order they are given in the extra fields plug-in's setup.
*/
function getSkillFields()
{
  //Get the list of available skills.
  $fieldArr = get_cimyFields();
  
  //Extract the field labels for use on our search checkboxes.
  foreach($fieldArr as $field) {
    //To avoid duplication, we'll just get all listed skills in the "have skill" fieldset.
    if($field['FIELDSET'] != SKILL_FIELDSET) {
      continue;
    }
    
    $fieldName = $field['NAME'];
    $skillArr[$fieldName] = $field['LABEL'];
  }
  
  return $skillArr;
}
?>
