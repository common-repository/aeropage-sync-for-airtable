<?php
/**
 * Plugin Name: Aeropage Sync for Airtable
 * Plugin URI: https://tools.aeropage.io/api-connector/dashboard
 * Description: Airtable to Wordpress Custom Post Type Sync Plugin
 * Version: 3.2.0
 * Author: Aeropage
 * Author URI: https://tools.aeropage.io/
 * License: GPL2
 * Requires PHP: 7.0.0
*/

//Add the cron job to the list of cron jobs upon activation of the function
//Cron job has an hourly schedule changed from the 10 minute schedule
register_activation_hook( __FILE__, "aero_plugin_activate" );
function aero_plugin_activate()
{
  if (!wp_next_scheduled ( "aero_hourly_sync" )) {
    wp_schedule_event(time(), "hourly", "aero_hourly_sync");
  }
}

//Remove the cron job from the list upon deactivation of the funciton
register_deactivation_hook( __FILE__, "aero_plugin_deactivate" );
function aero_plugin_deactivate() 
{
  wp_clear_scheduled_hook( "aero_hourly_sync" );
}

//Function that runs hourly
add_action("aero_hourly_sync", "aero_hourly_sync");
add_action("wp_ajax_testCronFunction", "aero_hourly_sync");
function aero_hourly_sync()
{
  try{
    //Get the posts where the auto sync is enabled
    $aeroPosts = get_posts([
      'meta_key' => 'aero_auto_sync',
      'meta_value' => 1,
      'post_type' => 'aero-template', 
      'post_status' => 'private',
      'numberposts' => -1
    ]);

    //Loop through the post
    foreach ($aeroPosts as $post)
    {
      //Get the token
      $token = get_post_meta($post->ID, "aero_token",true);
      //Check if there are new/modified records
      $response = aeropageModCheckApiCall($token);
      
      //If there's an error, we skip
      if($response["status"] !== "success") continue;

      //if there are new/modified records, we sync it
      if($response["has_new_records"] == 1){
        aeropageSyncPosts($post->ID);
      }
    }
    
  }catch(Exception $e){
    die(json_encode(
      array(
        "status" => "error",
        "message" => $e->getMessage()
      )
    ));
  }
}

add_action('admin_menu', 'aeropage_plugin_menu');
 
function aeropage_plugin_menu(){
  add_menu_page( 
    'Aeropage Sync for Airtable', 
    'Aeropage', 
    'manage_options', 
    'aeropage' , 
    'aeroplugin_admin_page', 
    plugin_dir_url( __FILE__ ) . 'assets/aeropage-icon-white-20px.svg', 
    61 
  );
}

/**
 * Init Admin Page.
 *
 * @return void
 */
function aeroplugin_admin_page() {
  require_once plugin_dir_path( __FILE__ ) . 'templates/app.php';
  
  //aeropageList();
  
}
add_action( 'admin_enqueue_scripts', 'aeroplugin_admin_enqueue_scripts' );

/**
 * Enqueue scripts and styles.
 *
 * @return void
 */
function aeroplugin_admin_enqueue_scripts() {
  //Enqueue only in the plugin page.
  if(isset($_GET['page']) && $_GET['page'] === "aeropage"){
    wp_enqueue_style( 'aeroplugin-style', plugin_dir_url( __FILE__ ) . 'build/index.css', array(), '1.2.3' );
    wp_enqueue_script( 'aeroplugin-script', plugin_dir_url( __FILE__ ) . 'build/index.js', array( 'wp-element' ), date("h:i:s"), true );
    wp_add_inline_script( 'aeroplugin-script', 'const MYSCRIPT = ' . json_encode( array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'plugin_admin_path' => parse_url(admin_url())["path"],
        'plugin_assets' => plugin_dir_url( __FILE__ ).'assets',
        'plugin_name' => "aeropage" //This is the name of the plugin.
    ) ), 'before' );
  }
}

add_action("wp_ajax_aeropageList", "aeropageList");
function aeropageList()
{

  $aeroPosts = get_posts(['post_type' => 'aero-template','post_status' => 'private','numberposts' => -1]);
	
	foreach ($aeroPosts as $post)
	{
    $post->sync_status = get_post_meta($post->ID, "aero_sync_status",true);
    $post->sync_time = get_post_meta($post->ID, "aero_sync_time",true);
    $post->sync_message = get_post_meta($post->ID, "aero_sync_message",true);
    $post->connection = get_post_meta($post->ID, "aero_connection", true);
    $post->aero_page_id = get_post_meta($post->ID, "aero_page_id", true);
    $post->aero_website_id = get_post_meta($post->ID, "aero_website_id", true);
    $post->token = get_post_meta($post->ID, "aero_token", true);
	}
	
  // this is for react...

  header('Content-Type: application/json');
  die(json_encode($aeroPosts));
}

/** This adds sync to the admin bar */
add_action( 'admin_bar_menu', 'aeroAddAdminBar', 100 );
function aeroAddAdminBar( $admin_bar ){
  global $post;

  if(!$post) return;

  $aeroCPT = get_post_meta($post->ID, '_aero_cpt', true);
  //If there's a value for _aero_cpt, we add this nav bar item
  //Will only show if user is an admin, we don't want it to show for 'members' only
  if($aeroCPT && current_user_can( 'manage_options' )){
    $admin_bar->add_menu( 
      array( 
        'id'=>'aero-sync-bar',
        'title'=>'
          <div
            style="display: flex;"
            id="aero-page-sync-container"
          >
            <svg
              style="margin-right: 2px;"
              xmlns="http://www.w3.org/2000/svg"
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="#FFF"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="feather feather-refresh-cw"
            >
              <polyline points="23 4 23 10 17 10"></polyline>
              <polyline points="1 20 1 14 7 14"></polyline>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>&nbsp;Resync Aeropage
          </div>
        ',
        'href'=>'',
      ) 
    );
  }
}

//This adds the View Record link in the admin bar menu
add_action( 'admin_bar_menu', 'aeroRecordLink', 101 );
function aeroRecordLink( $admin_bar ){
  global $post;

  if(!$post) return;

  //Get the Custom Post Type ID used for the 
  $aeroCPT = get_post_meta($post->ID, '_aero_cpt', true);
  //If there's a value for _aero_cpt, we add this nav bar item
  //Will only show if user is an admin, we don't want it to show for 'members' only
  if($aeroCPT && current_user_can( 'manage_options' )){
    //Get the connection from the parent CPT
    $connection = get_post_meta($aeroCPT, "aero_connection", true);
    //Get the record ID
    $recordID = get_post_meta($post->ID, '_aero_id', true);
    $admin_bar->add_menu( 
      array( 
        'id'=>'aero-record-link',
        'title'=>'View Record',
        'href'=>"https://airtable.com/".$connection."/".$recordID,
        'meta' => array(
          'target' => '_blank'
        )
      )
    );
  }
}

add_action( 'wp_footer', 'aeroSyncScript' );
function aeroSyncScript() {
  global $post;
  
  if($post){$aeroCPT = get_post_meta($post->ID, '_aero_cpt', true);}
  
  //If there's a value for aero_cpt, we add this script to the footer in the
  //actual wordpress site. This will only be shown if the user is an admin
  if(isset($aeroCPT) && current_user_can( 'manage_options' )){
  ?> 
    <script type="text/javascript">
      document.getElementById("aero-page-sync-container").onclick = function () {
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        var params = new URLSearchParams();
        var xhttp = new XMLHttpRequest();

        params.append("action", "aeropageSyncPosts");
        params.append("id", "<?php echo $aeroCPT; ?>");

        xhttp.open("POST", ajaxurl, false);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send(params);

        location.reload();
      }
    </script>
  <?php
  }
}

add_action( 'wp_ajax_aeropageEditorMeta', 'aeropageEditorMeta');
//Gets the aero page token when in the edit post
function aeropageEditorMeta(){
  $pid = intval($_POST["id"]);
  $token = get_post_meta($pid, "aero_token");
  $status = get_post_meta($pid, "aero_sync_status");
  $sync_time = get_post_meta($pid, "aero_sync_time");
  $auto_sync = get_post_meta($pid, "aero_auto_sync");
  $record_post_status = get_post_meta($pid, "aero_post_status");
  $mapped_post_type = get_post_meta($pid, "aero_mapped_post_type", true);
  $mapped_fields = get_post_meta($pid, "aero_mapped_post_meta_fields", true);
  die(json_encode(
    array(
      "token" => $token,
      "status" => $status, 
      "sync_time" => $sync_time, 
      "auto_sync" => $auto_sync,
      "post_status" => $record_post_status,
      "mapped_fields" => $mapped_fields,
      "mapped_post_type" => $mapped_post_type ? $mapped_post_type : ""
    )
  ));
}

add_action("wp_ajax_aeropageGetRegisteredPostTypes", "aeropageGetRegisteredPostTypes");
function aeropageGetRegisteredPostTypes(){
  $registeredPostTypes = get_post_types(array(), "objects");
  die(json_encode(array(
    "status" => "success",
    "post_types" => $registeredPostTypes
  )));
}


// make sure all the custom post types are registered.
add_action( 'init', 'aeroRegisterTypes' );

function aeroRegisterTypes()
{

  try{
    $flush = null;

		$aeroPosts = get_posts(['post_type' => 'aero-template','post_status' => 'private','numberposts' => -1]);

		foreach ($aeroPosts as $template)
		{

		$title = $template->post_title;

		$slug = $template->post_name; // eg Headphones

    $mapped_post_type = get_post_meta($template->ID, "aero_mapped_post_type", true);

    //Prevent mapped post types from being registered.
		if (!post_type_exists($slug) && $slug && !$mapped_post_type)
		{

    // echo "SLUG: ";
    // echo $slug;
		$flush = true;

		register_post_type( "$slug", //airconnex_templates
				array(
					"labels" => array(
						"name"=> _("$title"),
						"singular_name" => _("$title")
					),
					'hierarchical' => true,
					"has_archive" => false,
					"rewrite" => array( "slug" => "$slug" ), // my custom slug
					"supports" => array( "title","editor","thumbnail" ), // editor page settings
					"show_in_rest" => true, // gutenberg
					"description" => "$title",
					"public" => true, // All the relevant settings below inherit from this setting
					"publicly_queryable" => true, // When a parse_request() search is conducted, should it be included?
					"show_ui" => true, // Should the primary admin menu be displayed?
					"show_in_nav_menus" => true, // Should it show up in Appearance > Menus?
					"show_in_menu" => true, // This inherits from show_ui, and determines *where* it should be displayed in the admin
					"show_in_admin_bar" => true, // Should it show up in the toolbar when a user is logged in?
					'taxonomies' => array('category', 'post_tag'), // add taxonomies //,'post_tag'
				)
				
			);

		}

    //Register the post meta for the posts
    $synced_fields = get_post_meta($template->ID, "aero_sync_fields", true);

    if($synced_fields){
      //Add the cpt meta key and the record ID meta key
      register_post_meta($slug, "_aero_cpt", array("show_in_rest" => true, "description" => "Custom post type ID"));
      register_post_meta($slug, "_aero_id", array("show_in_rest" => true, "description" => "Airtable Record ID"));

      foreach($synced_fields as $key => $value){
        //Register the fields
        register_post_meta($slug, "aero_$key", array("show_in_rest" => true, "description" => $key));
      }
    }
    }
		if ($flush){flush_rewrite_rules();}
  }catch(Exception $e){
    echo esc_attr($e->getMessage());
  }
}



// add custom types to category / archive page queries

// function aeroPostTypesInQuery ($query) 
// {

//   if(empty($query->query['post_type']) or $query->query['post_type'] === 'post')
//   {

//     $defaultTypes = array ('post', 'page');
//     $aeroTypes = array();
//     $aeroPosts = get_posts(['post_type' => 'aero-template','post_status' => 'private','numberposts' => -1]);

//     foreach ($aeroPosts as $template)
//     {
//       $slug = $template->post_name; // eg Headphone
//       $aeroTypes[] = $slug; // add to array
//     }

//     $postArray = array_merge($defaultTypes,$aeroTypes);

//     $query->set('post_type', $postArray);

//   }
// }
 
// add_action('pre_get_posts', 'aeroPostTypesInQuery');

add_action("wp_ajax_aeropageEdit", "aeropageEdit");
function aeropageEdit() // called by ajax, adds the cpt
{
  $post_id = null;

  if($_POST['id'])
  {
    $post_id = intval($_POST['id']);
  }

  // can be passed an id (edit) or empty to create new
  // wordpress will automatically increment the slug if its already used.
  $template_post = array(
    'ID' => $post_id,
    'post_title' => sanitize_text_field($_POST['title']),
    'post_name' => sanitize_text_field($_POST['slug']),
    'post_excerpt'=> sanitize_text_field($_POST['dynamic']),
    'post_type' => 'aero-template',
    'post_status' => 'private'
  );

  $id = wp_insert_post($template_post);

  if ($id)
  {
    $auto_sync = false;
    
    if($_POST['auto_sync'] === "true"){
      $auto_sync = true;
    }

    update_post_meta ($id,'aero_token', sanitize_text_field($_POST['token']));
    update_post_meta ($id,'aero_auto_sync', $auto_sync);
    // update_post_meta ($id,'aero_connection', sanitize_text_field($_POST["app"])."/".sanitize_text_field($_POST["table"])."/".sanitize_text_field($_POST["view"]));
    update_post_meta ($id, 'aero_post_status', sanitize_text_field($_POST["post_status"]));
    
    $post_type = sanitize_text_field($_POST["post_type"]);

    //If there is a post type, we will save it to the post meta
    update_post_meta($id, 'aero_mapped_post_type', $post_type);

    if($post_type){
      update_post_meta($id, 'aero_mapped_post_meta_fields', json_decode(stripslashes($_POST["mapped_fields"])));
    }

    // Removed this since we are already doing the sync process in the frontend when saving the settings and when manually syncing...
    // This is so that it will prevent 504 issues when syncing and updating posts in the database.
    // $response = aeropageSyncPosts($id);

    // if($response['status'] === "error"){
    //   header('Status: 503 Service Temporarily Unavailable');
    //   die(json_encode(array("status" => "error", "message" => $response["message"])));
    // }else{
    //   die(json_encode(array("status" => "success", "post_id" => $id, "response" => $response)));
    // }
    die(json_encode(array("status" => "success", "post_id" => $id)));
  }
  die(json_encode(array("status" => "error", "message" => "No ID found in the database.")));
}

// function aeropageTokenCheck()
// {
//   $token = $_POST["token"];
//   aeropageTokenApiCall($token);
// }

function aeropageTokenApiCall($token)
{
  $split = explode('-', $token);

  if(count($split) > 1){
    $api_url = "https://api.aeropage.io/api/v5/tools/connector/$split[1]";
  }else{
    $api_url = "https://tools.aeropage.io/api/token/$token/";
  }
	
  $response = wp_remote_get($api_url, array("timeout" => 30));
  $result = json_decode( wp_remote_retrieve_body($response), true);
  return $result;
}

function aeropageModCheckApiCall($token)
{
  $split = explode('-', $token);

  if(count($split) > 1){
    $api_url = "https://api.aeropage.io/api/v5/modtime/check/$split[1]";
  }else{
    $api_url = "https://tools.aeropage.io/api/modcheck/$token/";
  }

  $result = json_decode(wp_remote_retrieve_body(wp_remote_get($api_url, array("timeout" => 30))), true);
  return $result;
}

add_action("wp_ajax_aeropageDeletePost", "aeropageDeletePost");
function aeropageDeletePost() 
{

  global $wpdb;

  $post_id = null;

  if($_POST['id'])
  {
    $post_id = intval($_POST['id']);
  }

  $parent = get_post($post_id);

  $mapped_post_type = get_post_meta($post_id, 'aero_mapped_post_type', true);

  $slug = $parent->post_name;

  //Delete all the posts for that post type
  $wpdb->query($wpdb->prepare(
    "
    DELETE a,b,c
    FROM wp_posts a
    LEFT JOIN wp_term_relationships b
        ON (a.ID = b.object_id)
    LEFT JOIN wp_postmeta c
        ON (a.ID = c.post_id)
    WHERE a.post_type = %s;
    "
  , $slug));

  if($mapped_post_type){
    $wpdb->query($wpdb->prepare(
      "
      DELETE a,b,c
      FROM wp_posts a
      LEFT JOIN wp_term_relationships b
          ON (a.ID = b.object_id)
      LEFT JOIN wp_postmeta c
          ON (a.ID = c.post_id AND c.meta_key = '_aero_cpt')
      WHERE c.meta_value = %d;
      "
    , $post_id));
  }

  // Unregister the post type first
  unregister_post_type($slug);

  // Remove the post
  wp_delete_post($post_id, true);

  die(json_encode(array("status" => "success"))); 
}

add_action("wp_ajax_aeropageGetPostMetaForSelectedPostType", "aeropageGetPostMetaForSelectedPostType");
function aeropageGetPostMetaForSelectedPostType(){
  $postType = $_POST["post_type"];
  $postMetaKeys = get_registered_meta_keys("post", $postType);
  $acfFields = array();
  //Get the ACF fields
  if (function_exists('acf_get_field_groups')) {
    $acf_field_group = acf_get_field_groups(array('post_type' => $postType));

    foreach ($acf_field_group as $key => $value) {
      $acfGroupID = $value["key"];
      $acf_fields = acf_get_fields($acfGroupID);

      if($acf_fields){
        foreach ($acf_fields as $key => $field) {
          $acfFields[$field["name"]] = $field;
        }
      }
    }
  }

  die(json_encode(array(
    "status" => "success",
    "meta_keys" => array(
      "Registered Meta" => $postMetaKeys,
      "ACF" => $acfFields
    )
  )));
}

//echo aeropageSyncPosts(343);
//global $wpdb;
//$dump = get_posts(['meta_key' => "aero_media_atttebWsy0MAl73jq", 'numberposts' => 1]);
//echo var_dump($dump);
 
function aeropageGetChoices($value){
  return $value["name"];
}

add_action("wp_ajax_aeropageSyncPosts", "aeropageSyncPosts");
function aeropageSyncPosts($parentId)
{
  $isAjax = false;

  try{
    if(!$parentId)
    {
      $isAjax = true;
      $parentId = intval($_POST["id"]);
    }

    if(!$parentId)
    {
      die(json_encode(array("status" => "error", "message" => "No parent ID was passed.")));
    }
  

    global $wpdb;

    $parent = get_post($parentId);

    $token = get_post_meta($parentId,'aero_token',true);

    $record_post_status = get_post_meta($parentId, 'aero_post_status', true);

    $mapped_fields = get_post_meta($parentId, 'aero_mapped_post_meta_fields', true);

    $mapped_post_type = get_post_meta($parentId, 'aero_mapped_post_type', true);

    $callFromBackend = false;

    if($_POST["noCall"] != "1"){
      $apiData = aeropageTokenApiCall($token);
      $callFromBackend = true;
    }else{
      //Use the data from the frontend
      //it has the same data structure from the tools response
      //except that the records are in batches of 100
      $apiData = json_decode(wp_unslash($_POST["apiData"]), true);
    }

    $response = array();

    $acf_image_fields = array();

    if ( 
      isset($apiData['status']['type']) && 
      $apiData['status']['type'] == 'success' && 
      $apiData['records']
    )
    {
      $response['status'] = 'success';
      $response['message'] = "";

      // If it's not the first batch of sync and the api call did not come from the backend
      // We will just append the message so that we can still see the whole sync
      if($_POST["firstBatch"] == 0 && !$callFromBackend){
        $response['message'] = get_post_meta ($parentId,'aero_sync_message', true);
      }

      update_post_meta ($parentId,'aero_sync_status','success');
      $sync_time = time();
      update_post_meta ($parentId,'aero_sync_time', $sync_time);
      // trash posts 
      
      
      // fields are indexed numerically - iterate to create an array of types and sanitize
      
      $fieldData = array();
      $fieldTypeByName = array();
      $postContentFieldNames = array();
    
      foreach ($apiData['fields'] as $key=>$datafield)
      {
        $fld_id = sanitize_text_field($datafield['id']);
        $fld_name = sanitize_text_field($datafield['name']);
        $fld_type = sanitize_text_field($datafield['type']);
        $fieldData[$fld_id] = $datafield; //add the whole object
        $fieldData[$fld_id]['id'] = $fld_id;
        $fieldData[$fld_id]['name'] = $fld_name;
        $fieldData[$fld_id]['type'] = $fld_type;
        $fieldTypeByName[$fld_name] = $fld_type;

        if(is_array($apiData["status"]["content"]) && in_array($fld_id, $apiData["status"]["content"])){
          $postContentFieldNames[] = $fld_name;
        }

        //
      }

      //Add the choices to ACF and then retrive the fields that are image type
      if($mapped_fields){
        $typesWithChoices = array("select", "radio", "checkbox");

        foreach($mapped_fields as $key => $value){
          //Check if image type
          if($value->metaValues->type == "image" || $value->metaValues->type == "file" ){
            $acf_image_fields[$value->airtableField] = $value;
          }

          if(!in_array($value->metaValues->type, $typesWithChoices)) continue;

          //
          $airtableField = $value->airtableField;
          $fieldDta = null;
          $label = $value->metaValues->label;

          foreach($apiData["fields"] as $key => $field){
            if($field["name"] == $airtableField){
              $fieldDta = $field;
            }
          }

          if($fieldDta && is_array($fieldDta["options"]["choices"])){
            $choices = array_map("aeropageGetChoices", $fieldDta["options"]["choices"]);
            
            if(function_exists("acf_get_field")){
              $ACFField = acf_get_field($value->metaValues->key, true);
              foreach ($choices as $key => $choice) {
                $ACFField["choices"][$choice] = $choice;
              }
              acf_update_field($ACFField);
              $response['message'] .= "Choices for ACF Field $label updated using $airtableField Airtable field choices. <br />";
            }
          }
        }

        $response["acf_media_fields"] = $acf_image_fields;
      }

      // echo "<pre>";
      // print_r($postContentFieldNames);
      // echo "</pre>";

      update_post_meta ($parentId,'aero_sync_fields', $fieldTypeByName); // add to the parent cpt
    
      //If there is a mapped post type, we use that post type instead of the parent
      $post_type = $mapped_post_type ? $mapped_post_type : $parent->post_name;
      
      $dynamic = $parent->post_excerpt; //record_id or name
    
    
      // ACF FUNCTION - ON HOLD PENDING ACF UPDATES
      
      //$response['message'] .= aeropageACF($parentId,$post_type,$apiData['fields']);
      
      
      // CATEGORY MAPPING --------------------------------------------------
      
      if (isset($apiData['status']['categories']))
      {
      
        $categoryArray = array();
        
        // build one array with all categories
        
        foreach ($apiData['status']['categories'] as $categoryFieldID)
        {
        
          $categoryFieldName = $fieldData[$categoryFieldID]['name'];
          $response['message'] .= " categories field is $categoryFieldName<br>";
          $categoryChoices = $fieldData[$categoryFieldID]['options']['choices'];
          
          // create a parent category using the field name -----------
            
          $parentTerm = get_term_by('name', $categoryFieldName, 'category'); // does a term with this name exist
          $parentTermID = $parentTerm->term_id;
          
          $parentCategoryMetaKey = "aero_category_$categoryFieldID";
          
          if (is_int($parentTermID)) // if yes, update the choice with the existing term
          {
            update_post_meta($parentId,$parentCategoryMetaKey, $parentTermID);
          }
          else // if no, check if the choice had a category but was renamed
          {
            $parentTermID = get_post_meta ($parentId,$parentCategoryMetaKey);
          }
          
          if (is_int($parentTermID)) // if yes, rename the existing category
          {
            wp_update_term($parentTermID,'category', array('name' => $categoryFieldName ));
          }
          else // else create a new category
          {
            $term = wp_insert_term($categoryFieldName,'category');

            if(is_array($term)){
              $parentTermID = $term['term_id'];
              update_post_meta($parentId,$parentCategoryMetaKey, $parentTermID);
            }
          }
          
          foreach ($categoryChoices as $key => $choice)
          {
          
            if (isset($choice['id']) && isset($choice['name']))
            {
              $choiceID = $choice['id'];
              $choiceName = $choice['name'];
              $choiceName = trim($choiceName);// get rid of spaces!
              
              //$response['message'] .= "category $choiceID has a name : $choiceName <br>";

              if (strlen($choiceName > 0 ))
              {
              
                $response['message'] .= "category $choiceID is : $choiceName <br>";
                
                $term = get_term_by('name', $choiceName, 'category'); // does a term with this name exist
                $termID = $term->term_id;
                
                if (is_int($termID)) // if yes, update the choice with the existing term
                {
                  update_post_meta($parentId,"aero_terms_$choiceID", $termID);
                }
                else // if no, check if the choice had a category but was renamed
                {
                  $termID = get_post_meta ($parentId,"aero_terms_$choiceID");
                }

                if (is_int($termID)) // if yes, rename the existing category
                {
                  wp_update_term($termID,'category', array('name' => $choiceName,'parent' => $parentTermID  ));
                }
                else // else create a new category
                {
                  $term = wp_insert_term($choiceName,'category',array('name' => $choiceName,'parent' => $parentTermID  ));
                  $termID = $term['term_id'];
                  update_post_meta($parentId,"aero_terms_$choiceID", $termID);
                }
                
                if (is_int($termID))
                {
                  $categoryArray[$choiceName] = $termID; // build the array for reference in posts loop
                }
                
                unset($termID);
              }
            }
          // end if choices are set.
          }
          // end foreach choices
        }
        // end foreach categories
      }
      //end if categories ------------------------------------------------------------------

      //execute only on first batch or when in hourly sync.
      if($_POST["firstBatch"] == 1 || $callFromBackend){
        $trash = "
          UPDATE $wpdb->posts p
              INNER JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_aero_cpt') 
          SET p.post_status = 'trash' 
          WHERE pm.meta_value = %d";
        $results = $wpdb->get_results($wpdb->prepare($trash, $parentId));
      }
      
      $count = 1;
      
      //Media array of the records to be downloaded.
      $response["media"] = array();
      foreach ($apiData['records'] as $record)
      {
        $record_id = sanitize_text_field($record['id']);
        $record_name = sanitize_text_field($record['name']); 
        $record_slug = sanitize_text_field($record['slug']); 
        
        
        
        if ($dynamic !== 'name')
        {
        $record_slug = $record_id;
        }
        
        // find if theres a trashed post with this record id already

        $existing = get_posts([
          'post_type'=> $post_type,
          'post_status' => 'trash',
          'numberposts' => 1,
          'meta_key' => '_aero_id', 
          'meta_value' => $record_id 
        ]);
        
        $post_status = $record_post_status;

        //If there's a post, use that post ID otherwise just left it empty
        if ($existing){
          $existing_id = $existing[0]->ID;
          // $post_status = "publish";
          $post_status = $record_post_status;
          //We set the existing post content so that it won't be overwritten when saving/updating post
          $existing_post_content = $existing[0]->post_content;
        }else{
          $existing_id = "";
          $existing_post_content = "";
        }
        
        
        if (strlen($record['post_title']) > 0)
        {
        $post_title = sanitize_text_field($record['post_title']);
        $post_title_msg = "<br>--> adding custom post_title as $post_title.";
        }
        else
        {
        $post_title = $record_name;
        $post_title_msg = "<br>--> no custom post title.";
        }
        
        if (strlen($record['post_excerpt']) > 0)
        {
        $post_excerpt = sanitize_text_field($record['post_excerpt']);
        $post_excerpt_msg = "<br>--> adding custom post_excerpt as $post_excerpt.";
        }
        else
        {
        $post_excerpt = $record_name;
        $post_excerpt_msg = "<br>--> no custom post excerpt.";
        }

        $record_post = array(
          'ID' => $existing_id,
          'post_title' => $post_title,
          'post_excerpt' => $post_excerpt,
          'post_name' => $record_slug,
          'post_parent' => '',
          'post_type' => $post_type,
          'post_status' => $post_status,
          'post_content' => $existing_post_content
        );

        //If there is a post content, then we will set the post content to the value of these fields.
        if(count($postContentFieldNames) > 0){
          //Reset the post content
          $record_post['post_content'] = "";
          $post_content = "<br>--> Post Content added.";
          //Concatenate all the post content fields in the records
          foreach($postContentFieldNames as $key => $field){
            $record_post["post_content"] .= html_entity_decode($record["fields"][$field]) . " ";
            $post_content .= "<br>------> Added Content for field $field";
          }
        }
          
        $record_post_id = wp_insert_post($record_post);
        
        $count++;

        update_post_meta ($record_post_id, '_aero_cpt', $parentId);
        update_post_meta ($record_post_id, '_aero_id', $record_id);


        if ($existing)
        {
          $response['message'] .= "<br>record $record_id : $record_name already exists as $record_post_id and is being updated.".$post_title_msg.$post_excerpt_msg.$post_content;
        }
        else
        {
          $response['message'] .= "<br>record $record_id : $record_name has been created as $record_post_id.".$post_title_msg.$post_excerpt_msg.$post_content;
        }



        foreach ($record['fields'] as $key=>$value)
        {
      
          $type = $fieldTypeByName[$key]; // get the type
          
          if ($type == 'select_multiple' or $type == 'lookup_text_short' or $type == 'linked' )
          {
            $value = implode(',',$value); // implode array into string before we add it
            $response['message'] .= "<br> ---> field $key array to csv is $value.";
          }
          
          if (substr($type,0,11) == 'attachment_' )
          {
            $value = sanitize_url($value[0]['url']);
          }
          
          if ($value)
          {
            $value = sanitize_text_field($value);  
            update_post_meta ($record_post_id, "aero_$key", $value);
            $response['message'] .= "<br> ---> field $key of type $type has been added.";
          }
          //for ACF --> update_field using the field key
          //$field_id = $fieldIDByName[$key]; // get the field id
          //$acf_field_key = 'field_'.$parentId.$field_id;
          //update_field( $acf_field_key, $value, $record_post_id );
        }
        // end foreach field
        if($mapped_fields){
          //For post meta and ACF
          foreach ($mapped_fields as $key => $mappedFieldData) {
            $value = $record['fields'][$mappedFieldData->airtableField];

            $type = $fieldTypeByName[$mappedFieldData->airtableField];

            if (($type == 'select_multiple' or $type == 'lookup_text_short' or $type == 'linked') && $value)
            {
              $value = implode(',',$value); // implode array into string before we add it
            }

            if (substr(str_replace("lookup_", "", $type) ,0,11) == 'attachment_' && $value)
            {
              $value = sanitize_url($value[0]['url']);
            }
            
            if(is_string($value)){
              $value = html_entity_decode($value);
            }

            //Update ACF field
            if($mappedFieldData->metaValues->key && function_exists("update_field")){
              update_field($mappedFieldData->metaValues->key, $value, $record_post_id);  
            }

            update_post_meta($record_post_id, $key, $value);
            $response['message'] .= "<br> -----> Updated custom post field $key.";
          }
        }

      
        // POST MEDIA (ARRAY) DOWNLOAD
      
        if (is_array($apiData['status']['media']))
        {
        
          $check = true; 

          if ( defined( 'DOING_CRON' ) )
          {
            $check = false;
          }

          $count = 0;
          $mediaArray = array();
          
          foreach ($apiData['status']['media'] as $mediaFieldID)
          {
            $mediaFieldName = $fieldData[$mediaFieldID]['name'];
            $mediaFieldType = $fieldData[$mediaFieldID]['type'];
            $mediaFieldValue = $record['fields'][$mediaFieldName];
            $response['message'] .= "<br>--> media field is $mediaFieldName ";

            foreach ($mediaFieldValue as $key => $mediaObject)
            {
              $response['message'] .= "<br>------> media $mediaFieldName $key is being checked... ";
              //Check if media needs to be downloaded
              $mediaResponse = aeropage_media_downloader($mediaObject,$record_post_id, $count, $check, $mediaFieldName);
              $mediaPostid = $mediaResponse[0];
              $mediaPostMsg = $mediaResponse[1];
              $mediaToBeDownloaded = $mediaResponse[3];

              //If media needs to be downloaded we add it to the media array
              if($mediaToBeDownloaded){
                $response["media"][] = $mediaToBeDownloaded;
              }

              if(count($acf_image_fields) > 0 && $mediaPostid){
                //Check which acf field it is in
                $acfValues = $acf_image_fields[$mediaFieldName]->metaValues;

                if($acfValues->key && function_exists("update_field")){
                  
                  update_field($acfValues->key, $mediaPostid, $record_post_id);
                  $response['message'] .= "<br>-------->Updated ACF field";
                }
              }
              
              $response['message'] .= "<br>-------->$mediaPostMsg";
              $count++;
            }
          }
            // end foreach media field
        }
        elseif (strlen($record['post_image']) > 0) // this was using url, superceded by the above
        {
          $image_value = sanitize_url($record['post_image']);
          $thumbnail_id = get_post_meta( $record_post_id, '_thumbnail_id', true ); // check if this post already has thumbnails...

          if (!$thumbnail_id) // if we dont already have the thumb for this post
          {
            $response['message'] .= "<br>--> There is a post_image, but no thumbnail found. downloading now.";
            $thumbnail_id = aeropage_external_image($image_value,$record_post_id,$record_name);

            //Set the attachment as featured image.
            delete_post_meta( $record_post_id, '_thumbnail_id' );
            add_post_meta( $record_post_id , '_thumbnail_id' , $thumbnail_id, true );
          }
          unset($thumbnail_id);
        }


        //----------------------
      // CATEGORY ASSIGNMENTS --------
      
        if (is_array($categoryArray) && count($categoryArray) > 0)
        {
          $postCategories = array(); //make an array
          
          foreach ($apiData['status']['categories'] as $categoryFieldID)
          {
            $categoryFieldName = $fieldData[$categoryFieldID]['name'];
            $categoryValue = $record['fields'][$categoryFieldName]; // get the csv
            
            if (is_array($categoryValue)) // already an array
            {
              $categoryValueArray = $categoryValue;
            }
            else
            {
              $categoryValueArray = explode(',',$categoryValue); // comma sep,  explode to array
            }
            
            if (count($categoryValueArray) == 0 && strlen($categoryValue) > 0 ) // still not array and string
            {
              $categoryValueArray = array($categoryValue); // 
            }
            
            foreach ($categoryValueArray as $categoryName)
            {
              $categoryName = trim($categoryName);// get rid of spaces!
              $categoryTermId = $categoryArray[$categoryName]; // get the term id
              if (is_int($categoryTermId)){$postCategories[] = $categoryTermId;} // add to array if valid
            }
              //end foreach category	
            if (is_array($postCategories)){
              $response['message'] .= "<br> -----> Post Categories | $categoryFieldName : $categoryName";
              wp_set_post_categories($record_post_id,$postCategories);
            } // set the post categories    
          }
          // end foreach category field
        }
        // end if categories ----------
        //---------- TAGS ASSIGNMENT --------------------------------
        if (isset($apiData['status']['tags'])){
          $postTags = array(); //make an array
          foreach ($apiData['status']['tags'] as $tagsFieldID){
            $tagFieldName = $fieldData[$tagsFieldID]['name'];
            $tagValue = $record['fields'][$tagFieldName]; // get the value

            if (is_array($tagValue)){ // already an array merge it with the current elements of the array
              $postTags = array_merge($postTags, $tagValue);
            }else{ //Is a string so explode and add to array
              $postTags = array_merge($postTags, explode(',',$tagValue));
            }
          }

          wp_set_post_tags($record_post_id, $postTags, false);
        }else{
          //If it is not set, just set it to empty array to reset the tags.
          wp_set_post_tags($record_post_id, array(), false);
        }
      }
      //-----------------------------------------------------------
      
      // end foreach record
      update_post_meta ($parentId,'aero_sync_message', trimStrings($response['message']));
      update_post_meta ($parentId,'aero_page_id', sanitize_text_field($apiData['status']['id']));

      //If there's a website ID in the data, save it.
      if($apiData['status']['websiteID']){
        update_post_meta ($parentId,'aero_website_id', sanitize_text_field($apiData['status']['websiteID']));
      }

      update_post_meta ($parentId,'aero_connection', sanitize_text_field($apiData['status']["app"])."/".sanitize_text_field($apiData['status']["table"])."/".sanitize_text_field($apiData['status']["view"]));
    }
    else // some problem with api
    {
      $response['status'] = 'error';
      update_post_meta ($parentId,'aero_sync_status','error');
      $message = sanitize_text_field($apiData['message']);
      update_post_meta ($parentId,'aero_sync_message',$message);
      $response['message'] = $message;
    }

    //If doing AJAX

    if($isAjax)
    {
      $response["sync_time"] = $sync_time;
      $response["check"] = !$_POST["firstBatch"] && !$callFromBackend;
      $response["trash_check"] = $_POST["firstBatch"] == 1 || $callFromBackend;
      $response['$_POST["firstBatch"]'] = $_POST["firstBatch"];
      $response['$callFromBackend'] = $callFromBackend;
      $response["results"] = $results;
      die(json_encode($response));
    }
    else{
      return $response;
    }
  }catch(Exception $e){
    if($isAjax){
      http_response_code(500);
      echo json_encode(array("status" => "error", "message" => $e->getMessage()));
      die(0);
    }else{
      echo $e->getMessage();
    }
  }catch (Error $e) {
    if($isAjax){
      http_response_code(500);
      echo json_encode(array("status" => "error", "message" => $e->getMessage()));
      die(0);
    }else{
      echo $e->getMessage();
    }
  }catch (Throwable $e) {
    if($isAjax){
      http_response_code(500);
      echo json_encode(array("status" => "error", "message" => $e->getMessage()));
      die(0);
    }else{
      echo $e->getMessage();
    }
  }
}
// end function

/**
 * Trims a string to ensure it is less than 1MB.
 *
 * @param string $string The input string.
 * @return string The trimmed string if it exceeds 1MB, otherwise the original string.
 */
function trimStrings($string) {
  // Define the 1MB size limit
  // $maxSize = 1048576; // 1MB in bytes
  $maxSize = 900000; // Around 900KB in bytes

  // Check the size of the string in bytes
  $stringSize = strlen($string);

  // If the string exceeds the 1MB size limit, truncate it
  if ($stringSize > $maxSize) {
      // Truncate the string to the maximum allowed size
      $string = substr($string, 0, $maxSize);
  }

  return $string;
}

add_action("wp_ajax_aeropageMediaDownload", "aeropageMediaDownload");
function aeropageMediaDownload() {
  try{
    $media = json_decode(stripslashes($_POST["media"]), true);

    if(is_array($media)){
      $response = aeropage_media_downloader($media["media"], $media["record_post_id"], $media["field_index"]);
      $mediaPostid = $mediaResponse[0];
      $mediaFieldName = $media["field_name"];
      $acf_image_fields = (array) json_decode(stripslashes($_POST["acf_image_fields"]));

      if(count($acf_image_fields) > 0 && $mediaPostid){
        //Check which acf field it is in
        $acfValues = $acf_image_fields[$mediaFieldName]->metaValues;

        if($acfValues->key && function_exists("update_field")){
          update_field($acfValues->key, $mediaPostid, $media["record_post_id"]);  
        }
      }

      die(json_encode(array("status" => "success", "message" => $response[1])));
    }else{
      throw new Exception("Invalid data type received by the request handler.");
    }
  }catch(Exception $e){
    header('Status: 503 Service Temporarily Unavailable');
    die(json_encode(array("status" => "error", "message" => $e->getMessage())));
  }
}

// MEDIA DOWNLOADER  --------------------------------------

//$mediaObject => Airtable Media structure
//$parent => wordpress post ID where the image will be attached
//$field_index => index of the airtable field--will be used to set the featured image
//$check => flag for checking if we add the image to an array when syncing.
function aeropage_media_downloader($mediaObject, $parent, $field_index, $check = NULL, $fieldName = "")
{

  global $wpdb;

  $response = array();
  $attachmentURL = sanitize_url($mediaObject['url']);
  $attachmentID = sanitize_text_field($mediaObject['id']);
  $attachmentFileType = sanitize_text_field($mediaObject['type']);
  $fileNameExploded = explode(".", $mediaObject['filename']);
  $fileTypeExploded = explode("/", $attachmentFileType);
  $attachmentFileName = sanitize_file_name(strtolower($fileNameExploded[0]));

  $response[1] = "Checking Attachment : $attachmentID <br>";

  $uploadDir = wp_upload_dir();
  $uploadFolder = $uploadDir['basedir'].'/airtable/';
  $uploadPathWithAttachmentID = $uploadFolder.$attachmentID.'_'.$attachmentFileName.'.'.$fileTypeExploded[1];
  //$check is a flag that tells us if we are just adding to media array, if true we will add the media object to an array along with the record post ID
  //This will be used for the media download in the second step.
 
    //Otherwise if we are not checking the image
    if (!file_exists($uploadFolder)) wp_mkdir_p($uploadFolder); // if no folder, create.

    // CHECK IF FILE NEEDS TO BE DOWNLOADED

  if (!file_exists($uploadPathWithAttachmentID)) // file doesnt already exist in folder, download it
  {
    if($check){
      $response[1] .= "Adding media for ".$parent."to be downloaded later.";
      $response[3] = array(
        "media" => $mediaObject,
        "record_post_id" => $parent,
        "field_index" => $field_index,
        "field_name" => $fieldName
      );
    }else{
      //If the 
      $checkURL = wp_remote_get( $attachmentURL ); // check the url to make sure its valid

      if (! is_wp_error( $checkURL ) ) 
      {
        $response[1] .= "File doesnt exist and url is valid. Downloading.";
        $downloadedFile = wp_remote_retrieve_body( $checkURL ); // get the image file
        $fp = fopen($uploadPathWithAttachmentID , 'w' ); // set the path to save
        fwrite( $fp, $downloadedFile ); // write the contents to the file
        fclose( $fp ); // close the path
      }
    }
  }else{
    $response[1] .= "File already exists in the folder.";
    $downloadedFile = true; // indicate that the file is already downloaded.
  }
  

  // CHECK IF THIS ATTACHMENT WAS ALREADY PROCESSED ON A PREVIOUS RUN
  $existingMediaCheck = get_posts(['meta_key' => "aero_media_$attachmentID",'post_parent' => $parent,'post_type' => 'attachment', 'numberposts' => 1]);
  $existingMedia = $existingMediaCheck[0]->ID;

  // check for an attachment with meta_key matching this

  if ($existingMedia) // attachment exists, skip this
  {
    $response[0] = $existingMedia;
    $response[1] .= " > Attachment Post already exists as : $existingMedia. Skipping.";
  }
  elseif ($downloadedFile) // no attachment exists and we have the file. If there's no downloaded file, then we just skip this code block.
  {
    $attachment = array(
      'post_title' => $attachmentFileName,
      'post_mime_type' => $attachmentFileType,
      'post_status' => 'inherit'
    );

    $mediaPostid = wp_insert_attachment( $attachment, $uploadPathWithAttachmentID, $parent ); //create / attach file to its parent post
    
    if (is_int($mediaPostid))
    {
        require_once ABSPATH . 'wp-admin/includes/image.php'; //require for wp_generate_attachment_metadata
        $attachmentMeta = wp_generate_attachment_metadata( $mediaPostid, $uploadPathWithAttachmentID);
        wp_update_attachment_metadata( $mediaPostid, $attachmentMeta );
        update_post_meta($mediaPostid,"aero_media_$attachmentID",'done');// mark media with attachment id.
        $response[0] = $mediaPostid;
        $response[1] .= " > Attachment Post was inserted as : $mediaPostid";
    }
    else
    {
    $response[1] .= "The file was downloaded, but a problem occurred with wp_insert_attachment.";
    }
  }	

  //If the field is the first index in the media/attachments and there is a mediaID, we will add the media as a feature image
  if (is_int($response[0]) and $field_index == 0) //set first as thumbnail
  {
    $response[1] .= "<br>----------->Setting as thumbnail -> $response[0]";
    update_post_meta( $parent , '_thumbnail_id' , $response[0], true );
  }

  return $response;
}



// IMAGE DOWNLOADER (DEPRECATED) --------------------------------------

function aeropage_external_image($ext_url,$parent,$record_name)
{
  global $wpdb;
  
  //$ext_url -- the external url
  //$parent -- the parent post to attach to  
  
  $upload_dir = wp_upload_dir();
  $upload_folder = $upload_dir['basedir'].'/aeropage/';
        
  if(!file_exists($upload_folder)) wp_mkdir_p($upload_folder);
    
  $ext_img = wp_remote_get( $ext_url ); // check the url to make sure its valid
  
  //$data['headers']['content-disposition']

  $content_type = $ext_img['headers']['content-type'];
  $exploded = explode("/", $content_type); //'application/pdf'
  $extension = $exploded[1]; //Contains the extension i.e. "jpeg, png, etc"
  
  
  $image_title = $record_name; // this is already sanitized before being passed
  $image_filename = sanitize_file_name( $record_name.'.'.$extension);
  

  if (! is_wp_error( $ext_img ) ) 
  {
    $img_content = wp_remote_retrieve_body( $ext_img ); // get the image file
    $fp = fopen( $upload_folder.$image_filename , 'w' ); // set the path to save
    fwrite( $fp, $img_content ); // write the contents to the file
    fclose( $fp ); // close the path
    $wp_filetype = wp_check_filetype( $image_filename , null ); // check the filename
    $attachment = array(
      'post_mime_type' => $ext_img['headers']['content-type'], //We'll use the content type returned from response since this is more reliable //$wp_filetype['type'], // mimetype
      'post_title' => $image_title,
      'post_content' => '',
      'post_status' => 'inherit'
    );
    
    $image_filepath = $upload_folder.$image_filename;

    //require for wp_generate_attachment_metadata which generates image related meta-data also creates thumbs
    
    require_once ABSPATH . 'wp-admin/includes/image.php';
    
    $thumbnail_id = wp_insert_attachment( $attachment, $image_filepath, $parent );
    
    if ($thumbnail_id)
    {
    //Generate post thumbnail of different sizes.
    $attach_data = wp_generate_attachment_metadata( $thumbnail_id , $image_filepath);
    wp_update_attachment_metadata( $thumbnail_id,  $attach_data );

    return $thumbnail_id; 
    } 
  }
}
// end function


// ADVANCED CUSTOM FIELDS (SUSPENDED) --------------------------------------
	
function aeropageACF($parentId, $post_type, $fields)
{

global $wpdb;
 
if( function_exists('acf_add_local_field_group') ):

//Note: Field Groups and Fields registered via code will not be visible/editable via the �Edit Field Groups� admin page.

$response = "<br>ACF CONDITION IS EXECUTING for $post_type ($parentId), $acf_field_group_name";

$acf_field_group_name = 'group_'.$parentId;

    $acf_existing = get_posts([
      'post_type'=> 'acf-field-group',
      'name' => "$acf_field_group_name",
      'post_status' => 'publish'
    ]);
    
   if ($acf_existing){$acf_existing_id = $acf_existing[0]->ID;}
   
   $count = count($acf_existing);
   
   if ($acf_existing_id){$response .= "<br>-- $count group post $acf_existing_id was found by post name $acf_field_group_name";}

$acf_field_group_content = array (
    'location' => array (
        array (
            array (
                'param' => 'post_type',
                'operator' => '==',
                'value' => $post_type,
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
	'description' => 'Automatically added by Aeropage',
);

$acf_field_group_content = serialize($acf_field_group_content);

   $acf_field_group = array(
		'ID' => $acf_existing_id,
        'post_title'     => $post_type,
		'post_content'     => $acf_field_group_content,
        'post_excerpt'   => sanitize_title( $post_type ),
        'post_name'      => $acf_field_group_name,
        'post_date'      => date( 'Y-m-d H:i:s' ),
        'comment_status' => 'closed',
        'post_status'    => 'publish',
        'post_type'      => 'acf-field-group',
    );
    
	$acf_field_group_id  = wp_insert_post( $acf_field_group );
	
	if ($acf_existing_id){$response .= "<br>-- group post $acf_existing_id was updated for group_$parentId";}
	else{$response .= "<br>-- group was created for group_$parentId";}
	
	
	//--- register each field ---------------------------------------------
	
	$order = 0;
	
	foreach ($fields as $key=>$value)
    {
	
	extract($value, EXTR_PREFIX_ALL, "field");
	
	$acf_field_key = 'field_'.$parentId.$field_id;

	$response .= "<br>-- field $field_id $field_name of type $field_type is being registered as $acf_field_key ...";
	
	
	// -- mapping aeropage types to acf types ---
	
	$acf_type = 'text'; // failover to text
	
	if ($field_type == 'number'){$acf_type = 'number';}
	
	if ($field_type == 'select_single')
	{
	$acf_type = 'select';
	$choices = array();
	foreach ($field_options['choices'] as $key=>$value)
    {
	$choice = $value['name'];
	$choices[$choice]= $choice;
	};
	//$default_value = $field_options['choices'][0];
	}
	
	
	$acf_field_existing = get_posts([
      'post_type'=> 'acf-field',
      'name' => $acf_field_key,
      'post_status' => 'publish'
    ]);
    
   if ($acf_field_existing){$acf_field_existing_id = $acf_field_existing[0]->ID;}
	
	$acf_field_content = array(
            'key' => $acf_field_key,
            'label' => 'My Field Title',
            'name' => 'my_field_name',
            'type' => $acf_type, 
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'message' => '',
            'default_value' => 0,
            'ui' => 1,
            'ui_on_text' => '',
            'ui_off_text' => '',
        );
		
	if ($choices){$acf_field_content['choices'] = $choices;}
	
	$acf_field_content = serialize($acf_field_content);

	$acf_field_post = array(
		'ID' => $acf_field_existing_id,
        'post_title'     => $field_name,
		'post_content'   => $acf_field_content,
        'post_excerpt'   => sanitize_title( $field_name ),
        'post_name'      => $acf_field_key,
        'post_date'      => date( 'Y-m-d H:i:s' ),
        'comment_status' => 'closed',
        'post_status'    => 'publish',
        'post_type'      => 'acf-field',
		'post_parent'      => $acf_field_group_id,
		'menu_order'      => $order,
    );
    
	$acf_field_post_id = wp_insert_post( $acf_field_post );
	
	if ($acf_field_existing_id){$response .= "<br>-- existing field was updated";}
	else{$response .= "<br>-- new field was created";}
	
	$order++;
	
	}
	//---- 
	
	unset($acf_existing_id);


endif;

return $response;
	
	//------------------------------------------------------------

}


// WOOCOMMERCE (FUTURE) --------------------------------------

  /* 
	$product = new WC_Product_Simple();
    $product->set_name( 'Photo: ' . get_the_title( $image_id ) );
    $product->set_status( 'publish' ); 
    $product->set_catalog_visibility( 'visible' );
    $product->set_price( 19.99 );
    $product->set_regular_price( 19.99 );
    $product->set_sold_individually( true );
    $product->set_image_id( $image_id );
    $product->set_downloadable( true );
    $product->set_virtual( true );      
   
 
	$src_img = wp_get_attachment_image_src( $image_id, 'full' );
    $file_url = reset( $src_img );
    $file_md5 = md5( $file_url );
	$download = new WC_Product_Download();
    $download->set_name( get_the_title( $image_id ) );
    $download->set_id( $file_md5 );
    $download->set_file( $file_url );
    $downloads[$file_md5] = $download;
    $product->set_downloads( $downloads );
	*/