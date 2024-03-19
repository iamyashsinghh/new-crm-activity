<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\CssSelector\XPath\Extension\FunctionExtension;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasAuthenticatedUser;
class nvrmLeadForward extends Model {
    use HasFactory, HasAuthenticatedUser, SoftDeletes,LogsActivity;
    public function getActivitylogOptions(): LogOptions
    {
        $userId = $this->getAuthenticatedUserId();

        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(function (string $eventName) use ($userId) {
                return "This model has been {$eventName} by User ID: {$userId}";
            });
    }
    public function get_nvrm_messages() {
        return $this->hasMany(nvrmMessage::class, 'lead_id', 'lead_id');
    }

    public function get_events(){
        return $this->hasMany(nvEvent::class, 'lead_id', 'lead_id');
    }
    public function get_nvrm_tasks() {
        $auth_user = Auth::guard('nonvenue')->user();
        return nvrmTask::select(
            'nvrm_tasks.id',
            'nvrm_tasks.task_schedule_datetime',
            'nvrm_tasks.follow_up',
            'nvrm_tasks.message',
            'nvrm_tasks.done_with',
            'nvrm_tasks.done_message',
            'nvrm_tasks.done_datetime',
        )->join('team_members as tm', ['tm.id' => 'nvrm_tasks.created_by'])->orderBy('task_schedule_datetime', 'asc')->where(['nvrm_tasks.lead_id' => $this->lead_id, 'tm.role_id' => 3, 'created_by' =>  $auth_user->id])->get();
    }
}
