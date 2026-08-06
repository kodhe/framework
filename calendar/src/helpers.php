<?php


if (!function_exists('days_in_month')) {
    /**
     * Days in Month
     *
     * Returns the number of days in a given month and year,
     * taking leap years into account.
     *
     * @param int $month Numeric month (1-12)
     * @param int $year Numeric year
     * @return int
     */
    function days_in_month($month, $year)
    {
        if ($month < 1 || $month > 12) {
            return 0;
        }

        $days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        if ($month == 2) {
            // Leap year check
            if ((($year % 4 == 0) && ($year % 100 != 0)) || ($year % 400 == 0)) {
                return 29;
            }
        }

        return $days_in_month[$month - 1];
    }
}

if (!function_exists('kodhe')) {
    function kodhe()
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new class {
                public $load;
                public $lang;
                public $config;
                public $router;

                public function __construct()
                {
                    $this->load = new class {
                        public function helper($name) {}
                        public function language($name) {}
                    };

                    $this->lang = new class {
                        public function load($name) {}
                        
                        public function line($key)
                        {
                            // Built-in language lines for calendar
                            static $language = null;

                            if ($language === null) {
                                $language = [
                                    // Long month names
                                    'cal_january'   => 'January',
                                    'cal_february'  => 'February',
                                    'cal_march'     => 'March',
                                    'cal_april'     => 'April',
                                    'cal_mayl'      => 'May',
                                    'cal_june'      => 'June',
                                    'cal_july'      => 'July',
                                    'cal_august'    => 'August',
                                    'cal_september' => 'September',
                                    'cal_october'   => 'October',
                                    'cal_november'  => 'November',
                                    'cal_december'  => 'December',

                                    // Short month names
                                    'cal_jan' => 'Jan',
                                    'cal_feb' => 'Feb',
                                    'cal_mar' => 'Mar',
                                    'cal_apr' => 'Apr',
                                    'cal_may' => 'May',
                                    'cal_jun' => 'Jun',
                                    'cal_jul' => 'Jul',
                                    'cal_aug' => 'Aug',
                                    'cal_sep' => 'Sep',
                                    'cal_oct' => 'Oct',
                                    'cal_nov' => 'Nov',
                                    'cal_dec' => 'Dec',

                                    // Long day names
                                    'cal_sunday'    => 'Sunday',
                                    'cal_monday'    => 'Monday',
                                    'cal_tuesday'   => 'Tuesday',
                                    'cal_wednesday' => 'Wednesday',
                                    'cal_thursday'  => 'Thursday',
                                    'cal_friday'    => 'Friday',
                                    'cal_saturday'  => 'Saturday',

                                    // Short day names
                                    'cal_sun' => 'Sun',
                                    'cal_mon' => 'Mon',
                                    'cal_tue' => 'Tue',
                                    'cal_wed' => 'Wed',
                                    'cal_thu' => 'Thu',
                                    'cal_fri' => 'Fri',
                                    'cal_sat' => 'Sat',

                                    // Abbreviated day names
                                    'cal_su' => 'Su',
                                    'cal_mo' => 'Mo',
                                    'cal_tu' => 'Tu',
                                    'cal_we' => 'We',
                                    'cal_th' => 'Th',
                                    'cal_fr' => 'Fr',
                                    'cal_sa' => 'Sa',
                                ];
                            }

                            return isset($language[$key]) ? $language[$key] : false;
                        }
                    };

                    $this->config = new class {
                        public function site_url($uri = '')
                        {
                            $base_url = isset($_SERVER['HTTP_HOST']) 
                                ? 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') 
                                    . '://' . $_SERVER['HTTP_HOST'] . '/'
                                : '/';
                            return rtrim($base_url, '/') . '/' . ltrim($uri, '/');
                        }
                    };

                    $this->router = new class {
                        public $class = 'calendar';
                        public $method = 'index';
                    };
                }
            };
        }

        return $instance;
    }
}