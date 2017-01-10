<?php
/**
 * Status tracker/information for a job.
 *
 * @package     Resque/Job
 * @author      David Shorthouse <dshorthouse@mbl.edu>
 */
class Resque_Job_Message
{

    /**
     * @var string The ID of the job this message class refers back to.
     */
    private $id;

    /**
     * @var mixed Cache variable if the status of this job is being monitored or not.
     *  True/false when checked at least once or null if not checked yet.
     */
    private $hasMessage = null;
    
    /**
     * Setup a new instance of the job monitor class for the supplied job ID.
     *
     * @param string $id The ID of the job to manage the status for.
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Create a new status monitor item for the supplied job ID. Will create
     * all necessary keys in Redis to monitor the status of a job.
     *
     * @param string $id The ID of the job to monitor the status of.
     */
    public static function create($id, $message = '')
    {
        $statusPacket = array(
            'message' => $message,
            'updated' => time(),
            'started' => time(),
        );
        Resque::redis()->set('job:' . $id . ':message', json_encode($statusPacket));
    }

    /**
     * Check if we're actually checking the message of the loaded job message
     * instance.
     *
     * @return boolean True if the message is included, false if not.
     */
    public function hasMessage()
    {
        if($this->hasMessage === false) {
            return false;
        }

        if(!Resque::redis()->exists((string)$this)) {
            $this->hasMessage = false;
            return false;
        }

        $this->hasMessage = true;
        return true;
    }
    
    /**
     * Update the message for the current job with a new message.
     *
     * @param string The message for the job
     */
    public function update($message)
    {

        $statusPacket = array(
            'message' => $message,
            'updated' => time(),
        );
        Resque::redis()->set((string)$this, json_encode($statusPacket));
    }

    /**
     * Fetch the status for the job being monitored.
     *
     * @return mixed False if the status is not being monitored, otherwise the status as
     *  as an integer, based on the Resque_Job_Status constants.
     */
    public function get()
    {
        $statusPacket = json_decode(Resque::redis()->get((string)$this), true);
        if(!$statusPacket) {
            return false;
        }
        return $statusPacket['message'];
    }
    
    public function getAll()
    {
        $statusPacket = json_decode(Resque::redis()->get((string)$this), true);
        if(!$statusPacket) {
            return false;
        }
        return $statusPacket;
    }

    /**
     * Generate a string representation of this object.
     *
     * @return string String representation of the current job status class.
     */
    public function __toString()
    {
        return 'job:' . $this->id . ':message';
    }
}
?>