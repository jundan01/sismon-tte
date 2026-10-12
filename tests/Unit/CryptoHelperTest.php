<?php

namespace Tests\Unit;

use App\Services\Security\CryptoHelper;
use Tests\TestCase;

class CryptoHelperTest extends TestCase
{
    public function test_can_encrypt_and_decrypt_sensitive_field(): void
    {
        $plaintext = '3274010101900001'; // Contoh NIK ASN
        
        $ciphertext = CryptoHelper::encryptField($plaintext);
        
        $this->assertNotNull($ciphertext);
        $this->assertNotEquals($plaintext, $ciphertext);
        
        $decrypted = CryptoHelper::decryptField($ciphertext);
        $this->assertEquals($plaintext, $decrypted);
    }

    public function test_generates_deterministic_blind_index(): void
    {
        $nik = '3274010101900001';
        
        $hash1 = CryptoHelper::generateBlindIndex($nik);
        $hash2 = CryptoHelper::generateBlindIndex($nik);
        $hashDifferent = CryptoHelper::generateBlindIndex('3274010101900002');
        
        $this->assertNotNull($hash1);
        $this->assertEquals(64, strlen($hash1)); // SHA-256 hex string length
        $this->assertEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hashDifferent);
    }

    public function test_can_calculate_and_verify_file_checksum(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'tte_test_');
        file_put_contents($tempFile, 'DOKUMEN_SK_PELANTIKAN_ASN_2026');
        
        $expectedChecksum = hash('sha256', 'DOKUMEN_SK_PELANTIKAN_ASN_2026');
        
        $calculated = CryptoHelper::calculateFileChecksum($tempFile);
        $this->assertEquals($expectedChecksum, $calculated);
        
        $isValid = CryptoHelper::verifyFileChecksum($tempFile, $expectedChecksum);
        $this->assertTrue($isValid);
        
        $isInvalid = CryptoHelper::verifyFileChecksum($tempFile, 'invalid_checksum_string_123456');
        $this->assertFalse($isInvalid);
        
        @unlink($tempFile);
    }

    public function test_signed_payload_creation_and_extraction(): void
    {
        $payload = ['user_id' => 10, 'action' => 'VERIFIKASI_EMAIL'];
        
        $token = CryptoHelper::createSignedPayload($payload, 300);
        $this->assertNotNull($token);
        
        $extracted = CryptoHelper::extractSignedPayload($token);
        $this->assertIsArray($extracted);
        $this->assertEquals(10, $extracted['user_id']);
        $this->assertEquals('VERIFIKASI_EMAIL', $extracted['action']);
    }
}
