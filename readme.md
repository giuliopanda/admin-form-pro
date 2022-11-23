# Admin-Form-Pro
Manages database tables in the Wordpress Admin panel 

The pro version of the plugin for wordpress [admin-form](https://wordpress.org/plugins/admin-form/).
To work on the site, the admin-form downloaded from the official wordpress repository must be installed!

# Installation
Download the plugin, if it's in a compressed folder unzip first. The plugin must be placed inside yoursite/wp-content/plugins

# The pro version adds:
- The calculated fields
- The lookups
- A system for managing tables through queries
- The ability to create query forms (LAB).
- Advanced management in the creation of tables
- Import and export of data in mysql / csv.

# changelog

V.1.2.0 2022-11-23
FIXBUG: saving text remove html.
FIXBUG: (database_press) button create list from query, the list it creates doesn't work!
IMPROVEMENT: new hooks to add new functionality when creating a new form.
FIXBUG [PRO] (list-browse) csv download didn't work.
REFACTOR: renamed the other hooks and created the legacy for the old hooks
IMPROVEMENT: Possibility to use the database in the Pro version even if the admin form is not installed
FEAT: new fields: color picker and range
DOCS: list_view_formatting.

V.1.1.0 2022-11-17
FIXBUG: (list-form) When I create a new field the autoincrement doesn't appear
FEAT: (list-form) new field: field email, link
FEAT: (list-form) when you create a field it also creates a default in the list view. In lookups it doesn't create the additional field and it doesn't hide the main one
FIX MAJOR BUG: (list-structure) If I save a lookup without primary the query gives an error.
IMPROVEMENT: Documentation of field types
FIXBUG: the namespaces referring to object verification were wrong. Discovered on calculated field
FIXBUG: (list-form) When I create a new field the special fields of the pro version don't appear
FIXBUG: (browse-list) the change values ​​set in the list structure are not displayed when updating a record!
FIXBUG: if I remove a field in the list-form it doesn't remove the field(s) of list-view-formatting!
FIXBUG: on list-structure custom column has label column type instead of custom code
IMPROVEMENT: in the column type list view (Show checkbox values) I divided the various results with a graphic element.

V.1.0.1 2022-11-12
FIXBUG: list description.
FIXBUG: show php error.
