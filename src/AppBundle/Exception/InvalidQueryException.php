<?php

namespace AppBundle\Exception;

/**
 * Invalid query given
 */
class InvalidQueryException extends QueryException
{
    /**
     * InvalidQueryException constructor.
     */
    public function __construct()
    {
        parent::__construct('Could not understand query');
    }
}
