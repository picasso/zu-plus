#### 1.2.0 / 2018-04-27
* debug file output improved
* improvement in css
* fixed errors with `zuplus_dismiss_error`
* improved `zuplus_turn_option()` js function to support `ajax_value` and `confirm` options
* 

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
* added _GitHub Plugin URI_ to main file

#### 0.1.0 / 2017-09-03
* plugin created
* added support for [GitHub Updater](https://github.com/afragen/github-updater/)
