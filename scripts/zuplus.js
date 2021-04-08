// WordPress dependencies

const { get } = lodash;

// Zukit dependencies

const { renderPage, toggleOption } = wp.zukit.render;
const { ZukitPanel } = wp.zukit.components;

// Internal dependencies

import { zuplus } from './settings/data.js';
import ZuplusDebug from './settings/debug.js';
import ZuplusDupMenu from './settings/duplicate-menu.js';

const EditZuplus = ({
		wp,
		title,
		options,
		updateOptions,
		// setUpdateHook,
		ajaxAction,
		moreData,
}) => {

	const {
		options: optionsData,
		debug: debugOptionsData,
		debugSelect: debugSelectData,
		duplicate: duplicateData,
	} = zuplus;

	return (
			<>
				<ZukitPanel title={ title }>
					{ toggleOption(optionsData, options, updateOptions) }
				</ZukitPanel>
				<ZuplusDebug
					wp={ wp }
					data={ debugOptionsData }
					selectData={ debugSelectData }
					options={ options }
					updateOptions={ updateOptions }
				/>
				<ZuplusDupMenu
					data={ duplicateData }
					menus={ get(moreData, 'menus', null) }
					ajaxAction={ ajaxAction }
				/>
			</>
	);
};

renderPage('zuplus', {
	edit: EditZuplus,
	panels: zuplus.panels,
});
