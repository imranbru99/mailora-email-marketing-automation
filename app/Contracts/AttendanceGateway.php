<?php

namespace App\Contracts;

use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;

interface AttendanceGateway
{
    /**
     * @param  array<string,mixed>  $connection
     * @return iterable<AttendancePunch>
     */
    public function pull(string $provider, array $connection, \DateTimeInterface $since): iterable;
}
