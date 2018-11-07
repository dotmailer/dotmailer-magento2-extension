<?php

namespace Dotdigitalgroup\Email\Model\Customer\Review;

/**
 * Customer review rating data.
 *
 */
class Rating
{
    /**
     * @var int
     */
    public $ratingScore;

    /**
     * @param mixed $rating
     *
     * @return $this
     */
    public function setRating($rating)
    {
        $this->setRatingScore($rating->getValue());

        return $this;
    }

    /**
     * @param int $score
     *
     * @return $this
     */
    public function setRatingScore($score)
    {
        $this->ratingScore = (int)$score;

        return $this;
    }

    /**
     * @return int
     */
    public function getRatingScore()
    {
        return $this->ratingScore;
    }

    /**
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }
}
