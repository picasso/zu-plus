// WordPress dependencies

const { __ } = wp.i18n;

// Internal dependencies

const options = {
	debug_mode: {
		label: 	__('Activate Debug Mode?', 'zu-plus'),
		help:	__('All debug functions like *_dbug()* will be activated. Otherwise all calls will be muted.', 'zu-plus'),
	},
	dup_page: {
		label: 	__('Activate Duplicate Page & Menu?', 'zu-plus'),
		help:	__('Allows duplicate Menu, Posts, Pages and Custom Posts using single click.', 'zu-plus'),
		// depends: 'responsive',
	},
	cookie_notice: {
		label: 	__('Activate Cookie Notice?', 'zu-plus'),
		help:	__('Allows you to inform users that the site uses cookies and to comply with the EU GDPR regulations.', 'zu-plus'),
		// 2em -> margins above and under the divider
		divider: 2,
	},
	disable_cached: {
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
	debug_frontend: {
		label: 	__('Support front-end debugging?', 'zu-plus'),
		help:	__('Enable debugging JS & CSS on the front side. Commonly used with KINT.', 'zu-plus'),
		// depends: '!hide_root',
	},
	debug_rsjs: {
		label: 	__('Activate `Responsive JS` debug info', 'zu-plus'),
		help:	__('Adds class *debug* to BODY and displays debug info for responsive elements.', 'zu-plus'),
	},
	debug_caching: {
		label: 	__('Debug Caching', 'zu-plus'),
		help: 	__('If checked, all calls to cache functions will be logged.', 'zu-plus'),
		divider: 2,
	},
	debug_bar: {
		label: 	__('Use Debug Bar', 'zu-plus'),
		help:	__('Works only if [Query Monitor](https://github.com/johnbillion/query-monitor) is activated.', 'zu-plus'),
	},
	output_html: {
		label: 	__('Convert to HTML entities in Debug Bar', 'zu-plus'),
		help: 	__('If checked, all characters which have HTML character entity equivalents are translated into these entities.', 'zu-plus'),
		depends: 'debug_bar',
	},
	write_file: {
		label: 	__('Write log to file', 'zu-plus'),
		help: 	__('If unchecked, only the information for `Debug Bar` will be saved.', 'zu-plus'),
	},
	overwrite_file: {
		label: 	__('Overwrite logfile', 'zu-plus'),
		help: 	__('If checked, the log data will be **overwritten on every request**, otherwise it will be appended to the file.', 'zu-plus'),
		depends: 'write_file',
	},
	flywheel_log: {
		label: 	__('Use Local logfile location', 'zu-plus'),
		help: __('[Local by Flywheel](https://localwp.com//) is a free development application to develop WordPress locally.', 'zu-plus'),
		depends: 'write_file',
	},

	// debug_backtrace: {
	// 	label: 	__('Always Include Backtrace', 'zu-plus'),
	// 	help: 	__('In some cases, this can `greatly slow down` the loading of the page and even lead to a fatal error.', 'zu-plus'),
	// },
	//
	// beautify_html: {
	// 	label: 	__('Beautify HTML in output', 'zu-plus'),
	// 	help: 	__('If unchecked, all HTML values will be saved without any modifications. Otherwise HTML beautifier will be used.', 'zu-plus'),
	// },
	// ajax_log: {
	// 	label: 	__('Activate AJAX Logging', 'zu-plus'),
	// 	help: 	__('You should make `AJAX calls` from your JS.', 'zu-plus'),
	// },
	// profiler: {
	// 	label: 	__('Activate Profiler', 'zu-plus'),
	// 	help: 	__('You should call `_profiler_flag()` at each point of interest, passing a descriptive string.', 'zu-plus'),
	// },

};

const panels = {
	debug: {
		value: true,
		label: 	__('Debug Mode Settings', 'zu-plus'),
		// Это позволит исключить эту панель когда значение option is false
		depends: 'debug_mode',
	},
	cookie_notice: {
		value: true,
		label: 	__('Cookie Notice', 'zu-plus'),
		depends: 'cookie_notice',
	},
};

export const zuplus = {
	options,
	debug,
	panels,
}
