<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\Dashboard;
use Auth;

class DashboardController extends Controller
{
    private $butler;
    private $dashboard;

    /**
     * DashboardController constructor.
     * @param Request $request
     */
    function __construct(Request $request)
    {
        $this->butler = $request->get('butler');
        $this->dashboard = new Dashboard($this->butler);
    }

    /**
     * Get data
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        $total_revenue = $this->dashboard->totalRevenue();
        $total_orders = $this->dashboard->totalOrders();
        $total_customers = $this->dashboard->totalCustomers();
        $total_products = $this->dashboard->totalProducts();
        $revenue_month = $this->dashboard->revenueMonth($request->payload, false);
        $revenue_month_recommended = $this->dashboard->revenueMonth($request->payload, true);

        $data = [
            'total_revenue' => $total_revenue,
            'total_orders' => $total_orders,
            'total_customers' => $total_customers,
            'total_product' => $total_products,
            'revenue_month' => $revenue_month,
            'revenue_month_recommended' => $revenue_month_recommended
        ];

        return response()->json($data);
    }
}
