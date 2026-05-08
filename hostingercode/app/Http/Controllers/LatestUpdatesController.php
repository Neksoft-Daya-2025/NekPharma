<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use Illuminate\Http\Request;

class LatestUpdatesController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Latest Updates';
    }

    /**
     * Display the latest updates page
     */
    public function index()
    {
        $updates = [
            [
                'date' => 'December 29, 2024',
                'version' => '5.5.19',
                'title' => 'DCR Reports Module - Complete Implementation',
                'type' => 'feature',
                'items' => [
                    'Added DCR Report creation functionality with form submission',
                    'Implemented DCR Report approval workflow for managers',
                    'Added menu visibility logic for "Approve DCR Reports" based on permissions and reporting structure',
                    'Fixed form submission using AJAX for file uploads',
                    'Added DCR Report listing and management pages',
                    'Created database migrations for DCR Reports tables',
                    'Added doctor visits tracking with inline creation',
                    'Added chemist visits tracking with inline creation',
                    'Added stockist visits tracking with inline creation',
                    'Implemented work status and work with fields',
                    'Added approval fields (submitted_to, approved_by, approval_date)',
                    'Integrated with Tour Plans for automatic date selection'
                ],
                'instructions' => [
                    'Navigate to DCR Reports menu to create new reports',
                    'Managers can approve DCR Reports from the Approve DCR Reports submenu',
                    'Reports can include visits to doctors, chemists, and stockists',
                    'File attachments are supported in DCR Reports',
                    'Use inline creation to add doctors/chemists/stockists on the fly',
                    'Link DCR reports to approved tour plans for seamless workflow'
                ]
            ],
            [
                'date' => 'December 28, 2024',
                'version' => '5.5.19',
                'title' => 'Pharma Expense Module - Enhanced Features',
                'type' => 'feature',
                'items' => [
                    'Added pharma-specific expense fields (expense_type, headquarter_id, expense_month)',
                    'Implemented daily expense tracking with town worked and work with fields',
                    'Added doctor and retailer meeting counts tracking',
                    'Added travel expense fields (headquarter_from, headquarter_to, mode_of_transport, km, fare)',
                    'Implemented daily allowance tracking (US and EX station rates)',
                    'Added fixed expenses and other expenses fields',
                    'Created expense submission and approval workflow',
                    'Added expense posting date and voucher count tracking'
                ],
                'instructions' => [
                    'Navigate to Expenses menu and select "Pharma Statement" expense type',
                    'Fill in daily expense details including town worked and work with',
                    'Track doctor and retailer meetings for each expense entry',
                    'Record travel expenses with mode of transport and distance',
                    'Submit expenses for approval through the workflow'
                ]
            ],
            [
                'date' => 'December 27, 2024',
                'version' => '5.5.19',
                'title' => 'Tour Planning Module - Complete Implementation',
                'type' => 'feature',
                'items' => [
                    'Added Tour Plan creation and management functionality',
                    'Implemented monthly tour planning with approval workflow',
                    'Added work status tracking for tour plans',
                    'Integrated with headquarters and stations',
                    'Added "Work With" field for team collaboration tracking',
                    'Implemented tour approval system for managers',
                    'Added bulk approval and deletion features',
                    'Created tour status tracking page'
                ],
                'instructions' => [
                    'Navigate to Tours menu to create monthly tour plans',
                    'Select month, headquarters, and stations for planning',
                    'Set work status and work with team members',
                    'Submit tour plans for manager approval',
                    'Managers can approve tours from the Tours Status page',
                    'Link approved tours with DCR reports for seamless reporting'
                ]
            ],
            [
                'date' => 'December 26, 2024',
                'version' => '5.5.19',
                'title' => 'Invoice Module - Pharma Enhancements',
                'type' => 'feature',
                'items' => [
                    'Added LR (Lorry Receipt) number and date fields to invoices',
                    'Added delivery status tracking for invoices',
                    'Added pharma-specific fields to invoice items',
                    'Enhanced invoice management for pharmaceutical distribution'
                ],
                'instructions' => [
                    'When creating invoices, add LR number and date for tracking',
                    'Update delivery status as orders are dispatched',
                    'Use pharma-specific fields for better inventory management'
                ]
            ],
            [
                'date' => 'December 26, 2024',
                'version' => '5.5.19',
                'title' => 'Doctors & Chemists - MSL Number Tracking',
                'type' => 'feature',
                'items' => [
                    'Added MSL (Medical Sales License) number field to Doctors table',
                    'Added MSL number field to Chemists table',
                    'Enhanced customer tracking for pharmaceutical compliance'
                ],
                'instructions' => [
                    'Add MSL numbers when creating or editing doctors',
                    'Add MSL numbers when creating or editing chemists',
                    'Use MSL numbers for compliance and reporting purposes'
                ]
            ],
            [
                'date' => 'December 22, 2024',
                'version' => '5.5.19',
                'title' => 'CFA Distributor Stocks Module',
                'type' => 'feature',
                'items' => [
                    'Created CFA Distributor Stocks tracking system',
                    'Added stockist stocks management',
                    'Implemented stock tracking with batch and expiry dates',
                    'Added PTS, PTR, MRP, and discount fields',
                    'Linked stocks to purchase entries and invoices',
                    'Added available quantity tracking for stockist billing'
                ],
                'instructions' => [
                    'Navigate to CFA Distributor Stocks menu to manage stocks',
                    'Track stock levels by product, batch, and expiry',
                    'Link stocks to purchase entries and invoices',
                    'Monitor available quantities for stockist distribution'
                ]
            ],
            [
                'date' => 'December 22, 2024',
                'version' => '5.5.19',
                'title' => 'Client Details - Bank Information',
                'type' => 'feature',
                'items' => [
                    'Added bank details fields to client details',
                    'Enhanced client information management for payment processing'
                ],
                'instructions' => [
                    'Add bank account details when creating or editing clients',
                    'Use bank information for payment and invoicing purposes'
                ]
            ],
            [
                'date' => 'December 21, 2024',
                'version' => '5.5.19',
                'title' => 'Pharma Areas & Zones Management',
                'type' => 'feature',
                'items' => [
                    'Implemented Pharma Areas management system',
                    'Added Headquarters management with stations',
                    'Created Zones management functionality',
                    'Added zone assignment to employees',
                    'Created overview page for area management',
                    'Enhanced territory management for pharmaceutical operations'
                ],
                'instructions' => [
                    'Navigate to Pharma Areas menu to manage headquarters and stations',
                    'Create zones and assign them to employees',
                    'Use overview page to see complete area structure',
                    'Link areas to tours, DCR reports, and expenses'
                ]
            ],
            [
                'date' => 'December 20, 2024',
                'version' => '5.5.19',
                'title' => 'Products Module - Vendor Enhancement',
                'type' => 'improvement',
                'items' => [
                    'Replaced manufacturer field with vendor field in products',
                    'Enhanced product management for better supplier tracking'
                ],
                'instructions' => [
                    'Update existing products to use vendor instead of manufacturer',
                    'Use vendor field for supplier and distributor management'
                ]
            ],
            [
                'date' => 'December 27, 2024',
                'version' => '5.5.19',
                'title' => 'Dashboard - Pharma Widgets',
                'type' => 'feature',
                'items' => [
                    'Added pharma-specific dashboard widgets',
                    'Enhanced dashboard for pharmaceutical operations visibility'
                ],
                'instructions' => [
                    'Customize dashboard widgets from dashboard settings',
                    'Add pharma widgets to monitor key metrics'
                ]
            ],
            [
                'date' => 'December 29, 2024',
                'version' => '5.5.19',
                'title' => 'Shared Hosting Deployment Support',
                'type' => 'improvement',
                'items' => [
                    'Created deployment package for Hostinger shared hosting',
                    'Added root index.php for fixed document root configuration',
                    'Implemented .htaccess rewrite rules for asset serving',
                    'Created migration runner for shared hosting environments',
                    'Added comprehensive deployment documentation',
                    'Fixed asset loading issues for CSS, JS, and vendor files'
                ],
                'instructions' => [
                    'Upload entire hostinger code folder to public_html/hostingercode/',
                    'Upload public_html_index.php to public_html/index.php',
                    'Upload public_html_htaccess to public_html/.htaccess',
                    'Run migrations via terminal: php artisan migrate --force',
                    'Set proper file permissions (storage/, bootstrap/cache/ → 755)',
                    'Use run-migrations.php for web-based migration execution'
                ]
            ],
            [
                'date' => 'December 29, 2024',
                'version' => '5.5.19',
                'title' => 'Bug Fixes and Improvements',
                'type' => 'bugfix',
                'items' => [
                    'Fixed DCR form submission error with file uploads',
                    'Fixed menu visibility for Approve DCR Reports',
                    'Resolved asset loading issues on shared hosting',
                    'Fixed cache path configuration errors',
                    'Improved error handling and debugging',
                    'Fixed version.txt file location issue',
                    'Resolved view not found errors',
                    'Fixed public vendor folder asset loading'
                ],
                'instructions' => [
                    'Clear browser cache after update (Ctrl+F5)',
                    'Run migrations if database structure changed',
                    'Clear application cache: php artisan cache:clear',
                    'Clear config cache: php artisan config:clear',
                    'Clear view cache: php artisan view:clear'
                ]
            ]
        ];

        $this->updates = $updates;
        return view('latest-updates.index', $this->data);
    }
}

