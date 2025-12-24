<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$notifications = App\Models\Notification::orderBy('id')->get(['id', 'status', 'recipient_email', 'sent_at', 'error_message']);

foreach ($notifications as $notification) {
    echo json_encode($notification->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
}

