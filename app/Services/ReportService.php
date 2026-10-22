<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Tenant;
use App\Models\Business;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\Room;
use App\Models\Subscription;
use App\Models\User;

/**
 * Dashboard statistics and reports.
 */
class ReportService
{
    /** Platform-wide statistics (Super Admin). */
    public static function platformStats(): array
    {
        $businesses = Business::stats();
        $subscriptions = Subscription::stats();
        $revenue = Business::revenue();

        $owners = User::countByRole('BUSINESS_OWNER');
        $managers = User::countByRole('MANAGER');
        $employees = (int)Database::run(
            'SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL AND role NOT IN ("SUPER_ADMIN","BUSINESS_OWNER","CUSTOMER")'
        )->fetch()['c'];
        $customers = User::countByRole('CUSTOMER');

        $recentBusinesses = Business::query()->whereNull('deleted_at')->orderBy('id', 'DESC')->limit(8)->get();

        // Subscription analytics: active by package
        $packageAnalytics = Database::run(
            'SELECT p.name, p.price_monthly, p.price_yearly, COUNT(s.id) AS subs
             FROM subscriptions s JOIN packages p ON p.id = s.package_id
             WHERE s.status = "active" GROUP BY p.id ORDER BY subs DESC'
        )->fetchAll();

        // Registrations per day (last 14 days)
        $registrations = Database::run(
            'SELECT DATE(created_at) AS day, COUNT(*) AS total FROM businesses WHERE deleted_at IS NULL
             GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 14'
        )->fetchAll();

        return [
            'businesses' => $businesses,
            'subscriptions' => $subscriptions,
            'revenue' => $revenue,
            'owners' => $owners,
            'managers' => $managers,
            'employees' => $employees,
            'customers' => $customers,
            'recent_businesses' => $recentBusinesses,
            'package_analytics' => $packageAnalytics,
            'registrations' => $registrations,
        ];
    }

    /** Business dashboard overview combining restaurant + hotel metrics. */
    public static function businessOverview(int $businessId, string $type): array
    {
        $data = [];
        if (in_array($type, ['RESTAURANT', 'HOTEL_AND_RESTAURANT'], true)) {
            $data['restaurant'] = [
                'orders' => Order::stats($businessId),
                'tables' => \App\Models\RestaurantTable::stats($businessId),
                'products' => Product::countForBusiness($businessId),
                'low_stock' => count(Product::lowStock($businessId)),
                'categories' => Category::forBusiness($businessId),
            ];
            $data['restaurant']['staff_on_duty'] = (int)Database::run(
                'SELECT COUNT(*) AS c FROM business_users WHERE business_id = ? AND is_active = 1',
                [$businessId]
            )->fetch()['c'];
        }
        if (in_array($type, ['HOTEL', 'HOTEL_AND_RESTAURANT'], true)) {
            $data['hotel'] = [
                'rooms' => Room::stats($businessId),
                'bookings' => \App\Models\Booking::stats($businessId),
                'housekeeping' => \App\Models\Housekeeping::stats($businessId),
            ];
        }
        $data['payments'] = \App\Models\Payment::stats($businessId);
        $data['subscription'] = Subscription::currentForBusiness($businessId);
        if ($data['subscription']) {
            Subscription::refreshStatus((int)$data['subscription']['id']);
            $data['subscription']['package'] = Package::find((int)$data['subscription']['package_id']);
        }
        $data['reviews'] = \App\Models\Review::average($businessId);
        return $data;
    }

    /** Sales trend for the last N days (restaurant). */
    public static function salesTrend(int $businessId, int $days = 14): array
    {
        $from = date('Y-m-d 00:00:00', strtotime("-$days days"));
        return Database::run(
            'SELECT DATE(ordered_at) AS day, COUNT(*) AS orders, COALESCE(SUM(total_amount),0) AS revenue
             FROM orders
             WHERE business_id = ? AND ordered_at >= ? AND status NOT IN ("cancelled")
             GROUP BY DATE(ordered_at) ORDER BY day ASC',
            [$businessId, $from]
        )->fetchAll();
    }

    /** Occupancy trend for the last N weeks (hotel). */
    public static function occupancyTrend(int $businessId, int $weeks = 8): array
    {
        $from = date('Y-m-d', strtotime("-$weeks weeks"));
        return Database::run(
            'SELECT YEAR(check_in_date) AS yr, WEEK(check_in_date) AS wk, COUNT(*) AS bookings,
                    SUM(nights) AS nights
             FROM bookings
             WHERE business_id = ? AND check_in_date >= ? AND status NOT IN ("cancelled")
             GROUP BY yr, wk ORDER BY check_in_date ASC',
            [$businessId, $from]
        )->fetchAll();
    }

    public static function auditTrail(?int $businessId = null, int $limit = 100): array
    {
        $sql = 'SELECT a.*, u.name AS user_name, b.name AS business_name FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                LEFT JOIN businesses b ON b.id = a.business_id';
        $params = [];
        if ($businessId !== null) {
            $sql .= ' WHERE a.business_id = ?';
            $params[] = $businessId;
        }
        $sql .= ' ORDER BY a.id DESC LIMIT ' . (int)$limit;
        return Database::run($sql, $params)->fetchAll();
    }

    public static function contactMessages(int $limit = 50): array
    {
        return ContactMessage::query()->orderBy('id', 'DESC')->limit($limit)->get();
    }

    public static function unreadContactCount(): int
    {
        return ContactMessage::unreadCount();
    }
}
