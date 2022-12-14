# Admin-Form-Pro
Manages database tables in the Wordpress Admin panel 

The pro version of the plugin for wordpress [admin-form](https://wordpress.org/plugins/admin-form/).
To work on the site, the admin-form downloaded from the official wordpress repository must be installed!

# Installation
Download the plugin, if it's in a compressed folder unzip first. 
The plugin must be placed inside yoursite/wp-content/plugins.

# The pro version adds:
- The calculated fields
- The lookups
- A system for managing tables through queries
- The ability to create query forms (LAB).
- Advanced management in the creation of tables
- Import and export of data in mysql / csv.
- Import export data of a specific form.

# changelog

### V.1.5.0 2023-01-08 Metadata support
- NEW Inside the template engine, when a variable is extracted from the data of a list, the VAL attribute has been added.
the extracted variables return the processed values. If this attribute is used, the variable returns the original value saved in the database.
- NEW: In the page where the form is configured, the possibility of sorting the order in which to show multiple tables has been added
- NEW: Special handling for metadata has been developed. In the setting screen it is possible to select the metadata table linked to the main table. If this is selected in the form configuration page, an Add metadata button will appear with which it will be possible to add new custom fields.
- FIXBUG: inherited the single checkbox was not passing the value
- FIXBUG: on lists with multiple tables the system that inherits the configuration on the list doesn't seem to work.
- FIXBUG: (TAB FORM) change styles doesn't work correctly
- FIXBUG The query aliases of a new FORM must always be quoted
- IMPROVEMENT: On the autocomplete popup added the handling of the ESC key.

### V.1.4.0 2022-12-15 - Clone list
- NEW: (list-all) Clone list: it is now possible to clone a list.
- NEW: (Browse-list) The ability to clone a record has been added to lists.
- NEW: Added function ADFO::get_clone_detail(); Returns the data to clone a record
- NEW: (PRO list all tables) Add Clone table action
- NEW: (PRO) Now it is also possible to update the PRO version directly from the plugins page
- IMPROVEMENT: (list view formatting) Add checkbox Keeps settings aligned with information from the same field in tab 'Form'. 
- FIXBUG: notice in list_browse (wpdb->prepare)
- FIXBUG: (dropdown filter) checboxes with comma 
- FIXBUG: remove sanitize in get_request
- FIXBUG: (admin-list) Remove pagination html after bulk actions.
- FIXBUG: (dropdown filter) Results were not corrected when filtering by operators: '> < >= <='
- FIXBUG: The lookup filters with the column filters didn't work together. With search the drop down menu was not working properly
- FIXBUG: Did not show title and description of table attributes when managed table is only one.

### V.1.3.0 2022-12-05 IMPORT/EXPORT
- NEW: [Only for PRO]: A system for importing data from a form. Now you can export, edit and re-import your data. Before importing the data, these are verified using a check system. 
- IMPROVEMENT (database browse query) [Only for PRO]: Added 'show all text' checkbox in broswer data
- IMPROVEMENT (Code) ADFO:get_detail added raw_data:boolean parameter
- IMPROVEMENT (Code) ADFO:get_data added the raw type return that is used to save the query
- IMPROVEMENT (Code) ADFO:save_data if all fields are null or empty, but id is there i added delete row action.
- FIXBUG: The plugin miscounted the total number of records when the group by was present
- FIXBUG: On import it does not recalculate CALCULATED FIELD type fields

### V.1.2.0 2022-11-23 NEW FIELDS
- FIXBUG: saving text remove html.
- FIXBUG: (database_press) button create list from query, the list it creates doesn't work!
- IMPROVEMENT: new hooks to add new functionality when creating a new form.
- FIXBUG [Only for PRO] (list-browse) csv download didn't work.
- REFACTOR: renamed the other hooks and created the legacy for the old hooks
- IMPROVEMENT: Possibility to use the database in the Pro version even if the admin form is not installed
- NEW: new fields: color picker and range
- DOCS: list_view_formatting.

### V.1.1.0 2022-11-17
- FIXBUG: (list-form) When I create a new field the autoincrement doesn't appear
- NEW: (list-form) new field: field email, link
- NEW: (list-form) when you create a field it also creates a default in the list view. In lookups it doesn't create the additional field and it doesn't hide the main one
- FIX MAJOR BUG: (list-structure) If I save a lookup without primary the query gives an error.
- IMPROVEMENT: Documentation of field types
- FIXBUG: the namespaces referring to object verification were wrong. Discovered on calculated field
- FIXBUG: (list-form) When I create a new field the special fields of the pro version don't appear
- FIXBUG: (browse-list) the change values ??????set in the list structure are not displayed when updating a record!
- FIXBUG: if I remove a field in the list-form it doesn't remove the field(s) of list-view-formatting!
- FIXBUG: on list-structure custom column has label column type instead of custom code
- IMPROVEMENT: in the column type list view (Show checkbox values) I divided the various results with a graphic element.

### V.1.0.1 2022-11-12
- FIXBUG: list description.
- FIXBUG: show php error.
