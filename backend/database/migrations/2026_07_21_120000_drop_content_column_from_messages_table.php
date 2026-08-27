<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Services\MessageEncryptionService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First ensure all remaining unencrypted content is encrypted
        if (Schema::hasColumn('messages', 'content')) {
            // Only attempt encryption migration if is_encrypted column exists
            if (Schema::hasColumn('messages', 'is_encrypted')) {
                $encryptionService = new MessageEncryptionService();

                \DB::table('messages')
                    ->where('is_encrypted', false)
                    ->whereNotNull('content')
                    ->orderBy('id')
                    ->chunk(100, function ($messages) use ($encryptionService) {
                        foreach ($messages as $message) {
                            try {
                                $encrypted = $encryptionService->encrypt($message->content);
                                \DB::table('messages')
                                    ->where('id', $message->id)
                                    ->update([
                                        'encrypted_content' => $encrypted,
                                        'is_encrypted' => true,
                                    ]);
                            } catch (\Exception $e) {
                                \Log::error('Failed to encrypt message before dropping content column', [
                                    'message_id' => $message->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
            }

            // Drop content column to enforce zero plaintext storage
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn('content');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('messages', 'content')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->text('content')->nullable()->after('receiver_id');
            });
        }
    }
};
