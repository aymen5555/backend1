<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class InspectNotifications extends Command
{
    protected $signature = 'inspect:notifications {limit=10}';

    protected $description = 'Show latest database notifications with parsed data';

    public function handle(): int
    {
        $limit = (int) $this->argument('limit');
        $notifs = DatabaseNotification::latest()->take($limit)->get();
        foreach ($notifs as $n) {
            $this->line('---');
            $this->line('id: '.$n->id);
            $this->line('notifiable_type: '.$n->notifiable_type);
            $this->line('notifiable_id: '.$n->notifiable_id);
            $this->line('type: '.($n->data['type'] ?? '')); 
            $this->line('message: '.($n->data['message'] ?? '')); 
            $this->line('raw: '.json_encode($n->data));
        }

        return Command::SUCCESS;
    }
}
