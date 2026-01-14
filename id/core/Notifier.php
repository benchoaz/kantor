<?php
namespace App\Core;

class Notifier {
    private $telegramBotToken;
    private $telegramChatId;
    private $logFile;

    public function __construct() {
        // Hardcoded configuration for now, ideally moved to config/app.php
        $this->telegramBotToken = '7479639019:AAEt_2b1--x44-2321323'; // Placeholder
        $this->telegramChatId = '-100234234234'; // Placeholder
        $this->logFile = __DIR__ . '/../storage/logs/security.log';
        
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * Send an alert message
     * @param string $title
     * @param string $message
     * @param string $level (info, warning, critical)
     */
    public function alert($title, $message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [$level] $title: $message";

        // 1. Write to Log File
        file_put_contents($this->logFile, $formattedMessage . PHP_EOL, FILE_APPEND);

        // 2. Send to Telegram (if critical or warning)
        if ($level === 'critical' || $level === 'warning') {
            $this->sendToTelegram("🚨 *[$level] $title*\n\n$message");
        }
    }

    private function sendToTelegram($text) {
        if (empty($this->telegramBotToken) || empty($this->telegramChatId)) {
            return;
        }

        $url = "https://api.telegram.org/bot{$this->telegramBotToken}/sendMessage";
        $data = [
            'chat_id' => $this->telegramChatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ];

        // Use curl to send async-ish (timeout 1s)
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Short timeout to not block app
        curl_exec($ch);
        curl_close($ch);
    }
}
