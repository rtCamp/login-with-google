/**
 * JS for Login with Google Block.
 *
 * @package login-with-google
 */

const {registerBlockType} = wp.blocks;
const {__} = wp.i18n;
const {InspectorControls, RichText, useBlockProps} = wp.blockEditor;
const {Panel, PanelBody, CheckboxControl} = wp.components;

/**
 * Register block type.
 *
 * @param metadata ( object )
 * @param settings ( object )
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
	 * @param {Object} props Props.
	 *
	 * @return {Object} Block elements.
	 */
	edit: (props) => {
		const {attributes, setAttributes} = props;

		const buttonTextAttributes = {
			format: 'string',
			className: props.className,
			onChange: (value) => {
				setAttributes({buttonText: value});
			},
			value: attributes.buttonText,
			placeholder: __('Log in with Google', 'login-with-google'),
		};

		const checkboxAttributes = {
			label: __('Display Logout', 'login-with-google'),
			help: __(
				'If the user is logged in, keeping this box unchecked will remove the Login with Google button from the page. If the box is checked, the button will show with title changed to ‘Logout’',
				'login-with-google'),
			checked: attributes.forceDisplay,
			onChange: (val) => {
				setAttributes({forceDisplay: val});
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
