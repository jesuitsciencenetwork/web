<?php

namespace AppBundle\Exception;

/**
 * No query given
 */
class EmptyQueryException extends QueryException
{
    /**
     * EmptyQueryException constructor.
     */
    public function __construct()
    {
        parent::__construct('No query given');
    }
}
