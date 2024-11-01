=== Aeropage Sync for Airtable ===
 
Contributors: Aeropage
Tags: Airtable, Sync, CPT, Custom Post Type, Divi, Elementor, Dynamic Tags, Metadata
Requires at least: 6.0.2
Tested up to: 6.6
Stable tag: 3.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 7.0.0

=== Description ===
<p>A powerful, easy to use combination of tools that allow you to automatically (or manually) generate Wordpress posts with custom metadata.</p>
<p><strong>Create Wordpress posts from your Airtable data.</strong></p>
<p><img src="assets/Untitled.png" alt="Untitled"></p>
<h2 id="install-the-wordpress-plugin">Install the Wordpress Plugin</h2>
<p>The Wordpress Connector has two parts, a Wordpress plugin that pulls your Airtable data into wordpress, and an API tool to connect with airtable <a href="https://tools.aeropage.io/api-connector/dashboard">here</a>.</p>
<p>To connect to wordpress, use the <a href="https://wordpress.org/plugins/aeropage-sync-for-airtable/">Aeropage Sync for Airtable</a> plugin. You can find it in the Wordpress directory by searching for "<strong>Aeropage</strong>".</p>
<p><img src="assets/Untitled%201.png" alt="Untitled"></p>
<h2 id="add-a-custom-post-type">Add a Custom Post Type</h2>
<p>Once the plugin is installed, just open it from the sidebar and "Add Post".</p>
<p><img src="assets/Untitled%202.png" alt="Untitled"></p>
<p><img src="assets/Untitled%203.png" alt="Untitled"></p>
<p><strong>After clicking "Add a Post" you will be shown a form.</strong></p>
<p><img src="assets/Untitled%204.png" alt="Untitled"></p>
<p><strong>Title</strong></p>
<p>The title of the custom post type will usually be the same name as the table you"re importing data from in Airtable.</p>
<p><strong>Dynamic URL</strong> </p>
<p>The dynamic url can be "SEO" friendly by using the name eg "shure-headphones" or it can be easier to manage potential duplicate names by using the unique id of each record.</p>
<p><strong>API Token</strong></p>
<p><strong>Continue to the next steps</strong> to create a token to use with the api connector.</p>
<p><strong>Auto Sync</strong></p>
<p>A cron job that is executed in an hourly interval which is based on Wordpress Cron implementation. WordPress Cron is WordPress's task scheduler that runs on site visits to handle tasks like publishing posts and checking for updates. </p>
<p><b>Wordpress Cron, and to an extent auto sync, relies on site traffic, so on low-traffic sites, tasks may not run on time.</b></p>
<p>If you have an access to the hosting server settings, you can update it like so:</p>
<p>Disable WordPress Cron in wp-config.php:</p>
<code>define('DISABLE_WP_CRON', true);</code>
<p>Set up a server cron job to run wp_cron.php every hour:</p>
<code>*/5 * * * * curl http://example.com/wp-cron.php?doing_wp_cron</code>
<p>When submitted this will create a custom post type and automatically add a new post for every record in your connected Airtable</p>
<p><strong>To complete the form you will need an API token (below)...</strong></p>
<h2 id="create-api-connector-token">Create API Connector &amp; Token</h2>
<p>The api connector stores your connection info and prepares the data in the response to be used in Wordpress.</p>
<h3 id="connect-to-airtable">Connect to Airtable</h3>
<p>After clicking new and creating your project, copy and paste the url to the airtable data you want to connect. To find out more details on this step, click on the instructions button.</p>
<p><img src="assets/Untitled%205.png" alt="Untitled"></p>
<p><img src="assets/Untitled%206.png" alt="Untitled"></p>
<h2 id="post-data-fields-metadata-">Post Data &amp; Fields (MetaData)</h2>
<p>you can create dynamic values for your posts when they appear in "loops" on your Wordpress archive pages, query and loop templates</p>
<p><strong>Post Title</strong></p>
<p>If you want to make a custom title, instead of using the default name of the records.</p>
<p><strong>Post Image</strong></p>
<p>The image will be automatically downloaded to your wordpress media library.</p>
<p><strong>Post Excerpt</strong></p>
<p>A short description of the post content.</p>
<p><strong>Fields (Metadata)</strong></p>
<p>By default, every field in your airtable record will be created as post metadata. If you want to prevent some data from being synced,  click the field settings and toggle off the ones you want to exclude.</p>
<p><strong>Attachment Proxy</strong></p>
<p>Replaces temporary links in your attachment fields with permanent urls.</p>
<aside>
ðŸ’¡ After making changes, you need to refresh the data to see the result.

</aside>

<p><img src="assets/Untitled%207.png" alt="Untitled"></p>
<p><img src="assets/Untitled%208.png" alt="Untitled"></p>
<h2 id="posts-preview">Posts Preview</h2>
<p>You can see a preview of how your posts would look in a Wordpress loop by click on the "posts" view. This can be used to confirm the Post Title, Image and Excerpt are correct before you syncronize with Wordpress.</p>
<p><img src="assets/Untitled%209.png" alt="Untitled"></p>
<h2 id="syncronizing-with-airtable">Syncronizing with Airtable</h2>
<p>Once your data is setup in Aeropageâ€¦</p>
<ul>
<li>Open the settings.</li>
<li>Click on the "token" field to copy it.</li>
<li>Go back to Wordrpress plugin, "create post" page.</li>
<li>Paste your token into the API Token field.</li>
</ul>
<aside>
ðŸ’¡ Auto sync can be toggled on to check for new and changed data.

</aside>

<p><img src="assets/Untitled%2010.png" alt="Untitled"></p>
<p><img src="assets/Untitled%2011.png" alt="Untitled"></p>
<p><strong>After a few seconds, you should see your data appear in the right - and a Success message.</strong></p>
<p>You are now ready to save the post, which will syncronize you posts for the first time.  After syncronizing your custom post will appear in the Wordpress menu, and a post will have been added for each record in your Airtable data.</p>
<aside>
ðŸ’¡ The first time you sync it can take longer as it"s downloading your featured images.

</aside>

<p><img src="assets/Untitled%2012.png" alt="Untitled"></p>
<p>You should also see the featured images for each post in your media library.</p>
<p><img src="assets/Untitled%2013.png" alt="Untitled"></p>
<h1 id="finished">Finished</h1>
<p>You can now use your custom post to make queries and templates â†’ also use custom post meta data, for any of the fields in your Airtable data  when making Single Post templates. The custom posts and metadata should work with all your favorite builders. </p>
<p><img src="assets/Untitled%2014.png" alt="Untitled"></p>
<p><strong>Gutenberg</strong></p>
<ul>
<li>Create loops using the "Query Loop" Block</li>
<li>Make templates for your single posts.</li>
</ul>
<p><strong>Elementor</strong></p>
<ul>
<li>Create loops using the "Loop Template" widget.</li>
<li>Make templates for your single posts.</li>
</ul>
<p><strong>Divi</strong></p>
<ul>
<li>Refer to documentation for how to make loops with custom posts and metadata.</li>
</ul>
<h3 id="-update-your-data-"><strong>Update your Data</strong></h3>
<p>To manually resync your data, make changes or delete - just click the icons below.</p>
<p><img src="assets/Untitled%2015.png" alt="Make changes to the configuration"></p>
<p>Make changes to the configuration</p>
<p><img src="assets/Untitled%2016.png" alt="Resyncronize your data manually."></p>
<p>Resyncronize your data manually.</p>
<p><strong>If you have auto sync toggled on, your posts will be updated automatically after running the cron job.</strong></p>


  
=== Installation ===
  
1. Upload the plugin folder to your /wp-content/plugins/ folder.
2. Go to the **Plugins** page and activate the plugin.
3. Create an API connector for Airtable on tools.aeropage.io.
4. Setup your custom post type, and sync your Airtable data to WordPress.
  
== Frequently Asked Questions ==


== Upgrade Notice ==



=== Screenshots ===

1. Main plugin panel
2. Adding a Post. 

=== Changelog ===
3.2.0
* Added Support for wordpress connector from Aeropage Builder

3.1.4
* Fix for the tags not added to the post

3.1.3
* General bug fixes and improved error handling

3.1.2
* General bug fixes

3.1.1
* Updates to the Auto Sync interval

3.1.0
* Sync by batch to avoid the 504 errors

3.0.0 
* Mapping an Aeropage project to a post type 
* Mapping Airtable Fields to a registered post meta and ACF support 
* Setting an Airtable field as Post Content 
* HTML support in Airtable fields for WYSIWYG editors

2.0.3
* Fixed bugs
* UI Improvements

2.0.2
* Fixed bugs
* Added a new flow for syncing
* UI Improvements

2.0.1
* Bug fixes

2.0.0
* New version released
* Added a method for letting users select airtable fields for categories

1.3.1
* Fixed the links for the tools website.

1.3.0
* Added View Record menu item in the admin bar
* UI changes and i mprovements

1.2.3
* Fixed the plugin import issue
* fixed issues reported for syncing and image upload to wordpress

1.2.2
* Fixed the image upload issue

1.2.1
* Fixed sync issues
* Fixed the links

1.2.0
* Added resyncing data in the admin bar
* Added a post status option in the Add/Edit Pages
* Added support for opening the Airtable URL of an Aeropage in a separate tab
* UI Improvements

1.1.0
* Added support for feature image downloading from airtable

1.0.4
* UI fixed and improvements

1.0.3
* Sync fixes and UI improvements

1.0.2
* UI improvements to the admin bar

1.0.1
* General bug fixes

1.0
* Plugin released. 