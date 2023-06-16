<?php

namespace App\Utils;

use App\Application;
use Laminas\Session\Container;

/**
 * Various Miscellanous Utilities
 */
class Generic
{
    public static function getAppUrl($path)
    {
        $baseUrl = Application::baseUrl();

        $path = trim($path);
        $path = preg_replace('/^\//','',$path);
        
        return filter_var($baseUrl.'/'.$path,FILTER_SANITIZE_URL);
    }

    public static function validateEmail(string $email)
    {
        if(empty($email)){
            throw new \InvalidArgumentException('Email is empty');
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE?false:true;
    }

    /**
     * Calculate how many pages the records will fit in.
     *
     * @param integer $records_per_page  How many records a single page will contain
     * @param integer $record_numbers How many records in total you have
     * @return integer
     */
    public static function calculateNumberOfPages(int $records_per_page, int $total_records_numbers):int
    {
        return (int)ceil((float)$records_per_page/(float)$total_records_numbers);
    }

    /**
     * Calculate Pagination Offset
     *
     * @param int $page The page Number If negative or 1 is assumed
     * @param int $records_per_page How may records need to be returend per page
     *  
     * @return int The pagination offset
     * @throws \InvalidArgumentException If page < 0
     */
    public static function calculateOffset(int $page, int $records_per_page)
    {
        $page = ($page<=0)?1:$page;
        return ($page - 1)*$records_per_page;
    }
}