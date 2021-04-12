// WordPress dependencies

// const { mapKeys } = lodash;
// const { __ } = wp.i18n;
// const { RangeControl, ColorPalette, BaseControl } = wp.components;
// const { useCallback } = wp.element;

// Zukit dependencies

const { toggleOption, selectOption } = wp.zukit.render;
// const { mergeClasses, compareVersions } = wp.zukit.utils;
const { ZukitPanel } = wp.zukit.components;

// Internal dependencies

// import ZuplusDebugPreview from './debug-preview.js';

const optionsKey = 'zuplus_debug_options';

const ZuplusDebug = ({
		// wp,
		labels,
		selectLabels,
		options,
		updateOptions,
}) => {

	if(options['debug_mode'] === false) return null;

	return (
			<ZukitPanel className="__debug" id="debug" options={ options } initialOpen={ false }>
				{ toggleOption(labels, options, updateOptions, optionsKey) }
				{ selectOption(selectLabels.dump_method, options, updateOptions, optionsKey) }
			</ZukitPanel>
	);
};

export default ZuplusDebug;
