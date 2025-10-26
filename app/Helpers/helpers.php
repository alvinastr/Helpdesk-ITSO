<?php

// Global helper functions for Indonesian date formatting
// This file is loaded through composer.json files autoload

if (!function_exists('formatDateIndonesian')) {
    /**
     * Format date to Indonesian format
     * @param mixed $date
     * @param string $format
     * @return string
     */
    function formatDateIndonesian($date, $format = 'd F Y H:i')
    {
        return \App\Helpers\DateHelper::formatDateIndonesian($date, $format);
    }
}

if (!function_exists('diffForHumansIndonesian')) {
    /**
     * Get human readable time difference in Indonesian
     * @param mixed $date
     * @return string
     */
    function diffForHumansIndonesian($date)
    {
        return \App\Helpers\DateHelper::diffForHumansIndonesian($date);
    }
}