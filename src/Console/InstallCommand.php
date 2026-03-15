<?php

namespace Inqord\PaymentHelper\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'paymenthelper:install';

    protected $description = 'Install the Inqord Payment Helper package and update .env variables';

    public function handle()
    {
        $this->info('Publishing Configuration...');
        $this->call('vendor:publish', ['--tag' => 'paymenthelper-config']);

        $this->info('Updating Environment Files...');
        $this->updateEnvFile('.env');
        
        if (File::exists(base_path('.env.example'))) {
            $this->updateEnvFile('.env.example');
        }

        $this->info('Inqord Payment Helper installed successfully!');
    }

    protected function updateEnvFile($file)
    {
        $path = base_path($file);
        
        if (!File::exists($path)) {
            return;
        }

        $envContent = File::get($path);

        $envKeys = <<<EOF

# Inqord Payment Helper configuration
PAYMENT_GATEWAY=eps

EPS_ENABLED=true
EPS_VERIFY_SSL=true
EPS_MERCHANT_ID=
EPS_STORE_ID=
EPS_USER_NAME=
EPS_PASSWORD=
EPS_HASH_KEY=
EPS_API_URL=https://v1.sandboxpg.eps.com.bd

SSLC_ENABLED=false
SSLC_VERIFY_SSL=true
SSLC_STORE_ID=
SSLC_STORE_PASSWORD=
SSLC_API_URL=https://sandbox.sslcommerz.com
EOF;

        if (!str_contains($envContent, 'PAYMENT_GATEWAY=')) {
            File::append($path, $envKeys);
            $this->line("Appended payment variables to $file");
        } else {
            $this->line("Variables already exist in $file. Skipping.");
        }
    }
}
