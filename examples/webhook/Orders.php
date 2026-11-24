<?php

declare(strict_types=1);

/**
 * A tiny order store in a JSON file, standing in for your database. Marking an order paid is
 * idempotent: the return URL and the webhook can both report the same payment, and a webhook
 * can be delivered more than once.
 */
final class Orders
{
    public function __construct(private readonly string $file) {}

    /**
     * @param array<string, array{total: string, currency: string}> $orders
     */
    public function seed(array $orders): void
    {
        $data = [];
        foreach ($orders as $id => $order) {
            $data[$id] = $order + ['status' => 'new', 'paid_by' => null, 'payment_id' => null];
        }
        $this->save($data);
    }

    /**
     * @return array{total: string, currency: string, status: string, paid_by: ?string, payment_id: ?string}|null
     */
    public function find(string $id): ?array
    {
        return $this->load()[$id] ?? null;
    }

    /**
     * @return bool false when the order was already paid (nothing changed)
     */
    public function markPaid(string $id, string $source, ?string $paymentId = null): bool
    {
        $data = $this->load();
        if (!isset($data[$id]) || $data[$id]['status'] === 'paid') {
            return false;
        }
        $data[$id]['status'] = 'paid';
        $data[$id]['paid_by'] = $source;
        $data[$id]['payment_id'] = $paymentId;
        $this->save($data);

        return true;
    }

    public function markFailed(string $id): void
    {
        $data = $this->load();
        if (isset($data[$id]) && $data[$id]['status'] === 'new') {
            $data[$id]['status'] = 'failed';
            $this->save($data);
        }
    }

    /**
     * @return array<string, array{total: string, currency: string, status: string, paid_by: ?string, payment_id: ?string}>
     */
    public function all(): array
    {
        return $this->load();
    }

    /**
     * @return array<string, array{total: string, currency: string, status: string, paid_by: ?string, payment_id: ?string}>
     */
    private function load(): array
    {
        $json = is_file($this->file) ? json_decode((string) file_get_contents($this->file), true) : [];

        return is_array($json) ? $json : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function save(array $data): void
    {
        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);
    }
}
