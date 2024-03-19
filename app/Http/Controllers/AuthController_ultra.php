<?php

namespace App\Http\Controllers;

use App\Mail\LoginMail;
use App\Models\LoginInfo;
use App\Models\TeamMember;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;

class AuthController extends Controller
{
    public function login()
    {
        return view('login');
    }

    public function login_verify(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'phone_number' => "required|digits:10",
            'login_type' => 'required|string|min:4|max:6',
        ]);

        if ($validate->fails()) {
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => $validate->errors()->first()], 400);
        }

        if ($request->login_type === "team") {
            $user = TeamMember::where('mobile', $request->phone_number)->first();
        } else if ($request->login_type === "vendor") {
            $user = Vendor::where('mobile', $request->phone_number)->first();
        }

        if (!$user) {
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => 'Invalid credentials.'], 400);
        } else if ($user->status == 0) {
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => 'Profile is inactive, kindly contact to your manager.'], 400);
        }

        try {
            // $verification_code = rand(111111, 999999);
            $verification_code = 999999;

            $agent = new Agent();
            $browser_name = $agent->browser();
            $browser_version = $agent->version($browser_name);
            $platform = $agent->platform();
            $client_ip = $request->getClientIp();

            // $authentic_ips = ['49.36.144.77', '49.47.69.107'];
            // if (in_array($user->role_id, [3, 4])) {
            //     if (!in_array($client_ip, $authentic_ips)) {
            //         return response()->json(['success' => false, 'alert_type' => 'error', 'message' => 'Invalid request.'], 400);
            //     }
            // }

            $login_info = LoginInfo::where(['login_type' => $request->login_type, 'user_id' => $user->id])->first();

            if ($login_info) {
                $last_request_otp_date = date('Y-m-d', strtotime($login_info->request_otp_at));
                $current_date = date('Y-m-d');
                if ($current_date > $last_request_otp_date) {
                    $login_info->request_otp_count = 1;
                } else {
                    $login_info->request_otp_count = $login_info->request_otp_count + 1;
                }
            } else {
                $login_info = new LoginInfo();
                $login_info->user_id = $user->id;
                $login_info->login_type = $request->login_type;
                $login_info->request_otp_count = 1;
            }

            $login_info->otp_code = $verification_code;
            $login_info->request_otp_at = date('Y-m-d H:i:s');
            $login_info->ip_address = $client_ip;
            $login_info->browser = "$browser_name Ver:$browser_version";
            $login_info->platform = $platform;
            $login_info->status = 0;
            $login_info->save();

            if (env('INTERAKT_STATUS') === true) {
                $this->interakt_wa_msg_send($user->mobile, $user->name, $verification_code);
            }

            if ($user->email != null && env('MAIL_STATUS') === true) {
                $res_data = ['name' => $user->name, 'otp' => $verification_code];
                Mail::to($user->email)->send(new LoginMail($res_data));
            }

            return response()->json(['success' => true, 'alert_type' => 'success', 'message' => 'Verification code has been sent to your registered WhatsApp & Email.'], 200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong. internal server error.', 'error' => $th->getMessage()], 500);
        }
    }

    public function login_process(Request $request)
    {
        $validate = validator::make($request->all(), [
            'verified_phone_number' => "required|digits:10",
            'verified_login_type' => 'required|string|min:4|max:6',
            'verification_code' => "required|integer|digits:6",
        ]);

        if ($validate->fails()) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Invalid credentials, Something went wrong.']);
            return redirect()->back();
        }

        if ($request->verified_login_type === "team") {
            $user = TeamMember::where('mobile', $request->verified_phone_number)->first();
        } else if ($request->verified_login_type === "vendor") {
            $user = Vendor::where('mobile', $request->verified_phone_number)->first();
        }

        if (!$user) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Invalid credentials']);
            return redirect()->back();
        } else if ($user->status == 0) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Profile is inactive, kindly contact to your manager.']);
            return redirect()->back();
        }

        $login_info = LoginInfo::where([
            'login_type' => $request->verified_login_type,
            'user_id' => $user->id,
            'otp_code' => $request->verification_code,
        ])->first();

        if (!$login_info || $login_info == null) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Invalid credentials']);
            return redirect()->back();
        }

        $request_otp_at = date('YmdHis', strtotime($login_info->request_otp_at));
        $ten_minutes_ago = date('YmdHis', strtotime('-10 minutes'));
        if ($request_otp_at < $ten_minutes_ago) {
            if ($login_info !== null) {
                $login_info->otp_code = null;
                $login_info->save();
            }
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Timeout. Please try again.']);
            return redirect()->back();
        }

        $last_login_at = date('Y-m-d', strtotime($login_info->login_at));
        $current_date = date('Y-m-d');
        if ($current_date > $last_login_at) {
            $login_info->login_count = 1;
        } else {
            $login_info->login_count = $login_info->login_count + 1;
        }
        $login_info->otp_code = null;
        $login_info->login_at = date('Y-m-d H:i:s');
        $login_info->status = 1;
        $login_info->token = $request->_token;
        $login_info->logout_at = null;
        $login_info->save();

        if ($request->verified_login_type === "team") {
            if ($user->role_id == 1) {
                Auth::guard('admin')->login($user);
                return redirect()->route('admin.dashboard');
            } else if ($user->role_id == 2) {
                Auth::guard('manager')->login($user);
                return redirect()->route('manager.dashboard');
            } else if ($user->role_id == 3) {
                Auth::guard('nonvenue')->login($user);
                return redirect()->route('nonvenue.dashboard');
            } else {
                Auth::guard('team')->login($user);
                return redirect()->route('team.dashboard');
            }
        } else {
            Auth::guard('vendor')->login($user);
            return redirect()->route('vendor.dashboard');
        }
    }

    public function logout()
    {
        $login_route_name = "login";
        if (Auth::guard('admin')->check()) {
            $guard_authenticated = Auth::guard('admin');
        } else if (Auth::guard('manager')->check()) {
            $guard_authenticated = Auth::guard('manager');
        } else if (Auth::guard('nonvenue')->check()) {
            $guard_authenticated = Auth::guard('nonvenue');
        } else if (Auth::guard('team')->check()) {
            $guard_authenticated = Auth::guard('team');
        } else {
            $guard_authenticated = Auth::guard('vendor');
            $login_route_name = "vendor.login";
        }

        $user = $guard_authenticated->user();
        if (!$user) {
            return redirect()->back();
        }

        if (isset($user->role_id) && $user->role_id !== null) {
            $login_type = "team";
        } else {
            $login_type = "vendor";
        }

        $login_info = LoginInfo::where(['login_type' => $login_type, 'user_id' => $user->id])->first();
        $login_info->logout_at = date('Y-m-d H:i:s');
        $login_info->status = 0;
        $login_info->token = null;
        $login_info->save();

        $guard_authenticated->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route($login_route_name);
    }

    public function team_login_via_admin($team_id)
    {
        try {
            if (Auth::guard('admin')->hasUser()) {
                $member = TeamMember::find($team_id);
                if ($member->role_id == 2) {
                    Auth::guard('manager')->login($member);
                    return redirect()->route('manager.dashboard');
                } else if ($member->role_id == 3) {
                    Auth::guard('nonvenue')->login($member);
                    return redirect()->route('nonvenue.dashboard');
                } else {
                    Auth::guard('team')->login($member);
                    return redirect()->route('team.dashboard');
                }
            } else {
                session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong.']);
                return redirect()->back();
            }
        } catch (\Throwable $th) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong.']);
            return redirect()->back();
        }
    }

    public function vendor_login_via_admin($vendor_id)
    {
        if (Auth::guard('admin')->hasUser()) {
            $member = Vendor::find($vendor_id);
            Auth::guard('vendor')->login($member);
            return redirect()->route('vendor.dashboard');
        } else {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong.']);
            return redirect()->back();
        }
    }

    public function team_login_via_manager($team_id)
    {
        try {
            if (Auth::guard('manager')->hasUser()) {
                $member = TeamMember::find($team_id);
                if ($member->role_id === 5) {
                    Auth::guard('team')->login($member);
                    return redirect()->route('team.dashboard');
                }
            }
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong.']);
            return redirect()->back();
        } catch (\Throwable $th) {
            session()->flash('status', ['success' => false, 'alert_type' => 'error', 'message' => 'Something went wrong.']);
            return redirect()->back();
        }
    }

    public function mail_test($mail_id)
    {
        // $verification_code = rand(111111, 999999);
        // $res_data = ['name' => "Test Name", 'otp' => $verification_code];
        // Mail::to($mail_id)->send(new LoginMail($res_data));

        // $data = [
        //     'lead_name' => 'Test Name',
        //     'event_name' => 'Wedding',
        //     'event_date' => date('d-M-Y'),
        //     'event_slot' => 'Dinner',
        //     'lead_email' => 'test@gmail.com',
        //     'lead_mobile' => '9988776655',
        // ];
        // Mail::mailer('smtp2')->to($mail_id)->send(new NotifyVendorLead($data));

        // return response()->json(['success' => true, 'message' => 'Mail has been send, check your mail box.']);
    }

    // public function notify_vendor_lead($phoneNumber) {
    //     $business_name = "Imagic Studio";
    //     $message = "98XX";
    //     $lead_id = 101;
    //     return $this->notify_vendor_lead_using_interakt($phoneNumber, $business_name, $message, $lead_id);
    // }
}
