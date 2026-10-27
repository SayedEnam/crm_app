<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('app_name', 100)->default('ZomZom CRM');
            $table->string('company_name', 180)->default('ZomZom Travels & High Think HR Consultancy');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};
