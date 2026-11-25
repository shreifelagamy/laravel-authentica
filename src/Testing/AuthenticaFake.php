<?php

namespace Shreifelagamy\Authentica\Testing;

use Closure;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;
use Shreifelagamy\Authentica\Contracts\Authentica;
use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Http\ApiResponse;
use Shreifelagamy\Authentica\Support\PayloadBuilder;

/**
 * Stands in for the client in tests (Authentica::fake()): records what would have been sent, with the same number
 * checks and defaults as the real client, and sends nothing.
 */
class AuthenticaFake implements Authentica
{
    /** @var array<int, SentMessage> */
    private array $sent = [];

    /** @var array<string, string> the last code sent to each recipient */
    private array $codes = [];

    /** @var (Closure(string, string): bool)|null */
    private ?Closure $verifier = null;

    public function __construct(
        private readonly PayloadBuilder $payloads,
        private int $balance = 100,
    ) {}

    public function sendSms(string $phone, string $message, ?string $sender = null): ApiResponse
    {
        $this->sent[] = SentMessage::sms($this->payloads->sms($phone, $message, $sender));

        return new ApiResponse(200, ['success' => true, 'data' => null, 'message' => 'SMS sent successfully']);
    }

    public function sendOtp(
        string $recipient,
        ?string $code = null,
        OtpMethod|string|null $method = null,
        int|string|null $template = null,
        ?string $fallbackPhone = null,
        ?string $fallbackEmail = null,
    ): ApiResponse {
        $message = SentMessage::otp($this->payloads->otp($recipient, $code, $method, $template, $fallbackPhone, $fallbackEmail));

        $this->sent[] = $message;
        $this->codes[$message->to] = $message->code ?? '000000';

        return new ApiResponse(200, ['success' => true, 'data' => null, 'message' => 'OTP send successfully']);
    }

    /**
     * True when the code matches the last code sent to the recipient ("000000" when Authentica would have made
     * it), unless verifyUsing() says otherwise.
     */
    public function verifyOtp(string $recipient, string $code): bool
    {
        $payload = $this->payloads->verify($recipient, $code);
        $recipient = $payload['phone'] ?? $payload['email'] ?? $recipient;

        if ($this->verifier !== null) {
            return ($this->verifier)($recipient, $code);
        }

        return isset($this->codes[$recipient]) && hash_equals($this->codes[$recipient], $code);
    }

    public function balance(): int
    {
        return $this->balance;
    }

    public function setBalance(int $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @param  Closure(string $recipient, string $code): bool  $verifier
     */
    public function verifyUsing(Closure $verifier): static
    {
        $this->verifier = $verifier;

        return $this;
    }

    /**
     * @return Collection<int, SentMessage>
     */
    public function sent(?Closure $filter = null): Collection
    {
        $sent = collect($this->sent);

        return $filter === null ? $sent : $sent->filter($filter)->values();
    }

    /**
     * @param  (Closure(SentMessage): bool)|null  $filter
     */
    public function assertSmsSent(?Closure $filter = null, ?int $times = null): void
    {
        $this->assertSentOfType('sms', 'SMS', $filter, $times);
    }

    /**
     * @param  (Closure(SentMessage): bool)|null  $filter
     */
    public function assertSmsNotSent(?Closure $filter = null): void
    {
        $this->assertNotSentOfType('sms', 'SMS', $filter);
    }

    /**
     * @param  (Closure(SentMessage): bool)|null  $filter
     */
    public function assertOtpSent(?Closure $filter = null, ?int $times = null): void
    {
        $this->assertSentOfType('otp', 'one-time code', $filter, $times);
    }

    /**
     * @param  (Closure(SentMessage): bool)|null  $filter
     */
    public function assertOtpNotSent(?Closure $filter = null): void
    {
        $this->assertNotSentOfType('otp', 'one-time code', $filter);
    }

    public function assertSentTo(string $recipient): void
    {
        $recipient = $this->normalizedRecipient($recipient);

        PHPUnit::assertTrue(
            $this->sent(fn (SentMessage $message): bool => $message->to === $recipient)->isNotEmpty(),
            "Nothing was sent to [{$recipient}] through Authentica.",
        );
    }

    public function assertSentCount(int $count): void
    {
        PHPUnit::assertCount($count, $this->sent, sprintf(
            'Expected %d Authentica messages, %d were sent.',
            $count,
            count($this->sent),
        ));
    }

    public function assertNothingSent(): void
    {
        PHPUnit::assertEmpty($this->sent, sprintf('%d unexpected Authentica messages were sent.', count($this->sent)));
    }

    /**
     * @param  (Closure(SentMessage): bool)|null  $filter
     */
    private function assertSentOfType(string $type, string $label, ?Closure $filter, ?int $times): void
    {
        $matching = $this->matching($type, $filter);

        if ($times === null) {
            PHPUnit::assertTrue($matching->isNotEmpty(), "The expected {$label} was not sent through Authentica.");

            return;
        }

        PHPUnit::assertCount($times, $matching, sprintf(
            'The expected %s was sent %d times instead of %d.',
            $label,
            $matching->count(),
            $times,
        ));
    }

    /**
     * @param  (Closure(SentMessage): bool)|null  $filter
     */
    private function assertNotSentOfType(string $type, string $label, ?Closure $filter): void
    {
        PHPUnit::assertTrue($this->matching($type, $filter)->isEmpty(), "An unexpected {$label} was sent through Authentica.");
    }

    /**
     * @param  (Closure(SentMessage): bool)|null  $filter
     * @return Collection<int, SentMessage>
     */
    private function matching(string $type, ?Closure $filter): Collection
    {
        return $this->sent(fn (SentMessage $message): bool => $message->type === $type
            && ($filter === null || $filter($message) === true));
    }

    private function normalizedRecipient(string $recipient): string
    {
        $payload = $this->payloads->verify($recipient, '0');

        return $payload['phone'] ?? $payload['email'] ?? $recipient;
    }
}
