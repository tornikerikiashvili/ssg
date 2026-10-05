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
        Schema::table('resource_items', function (Blueprint $table): void {
            $table->string('documentation_type')->default('file');
            $table->string('external_url', 2048)->nullable();
            $table->boolean('show_on_engagement_tools')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resource_items', function (Blueprint $table): void {
            $table->dropColumn(['documentation_type', 'external_url', 'show_on_engagement_tools']);
        });
    }
};
