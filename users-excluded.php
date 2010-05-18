<?php
/*
Plugin Name: Users Excluded
Description: Excluded users from specific pages.
*/

//Includes
include "userClass.php";

//Objects
$userOnly=new userClass();




// Actions
add_action('admin_init', 'uo_class');
add_action('admin_init', 'uo_check');
add_action('admin_menu', 'uo_add_custom_box');
add_action('save_post', 'uo_save');
//add_action('pre_post_update', 'uo_save');
add_action('admin_head', 'uo_head',100);
add_action('admin_menu', 'uo_add_pages');


//Functions

function uo_add_pages(){
    if (function_exists('add_menu_page')){
        add_menu_page('User Permissions', 'User Permissions', 10, __FILE__, 'uoPage');

    }
}

function uoPage() {
    global $userOnly;
    echo "<div class=\"wrap\"><h2>User Permissions</h2>";

    if (isset($_POST['updatePerm'])){

        $addPages=$_POST['addPages'];
        $addPosts=$_POST['addPosts'];
        $editComments=$_POST['editComments'];
        $theUser=$_POST['theUser'];
        if (is_array($theUser)){
            foreach ($theUser as $userID){
                $v=1;

                delete_usermeta($userID,"addPages");
                delete_usermeta($userID,"addPosts");
                delete_usermeta($userID,"editComments");
                if (isset($addPages[$userID])){
                    update_usermeta($userID,"addPages",$v);
                }
                if (isset($addPosts[$userID])){
                    update_usermeta($userID,"addPosts",$v);
                }
                if (isset($editComments[$userID])){
                    update_usermeta($userID,"editComments",$v);
                }
            }
            echo "<div id=\"message\" class=\"updated fade\">Updated User Permissions.</div>";
        }
    }

    echo "<form action=\"\" method=\"post\">";
    echo "<table class=\"fixed widefat\">
<thead><tr><th>User:</th><th>Add Pages:</th><th>Add Posts:</th><th>Comments:</th><th>Links:</th></tr></thead><tbody>";
$x=0;
    foreach ($userOnly->users as $k=>$user){
        $userinfo=get_userdata($user['ID']);

        $user_level=0;
        if (array_key_exists('administrator',$userinfo->wp_capabilities)) {
            $user_level=10;
        }
        if ($user_level == 10){
            continue;
        }
        //pr($userinfo);
        $pageCheck="";
        $postCheck="";
        $commentCheck="";
        $linkCheck="";
        if ($userinfo->addPages == 1){
            $pageCheck="checked";
        }
        if ($userinfo->addPosts == 1){
            $postCheck="checked";
        }
        if ($userinfo->editComments == 1){
            $commentCheck="checked";
        }
        if ($userinfo->links == 1){
            $linkCheck="checked";
        }
        echo "<tr><td>$userinfo->display_name<input type=\"hidden\" name=\"theUser[]\" value=\"$userinfo->ID\" /></td>
<td><input type=\"checkbox\" name=\"addPages[$user[ID]]\" value=\"1\" $pageCheck /></td>
<td><input type=\"checkbox\" name=\"addPosts[$user[ID]]\" value=\"1\" $postCheck /></td>
<td><input type=\"checkbox\" name=\"editComments[$user[ID]]\" value=\"1\" $commentCheck /></td>
<td><input type=\"checkbox\" name=\"links[$user[ID]]\" value=\"1\" $linkCheck /></td>
</tr>";
        $x++;
    }
    echo "</tbody></table>";
    if ($x == 0){
        echo "There are no users other than administrators.";
   }else{
    echo "<br/><input type=\"submit\" value=\"Update\" name=\"updatePerm\" class=\"button-primary\" />";

   }
   echo "</form>";
    
    
    echo "</div>";
}

function uo_add_custom_box(){
    global $current_user;

    if( function_exists( 'add_meta_box' )) {
        if (array_key_exists('administrator',$current_user->wp_capabilities)) {
            add_meta_box( 'uo_sectionid', __( 'Set Permissions', 'uo_textdomain' ),
                'uo_inner_custom_box', 'post', 'advanced' );
            add_meta_box( 'uo_sectionid', __( 'Set Permissions', 'uo_textdomain' ),
                'uo_inner_custom_box', 'page', 'advanced' );
        }
    }

}

function uo_inner_custom_box(){
    global $userOnly;
    echo "Block access to this page for the following users:<br/><br/>";

    echo "<table class=\"widefat\" style=\"width:200px;\">";
    echo "<thead><tr><th>Name:</th><th>Block:</th></tr></thead><tbody>";
    $thePost=$_GET['post'];
    foreach ($userOnly->users as $k=>$user){

        $userinfo=get_userdata($user['ID']);
        $user_level=0;
        if (array_key_exists('administrator',$userinfo->wp_capabilities)) {
            $user_level=10;
        }
        $checked="";
        //$perms=$userOnly->permissions;
        $perms=get_usermeta($user['ID'],"userPermissions");
        if (is_array($perms)){
            if (array_key_exists($thePost,$perms)){
                $checked="checked";
            }

        }
        $adminified="";
        if ($user_level == 10){
            $adminified="disabled";
            $checked="";
        }

        echo "<tr><td>$userinfo->display_name<input type=\"hidden\" name=\"usersName[]\" value=\"$user[ID]\" /></td><td><input type=\"checkbox\" name=\"userPerm[]\" value=\"$user[ID]\" $adminified $checked/></td></tr>";
    }
    echo "</tbody></table>";




}



function uo_check() {
    global $userOnly;
    if (isset($_GET['allowed'])){
        $badid=$_GET['badid'];
        $badPost=get_post($badid);

        add_action('admin_notices' , create_function( '', "echo '<div id=\"message\" class=\"updated fade\">You do not have permission to edit $badPost->post_title.</div>';" ) );
        return false;
    }

    // Is the user allowed to access this page/post?
    if (!isset($_GET['post'])){
        return false;
    }
    $thePost=$_GET['post'];

    $userOnly->checkPermissions($thePost);
}

function uo_class(){
    global $current_user;
    global $userOnly;
    $userOnly->currentID=$current_user->ID;
    if (array_key_exists('administrator',$current_user->wp_capabilities)) {
        $current_user->wp_user_level=10;
    }
    $userOnly->user_level=$current_user->wp_user_level;

    $userOnly->getPermissions();
}

function uo_save($post_id){
    $userName=$_POST['usersName'];
    if (!$userName){
        return false;
    }
    $userPerm=$_POST['userPerm'];
    if (!is_array($userPerm)){
        $userPerm[1]="D";
    }
    foreach ($userName as $k=>$v){
        if ($v == 1) {
            continue;
        }
        $usermeta=unserialize(get_usermeta($v,"userPermissions")); // Array of pages user can access.
        if (!is_array($usermeta)) {
          //there is no permission data for this user yet.
          
          //init usermeta with empty array
          $usermeta = array();
        }
              
        if ((in_array($v,$userPerm))&&(is_array($usermeta))){

            if (!array_key_exists($post_id,$usermeta)){
                $usermeta[$post_id]=1;

                $usermeta=serialize($usermeta);
                update_usermeta($v,"userPermissions",$usermeta);
                unset($usermeta);
            }
        }else{
            unset($usermeta[$post_id]);

            $usermeta=serialize($usermeta);

            update_usermeta($v,"userPermissions",$usermeta);
            unset($usermeta);
        }
    }

}

function uo_head(){
    global $userOnly;

    if ($userOnly->user_level == 10){
        return false;
    }
    
    
    $allowPageAccessByDefault = true;
    
    $allowAccessValue = 1;
    
    echo "<script>

jQuery(function(){\n";

    if (is_array($userOnly->permissions)){
      if($allowPageAccessByDefault==false)
      {
        echo "jQuery('.iedit').fadeOut();\n";
        foreach ($userOnly->permissions as $k=>$v){
            echo "jQuery('#page-$k').fadeIn();\n";
            echo "jQuery('#post-$k').fadeIn();\n";
        }
      }
      else
      {
        foreach ($userOnly->permissions as $k=>$v){
            //optionally, color background for this entry red
            //echo "jQuery('#page-$k').attr(\"style\", \"background:#F6968C !important;\");\n";
            
            //remove page link from edit list
            echo "jQuery('#page-$k .post-title a').each(function(){";
            echo "    var \$t = jQuery(this);";
            echo "    \$t.after(\$t.text());";
            echo "    \$t.remove();";
            echo "});";
            
            //remove post link from edit list
            echo "jQuery('#post-$k .post-title a').each(function(){";
            echo "    var \$t = jQuery(this);";
            echo "    \$t.after(\$t.text());";
            echo "    \$t.remove();";
            echo "});";

            //remove inline quick edit functions.
            echo "jQuery('#page-$k .row-actions').fadeOut();\n";
            echo "jQuery('#post-$k .row-actions').fadeOut();\n";
        }
      }
    }
    if ($userOnly->addPages != $allowAccessValue){
        echo "jQuery(\"a[href='page-new.php']\").parent().hide().empty();\n";
    }
    if ($userOnly->addPosts != $allowAccessValue){
        echo "jQuery(\"a[href='post-new.php']\").parent().hide().empty();\n";
    }
    if ($userOnly->editComments != $allowAccessValue){
        echo "jQuery(\"#menu-comments\").hide().empty();\n";
    }

    if ($userOnly->links != $allowAccessValue){
        echo "jQuery(\"#menu-links\").hide().empty();\n";
    }

    echo "});\n
</script>";

}
?>