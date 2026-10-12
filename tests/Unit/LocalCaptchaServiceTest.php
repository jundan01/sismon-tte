<?php

namespace Tests\Unit;

use App\Services\LocalCaptchaService;
use Tests\TestCase;

class LocalCaptchaServiceTest extends TestCase
{
    protected LocalCaptchaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LocalCaptchaService();
    }

    public function test_can_generate_math_challenge_with_signature(): void
    {
        $challenge = $this->service->generate();

        $this->assertArrayHasKey('question', $challenge);
        $this->assertArrayHasKey('signature', $challenge);
        $this->assertStringContainsString('Berapa hasil dari', $challenge['question']);
        $this->assertNotEmpty($challenge['signature']);
    }

    public function test_verifies_correct_answer_and_rejects_wrong_answer(): void
    {
        $challenge = $this->service->generate();

        // Ekstrak pertanyaan untuk mencari tahu jawaban yang tepat
        preg_match('/(\d+)\s+([\+\-])\s+(\d+)/', $challenge['question'], $matches);
        $this->assertCount(4, $matches);

        $num1 = (int)$matches[1];
        $op = $matches[2];
        $num2 = (int)$matches[3];

        $correctAnswer = $op === '+' ? ($num1 + $num2) : ($num1 - $num2);

        // Uji jawaban benar
        $this->assertTrue($this->service->verify((string)$correctAnswer, $challenge['signature']));

        // Uji jawaban salah
        $this->assertFalse($this->service->verify((string)($correctAnswer + 99), $challenge['signature']));
        $this->assertFalse($this->service->verify('invalid', $challenge['signature']));
        $this->assertFalse($this->service->verify(null, $challenge['signature']));
        $this->assertFalse($this->service->verify((string)$correctAnswer, null));
    }
}
