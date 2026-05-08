<?php

use App\Models\Company;
use App\Models\DashboardWidget;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $companies = Company::all();

        $widgets = [
            ['widget_name' => 'pending_dcr_approvals', 'status' => 1, 'dashboard_type' => 'admin-dashboard'],
            ['widget_name' => 'pending_expense_approvals', 'status' => 1, 'dashboard_type' => 'admin-dashboard'],
            ['widget_name' => 'total_cfa_stockists', 'status' => 1, 'dashboard_type' => 'admin-dashboard'],
        ];

        foreach ($companies as $company) {
            foreach ($widgets as $widget) {
                $existingWidget = DashboardWidget::where('company_id', $company->id)
                    ->where('widget_name', $widget['widget_name'])
                    ->where('dashboard_type', $widget['dashboard_type'])
                    ->first();

                if (!$existingWidget) {
                    $widget['company_id'] = $company->id;
                    DashboardWidget::create($widget);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $widgetNames = [
            'pending_dcr_approvals',
            'pending_expense_approvals',
            'total_cfa_stockists',
        ];

        DashboardWidget::where('dashboard_type', 'admin-dashboard')
            ->whereIn('widget_name', $widgetNames)
            ->delete();
    }
};
