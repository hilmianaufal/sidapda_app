<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('icon');
            $table->text('address')->nullable()->after('description');
            $table->string('phone', 30)->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('leader_name')->nullable()->after('website');
            $table->boolean('fonnte_enabled')->default(false)->after('is_active');
            $table->text('fonnte_token')->nullable()->after('fonnte_enabled');
            $table->boolean('fonnte_notify_attendance')->default(true)->after('fonnte_token');
            $table->boolean('fonnte_notify_excuse')->default(true)->after('fonnte_notify_attendance');
            $table->boolean('fonnte_notify_boarding')->default(true)->after('fonnte_notify_excuse');
            $table->string('fonnte_message_footer')->nullable()->after('fonnte_notify_boarding');
            $table->string('fonnte_device_number', 30)->nullable()->after('fonnte_message_footer');
            $table->string('fonnte_device_status', 30)->nullable()->after('fonnte_device_number');
            $table->timestamp('fonnte_last_tested_at')->nullable()->after('fonnte_device_status');
        });

        Schema::create('institution_whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 50);
            $table->string('target', 30);
            $table->text('message');
            $table->string('status', 30)->default('pending');
            $table->string('request_id')->nullable();
            $table->text('error')->nullable();
            $table->json('response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_whatsapp_logs');

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'address',
                'phone',
                'email',
                'website',
                'leader_name',
                'fonnte_enabled',
                'fonnte_token',
                'fonnte_notify_attendance',
                'fonnte_notify_excuse',
                'fonnte_notify_boarding',
                'fonnte_message_footer',
                'fonnte_device_number',
                'fonnte_device_status',
                'fonnte_last_tested_at',
            ]);
        });
    }
};
