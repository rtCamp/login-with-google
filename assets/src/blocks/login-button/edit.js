/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * Editor styles for the block.
 */
import './editor.scss';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import {
	useBlockProps,
	InspectorControls,
	RichText,
} from '@wordpress/block-editor';
import { Panel, PanelBody, CheckboxControl } from '@wordpress/components';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object}   root0               - Object containing the block's attributes and functions.
 * @param {Object}   root0.attributes    - The block's attributes.
 * @param {Function} root0.setAttributes - Function to update the block's attributes.
 * @param {string}   root0.className     - The block's class name.
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes, className } ) {
	const { buttonText, forceDisplay } = attributes;

	const buttonTextAttributes = {
		format: 'string',
		className: 'google-login-button-text',
		onChange: ( value ) => {
			setAttributes( { buttonText: value } );
		},
		value: buttonText,
		placeholder: __( 'Login with Google', 'login-with-google' ),
	};

	const forceDisplayAttributes = {
		label: __( 'Display Logout', 'login-with-google' ),
		help: __(
			'If the user is logged in, keeping this box unchecked will remove the Login with Google button from the page. If the box is checked, the button will show with title changed to ‘Logout’',
			'login-with-google'
		),
		checked: forceDisplay,
		onChange: ( val ) => {
			setAttributes( { forceDisplay: val } );
		},
	};

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<Panel>
					<PanelBody title={ __( 'Settings', 'login-with-google' ) }>
						<CheckboxControl { ...forceDisplayAttributes } />
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div className="wp_google_login__button-container">
				<span className="wp_google_login__button">
					<span className="wp_google_login__google-icon"> </span>
					<RichText { ...buttonTextAttributes } />
				</span>
			</div>
		</div>
	);
}
