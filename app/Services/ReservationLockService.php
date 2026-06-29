<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

class ReservationLockService
{
    public function executeWithTerrainLock(int $terrainId, callable $callback, int $seconds = 10)
    {
        return $this->executeWithKey($this->terrainLockKey($terrainId), $callback, $seconds);
    }

    public function executeWithReservationLock(int $reservationId, callable $callback, int $seconds = 10)
    {
        return $this->executeWithKey($this->reservationLockKey($reservationId), $callback, $seconds);
    }

    private function executeWithKey(string $key, callable $callback, int $seconds)
    {
        $lockStore = $this->lockStore();
        $lock = null;

        try {
            $lock = $lockStore->lock($key, $seconds);
        } catch (\Throwable $exception) {
            $lockStore = Cache::store(config('cache.default'));
            try {
                $lock = $lockStore->lock($key, $seconds);
            } catch (\Throwable $fallbackException) {
                return $callback();
            }
        }

        try {
            $acquired = method_exists($lock, 'block') ? $lock->block(5) : $lock->get();
        } catch (\Throwable $exception) {
            return $callback();
        }

        if (! $acquired) {
            return null;
        }

        try {
            return $callback();
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $releaseException) {
                // Silent release failure; lock expiration will eventually free it.
            }
        }
    }

    private function lockStore(): CacheRepository
    {
        try {
            return Cache::store('redis');
        } catch (\Throwable $exception) {
            return Cache::store(config('cache.default'));
        }
    }

    private function terrainLockKey(int $terrainId): string
    {
        return "reservations:terrain:{$terrainId}";
    }

    private function reservationLockKey(int $reservationId): string
    {
        return "reservations:reservation:{$reservationId}";
    }
}
