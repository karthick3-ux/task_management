<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_name');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->date('date_of_completion')->nullable();
            $table->enum('task_status', ['pending', 'in progress', 'on hold', 'completed', 'not completed'])->default('pending');
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            $table->index(['project_id', 'task_status']);
 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};