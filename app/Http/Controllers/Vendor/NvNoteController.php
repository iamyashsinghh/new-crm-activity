<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\nvLeadForward;
use App\Models\nvLeadForwardInfo;
use App\Models\nvLead;
use App\Models\nvrmLeadForward;
use App\Models\nvNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class NvNoteController extends Controller {
    public function manage_ajax($note_id) {
        $note = nvNote::where(['id' => $note_id, 'created_by' => Auth::guard('vendor')->user()->id])->first();
        if (!$note) {
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong.'], 500);
        } else {
            return response()->json(['success' => true, 'note' => $note]);
        }
    }

    public function manage_process(Request $request, $note_id = 0) {
        $validate = Validator::make($request->all(), [
            'lead_id' => 'required|exists:nv_leads,id',
            'note_message' => 'required|string',
        ]);

        if ($validate->fails()) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => $validate->errors()->first()]);
            return redirect()->back();
        }

        $auth_user = Auth::guard('vendor')->user();
        if ($note_id > 0) {
            $msg = "Note updated successfully.";
            $note = nvNote::where(['id' => $note_id, 'created_by' => $auth_user->id])->first();
            if (!$note) {
                session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong, please try again later.']);
                return redirect()->back();
            }
        } else {
            $msg = "Note added successfully.";
            $note = new nvNote();
            $note->lead_id = $request->lead_id;
            $note->created_by = $auth_user->id;
        }

        $note->message = $request->note_message;
        $note->is_solved = '0';
        $note->save();

        $lead_forwards = nvLeadForward::where(['lead_id' => $request->lead_id, 'forward_to' => $auth_user->id])->first();
        $lead_forwards->read_status = true;
        $lead_forwards->save();

        $nvrm_lead_forwards = nvrmLeadForward::where(['lead_id' => $request->lead_id])->first();
        $nvrm_lead_forwards->whatsapp_msg_time = Carbon::now();     
        $nvrm_lead_forwards->save();

        $nvlead = nvLead::where(['id' => $request->lead_id])->first();
        $nvlead->whatsapp_msg_time = Carbon::now();
        $nvlead->save();

        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => $msg]);
        return redirect()->back();
    }

    public function delete($note_id) {
        $auth_user = Auth::guard('vendor')->user();
        $note = nvNote::where(['id' => $note_id, 'created_by' => $auth_user->id])->first();
        if (!$note) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong, please try again later.']);
            return redirect()->back();
        }

        $note->delete();
        session()->flash('status', ['success' => true, 'alert_type' => 'success', 'message' => 'Note Deleted.']);
        return redirect()->back();
    }
}
