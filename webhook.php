<?php

require_once 'config.php';
require_once 'functions.php';

$update = json_decode(file_get_contents('php://input'), true);

if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    
    if (strpos($text, '/start') === 0) {
        addSubscriber($chatId);
        sendTelegramMessage($chatId, "Welcome! You are now subscribed to status updates.\n\nUse /status to check current status\nUse /stop to unsubscribe");
    }
    elseif (strpos($text, '/stop') === 0) {
        removeSubscriber($chatId);
        sendTelegramMessage($chatId, "You have been unsubscribed from status updates.");
    }
    elseif (strpos($text, '/status') === 0) {
        $status = getAccountStatus(ACCOUNT_NAME);
        
        if ($status['status'] !== 'error') {
            if ($status['url']) {
                sendTelegramPhoto($chatId, $status['url']);
            }
            sendTelegramMessage($chatId, "Name: " . ACCOUNT_NAME . "\nStatus: " . $status['status']);
        } else {
            sendTelegramMessage($chatId, "Sorry, I couldn't check the status at the moment. Please try again later.");
        }
    }
    elseif (strpos($text, '/help') === 0) {
        $helpText = "Available commands:\n" .
                   "/start - Subscribe to status updates\n" .
                   "/stop - Unsubscribe from updates\n" .
                   "/status - Check current status\n" .
                   "/help - Show this help message";
        sendTelegramMessage($chatId, $helpText);
    }
}

http_response_code(200);
