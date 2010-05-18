<?php

class userClass {
    var $currentID;
    var $user_level;
    var $permissions;
    var $users=array();
    var $addPages;
    var $addPosts;
    var $editComments;
    var $links;

    function userClass($currentID=0){
        global $wpdb;
        $q="SELECT ID,user_nicename FROM $wpdb->users";
        $this->users=$wpdb->get_results($q,ARRAY_A);

    }

    function getPermissions(){
        $perms=get_usermeta($this->currentID,"userPermissions");

        if (!$perms){
$this->permissions[0]='';
            return false;
        }
        if (is_array($perms)){
            $this->permissions=$perms;
        }else{
            $this->permissions=unserialize($perms);

        }
        $this->addPages=get_usermeta($this->currentID,"addPages");
        $this->addPosts=get_usermeta($this->currentID,"addPosts");
        $this->editComments=get_usermeta($this->currentID,"editComments");
        $this->links=get_usermeta($this->currentID,"links");
    }

    function checkPermissions($postID){

        if ($this->user_level == 10){
            return false;
        }
        
        $allowPageAccessByDefault = true;

        $thePost=get_post($postID);
        $postType=$thePost->post_type;

        if (($allowPageAccessByDefault==false && !array_key_exists($postID,$this->permissions)) || ($allowPageAccessByDefault==true && array_key_exists($postID,$this->permissions))){
            if ($postType == 'page'){

                $url="edit-pages.php";
            }else{
                $url="edit.php";
            }
            header("Location: $url?allowed=false&badid=$postID");
        }
      
    }

}
?>
