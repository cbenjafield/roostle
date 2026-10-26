<?php

use App\Models\Inbox;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

beforeEach(function () {
    config([
        'roostle.smtp.service_token' => 'secret-token',
        'roostle.catch_domain' => 'catch.roostle.test',
        'roostle.disk' => 'local',
    ]);

    Storage::fake('local');
});

function smtpService(): TestCase
{
    return test()->withToken('secret-token')->withHeader('Accept', 'application/json');
}

test('service endpoints reject requests without the service token', function (string $route) {
    $this->postJson(route($route))->assertUnauthorized();
    $this->withToken('wrong')->postJson(route($route))->assertUnauthorized();
})->with([
    'internal.smtp.recipients.validate',
    'internal.smtp.credentials.validate',
    'internal.smtp.messages.store',
]);

test('service endpoints are closed when no token is configured', function () {
    config(['roostle.smtp.service_token' => null]);

    $this->postJson(route('internal.smtp.recipients.validate'))->assertUnauthorized();
});

test('catch address resolves to its inbox', function () {
    $inbox = Inbox::factory()->create(['catch_token' => 'abc123']);

    smtpService()
        ->postJson(route('internal.smtp.recipients.validate'), ['address' => 'ABC123@catch.roostle.test'])
        ->assertOk()
        ->assertExactJson(['inbox_id' => $inbox->id]);
});

test('unknown tokens and foreign domains are not valid recipients', function (string $address) {
    Inbox::factory()->create(['catch_token' => 'abc123']);

    smtpService()
        ->postJson(route('internal.smtp.recipients.validate'), ['address' => $address])
        ->assertNotFound();
})->with([
    'unknown token' => 'nope@catch.roostle.test',
    'wrong domain' => 'abc123@example.com',
    'no domain' => 'abc123',
]);

test('correct smtp credentials resolve to the inbox', function () {
    $inbox = Inbox::factory()->withSmtpPassword('hunter2')->create(['smtp_username' => 'rstuser']);

    smtpService()
        ->postJson(route('internal.smtp.credentials.validate'), ['username' => 'rstuser', 'password' => 'hunter2'])
        ->assertOk()
        ->assertExactJson(['inbox_id' => $inbox->id]);
});

test('wrong smtp credentials are rejected', function (string $username, string $password) {
    Inbox::factory()->withSmtpPassword('hunter2')->create(['smtp_username' => 'rstuser']);

    smtpService()
        ->postJson(route('internal.smtp.credentials.validate'), compact('username', 'password'))
        ->assertUnauthorized();
})->with([
    'wrong password' => ['rstuser', 'nope'],
    'unknown user' => ['rstother', 'hunter2'],
]);

test('delivered messages are stored with their raw source and attachments', function () {
    $inbox = Inbox::factory()->create();

    $response = smtpService()->post(route('internal.smtp.messages.store'), [
        'inbox_id' => $inbox->id,
        'message_id' => '<abc@example.com>',
        'from_address' => 'sender@example.com',
        'from_name' => 'Sender',
        'to' => [['address' => 'rcpt@catch.roostle.test', 'name' => null]],
        'subject' => 'Hello',
        'text_body' => 'Plain',
        'html_body' => '<p>Rich</p>',
        'headers' => [['name' => 'X-Test', 'value' => 'yes']],
        'raw' => UploadedFile::fake()->createWithContent('raw.eml', 'RAW SOURCE'),
        'attachments' => [UploadedFile::fake()->create('invoice.pdf', 10, 'application/pdf')],
    ]);

    $response->assertCreated();

    $message = $inbox->messages()->with('attachments')->sole();

    expect($message->subject)->toBe('Hello')
        ->and($message->to[0]['address'])->toBe('rcpt@catch.roostle.test')
        ->and($message->cc)->toBe([])
        ->and($message->read_at)->toBeNull()
        ->and($message->attachments->sole()->filename)->toBe('invoice.pdf');

    Storage::disk('local')->assertExists($message->raw_path);
    Storage::disk('local')->assertExists($message->attachments->sole()->path);
    expect(Storage::disk('local')->get($message->raw_path))->toBe('RAW SOURCE');
});

test('delivery requires an existing inbox, a sender and the raw source', function () {
    smtpService()
        ->post(route('internal.smtp.messages.store'), ['inbox_id' => 999])
        ->assertInvalid(['inbox_id', 'from_address', 'raw']);
});

test('oversized messages are rejected', function () {
    config(['roostle.max_message_kilobytes' => 1]);
    $inbox = Inbox::factory()->create();

    smtpService()
        ->post(route('internal.smtp.messages.store'), [
            'inbox_id' => $inbox->id,
            'from_address' => 'sender@example.com',
            'raw' => UploadedFile::fake()->create('raw.eml', 5),
        ])
        ->assertInvalid(['raw']);

    expect($inbox->messages()->count())->toBe(0);
});
