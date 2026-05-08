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

        $pharmaWidgets = [
            ['widget_name' => 'total_doctors', 'status' => 1, 'dashboard_type' => 'admin-dashboard'],
            ['widget_name' => 'total_stockists', 'status' => 1, 'dashboard_type' => 'admin-dashboard'],
            ['widget_name' => 'total_chemists', 'status' => 1, 'dashboard_type' => 'admin-dashboard'],
            ['widget_name' => 'total_medical_representatives', 'status' => 1, 'dashboard_type' => 'admin-dashboard'],
            ['widget_name' => 'total_tours', 'status' => 1, 'dashboard_type' => 'admin-dashboard'],
            ['widget_name' => 'pending_tour_approvals', 'status' => 1, 'dashboard_type' => 'admin-dashboard'],
        ];

        foreach ($companies as $company) {
            foreach ($pharmaWidgets as $widget) {
                // Check if widget already exists for this company
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
        $pharmaWidgetNames = [
            'total_doctors',
            'total_stockists',
            'total_chemists',
            'total_medical_representatives',
            'total_tours',
            'pending_tour_approvals',
        ];

        DashboardWidget::where('dashboard_type', 'admin-dashboard')
            ->whereIn('widget_name', $pharmaWidgetNames)
            ->delete();
    }
};
