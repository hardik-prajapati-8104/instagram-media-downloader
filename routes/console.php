<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('media:cleanup')->everyFiveMinutes()->withoutOverlapping(10);
Schedule::command('queue:prune-failed --hours=168')->daily();
