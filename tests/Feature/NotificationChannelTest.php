<?php

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Shreifelagamy\Authentica\AuthenticaServiceProvider;
use Shreifelagamy\Authentica\Enums\OtpMethod;
use Shreifelagamy\Authentica\Exceptions\AuthenticaMissingRecipientException;
use Shreifelagamy\Authentica\Facades\Authentica;
use Shreifelagamy\Authentica\Notifications\AuthenticaChannel;
use Shreifelagamy\Authentica\Notifications\AuthenticaMessage;
use Shreifelagamy\Authentica\Testing\SentMessage;

class ParentUser
{
    use Notifiable;

    public function __construct(public ?string $phone = '0551234567', public ?string $email = 'parent@example.com') {}
}

class RoutedParent extends ParentUser
{
    public function routeNotificationForAuthentica(Notification $notification): string
    {
        return '0559998888';
    }
}

class InvoiceReady extends Notification
{
    public function __construct(public string $channel = 'authentica') {}

    public function via(object $notifiable): array
    {
        return [$this->channel];
    }

    public function toAuthentica(object $notifiable): AuthenticaMessage
    {
        return AuthenticaMessage::sms('Your invoice is ready')->sender('YoungFit');
    }
}

class SignInCode extends Notification
{
    public function __construct(public string $code, public OtpMethod $method = OtpMethod::Sms) {}

    public function via(object $notifiable): array
    {
        return ['authentica'];
    }

    public function toAuthentica(object $notifiable): AuthenticaMessage
    {
        return AuthenticaMessage::otp($this->code)->via($this->method)->template(31);
    }
}

class ProviderNeutralReminder extends Notification
{
    public function via(object $notifiable): array
    {
        return [AuthenticaChannel::class];
    }

    public function toSms(object $notifiable): ?string
    {
        return 'Training tomorrow at 5pm';
    }
}

class QueuedReminder extends ProviderNeutralReminder implements ShouldQueue
{
    use Queueable;
}

class SkippedReminder extends ProviderNeutralReminder
{
    public function toSms(object $notifiable): ?string
    {
        return null;
    }
}

beforeEach(function () {
    $this->authentica = Authentica::fake();
});

it('sends an SMS to the phone attribute of the notifiable', function () {
    (new ParentUser)->notify(new InvoiceReady);

    $this->authentica->assertSmsSent(fn (SentMessage $sms) => $sms->to === '+966551234567'
        && $sms->content === 'Your invoice is ready'
        && $sms->sender === 'YoungFit');
});

it('prefers the routeNotificationForAuthentica() number', function () {
    (new RoutedParent)->notify(new InvoiceReady);

    $this->authentica->assertSentTo('0559998888');
});

it('sends a one-time code', function () {
    (new ParentUser)->notify(new SignInCode('482913', OtpMethod::Whatsapp));

    $this->authentica->assertOtpSent(fn (SentMessage $otp) => $otp->to === '+966551234567'
        && $otp->code === '482913'
        && $otp->method === OtpMethod::Whatsapp
        && $otp->template === 31);
});

it('sends an email code to the email address', function () {
    (new ParentUser)->notify(new SignInCode('482913', OtpMethod::Email));

    $this->authentica->assertOtpSent(fn (SentMessage $otp) => $otp->to === 'parent@example.com');
});

it('sends on demand without a model', function () {
    NotificationFacade::route('authentica', '0551234567')->notify(new InvoiceReady);

    $this->authentica->assertSentTo('+966551234567');
});

it('works with the channel class and a provider-neutral toSms()', function () {
    (new ParentUser)->notify(new ProviderNeutralReminder);
    NotificationFacade::route(AuthenticaChannel::class, '0559998888')->notify(new ProviderNeutralReminder);

    $this->authentica->assertSmsSent(fn (SentMessage $sms) => $sms->content === 'Training tomorrow at 5pm', times: 2);
});

it('can be named sms in the config', function () {
    config(['authentica.channel_name' => 'sms']);
    app()->forgetInstance(ChannelManager::class);
    NotificationFacade::clearResolvedInstances();
    app()->register(AuthenticaServiceProvider::class, force: true);
    $this->authentica = Authentica::fake();

    NotificationFacade::route('sms', '0551234567')->notify(new InvoiceReady('sms'));

    $this->authentica->assertSentTo('+966551234567');
});

it('sends queued notifications', function () {
    (new ParentUser)->notify(new QueuedReminder);

    $this->authentica->assertSmsSent(times: 1);
});

it('sends nothing when the notification returns null', function () {
    (new ParentUser)->notify(new SkippedReminder);

    $this->authentica->assertNothingSent();
});

it('fails loudly when the notifiable has no number', function () {
    (new ParentUser(phone: null))->notify(new InvoiceReady);
})->throws(AuthenticaMissingRecipientException::class);
