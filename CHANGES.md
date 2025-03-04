#### 2.2.5 / 2025-01-08

* __Zukit__ updated to version 2.0.1
* update outdated npm packages
* add __Prettier__ and its config
* replace __ESLint__ config with v9
* add `markdownlint` and fix `.md` files
* add `wp-scripts` build and webpack configs for it
* migrate SASS files to avoid deprecation warnings
* refactor JS  with `lodash-es` and prettier rules
* replace wordpress import from `const` to `import`
* fix PHP formatting and some errors found by `intelephense`
* update `gitattributes` ignore

#### 2.2.3 / 2022-03-07

* __Zukit__ updated to version 1.5.5
* added `instant_caching` option for `zu_PlusDebug` addon
* fix initial option bug

#### 2.2.1 / 2022-02-15

* hide non-implemented options on `Settings Page`
* added `more_actions` option to register internal methods that should be called on `init`
* added `Obsolete Taxonomy` debug action

#### 2.2.0 / 2022-01-30

* implemented __load Zu Plus first__ approach
* added option to log the current order of all __acivated__ plugins
* refactoring `Framework Info` table to support CSS Grid and `Zu Debug` plugin
* added `disable_admenu` option
* use new keys (refactored) for `custom_admin_menu` options
* __Zukit__ updated to version 1.5.1
* remove `/` for division because is deprecated and will be removed in Dart Sass 2.0.0
* fixed bug when `config_singleton` name was changed to `singleton_config`
* fixed KINT output for `conditionally logged` records
* fixed bug with `htmlentities` in context when `Kint` used
* fixed bug when the repo is unavailable or private
* replaced jQuery deprecated `click` function

#### 2.1.1 / 2021-08-10

* for the logging functions, the `classname_only` option is implemented, which allows you to display only the class name without properties and methods - which speeds up logging and reduces the amount of logs
* fixed bug with `zu_logc` in KINT mode
* always load __Zukit__ even if we do not use it later
* changed loading order for Zukit
* __Zukit__ updated to version 1.3.0
* improved support `zu_log_if` function
* fixed badges
* improved context replacement
* fixed bug with `htmlentities` in context string

#### 2.1.0 / 2021-05-03

* first stable version after refactoring (`shortcode cache` and `cookie notice` not restored yet)
* added support for GitHub data for `Core Info` panel
* __Zukit__ updated to version 1.2.2
* according to the changes in __Zukit__, rows in `Plugin Info` are now hidden through the value equal to __null__
* min `php` version updated
* refactored version of `duplicate`
* fixed bug with `overwrite` check
* cleaning and css improvements

#### 2.0.3 / 2021-04-12

* addded Zukit `Core Info` panel
* admin styles are divided into common and for the Settings Page
* implemented supports log clearing now

#### 2.0.2 / 2021-04-08

* plugin description updated
* implemented `Output menu order` debug option
* re-implemented the functionality of duplicating the menu
* improved log `location` methods
* css improvements

#### 2.0.1 / 2021-04-01

* added custom colors for Settings Page
* updated `Kint` phar
* restored support of `remove_autosave` option
* added `avoid_ajax` debug option
* fixed css styles for `<pre>`
* improved Kint support
* added support for `dump method`
* small improvements

#### 2.0.0 / 2021-03-29

* first working version after refactoring (not perfect)
* updated `Kint` to ver 3.3
* simple __Settings Page__ re-implemented
* refactoring to use __Zukit__

---

&#x274C;  __Attention!__ Breaking changes in version 2.0.0

---

#### 1.4.8 / 2020-04-20

* refactoring `enqueue_style` and `enqueue_script` for Admin
* !!changed order of args for `enqueue_script` to support `wp_localize_script`
* bug fixing and minor improvements

#### 1.4.7 / 2020-03-26

* added `admin_extend_localize_data` function and now the localized data wrapped inside an array
* added support for `remove_autosave` option

#### 1.4.6 / 2020-02-28

* added `get_post_gallery_blocks()` function to check `gallery` blocks in posts and pages
* improved `get_post_gallery()` function to checks blocks as well (before were only shortcodes)

#### 1.4.5 / 2020-02-03

* fixed compatibility with Wordpress 5.3
* added `Kint` support & integration with `Query Monitor`
* added Kint theme for Query QueryMonitor
* added `_dbug` function to use with Kint
* small fixes

#### 1.4.4 / 2018-07-03

* improved check for `$attachment_id` in `get_attachment_id()` function

#### 1.4.3 / 2018-06-16

* basic language functionality was ported from `Translate+` plugin to avoid plugins loading dependency
* to prevent dependency on plugins loading `zuplus_loaded` action is initiated after `plugins_loaded`

#### 1.4.2 / 2018-06-16

* finished `ZU_CookieNotice` addon
* added `textarea()` and `set_if_empty()` functions to `zuplus_Form`
* improved css

#### 1.4.1 / 2018-06-14

* refactored `zuplus_Addon` to provide `defaults` and `novalidate` (and support metabox printing)
* added `keys_values()`, `get_form()` and `get_form_value()` functions to `zuplus_Addon`
* refactored `zuplus_Admin` to support `novalidate` options
* added `create_addon()` function to create and register addons (`zuplus_Plugin` and `zuplus_Admin` classes)
* added `options_restore` option to restore defaults after meta box was switched off
* added `maybe_restore_defaults()` function to implement check and restore if needed
* added `preprocess_defaults()` function which include processing defaults, novalidate and restored options
* refactored `options_defaults()` function
* fixed bug for `hidden` filed type
* added `$ajax_value` to all `button_link` functions
* refactored `ZU_Debug`, `ZU_CookieNotice` and `ZU_DuplicatePage` to support defaults and metabox printing
* created `Debug Options` metabox
* supported `zu_update_defaults` filter to modify theme options
* added `is_debug()` function
* added `zuplus_revoke_cookie` ajax call
* added `Revoke Cookie` button to theme actions
* refactored `set_option()` function in `zu()` namespace
* improved JS

#### 1.3.8 / 2018-06-09

* improved css
* fixed some bugs

#### 1.3.7 / 2018-05-27

* improved JS to add spinner for all AJAX actions

#### 1.3.6 / 2018-05-27

* added `Reset All Cached Shortcodes` action

#### 1.3.5 / 2018-05-26

* changed `$_split_index` global var value to `privacy.php` index (before was `options-permalink.php`)
* added `HTML beautifier`
* added `debug_cache` option to log calls of `cache` functions
* added `output_html` option to convert HTML entity equivalents into these entities
* added `beautify_html` option to HTML beautifier if logged var contains HTML

#### 1.3.4 / 2018-05-23

* improved css

#### 1.3.3 / 2018-05-16

* fixed some bugs in submenu custom order

#### 1.3.2 / 2018-05-16

* changed cache time for 12 hours (recommended)
* improved css

#### 1.3.1 / 2018-05-10

* moved `enqueue_style_or_script()` function to `zuplus_Plugin` from `zuplus_Addon`
* added some wrapper functions to support these changes

#### 1.2.9 / 2018-05-05

* added basic cache functions
* added `minify_html()` function
* improved performance of `set_random_featured_attachment_id()` function

#### 1.2.5 / 2018-04-30

* fixed bug with `use_backtrace` option

#### 1.2.4 / 2018-04-29

* fixed bug in custom menu order
* added `$_split_index` global var to indicate split position in submenu
* changed access for custom menu order functions (`protected` from `private`)
* improved `add_body_class()`, `add_admin_body_class()` and `merge_classes()` functions to avoid class duplicates
* added suport for `.zu-file-tree` in JS
* added `current_timestamp()` function to `zuplus_Plugin` and `zuplus_Addon`

#### 1.2.1 / 2018-04-28

* css improvements
* added `button link_with help()` function to available form fields
* debug file output improved

#### 1.2.0 / 2018-04-27

* debug file output improved
* improvement in css
* fixed errors with `zuplus_dismiss_error`
* improved `zuplus_turn_option()` js function to support `ajax_value` and `confirm` options

#### 1.1.8 / 2018-04-15

* added `$prefix` argument to `add_body_class()` function
* added `$skip_attachments` argument to functions which get top ancestor

#### 1.1.6 / 2018-04-13

* added `write_to_file` option to disable file logging

#### 1.1.5 / 2018-04-12

* added `debug_backtrace` option to disable automatic inclusion of backtrace (sometimes leads to memory exhausted)

#### 1.1.4 / 2018-04-11

* added function `get_all_languages()` (improved)

#### 1.1.2 / 2018-04-10

* added language functions (ported from `zu` theme)

#### 1.1.1 / 2018-03-31

* added functions `register_addon()` and `clean_addons()`
* addons clean is now supported in `deactivation_hook` of plugin
* added function `print_option()` to `zuplus_Addon`

#### 1.1.0 / 2018-03-31

* added functions `update_options()` to `zuplus_Plugin` and `zuplus_Addon`

#### 1.0.2 / 2018-03-25

* added functions `cut_content()` and `modify_content()`
* fixed bugs in `ZU_DuplicatePage`
* improved css

#### 1.0.0 / 2018-03-25

* added function `option_value()` to `zuplus_Addon`
* class `ZU_DuplicatePage` added (based on Duplicate Page Plugin)
* added options for Duplicate Page functionality (`dup_page`, `dup_status`, `dup_redirect`, `dup_suffix`)
* inherited `validate_options()` to bypass validation of select
* added `print_duplicate_page()` to display Duplicate Page options
* class `zuplus_Duplicate` was renamed for `ZU_DuplicateMenu` to avoid confusion

#### 0.9.12 / 2018-03-20

* added function `submenu_move` to move section of submenu to a new position

#### 0.9.11 / 2018-03-19

* added functions to modify admin menu and submenu items (reorder, rename, remove, separator)
* modified order for `Media`, `Posts` and `Genesis` menu
* removed advertising menu for some plugins
* bug fixed in `get_submenu_index`

#### 0.9.7 / 2018-03-15

* added functions `deactivation_clean()` for *zuplus_Admin* and `clean()` for *zuplus_Addon* which should be called in deactivation

#### 0.9.6 / 2018-03-15

* updated css for tables

#### 0.9.5 / 2018-03-14

* improvement in css

#### 0.9.3 / 2018-02-27

* fixed bug in function `get_top_ancestor_slug`

#### 0.9.2 / 2018-02-27

* added function`get_featured_from_posts`
* added support for  `-1` in function `get_featured_attachment_id`

#### 0.9.1 / 2018-01-06

* added support for  `sys-debug`: see file in `/includes/debug/sys-debug.php` for more instructions

#### 0.8.9 / 2017-10-16

* option `use_var_dump` was added to all interfaces
* function `_dbug_dump` added
* bug fixing

#### 0.8.8 / 2017-10-16

* class `ZU_Debug_Sys` added

#### 0.8.7 / 2017-10-14

* added functions `config_addon()` and `admin_enqueue_fonts()`
* bug fixing

#### 0.8.6 / 2017-10-07

* "Dominant Color" now used from `Media+` function

#### 0.8.5 / 2017-10-06

* AJAX spinner added
* added functions `check_config()` and `get_config()`
* added `ZUDEBUG` which activate "filetime" for JS and CSS files
* improvement in css

#### 0.8.3 / 2017-10-04

* AJAX prefix was changed

#### 0.8.1 / 2017-10-03

* changed logic in meta boxes creation
* added functions `config_addon()`, `meta_boxes_more()` and `construct_more()`
* css updated (remove plugin advertisements)
* improvement in classes

#### 0.7.6 / 2017-09-30

* bug fixing

#### 0.7.5 / 2017-09-28

* added functions `is_child()` and `is_child_of_slug()`

#### 0.7.4 / 2017-09-27

* bug fixing

#### 0.7.2 / 2017-09-23

* added dbug trace for `Query Monitor`
* improvement in function `fix_content()`

#### 0.7.0 / 2017-09-22

* bug fixed for metaboxes when user does not have the capability required

#### 0.6.8 / 2017-09-20

* added option `custom` for `get_svgcurve()` function

#### 0.6.7 / 2017-09-17

* improvement in `filter trace()` function

#### 0.6.6 / 2017-09-16

* modified singleton of class `zuplus_Plugin` to be extended in child classes
* fixed bug with plugin `prefix` in template
* fixed bug in `print_status()`
* added functions `option_value()` and `default_value()`

#### 0.6.0 / 2017-09-16

* added functions `array_prefix_keys()` and `check_option()`
* modified class `ZU_PlusRepeaters` to extract variables from `args`
* added some functionality to `zuplus_Plugin`

#### 0.5.9 / 2017-09-12

* remove `GITHUB_UPDATER_OVERRIDE_DOT_ORG` setting

#### 0.5.8 / 2017-09-07

* `landscape_only` for featured background
* functionality to duplicate menu ported from `Translate+` plugin

#### 0.5.6 / 2017-09-07

* bug fixing
* function `get_post_gallery()` ported from `Media+` plugin
* `GITHUB_UPDATER_OVERRIDE_DOT_ORG` define added
* initial commit for GitHub

#### 0.5.2 / 2017-09-05

* ported some functions from `ZU`
* split class `ZU_PlusFunctions` with Traits

#### 0.4.0 / 2017-09-05

* added trace location for all `_dbug*()` calls
* added `write_trace()` function

#### 0.3.2 / 2017-09-04

* added support for `zuplus-debug`
* added log statistics

#### 0.3.0 / 2017-09-04

* first working version
* added check for `ZU+` be activated for all plugins

#### 0.2.0 / 2017-09-03

* refactored `zuplus_Form` class to avoid options in call
* added *GitHub Plugin URI* to main file

#### 0.1.0 / 2017-09-03

* plugin created
* added support for [GitHub Updater](https://github.com/afragen/github-updater/)
