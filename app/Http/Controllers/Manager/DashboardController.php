<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Vendor;
use App\Models\nvLead;
use App\Models\LeadForward;
use App\Models\Task;
use App\Models\TeamMember;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller {
    public function index() {
        $auth_user = Auth::guard('manager')->user();

        $vm_members = TeamMember::select('id', 'name', 'venue_name')->where(['parent_id' => $auth_user->id, 'role_id' => 5])->orderBy('venue_name', 'asc')->get(); //role id: 5 is VM;
        $current_month = date('Y-m');
        $current_date = date('Y-m-d');

        $from =  Carbon::today()->startOfMonth();
        $to =  Carbon::today()->endOfMonth();

        foreach ($vm_members as $vm) {
            $vm['leads_received_this_month'] = LeadForward::where(['forward_to' => $vm->id])->where('lead_datetime', 'like', "%$current_month%")->count();
            $vm['leads_received_today'] = LeadForward::where(['forward_to' => $vm->id])->where('lead_datetime', 'like', "%$current_date%")->count();
            $vm['unread_leads_this_month'] = LeadForward::where(['forward_to' => $vm->id, 'read_status' => false])->where('lead_datetime', 'like', "%$current_month%")->count();
            $vm['unread_leads_today'] = LeadForward::where(['forward_to' => $vm->id, 'read_status' => false])->where('lead_datetime', 'like', "%$current_date%")->count();
            $vm['unread_leads_overdue'] = LeadForward::where(['forward_to' => $vm->id, 'read_status' => false])->where('lead_datetime',  '<', Carbon::today())->count();

            $vm['task_schedule_this_month'] = Task::where(['created_by' => $vm->id, 'done_datetime' => null])->where('task_schedule_datetime', 'like', "%$current_month%")->count();
            $vm['task_schedule_today'] = Task::where(['created_by' => $vm->id, 'done_datetime' => null])->where('task_schedule_datetime', 'like', "%$current_date%")->count();
            $vm['task_overdue'] = Task::where(['created_by' => $vm->id, 'done_datetime' => null])->where('task_schedule_datetime', '<', Carbon::today())->count();

            // $vm['recce_schedule_this_month'] = Visit::where(['created_by' => $vm->id, 'done_datetime' => null])->where('visit_schedule_datetime', 'like', "%$current_month%")->count();
            // $vm['recce_schedule_today'] = Visit::where(['created_by' => $vm->id, 'done_datetime' => null])->where('visit_schedule_datetime', 'like', "%$current_date%")->count();
            // $vm['recce_overdue'] = Visit::where(['created_by' => $vm->id, 'done_datetime' => null])->where('visit_schedule_datetime', '<', Carbon::today())->count();
            //$vm['recce_done_this_month'] = LeadForward::join('visits', ['visits.id' => 'lead_forwards.visit_id'])->where(['lead_forwards.forward_to' => $vm->id])->whereBetween('visits.done_datetime', [$from, $to])->count();

            $vm['recce_schedule_this_month'] = LeadForward::join('visits', ['visits.id' => 'lead_forwards.visit_id'])->where(['lead_forwards.forward_to' => $vm->id, 'lead_forwards.source' => 'WB|Team', 'visits.done_datetime' => null, 'visits.deleted_at' => null])->whereBetween('visits.visit_schedule_datetime', [$from, $to])->count();
            $vm['recce_schedule_today'] = LeadForward::join('visits', ['visits.id' => 'lead_forwards.visit_id'])->where(['lead_forwards.forward_to' => $vm->id, 'lead_forwards.source' => 'WB|Team', 'visits.done_datetime' => null, 'visits.deleted_at' => null])->where('visits.visit_schedule_datetime', 'like', "%$current_date%")->count();
            $vm['recce_done_this_month'] = LeadForward::join('visits', ['visits.id' => 'lead_forwards.visit_id'])->where(['lead_forwards.forward_to' => $vm->id, 'lead_forwards.source' => 'WB|Team', 'visits.deleted_at' => null])->whereBetween('visits.done_datetime', [$from, $to])->count();
            $vm['recce_overdue'] = Visit::where(['created_by' => $vm->id, 'done_datetime' => null])->where('visit_schedule_datetime', '<', Carbon::today())->count();

            $vm['bookings_this_month'] = LeadForward::join('bookings', 'bookings.id', 'lead_forwards.booking_id')->where(['lead_forwards.forward_to' => $vm->id, 'lead_forwards.source' => 'WB|Team', 'bookings.deleted_at' => null])->whereBetween('bookings.created_at', [$from, $to])->count();

            $l = $vm->leads_received_this_month;
            $r = $vm->recce_done_this_month;
            $l2r = $l > 0 ? ($r * 100) / $l : $l;
            $vm['l2r'] = number_format($l2r, 1);

            $r = (int) $vm->recce_done_this_month;
            $b = (int) $vm->bookings_this_month;
            if ($b > 0 && $r > 0) {
                $r2c = ($b * 100) / $r;
            } else {
                $r2c = 0;
            }
            $vm['r2c'] = number_format($r2c, 1);
        }

        $vms_id = [];
        foreach ($vm_members as $list) {
            array_push($vms_id, $list->id);
        }

        $total_leads_received = LeadForward::whereIn('forward_to', $vms_id)->count();
        return view('manager.dashboard', compact('vm_members', 'total_leads_received'));
    }

    public function update_profile_image(Request $request) {
        $auth_user = Auth::guard('manager')->user();

        $validate = Validator::make($request->all(), [
            'profile_image' => 'mimes:jpg,jpeg,png,webp|max:1024',
        ]);

        if ($validate->fails()) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => $validate->errors()->first()]);
            return redirect()->back();
        }

        $member = TeamMember::find($auth_user->id);
        if (!$member) {
            abort(404);
        }

        if (is_file($request->profile_image)) {
            $file = $request->file('profile_image');
            $ext = $file->getClientOriginalExtension();

            $sub_str =  substr($member->name, 0, 5);
            $file_name = strtolower(str_replace(' ', '_', $sub_str)) . "_profile" . date('dmyHis') . "." . $ext;
            $path = "memberProfileImages/$file_name";
            Storage::put("public/" . $path, file_get_contents($file));
            $profile_image = asset("storage/" . $path);

            $member->profile_image = $profile_image;
            $member->save();

            session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => 'Image updated.']);
        } else {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Someting went wrong, please contact to administrator.']);
        }

        return redirect()->back();
    }
}
