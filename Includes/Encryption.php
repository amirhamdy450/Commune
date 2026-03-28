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


// Derive a fixed 32-byte key from the configured key string
define('ENCRYPTION_KEY_DERIVED', hash('sha256', ENCRYPTION_KEY, true));

//ENCRYPTION
//POSITIONED => D{timestamp}I{id}, encrypted with AES-256-CBC + random IV
//Ciphertext format: base64( random_iv(16 bytes) + aes_ciphertext )
function Encrypt($Target, $Type = "Positioned", $parameters = []) {

    try {
        CheckEncryptionParams($Type, $parameters);

        switch ($Type) {
            case "Positioned":
                $Timestamp = $parameters["Timestamp"];
                $FormatedTarget = 'D' . $Timestamp . 'I' . $Target;

                $IV = random_bytes(16); // Random IV per encryption
                $Ciphertext = openssl_encrypt($FormatedTarget, 'aes-256-cbc', ENCRYPTION_KEY_DERIVED, OPENSSL_RAW_DATA, $IV);
                if ($Ciphertext === false) {
                    throw new Exception("Encryption failed: openssl error.");
                }

                return base64_encode($IV . $Ciphertext);

            default:
                error_log("[ENCRYPTION] Unhandled encryption type '$Type'.");
                throw new Exception("Encryption failed: unsupported encryption type.");
        }

    } catch (Exception $e) {
        return "Something went wrong with the encryption.";
    }
}


function Decrypt($EncryptedData, $Type = "Positioned") {
    try {
        switch ($Type) {
            case "Positioned":
                $decoded = base64_decode($EncryptedData, true);
                if ($decoded === false || strlen($decoded) <= 16) {
                    error_log("[DECRYPTION ERROR] Base64 decode failed or data too short for type 'Positioned'.");
                    throw new Exception("Decryption failed.");
                }

                // First 16 bytes are the IV, the rest is the ciphertext
                $IV         = substr($decoded, 0, 16);
                $Ciphertext = substr($decoded, 16);

                $decrypted = openssl_decrypt($Ciphertext, 'aes-256-cbc', ENCRYPTION_KEY_DERIVED, OPENSSL_RAW_DATA, $IV);
                if ($decrypted === false) {
                    error_log("[DECRYPTION ERROR] OpenSSL decryption failed for 'Positioned'.");
                    throw new Exception("Decryption failed.");
                }

                // Expected format: D{Timestamp}I{Target}
                if (!preg_match('/^D(\d+)I(.+)$/', $decrypted, $matches)) {
                    error_log("[DECRYPTION ERROR] Invalid format after decryption for 'Positioned'. Got: $decrypted");
                    throw new Exception("Decryption failed.");
                }

                return $matches[2];

            default:
                error_log("[DECRYPTION] Unhandled decryption type '$Type'.");
                throw new Exception("Decryption failed: unsupported encryption type.");
        }

    } catch (Exception $e) {
        return "Something went wrong with the decryption.";
    }
}




?>