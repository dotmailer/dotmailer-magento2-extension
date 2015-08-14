<?php

class Dotdigitalgroup_Email_Model_Customer_Review_Rating
{
    /**
     * @var int
     */
    public $rating_score;

    /**
     * constructor
     *
     * @param $rating
     */
    public function __construct($rating)
    {
        $this->setRatingScore($rating->getValue());
    }

    /**
     * @param $score
     *
     * @return $this
     */
    public function setRatingScore($score)
    {
        $this->rating_score = (int)$score;
        return $this;
    }

    /**
     * @return int
     */
    public function getRatingScore()
    {
        return $this->rating_score;
    }

    /**
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }
}