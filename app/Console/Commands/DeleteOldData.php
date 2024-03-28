<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use DB;

class DeleteOldData extends Command
{
    protected $signature = 'data:delete-old';
    protected $description = 'Deletes data older than one year from specified tables.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
{
    DB::beginTransaction();

    try {
        $leadIds = Lead::where('updated_at', '<', now()->subYear())->pluck('lead_id');
        \App\Models\LeadForward::whereIn('lead_id', $leadIds)->forceDelete();
        $taskIds = \App\Models\Task::whereIn('lead_id', $leadIds)->pluck('id');
        \App\Models\LeadForward::whereIn('task_id', $taskIds)->forceDelete();
        \App\Models\Event::whereIn('lead_id', $leadIds)->forceDelete();
        \App\Models\Booking::whereIn('lead_id', $leadIds)->forceDelete();
        \App\Models\Note::whereIn('lead_id', $leadIds)->forceDelete();
        \App\Models\Task::whereIn('lead_id', $leadIds)->forceDelete();
        \App\Models\Visit::whereIn('lead_id', $leadIds)->forceDelete();
        \App\Models\RmMessage::whereIn('lead_id', $leadIds)->forceDelete();
        \App\Models\LeadForwardInfo::whereIn('lead_id', $leadIds)->forceDelete();
        $affectedLeads = Lead::whereIn('lead_id', $leadIds)->forceDelete();
        DB::commit();
        $this->info("Successfully deleted $affectedLeads leads and their related records.");
    } catch (\Exception $e) {
        DB::rollBack();
        $this->error("An error occurred: " . $e->getMessage());
    }
}
}
