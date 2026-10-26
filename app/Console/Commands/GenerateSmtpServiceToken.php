<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('roostle:smtp-token {--show : Display the token instead of modifying the .env file} {--force : Replace an existing token without confirmation}')]
#[Description('Generate the shared token used by the SMTP service to call the internal API')]
class GenerateSmtpServiceToken extends Command
{
    private const ENV_KEY = 'ROOSTLE_SMTP_SERVICE_TOKEN';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = bin2hex(random_bytes(32));

        if ($this->option('show')) {
            $this->line($token);

            return self::SUCCESS;
        }

        $path = $this->laravel->environmentFilePath();

        if (! File::exists($path)) {
            $this->components->error('No .env file found. Use --show to display a token instead.');

            return self::FAILURE;
        }

        $contents = File::get($path);
        $hasKey = (bool) preg_match($this->keyPattern(), $contents);

        if ($hasKey && ! $this->confirmReplacement($contents)) {
            return self::FAILURE;
        }

        File::put($path, $hasKey
            ? preg_replace($this->keyPattern(), self::ENV_KEY.'='.$token, $contents)
            : rtrim($contents, "\n")."\n".self::ENV_KEY.'='.$token."\n");

        $this->components->info('SMTP service token set successfully.');
        $this->components->twoColumnDetail(self::ENV_KEY, $token);
        $this->components->warn('Give this token to the SMTP service, and clear the config cache if it is cached.');

        return self::SUCCESS;
    }

    /**
     * Pattern matching the token's line in the .env file.
     */
    private function keyPattern(): string
    {
        return '/^'.preg_quote(self::ENV_KEY, '/').'=.*$/m';
    }

    /**
     * Whether an existing non-empty token may be replaced.
     */
    private function confirmReplacement(string $contents): bool
    {
        preg_match($this->keyPattern(), $contents, $matches);

        if (Str::after($matches[0], '=') === '' || $this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->components->error('A token already exists. Use --force to replace it.');

            return false;
        }

        return $this->components->confirm('A token already exists and the SMTP service will stop working until it is updated. Replace it?');
    }
}
