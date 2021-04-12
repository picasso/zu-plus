// WordPress dependencies

const { defaults } = lodash;
const { useCallback, useState } = wp.element;
const { Spinner } = wp.components;

// Zukit dependencies

const { ZukitPanel, ZukitTable } = wp.zukit.components;

const cprefix = 'zuplus_core_info';

const ZuplusCoreInfo = ({
		// options,
		labels,
		ajaxAction,
		// setUpdateHook,
}) => {

	const [isOpen, setIsOpen] = useState(false);
	const [coreData, setCoreData] = useState(null);

	const onToggle = useCallback(() => {
		if(coreData === null) {
			ajaxAction('zuplus_zukit_info', zukitData => {
				setCoreData(defaults(zukitData, {
					version: '?'
				}));
			});
		}
		setIsOpen(prev => !prev);
	}, [ajaxAction, coreData]);

	return (
			<ZukitPanel id="core_info" initialOpen={ isOpen } onToggle={ onToggle }>
				{ coreData !== null &&
					<div className={ cprefix }>
						<ul className="__info">
							<li>
								<strong>{ labels.version }</strong>
								<span className="__ver">{ coreData.version }</span>
							</li>
							<li>
								<strong>{ labels.loaded }</strong>
								<span className="__path">{ coreData.from }</span>
							</li>
						</ul>
						<ZukitTable
							fixed={ true }
							config={ coreData.plugins.config }
							body={ coreData.plugins.rows }
						/>
					</div>
				}
				{ coreData === null && <Spinner/> }
			</ZukitPanel>
	);
};

export default ZuplusCoreInfo;
