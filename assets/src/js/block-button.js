/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Panel, PanelBody, CheckboxControl } from '@wordpress/components';

/**
 * Register Google login button block.
 */
registerBlockType('google-login/login-button', {
	title: __('Log in with Google', 'login-with-google'),
	icon: 'admin-users',
	category: 'widgets',
	attributes: {
		buttonText: {
			type: 'string',
		},
		forceDisplay: {
			type: 'boolean',
			default: false,
		},
	},

	/**
	 * Describes the structure of the block in the context of the editor.
	 *
	 * @param {Object}   props               Block properties.
	 * @param {Object}   props.attributes    Block attributes.
	 * @param {Function} props.setAttributes Function to set block attributes.
	 * @param {Function} props.className     Class name.
	 */
	edit: ({ attributes, setAttributes, className }) => {
		const buttonTextAttributes = {
			format: 'string',
			className,
			onChange: (value) => {
				setAttributes({ buttonText: value });
			},
			value: attributes.buttonText,
			placeholder: __('Log in with Google', 'login-with-google'),
		};

		const checkboxAttributes = {
			label: __('Display Logout', 'login-with-google'),
			help: __(
				'If the user is logged in, keeping this box unchecked will remove the Login with Google button from the page. If the box is checked, the button will show with title changed to ‘Logout’',
				'login-with-google'
			),
			checked: attributes.forceDisplay,
			onChange: (val) => {
				setAttributes({ forceDisplay: val });
			},
		};

		return (
			<div {...useBlockProps}>
				<InspectorControls>
					<Panel>
						<PanelBody title={__('Settings', 'login-with-google')}>
							<CheckboxControl {...checkboxAttributes} />
						</PanelBody>
					</Panel>
				</InspectorControls>
				<div className="wp_google_login__button-container">
					<span className="wp_google_login__button">
						<span className="wp_google_login__google-icon"></span>
						<RichText {...buttonTextAttributes} />
					</span>
				</div>
			</div>
		);
	},
});
