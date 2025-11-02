<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class VerifyUserEmail extends Command
{
    protected $signature = 'user:verify-email {email}';

    protected $description = 'Manually verify a user\'s email address for local development';

    public function handle(): int
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return self::FAILURE;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info("User '{$user->email}' is already verified.");
            return self::SUCCESS;
        }

        $user->markEmailAsVerified();

        $this->info("Successfully verified email for '{$user->email}'.");
        
        // Show verification URL for testing purposes
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
        
        $this->line("Verification URL (for reference): {$verificationUrl}");

        return self::SUCCESS;
    }
}

