<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    public function testCSRFTokenGeneration()
    {
        $token1 = Security::generateCSRFToken();
        $token2 = Security::generateCSRFToken();
        
        $this->assertNotEmpty($token1);
        $this->assertNotEmpty($token2);
        $this->assertEquals($token1, $token2); // Should be same in same session
        $this->assertEquals(64, strlen($token1)); // 32 bytes = 64 hex chars
    }
    
    public function testCSRFTokenValidation()
    {
        $token = Security::generateCSRFToken();
        
        $this->assertTrue(Security::validateCSRFToken($token));
        $this->assertFalse(Security::validateCSRFToken('invalid_token'));
        $this->assertFalse(Security::validateCSRFToken(''));
    }
    
    public function testPasswordValidation()
    {
        // Valid passwords
        $this->assertEmpty(Security::validatePassword('StrongPass123!'));
        $this->assertEmpty(Security::validatePassword('MySecure@Pass2024'));
        
        // Invalid passwords
        $this->assertNotEmpty(Security::validatePassword('weak')); // Too short
        $this->assertNotEmpty(Security::validatePassword('nouppercase123!')); // No uppercase
        $this->assertNotEmpty(Security::validatePassword('NOLOWERCASE123!')); // No lowercase
        $this->assertNotEmpty(Security::validatePassword('NoNumbers!')); // No numbers
        $this->assertNotEmpty(Security::validatePassword('NoSpecialChars123')); // No special chars
    }
    
    public function testEmailValidation()
    {
        // Configure test domain
        Config::set('school.email_domain', '@school.edu');
        
        // Valid emails
        $this->assertEmpty(Security::validateEmail('student@school.edu'));
        $this->assertEmpty(Security::validateEmail('john.doe@school.edu'));
        
        // Invalid emails
        $this->assertNotEmpty(Security::validateEmail('invalid-email'));
        $this->assertNotEmpty(Security::validateEmail('student@gmail.com')); // Wrong domain
        $this->assertNotEmpty(Security::validateEmail('')); // Empty
        $this->assertNotEmpty(Security::validateEmail('student@wrongschool.edu')); // Wrong domain
    }
    
    public function testInputSanitization()
    {
        $input = '<script>alert("xss")</script>Hello & World';
        $sanitized = Security::sanitizeInput($input);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('&lt;script&gt;', $sanitized);
        $this->assertStringContainsString('&amp;', $sanitized);
    }
    
    public function testFileSize()
    {
        $this->assertEquals('1 KB', Security::formatBytes(1024));
        $this->assertEquals('1 MB', Security::formatBytes(1024 * 1024));
        $this->assertEquals('1.5 MB', Security::formatBytes(1024 * 1024 * 1.5));
        $this->assertEquals('500 B', Security::formatBytes(500));
    }
    
    public function testSecureFilenameGeneration()
    {
        $filename1 = Security::generateSecureFilename('test.jpg');
        $filename2 = Security::generateSecureFilename('test.jpg');
        
        $this->assertNotEquals($filename1, $filename2);
        $this->assertStringEndsWith('.jpg', $filename1);
        $this->assertStringEndsWith('.jpg', $filename2);
        $this->assertEquals(36, strlen($filename1)); // 32 chars + .jpg
    }
    

}