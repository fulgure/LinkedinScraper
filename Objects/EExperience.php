<?php

class EExperience{

    /**
     * The company ID
     *
     * @var int
     */
    public $companyID;

    /**
     * The contact's ID
     *
     * @var int
     */
    public $contactID;

    /**
     * The job title
     *
     * @var string
     */
    public $title;

    /**
     * The job's region
     *
     * @var string
     */
    public $region;

    /**
     * The job's description
     *
     * @var string
     */
    public $description;

    /**
     * The timeframe
     *
     * @var ETimeFrame
     */
    public $timeFrame;

    /**
     * Whether it's the contact's current job
     *
     * @var bool
     */
    public $isCurrent;

    public function __construct($companyID, $contactID, $timeFrame, $title = "", $region = "", $description = "", $isCurrent = false){
        $this->companyID = $companyID;
        $this->contactID = $contactID;
        $this->timeFrame = $timeFrame;
        /* We don't care if some fields are empty, so no need to check if optional parameters are set */
        $this->title = $title;
        $this->region = $region;
        $this->description = $description;
        $this->isCurrent = $isCurrent;

    }
}