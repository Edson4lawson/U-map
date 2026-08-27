<?php
// Script temporaire : marquer les migrations déjà appliquées manuellement sur Supabase
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pending = [
    '2026_05_06_180212_create_places_table',
    '2026_05_06_184938_create_messages_table',
    '2026_05_09_182825_add_added_by_to_places_table',
    '2026_05_09_183801_add_status_to_places_table',
    '2026_05_10_173122_create_personal_access_tokens_table',
    '2026_05_10_181424_create_events_table',
    '2026_05_10_184448_add_is_read_to_messages_table',
    '2026_05_22_204150_add_is_restricted_to_users_table',
    '2026_05_22_204152_create_reports_table',
    '2026_05_30_232000_create_live_reports_table',
    '2026_05_30_233000_add_study_status_to_users',
    '2026_06_26_000001_optimize_messages_table_indexes',
    '2026_06_26_000002_optimize_users_table_indexes',
    '2026_06_26_000003_optimize_places_table_indexes',
    '2026_06_26_000004_migrate_to_mysql',
    '2026_06_26_100000_add_role_to_users_table',
    '2026_06_26_100001_create_audit_logs_table',
    '2026_06_26_100002_add_encryption_to_messages',
    '2026_07_06_200000_add_2fa_and_social_fields_to_users_table',
];

$batch = 3;
$count = 0;
foreach ($pending as $migration) {
    $exists = DB::table('migrations')->where('migration', $migration)->exists();
    if (!$exists) {
        DB::table('migrations')->insert(['migration' => $migration, 'batch' => $batch]);
        echo "Marked: $migration\n";
        $count++;
    } else {
        echo "Already exists: $migration\n";
    }
}
echo "\nDone. Marked $count migrations as ran.\n";
