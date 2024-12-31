<?php

// check_status.php
// This is the file that should be run by cron
require_once 'config.php';
require_once 'functions.php';

writeLog("Starting status check...");

// Read last status
$lastStatusFile = 'last_status.txt';
$lastStatus = file_exists($lastStatusFile) ? file_get_contents($lastStatusFile) : '';
writeLog("Last recorded status: " . ($lastStatus ?: 'none'));

// Get current status
$currentStatus = getAccountStatus(ACCOUNT_NAME);
writeLog("Current status check result: " . json_encode($currentStatus));

// Important: Compare only the status text
if ($currentStatus['status'] != 'error') {
    if (!file_exists($lastStatusFile) || $currentStatus['status'] !== $lastStatus) {
        writeLog("Status change detected!");
        
        $subscribers = getSubscribers();
        writeLog("Found " . count($subscribers) . " subscribers");
        
        foreach ($subscribers as $chatId) {
            writeLog("Sending update to subscriber: " . $chatId);
            
            // Send status image first
            if ($currentStatus['url']) {
                $photoResult = sendTelegramPhoto($chatId, $currentStatus['url']);
                writeLog("Photo send result: " . ($photoResult ? 'success' : 'failed'));
            }
            
            // Then send text message
            $message = "🔄 Status Change Detected!\n" .
                      "Name: " . ACCOUNT_NAME . "\n" .
                      "New Status: " . $currentStatus['status'] . "\n" .
                      "Time: " . date('Y-m-d H:i:s');
            
            $messageResult = sendTelegramMessage($chatId, $message);
            writeLog("Message send result: " . ($messageResult ? 'success' : 'failed'));
        }
        
        // Save new status only after successful notifications
        file_put_contents($lastStatusFile, $currentStatus['status']);
        writeLog("New status saved to file");
    } else {
        writeLog("No status change detected");
    }
} else {
    writeLog("Error getting status - skipping notifications");
}

writeLog("Status check completed\n");
