<?php

namespace App\Listeners;

use App\Events\QueryExecuted;
use App\Repositories\Contracts\QueryLogRepositoryInterface;

class LogQueryListener
{
    public function __construct(
        private QueryLogRepositoryInterface $repo,
    ) {}

    public function handle(QueryExecuted $event): void
    {
        $this->repo->createFromTurn($event->turn, $event->tenantId, $event->userId);
    }
}
