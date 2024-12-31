<?php

function writeLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
}

function removeSubscriber($chatId) {
    $subscribers = getSubscribers();
    $subscribers = array_diff($subscribers, [$chatId]);
    file_put_contents(SUBSCRIBERS_FILE, json_encode(array_values($subscribers)));
}

function getAccountStatus($accountName) {
    $imageUrl = "https://agsa.arsacia.ir/players/bars/{$accountName}.png";
    
    try {
        $imageData = file_get_contents($imageUrl);
        if ($imageData === false) {
            throw new Exception("Failed to download image");
        }
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'status_');
        file_put_contents($tmpFile, $imageData);
        
        $image = imagecreatefrompng($tmpFile);
        if (!$image) {
            throw new Exception("Failed to create image resource");
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $colors = imagecolorsforindex($image, $rgb);
                
                if ($colors['red'] == 40 && $colors['green'] == 173 && $colors['blue'] == 16) {
                    return ['status' => 'Online 🟢', 'url' => $imageUrl];
                }
                if ($colors['red'] == 188 && $colors['green'] == 115 && $colors['blue'] == 14) {
                    return ['status' => 'Sleep 🟠', 'url' => $imageUrl];
                }
            }
        }
        
        return ['status' => 'Offline 🔴', 'url' => $imageUrl];
    } catch (Exception $e) {
        writeLog("Error in getAccountStatus: " . $e->getMessage());
        return ['status' => 'error', 'url' => null];
    } finally {
        if (isset($tmpFile) && file_exists($tmpFile)) {
            unlink($tmpFile);
        }
        if (isset($image)) {
            imagedestroy($image);
        }
    }
}

function sendTelegramMessage($chatId, $message) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        writeLog("Failed to send message to Telegram");
        return false;
    }
    return true;
}

function sendTelegramPhoto($chatId, $photoUrl) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto";
    $data = [
        'chat_id' => $chatId,
        'photo' => $photoUrl
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        writeLog("Failed to send photo to Telegram");
        return false;
    }
    return true;
}

function getSubscribers() {
    if (file_exists(SUBSCRIBERS_FILE)) {
        $data = file_get_contents(SUBSCRIBERS_FILE);
        $subscribers = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($subscribers)) {
            return array_values(array_unique($subscribers));
        }
        writeLog("Error reading subscribers file: " . json_last_error_msg());
    }
    writeLog("No subscribers file found or empty");
    return [];
}

function addSubscriber($chatId) {
    $subscribers = getSubscribers();
    if (!in_array($chatId, $subscribers)) {
        $subscribers[] = $chatId;
        $success = file_put_contents(SUBSCRIBERS_FILE, json_encode(array_values($subscribers)));
        writeLog("Added subscriber " . $chatId . " - Success: " . ($success !== false ? 'yes' : 'no'));
        return $success !== false;
    }
    writeLog("Subscriber " . $chatId . " already exists");
    return true;
}
