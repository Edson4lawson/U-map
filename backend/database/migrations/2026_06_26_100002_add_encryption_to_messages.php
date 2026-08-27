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
        Schema::table('messages', function (Blueprint $table) {
            // Add encrypted_content column
            $table->text('encrypted_content')->nullable()->after('content');
            
            // Mark content for migration
            $table->boolean('is_encrypted')->default(false)->after('encrypted_content');
        });

        // Encrypt existing messages
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
                        \Log::error('Failed to encrypt existing message', [
                            'message_id' => $message->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        // After migration, make encrypted_content required and drop content
        Schema::table('messages', function (Blueprint $table) {
            $table->text('encrypted_content')->nullable(false)->change();
        });

        // Note: We keep content column for now for rollback safety
        // In production, after verification, you can drop the content column
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['encrypted_content', 'is_encrypted']);
        });
    }
};
