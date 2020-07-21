<?php
/**
 * Represents a Time frame
 * All the fields are strings because we don't parse the dates we get from linkedin
 */
class ETimeFrame{
    
    /**
     * When the job started
     *
     * @var string
     */
    public $start;

    /**
     * When the job ended
     *
     * @var string
     */
    public $end;

    /**
     * How long was the person employed
     *
     * @var string
     */
    public $length;
    
    public function __construct($start, $end,$len)
    {
        $this->start = $start;
        $this->end = $end;
        $this->length = $len;
    }
}