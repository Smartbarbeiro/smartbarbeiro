<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = dirname(__DIR__).'/laravel';
require $laravelRoot.'/vendor/autoload.php';
$app = require_once $laravelRoot.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$migration = '2026_07_10_160000_add_stripe_connect_fields_to_users_table';
$file = $laravelRoot.'/database/migrations/'.$migration.'.php';

echo 'migration_file: '.(is_file($file) ? 'yes' : 'no')."\n";
echo 'migration_ran: '.(Illuminate\Support\Facades\DB::table('migrations')->where('migration', $migration)->exists() ? 'yes' : 'no')."\n";
echo 'has_stripe_connect_account_id: '.(Illuminate\Support\Facades\Schema::hasColumn('users', 'stripe_connect_account_id') ? 'yes' : 'no')."\n";
echo 'stripe_configured: '.(app(App\Services\StripeServicePlanService::class)->isConfigured() ? 'yes' : 'no')."\n";
echo 'connect_enabled: '.(config('stripe.connect_enabled') ? 'yes' : 'no')."\n";
echo 'fee_percent: '.config('stripe.application_fee_percent')."\n";
echo "\nDELETE check-stripe-connect-tesora.php when done.\n";
