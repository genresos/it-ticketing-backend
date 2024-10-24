<?php

use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {
    $api->group(['middleware' => 'cors'], function (Router $api) {
        $api->group(['prefix' => 'auth'], function (Router $api) {
            $api->post('signup', 'App\\Api\\V1\\Controllers\\SignUpController@signUp');
            $api->post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');
            $api->post('recovery', 'App\\Api\\V1\\Controllers\\ForgotPasswordController@sendResetEmail');
            $api->post('reset', 'App\\Api\\V1\\Controllers\\ResetPasswordController@resetPassword');
            $api->put('resetpassword', 'App\\Api\\V1\\Controllers\\ResetPasswordController@update_password');
            $api->put('changelevel', 'App\\Api\\V1\\Controllers\\ResetPasswordController@change_level');
            $api->post('logout', 'App\\Api\\V1\\Controllers\\LogoutController@logout');
            $api->post('refresh', 'App\\Api\\V1\\Controllers\\RefreshController@refresh');
            $api->get('me', 'App\\Api\\V1\\Controllers\\UserController@me');
            $api->get('service', 'App\\Api\\V1\\Controllers\\ServiceReportController@index');
        });

        $api->group(['prefix' => 'dashboard'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\ApiDashboardController@index');
        });

        $api->group(['prefix' => 'am'], function (Router $api) {
            $api->get('/car/list', 'App\\Api\\V1\\Controllers\\AssetController@car_list');
            $api->get('/tolcard/list', 'App\\Api\\V1\\Controllers\\AssetController@tol_card_list');
            $api->post('/request_kendaraan', 'App\\Api\\V1\\Controllers\\AssetController@request_kendaraan');
        });

        $api->group(['prefix' => 'purchase'], function (Router $api) {
            $api->get('/po_need_approve', 'App\\Api\\V1\\Controllers\\ApiPurchOrderController@po_need_approval');
            $api->put('/approve_po', 'App\\Api\\V1\\Controllers\\ApiPurchOrderController@approve_po');
        });

        $api->group(['prefix' => 'accounting'], function (Router $api) {
            $api->get('/get_ap_journal', 'App\\Api\\V1\\Controllers\\ApiAccountingController@get_ap_journal');
            $api->put('/modify_ap_journal', 'App\\Api\\V1\\Controllers\\ApiAccountingController@update_pph_ap');
        });

        $api->group(['prefix' => 'inventory'], function (Router $api) {
            $api->get('/get_qr_value', 'App\\Api\\V1\\Controllers\\ApiInventoryInternalUseController@get_data_item_from_qr');
            $api->post('/internal_use_inventory', 'App\\Api\\V1\\Controllers\\ApiInventoryInternalUseController@add_stock_internal_use');
            $api->post('/items_mr_tmp', 'App\\Api\\V1\\Controllers\\ApiInventoryInternalUseController@get_items_mr_tmp');
            $api->post('/validating_mr', 'App\\Api\\V1\\Controllers\\ApiInventoryInternalUseController@validation_material_receipt');
            $api->get('/location_list', 'App\\Api\\V1\\Controllers\\ApiInventoryInternalUseController@location_list_row');
            $api->get('/download_qr_grn', 'App\\Api\\V1\\Controllers\\ApiGrnController@download_qr_grn');
            $api->get('/items_pr', 'App\\Api\\V1\\Controllers\\ApiInventoryMoveController@get_items_pr');
            $api->post('/inventory_movement', 'App\\Api\\V1\\Controllers\\ApiInventoryMoveController@add_stock_transfer');
        });

        $api->group(['prefix' => 'employees'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@employees');
            $api->get('/details', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@employee_detail');
            // $api->get('/detail/hardware', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@employee_detail_hardware');
            // $api->get('/detail/tools', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@employee_detail_tools');
            // $api->get('/detail/ca', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@employee_detail_ca');
            $api->post('/create_exit_clearence', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@create_exit_clearence');
            $api->get('/exit_clearence', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@show_exit_clearence');
            $api->get('/ec_need_approve', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@exit_clearence_need_approve');
            $api->put('/approve_exit_clearence', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@approve_ec');
            $api->put('/update_exit_clearence', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@edit_project_manager_user');
            $api->put('/edit_exit_clearence', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@edit_ec_pm');
            $api->get('/ec_need_update', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@show_ec_need_pm');
            $api->get('/ec_history', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@show_ec_history');
            $api->post('/upload_ec_attachments', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@add_attachment_ec');
            $api->get('/list', 'App\\Api\\V1\\Controllers\\EmployeesController@employees');
            $api->get('/export_ec', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@export_exit_clearence');
            $api->get('/download_summary', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@export_summary_exit_clearence');
            $api->put('/ec/cancel', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@cancel_exit_clearernce');
            $api->put('/ec/close_manual', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@close_exit_clearence_manual');
            $api->put('/ec/edit', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@edit_exit_clearences_history');
        });

        $api->group(['prefix' => 'finance'], function (Router $api) {
            $api->get('/pettycash/list', 'App\\Api\\V1\\Controllers\\FinanceController@petty_cash_list');
            $api->get('/export_ca', 'App\\Api\\V1\\Controllers\\FinanceController@export_ca');
            $api->post('/upload_json', 'App\\Api\\V1\\Controllers\\FinanceController@upload_json');
        });

        $api->group(['prefix' => 'ca'], function (Router $api) {
            // $api->get('/', 'App\\Api\\V1\\Controllers\\CashadvanceController@needApproval');
            $api->get('/', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@ca_approval');
            // $api->get('/list', 'App\\Api\\V1\\Controllers\\CashadvanceController@ca_list_users');
            $api->get('/list', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@view_ca_list');
            // $api->put('/{id}/{id2}', 'App\\Api\\V1\\Controllers\\CashadvanceController@update_detail');
            $api->put('/{id}/{id2}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@update_ca_detail');
            $api->get('/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceController@detail');
            // $api->put('/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceController@update_ca');
            $api->put('/{id}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@update_ca');
            $api->get('/history/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@ca_history');
            $api->get('/test/{project_no}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@test');
            $api->post('/send/notification', 'App\\Api\\V1\\Controllers\\ApiNotificationController@cashadvance_notification');

            $api->get('/revision/allocation', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@view_ca_revision');
            $api->get('/revision/history/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@history_approval_rev_ca');
            $api->put('/revision/allocation/approve/{id}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@update_approve_rev_ca');
            $api->put('/revision/allocation/approve/{id}/{id2}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@update_approve_rev_ca_details');
            $api->put('/revision/allocation/disapprove/{id}/{id2}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@disapprove_rev_ca');
            //$api->get('/list',     'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@view_ca_list');
        });


        $api->group(['prefix' => 'approve'], function (Router $api) {
            // $api->put('/ca/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceController@approve_all');
            $api->put('/ca/{id}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@ca_approve_all');
            $api->put('/attendance', 'App\\Api\\V1\\Controllers\\AttendanceController@approve_attendance');
            $api->put('/stl/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceSettlementController@approve_all');
            $api->put('/am_issues/{doc_no}', 'App\\Api\\V1\\Controllers\\AssetsapprovalController@approve_all');
            $api->put('/budget/{budget_detail_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@budget_approve');
        });

        $api->group(['prefix' => 'disapprove'], function (Router $api) {
            // $api->put('/ca/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceController@disapprove_all');
            $api->put('/ca/{id}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@ca_disapprove_all');
            // $api->put('/ca/remark_diss/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceController@remark_diss');
            $api->put('/ca/remark_diss/{id}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@ca_remark_diss');
            // $api->put('/ca/remark_diss_all/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceController@remark_diss_all');
            $api->put('/ca/remark_diss_all/{id}', 'App\\Api\\V1\\Controllers\\ApiCashAdvanceController@ca_remark_diss_all');
            $api->put('/budget/{budget_detail_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@budget_disapprove');
            $api->put('/attendance', 'App\\Api\\V1\\Controllers\\AttendanceController@disapprove_attendance');
            $api->put('/stl/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceSettlementController@disapprove_all');
            $api->put('/stl/remark_diss/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceSettlementController@remark_diss');
            $api->put('/stl/remark_diss_all/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceSettlementController@remark_diss_all');
            $api->put('/am_issues/{doc_no}', 'App\\Api\\V1\\Controllers\\AssetsapprovalController@disapprove_all');
        });

        $api->group(['prefix' => 'stl'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\CashadvanceSettlementController@needApproval');
            $api->get('/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceSettlementController@detail');
            $api->put('/{id}/{id2}', 'App\\Api\\V1\\Controllers\\CashadvanceSettlementController@update_detail');
            $api->put('/{id}', 'App\\Api\\V1\\Controllers\\CashadvanceSettlementController@update_ca_stl');
            $api->get('/history/{trans_no}', 'App\\Api\\V1\\Controllers\\CashadvanceSettlementController@get_stl_history');
        });

        $api->group(['prefix' => 'assets_issue'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\AssetsapprovalController@needApproval');
            $api->put('/{doc_no}/{issue_id}', 'App\\Api\\V1\\Controllers\\AssetsapprovalController@update_detail');
            $api->put('/{doc_no}', 'App\\Api\\V1\\Controllers\\AssetsapprovalController@update_issue');
        });

        $api->group(['prefix' => 'assets_registration'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\AssetsregistrationController@view');
            $api->get('/approval', 'App\\Api\\V1\\Controllers\\AssetsregistrationController@needApproval');
            $api->post('/', 'App\\Api\\V1\\Controllers\\AssetsregistrationController@upload');
            $api->put('/approve/{uniq_id}', 'App\\Api\\V1\\Controllers\\AssetsregistrationController@approve_ar');
            $api->put('/disapprove/{uniq_id}', 'App\\Api\\V1\\Controllers\\AssetsregistrationController@disapprove_ar');
            $api->get('/view', 'App\\Api\\V1\\Controllers\\AssetsregistrationController@view_all');
            $api->get('/export', 'App\\Api\\V1\\Controllers\\AssetsregistrationController@export_asset_registration');
        });

        $api->group(['prefix' => 'gl'], function (Router $api) {
            $api->get('/bank_payment_approval', 'App\\Api\\V1\\Controllers\\ApiBankPaymentController@bank_payment_need_approval');
            $api->post('/update_bank_payment', 'App\\Api\\V1\\Controllers\\ApiBankPaymentController@update_bank_payment');
            $api->post('/send/notification', 'App\\Api\\V1\\Controllers\\ApiNotificationController@bp_notification');
            $api->get('/project_deposit_approval', 'App\\Api\\V1\\Controllers\\ApiProjectDepositController@project_deposit_need_approval');
            $api->put('/project_deposit_update', 'App\\Api\\V1\\Controllers\\ApiProjectDepositController@update_project_deposit');
        });

        $api->group(['prefix' => 'tickets'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\ICTTicketController@get_tickets');
            $api->get('/detail', 'App\\Api\\V1\\Controllers\\ICTTicketController@detail_tickets');
            $api->get('/my_tickets', 'App\\Api\\V1\\Controllers\\ICTTicketController@get_my_tickets');
            $api->get('/category', 'App\\Api\\V1\\Controllers\\ICTTicketController@category_ticket');
            $api->get('/priority', 'App\\Api\\V1\\Controllers\\ICTTicketController@priority_ticket');
            $api->get('/status', 'App\\Api\\V1\\Controllers\\ICTTicketController@status_ticket');
            $api->get('/ict_members', 'App\\Api\\V1\\Controllers\\ICTTicketController@ict_members');
            $api->post('/create', 'App\\Api\\V1\\Controllers\\ICTTicketController@create_tickets');
            $api->put('/update/{id}', 'App\\Api\\V1\\Controllers\\ICTTicketController@update_tickets');
            $api->put('/reopen/{id}', 'App\\Api\\V1\\Controllers\\ICTTicketController@reopen_tickets');
            $api->get('/issue_line_chart_year/{id}', 'App\\Api\\V1\\Controllers\\ICTTicketController@issue_line_chart_year');
            $api->post('/issue_pie_chart', 'App\\Api\\V1\\Controllers\\ICTTicketController@issue_pie_chart_year');
            $api->post('/issue_close_in_a_day', 'App\\Api\\V1\\Controllers\\ICTTicketController@issue_close_in_a_day');
            $api->post('/table_list', 'App\\Api\\V1\\Controllers\\ICTTicketController@table_list');
            $api->get('/issue_summary', 'App\\Api\\V1\\Controllers\\ICTTicketController@issue_summary');
            $api->get('/current_issue', 'App\\Api\\V1\\Controllers\\ICTTicketController@current_issue');
            $api->get('/export', 'App\\Api\\V1\\Controllers\\ICTTicketController@export_tickets');
        });


        $api->group(['prefix' => 'attendance'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\AttendanceController@view');
            $api->get('/is_user_noc/{emp_id}', 'App\\Api\\V1\\Controllers\\AttendanceController@is_user_noc');
            $api->get('/zone', 'App\\Api\\V1\\Controllers\\AttendanceController@list_zone');
            $api->put('/sync', 'App\\Api\\V1\\Controllers\\AttendanceController@sync_to_devosa');
            $api->post('/addzone', 'App\\Api\\V1\\Controllers\\AttendanceController@add_zone');
            $api->post('/in', 'App\\Api\\V1\\Controllers\\AttendanceController@attendance_in');
            $api->get('/need/approval', 'App\\Api\\V1\\Controllers\\AttendanceController@need_approval_attendance');
            $api->post('/out/{id}', 'App\\Api\\V1\\Controllers\\AttendanceController@attendance_out');
            $api->get('/type/list', 'App\\Api\\V1\\Controllers\\AttendanceController@attendance_out');
            $api->delete('/delete', 'App\\Api\\V1\\Controllers\\AttendanceController@delete_attendance');
            $api->get('/export', 'App\\Api\\V1\\Controllers\\AttendanceController@export_attendance');
        });

        $api->group(['prefix' => 'projects'], function (Router $api) {
            $api->get('/detail/{project_no}', 'App\\Api\\V1\\Controllers\\ApiProjectListController@get_project');
            $api->get('/list', 'App\\Api\\V1\\Controllers\\ProjectsController@projects');
            $api->get('/list_search', 'App\\Api\\V1\\Controllers\\ProjectsController@projects_search');
            $api->get('/spk_search', 'App\\Api\\V1\\Controllers\\ProjectsController@projects_spk');
            $api->get('/so_search', 'App\\Api\\V1\\Controllers\\ProjectsController@so_search');
            $api->get('/po_search', 'App\\Api\\V1\\Controllers\\ProjectsController@po_search');
            $api->post('/sites', 'App\\Api\\V1\\Controllers\\ProjectsController@project_sites');
            $api->get('/project_managers', 'App\\Api\\V1\\Controllers\\ApiProjectListController@project_managers');
            $api->post('/project_managers/new', 'App\\Api\\V1\\Controllers\\ApiProjectListController@add_project_managers');
            $api->put('/project_managers', 'App\\Api\\V1\\Controllers\\ApiProjectListController@update_project_managers');
            $api->post('/create/site', 'App\\Api\\V1\\Controllers\\ProjectsController@add_sites');
            $api->post('/upload/newsite', 'App\\Api\\V1\\Controllers\\ProjectsController@upload_new_site');
            $api->put('/sites/update/{site_no}', 'App\\Api\\V1\\Controllers\\ProjectsController@update_project_sites');
            $api->get('/milestones', 'Astlpp\\Api\\V1\\Controllers\\ProjectsController@project_milestones');
            $api->get('/uom', 'App\\Api\\V1\\Controllers\\ProjectsController@uom_list');
            $api->get('/list/download', 'App\\Api\\V1\\Controllers\\ApiProjectListController@export_project');
            $api->post('/create/new/du_id', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@create_project_du_id');
            $api->get('/show/du_id', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@project_du_id');

            $api->get('/commited/overview/', 'App\\Api\\V1\\Controllers\\ApiProjectOverviewController@index_commited_project_overview');
            $api->get('/actual/overview', 'App\\Api\\V1\\Controllers\\ApiProjectOverviewController@index_actual_project_overview');
            $api->get('/curva/overview', 'App\\Api\\V1\\Controllers\\ApiProjectOverviewController@curva_project_overview');
            $api->post('/update/bcwp', 'App\\Api\\V1\\Controllers\\ApiProjectOverviewController@update_bcwp_project');
            $api->get('/commited/overview_details', 'App\\Api\\V1\\Controllers\\ApiProjectOverviewController@details_commited_project_overview');
            $api->get('/actual/overview_details', 'App\\Api\\V1\\Controllers\\ApiProjectOverviewController@details_actual_project_overview');
            $api->get('/budgetvscost/overview', 'App\\Api\\V1\\Controllers\\ApiProjectOverviewController@overview_budget_v_cost');


            $api->post('/create', 'App\\Api\\V1\\Controllers\\ApiProjectListController@create_project');
            $api->put('/update/{project_no}', 'App\\Api\\V1\\Controllers\\ApiProjectListController@update_project');
            $api->delete('/delete/{project_no}', 'App\\Api\\V1\\Controllers\\ApiProjectListController@delete_project');


            $api->get('/budgets/{project_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@project_budgets');
            $api->get('/budgets/detail/{project_budget_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@budget_detail');
            $api->put('/budgets/{project_budget_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@edit_project_budget');
            $api->get('/budgets/list/{project_code}', 'App\\Api\\V1\\Controllers\\ProjectsController@project_budget_list');
            $api->post('/budgets/add/new', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@add_new_budget');
            $api->post('/budget_req/{project_budget_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@add_req_budget');
            $api->get('/budgets/need/approve', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@budget_approve_list');
            $api->get('/budgets/test', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@test');
            $api->get('/budgets/show/used_amount/{project_budget_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@show_used_budget');
            $api->get('/budgets/export/{project_budget_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_export_budget_use');
            $api->get('/budgets/curve/graph', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_curve_graph_total_budget');
            $api->post('/budgets/upload_salary', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@upload_budget_salary');
            $api->post('/budgets/upload_project_rent_tools', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@upload_project_rent_tools');
            $api->post('/budgets/upload_project_rent_vehicle', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@upload_project_rent_vehicle');
            $api->get('/so/list/{project_code}', 'App\\Api\\V1\\Controllers\\ApiSalesOrderController@get_so_by_project');

            $api->post('/so/upload_order', 'App\\Api\\V1\\Controllers\\ApiSalesOrderController@upload_order');

            $api->post('/budget/rab_adjustment/{id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@rab_budget_adjustment');
            $api->get('/budget/rab', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@project_rab_history');
            $api->post('/upload/budget/rab', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@upload_rab');
            // $api->post('/budget/add_rab/{project_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@add_rab_budget');
            // $api->get('/budget/rab/need_approve', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@rab_need_approve');
            // $api->put('/budget/rab/approve', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@approval_rab');
            // $api->put('/budget/edit_rab', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@edit_rab');
            // $api->post('/budget/req_add_rab', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@req_add_rab

            $api->get('/rab_type', 'App\\Api\\V1\\Controllers\\ProjectsController@rab_type_list');
            $api->post('/budgetary/rab/create', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_project_budgetary');
            $api->put('/budgetary/rab/edit/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@edit_project_budgetary');
            $api->post('/budgetary/rab/duplicated/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@duplicated_project_budgetary');
            $api->delete('/budgetary/rab/delete/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@delete_project_budgetary');
            $api->get('/submission/rab', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_rab_list');
            $api->get('/submission/rab/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_rab_bytrans_no');
            $api->put('/submission/rab/update_rab_revision/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_rab_revision');
            $api->get('/budgetary/rab/need_approval', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@need_approval_project_budgetary');
            $api->put('/budgetary/rab/submit/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@submit_approval_rab');
            $api->put('/budgetary/rab/update/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_project_budgetary');

            $api->get('/submission/rab/calculated_interest/{cash_flow_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@calculated_interest');

            $api->post('/budgetary/rab/cashflow', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_cashflow_budgetary');
            $api->get('/budgetary/rab/cashflow', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_cashflow_budgetary');
            $api->put('/budgetary/rab/cashflow', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_cashflow_budgetary');
            $api->get('/budgetary/rab/cashflow/edit_cashout', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_budgetary_cashout');
            $api->put('/budgetary/rab/cashflow/update_cashout', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_budgetary_cashout');

            $api->delete('/submission/rab/delete_detail', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@delete_detail_submission');
            $api->delete('/submission/rab/delete_project_value', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@delete_project_value');
            $api->delete('/submission/rab/delete_all_project_value', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@delete_all_project_value');
            $api->post('/submission/rab/manpower', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_rab_manpower');
            $api->post('/submission/rab/procurement', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_rab_procurement');
            $api->post('/submission/rab/vehicle_ops', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_rab_vehicle_ops');
            $api->post('/submission/rab/training', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_rab_training');
            $api->post('/submission/rab/tools', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_rab_tools');
            $api->post('/submission/rab/other_expenses', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_rab_other_expenses');
            $api->post('/submission/rab/other_information', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_rab_other_information');
            $api->post('/submission/rab/project_value', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_rab_project_value');
            $api->post('/submission/rab/upload_project_value', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@upload_rab_project_value');

            $api->get('/submission/rab/manpower/get_position', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_mp_position');
            $api->get('/submission/rab/list_project_value/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_list_rab_project_value');

            $api->put('/submission/rab/update_manpower/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_manpower');
            $api->put('/submission/rab/update_procurement/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_procurement');
            $api->put('/submission/rab/update_vehicle_ops/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_vehicle_ops');
            $api->put('/submission/rab/update_training/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_training');
            $api->put('/submission/rab/update_tools/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_tools');
            $api->put('/submission/rab/update_other_expenses/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_other_expenses');
            $api->put('/submission/rab/update_other_information/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_other_information');
            $api->put('/submission/rab/update_project_value/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_project_value');

            $api->get('/submission/rab/export/{trans_no}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@export_detail_rab');

            $api->post('/spp/create_spp', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@create_new_spp');
            $api->put('/spp/update/{spp_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_spp');
            $api->get('/spp/spp_list', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_spp_list');
            $api->get('/spp/spp_list/{spp_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_spp_by_id');
            $api->get('/spp/spp_need_approve', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_spp_need_approve');
            $api->put('/spp/spp_approved/{spp_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_spp_approved');
            $api->post('/spp/attachments', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@upload_attachments_spp');
            $api->get('/spp/spp_need_check_fa', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@get_spp_need_check_fa');
            $api->put('/spp/spp_check_fa/{spp_id}', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@update_spp_check_fa');
            $api->get('/spp/export', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@export_spp');

            $api->get('/cost', 'App\\Api\\V1\\Controllers\\ApiProjectCostController@cost_detail_usage');
            $api->get('/project_budget_cost', 'App\\Api\\V1\\Controllers\\ApiProjectCostController@get_project_performance_summary');
            $api->get('/project_budget_cost_monthly', 'App\\Api\\V1\\Controllers\\ApiProjectCostController@get_project_performance_summary_monthly');


            $api->get('/qrcode/{id}', 'App\\Api\\V1\\Controllers\\ProjectsController@qr_code');
            $api->get('/daily_plan', 'App\\Api\\V1\\Controllers\\ApiProjectDailyPlanController@get_daily_plan');
            $api->get('/daily_plan/carpool', 'App\\Api\\V1\\Controllers\\ProjectsController@carpool_daily_plan');
            $api->get('/daily_plan/status', 'App\\Api\\V1\\Controllers\\ApiProjectDailyPlanController@status_daily_plan');
            $api->post('/add/daily_plan', 'App\\Api\\V1\\Controllers\\ApiProjectDailyPlanController@add_daily_plan');
            $api->get('/daily_plan/approval', 'App\\Api\\V1\\Controllers\\ApiProjectDailyPlanController@approval_daily_plan');
            $api->get('/daily_plan/export', 'App\\Api\\V1\\Controllers\\ApiProjectDailyPlanController@export_surtug');

            $api->put('/search/daily_plan', 'App\\Api\\V1\\Controllers\\ApiProjectDailyPlanController@search_daily_plan');
            $api->put('/daily_plan/update/{id}', 'App\\Api\\V1\\Controllers\\ProjectsController@update_pdp');
            $api->put('/daily_plan/disapprove/{id}', 'App\\Api\\V1\\Controllers\\ProjectsController@disapprove_pdp');
            $api->put('/daily_plan/security/{id}', 'App\\Api\\V1\\Controllers\\ProjectsController@security_remark_pdp');

            $api->get('/task/view/{project_no}', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@view_task');
            $api->post('/create/task', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@create_task');
            $api->post('/task/assignee', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@task_assignee');
            $api->get('/task/users', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@my_project_task');
            $api->get('/task/details/{id}', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@detail_reports');
            $api->post('/task/checkin/{id}', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@check_in');
            $api->post('/task/checkout/{id}', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@check_out');
            $api->post('/task/update_progress/{id}', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@update_task');
            $api->get('/task/attendance/employee', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@attendance_emp');
            $api->post('/task/fat_details/before', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@upload_fat_details_before');
            $api->post('/task/fat_details/after', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@upload_fat_details_after');
            $api->delete('/task/delete/{id}', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@delete_project_task');
            $api->get('/task/fat_by_site', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@fat_by_site');


            $api->get('/cost_type_group', 'App\\Api\\V1\\Controllers\\ProjectsController@project_cost_type_group_list');
            $api->get('/task/view/tree/{project_no}', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@view_tree_task');
        });

        $api->group(['prefix' => 'version'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\ApiAppVersionController@checkversion');
        });

        $api->group(['prefix' => 'users'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\UserController@all_user');
            $api->get('/profile', 'App\\Api\\V1\\Controllers\\UserController@user_profile');
            $api->get('/deviceidcheck', 'App\\Api\\V1\\Controllers\\UserController@deviceidcheck');
            $api->put('/update/{id}', 'App\\Api\\V1\\Controllers\\UserController@update_user');
            $api->put('/updatedevice', 'App\\Api\\V1\\Controllers\\UserController@updatedevice');
            $api->post('/sendverification', 'App\\Api\\V1\\Controllers\\EmailVerificationController@index');
            $api->put('/resetvalidation', 'App\\Api\\V1\\Controllers\\EmailVerificationController@validation_reset');
            $api->put('/forgotpassword', 'App\\Api\\V1\\Controllers\\EmailVerificationController@forgot_password');
            $api->post('/signature/{id}', 'App\\Api\\V1\\Controllers\\UserController@upload_signature');
            $api->post('/photos', 'App\\Api\\V1\\Controllers\\UserController@upload_profile_picture');
        });


        $api->group(['prefix' => 'fom'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\ApiFomController@index');
            $api->post('/create', 'App\\Api\\V1\\Controllers\\ApiFomController@new_fom');
            $api->get('/need_approve', 'App\\Api\\V1\\Controllers\\ApiFomController@fom_need_approval');
            $api->put('/update/{fom_id}', 'App\\Api\\V1\\Controllers\\ApiFomController@update_fom');
            $api->put('/edit', 'App\\Api\\V1\\Controllers\\ApiFomController@edit_fom');
            $api->delete('/delete', 'App\\Api\\V1\\Controllers\\ApiFomController@delete_fom');
        });

        $api->group(['prefix' => 'input'], function (Router $api) {
            $api->get('/project_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@project_list');
            $api->put('/project_list/{project_no}', 'App\\Api\\V1\\Controllers\\ApiProjectListController@update_project_value');
            $api->get('/division_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@division_list');
            $api->post('/project_type_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@project_type_list');
            $api->get('/customer_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@customer_list');
            $api->get('/sow_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@sow_list');
            $api->get('/po_category_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@po_category_list');
            $api->get('/site_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@site_list');
            $api->get('/site_office', 'App\\Api\\V1\\Controllers\\ApiProjectListController@site_office');
            $api->get('/project_manager_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@project_manager_list');
            $api->get('/project_management_fee', 'App\\Api\\V1\\Controllers\\ApiProjectListController@management_fee');
            $api->get('/project_area_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@project_area_list');
            $api->get('/project_payment_term_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@project_payment_term_list');
            $api->get('/currencies_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@currencies_list');
            $api->get('/project_po_status_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@project_po_status_list');
            $api->get('/project_status_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@project_status_list');
            $api->get('/project_parent_list', 'App\\Api\\V1\\Controllers\\ApiProjectListController@project_parent_list');
            $api->get('/emp_list', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@emp_list');
            $api->get('/project_task_list', 'App\\Api\\V1\\Controllers\\ApiProjectTaskController@task_list');
            $api->get('/cost_type_group', 'App\\Api\\V1\\Controllers\\ProjectsController@project_cost_type_group_list');
            $api->get('/company_list', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@company_list');
            $api->get('/emp_level_list', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@employee_level_list');
            $api->get('/emp_type_list', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@employee_type_list');
            $api->get('/emp_status_list', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@employee_status_list');
            $api->get('/attendance_type', 'App\\Api\\V1\\Controllers\\AttendanceController@attendance_type_list');
            $api->get('/budget_by_project', 'App\\Api\\V1\\Controllers\\ApiProjectBudgetsController@budget_by_project_no');
            $api->get('/ec_reason_list', 'App\\Api\\V1\\Controllers\\ApiEmployeesController@ec_reason_list');
            $api->get('/items', 'App\\Api\\V1\\Controllers\\ApiFomController@get_items');
            $api->get('/asset_list', 'App\\Api\\V1\\Controllers\\AssetController@asset_by_emp_list');
            $api->get('/asset_type', 'App\\Api\\V1\\Controllers\\AssetController@get_asset_type');
            $api->get('/asset_group', 'App\\Api\\V1\\Controllers\\AssetController@get_asset_group');
            $api->get('/locations', 'App\\Api\\V1\\Controllers\\ApiInventoryInternalUseController@location_list_row');
        });


        $api->group(['prefix' => 'report'], function (Router $api) {
            $api->get('/001', 'App\\Api\\V1\\Controllers\\ApiProjectCostController@export_project_performance_summary');
        });

       
        $api->group(['prefix' => 'ca_deduction'], function (Router $api) {
            $api->post('/', 'App\\Api\\V1\\Controllers\\ApiUploadSalaryDeductionController@upload_ca_deduction');
        });

        $api->group(['prefix' => 'testing'], function (Router $api) {
            $api->post('/', 'App\\Api\\V1\\Controllers\\ApiTestingController@test');
            $api->get('/due_date', 'App\\Api\\V1\\Controllers\\ApiTestingController@emp_due_date');
        });

        $api->group(['prefix' => 'convert'], function (Router $api) {
            $api->post('/fromjson', 'App\\Api\\V1\\Controllers\\ApiExportController@exportJsonToExcel');
        });

        $api->group(['prefix' => 'ospro'], function (Router $api) {
            $api->post('/post_attendance', 'App\\Api\\V1\\Controllers\\ApiOsproController@post_attendance');
            $api->get('/project_cost', 'App\\Api\\V1\\Controllers\\ApiOsproController@project_cost');
        });


        $api->group(['prefix' => 'audit'], function (Router $api) {
            $api->get('/asset', 'App\\Api\\V1\\Controllers\\AssetController@get_audit_asset');
            $api->get('/export_asset', 'App\\Api\\V1\\Controllers\\AssetController@export_audit_asset');
            $api->post('/asset', 'App\\Api\\V1\\Controllers\\AssetController@insert_audit_asset');
            $api->get('/inventory', 'App\\Api\\V1\\Controllers\\AssetController@get_audit_material');
            $api->post('/inventory', 'App\\Api\\V1\\Controllers\\AssetController@insert_audit_material');
            $api->get('/export_material', 'App\\Api\\V1\\Controllers\\AssetController@export_audit_material');
        });

        // $api->group(['prefix' => 'testing'], function (Router $api) {
        //     $api->get('/', 'App\\Api\\V1\\Controllers\\ApiTestingController@test');
        //     $api->get('/due_date', 'App\\Api\\V1\\Controllers\\ApiTestingController@emp_due_date');
        // });

        $api->group(['prefix' => 'query'], function (Router $api) {
            $api->get('/', 'App\\Api\\V1\\Controllers\\ApiTestingController@test');
        });
        $api->group(['middleware' => 'jwt.auth'], function (Router $api) {
            $api->get('protected', function () {
                return response()->json([
                    'message' => 'Access to protected resources granted! You are seeing this text as you provided the token correctly.'
                ]);
            });

            $api->get('refresh', [
                'middleware' => 'jwt.refresh',
                function () {
                    return response()->json([
                        'message' => 'By accessing this endpoint, you can refresh your access token at each request. Check out this response headers!'
                    ]);
                }
            ]);
        });

        $api->group(['middleware' => 'jwt.auth'], function (Router $api) {
            $api->post('divisions/store', 'App\\Api\\V1\\Controllers\\DivisionController@store');
            $api->get('divisions', 'App\\Api\\V1\\Controllers\\DivisionController@index');
        });


        $api->get('hello', function () {
            return response()->json([
                'message' => 'This is a simple example of item returned by your APIs. Everyone can see it.'
            ]);
        });
    });
});
