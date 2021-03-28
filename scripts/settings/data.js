// WordPress dependencies

// const { ExternalLink } = wp.components;
const { __ } = wp.i18n;

// Internal dependencies

const options = {
	debug_mode: {
		label: 	__('Activate Debug Mode?', 'zu-plus'),
		help:	__('All debug functions like <span>_dbug_*()</span> will be activated. Otherwise all calls will be muted.', 'zu-plus'),
	},
	remove_autosave: {
		label: 	__('Remove Autosave Notices?', 'zu-plus'),
		help:	__('Removes Wordpress <span>autosave</span> and <span>backup</span> notices which could be very annoying. You should understand what you are doing.', 'zu-plus'),
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
};

const debug = {
	debug_bar: {
		label: 	__('Use Debug Bar', 'zu-plus'),
		help:	__('Works only if <span>Query Monitor</span> is activated.', 'zu-plus'),
	},
	use_kint: {
		label: 	__('Use KINT', 'zu-plus'),
		help:	__('<span>Kint for PHP</span> is a tool designed to present debugging data in the best way possible graphically.', 'zu-plus'),
	},
	debug_frontend: {
		label: 	__('Support on Front-End?', 'zu-plus'),
		help:	__('Enable debugging JS & CSS on the front side. Commonly used with KINT.', 'zu-plus'),
		// depends: '!hide_root',
	},
	debug_js: {
		label: 	__('Activate Responsive JS Debug info', 'zu-plus'),
		help:	__('Adds class <span>debug</span> to BODY and displays debug info for responsive elements.', 'zu-plus'),
	},
	debug_cache: {
		label: 	__('Debug Caching', 'zu-plus'),
		help: 	__('If checked, all calls to cache functions will be logged.', 'zu-plus'),
	},

	debug_backtrace: {
		label: 	__('Always Include Backtrace', 'zu-plus'),
		help: 	__('In some cases, this can <span>greatly slow down</span> the loading of the page and even lead to a fatal error.', 'zu-plus'),
	},
	write_to_file: {
		label: 	__('Write log to file', 'zu-plus'),
		help: 	__('If unchecked, only the information for <span>Debug Bar</span> will be saved.', 'zu-plus'),
	},
	output_html: {
		label: 	__('Display HTML entities in Debug Bar', 'zu-plus'),
		help: 	__('If checked, all characters which have HTML character entity equivalents are translated into these entities.', 'zu-plus'),
	},
	beautify_html: {
		label: 	__('Beautify HTML in output', 'zu-plus'),
		help: 	__('If unchecked, all HTML values will be saved without any modifications. Otherwise HTML beautifier will be used.', 'zu-plus'),
	},
	ajax_log: {
		label: 	__('Activate AJAX Logging', 'zu-plus'),
		help: 	__('You should make <span>AJAX calls</span> from your JS.', 'zu-plus'),
	},
	profiler: {
		label: 	__('Activate Profiler', 'zu-plus'),
		help: 	__('You should call <span>_profiler_flag()</span> at each point of interest, passing a descriptive string.', 'zu-plus'),
	},

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
