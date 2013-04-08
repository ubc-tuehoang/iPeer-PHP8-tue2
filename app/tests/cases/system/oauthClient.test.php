<?php
require_once('PHPWebDriver/WebDriver.php');
require_once('PHPWebDriver/WebDriverBy.php');
require_once('PHPWebDriver/WebDriverWait.php');
require_once('PageFactory.php');

class oauthClientTestCase extends CakeTestCase
{
    protected $web_driver;
    protected $session;
    protected $url = 'http://ipeerdev.ctlt.ubc.ca/';
    protected $clientId = 0;
    
    public function startCase()
    {
        $wd_host = 'http://localhost:4444/wd/hub';
        $this->web_driver = new PHPWebDriver_WebDriver($wd_host);
        $this->session = $this->web_driver->session('firefox');
        $this->session->open($this->url);
        
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $this->session->deleteAllCookies();
        $login = PageFactory::initElements($this->session, 'Login');
        $home = $login->login('root', 'ipeeripeer');
    }
    
    public function endCase()
    {
        $this->session->deleteAllCookies();
        $this->session->close();
    }
    
    public function testAddOauthClient()
    {
        $this->session->open($this->url.'pages/admin');
        $title = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "h1.title")->text();
        $this->assertEqual($title, 'Admin');
        
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'OAuth Client Credentials')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Add Client')->click();
        $title = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "h1.title")->text();
        $this->assertEqual($title, 'Create New OAuth Client Credential');
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'OauthClientComment')->sendKeys('For Testing');
        
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[type="submit"]')->click();
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'A new OAuth client has been created');
        $this->assertEqual($this->session->url(), $this->url.'oauthclients');  
    }
    
    public function testEditOauthClient()
    {
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[aria-controls="table_id"]')->sendKeys('For Testing');
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                $count = count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'tr[class="odd"]'));
                return ($count == 1);
            }
        );
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'td[class="  sorting_1"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Edit')->click();
        
        $this->clientId = end(explode('/', $this->session->url()));
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'OauthClientComment')->sendKeys('Has been edited'); 
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[type="submit"]')->click();

        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'The OAuth client has been saved');
        $this->assertEqual($this->session->url(), $this->url.'oauthclients');  
    }
    
    public function testEditProfile()
    {
        $this->session->open($this->url.'users/editProfile');
        $id = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="'.$this->clientId.'"]')->attribute('id');
        $selectId = str_replace('Id','Enabled',$id);
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="'.$selectId.'"] option[value="0"]')->click();
    
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[type="submit"]')->click();
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'Your Profile Has Been Updated Successfully.');
        $label = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="'.$selectId.'"] option[value="0"]')->text();
        $this->assertEqual($label, 'Disabled');
    }
    
    public function testDelete()
    {
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'a[href="/oauthclients/delete/'.$this->clientId.'"]')->click();
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'OAuth client deleted');
    }
}