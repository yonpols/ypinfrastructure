<?php
    class YPILogger
    {
        private static $log = array();
        private static $logFileName = null;

        private static $colors = array('ERROR' => 31, 'INFO' => 32, 'NOTICE' => 33, 'DEBUG' => 36);

        public static function initialize()
        {
            self::$logFileName = LOG_PATH.DIRECTORY_SEPARATOR.sprintf('ypinfrastructure-%s.log', date('Y-m'));

            if (count(self::$log))
            {
                $fd = fopen(self::$logFileName, "a");
                foreach(self::$log as $log)
                    fwrite($fd, $log);
                fclose($fd);
                self::$log = array();
            }
        }

        public static function finalize() { }

        public static function log($type, $log)
        {
            if (strpos($type, ':') !== false)
                list($type, $subtype) = explode(':', $type);
            else
                $subtype = 'LOG';

            $text = sprintf("[%s] \x1B[1;%d;1m%s:%s\x1B[0;0;0m %s\n", strftime('%F %T'), self::getColor($type), $type, $subtype, $log);

            if (self::$logFileName)
            {
                $fd = fopen(self::$logFileName, "a");
                fwrite($fd, $text);
                fclose($fd);
            } else
                self::$log[] = $text;
        }

        private static function getColor($type)
        {
            if (isset(self::$colors[$type]))
                return self::$colors[$type];
            else
                return 0;
        }
    }
?>
