<?php

namespace App\Support\Integrations;

use App\Contracts\AttendanceGateway;
use App\Services\ModuleRegistry;
use Carbon\Carbon;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;

class AttendanceAdapter implements AttendanceGateway
{
    /**
     * @param  array<string,mixed>  $connection
     * @return iterable<AttendancePunch>
     */
    public function pull(string $provider, array $connection, \DateTimeInterface $since): iterable
    {
        abort_unless(app(ModuleRegistry::class)->enabled('attendance'), 403);

        $driver = AttendanceHub::provider($provider);
        if (! $driver->connect(DeviceConnection::fromArray($connection))) {
            throw new \RuntimeException('Could not connect to the attendance device.');
        }
        try {
            return $driver->pullAttendanceLogs(since: Carbon::instance($since));
        } finally {
            $driver->disconnect();
        }
    }
}
