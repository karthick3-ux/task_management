<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old task_users table
        Schema::dropIfExists('task_users');
        
        // Create new task_assignments table
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('sequence_number')->default(1);
            $table->text('work_description');
            $table->date('start_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('deadline')->nullable();
             $table->date('doc')->nullable();
            $table->enum('status', [
                'Pending', 
                'Inprogress', 
                'Reassigned', 
                'Completed', 
                'Not Completed'
            ])->default('Pending');
            $table->integer('no_of_days')->nullable(); // Can be calculated or manual
             $table->integer('is_admin')->default(0); // Can be calculated or manual

            $table->timestamps();
            
            // Indexes
            $table->index('task_id');
            $table->index('user_id');
            $table->index(['task_id', 'sequence_number']);
            $table->unique(['task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
        
        // Recreate simple task_users table
        Schema::create('task_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['task_id', 'user_id']);
            $table->index('task_id');
            $table->index('user_id');
        });
    }
};