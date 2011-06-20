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
 * MixevalsQuestion
 *
 * Enter description here...
 *
 * @package
 * @subpackage
 * @since
 */
class MixevalsQuestion extends AppModel
{
  var $name = 'MixevalsQuestion';
  var $hasMany = array(
                  'Description' =>
                     array('className'   => 'MixevalsQuestionDesc',
                           'order'       => '',
                           'foreignKey'  => 'question_id',
                           'dependent'   => true,
                           'exclusive'   => true,
                           'finderSql'   => ''
                          ),
                     );

  /**
   * Saves Mix evaluation questions to database
   * 
   * @param Type_int $id: id of mix corresponding mix evaluation
   * @param Type_array $data: array of the mixevals questions to be inserted
   */
  function insertQuestion($id=null, $data=null) {
    if(!is_null($id) && !is_null($data)){
      foreach($data as $value){
   		$value['mixeval_id'] = $id;
   		$this->save($value);
  		$this->id = null;
      }
    }
	else return false;    
  }
   
  /*FUNCTION NOT BEING USED
    called by mixevals controller during an edit of an
    existing mixeval question(s)*/
  /*function updateQuestion($id, $data){
    $this->deleteQuestions($id);
    $this->insertQuestion($id, $data);
  }*/
  
  // called by the delete function in the controller
  function deleteQuestions($id){
//  	$this->query('DELETE FROM mixevals_questions WHERE mixeval_id='.$id);
    $this->delete($id);
  }
  
/**
 * Get corresponding mix evaluation question corresponding to some mix evaluation
 * 
 * @param Tpye_int $mixEvalId : mix evaluation id
 */
  function getQuestion($mixEvalId=null){
//  	$data = $this->find('all','mixeval_id='.$id, null, 'question_num ASC');
  	return $this->find('all', array(
            'conditions' => array('mixeval_id' => $mixEvalId),
            'order' => 'question_num ASC'
        ));
  }  
}
?>
