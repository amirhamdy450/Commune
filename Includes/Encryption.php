<?php
//requirements besides the target that will be encrypted
$ENC_TYPE_REQUIREMENTS=[
    "Positioned"=>["Timestamp"]
];

function CheckEncryptionParams($Type, $parameters) {
    global $ENC_TYPE_REQUIREMENTS;

    // No requirements defined for this type
    if (!isset($ENC_TYPE_REQUIREMENTS[$Type])) {
        error_log("[ENCRYPTION] Unknown encryption type '$Type' encountered.");
        return false;
    }

    $required = $ENC_TYPE_REQUIREMENTS[$Type];
    $missing = [];

    foreach ($required as $key) {
        if (!array_key_exists($key, $parameters) || $parameters[$key] === null) {
            $missing[] = $key;
        }
    }

    if (!empty($missing)) {
        $msg = "[ENCRYPTION ERROR] Missing required parameters for type '$Type': " . implode(", ", $missing);
        error_log($msg);
        throw new Exception("Encryption failed: developer missing required data.");
    }

    return true;
}


//ENCRYPTION
//POSITIONED => D12234414I5     I is the targeted id
function Encrypt($Target,$Type="Positioned" ,$parameters=[]){
    
    try{
        CheckEncryptionParams($Type,$parameters);

        switch($Type){
            case "Positioned":
                $Timestamp=$parameters["Timestamp"];
                $FormatedTarget='D'.$Timestamp.'I'.$Target;

                $Target_Encrypted=base64_encode(openssl_encrypt($FormatedTarget, 'aes-256-cbc', ENCRYPTION_KEY, OPENSSL_RAW_DATA, ENCRYPTION_IV));
                return $Target_Encrypted;
        
            default:
                error_log("[ENCRYPTION] Unhandled encryption type '$Type'.");
                throw new Exception("Encryption failed: unsupported encryption type.");
                
        }
    }catch (Exception $e) {
        // Controlled message for user-facing logic
        return "Something went wrong with the encryption.";
    }

}


function Decrypt($EncryptedData, $Type = "Positioned") {
    try {
        switch ($Type) {
            case "Positioned":
                $decoded = base64_decode($EncryptedData, true);
                if ($decoded === false) {
                    error_log("[DECRYPTION ERROR] Base64 decode failed for type 'Positioned'.");
                    throw new Exception("Decryption failed.");
                }

                $decrypted = openssl_decrypt($decoded, 'aes-256-cbc', ENCRYPTION_KEY, OPENSSL_RAW_DATA, ENCRYPTION_IV);
                if ($decrypted === false) {
                    error_log("[DECRYPTION ERROR] OpenSSL decryption failed for 'Positioned'.");
                    throw new Exception("Decryption failed.");
                }

                // Expected format: D{Timestamp}I{Target}
                if (!preg_match('/^D(\d+)I(.+)$/', $decrypted, $matches)) {
                    error_log("[DECRYPTION ERROR] Invalid format after decryption for 'Positioned'. Got: $decrypted");
                    throw new Exception("Decryption failed.");
                }

                return $matches[2]; // Return the target only (the core data)



            default:
                error_log("[DECRYPTION] Unhandled decryption type '$Type'.");
                throw new Exception("Decryption failed: unsupported encryption type.");
        }

    } catch (Exception $e) {
        return "Something went wrong with the decryption.";
    }
}




?>