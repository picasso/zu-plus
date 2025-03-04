/* eslint-disable no-use-before-define */
import { get, once, set } from 'lodash-es'

// WordPress dependencies
import { select, subscribe } from '@wordpress/data'
const { getCurrentPostId, isEditedPostNew } = select('core/editor')

function resetPostEditorAutosave(origin = 'ZUKIT') {
	const resetKey = 'resetAutosave'
	// may have already been initiated in another plugin or theme
	if (!get(wp, ['zukit', resetKey])) {
		set(wp, ['zukit', resetKey], true)
		const unsubscribe = subscribe(() => {
			const postId = getCurrentPostId()
			if (postId) {
				resetLocalBackup(postId)
				unsubscribe()
			}
		})

		const resetLocalBackup = (postId) => {
			if (hasSessionStorageSupport()) {
				const isPostNew = isEditedPostNew()

				let localAutosave = localAutosaveGet(postId, isPostNew)
				if (!localAutosave) return

				try {
					localAutosave = JSON.parse(localAutosave)
				} catch (_error) {
					// not usable if it can't be parsed.
					return
				}
				// if 'localAutosave' was found, it can be safely ejected from storage.
				localAutosaveClear(postId, isPostNew)
				window.console.warn(
					`${origin.toUpperCase()}: The backup of the post [${postId}] in your browser was removed`,
				)
			}
		}
	}
}

// Function which returns true if the current environment supports browser
// sessionStorage, or false otherwise.
const hasSessionStorageSupport = once(() => {
	try {
		// Private Browsing in Safari 10 and earlier will throw an error when
		// attempting to set into sessionStorage. The test here is intentional in
		// causing a thrown error as condition bailing from local autosave.
		window.sessionStorage.setItem('__wpEditorTestSessionStorage', '')
		window.sessionStorage.removeItem('__wpEditorTestSessionStorage')
		return true
	} catch (_error) {
		return false
	}
})

// Function returning a sessionStorage key to set or retrieve a given post's
// automatic session backup.

function postKey(postId, isPostNew) {
	return `wp-autosave-block-editor-post-${isPostNew ? 'auto-draft' : postId}`
}

function localAutosaveGet(postId, isPostNew) {
	return window.sessionStorage.getItem(postKey(postId, isPostNew))
}

function localAutosaveClear(postId, isPostNew) {
	window.sessionStorage.removeItem(postKey(postId, isPostNew))
}

resetPostEditorAutosave('Zu Plus')
