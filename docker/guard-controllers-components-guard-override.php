<?php
Configure::load('guard');

App::import('Component', 'Auth');
App::import('AuthModule', 'AuthModule', true, array(dirname(__FILE__)), Inflector::underscore('AuthModule').'.php');

/**
 * GuardComponent the guard component extended from CakePHP build-in
 * AuthComponent. This component allows users to write their own authentication
 * module easily. It also support loading the authentication module and
 * parameters through configuration file, which will make it very easy to switch
 * between authentication modules.
 *
 * @uses AuthComponent
 * @package
 * @version $id$
 * @copyright Copyright (C) 2010 CTLT
 * @author Compass
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class GuardComponent extends AuthComponent {
    /**
     * controller controller pointer
     *
     * @var Controller the controller from which this component is called
     * @access public
     */
    var $controller          = null;

    /**
     * authModule authentication module used, which will be dynamicly loaded
     * according to the configuration file
     *
     * @var AuthModule
     * @access public
     */
    var $authModule          = null;

    /**
     * authModuleUndefined error message for undefined authentication module
     *
     * @var string
     * @access public
     */
    var $authModuleUndefined = 'Please define your authentication module with Guard.AuthModule.';

    /**
     * authModuleNotExist error message for non-existing authentication module
     *
     * @var string
     * @access public
     */
    var $authModuleNotExist  = 'The %s authentication module is not exists.';

    /**
     * authMethodUndefined error message for undefined  method in authentication
     * module
     *
     * @var string
     * @access public
     */
    var $authMethodUndefined = 'Method %s is not defined in the authentication module %s.';

    /**
     * authRequiredMethods the array of the required method that should be
     * implemented by the authentication module
     *
     * @var array
     * @access public
     */
    var $authRequiredMethods = array('authenticate');

    /**
     * overridables the properties from AuthComponent that can be overridden by the configuration file
     *
     * @var array
     * @access public
     */
    var $overridables        = array('userModel', 'fields', 'userScope', 'loginRedirect',
        'logoutRedirect', 'loginError', 'authError',
        'sessionKey', 'ajaxLogin', 'flashElement');

    /**
     * initialize override the base method to provide additional initialization
     * and load the authentication module. The properies from AuthComponent may
     * also be override if defined in configuration file.
     *
     * @param Controller $controller the controller that called this component
     * @param array $settings the settings to initialize
     * @access public
     * @return void
     */
    function initialize(&$controller, $settings=array()) {
        $this->controller =& $controller;
        // ugly hack to register myself to Auth because of the limitation of Cake
        $this->controller->Auth = $this;

        // initialize internal variables
        $this->loginAction = array('plugin' => 'guard', 'controller' => 'guard', 'action' => 'login');

        $this->log('YYYYYYYYYYYYYYYY:initialize!');

        $log_data = print_r($settings, true);
        $this->log('YYYYYYYYYYYYYYYY:initialize!:log_data::'.$log_data);
        $controller = print_r($controller, true);
        $this->log('YYYYYYYYYYYYYYYY:initialize!:controller::'.$controller);

        // check the authentication module and new it
        if (null == ($authModuleName = Configure::read('Guard.AuthModule.Name'))) {
            $this->error($this->authModuleUndefined);
        }

        // if the auth_method is specified, use it to override configuration
        if (isset($_REQUEST['auth_method'])) {
            $authModuleName = $_REQUEST['auth_method'];
            $this->log('YYYYYYYYYYYYYYYY:initialize::'.$authModuleName);
        }

        $authModuleFullName = $authModuleName.'Module';

        if (!App::import('AuthModule', $authModuleFullName, true, $this->_getModuleSearchPath(), Inflector::underscore($authModuleFullName) . '.php')) {
            $this->error(sprintf($this->authModuleNotExist, $authModuleName));
        } else {
            $this->authModule = new $authModuleFullName($this, $authModuleName);
        }

        // check if all the required methods are defined
        foreach ($this->authRequiredMethods as $m) {
            if (!method_exists($authModuleFullName, $m)) {
                $this->error(sprintf($this->authMethodUndefined, $m, $authModuleName));
            }
        }

        // override the auth component variables reading from the config file
        $this->_overrideVars();

        //$testUserID = $this->data['User']['id'];
        //$testUserName = $this->data['User']['username'];
        //$this->log("AAAAAAAAAA:GuardController:testUserID:.............");
        //$this->log("AAAAAAAAAA:GuardController:testUserName:".$testUserName);


        parent::initialize($controller, $settings);
    }

    /**
     * startup Main login logic. It uses the authentication module method to
     * authenticate users and process of login data.
     *
     * @param Controller $controller the reference to the instantiating controller
     * object
     * @access public
     * @return boolean
     */
    function startup(&$controller) {
        $isErrorOrTests = (
            strtolower($controller->name) == 'cakeerror' ||
            (strtolower($controller->name) == 'tests' && Configure::read() > 0)
        );
        if ($isErrorOrTests) {
            return true;
        }

        $methods = array_flip($controller->methods);
        $action = strtolower($controller->params['action']);
        $isMissingAction = (
            $controller->scaffold === false &&
            !isset($methods[$action])
        );

        if ($isMissingAction) {
            return true;
        }

        if (!$this->__setDefaults()) {
            return false;
        }

        $this->data = $controller->data;
        $url = '';

        if (isset($controller->params['url']['url'])) {
            $url = $controller->params['url']['url'];
        }
        $url = Router::normalize($url);
        $loginAction = Router::normalize($this->loginAction);

        $allowedActions = array_map('strtolower', $this->allowedActions);
        $isAllowed = (
            $this->allowedActions == array('*') ||
            in_array($action, $allowedActions)
        );

        if ($loginAction != $url && $isAllowed) {
            return true;
        }

        if ($this->isLoggedIn() && $loginAction != $url) {
                    // Get all object properties as an associative array
            $properties = get_object_vars($this);
            $this->log('MMMMMMMMMMMMMMMM222222:'.json_encode($properties));

            return true;
        }

        if (method_exists($controller, '_beforeLogin')) {
            $controller->_beforeLogin();
        }

        if ($loginAction == $url || $url == '/loginout/loginByCWL') {
            // we are in the login action
            try {
                if (!$this->authModule->hasLoginData()) {
                    if (!$this->Session->check('Auth.redirect') && !$this->loginRedirect && env('HTTP_REFERER')) {
                        $this->Session->write('Auth.redirect', $controller->referer(null, true));
                    }
                    return false;
                } else if ($this->authModule->authenticate()) {
                    //// authenticate success, identify the user from local table
                    if (!($user = $this->authModule->identify($this->authModule->data[$this->fields['username']]))) {
                        throw new Exception('Access Denied. You have successfully authenticated, but you do not have access to this application.');
                    }

                    $this->Session->write($this->sessionKey, $user);
                    CakeLog::write('login', 'User '.$this->authModule->data[$this->fields['username']].' logged in with '.$this->authModule->name.' module.');
                } else {
                    throw new Exception($this->loginError);
                }
            } catch (Exception $e) {
                if (isset($this->authModule->fallbackInternal) && $this->authModule->fallbackInternal) {
                    CakeLog::write('debug', 'Extneral authentication failed, fallback to internal authentication module.');
                    $internal = new DefaultModule($this);
                    if (!$internal->authenticate($this->authModule->data[$this->fields['username']])) {
                        CakeLog::write('login', 'User '.$this->authModule->data[$this->fields['username']]. ' login failed with Default module. '.$e->getMessage());
                        $this->Session->setFlash($e->getMessage(), $this->flashElement, array(), 'auth');
                        return false;
                    } else {
                        CakeLog::write('login', 'User '.$this->authModule->data[$this->fields['username']].' logged in with Default module.');
                    }
                } else {
                    return false;
                }
            }

            $this->_loggedIn = true;

            if (method_exists($controller, '_afterLogin')) {
                $controller->_afterLogin();
            }
            if ($this->autoRedirect) {
                $controller->redirect($this->redirect(), null, true);
            }

            return true;
        } else {
            // we are in other actions and we need authentication, redirect
            return $this->authModule->redirectToLogin($url);
        }

        // duplicated authorization code from AuthComponent as both authentication
        // and authorization code are in the same method
        if (!$this->authorize) {
            return true;
        }

        extract($this->__authType());
        switch ($type) {
        case 'controller':
            $this->object =& $controller;
            break;
        case 'crud':
        case 'actions':
            if (isset($controller->Acl)) {
                $this->Acl =& $controller->Acl;
            } else {
                trigger_error(__('Could not find AclComponent. Please include Acl in Controller::$components.', true), E_USER_WARNING);
            }
            break;
        case 'model':
            if (!isset($object)) {
                $hasModel = (
                    isset($controller->{$controller->modelClass}) &&
                    is_object($controller->{$controller->modelClass})
                );
                $isUses = (
                    !empty($controller->uses) && isset($controller->{$controller->uses[0]}) &&
                    is_object($controller->{$controller->uses[0]})
                );

                if ($hasModel) {
                    $object = $controller->modelClass;
                } elseif ($isUses) {
                    $object = $controller->uses[0];
                }
            }
            $type = array('model' => $object);
            break;
        }

        if ($this->isAuthorized($type)) {
            return true;
        }

        $this->Session->setFlash($this->authError, $this->flashElement, array(), 'auth');
        $controller->redirect($controller->referer(), null, true);
        return false;
    }

    /**
     * isLoggedIn test if the user is logged in. Calls isLoggedIn in
     * authentication module
     *
     * @access public
     * @return void
     */
    function isLoggedIn() {
        $this->log('YYYYYYYYYYYYYYYY:isLoggedIn::'.json_encode($this->$_COOKIE).'::'.json_encode($this->$_ENV));
        return $this->authModule->isLoggedIn();
    }

    /**
     * logout logout user. Calls logout in authentication module.
     *
     * @access public
     * @return void
     */
    function logout() {
        $this->authModule->logout();
        CakeLog::write('login', 'User '.$this->user('username').' logged out');
        if (method_exists($this->controller, '_afterLogout')) {
            $this->controller->_afterLogout();
        }
        return parent::logout();
    }

    /**
     * error output an error message
     *
     * @param mixed $message
     * @access public
     * @return void
     */
    function error($message) {
        trigger_error($message, E_USER_ERROR);
        $this->_stop();
    }

    /**
     * getLoginUrl get the login URL, which will be generated by authentication
     * module, used for login buttons. By default, the URL is the login action.
     * External authentication module like Shibboleth may generate a different URL.
     *
     * @access public
     * @return void
     */
    function getLoginUrl() {
        return $this->authModule->getLoginUrl();
    }

    /**
     * hasLoginForm test if the authentication uses a login form
     *
     * @access public
     * @return void
     */
    function hasLoginForm() {
        return $this->authModule->hasLoginForm();
    }

    /**
     * getAuthModuleName a shortcut to get the authentication module's name.
     *
     * @access public
     * @return void
     */
    function getAuthModuleName() {
        return $this->authModule->name;
    }

    /**
     * getParameters get paramters defined in configuration
     *
     * @access public
     * @return void
     */
    function getParameters() {
        return $this->authModule->getParameters();
    }

    /**
     * _getModuleSearchPath get the paths for searching the authentication modules.
     * Usually user defined authentication modules are placed in app/libs
     *
     * @access protected
     * @return void
     */
    function _getModuleSearchPath() {
        $paths = array_merge(App::path('libs'), App::path('components'));
        $paths[] = App::pluginPath('Guard') . 'controllers' . DS . 'components' . DS;
        return $paths;
    }

    /**
     * _overrideVars override the properties in AuthComponent from configuration
     * file.
     *
     * @access protected
     * @return void
     */
    function _overrideVars() {
        foreach($this->overridables as $o) {
            if(isset($this->authModule->$o)) {
                $this->$o = $this->authModule->$o;
            }
        }
    }
}
