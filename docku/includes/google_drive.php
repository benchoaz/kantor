<?php
// includes/google_drive.php

class GoogleDriveHelper {
    private $accessToken;
    private $folderId;

    public function __construct($accessToken, $folderId = null) {
        $this->accessToken = $accessToken;
        $this->folderId = $folderId;
    }

    public function uploadFile($filePath, $fileName, $mimeType) {
        $metadata = [
            'name' => $fileName,
            'parents' => $this->folderId ? [$this->folderId] : []
        ];

        $boundary = "-------" . md5(time());
        $content = "--$boundary\r\n";
        $content .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $content .= json_encode($metadata) . "\r\n";
        $content .= "--$boundary\r\n";
        $content .= "Content-Type: $mimeType\r\n\r\n";
        $content .= file_get_contents($filePath) . "\r\n";
        $content .= "--$boundary--";

        $ch = curl_init("https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->accessToken,
            "Content-Type: multipart/related; boundary=$boundary",
            "Content-Length: " . strlen($content)
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $status,
            'response' => json_decode($response, true)
        ];
    }
}
?>
