// WordPress dependencies

const { map, get, find, isEmpty } = lodash;
const { NavigableMenu, MenuItem, Popover, Button } = wp.components;
const { useState, useRef, useCallback } = wp.element;

// Zukit dependencies

const { close: closeIcon } = wp.zukit.icons;
const { ZukitSidebar, AdvTextControl } = wp.zukit.components;

const cprefix = 'zuplus_dup_menu';
const buttonIcon = 'welcome-widgets-menus';

const ZuplusDupMenu = ({
		labels,
		menus,
		ajaxAction,
}) => {

	const [ isOpen, setIsOpen ] = useState(false);
	const [ title, setTitle ] = useState('');
	const [ selectedId, setSelectedId ] = useState(0);
	const [ menuOptions, setMenuOptions ] = useState(menus);

	const anchorRef = useRef(null);

	const openLinkUI = useCallback(() => {
		setIsOpen(!isOpen);
	}, [isOpen]);

	const closeLinkUI = useCallback(() => {
		setIsOpen(false);
		setSelectedId(0);
		setTitle('');
	}, []);

	const duplicateMenu = useCallback(() => {
		closeLinkUI();
		ajaxAction({
			action: 'zuplus_duplicate_menu',
			value: { id: selectedId, title },
		},
			data => setMenuOptions(data)
		);
	}, [selectedId, title, ajaxAction, closeLinkUI]);

	const selectMenu = useCallback(id => {
		setSelectedId(id);
		const newTitle = get(find(menuOptions, { id }), 'title', '');
		setTitle(newTitle);
	}, [menuOptions]);

	if(isEmpty(menus)) return null;

	return (
		<ZukitSidebar.MoreActions>
			<ZukitSidebar.ActionButton
				color="green"
				icon={ buttonIcon }
				onClick={ openLinkUI }
				label={ labels.action }
				help={ labels.help }
				ref={ anchorRef }
			/>
			{ isOpen && (
				<Popover
					position="middle left"
					noArrow={ false }
					onClose={ closeLinkUI }
					anchorRect={ anchorRef.current ? anchorRef.current.getBoundingClientRect() : null }
					focusOnMount={ false }
				>
					<div className={ cprefix }>
						<div className="__title">
							<div className="components-menu-group__label">{ labels.select }</div>
							<Button
								className="__close"
								icon={ closeIcon }
								onClick={ closeLinkUI }
							/>
						</div>
						<NavigableMenu className="__menu">
							{ map(menuOptions, item => (
								<MenuItem
									icon={ item.id === selectedId ? 'yes' : null }
									isSelected={ item.id === selectedId }
									key={ item.id }
									onClick={ () => selectMenu(item.id) }
								>
									{ item.title }
								</MenuItem>)
							) }
						</NavigableMenu>
						<div className="__input">
							<div className="components-menu-group__label">{ labels.input }</div>
							<AdvTextControl
								// withDebounce
								// withoutClear
								showTooltip={ false }
								value={ title }
								onChange={ value => setTitle(value) }
								withoutValues={ map(menuOptions, m => m.title) }
								fallbackValue="menu"
							/>
							<Button
								isPrimary
								disabled={ selectedId === 0 || isEmpty(title) }
								className="__submit"
								icon={ buttonIcon }
								onClick={ duplicateMenu }
							>
								{ labels.button }
							</Button>
						</div>
					</div>
				</Popover>
			) }
		</ZukitSidebar.MoreActions>
	);
};

export default ZuplusDupMenu;
