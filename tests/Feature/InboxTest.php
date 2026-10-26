<?php

use App\Models\Attachment;
use App\Models\Inbox;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['roostle.catch_domain' => 'catch.roostle.test', 'roostle.disk' => 'local']);
    Storage::fake('local');
});

test('guests cannot see inboxes', function () {
    $this->get(route('inboxes.index'))->assertRedirect(route('login'));
});

test('users only see their own inboxes with message counts', function () {
    $user = User::factory()->create();
    $inbox = Inbox::factory()->for($user)->create(['catch_token' => 'abc123']);
    Message::factory()->for($inbox)->create();
    Message::factory()->for($inbox)->read()->create();
    Inbox::factory()->create();

    $this->actingAs($user)
        ->get(route('inboxes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('inboxes/Index')
            ->has('inboxes', 1)
            ->where('inboxes.0.catch_address', 'abc123@catch.roostle.test')
            ->where('inboxes.0.messages_count', 2)
            ->where('inboxes.0.unread_messages_count', 1));
});

test('creating an inbox reveals the smtp password once and stores only its hash', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('inboxes.store'), ['name' => 'Staging']);

    $inbox = $user->inboxes()->sole();

    $response->assertRedirect(route('inboxes.show', $inbox))->assertInertiaFlash('smtpPassword');

    $password = session(SessionKey::FLASH_DATA)['smtpPassword'];

    expect($inbox->name)->toBe('Staging')
        ->and($inbox->catch_token)->toHaveLength(16)
        ->and($inbox->smtp_password_hash)->not->toBe($password)
        ->and($inbox->verifySmtpPassword($password))->toBeTrue();

    $this->get(route('inboxes.show', $inbox))
        ->assertInertia(fn (Assert $page) => $page->missing('inbox.smtp_password_hash'))
        ->assertInertiaFlashMissing('smtpPassword');
});

test('an inbox needs a name', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('inboxes.store'), ['name' => ''])
        ->assertInvalid(['name']);
});

test('opening a message marks it read and returns its details', function () {
    $user = User::factory()->create();
    $inbox = Inbox::factory()->for($user)->create();
    $message = Message::factory()->for($inbox)->create(['subject' => 'Welcome']);
    Attachment::factory()->for($message)->create(['filename' => 'a.pdf']);

    $this->actingAs($user)
        ->get(route('inboxes.show', ['inbox' => $inbox, 'message' => $message->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('message.subject', 'Welcome')
            ->where('message.attachments.0.filename', 'a.pdf')
            ->where('messages.data.0.is_read', false));

    expect($message->fresh()->read_at)->not->toBeNull();
});

test('a message from another inbox cannot be opened through this one', function () {
    $user = User::factory()->create();
    $inbox = Inbox::factory()->for($user)->create();
    $foreign = Message::factory()->create();

    $this->actingAs($user)
        ->get(route('inboxes.show', ['inbox' => $inbox, 'message' => $foreign->id]))
        ->assertNotFound();
});

test('users cannot access another users inbox', function () {
    $inbox = Inbox::factory()->create();
    $message = Message::factory()->for($inbox)->create();
    $attachment = Attachment::factory()->for($message)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder);

    $this->get(route('inboxes.show', $inbox))->assertForbidden();
    $this->delete(route('inboxes.destroy', $inbox))->assertForbidden();
    $this->put(route('inboxes.password.update', $inbox))->assertForbidden();
    $this->delete(route('inboxes.messages.destroy', [$inbox, $message]))->assertForbidden();
    $this->get(route('inboxes.messages.raw', [$inbox, $message]))->assertForbidden();
    $this->get(route('inboxes.messages.attachments.show', [$inbox, $message, $attachment]))->assertForbidden();
});

test('regenerating the password invalidates the old one', function () {
    $user = User::factory()->create();
    $inbox = Inbox::factory()->for($user)->withSmtpPassword('old')->create();

    $this->actingAs($user)->put(route('inboxes.password.update', $inbox))->assertRedirect();

    expect($inbox->fresh()->verifySmtpPassword('old'))->toBeFalse();
});

test('raw source and attachments can be downloaded by the owner', function () {
    $user = User::factory()->create();
    $inbox = Inbox::factory()->for($user)->create();
    $message = Message::factory()->for($inbox)->create(['raw_path' => 'm/message.eml']);
    $attachment = Attachment::factory()->for($message)->create(['filename' => 'a.pdf', 'path' => 'm/a']);
    Storage::disk('local')->put('m/message.eml', 'RAW');
    Storage::disk('local')->put('m/a', 'PDF');

    $this->actingAs($user);

    $this->get(route('inboxes.messages.raw', [$inbox, $message]))->assertOk()->assertDownload();
    $this->get(route('inboxes.messages.attachments.show', [$inbox, $message, $attachment]))
        ->assertOk()
        ->assertDownload('a.pdf');
});

test('deleting a message removes its files', function () {
    $user = User::factory()->create();
    $inbox = Inbox::factory()->for($user)->create();
    $message = Message::factory()->for($inbox)->create(['raw_path' => 'm/message.eml']);
    Storage::disk('local')->put('m/message.eml', 'RAW');

    $this->actingAs($user)->delete(route('inboxes.messages.destroy', [$inbox, $message]))->assertRedirect();

    expect(Message::find($message->id))->toBeNull();
    Storage::disk('local')->assertMissing('m/message.eml');
});

test('deleting an inbox deletes its messages', function () {
    $user = User::factory()->create();
    $inbox = Inbox::factory()->for($user)->create();
    $message = Message::factory()->for($inbox)->create();

    $this->actingAs($user)->delete(route('inboxes.destroy', $inbox))->assertRedirect(route('inboxes.index'));

    expect(Inbox::find($inbox->id))->toBeNull()
        ->and(Message::find($message->id))->toBeNull();
});
