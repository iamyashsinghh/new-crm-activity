<?php

namespace App\Http\Controllers\NonVenue;

use App\Http\Controllers\Controller;
use App\Mail\NotifyVendorLead;
use App\Models\nvEvent;
use App\Models\nvLead;
use App\Models\nvLeadForward;
use App\Models\nvLeadForwardInfo;
use App\Models\nvrmLeadForward;
use App\Models\Vendor;
use App\Models\WhatsappCampain;
use App\Models\VendorCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class NvLeadController extends Controller
{
    private $current_timestamp;
    public function __construct()
    {
        $this->current_timestamp = date('Y-m-d H:i:s');
    }
    public function list(Request $request, $dashboard_filters = null)
    {
        $filter_params = "";
        if ($request->lead_status != null) {
            $filter_params = ['lead_status' => $request->lead_status];
        }
        if ($request->lead_read_status != null) {
            $filter_params = ['lead_read_status' => $request->lead_read_status];
        }
        if ($request->service_status != null) {
            $filter_params = ['service_status' => $request->service_status];
        }
        if ($request->has_rm_message != null) {
            $filter_params = ['has_rm_message' => $request->has_rm_message];
        }
        if ($request->event_from_date != null) {
            $filter_params = ['event_from_date' => $request->event_from_date, 'event_to_date' => $request->event_to_date];
        }
        if ($request->lead_from_date != null) {
            $filter_params = ['lead_from_date' => $request->lead_from_date, 'lead_to_date' => $request->lead_to_date];
        }
        if ($request->lead_done_from_date != null) {
            $filter_params = ['lead_done_from_date' => $request->lead_done_from_date, 'lead_done_to_date' => $request->lead_done_to_date];
        }

        $page_heading = $filter_params ? "Leads - Filtered" : "Leads";

        if ($dashboard_filters !== null) {
            $filter_params = ['dashboard_filters' => $dashboard_filters];
            $page_heading = ucwords(str_replace("_", " ", $dashboard_filters));
        }
        $auth_user = Auth::guard('nonvenue')->user();
        $whatsapp_campaigns = WhatsappCampain::select('id','name')->where('status', 1)->where('assign_to' ,$auth_user->id)->get();
        return view('nonvenue.lead.list', compact('page_heading', 'filter_params', 'whatsapp_campaigns'));
    }
    public function ajax_list(Request $request)
    {
        $auth_user = Auth::guard('nonvenue')->user();
        $leads = nvrmLeadForward::select(
            'nvrm_lead_forwards.id as forward_id',
            'nvrm_lead_forwards.lead_id',
            'nvrm_lead_forwards.lead_datetime as lead_date',
            'nvrm_lead_forwards.name',
            'nvrm_lead_forwards.mobile',
            'nvrm_lead_forwards.lead_status',
            'nvrm_lead_forwards.event_datetime as event_date',
            'nvrm_lead_forwards.read_status',
            'nvrm_lead_forwards.is_whatsapp_msg',
            DB::raw("count(fwd_info.id) as forwarded_count"),
        )->leftJoin('nv_lead_forward_infos as fwd_info', ['nvrm_lead_forwards.lead_id' => 'fwd_info.lead_id']);

        if ($request->lead_status != null) {
            $leads->where('nvrm_lead_forwards.lead_status', $request->lead_status);
        }
        if ($request->lead_read_status != null) {
            $leads->where('nvrm_lead_forwards.read_status', '=', $request->lead_read_status);
        }
        if ($request->event_from_date != null) {
            $from = Carbon::make($request->event_from_date);
            if ($request->event_to_date != null) {
                $to = Carbon::make($request->event_to_date)->endOfDay();
            } else {
                $to = Carbon::make($request->event_from_date)->endOfDay();
            }
            $leads->whereBetween('nvrm_lead_forwards.event_datetime', [$from, $to]);
        }
        if ($request->lead_from_date != null) {
            $from = Carbon::make($request->lead_from_date);
            if ($request->lead_to_date != null) {
                $to = Carbon::make($request->lead_to_date)->endOfDay();
            } else {
                $to = Carbon::make($request->lead_from_date)->endOfDay();
            }
            $leads->whereBetween('nvrm_lead_forwards.lead_datetime', [$from, $to]);
        }
        if ($request->lead_done_from_date != null) {
            $from = Carbon::make($request->lead_done_from_date);
            if ($request->lead_done_to_date != null) {
                $to = Carbon::make($request->lead_done_to_date)->endOfDay();
            } else {
                $to = Carbon::make($request->lead_done_from_date)->endOfDay();
            }
            $leads->where('nvrm_lead_forwards.lead_status', 'Done')->whereBetween('nvrm_lead_forwards.updated_at', [$from, $to]);
        }
        if ($request->has_rm_message != null) {
            if ($request->has_rm_message == "yes") {
                $leads->join('nvrm_messages as rm_msg', 'nvrm_lead_forwards.lead_id', '=', 'rm_msg.lead_id');
            } else {
                $leads->leftJoin('nvrm_messages as rm_msg', 'nvrm_lead_forwards.lead_id', '=', 'rm_msg.lead_id')->where('rm_msg.title', null);
            }
        }
        if ($request->dashboard_filters != null) {
            if ($request->dashboard_filters == "leads_received_this_month") {
                $from = Carbon::today()->startOfMonth();
                $to = Carbon::today()->endOfMonth();
                $leads->whereBetween('nvrm_lead_forwards.lead_datetime', [$from, $to]);
            } elseif ($request->dashboard_filters == "leads_received_today") {
                $from = Carbon::today()->startOfDay();
                $to = Carbon::today()->endOfDay();
                $leads->whereBetween('nvrm_lead_forwards.lead_datetime', [$from, $to]);
            } elseif ($request->dashboard_filters == "unread_leads_this_month") {
                $from = Carbon::today()->startOfMonth();
                $to = Carbon::today()->endOfMonth();
                $leads->whereBetween('nvrm_lead_forwards.lead_datetime', [$from, $to])->where('nvrm_lead_forwards.read_status', false);
            } elseif ($request->dashboard_filters == "unread_leads_today") {
                $from = Carbon::today()->startOfDay();
                $to = Carbon::today()->endOfDay();
                $leads->whereBetween('nvrm_lead_forwards.lead_datetime', [$from, $to])->where('nvrm_lead_forwards.read_status', false);
            } elseif ($request->dashboard_filters == "total_unread_leads_overdue") {
                $leads->where('nvrm_lead_forwards.lead_datetime', '>=', Carbon::parse('2024-02-01')->startOfDay())
                    ->where('nvrm_lead_forwards.lead_datetime', '<=', Carbon::now())
                    ->where('nvrm_lead_forwards.read_status', false);
            } elseif ($request->dashboard_filters == "forward_leads_this_month") {
                $from = Carbon::today()->startOfMonth();
                $to = Carbon::today()->endOfMonth();
                $leads->whereBetween('fwd_info.updated_at', [$from, $to]);
            } elseif ($request->dashboard_filters == "forward_leads_today") {
                $from = Carbon::today()->startOfDay();
                $to = Carbon::today()->endOfDay();
                $leads->whereBetween('fwd_info.updated_at', [$from, $to]);
            }
        }

        $leads = $leads->groupBy('nvrm_lead_forwards.lead_id')->get();
        return datatables($leads)->toJson();
    }

    public function edit_process(Request $request, $forward_id)
    {
        $forward = nvrmLeadForward::find($forward_id);
        $forward->name = $request->name;
        $forward->email = $request->email;
        $forward->alternate_mobile = $request->alternate_mobile_number;
        $forward->save();

        $lead = nvLead::find($forward->lead_id);
        $lead->name = $request->name;
        $lead->email = $request->email;
        $lead->alternate_mobile = $request->alternate_mobile_number;
        $lead->save();
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => 'Lead updated successfully.']);
        return redirect()->back();
    }

    public function add_process(Request $request)
    {
        $is_name_valid = $request->name !== null ? "required|string|max:255" : "";
        $is_email_valid = $request->email !== null ? "required|email" : "";
        $is_alt_mobile_valid = $request->alternate_mobile_number !== null ? "required|digits:10|" : "";
        $is_pax_valid = $request->number_of_guest !== null ? "required|int" : "";
        $validate = Validator::make($request->all(), [
            'name' => $is_name_valid,
            'email' => $is_email_valid,
            'alternate_mobile_number' => $is_alt_mobile_valid,
            'mobile_number' => "required|digits:10",
            'number_of_guest' => $is_pax_valid,
        ]);

        if ($validate->fails()) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => $validate->errors()->first()]);
            return redirect()->back();
        }

        $auth_user = Auth::guard('nonvenue')->user();
        $exist_lead = nvLead::where('mobile', $request->mobile_number)->first();
        if ($exist_lead) {
            $exist_forward = nvrmLeadForward::where(['lead_id' => $exist_lead->id, 'forward_to' => $auth_user->id])->first();
            if ($exist_forward) {
                $lead_link = route('nonvenue.lead.view', $exist_forward->lead_id);
                session()->flash('status', ['success' => true, 'alert_type' => 'info', 'message' => "Lead is already exist with this mobile number. Click on the link below to view the lead. <a href='$lead_link'><b>$lead_link</b></a>"]);
            } else {
                session()->flash('status', ['success' => true, 'alert_type' => 'warning', 'message' => "Something went wrong, Please contact to your manager."]);
            }
            return redirect()->back();
        }

        $lead = new nvLead();
        $lead->created_by = $auth_user->id;
        $lead->lead_datetime = $this->current_timestamp;
        $lead->name = $request->name;
        $lead->email = $request->email;
        $lead->mobile = $request->mobile_number;
        $lead->alternate_mobile = $request->alternate_mobile_number;
        $lead->address = $request->address;
        $lead->event_datetime = $request->event_date ? $request->event_date . " " . date('H:i:s') : '';
        $lead->save();

        if ($request->event_date != null) {
            $event = new nvEvent();
            $event->created_by = $auth_user->id;
            $event->lead_id = $lead->id;
            $event->event_name = $request->event_name;
            $event->event_datetime = $request->event_date . " " . date('H:i:s');
            $event->pax = $request->number_of_guest;
            $event->event_slot = $request->event_slot;
            $event->venue_name = $request->venue_name;
            $event->save();
        }

        $forward = new nvrmLeadForward();
        $forward->lead_id = $lead->id;
        $forward->forward_to = $auth_user->id;
        $forward->lead_datetime = $this->current_timestamp;
        $forward->name = $request->name;
        $forward->email = $request->email;
        $forward->mobile = $request->mobile_number;
        $forward->alternate_mobile = $request->alternate_mobile_number;
        $forward->address = $request->address;
        $forward->event_datetime = $request->event_date ? $request->event_date . " " . date('H:i:s') : '';
        $forward->lead_status = "Active";
        $forward->read_status = false;
        $forward->save();

        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Lead added successfully."]);
        return redirect()->back();
    }

    public function view($lead_id)
    {
        // $auth_user = Auth::guard('nonvenue')->user();
        $service_categories = VendorCategory::select('id', 'name')->get();
        // $lead = nvrmLeadForward::where(['forward_to' => $auth_user->id, 'lead_id' => $lead_id])->first();
        $lead = nvrmLeadForward::where(['lead_id' => $lead_id])->first();
        if (!$lead) {
            abort(404);
        }
        return view('nonvenue.lead.view', compact('lead', 'service_categories'));
    }

    public function get_forward_info($lead_id = 0)
    {
        $auth_user = Auth::guard('nonvenue')->user();
        try {
            $lead_forwards = nvLeadForwardInfo::from('nv_lead_forward_infos as fwd_info')->select(
                'v.name',
                'v.business_name',
                'fwd_info.updated_at as lead_forwarded_at',
                'vc.name as category_name',
            )->Join('vendors as v', 'fwd_info.forward_to', 'v.id')
                ->join('vendor_categories as vc', 'v.category_id', 'vc.id')
                ->where(['fwd_info.forward_from' => $auth_user->id, 'fwd_info.lead_id' => $lead_id])->orderBy('fwd_info.updated_at', 'desc')->get();
            return response()->json(['success' => true, 'lead_forwards' => $lead_forwards]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong.']);
        }
    }

    public function status_update(Request $request, $forward_id, $status = "Done")
    {
        $auth_user = Auth::guard('nonvenue')->user();
        $lead_forward = nvrmLeadForward::where(['id' => $forward_id, 'forward_to' => $auth_user->id])->first();

        if (!$lead_forward) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => "Something went wrong."]);
            return redirect()->back();
        }

        if ($status == "Active") {
            $lead_forward->lead_datetime = $this->current_timestamp;
            $lead_forward->lead_status = "Active";
            $lead_forward->read_status = false;
            $lead_forward->done_title = null;
            $lead_forward->done_message = null;
        } else {
            $validate = Validator::make($request->all(), [
                'forward_id' => 'required|exists:nvrm_lead_forwards,id',
                'done_title' => 'required|string',
            ]);

            if ($validate->fails()) {
                session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => $validate->errors()->first()]);
                return redirect()->back();
            }

            $lead_forward->lead_status = "Done";
            $lead_forward->read_status = true;
            $lead_forward->done_title = $request->done_title;
            $lead_forward->done_message = $request->done_message;
        }

        $lead_forward->save();

        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => "Lead status updated."]);
        return redirect()->back();
    }

    public function lead_forward(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'forward_id' => 'required|int|exists:nvrm_lead_forwards,id',
            'forward_vendors_id' => 'required',
        ]);

        if ($validate->fails()) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => $validate->errors()->first()]);
            return redirect()->back();
        }

        $auth_user = Auth::guard('nonvenue')->user();
        $forward = nvrmLeadForward::where(['id' => $request->forward_id])->first();
        if (!$forward) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong.']);
            return redirect()->back();
        }

        foreach ($request->forward_vendors_id as $vendor_id) {
            $exist_lead_forward = nvLeadForward::where(['lead_id' => $forward->lead_id, 'forward_to' => $vendor_id])->first();
            if ($exist_lead_forward) {
                $lead_forward = $exist_lead_forward;
            } else {
                $lead_forward = new nvLeadForward();
                $lead_forward->lead_id = $forward->lead_id;
                // $lead_forward->forward_from = $auth_user->id;
                $lead_forward->forward_to = $vendor_id;
            }
            $lead_forward->lead_datetime = $this->current_timestamp;
            $lead_forward->name = $forward->name;
            $lead_forward->email = $forward->email;
            $lead_forward->mobile = $forward->mobile;
            $lead_forward->alternate_mobile = $forward->alternate_mobile;
            $lead_forward->lead_status = "Active";
            $lead_forward->read_status = false;
            $lead_forward->done_title = null;
            $lead_forward->done_message = null;
            $lead_forward->event_datetime = $forward->event_datetime;
            $lead_forward->save();

            $lead_forward_info = nvLeadForwardInfo::where(['lead_id' => $forward->lead_id, 'forward_to' => $vendor_id])->first();
            if (!$lead_forward_info) {
                $lead_forward_info = new nvLeadForwardInfo();
                $lead_forward_info->lead_id = $forward->lead_id;
                $lead_forward_info->forward_from = $auth_user->id;
                $lead_forward_info->forward_to = $vendor_id;
            } else {
                $lead_forward_info->forward_from = $auth_user->id;
            }
            $lead_forward_info->updated_at = $this->current_timestamp;
            $lead_forward_info->save();

            $vendor = Vendor::find($vendor_id);

            $message = substr($forward->mobile, 0, -2) . "XX";
            $lead_id = $forward->lead_id;
            // if ($vendor->category_id == 4) {
            //     $number = $message;
            //     $event = nvEvent::where(['lead_id' => $forward->lead_id])->orderBy('id', 'desc')->first();
            //     $eventdate = $event->event_datetime;
            //     $pax = $event->pax;
            //     $this->notify_wbvendor_lead_using_interakt($vendor->mobile, $vendor->business_name, $number, $eventdate, $pax, $lead_id);
            // } else {
            //     $this->notify_vendor_lead_using_interakt($vendor->mobile, $vendor->business_name, $message, $lead_id);
            // }
                   $this->notify_vendor_lead_using_interakt($vendor->mobile, $vendor->business_name, $message, $lead_id);

            if ($vendor->email != null) {
                $event = nvEvent::where(['lead_id' => $forward->lead_id, 'created_by' => $auth_user->id])->orderBy('id', 'desc')->first();
                $data = [
                    'lead_name' => $forward->name ?: 'N/A',
                    'event_name' => $event ? $event->event_name : 'N/A',
                    'event_date' => $forward->event_datetime ? date('d-M-Y', strtotime($forward->event_datetime)) : 'N/A',
                    'event_slot' => $event ? $event->event_slot : 'N/A',
                    'lead_email' => $forward->email ?: 'N/A',
                    'lead_mobile' => $forward->mobile ?: 'N/A',
                ];
                if (env('MAIL_STATUS') === true) {
                    Mail::mailer('smtp2')->to($vendor->email)->send(new NotifyVendorLead($data));
                }
            }
        }
        $forward->read_status = true;
        $forward->save();

        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => 'Lead forwarded successfully.']);
        return redirect()->back();
    }

    public function get_vendor_by_category($category_id)
    {
        // $default_vendors = Vendor::select('id', 'name', 'business_name')->where(['category_id' => $category_id, 'status' => 1])->whereIn('id', [58, 80])->get();
        // $vendors = Vendor::select('id', 'name', 'business_name')->where(['category_id' => $category_id, 'status' => 1])->whereNotIn('id', [58, 80])->get();
        $vendors = Vendor::select('id', 'name', 'business_name', 'group_name')->where(['category_id' => $category_id, 'status' => 1])->orderBy('group_name')->get();
        if ($vendors && sizeof($vendors) > 0) {
            // return response()->json(['success' => true, 'vendors' => $vendors, 'default_vendors' => $default_vendors]);
            return response()->json(['success' => true, 'vendors' => $vendors]);
        } else {
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Vendor's not found."]);
        }
    }
}
