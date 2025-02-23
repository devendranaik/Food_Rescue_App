<?php
class Logger {
    private static $logDir = __DIR__ . "/../logs"; // Log folder outside includes

    // Get log file for today
    private static function getLogFile() {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0777, true); // Create logs folder if not exists
        }

        $date = date("Y-m-d");
        return self::$logDir . "/app_$date.log";
    }

    // Write log message
    public static function log($message, $level = "INFO") {
        $logFile = self::getLogFile();
        $logMessage = "[" . date("Y-m-d H:i:s") . "] [$level] $message" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
