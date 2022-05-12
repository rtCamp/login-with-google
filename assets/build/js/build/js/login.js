/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/login.js":
/*!*************************!*\
  !*** ./src/js/login.js ***!
  \*************************/
/***/ (function() {

/**
 * JS for Login and Register page.
 *
 * @package login-with-google
 */
var wpGoogleLogin = {
  /**
   * Init method.
   *
   * @return void
   */
  init: function init() {
    document.addEventListener('DOMContentLoaded', this.onContentLoaded);
  },

  /**
   * Callback function when content is load.
   * To render the google login button at after login form.
   *
   * Set cookie if "Login with Google" button displayed to bypass page cache
   * Do not set on wp login or registration page.
   *
   * @return void
   */
  onContentLoaded: function onContentLoaded() {
    // Form either can be login or register form.
    this.form = document.getElementById('loginform') || document.getElementById('registerform'); // Set cookie if "Login with Google" button displayed to bypass page cache
    // Do not set on wp login or registration page.

    if (document.querySelector('.wp_google_login') && null === this.form) {
      document.cookie = 'wp-login-with-google=1;path=' + window.location.pathname + ';';
    }

    if (null === this.form) {
      return;
    }

    this.googleLoginButton = this.form.querySelector('.wp_google_login');
    this.googleLoginButton.classList.remove('hidden'); // HTML is cloned from existing HTML node.

    this.form.append(this.googleLoginButton);
  }
};
wpGoogleLogin.init();

/***/ }),

/***/ "./src/scss/login.scss":
/*!*****************************!*\
  !*** ./src/scss/login.scss ***!
  \*****************************/
/***/ (function() {

throw new Error("Module build failed (from ./node_modules/laravel-mix/node_modules/mini-css-extract-plugin/dist/loader.js):\nCodeGenerationError: No template for dependency: CssDependency\n    at /home/nikhil/Projects/login-with-google/assets/node_modules/webpack/lib/Compilation.js:3336:18\n    at /home/nikhil/Projects/login-with-google/assets/node_modules/webpack/lib/Cache.js:91:34\n    at Array.<anonymous> (/home/nikhil/Projects/login-with-google/assets/node_modules/webpack/lib/cache/MemoryCachePlugin.js:45:13)\n    at /home/nikhil/Projects/login-with-google/assets/node_modules/webpack/lib/Cache.js:91:19\n    at Hook.eval [as callAsync] (eval at create (/home/nikhil/Projects/login-with-google/assets/node_modules/tapable/lib/HookCodeFactory.js:33:10), <anonymous>:17:1)\n    at Cache.get (/home/nikhil/Projects/login-with-google/assets/node_modules/webpack/lib/Cache.js:75:18)\n    at ItemCacheFacade.get (/home/nikhil/Projects/login-with-google/assets/node_modules/webpack/lib/CacheFacade.js:111:15)\n    at Compilation._codeGenerationModule (/home/nikhil/Projects/login-with-google/assets/node_modules/webpack/lib/Compilation.js:3319:9)\n    at /home/nikhil/Projects/login-with-google/assets/node_modules/webpack/lib/Compilation.js:3226:11\n    at arrayIterator (/home/nikhil/Projects/login-with-google/assets/node_modules/neo-async/async.js:3467:9)");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	__webpack_modules__["./src/js/login.js"]();
/******/ 	// This entry module doesn't tell about it's top-level declarations so it can't be inlined
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/scss/login.scss"]();
/******/ 	
/******/ })()
;
//# sourceMappingURL=login.js.map