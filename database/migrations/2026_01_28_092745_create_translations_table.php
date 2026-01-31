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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->foreignId('locale_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
            
            $table->unique(['key', 'locale_id']);
            $table->index('key');
            $table->index('locale_id');
            $table->index(['key', 'locale_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
