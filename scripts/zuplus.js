// WordPress dependencies

// Zukit dependencies

const { renderPage, toggleOption } = wp.zukit.render;
const { ZukitPanel } = wp.zukit.components;

// Internal dependencies

import { zuplus } from './settings/data.js';
import ZuplusDebug from './settings/debug.js';

const EditZuplus = ({
		wp,
		title,
		options,
		updateOptions,
		// setUpdateHook,
		// ajaxAction,
}) => {

	const { options: optionsData, debug: debugOptionsData } = zuplus;

	return (
			<>
				<ZukitPanel title={ title }>
					{ toggleOption(optionsData, options, updateOptions) }
				</ZukitPanel>
				<ZuplusDebug
					wp={ wp }
					data={ debugOptionsData }
					options={ options }
					updateOptions={ updateOptions }
				/>
			</>
	);
};

renderPage('zuplus', {
	edit: EditZuplus,
	panels: zuplus.panels,
});
