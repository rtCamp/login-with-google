const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { InspectorControls, RichText, useBlockProps } = wp.blockEditor;
const { Panel, PanelBody, CheckboxControl } = wp.components;

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
  edit: (props) => {
    const { attributes, setAttributes } = props;

    const buttonTextAttributes = {
      format: 'string',
      className: props.className,
      onChange: (value) => {
        setAttributes({ buttonText: value });
      },
      value: attributes.buttonText,
      placeholder: __('Log in with Google', 'login-with-google'),
    };

    const checkboxAttributes = {
      label: __('Display even when user is logged-in', 'login-with-google'),
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
						<span className="wp_gogle_login__google-icon"></span>
						<RichText {...buttonTextAttributes} />
					</span>
        </div>
      </div>
    );
  },
});
