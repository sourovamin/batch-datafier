<?php

/*
 Plugin Name:User Batch Data Modifier 
 Description:Add/Update/Delete/Drop batch user data for all or specific users with meta key.
 Version: 1.0
 Author: Sourov Amin
 License: GPLv2+
 Text Domain:batch-datafier
*/

/*
 * Add menu for the plugin under Users menu.
 */
add_action('admin_menu', 'bdm_add_menu');

function bdm_add_menu() {
  add_submenu_page( "users.php", "Batch Data Modifier", __("Batch Modifier", "batch-datafier"), "administrator", "bdm-main-menu", "bdm_main_menu"); 
}

/*
 * Dashboard for the plugin options.
 */
function bdm_main_menu(){
    if ( !is_admin() ){ return; }
    
    if (isset($_REQUEST["action"])){
        if(current_user_can("edit_users")){
            $nonce = $_REQUEST['_wpnonce'];
            
            if ($_REQUEST["action"] == "add"){
                $metaKey = !empty($_POST["bdm_add_meta_key"]) ? sanitize_text_field($_POST["bdm_add_meta_key"]) : "";
                $fieldData = !empty($_POST["bdm_add_field_data"]) ? sanitize_text_field($_POST["bdm_add_field_data"]) : "";
                $roles = !empty($_POST["bdm_add_role"]) ? array_map("sanitize_text_field", $_POST["bdm_add_role"]) : [];
                if(empty($metaKey) || empty($roles) || !wp_verify_nonce($nonce, "bdm_add_nonce")){
                    echo "<div class='error notice'><p>".__("Operation Failed!", "batch-datafier")."</p></div>";
                }else{
                    bdm_insert_batch_data($metaKey, $fieldData, $roles);
                }
            }
            
            if ($_REQUEST["action"] == "delete"){
                $metaKey = !empty($_POST["bdm_delete_meta_key"]) ? sanitize_text_field($_POST["bdm_delete_meta_key"]) : "";
                $fieldData = !empty($_POST["bdm_delete_field_data"]) ? sanitize_text_field($_POST["bdm_delete_field_data"]) : "";
                $roles = !empty($_POST["bdm_delete_role"]) ? array_map("sanitize_text_field", $_POST["bdm_delete_role"]) : [];
                if(empty($metaKey) || empty($roles || !wp_verify_nonce($nonce, "bdm_delete_nonce"))){
                    echo "<div class='error notice'><p>".__("Operation Failed!", "batch-datafier")."</p></div>";
                }else{
                    bdm_delete_batch_data($metaKey, $fieldData, $roles);
                }
            }
        }
        else{
            die("Failed security check");
        }
    }
?>
    <div class="wrap">
        <p><?php echo __("* Following operations are irreversible. Please be sure before proceed!", "batch-datafier"); ?></p>
        <h1>Add/Update User Batch Data</h1>
        <form id="bdm_add_data" action="<?php echo esc_url( admin_url( 'users.php?page=bdm-main-menu&action=add' ) ); ?>" method="post">
            <?php wp_nonce_field('bdm_add_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="bdm_add_meta_key"><?php echo __("Meta Key", "batch-datafier"); ?></label></th>
                    <td><input type="text" name="bdm_add_meta_key"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="bdm_add_field_data"><?php echo __("User Data", "batch-datafier"); ?></label></th>
                    <td><input type="text" name="bdm_add_field_data"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="bdm_role"><?php echo __("Role", "batch-datafier"); ?></label></th>
                    <td><select name="bdm_add_role[]" multiple><?php wp_dropdown_roles();?></select></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" value="<?php echo __("Insert/Update Data", "batch-datafier"); ?>" name="bdm_insert_data" class="button button-primary"><br>
            </p>
        </form>
    </div>
    
    <div class="wrap">
        <h1>Delete/Drop User Batch Data</h1>
        <p><?php echo __("* Leave 'User Data' field empty if you want to remove all metadata fields matching the meta key.", "batch-datafier"); ?></p>
        <form id="bdm_delete_data" action="<?php echo esc_url( admin_url( 'users.php?page=bdm-main-menu&action=delete' ) ); ?>" method="post">
            <?php wp_nonce_field('bdm_delete_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="bdm_delete_meta_key"><?php echo __("Meta Key", "batch-datafier"); ?></label></th>
                    <td><input type="text" name="bdm_delete_meta_key"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="bdm_delete_field_data"><?php echo __("User Data", "batch-datafier"); ?></label></th>
                    <td><input type="text" name="bdm_delete_field_data"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="bdm_delete_role"><?php echo __("Role", "batch-datafier"); ?></label></th>
                    <td><select name="bdm_delete_role[]" multiple><?php wp_dropdown_roles();?></select></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" value="<?php echo __("Delete/Drop Data", "batch-datafier"); ?>" name="bdm_delete_data" class="button button-primary"><br>
            </p>
        </form>
    </div>
<?
}

/*
 * Insert or update data function.
 */
function bdm_insert_batch_data($metaKey, $fieldData, $roles){
    $count = 0;
    $query = new WP_User_Query(['role__in' => $roles]);
    $users = $query->get_results();
    if (!empty($users)){
        foreach ($users as $user){
            if(update_user_meta($user->ID, $metaKey, $fieldData)){ $count++; }
        }
    }
    echo "<div class='updated notice'>".__("User field with meta key: ","batch-datafier")."<strong>".
    $metaKey."</strong> ".__("has been added/updated successfully for total users: ", "batch-datafier").$count."</div>";
}

/*
 * Delete or drop data function.
 */
function bdm_delete_batch_data($metaKey, $fieldData, $roles){
    $count = 0;
    $query = new WP_User_Query(['role__in' => $roles]);
    $users = $query->get_results();
    if (!empty($users)){
        if(!empty($fieldData)){
            foreach ($users as $user){
                if(delete_user_meta($user->ID, $metaKey, $fieldData)){ $count++; }
            }
        }
        else{
            foreach ($users as $user){
                if(delete_user_meta($user->ID, $metaKey)){ $count++;}
            }
        }
    }
    echo "<div class='updated notice'>".__("User field with meta key: ","batch-datafier")."<strong>".
    $metaKey."</strong> ".__("has been deleted/droped successfully for total users: ", "batch-datafier").$count."</div>";
}