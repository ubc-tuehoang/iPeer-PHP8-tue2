<?php
/**
 * AuthModule define the base class of the authentication module. All authentication
 * modules should extend this class.
 *
 * @uses Object
 * @package Plugins.Guard
 * @version //autogen//
 * @copyright Copyright (C) 2010 CTLT
 * @author Compass
 * @license LGPL {@link http://www.gnu.org/copyleft/lesser.html}
 */
class AuthModule extends CakeObject {

    /**
     * hasLoginForm if the authentication module uses build-in login form.
     * authentication module can override its value if using external login form
     *
     * @var boolean true, if the module uses login form. false, if the module uses
     * external authentication page.
     * @access protected
     * hasLoginForm = true (default)
     */
    var $hasLoginForm = true;

    /**
     * guard guard component
     *
     * @var GuardComponent guard component object
     * @access protected
     */
    var $guard = null;

    /**
     * controller controller object
     *
     * @var Controller controller object
     * @access protected
     */
    var $controller = null;

    /**
     * Session the shortcut for session object in component
     *
     * @var Session
     * @access protected
     */
    var $Session = null;

    /**
     * fields the copy of the fields array in AuthComponent
     *
     * @var array
     * @access protected
     * @see AuthComponent
     */
    var $fields = array();

    /**
     * data login data
     *
     * @var array
     * @access protected
     */
    var $data = array();

    /**
     * __construct contructor
     *
     * @param GuardComponent $guard pointor to the guard component
     * @param string $name the name of this authentication module
     * @access protected
     * @return void
     */
    function __construct($guard, $name = null) {
        parent::__construct();
        $this->guard = $guard;
        $this->fields = $guard->fields;
        $this->controller = $guard->controller;
        $this->Session = $guard->Session;

        // check if authentication module name is set
        if (!isset($this->name)) {
            $this->name = $name;
        }

        $this->extractConfig();
    }

    /**
     * hasLoginData test if the controller got the login data (submitted by form
     * or in HTTP header). The authentication module with out login form should
     * override this method to provide its own way to check if the login data is
     * received.
     *
     * @access public
     * @return boolean true if it got login data, false if not.
     */
    function hasLoginData() {
        
        $data = $this->getLoginData();

        return (!empty($data));
    }

    function hasLoginData2() {
        if (false === $this->hasLoginForm()) {
            $this->guard->error('You should override hasLoginData() method in your authentication module as you do not have login form.');
            $this->__stop();
        }

        $data = $this->getLoginData();

        return (!empty($data));
    }
    /**
     * getLoginData return the login data stored in authentication module
     *
     * @access public
     * @return array authentication data
     */
    function getLoginData() {
        
        //$this->log("overrise-auth_module-getLoginData");

        //print_r($this->controller->data);

        //$this->log("overrise-auth_module-getLoginData:GET");
        //print_r($_GET);

        //$this->log("overrise-auth_module-getLoginData:POST");
        //print_r($_POST);
        //print_r($_SERVER);

    
        if (!empty($this->controller->data) && isset($this->controller->data[$this->controller->name])) {
            $this->data = $this->controller->data[$this->controller->name];
        }
        return $this->data;
    }

    function getLoginData2($username,$password=null) {

        $this->data = $this->controller->data[$this->controller->name];


        $thisDataUsername = $this->data['username'];
        $this->log("AUTHMODULE::AAAAAAAAAAAAAAAAAAuth_MODULE.getLoginData2->this->data[userName]:".$thisDataUsername);

        $this->data['username'] = $username;
        $this->data['password'] = $password;

        $str_thisData = serialize($this->data);
        $this->log("AUTHMODULE::AAAAAAAAAAAAAAAAA--Auth_MODULE.getLoginData2->this->data:".$str_thisData);

        return $this->data;
    }
    
    /**
     * hasLoginForm getter method for hasLoginForm. test if authentication module
     * is using build-in login form
     *
     * @access public
     * @return boolean true, if the module uses login form. false, if the module uses
     * external authentication page.
     */
    function hasLoginForm() {
        return $this->hasLoginForm;
    }

    /**
     * logout logout method. Authentication module should override it if there are
     * more clean up to do before logout.
     *
     * @access public
     * @return void
     */
    function logout() {
    }

    /**
     * isLoggedIn test if the user is logged in
     *
     * @access public
     * @return boolean true, if the user is logged in. false, if not.
     */
    function isLoggedIn() {
        return (null != $this->guard->user());
    }

    /**
     * redirectToLogin redirect user to login page
     *
     * @param string $url the url to redirect to.
     * @access public
     * @return void
     */
    function redirectToLogin($url = null) {
        if (!$this->guard->RequestHandler->isAjax()) {
            // exclude root when displaying the auth error message
            if ($url != Router::url('/', false)) {
                $this->Session->setFlash($this->guard->authError, $this->guard->flashElement, array(), 'auth');
            }
            if (!empty($this->controller->params['url']) && count($this->controller->params['url']) >= 2) {
                $query = $this->controller->params['url'];
                unset($query['url'], $query['ext']);
                $url .= Router::queryString($query, array());
            }
            $this->Session->write('Auth.redirect', $url);
            $this->controller->redirect(Router::normalize($this->guard->loginAction));
            return false;
        } elseif (!empty($this->guard->ajaxLogin)) {
            $this->controller->viewPath = 'elements';
            echo $this->controller->render($this->guard->ajaxLogin, $this->guard->RequestHandler->ajaxLayout);
            $this->_stop();
            return false;
        } else {
            $this->controller->redirect(null, 403);
        }
    }

    /**
     * getLoginUrl return the login URL
     *
     * @access public
     * @return string the login URL
     */
    function getLoginUrl() {
        return Router::normalize(array('plugin' => 'guard', 'controller' => 'guard', 'action' => 'login'));
    }

    /**
     * urlNormalize replace the variables with values for URLs
     *
     * @param string $url the URL to be replaced
     * @access public
     * @return string the replaced URL
     */
    function urlNormalize($url) {
        $search = array('%HOST%');
        $replace = array($_SERVER['SERVER_NAME']);
        return str_replace($search, $replace, $url);
    }

    /**
     * extractConfig extract the configurations to variables. All the
     * values that are defined configurations for this authentication module are
     * extract into properties to current module. The arrays are merged. Other
     * types of values are replaced if there are existing ones.
     *
     * @access public
     * @return void
     */
    function extractConfig() {
        $configs = $this->getParameters();
        if (!empty($configs)) {
            foreach($configs as $k => $v) {
                if (isset($this->$k) && is_array($this->$k)) {
                    // merge array, if already defined
                    $this->$k = array_merge($this->$k, $v);
                } else {
                    // for other types of values, just replace them
                    $this->$k = $v;
                }
            }
        }
    }

    /**
     * getParameters return the paramters defined in configuration
     *
     * @access public
     * @return void
     */
    function getParameters() {
        return Configure::read('Guard.AuthModule.' . $this->name);
    }

    /**
     * _mapFields Mapping the fields from the external authentication module to
     * current system. The mappings are defiend in configuration files with
     * regular expressions. The mapped data are stored in $this->data
     * Authentication modules may not use this function if the fields are already
     * matched. This method can be overridden to provide custom mapping. This
     * method also calls convertField method to convert the value of each fields.
     *
     * @access protected
     * @return void
     * @see guard.php
     * @see convertField
     */
    function _mapFields() {
        foreach ($this->fieldMapping as $k => $v) {
            if (isset($_SERVER[$k])) {
                $this->data[$v] = self::convertField($k, $_SERVER[$k]);
            }
        }
    }

    /**
     * convertField convert the values from external authenticatio module to match
     * the current sysatem. The converting rules are defined in configuration
     * file with regular expressions. This method can be overridden to provide
     * custom conversion.
     *
     * @param mixed $field the original field name from external authentication
     * system
     * @param mixed $value the value to be converted
     * @access public
     * @return string converted value
     */
    function convertField($field, $value) {
        if (isset($this->mappingRules) && isset($this->mappingRules[$field])) {
            return preg_replace(array_keys($this->mappingRules[$field]),
                array_values($this->mappingRules[$field]),
                $value
            );
        } else {
            return $value;
        }
    }

    /**
     * identify find the user from database
     *
     * @param bool $username   username
     * @param bool $conditions search condition
     *
     * @access public
     * @return array user array
     */
    function identify($username = null, $conditions = null) {
        // get the model AuthComponent is configured to use
        $model =& $this->guard->getModel(); // default is User
        // do a query that will find a User record when given successful login data
        $user = $model->find('first', array('conditions' => array(
            $model->escapeField($this->guard->fields['username']) => $username)
        ));

        // return null if user invalid
        if (!$user) {
            return null; // this is what AuthComponent::identify would return on failure
        }

        // call original AuthComponent::identify with string for $user and false for $conditions
        return $this->guard->identify($user[$this->guard->userModel][$model->primaryKey], null);
    }

}
