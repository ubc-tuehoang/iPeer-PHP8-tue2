<?php
/* SVN FILE: $Id$ */

/**
 * Enter description here ....
 *
 * @filesource
 * @copyright    Copyright (c) 2006, .
 * @link
 * @package
 * @subpackage
 * @since
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date: 2006/06/20 18:44:18 $
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Survey
 *
 * Enter description here...
 *
 * @package
 * @subpackage
 * @since
 */
class Survey extends AppModel
{
  var $name = 'Survey';

  var $belongsTo = array('Course' =>
                         array('className'  =>  'Course',
                               'conditions' =>  '',
                               'order'      =>  '',
                               'foreignKey' =>  'course_id'),
                         'Creator' =>
                         array('className'  =>  'User',
                               'conditions' =>  '',
                               'order'      =>  '',
                               'foreignKey' =>  'creator_id')
                        );

  function getSurveyIdByCourseIdTitle($courseId=null,$title=null) {
    $tmp = $this->find('course_id='.$courseId.' AND name=\''.$title.'\'','id');
    return $tmp['Survey']['id'];
  }

  function getSurveyResult($courseId=null) {
    $condition = 'Survey.course_id='.$courseId;
    $fields = 'Survey.id,Survey.name,User.id,User.first_name,User.last_name,User.student_no,EvaluationSubmission.id,EvaluationSubmission.submitted,EvaluationSubmission.date_submitted';
    $joinTable = array(' LEFT JOIN (users as Users CROSS JOIN evaluation_submissions as EvaluationSubmission) ON (User.id=EvaluatonSubmission.submitter_id');

    return $this->findAll($condition, $fields, null, null, null, null, $joinTable );
  }

  function getSurveyTitleById($id=null) {
    $tmp = $this->find('id='.$id);
    return $tmp['Survey']['name'];
  }
}

?>
