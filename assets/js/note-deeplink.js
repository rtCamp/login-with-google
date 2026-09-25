( function () {
	'use strict';

	const noteId = Number( window.rtcampGoogleNoteId );
	if ( ! Number.isInteger( noteId ) || noteId < 1 || ! window.wp?.data ) {
		return;
	}

	const selector = '#note-thread-' + noteId;
	let timer;
	let attempts = 0;
	let sidebarRequested = false;

	function focusNote() {
		const note = document.querySelector( selector );
		if ( ! note ) {
			return false;
		}

		note.click();
		note.scrollIntoView( { block: 'center', behavior: 'smooth' } );
		note.focus( { preventScroll: true } );
		window.clearInterval( timer );
		return true;
	}

	function tryOpenNote() {
		attempts++;
		try {
			const interfaceStore = window.wp.data.dispatch( 'core/interface' );
			if (
				! sidebarRequested &&
				interfaceStore?.enableComplementaryArea
			) {
				interfaceStore.enableComplementaryArea(
					'core',
					'edit-post/collab-history-sidebar'
				);
				sidebarRequested = true;
			}
		} catch {
			// The editor's stores may still be loading.
		}

		if ( focusNote() ) {
			return true;
		}
		if ( attempts >= 80 ) {
			window.clearInterval( timer );
		}
		return false;
	}

	function openNoteSidebar() {
		if ( ! tryOpenNote() ) {
			timer = window.setInterval( tryOpenNote, 250 );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', openNoteSidebar, {
			once: true,
		} );
	} else {
		openNoteSidebar();
	}
} )();
