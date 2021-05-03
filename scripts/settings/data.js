// WordPress dependencies

const { __ } = wp.i18n;

// Internal dependencies

const options = {
	debug_mode: {
		label: 	__('Activate Debug Mode?', 'zu-plus'),
		help:	__('All debug functions like *zu_log()* will be activated. Otherwise all calls will be muted.', 'zu-plus'),
	},
	dup_page: {
		label: 	__('Activate Duplicate Page & Post?', 'zu-plus'),
		help:	__('Allows duplicate Posts, Pages and Custom Posts using single click.', 'zu-plus'),
	},
	cookie_notice: {
		label: 	__('Activate Cookie Notice?', 'zu-plus'),
		help:	__('Allows you to inform users that the site uses cookies and to comply with the EU GDPR regulations.', 'zu-plus'),
		// temporarily remove - not yet implemented
		depends: false,
	},
	disable_cached: {
		divider: 2,
		label: 	__('Disable Cached Shortcodes?', 'zu-plus'),
		help: __('Disabling caching will result in memory savings, but very small (**not recommended**).', 'zu-plus'),
	},
	remove_autosave: {
		label: 	__('Remove Autosave Notices?', 'zu-plus'),
		help:	__('Removes Wordpress *autosave* and *backup* notices which could be very annoying.\nYou should understand what you are doing.', 'zu-plus'),
	},
};

const debug = {
	use_kint: {
		label: 	__('Use KINT', 'zu-plus'),
		help: __('[Kint for PHP](https://kint-php.github.io/kint/) is a tool designed to present debugging data in the best way possible graphically.', 'zu-plus'),
	},
	debug_rsjs: {
		label: 	__('Activate `Responsive JS` debug info', 'zu-plus'),
		help:	__('Adds class *debug* to BODY and displays info for responsive elements (only if the theme supports it).', 'zu-plus'),
	},
	avoid_ajax: {
		label: 	__('Avoid AJAX calls', 'zu-plus'),
		help:	__('If checked, all logging inside AJAX calls (via **admin-ajax.php**) will be ignored.', 'zu-plus'),
	},
	debug_menus: {
		label: 	__('Output menu order', 'zu-plus'),
		help: 	__('If checked, the current order of items in **menus** and **submenus** will be logged.', 'zu-plus'),
	},
	debug_caching: {
		label: 	__('Debug Caching', 'zu-plus'),
		help: 	__('If checked, all calls to cache functions will be logged.', 'zu-plus'),
	},
	debug_bar: {
		label: 	__('Use Debug Bar', 'zu-plus'),
		help:	__('Works only if [Query Monitor](https://github.com/johnbillion/query-monitor) is activated.', 'zu-plus'),
		divider: 2,
	},
	debug_frontend: {
		label: 	__('Support front-end debugging?', 'zu-plus'),
		help:	__('Enable debugging JS & CSS on the front side. Used only with Debug Bar.', 'zu-plus'),
		depends: 'debug_bar',
	},
	convert_html: {
		label: 	__('Convert to HTML entities in Debug Bar', 'zu-plus'),
		help: 	__('If checked, all characters which have HTML character entity equivalents are translated into these entities.', 'zu-plus'),
		depends: 'debug_bar',
	},
	write_file: {
		label: 	__('Write log to file', 'zu-plus'),
		help: 	__('If unchecked, only the information for `Debug Bar` will be saved.', 'zu-plus'),
	},
	flywheel_log: {
		label: 	__('Use Local logfile location', 'zu-plus'),
		help: __('[Local by Flywheel](https://localwp.com//) is a free development application to develop WordPress locally.', 'zu-plus'),
		depends: 'write_file',
	},
	overwrite: {
		label: 	__('Overwrite logs', 'zu-plus'),
		help: 	__('If checked, the log data will be **overwritten on every reload**, otherwise it will be appended to the file/Debug Bar.', 'zu-plus'),
		depends: ['||', 'debug_bar', 'write_file'],
	},
	// ajax_log: {
	// 	label: 	__('Activate AJAX Logging', 'zu-plus'),
	// 	help: 	__('You should make `AJAX calls` from your JS.', 'zu-plus'),
	// },
};

const dumpMethod = {
	id: 'dump_method',
	label: 	__('Method for outputting debug data', 'zu-plus'),
	help:	__('Choose which method will be used to output **human-readable** information about a logged data.\nAttention! `var_export` does not handle circular references (**try another option in case of error**).', 'zu-plus'),
	options: [
		{ value: 'var_export', label: __('"var_export" function', 'zu-plus') },
		{ value: 'print_r', label: __('"print_r" function', 'zu-plus') },
		{ value: 'dump_var', label: __('"dump_var" function', 'zu-plus') },
	],
	defaultValue: 'var_export',
	depends: '!use_kint',
	divider: 2,
};

const debugSelect = {
	dump_method: dumpMethod,
};

const duplicate = {
	action: __('Duplicate Menu', 'zu-plus'),
	help: __('Here you can easily duplicate any of your **WordPress Menus**. Just follow the instructions.', 'zu-plus'),
	select: __('Select menu to duplicate', 'zu-plus'),
	input: __('And enter a name for menu', 'zu-plus'),
	button: __('Duplicate', 'zu-plus'),
};

const info = {
	lastest: __('Lastest Version', 'zu-plus'),
	version: __('Active Version', 'zu-plus'),
	loaded: __('Loaded From', 'zu-plus'),
	error: __('Ajax request failed', 'zu-plus'),
};

const panels = {
	debug: {
		value: true,
		label: 	__('Debug Mode Settings', 'zu-plus'),
		// This will exclude this panel when option is 'false'
		depends: 'debug_mode',
	},
	cookie_notice: {
		value: true,
		label: 	__('Cookie Notice', 'zu-plus'),
		depends: 'cookie_notice',
	},
	core_info: {
		value: false,
		label: 	__('Framework Info', 'zu-plus'),
	}
};

export const zuplus = {
	options,
	debug,
	debugSelect,
	duplicate,
	info,
	panels,
}
