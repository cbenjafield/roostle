<?php

beforeEach(function () {
    $this->envDirectory = sys_get_temp_dir().'/roostle-env-'.uniqid();
    mkdir($this->envDirectory);
    $this->envPath = $this->envDirectory.'/.env';

    $this->app->useEnvironmentPath($this->envDirectory);
});

afterEach(function () {
    @unlink($this->envPath);
    @rmdir($this->envDirectory);
});

test('it appends a new token when none is set', function () {
    file_put_contents($this->envPath, "APP_NAME=Roostle\n");

    $this->artisan('roostle:smtp-token')->assertSuccessful();

    expect(file_get_contents($this->envPath))
        ->toStartWith("APP_NAME=Roostle\n")
        ->toMatch('/^ROOSTLE_SMTP_SERVICE_TOKEN=[0-9a-f]{64}$/m');
});

test('it fills in an empty token in place', function () {
    file_put_contents($this->envPath, "A=1\nROOSTLE_SMTP_SERVICE_TOKEN=\nB=2\n");

    $this->artisan('roostle:smtp-token')->assertSuccessful();

    expect(file_get_contents($this->envPath))
        ->toMatch('/^A=1\nROOSTLE_SMTP_SERVICE_TOKEN=[0-9a-f]{64}\nB=2\n$/');
});

test('it will not replace an existing token without confirmation', function () {
    file_put_contents($this->envPath, "ROOSTLE_SMTP_SERVICE_TOKEN=existing\n");

    $this->artisan('roostle:smtp-token')
        ->expectsConfirmation('A token already exists and the SMTP service will stop working until it is updated. Replace it?', 'no')
        ->assertFailed();

    expect(file_get_contents($this->envPath))->toBe("ROOSTLE_SMTP_SERVICE_TOKEN=existing\n");
});

test('it replaces an existing token with force', function () {
    file_put_contents($this->envPath, "ROOSTLE_SMTP_SERVICE_TOKEN=existing\n");

    $this->artisan('roostle:smtp-token --force')->assertSuccessful();

    expect(file_get_contents($this->envPath))
        ->not->toContain('existing')
        ->toMatch('/^ROOSTLE_SMTP_SERVICE_TOKEN=[0-9a-f]{64}$/m');
});

test('it only prints the token with show', function () {
    file_put_contents($this->envPath, "A=1\n");

    $this->artisan('roostle:smtp-token --show')->assertSuccessful();

    expect(file_get_contents($this->envPath))->toBe("A=1\n");
});

test('it fails when there is no env file', function () {
    $this->artisan('roostle:smtp-token')->assertFailed();
});
