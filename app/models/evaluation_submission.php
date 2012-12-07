<?php
/**
 * EvaluationSubmission
 *
 * @uses AppModel
 * @package   CTLT.iPeer
 * @author    Pan Luo <pan.luo@ubc.ca>
 * @copyright 2012 All rights reserved.
 * @license   MIT {@link http://www.opensource.org/licenses/MIT}
 */
class EvaluationSubmission extends AppModel
{
    public $name = 'EvaluationSubmission';
    public $actsAs = array('Traceable');

    public $belongsTo = array(
        'Event' => array(
            'className' => 'Event',
            'foreignKey' => 'event_id'
        ),
        'GroupEvent' => array(
            'className' => 'GroupEvent',
            'foreignKey' => 'grp_event_id',
        ),
    );

    /**
     * getEvalSubmissionsByEventId
     *
     * @param mixed $eventId event id
     *
     * @access public
     * @return void
     */
    function getEvalSubmissionsByEventId($eventId)
    {
        return $this->find('all', array(
            'conditions' => array(
                $this->alias.'.event_id' => $eventId,
                $this->alias.'.submitted' => '1',
            ),
            'contain' => false,
        ));
    }

    /**
     * getEvalSubmissionByGrpEventIdSubmitter
     *
     * @param bool $grpEventId group event id
     * @param bool $submitter  submitter
     *
     * @access public
     * @return void
     */
    function getEvalSubmissionByGrpEventIdSubmitter($grpEventId=null, $submitter=null)
    {
        return $this->find('first', array(
            'conditions' => array(
                $this->alias.'.grp_event_id' => $grpEventId,
                $this->alias.'.submitter_id' => $submitter,
                $this->alias.'.submitted' => '1',
            ),
        ));
    }

    /**
     * getEvalSubmissionByEventIdSubmitter
     *
     * @param bool $eventId   event id
     * @param bool $submitter submitter
     *
     * @access public
     * @return void
     */
    function getEvalSubmissionByEventIdSubmitter($eventId, $submitter)
    {
        return $this->find('first', array(
            'conditions' => array(
                $this->alias.'.event_id' => $eventId,
                $this->alias.'.submitter_id' => $submitter,
                $this->alias.'.submitted' => 1,
            ),
            'contain' => false,
        ));
    }

    /**
     * numCountInGroupCompleted
     *
     * @param bool $groupEventId group event id
     *
     * @access public
     * @return void
     */
    function numCountInGroupCompleted($groupEventId)
    {
        return $this->find(
            'count',
            array(
                'conditions' => array(
                    $this->alias.'.submitted' => 1,
                    $this->alias.'.grp_event_id' => $groupEventId
                ),
            )
        );
    }

    /**
     * daysLate
     *
     * @param mixed $eventId        event id
     * @param mixed $submissionDate submission date
     *
     * @access public
     * @return void
     */
    function daysLate($eventId, $submissionDate)
    {
        $days = 0;
        $dueDate = $this->Event->find('first', array('conditions' => array('Event.id' => $eventId), 'fields' => array('Event.due_date')));
        $dueDate = $dueDate['Event']['due_date'];
        $seconds = strtotime($dueDate) - strtotime($submissionDate);
        $diff = $seconds / 60 /60 /24;
        if ($diff<0) {
            $days = abs(floor($diff));
        }

        return $days;
    }

    /**
     * countSubmissions
     *
     * @param mixed $grpEventId
     *
     * @access public
     * @return void
     */
    function countSubmissions($grpEventId)
    {
        return $this->find('count', array('conditions' => array('grp_event_id' => $grpEventId,)));
    }
}
