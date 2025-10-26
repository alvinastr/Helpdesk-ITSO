<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Format date to Indonesian format
     */
    public static function formatDateIndonesian($date, $format = 'd F Y H:i')
    {
        if (!$date) return '-';
        
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        
        $carbon = Carbon::parse($date);
        $formatted = $carbon->format($format);
        
        // Replace month names
        foreach ($months as $num => $name) {
            $formatted = str_replace($carbon->format('F'), $name, $formatted);
            $formatted = str_replace($carbon->format('M'), substr($name, 0, 3), $formatted);
        }
        
        // Replace day names
        foreach ($days as $eng => $indo) {
            $formatted = str_replace($eng, $indo, $formatted);
        }
        
        return $formatted . ' WIB';
    }
    
    /**
     * Get difference for humans in Indonesian
     */
    public static function diffForHumansIndonesian($date)
    {
        if (!$date) return '-';
        
        $carbon = Carbon::parse($date);
        $now = Carbon::now();
        
        $diff = $carbon->diffInSeconds($now);
        
        if ($diff < 60) {
            return 'baru saja';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' menit yang lalu';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' jam yang lalu';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' hari yang lalu';
        } elseif ($diff < 2419200) {
            $weeks = floor($diff / 604800);
            return $weeks . ' minggu yang lalu';
        } elseif ($diff < 29030400) {
            $months = floor($diff / 2419200);
            return $months . ' bulan yang lalu';
        } else {
            $years = floor($diff / 29030400);
            return $years . ' tahun yang lalu';
        }
    }
}