<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Core\Tenant;
use App\Core\Translator;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    /** Choose payment method for an order or booking. */
    public function pay(): void
    {
        $business = Tenant::requireBusiness();
        $payableType = (string)Security::input('payable_type', 'order');
        $payableId = (int)Security::input('payable_id', 0);

        $amount = 0.0;
        $title = '';
        if ($payableType === 'order') {
            $order = Order::query()->where('id', $payableId)->where('business_id', (int)$business['id'])->first();
            if (!$order) {
                Session::flash('error', 'Order not found.');
                $this->redirect('/orders');
                return;
            }
            $amount = (float)$order['total_amount'];
            $title = 'Order #' . $order['order_number'];
        } elseif ($payableType === 'booking') {
            $booking = Booking::query()->where('id', $payableId)->where('business_id', (int)$business['id'])->first();
            if (!$booking) {
                Session::flash('error', 'Booking not found.');
                $this->redirect('/hotel/bookings');
                return;
            }
            $amount = (float)$booking['total_amount'];
            $title = 'Booking ' . $booking['booking_reference'];
        }

        $pending = Payment::query()
            ->where('business_id', (int)$business['id'])
            ->where('payable_type', $payableType)
            ->where('payable_id', $payableId)
            ->where('status', 'pending')
            ->first();

        echo $this->view('payments/pay', [
            'business' => $business,
            'payableType' => $payableType,
            'payableId' => $payableId,
            'amount' => $amount,
            'title' => $title,
            'pending' => $pending,
        ]);
    }

    /** Initiate a mobile money / bank / card payment. */
    public function initiate(): void
    {
        $business = Tenant::requireBusiness();
        $payableType = (string)Security::input('payable_type', 'order');
        $payableId = (int)Security::input('payable_id', 0);
        $method = (string)Security::input('method', PaymentService::METHOD_MTN);
        $payerPhone = (string)Security::input('payer_phone', '');
        $payerName = (string)Security::input('payer_name', '');

        $validMethods = [PaymentService::METHOD_MTN, PaymentService::METHOD_AIRTEL, PaymentService::METHOD_BANK, PaymentService::METHOD_VISA];
        if (!in_array($method, $validMethods, true)) {
            $this->json(['ok' => false, 'error' => 'Invalid payment method.'], 422);
            return;
        }

        $amount = $this->payableAmount($business['id'], $payableType, $payableId);
        if ($amount <= 0) {
            $this->json(['ok' => false, 'error' => 'Nothing to pay.'], 422);
            return;
        }

        try {
            $payment = PaymentService::initiate([
                'business_id' => (int)$business['id'],
                'payable_type' => $payableType,
                'payable_id' => $payableId,
                'amount' => $amount,
                'method' => $method,
                'payer_phone' => $payerPhone,
                'payer_name' => $payerName,
            ]);

            $this->json([
                'ok' => true,
                'payment' => $payment,
                'message' => 'Payment initiated. Await provider confirmation or verify the transaction reference.',
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** Verify a pending payment by its reference (or provider reference). */
    public function verify(): void
    {
        $reference = (string)Security::input('reference', '');
        if ($reference === '') {
            $this->json(['ok' => false, 'error' => 'Enter the transaction reference.'], 422);
            return;
        }
        try {
            $payment = PaymentService::verify($reference);
            $this->json([
                'ok' => true,
                'payment' => $payment,
                'message' => $payment['status'] === 'success'
                    ? Translator::t('payments.payment_success')
                    : ($payment['note'] ?? Translator::t('payments.payment_pending')),
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** Provider webhook endpoint (no CSRF - external service). */
    public function callback(string $provider): void
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $reference = (string)($payload['reference'] ?? $_GET['reference'] ?? '');
        $status = (string)($payload['status'] ?? $_GET['status'] ?? 'pending');
        if ($reference === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing reference']);
            return;
        }
        try {
            $payment = PaymentService::handleCallback($reference, $status, $payload);
            http_response_code(200);
            echo json_encode(['ok' => true, 'payment_status' => $payment['status']]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /** Cashier records a cash payment. */
    public function recordCash(): void
    {
        $business = Tenant::requireBusiness();
        $payableType = (string)Security::input('payable_type', 'order');
        $payableId = (int)Security::input('payable_id', 0);
        $amount = (float)Security::input('amount', 0);
        $payerName = (string)Security::input('payer_name', '');
        $payerPhone = (string)Security::input('payer_phone', '');

        if ($amount <= 0) {
            $this->json(['ok' => false, 'error' => 'Enter the amount received.'], 422);
            return;
        }

        try {
            $payment = PaymentService::recordCash([
                'business_id' => (int)$business['id'],
                'payable_type' => $payableType,
                'payable_id' => $payableId,
                'amount' => $amount,
                'payer_name' => $payerName,
                'payer_phone' => $payerPhone,
                'paid_by_user_id' => \App\Core\Auth::id(),
            ]);
            $this->postPaymentSuccess($payableType, $payableId, $business['id']);
            $this->json(['ok' => true, 'payment' => $payment, 'message' => Translator::t('payments.cash_recorded')]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function refund(int $id): void
    {
        $business = Tenant::requireBusiness();
        $payment = Payment::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$payment) {
            $this->json(['ok' => false, 'error' => 'Not found.'], 404);
            return;
        }
        $ok = PaymentService::refund($id, (string)Security::input('reason', ''));
        if ($ok) {
            $this->json(['ok' => true, 'message' => 'Payment refunded.']);
        }
        $this->json(['ok' => false, 'error' => 'Unable to refund this payment.'], 422);
    }

    /** List payments for the current business. */
    public function list(): void
    {
        $business = Tenant::requireBusiness();
        $status = Security::input('status', 'all');
        $method = Security::input('method', 'all');
        $q = Payment::query()->where('business_id', (int)$business['id'])->orderBy('id', 'DESC');
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($method !== 'all') {
            $q->where('method', $method);
        }
        $payments = $q->limit(200)->get();
        $stats = Payment::stats((int)$business['id']);
        echo $this->view('payments/list', [
            'payments' => $payments,
            'stats' => $stats,
            'status' => $status,
            'method' => $method,
        ]);
    }

    /** Printable receipt. */
    public function receipt(int $id): void
    {
        $business = Tenant::requireBusiness();
        $payment = Payment::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$payment) {
            $this->json(['ok' => false], 404);
            return;
        }
        $transactions = \App\Models\PaymentTransaction::forPayment($id);
        echo $this->view('payments/receipt', [
            'payment' => $payment,
            'business' => $business,
            'transactions' => $transactions,
        ], false);
    }

    private function payableAmount(string $businessId, string $payableType, int $payableId): float
    {
        if ($payableType === 'order') {
            $order = Order::query()->where('id', $payableId)->where('business_id', (int)$businessId)->first();
            return $order ? (float)$order['total_amount'] : 0.0;
        }
        if ($payableType === 'booking') {
            $booking = Booking::query()->where('id', $payableId)->where('business_id', (int)$businessId)->first();
            return $booking ? (float)$booking['total_amount'] : 0.0;
        }
        if ($payableType === 'subscription') {
            $sub = Subscription::query()->where('id', $payableId)->where('business_id', (int)$businessId)->first();
            return $sub ? (float)$sub['amount'] : 0.0;
        }
        return 0.0;
    }

    private function postPaymentSuccess(string $payableType, int $payableId, string $businessId): void
    {
        if ($payableType === 'order') {
            Order::update($payableId, ['status' => 'confirmed', 'confirmed_at' => date('Y-m-d H:i:s')]);
        } elseif ($payableType === 'booking') {
            Booking::update($payableId, ['payment_status' => 'paid']);
        }
    }
}
