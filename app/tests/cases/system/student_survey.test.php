<?php
require_once('system_base.php');

class studentSurvey extends SystemBaseTestCase
{
    protected $eventId = 0;
    
    public function startCase()
    {
        $this->getUrl();
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
    
    public function testCreateEvent()
    {
        $this->session->open($this->url.'events/add/1');
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventTitle')->sendKeys('Survey');
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventDescription')->sendKeys('Description for the Survey.');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="EventEventTemplateTypeId"] option[value="3"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'select[id="EventSurvey"] option[value="2"]')->click();
        
        //set due date and release date end to next month so that the event is opened.
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventDueDate')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventReleaseDateBegin')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'EventReleaseDateEnd')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'a[title="Next"]')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, '4')->click();

        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[type="submit"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'Add event successful!');
    }
    
    public function testStudent()
    {
        $this->waitForLogoutLogin('redshirt0001');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Survey')->click();
        // multiple choice
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInput0ResponseId8')->click();
        // multiple answers
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInputResponseId11')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInputResponseId13')->click();
        // single line
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInput2ResponseText')->sendKeys('single line answer');
        // multi-line 
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInput3ResponseText')->sendKeys('multi line answer');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'Your survey was submitted successfully!');
    }
    
    public function testStudentResult()
    {
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Survey')->click();
        $mc = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'ol li strong[class="green"]');
        $this->assertEqual($mc->text(), 'B');
        $ma = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'ul li strong[class="green"]');
        $this->assertEqual($ma[0]->text(), 'choose me');
        $this->assertEqual($ma[1]->text(), 'me please');
        $sl = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="SurveyResults"]/p');
        $this->assertEqual($sl->text(), 'single line answer');
        $ml = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="SurveyResults"]/pre');
        $this->assertEqual($ml->text(), 'multi line answer');
        
        $this->secondStudent();
        $this->thirdStudent();
    }
    
    public function testTutor()
    {
        // should not have access to survey
        $this->waitForLogoutLogin('tutor1');
        $survey = $this->session->elements(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Survey');
        $this->assertTrue(empty($survey));
    }
    
    public function testSurveyResults()
    {
        $this->waitForLogoutLogin('root');
        $this->session->open($this->url.'events/index/1');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Survey')->click();
        $this->eventId = end(explode('/', $this->session->url()));
        $this->session->open($this->url.'evaluations/viewSurveySummary/'.$this->eventId);
        
        // multiple choice
        $title = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[1]/th')->text();
        $this->assertEqual($title, '1. Testing out MC (3 responses)');
        
        $a = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[2]/td[2]')->text();
        $b = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[3]/td[2]')->text();
        $c = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[4]/td[2]')->text();
        $d = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[5]/td[2]')->text();
        $this->assertEqual($a, '1');
        $this->assertEqual($b, '1');
        $this->assertEqual($c, '0');
        $this->assertEqual($d, '1');
        
        $a = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[2]/td[3]')->text();
        $b = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[3]/td[3]')->text();
        $c = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[4]/td[3]')->text();
        $d = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[5]/td[3]')->text();
        $this->assertEqual($a, '33%');
        $this->assertEqual($b, '33%');
        $this->assertEqual($c, '-');
        $this->assertEqual($d, '33%');
        
        $a = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[2]/td[4]/img');
        $b = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[3]/td[4]/img');
        $c = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[4]/td[4]/img');
        $d = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[5]/td[4]/img');
        $this->assertEqual($a->attribute('alt'), '33');
        $this->assertEqual($b->attribute('alt'), '33');
        $this->assertEqual($c->attribute('alt'), '0');
        $this->assertEqual($d->attribute('alt'), '33');
        
        // multiple answers
        $title = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[6]/th')->text();
        $this->assertEqual($title, '2. Testing out checkboxes (6 responses)');
        
        $a = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[7]/td[2]')->text();
        $b = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[8]/td[2]')->text();
        $c = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[9]/td[2]')->text();
        $this->assertEqual($a, '3');
        $this->assertEqual($b, '1');
        $this->assertEqual($c, '2');
        
        $a = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[7]/td[3]')->text();
        $b = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[8]/td[3]')->text();
        $c = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[9]/td[3]')->text();
        $this->assertEqual($a, '50%');
        $this->assertEqual($b, '17%');
        $this->assertEqual($c, '33%');
        
        $a = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[7]/td[4]/img');
        $b = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[8]/td[4]/img');
        $c = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[9]/td[4]/img');
        $this->assertEqual($a->attribute('alt'), '50');
        $this->assertEqual($b->attribute('alt'), '17');
        $this->assertEqual($c->attribute('alt'), '33');
        
        // single line answers
        $title = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[10]/th')->text();
        $this->assertEqual($title, '3. Testing out single line answers (3 responses)');
        
        $responders = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[11]/td[2]');
        $this->assertEqual($responders->text(), 'Alex Student, Ed Student, Matt Student');
        
        // multi line answers
        $title = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[12]/th')->text();
        $this->assertEqual($title, '4. Testing out multi-line long answers (3 responses)');
        
        $responders = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '/html/body/div[1]/table/tbody/tr[13]/td[2]');
        $this->assertEqual($responders->text(), 'Alex Student, Ed Student, Matt Student');
        
        // 3 results
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[aria-controls="individualResponses"]')->sendKeys('Result');
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                $filter = $session->element(PHPWebDriver_WebDriverBy::ID, "individualResponses_info")->text();
                return ($filter == 'Showing 1 to 3 of 3 entries (filtered from 13 total entries)');
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::ID, "individualResponses_info")->text();
        $this->assertEqual($msg, 'Showing 1 to 3 of 3 entries (filtered from 13 total entries)');
        
        $results = $this->session->elements(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Result');
        $href0 = $results[0]->attribute('href');
        $href1 = $results[1]->attribute('href');
        $href2 = $results[2]->attribute('href');
        $this->assertEqual($href0, $this->url.'evaluations/viewEvaluationResults/'.$this->eventId.'/5');
        $this->assertEqual($href1, $this->url.'evaluations/viewEvaluationResults/'.$this->eventId.'/6');
        $this->assertEqual($href2, $this->url.'evaluations/viewEvaluationResults/'.$this->eventId.'/7');
        
        $this->session->open($href0);
        $mc = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'ol li strong[class="green"]');
        $this->assertEqual($mc->text(), 'B');
        $ma = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'ul li strong[class="green"]');
        $this->assertEqual($ma[0]->text(), 'choose me');
        $this->assertEqual($ma[1]->text(), 'me please');
        $sl = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="SurveyResults"]/p');
        $this->assertEqual($sl->text(), 'single line answer');
        $ml = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="SurveyResults"]/pre');
        $this->assertEqual($ml->text(), 'multi line answer');
        
        $this->session->open($href1);
        $mc = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'ol li strong[class="green"]');
        $this->assertEqual($mc->text(), 'A');
        $ma = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'ul li strong[class="green"]');
        $this->assertEqual($ma[0]->text(), 'choose me');
        $this->assertEqual($ma[1]->text(), 'me please');
        $sl = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="SurveyResults"]/p[1]');
        $this->assertEqual($sl->text(), 'this is a short answer');
        $ml = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="SurveyResults"]/p[2]');
        $this->assertEqual($ml->text(), '-- No Answer --');
        
        $this->session->open($href2);
        $mc = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'ol li strong[class="green"]');
        $this->assertEqual($mc->text(), 'D');
        $ma = $this->session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'ul li strong[class="green"]');
        $this->assertEqual($ma[0]->text(), 'choose me');
        $this->assertEqual($ma[1]->text(), 'no, me');
        $sl = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="SurveyResults"]/p');
        $this->assertEqual($sl->text(), '-- No Answer --');
        $ml = $this->session->element(PHPWebDriver_WebDriverBy::XPATH, '//*[@id="SurveyResults"]/pre');
        $this->assertEqual($ml->text(), 'long long long');
    }
    
    public function testDelete()
    {
        $this->session->open($this->url.'events/delete/'.$this->eventId);
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'The event has been deleted successfully.');
    }
    
    public function secondStudent()
    {
        $this->waitForLogoutLogin('redshirt0002');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Survey')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInput0ResponseId7')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInputResponseId11')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInputResponseId13')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInput2ResponseText')->sendKeys('this is a short answer');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'Your survey was submitted successfully!');
    }
    
    public function thirdStudent()
    {
        $this->waitForLogoutLogin('redshirt0003');
        $this->session->element(PHPWebDriver_WebDriverBy::LINK_TEXT, 'Survey')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInput0ResponseId10')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInputResponseId11')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInputResponseId12')->click();
        $this->session->element(PHPWebDriver_WebDriverBy::ID, 'SurveyInput3ResponseText')->sendKeys('long long long');
        $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, 'input[value="Submit"]')->click();
        $w = new PHPWebDriver_WebDriverWait($this->session);
        $session = $this->session;
        $w->until(
            function($session) {
                return count($session->elements(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']"));
            }
        );
        $msg = $this->session->element(PHPWebDriver_WebDriverBy::CSS_SELECTOR, "div[class='message good-message green']")->text();
        $this->assertEqual($msg, 'Your survey was submitted successfully!');
    }
}