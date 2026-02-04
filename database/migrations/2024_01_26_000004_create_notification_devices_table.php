<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('device_type', ['web', 'mobile_app', 'pwa'])->default('web');
            $table->string('device_token')->nullable(); // FCM token for mobile/PWA
            $table->string('browser_type')->nullable(); // Chrome, Firefox, Safari, etc.
            $table->string('platform')->nullable(); // iOS, Android, Windows, etc.
            $table->json('subscription_data')->nullable(); // Web Push subscription data
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index('device_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_devices');
    }
};