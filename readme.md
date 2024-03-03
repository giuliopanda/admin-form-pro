# Admin-Form-Pro
Admin form pro extends the capabilities of the wordpress Admin form plugin by adding advanced database management. It's free and open source.

Take control of your wordpress database

![filter](https://github.com/giuliopanda/repo/blob/main/database_press_screenshot03.png)

Admin form pro allows you to manage your data quickly and efficiently, with functionality similar to PhpMyAdmin, but with some advanced features. In particular, it gives you the ability to filter tables by column, just like in Excel, allowing you to quickly find the data you are looking for.

Thanks to its intuitive and easy-to-use interface, you can easily manage information in several tables at once and also edit data from multiple tables at once. 

# Installation

This is the PRO version of the plugin for wordpress [admin-form](https://wordpress.org/plugins/admin-form/).
To work on the site, the admin-form downloaded from the official wordpress repository must be installed!


# The pro version adds:
- [The calculated fields](https://github.com/giuliopanda/admin-form-pro/wiki/Calculated-fields).
- [The lookups](https://github.com/giuliopanda/admin-form-pro/wiki/LOOKUP)
- A system for managing tables through queries
- The ability to create query forms (LAB).
- Advanced management in the creation of tables
- [Import and export of data in mysql / csv](https://github.com/giuliopanda/admin-form-pro/wiki/Import-Export)
- Import export data of a specific form.

# changelog

### 1.9.0 2023-03-08 Fixbug
* Compatibility with ADMIN-FORM 1.9.0
* NEW: ADD the possibility to insert multiple values in the lookup field
* IMPROVEMENT: Left join autocompletes now show only the actual values i.e. metavalues only show the values of the chosen metakey.
* FIXBUG: On select with left join if there are columns with the same name the query freezes and is annoying
* FIXBUG: on downloads it does not export the names of the columns taken from the list.

### 1.8.1 Fixbug 2023-03-08 Fixbug
* FIXBUG: (create list from query) fatal error in php 8.1+

### 1.8.0 Fixbug 2023-03-08 Fixbug
* IMPROVEMENT: Improved page loading performance. The query to calculate the total number of records has been optimized and other queries have been eliminated.
* BUGFIX PRO: Always appear show all text even when query gives error.
* BUGFIX PRO: If a query gave error the double editor would appear!
* BUGFIX PRO: List export and subsequent import used to fail for complex queries or some types of fields, especially calculated fields which are now added to RAW export. 


### 1.7.0 2023-02-16 Fxibug
- FIXBUG: enabled multiqueries in the input form even for queries other than select
- FIXBUG: show full text appeared even when there were no results
- FIXBUG: when the query gave error happened that the editor appeared double
- IMPROVEMENT: Improved the response performance of a query by decreasing the instances of recalculating the total results extracted.


### 1.6.2 2023-02-09 Fixbug
- COMMIT Error
### 1.6.1 2023-02-09 Fixbug
- FIXBUG: When you edit a query and then cancel, it still runs the edited query
- FIXBUG: On SELECT DISTINCT queries add table ids showing inconsistent results

### 1.6.0 2023-01-22 Fixbug
- NEW: Added Time field and template engine attributes: timenum-to-timestr, timestr-to-timenum and time-to-hour
- NEW (PRO) in the datapress main page added a box that shows the running queries and allows you to remove them
- FIXBUG (PRO) show table in import data list 
- IMPROVEMENT: Limited metadata function. Metadata must be unique. There can be only one metadata for each meta_key. During the save if I find more metadata for the same key I delete them.
- FIXBUG Added checking on date fields when they are invalid.
- IMPROVEMENT: (TAB FORM) option in select: if there are no values I set them equal to the labels
- IMPROVEMENT: Changed the default setting of the frontend of the tables.
- IMPROVEMENT: Rebuild of post and user fields on lists. They now display the post title or the user's nicename. Search now works on user title or name instead of IDs. However, it is not possible to sort the list by a post or user field. This will not be developed because if you change the data type of the list the query will give an error.
- FIXBUG: (page action=list-sql-edit) When I save it hides the primary keys in the lists.
- FIXBUG: on import for user post and lookup fields
- FIXBUG FRONTEND when you press enter and you have the focus on the search even if it is in ajax it sends the data in get
- IMPROVEMENT: (LIST view formatting) On post and user type columns I no longer choose which information to show, it's always the title or username, but I've added the option to automatically create the link to the article or author's page. Attention this change generates a small incompatibility with previous versions. If you are using the post or user field check out the new functionality. If you wanted to link to other fields, use the lookup field instead.
- IMPROVEMENT: (TAB FORM) the test of the calculated fields does not work with data which however works in production. Added an information box if using [%data.
- BUG: (TAB FORM) When you created a new field it didn't allow dragging it to sort.

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
- FIXBUG: (browse-list) the change values ​​set in the list structure are not displayed when updating a record!
- FIXBUG: if I remove a field in the list-form it doesn't remove the field(s) of list-view-formatting!
- FIXBUG: on list-structure custom column has label column type instead of custom code
- IMPROVEMENT: in the column type list view (Show checkbox values) I divided the various results with a graphic element.

### V.1.0.1 2022-11-12
- FIXBUG: list description.
- FIXBUG: show php error.
