// WordPress dependencies

const { get } = lodash;

// Zukit dependencies

const { renderPage, toggleOption } = wp.zukit.render;
const { ZukitPanel } = wp.zukit.components;

// Internal dependencies

import { zuplus } from './settings/data.js';
import ZuplusDebug from './settings/debug.js';
import ZuplusDupMenu from './settings/duplicate-menu.js';
import ZuplusCoreInfo from './settings/info.js';

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
		options: optionsLabels,
		debug: debugOptionsLabels,
		debugSelect: debugSelectLabels,
		duplicate: duplicateLabels,
		info: coreInfoLabels,
	} = zuplus;

	return (
			<>
				<ZukitPanel title={ title }>
					{ toggleOption(optionsLabels, options, updateOptions) }
				</ZukitPanel>
				<ZuplusDebug
					wp={ wp }
					labels={ debugOptionsLabels }
					selectLabels={ debugSelectLabels }
					options={ options }
					updateOptions={ updateOptions }
				/>
				<ZuplusDupMenu
					labels={ duplicateLabels }
					menus={ get(moreData, 'menus', null) }
					ajaxAction={ ajaxAction }
				/>
				<ZuplusCoreInfo
					labels={ coreInfoLabels }
					ajaxAction={ ajaxAction }
				/>
			</>
	);
};

renderPage('zuplus', {
	edit: EditZuplus,
	panels: zuplus.panels,
});
