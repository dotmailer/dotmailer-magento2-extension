<?php

namespace Dotdigitalgroup\Email\Model\Customer\Review;

class Rating
{

    /**
     * @var int
     */
    public $rating_score;


    public function setRating($rating)
    {
        $this->setRatingScore($rating->getValue());

        return $this;
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