// Zukit dependencies
const { toggleOption, selectOption } = wp.zukit.render
const { ZukitPanel } = wp.zukit.components
// const { mergeClasses, compareVersions } = wp.zukit.utils;

// Internal dependencies
// import ZuplusDebugPreview from './debug-preview.js';

const optionsKey = 'zuplus_debug_options'

const ZuplusDebug = ({
	// wp,
	labels,
	selectLabels,
	options,
	updateOptions,
}) => {
	if (options['debug_mode'] === false) return null

	return (
		<ZukitPanel className="__debug" id="debug" options={options} initialOpen={true}>
			{toggleOption(labels, options, updateOptions, optionsKey)}
			{selectOption(selectLabels.dump_method, options, updateOptions, optionsKey)}
		</ZukitPanel>
	)
}

export default ZuplusDebug
