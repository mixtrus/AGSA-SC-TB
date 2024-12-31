<?php
class Logger {
    public static function write($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents(LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
    }
}