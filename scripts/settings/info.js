// WordPress dependencies

const { get, defaults, forEach, isEmpty } = lodash;
const { useCallback, useState, useEffect } = wp.element;
const { Spinner } = wp.components;

// Zukit dependencies

const { compareVersions, mergeClasses, simpleMarkdown } = wp.zukit.utils;
const { ZukitPanel, ZukitTable } = wp.zukit.components;

const cprefix = 'zuplus_core_info';

const ZuplusCoreInfo = ({
		labels,
		ajaxAction,
		noticeOperations,
}) => {

	const { createNotice } = noticeOperations;
	const [isOpen, setIsOpen] = useState(false);
	const [coreData, setCoreData] = useState(null);
	const [coreVersion, setCoreVersion] = useState(null);
	const [activeVersion, setActiveVersion] = useState(null);
	const [instVersions, setInstVersions] = useState({});
	const [dynamicCells, updateCells] = ZukitTable.useDynamicCells();

	// get version from GitHub via AJAX (from package.json file)
	const getGitHubVersion = useCallback((uri, id, version, linkedRef) => {
		// use it for debug because GitHub has request limit per hour
		// if(uri || !uri) return;
		const github = uri === null ? 'https://github.com/picasso/zukit' : uri;
		const matches = String(github).match(/https?:\/\/github.com\/(.*?)\/?$/mi);
		const repo = get(matches, [1], null);
		if(repo === null) return updateCells('repository not found', id, 'content');

		const url = `https://api.github.com/repos/${repo}/contents/package.json`;
		fetch(url)
			.then(response => {
				// 404 often means that the repo is 'private'
				if(response.status === 404) {
					if(uri === null) {
						setCoreVersion('repo unavailable');
					} else {
						updateCells('repo unavailable (private?)', id, 'content');
					}
					return {};
				}
				return response.json();
			}).then(data => {
				if(!isEmpty(data)) {
					const content = get(data, 'content', '{}');
					const repoPackage = JSON.parse(atob(content));
					const repoVersion = get(repoPackage, 'version', '?');
					if(uri === null) {
						setCoreVersion(repoVersion);
					} else {
						const isGreat = compareVersions(repoVersion, version) > 0;
						const message = isGreat ? `update available \`${repoVersion}\`` : 'lastest version';
						updateCells(message, id, 'content');
						updateCells(isGreat ? 'less' : 'great', linkedRef, 'className');
					}
				}
			})
			.catch(error => {
				const request = url.replace('https://api.github.com/repos', '...');
				createNotice({
					status: 'error',
					content: simpleMarkdown(`${labels.error} [*${request}*]: **${error}**`, { raw: true }),
					isDismissible: true,
					__unstableHTML: true,
				});
			});
	}, [updateCells, createNotice, labels.error]);

	// load info from server on Open
	const onToggle = useCallback(() => {
		if(coreData === null) {
			ajaxAction('zuplus_zukit_info', zukitData => {
				setCoreData(defaults(zukitData, {
					version: 'undefined',
					from: 'unknown',
					plugins: [],
				}));
			});
			getGitHubVersion(null, setCoreVersion);
		}
		setIsOpen(prev => !prev);
	}, [ajaxAction, coreData, getGitHubVersion]);

	// init AJAX requests for dynamic cells
	const onDynamicCell = useCallback(params => {
		const { row, id, ref, github, current, linked } = params || {};
		if(id === 'lastest') {
			const cell = get(dynamicCells, ref);
			if(cell === undefined) {
				getGitHubVersion(github, ref, current, `${row}:${linked}`);
			}
		}
		else if(id === 'framework') {
			if(coreVersion === null) setInstVersions(prev => ({ ...prev, [ref]: current }));
			else updateInstClassName(coreVersion, current, ref);
		}
	}, [dynamicCells, coreVersion, updateInstClassName, getGitHubVersion]);

	// keep info for plugin intances to update cells when AJAX is completed
	const updateInstClassName = useCallback((core, current, ref) => {
		const isGreat = compareVersions(core, current) > 0;
		updateCells(isGreat ? 'less' : 'great', ref, 'className');
	}, [updateCells]);

	// update classes for instances if the framework version was obtained later
	// than the initialization of dynamic cells
	useEffect(() => {
		if(coreVersion !== null) {
			forEach(instVersions, (current, ref) => updateInstClassName(coreVersion, current, ref));
		}
	}, [coreVersion, instVersions, updateInstClassName])

	// update active version className when the version is received from AJAX
	useEffect(() => {
		let versionClassName = null;
		if(coreVersion !== null && coreData) {
			const fixed =  coreData.version.replace(/[^\d|.]+/, '');
			const isGreat = compareVersions(coreVersion, fixed) > 0;
			versionClassName = isGreat ? 'less' : (fixed === coreData.version) ? 'great' : 'active';
		}
		setActiveVersion(versionClassName);
	}, [coreVersion, coreData])

	return (
			<ZukitPanel id="core_info" initialOpen={ isOpen } onToggle={ onToggle }>
				{ coreData !== null &&
					<div className={ cprefix }>
						<ul className="__info">
							<li className="__lastest">
								<strong>{ labels.lastest }</strong>
								{ coreVersion ?
									<span className="__ver great">{ coreVersion }</span>
									:
									<Spinner/>
								}
							</li>
							<li>
								<strong>{ labels.version }</strong>
								<span className={ mergeClasses('__ver', activeVersion) }>{ coreData.version }</span>
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

							onDynamic={ onDynamicCell }
							dynamic={ dynamicCells }
						/>
					</div>
				}
				{ coreData === null && <ZukitTable loading/> }
			</ZukitPanel>
	);
};

export default ZuplusCoreInfo;
