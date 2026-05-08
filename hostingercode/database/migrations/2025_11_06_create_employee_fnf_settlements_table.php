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
        Schema::create('employee_fnf_settlements', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            
            $table->date('resignation_date')->nullable();
            $table->date('last_working_day');
            $table->date('fnf_initiated_date')->nullable();
            $table->date('fnf_completion_date')->nullable();
            
            $table->enum('status', ['initiated', 'in_progress', 'completed', 'cancelled'])->default('initiated');
            $table->enum('resignation_type', ['resignation', 'termination', 'retirement', 'end_of_contract'])->nullable();
            $table->text('resignation_reason')->nullable();
            
            // Clearance Checklist (JSON format)
            $table->json('clearance_checklist')->nullable()->comment('IT, Admin, HR, Finance, etc.');
            
            // Assets Return
            $table->json('assets_to_return')->nullable()->comment('List of company assets');
            $table->boolean('assets_returned')->default(false);
            $table->date('assets_return_date')->nullable();
            
            // Documents Collection
            $table->json('documents_to_collect')->nullable()->comment('Experience letter, relieving letter, etc.');
            $table->boolean('documents_issued')->default(false);
            
            // Financial Settlement
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('earned_salary', 15, 2)->default(0)->comment('Salary for working days');
            $table->integer('working_days')->default(0);
            $table->integer('payable_days')->default(0);
            
            // Leave Encashment
            $table->decimal('leave_balance_days', 8, 2)->default(0);
            $table->decimal('leave_encashment_amount', 15, 2)->default(0);
            
            // Bonus & Incentives
            $table->decimal('pending_bonus', 15, 2)->default(0);
            $table->decimal('pending_incentives', 15, 2)->default(0);
            
            // Deductions
            $table->decimal('loan_outstanding', 15, 2)->default(0);
            $table->decimal('advance_outstanding', 15, 2)->default(0);
            $table->decimal('notice_period_recovery', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->text('deduction_remarks')->nullable();
            
            // Final Settlement
            $table->decimal('gross_amount', 15, 2)->default(0)->comment('Total payable');
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_payable', 15, 2)->default(0)->comment('Final amount to pay');
            
            // Payment Details
            $table->enum('payment_status', ['pending', 'processed', 'paid'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->string('payment_mode')->nullable()->comment('Bank transfer, cheque, etc.');
            $table->string('payment_reference')->nullable();
            
            // FNF Statement
            $table->string('fnf_statement_file')->nullable()->comment('PDF file path');
            
            // Approval Workflow
            $table->integer('approved_by')->unsigned()->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->date('approved_date')->nullable();
            
            $table->text('remarks')->nullable();
            $table->text('hr_notes')->nullable();
            
            $table->integer('added_by')->unsigned()->nullable();
            $table->foreign('added_by')->references('id')->on('users')->onDelete('set null');
            
            $table->integer('last_updated_by')->unsigned()->nullable();
            $table->foreign('last_updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_fnf_settlements');
    }
};

