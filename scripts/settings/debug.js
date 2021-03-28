// WordPress dependencies

const { mapKeys } = lodash;
// const { __ } = wp.i18n;
// const { RangeControl, ColorPalette, BaseControl } = wp.components;
const { useCallback } = wp.element;

// Zukit dependencies

const { toggleOption } = wp.zukit.render;
// const { mergeClasses, compareVersions } = wp.zukit.utils;
const { ZukitPanel } = wp.zukit.components;

// Internal dependencies

// import FolderIcons from './folders-icons.js';
// import ZuplusDebugPreview from './folders-preview.js';

const optionsKey = 'zuplus_debug_options';

const ZuplusDebug = ({
		// wp,
		data,
		options,
		updateOptions,
}) => {

	// const folders = get(options, optionsKey, {});
	const updateDebugOptions = useCallback(update => {
		const debugUpdate = mapKeys(update, (_, key) => `${optionsKey}.${key}`);
		updateOptions(debugUpdate);
	}, [updateOptions]);

	if(options['debug_mode'] === false) return null;

	return (
			<ZukitPanel className="__debug" id="debug" options={ options } initialOpen={ true }>
				{ toggleOption(data, options, updateDebugOptions, optionsKey) }
			</ZukitPanel>
	);
};

// <SelectItemControl
// 	fillMissing
// 	columns={ 5 }
// 	label={ __('Select Back Icon', 'zu-media') }
// 	options={ backIcons }
// 	selectedItem={ folders.icons.back }
// 	onClick={ value => updateDebugOptions({ 'icons.back': value}) }
// 	transformValue={ getFolderIcon }
// />

export default ZuplusDebug;
