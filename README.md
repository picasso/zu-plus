# Zu Plus: Developer tools for WordPress and Zukit.

[![WordPress Plugin Version](https://img.shields.io/github/package-json/v/picasso/zu-plus?style=for-the-badge)](https://github.com/picasso/zu-plus)
[![WordPress Plugin: Tested WP Version](https://img.shields.io/github/package-json/testedWP/picasso/zu-plus?color=4ab866&label=wordpress%20tested&style=for-the-badge)](https://wordpress.org)
[![WordPress Plugin Required PHP Version](https://img.shields.io/github/package-json/requiresPHP/picasso/zu-plus?color=bc2a8d&label=php&style=for-the-badge)](https://www.php.net/)
[![License](https://img.shields.io/github/license/picasso/zu-plus?color=fcbf00&style=for-the-badge)](https://github.com/picasso/zu-plus/blob/master/LICENSE)

Supports development with the [Zukit framework](https://github.com/picasso/zukit) and implements various debugging methods and other service functions for WordPress.

![Zu Plus - Developer tools for WordPress and Zukit.](https://user-images.githubusercontent.com/399395/116901691-88e93e80-ac3a-11eb-90e5-d7cb538b84bc.png)


## Description

Unfortunately, when developing in PHP for WordPress, it is not always possible to use the debugger and therefore the only way to find errors and debug is logging. In this plugin, I have collected my practices on various logging and debugging methods. Debug information can be output to a log file or displayed in [Query Monitor](https://github.com/johnbillion/query-monitor) if it is installed. There is also support for a [powerful PHP debugging helper - Kint](https://kint-php.github.io/kint/), which allows you to compactly display information in a structured way.

> &#x1F383; In addition, I usually add various service solutions to this plugin that are needed when creating a site or copying it from a template.

### Debugging Features

* Output of logs using __Kint__
* Output of logs to __Query Monitor__
* Output of debug info about the menu and submenu of Wordpress
<!-- * Debug output for `Responsive JS` (for __Zu__ theme) -->
* Support for logging on the front-end
* Management of the location of the log file and automatic overwriting
* Displays summary information about the __Zukit__ framework and all themes and plugins using it in this instance

### Other Features

* Duplicate WordPress menu
* Duplicate Wordpress Posts, Pages and Custom Posts
<!-- * Caching the code generated in shortcode (*not yet recovered after refactoring*)
* Cookie Notice to inform users that the site uses cookies and to comply with the EU GDPR regulations (*not yet recovered after refactoring*) -->

## Download

+ [Zu Plus on GitHub](https://github.com/picasso/zu-plus/archive/refs/heads/master.zip)

## Installation

1. Upload the `zu-plus` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin using the `Plugins` menu in your WordPress admin panel.
3. You can adjust the necessary settings using your WordPress admin panel in `Settings > Zu+`.

## Public logging methods

In order to take advantage of the logging capabilities implemented in this plugin, you need to use one of the functions:

+ __zu_log(`...$params`)__
+ __zu_logc(`$context`, `...$params`)__
+ __zu_log_if(`$condition`, `...$params`)__
+ __zu_log_location(`$path`, `$priority = 1`)__

If you are using the __Zukit__ framework, you can use the internal methods of your class - `log` and `logc`. This has its advantages and disadvantages. You will get additional information about the instance in which class your method was called, but you will lose the name of the variable passed to the function (only when using __Kint__).

+ __log(`...$params`)__
+ __logc(`$context`, `...$params`)__

The `zu_log` function logs any number of arguments passed to it. The function `zu_logc` differs from it only in that the first parameter is a string (`context`) that will be displayed before the data. The context string can have __modifiers__. If the first character of the string is `!`, `?` or `*` then, this will change the color of the context line to `red`, `orange` or `green`, respectively (when outputting to the __Query Monitor__).
The `zu_log_location` function can be used to change the location of the log file. Moreover, if the `$priority` argument is less than the current priority, then the file location will not change (*sometimes useful when debugging several interacting plugins or modules*). You can pass the magic constant `__FILE__` to set the path of the log file in the same directory.

```php
zu_log_location(__FILE__, 5);

zu_log($post, $title, $data['input']);

zu_logc('!Something went wrong', $_GET);

zu_log_if(isset($post), $post->ID);
```
