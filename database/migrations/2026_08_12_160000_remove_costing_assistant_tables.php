<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('assistant_file_templates');
        Schema::dropIfExists('assistant_rules');
        Schema::dropIfExists('assistant_topics');
    }

    public function down(): void
    {
        Schema::create('assistant_topics', function (Blueprint $table) {
            $table->id();
            $table->string('menu')->default('general');
            $table->string('title');
            $table->text('content');
            $table->string('role')->nullable();
            $table->json('keywords')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('assistant_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->string('condition_type')->default('always');
            $table->json('condition_payload')->nullable();
            $table->string('severity')->default('info');
            $table->text('message');
            $table->string('action_label')->nullable();
            $table->string('action_url')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('assistant_file_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('excel');
            $table->string('name');
            $table->json('required_columns')->nullable();
            $table->json('optional_columns')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
};
