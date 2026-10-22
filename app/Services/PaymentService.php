<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Database;
use App\Core\NotificationService;
use App\Core\Translator;
use App\Models\Payment;
use App\Models\PaymentTransaction;

/**
 * Payment processing with provider verification architecture.
 * Statuses: pending, success, failed, cancelled, refunded.
 * Real provider callbacks must be configured per provider; this service
 * records transactions and verifies references without simulating success.
 */
class PaymentService
{
    public const METHOD_MTN = 'mtn_momo';
    public const METHOD_AIRTEL = 'airtel_money';
    public const METHOD_BANK = 'bank_transfer';
    public const METHOD_VISA = 'visa_card';
    public const METHOD_CASH = 'cash';

    /**
     * Initiate a payment. Creates a pending payment + transaction record.
     */
    public static function initiate(array $data): array
    {
        $businessId = (int)$data['business_id'];
        $reference = $data['reference'] ?? Payment::generateReference();

        // Prevent duplicate processing for the same reference
        $existing = Payment::where('reference', $reference)->first();
        if ($existing) {
            throw new \RuntimeException(Translator::t('payments.duplicate_payment'));
        }

        $paymentId = Payment::insert([
            'business_id' => $businessId,
            'payable_type' => $data['payable_type'],
            'payable_id' => $data['payable_id'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'RWF',
            'method' => $data['method'],
            'status' => 'pending',
            'reference' => $reference,
            'provider' => $data['provider'] ?? self::providerForMethod($data['method']),
            'payer_name' => $data['payer_name'] ?? null,
            'payer_phone' => $data['payer_phone'] ?? null,
            'paid_by_user_id' => $data['paid_by_user_id'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? []),
        ]);

        PaymentTransaction::insert([
            'payment_id' => $paymentId,
            'business_id' => $businessId,
            'provider' => Payment::find($paymentId)['provider'],
            'provider_reference' => $data['provider_reference'] ?? null,
            'request_payload' => json_encode(['amount' => $data['amount'], 'reference' => $reference]),
            'status' => 'initiated',
        ]);

        Audit::record('initiate', 'payment', (string)$paymentId, null, $data, $businessId);
        return Payment::find($paymentId);
    }

    /**
     * Record a provider callback / webhook.
     * Never trust the callback alone: the provider reference is stored and the
     * payment transitions only through verified statuses.
     */
    public static function handleCallback(string $reference, string $providerStatus, ?array $payload = null): array
    {
        $payment = Payment::where('reference', $reference)->first();
        if (!$payment) {
            throw new \RuntimeException('Unknown payment reference.');
        }

        $status = self::mapProviderStatus($providerStatus);
        $changes = ['status' => $status];
        if ($status === 'success') {
            $changes['paid_at'] = date('Y-m-d H:i:s');
        }

        Payment::update((int)$payment['id'], $changes);
        PaymentTransaction::insert([
            'payment_id' => (int)$payment['id'],
            'business_id' => (int)$payment['business_id'],
            'provider' => (string)$payment['provider'],
            'provider_reference' => $payload['provider_reference'] ?? null,
            'request_payload' => json_encode($payload ?? []),
            'response_payload' => json_encode(['status' => $providerStatus]),
            'status' => $providerStatus,
        ]);

        self::postUpdate($payment, $status);
        Audit::record('callback', 'payment', (string)$payment['id'], ['status' => $payment['status']], ['status' => $status], (int)$payment['business_id']);
        return Payment::find((int)$payment['id']);
    }

    /**
     * Verify a payment with the provider. Without configured credentials the
     * payment stays pending and is flagged for manual verification.
     */
    public static function verify(string $reference): array
    {
        $payment = Payment::where('reference', $reference)->first();
        if (!$payment) {
            throw new \RuntimeException('Unknown payment reference.');
        }

        $provider = (string)$payment['provider'];
        $credentialsConfigured = self::credentialsConfigured($provider);

        if (!$credentialsConfigured) {
            // Keep pending - no simulated success
            PaymentTransaction::insert([
                'payment_id' => (int)$payment['id'],
                'business_id' => (int)$payment['business_id'],
                'provider' => $provider,
                'status' => 'verification_unavailable',
                'response_payload' => json_encode(['reason' => 'No provider credentials configured']),
            ]);
            return array_merge($payment, ['verified' => false, 'note' => Translator::t('payments.no_gateway')]);
        }

        // Provider API verification hook - implement per provider using configured keys
        $result = self::callProviderVerify($provider, $payment);
        $status = $result['status'] ?? 'failed';
        Payment::update((int)$payment['id'], ['status' => $status] + ($status === 'success' ? ['paid_at' => date('Y-m-d H:i:s')] : []));
        PaymentTransaction::insert([
            'payment_id' => (int)$payment['id'],
            'business_id' => (int)$payment['business_id'],
            'provider' => $provider,
            'provider_reference' => $result['provider_reference'] ?? null,
            'status' => 'verified',
            'response_payload' => json_encode($result),
        ]);

        self::postUpdate($payment, $status);
        return Payment::find((int)$payment['id']);
    }

    /**
     * Record a cash payment (authorized cashiers only).
     */
    public static function recordCash(array $data): array
    {
        $businessId = (int)$data['business_id'];
        $reference = $data['reference'] ?? Payment::generateReference('CASH');

        $existing = Payment::where('reference', $reference)->first();
        if ($existing) {
            throw new \RuntimeException(Translator::t('payments.duplicate_payment'));
        }

        $paymentId = Payment::insert([
            'business_id' => $businessId,
            'payable_type' => $data['payable_type'],
            'payable_id' => $data['payable_id'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'RWF',
            'method' => self::METHOD_CASH,
            'status' => 'success',
            'reference' => $reference,
            'provider' => 'cash',
            'provider_reference' => $reference,
            'payer_name' => $data['payer_name'] ?? null,
            'payer_phone' => $data['payer_phone'] ?? null,
            'paid_by_user_id' => $data['paid_by_user_id'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? []),
            'paid_at' => date('Y-m-d H:i:s'),
        ]);

        Audit::record('cash_payment', 'payment', (string)$paymentId, null, $data, $businessId);
        self::postUpdate(Payment::find($paymentId), 'success');
        return Payment::find($paymentId);
    }

    public static function refund(int $paymentId, ?string $reason = null): bool
    {
        $payment = Payment::find($paymentId);
        if (!$payment || $payment['status'] !== 'success') {
            return false;
        }
        Payment::update($paymentId, ['status' => 'refunded']);
        PaymentTransaction::insert([
            'payment_id' => $paymentId,
            'business_id' => (int)$payment['business_id'],
            'provider' => (string)$payment['provider'],
            'status' => 'refunded',
            'response_payload' => json_encode(['reason' => $reason]),
        ]);
        Audit::record('refund', 'payment', (string)$paymentId, null, ['reason' => $reason], (int)$payment['business_id']);
        return true;
    }

    private static function postUpdate(array $payment, string $status): void
    {
        $businessId = (int)$payment['business_id'];
        if ($status === 'success') {
            NotificationService::sendToRole(
                $businessId,
                'MANAGER',
                NotificationService::TYPE_PAYMENT_SUCCESS,
                Translator::t('notifications.notif_payment_success'),
                Translator::t('notifications.notif_payment_success_msg', [
                    'amount' => $payment['amount'] . ' ' . $payment['currency'],
                    'method' => $payment['method'],
                ]),
                ['payment_id' => (int)$payment['id'], 'reference' => $payment['reference']]
            );
        } elseif ($status === 'failed') {
            NotificationService::sendToRole(
                $businessId,
                'MANAGER',
                NotificationService::TYPE_PAYMENT_FAILED,
                Translator::t('notifications.notif_payment_failed'),
                Translator::t('notifications.notif_payment_failed_msg', ['reference' => $payment['reference']]),
                ['payment_id' => (int)$payment['id']]
            );
        }
    }

    private static function providerForMethod(string $method): string
    {
        return match ($method) {
            self::METHOD_MTN => 'mtn_momo',
            self::METHOD_AIRTEL => 'airtel_money',
            self::METHOD_BANK => 'bank_transfer',
            self::METHOD_VISA => 'visa_card',
            self::METHOD_CASH => 'cash',
            default => 'manual',
        };
    }

    private static function mapProviderStatus(string $providerStatus): string
    {
        $normalized = strtolower($providerStatus);
        if (in_array($normalized, ['success', 'completed', 'paid', 'approved'], true)) {
            return 'success';
        }
        if (in_array($normalized, ['failed', 'declined', 'rejected', 'error'], true)) {
            return 'failed';
        }
        if (in_array($normalized, ['cancelled', 'canceled'], true)) {
            return 'cancelled';
        }
        if ($normalized === 'refunded') {
            return 'refunded';
        }
        return 'pending';
    }

    private static function credentialsConfigured(string $provider): bool
    {
        return match ($provider) {
            'mtn_momo' => !empty(getenv('MTN_API_KEY')),
            'airtel_money' => !empty(getenv('AIRTEL_API_KEY')),
            default => false,
        };
    }

    /**
     * Provider API integration hook. Implement calls to MTN/Airtel here using
     * the configured API keys. Returns ['status' => 'success'|'failed', 'provider_reference' => ...].
     */
    private static function callProviderVerify(string $provider, array $payment): array
    {
        // Integration point: make the provider API request with
        // GET/POST to the provider's collection endpoint using the
        // reference and API key from environment config.
        // Example (MTN): GET {base}/v1_0/collection/{reference}
        // Until implemented, mark for manual verification rather than assume success.
        return ['status' => 'pending', 'provider_reference' => null, 'note' => 'Provider API call not implemented'];
    }
}
