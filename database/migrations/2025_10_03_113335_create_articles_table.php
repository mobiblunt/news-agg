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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('author')->nullable();
            $table->string('source');
            $table->string('source_id')->unique();
            $table->string('category')->nullable();
            $table->dateTime('published_at');
            $table->string('url');
            $table->string('image_url')->nullable();
            $table->index(['published_at', 'source', 'category']);
            $table->fulltext(['title', 'content']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
