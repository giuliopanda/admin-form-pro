=== Database Press ===
Contributors: pandag
Donate link: https://www.paypal.com/donate/?cmd=_donations&business=giuliopanda%40gmail.com&item_name=wordpress+plugin+databasepress
Tags: database
Requires at least: 5.9
Tested up to: 6.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.0.0

DB press is designed to manage MySQL tables.

== Description ==
If you want to manage mysql tables quickly and don't have access to phpmyadmin.

### How does it work
The plugin is meant to help you build queries or to filter data quickly.

### Database management:
- Browse and edit table data
- Autocomplete on entries
- Ability to enter data on multiple tables at the same time
- Simplified metadata management
- utility for writing queries
- Advanced search filters
- Create new tables
- Edit tables
- Check for any data loss when alter table
- A simple filter management system
- Import export data in sql and csv.
- Import test in temporary tables.
- Import insert/update with csv.
- search and replace with serialized data support

== Installation == 
Upload plugin-name.php to the /wp-content/plugins/ directory, or install from the Plugin browser
Activate the plugin through the ‘Plugins’ menu in WordPress


Attention: the plugin is opensource and I take no responsibility for any bugs. Before any operation done on the database make a backup


= 2.0.0 - 2022-09-19 =
- Notes:
  - I have removed all the part of saving the forms and publishing on the frontend and limited the functionality to administrative users only.

= 1.0.0 - 2022-09-08 =
- Notes: 
  - Changed the name from database_tables to database_press.
  - Changed the lookup params, now they no longer connect to lists, but to tables. Now you can sort the columns created by lookup.
- Fixbug: Warnings & notice.
- fixbug: show and hide the save and delete buttons in the edit form. In the list it shows Edit or view if the single row is editable or not.
- Improvement in the form tab the management of module_type and what is visible and what is not.
- Added popup to rate the plugin.
- Improvement: Added search field for tables and lists in the sidebar.
- Removed: Removed the page with the list of tables and fields from the guide.
- feat: Added light style to tables
- feat: Template engine added decode_ids attribute
- doc: Tutorial_02 written
- improved: The detail view of the forntend.
- Improved: The [^ image shortcode in the template engine and added the image_size = winfit attribute.


= 0.9.1 - 2022-08-19 =
- Fixbug: Warning in import sql
- Fixbug: slow codemirror when copying and pasting long texts.
- Fixbug: Warning in class-dbp-list-admin.php on line 670
- improvement: On the 'organize columns' added the title to read the field if it is too long
- Fixbug: sorting form with new columns
- Fixbug list view formatting does not allow to change the ID
- Improvement: on merge query removed join type and the explanations have been simplified

= 0.9.0 - 2022-08-17 =
- Note: the template engine logic has changed so I cannot guarantee compatibility with previous versions.
Previously the template engine would delete a shortcode if it couldn't find it. This created a problem with regular expression like \[^/\] that the template engine recognized as shortcode and deleted them. Now if it doesn't find the shortcode it prints it as is. The verification test is in pina-test.php.
- Improvement: The css of the column sizes have been changed. This can lead to incompatibility with version v0.8. To correct the problem just go to the list and save list view formatting again.
- Feature: Search & replace. Added search on all fields in lists and the ability to search & replace in queries (not in lists!).
- Improvement: improved search filters.
- Improvement: Added option to align fields in frontend tables.
- Fixbug: clean warning & notice
- Improvement: improved the
sidebar navigation and added collapse option
- Improvement: In 'List view formatting' added the 'Choose column to show' button. This button gives the possibility to change the query select and add or remove columns.
- Fixbug: Tips for tables that have no primary key.
- Fixbug: Restored the primary key icon in the query results view.
- Improvement: Added an alert after you modify queries with Organize columns, Merge
- Improvement: On list browse, if the content editing window is open, it does not allow you to open the column search filters!
- Removed: Deleted the checkbox to display the primary key in the Add meta data
- Fixbug: Instead of showing the query of an update it shows the select of a '_transient'
- Note: The default editor is disabled in the form if the user has selected in his profile: Disable the visual editor when writing
- Rebuild: the delete system from sql.
- Improvement: Removed the choice of query type when creating a list from query
- Fixbug:frontend view Show if no longer worked.

= 0.8.1 - 2022-07-29 =
- Fixbug: The codeMirror did not appear on all wordpress configurations.

= 0.8.0 - 2022-07-29 =
- Improvement: Changed the page titles of the "list view formatting" and added the column types User, Post and Link popup detail.
- Improvement: Removed ids added secretly in browse table queries
- Fixbug: In the table Structure page when you changed the column to primary key, you could select multiple primary keys and sometimes the select disappeared.
- Fixbug: Form: I create a form with a required field. I open the modification of the contents and save leaving the mandatory field not filled in. The form disappears, but does not reappear with the error message.
- Improvement: Management of the decimal field.
- Fixbug: Test import creation of temporary table with correct ids
- Fixbug: List view formatting in showing titles and field types.


= 0.7.0 - 2022-07-24 =
- Improved help and translation
- Created a new class  Dbp_render_list in place of html-table-frontend. This made it possible to manage pagination and search in lists with more flexibility.
- On frontend> list type editor it is now possible to add shortcode in the template engine
[% html.pagination], [% html.search].
- Added dbp_frontend_get_list filter: It allows you to redesign the display of a list in php.
- fixbug: no longer save data due to _default_alias_table in default value
- figbug: Not all primary key columns appeared in the frontend tables
- Improved the management of multiple tables in the frontend through the addition of the prefix parameter. Now you can use filters and pagination on multiple tables within the same page.
- Added in the template engine: admin_url and counter.