@extends('vendor.layouts.app')
@php
    $page_title = $lead_forward->name ?: 'N/A';
    $page_title .= " | $lead_forward->mobile | View Lead | Venue CRM";
@endphp
@section('title', $page_title)
@section('header-css')
<link rel="stylesheet" href="{{asset('plugins/select2/css/select2.min.css')}}">
@endsection
@section('main')
@php
    $current_date = date('Y-m-d');
    $auth_user = Auth::guard('team')->user();
@endphp
<div class="content-wrapper pb-5">
    <section class="content-header">
        <div class="container-fluid">
            <h1>View Lead</h1>
        </div>
    </section>
    <section class="content">
        <div id="view_lead_card_container" class="card text-sm">
            <div class="card-header">
                <button class="btn btn-xs text-light px-2 m-1" style="background-color: var(--wb-dark-red)" data-bs-toggle="modal" data-bs-target="#manageTaskModal"><i class="fa fa-plus"></i> Add Task</button>
                <button class="btn btn-xs text-light px-2 m-1" style="background-color: var(--wb-renosand)" data-bs-toggle="modal" data-bs-target="#manageMeetingModal"><i class="fa fa-plus"></i> Add Meeting</button>
                <button class="btn btn-xs text-light px-2 m-1" style="background-color: var(--wb-dark-red)" onclick="handle_note_information(`{{route('vendor.note.manage.process')}}`)"><i class="fa fa-plus"></i> Add Note</button>
                <div class="dropdown d-inline-block">
                    <a href="javascript:void(0);" class="btn dropdown-toggle text-light btn-xs px-2 mx-1 {{$lead_forward->lead_status == 'Done' ? 'bg-secondary' : ''}}" data-bs-toggle="dropdown" style="background-color: var(--wb-renosand);"><i class="fa fa-chart-line"></i> Lead: Active</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" onclick="return confirm('Are you sure want to active this lead?')" href="{{route('vendor.lead.status.update', $lead_forward->id)}}/Active">Active</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#manageLeadStatusModal">Done</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="container-fluid">
                    <div class="card mb-5">
                        <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                            <h3 class="card-title">RM Message's</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="serverTable" class="table mb-0" style="background-color: #fdfd7b5c">
                                    <thead>
                                        <tr>
                                            <th class="text-nowrap">S.No.</th>
                                            <th class="text-nowrap">Created At</th>
                                            <th class="">RM Name</th>
                                            <th class="text-nowrap">Title</th>
                                            <th class="">Message</th>
                                            <th class="">Tentative Budget</th>
                                        </tr>
                                    </thead>

                                    <body>
                                        @if (sizeof($lead_forward->get_rm_messages) > 0)
                                        @foreach ($lead_forward->get_rm_messages as $key => $list)
                                        <tr>
                                            <td>{{$key+1}}</td>
                                            <td>{{date('d-M-Y h:i a', strtotime($list->created_at))}}</td>
                                            <td>{{$list->get_created_by->name ?? ''}}</td>
                                            <td>{{$list->title}}</td>
                                            <td>
                                                <button class="btn" onclick="handle_view_message(`{{$list->message ?: 'N/A'}}`)"><i class="fa fa-comment-dots" style="color: var(--wb-renosand);"></i></button>
                                            </td>
                                            <td>{{$list->budget ? "₹ ". number_format($list->budget) : 'N/A'}}</td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td class="text-center text-muted" colspan="5">No data available in table</td>
                                        </tr>
                                        @endif
                                    </body>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-5">
                        <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                            <h3 class="card-title">Lead Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">Lead Date: </span>
                                    <span class="mx-1">{{date('d-M-Y h:i a', strtotime($lead_forward->lead_datetime))}}</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">Lead ID: </span>
                                    <span class="mx-1">{{$lead_forward->lead_id}}</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">Name: </span>
                                    <span class="mx-1">{{$lead_forward->name ?: 'N/A'}}</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">Mobile No.: </span>
                                    <span class="mx-1">{{$lead_forward->mobile}}</span>
                                    <div class="phone_action_btns" style="position: absolute; top: -8px; left: 11rem;">
                                        <a target="_blank" href="https://wa.me/{{$lead_forward->mobile}}" class="text-success text-bold mx-1" style="font-size: 20px;"><i class="fab fa-whatsapp"></i></a>
                                        <a href="tel:{{$lead_forward->mobile}}" class="text-primary text-bold mx-1" style="font-size: 20px;"><i class="fa fa-phone-alt"></i></a>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">Email: </span>
                                    <span class="mx-1">{{$lead_forward->email ?: 'N/A'}}</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">Alternate Mobile No.: </span>
                                    <span class="mx-1">{{$lead_forward->alternate_mobile ?: "N/A"}}</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">Address: </span>
                                    <span class="mx-1">{{$lead_forward->address ?: 'N/A'}}</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">Lead Status: </span>
                                    <span class="mx-1 badge badge-{{$lead_forward->lead_status == 'Done' ? 'secondary' : 'success'}}">{{$lead_forward->lead_status}}</span>
                                </div>
                                <div class="col-sm-12">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">Done Title: </span>
                                    <span class="mx-1">{{$lead_forward->done_title ?: 'N/A'}}</span>
                                </div>

                                <div class="col-sm-12">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">Done Message: </span>
                                    <span class="mx-1">{{$lead_forward->done_message ?: 'N/A'}}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-5">
                        <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                            <h3 class="card-title">Events</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="serverTable" class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-nowrap">S.No.</th>
                                            <th class="">Event Name</th>
                                            <th class="text-nowrap">Event Date</th>
                                            <th class="text-nowrap">Pax</th>
                                            <th class="">Slot</th>
                                            <th class="">Venue Name</th>
                                        </tr>
                                    </thead>

                                    <body>
                                        @if (sizeof($lead_forward->get_events) > 0)
                                        @foreach ($lead_forward->get_events as $key => $list)
                                        <tr>
                                            <td>{{$key+1}}</td>
                                            <td>{{$list->event_name ?: 'N/A'}}</td>
                                            <td class="text-nowrap">{{date('d-M-Y', strtotime($list->event_datetime))}}</td>
                                            <td>{{$list->pax ?: 'N/A'}}</td>
                                            <td>{{$list->event_slot ?: 'N/A'}}</td>
                                            <td>{{$list->venue_name ?: 'N/A'}}</td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td class="text-center text-muted" colspan="6">No data available in table</td>
                                        </tr>
                                        @endif
                                    </body>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div id="task_card_container" class="card mb-5">
                        <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                            <h3 class="card-title">Task Details</h3>
                            <button data-bs-toggle="modal" data-bs-target="#manageTaskModal" class="btn p-0 text-light float-right" title="Add Task."><i class="fa fa-plus"></i></button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="serverTable" class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-nowrap">S.No.</th>
                                            <th class="text-nowrap">Task Schedule Date</th>
                                            <th class="text-nowrap">Follow Up</th>
                                            <th class="">Message</th>
                                            <th class="text-nowrap">Status</th>
                                            <th class="text-nowrap">Done With</th>
                                            <th class="text-nowrap">Done Message</th>
                                            <th class="text-nowrap">Done Date</th>
                                            <th class="text-nowrap">Action</th>
                                        </tr>
                                    </thead>
                                    <body>
                                        @if (sizeof($lead_forward->get_tasks()) > 0)
                                        @foreach ($lead_forward->get_tasks() as $key => $list)
                                        <tr>
                                            <td>{{$key+1}}</td>
                                            <td class="">{{date('d-M-Y h:i a', strtotime($list->task_schedule_datetime))}}</td>
                                            <td>{{$list->follow_up}}</td>
                                            <td>
                                                <button class="btn" onclick="handle_view_message(`{{$list->message ?: 'N/A'}}`)"><i class="fa fa-comment-dots" style="color: var(--wb-renosand);"></i></button>
                                            </td>
                                            <td>
                                                @php
                                                $schedule_date = date('Y-m-d', strtotime($list->task_schedule_datetime));
                                                if ($list->done_datetime !== null) {
                                                    $elem_class = "success";
                                                    $elem_text = "Done";
                                                } elseif ($schedule_date > $current_date) {
                                                    $elem_class = "info";
                                                    $elem_text = "Upcoming";
                                                } elseif ($schedule_date == $current_date) {
                                                    $elem_class = "warning";
                                                    $elem_text = "Today";
                                                } elseif ($schedule_date < $current_date) {
                                                    $elem_class = "danger";
                                                    $elem_text = "Overdue";
                                                }
                                                @endphp
                                                @if ($list->done_datetime !== null)
                                                    <span class="badge badge-{{$elem_class}}">{{$elem_text}}</span>
                                                @else
                                                    <button class="btn btn-{{$elem_class}} dropdown-toggle btn-xs" data-bs-toggle="dropdown" style="font-size: 75% !important;">{{$elem_text}}</button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:void(0);" onclick="handle_task_status_update({{$list->id}})">Done</a>
                                                        </li>
                                                    </ul>
                                                @endif
                                            </td>
                                            <td>{{$list->done_with ?: 'N/A'}}</td>
                                            <td>
                                                <button class="btn" onclick="handle_view_message(`{{$list->done_message ?: 'N/A'}}`)"><i class="fa fa-comment-dots" style="color: var(--wb-renosand);"></i></button>
                                            </td>
                                            <td class="">{{$list->done_datetime ? date('d-M-Y h:i a', strtotime($list->done_datetime)) : 'N/A'}}</td>
                                            <td class="text-nowrap">
                                                @if ($list->done_datetime == null)
                                                    <a href="{{route('vendor.task.delete', $list->id)}}" onclick="return confirm('Are you sure want to delete the task?')" class="text-danger mx-2"><i class="fa fa-trash-alt"></i></a>
                                                @else
                                                    <button class="btn p-0 text-secondary mx-2" disabled><i class="fa fa-trash-alt" title="Done task cannot be delete."></i></button>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td class="text-center text-muted" colspan="9">No data available in table</td>
                                        </tr>
                                        @endif
                                    </body>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-5">
                        <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                            <h3 class="card-title">Meeting Details</h3>
                            <button data-bs-toggle="modal" data-bs-target="#manageMeetingModal" class="btn p-0 text-light float-right" title="Add Meeting."><i class="fa fa-plus"></i></button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="serverTable" class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="">S.No.</th>
                                            <th class="">Meeting Schedule Date</th>
                                            <th class="">Message</th>
                                            <th class="text-nowrap">Status</th>
                                            <th class="">Event Name</th>
                                            <th class="">Event Date</th>
                                            <th class="">Done Message</th>
                                            <th class="text-nowrap text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <body>
                                        @if (sizeof($lead_forward->get_meetings()) > 0)
                                        @foreach ($lead_forward->get_meetings() as $key => $list)
                                        <tr>
                                            <td>{{$key+1}}</td>
                                            <td class="text-nowrap">{{date('d-M-Y H:i a', strtotime($list->meeting_schedule_datetime))}}</td>
                                            <td>
                                                <button class="btn" onclick="handle_view_message(`{{$list->message ?: 'N/A'}}`)"><i class="fa fa-comment-dots" style="color: var(--wb-renosand);"></i></button>
                                            </td>
                                            <td>
                                                @php
                                                $schedule_date = date('Y-m-d', strtotime($list->meeting_schedule_datetime));
                                                if ($list->done_datetime !== null) {
                                                    $elem_class = "success";
                                                    $elem_text = "Done";
                                                } elseif ($schedule_date > $current_date) {
                                                    $elem_class = "info";
                                                    $elem_text = "Upcoming";
                                                } elseif ($schedule_date == $current_date) {
                                                    $elem_class = "warning";
                                                    $elem_text = "Today";
                                                } elseif ($schedule_date < $current_date) {
                                                    $elem_class = "danger";
                                                    $elem_text = "Overdue";
                                                }
                                                @endphp
                                                @if ($list->done_datetime == null)
                                                    <button class="btn btn-{{$elem_class}} dropdown-toggle btn-xs" data-bs-toggle="dropdown" style="font-size: 75% !important;">{{$elem_text}}</button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:void(0);" onclick="handle_meeting_status_update({{$list->id}})">Done</a>
                                                        </li>
                                                    </ul>
                                                @else
                                                    <span class="badge badge-{{$elem_class}}">{{$elem_text}}</span>
                                                @endif
                                            </td>
                                            <td>{{$list->event_name}}</td>
                                            <td>{{$list->event_datetime ? date('d-M-Y', strtotime($list->event_datetime)) : "N/A"}}</td>
                                            <td>
                                                <button class="btn" onclick="handle_view_message(`{{$list->done_message ?: 'N/A'}}`)"><i class="fa fa-comment-dots" style="color: var(--wb-renosand);"></i></button>
                                            </td>
                                            <td class="text-nowrap text-center">
                                                @if ($list->done_datetime == null)
                                                    <a href="{{route('vendor.meeting.delete', $list->id)}}" onclick="return confirm('Are you sure want to delete the meeting?')" class="text-danger mx-2"><i class="fa fa-trash-alt"></i></a>
                                                @else
                                                    <button class="btn p-0 text-secondary mx-2" disabled><i class="fa fa-trash-alt" title="Done Meeting cannot be delete."></i></button>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td class="text-center text-muted" colspan="8">No data available in table</td>
                                        </tr>
                                        @endif
                                    </body>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-5">
                        <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                            <h3 class="card-title">RM Help Support</h3>
                            <button onclick="handle_note_information(`{{route('vendor.note.manage.process')}}`)" class="btn p-0 text-light float-right" title="Add Note."><i class="fa fa-plus"></i></button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="serverTable" class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-nowrap">S.No.</th>
                                            <th class="">Message</th>
                                            <th class="">Created At</th>
                                            <th class="">Action</th>
                                        </tr>
                                    </thead>

                                    <body>
                                        @if (sizeof($lead_forward->get_notes()) > 0)
                                        @foreach ($lead_forward->get_notes() as $key => $list)
                                        <tr>
                                            <td>{{$key+1}}</td>
                                            <td>
                                                <button class="btn" onclick="handle_view_message(`{{$list->message ?: 'N/A'}}`)"><i class="fa fa-comment-dots" style="color: var(--wb-renosand);"></i></button>
                                            </td>
                                            <td class="text-nowrap">{{date('d-M-Y h:i a', strtotime($list->created_at))}}</td>
                                            <td>
                                                <button onclick="handle_note_information(`{{route('vendor.note.manage.process', $list->id)}}`, `{{route('vendor.note.edit', $list->id)}}`)" class="btn p-0 text-success mx-2"><i class="fa fa-edit"></i></button>
                                                <a href="{{route('vendor.note.delete', $list->id)}}" onclick="return confirm('Are you sure want to delete the note?')" class="text-danger mx-2"><i class="fa fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td class="text-center text-muted" colspan="5">No data available in table</td>
                                        </tr>
                                        @endif
                                    </body>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="manageLeadStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Lead Done</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
                </div>
                <form action="{{route('vendor.lead.status.update', $lead_forward->id)}}" method="post">
                    <div class="modal-body text-sm">
                        @csrf
                        <div class="form-group">
                            <input type="hidden" name="forward_id" value="{{$lead_forward->id}}">
                            <label for="done_title_select">Done Title <span class="text-danger">*</span></label>
                            <select class="form-control" id="done_title_select" name="done_title" required>
                                <option value="Budget low.">Budget low. </option>
                                <option value=" very small function ."> very small function .</option>
                                <option value="customer didn't like the Sample/Demo">customer didn't like the Sample/Demo.</option>
                                <option value="Different locality: Customer is not looking in this locality or part of the town. Some other area of the city.">Different locality: Customer is not looking in this locality or part of the town. Some other area of the city.</option>
                                <option value="Looking for more premium services">Looking for more premium services.</option>
                                <option value="Not picking calls: Cannot say a reason as the customer is not picking calls">Not picking calls: Cannot say a reason as the customer is not picking calls</option>
                                <option value="Already Booked: Already booked">Already Booked: Already booked</option>
                                <option value="No requirment : customer not looking ">No requirment : customer not looking for </option>
                                <option value="Not Meet the customer expectations">Not Meet the customer expectations</option>
                                <option value="Others: Others">Others</option>
                                <option value="Lead successfully done.">Lead successfully done.</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="done_message_textarea">Done Message</label>
                            <textarea type="text" class="form-control" id="done_message_textarea" placeholder="Type message" name="done_message"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm text-light" style="background-color: var(--wb-dark-red);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="manageNoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Note</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
                </div>
                <form id="manage_note_form" method="post">
                    <div class="modal-body text-sm">
                        @csrf
                        <div class="form-group">
                            <input type="hidden" name="lead_id" value="{{$lead_forward->lead_id}}">
                            <label for="note_message_textarea">Message</label>
                            <textarea type="text" class="form-control" id="note_message_textarea" placeholder="Type message" name="note_message" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm text-light" style="background-color: var(--wb-dark-red);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manageTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Task</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
                </div>
                <form action="{{route('vendor.task.add.process')}}" method="post">
                    <div class="modal-body text-sm">
                        @csrf
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <div class="form-group">
                                    <input type="hidden" name="lead_id" value="{{$lead_forward->lead_id}}">
                                    <label for="task_schedule_datetime_inp">Task Schedule Date Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" id="task_schedule_datetime_inp" min="{{date('Y-m-d H:i')}}" class="form-control" name="task_schedule_datetime" required>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="form-group">
                                    <label for="task_follow_up_select">Task Follow Up</label>
                                    <select class="form-control" id="task_follow_up_select" name="task_follow_up">
                                        <option value="Call">Call</option>
                                        <option value="SMS">SMS</option>
                                        <option value="Mail">Mail</option>
                                        <option value="WhatsApp">WhatsApp</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12 mb-3">
                                <div class="form-group">
                                    <label for="task_message_textarea">Message</label>
                                    <textarea type="text" class="form-control" id="task_message_textarea" placeholder="Enter task message." name="task_message"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer text-sm">
                        <div class="col">
                            <p>
                                <span class="text-danger">*</span>
                                Fields are required.
                            </p>
                        </div>
                        <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm text-light" style="background-color: var(--wb-dark-red);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="manageTaskStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Update Task Status</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
                </div>
                <form id="task_status_update_form" method="post">
                    <div class="modal-body text-sm">
                        <div class="form-group mb-3">
                            @csrf
                            <label for="task_done_with_select">Task Done With <span class="text-danger">*</span></label>
                            <select class="form-control" id="task_done_with_select" name="task_done_with" required>
                                <option value="Call">Call</option>
                                <option value="SMS">SMS</option>
                                <option value="Mail">Mail</option>
                                <option value="WhatsApp">WhatsApp</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="task_done_message_textarea">Done Message</label>
                            <textarea type="text" class="form-control" id="task_done_message_textarea" placeholder="Enter done message." name="task_done_message"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer text-sm">
                        <div class="col">
                            <p>
                                <span class="text-danger">*</span>
                                Fields are required.
                            </p>
                        </div>
                        <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm text-light" style="background-color: var(--wb-dark-red);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="manageMeetingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Meeting</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
                </div>
                <form action="{{route('vendor.meeting.add.process')}}" method="post">
                    <div class="modal-body text-sm">
                        @csrf
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <div class="form-group">
                                    <input type="hidden" name="lead_id" value="{{$lead_forward->lead_id}}">
                                    <label for="meeting_schedule_datetime_inp">Meeting Schedule Date Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" id="meeting_schedule_datetime_inp" min="{{date('Y-m-d H:i')}}" class="form-control" name="meeting_schedule_datetime" required>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="form-group">
                                    <label for="meeting_event_name_select">Event Name<span class="text-danger">*</span></label>
                                    <select class="form-control" id="meeting_event_name_select" name="meeting_event_name" required>
                                        <option value="" disabled selected>Select Event Name</option>
                                        @foreach ($lead_forward->get_events as $item)
                                            <option value="{{$item['event_name']}}">{{$item['event_name']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12 mb-3">
                                <div class="form-group">
                                    <label for="meeting_message_textarea">Message</label>
                                    <textarea type="text" class="form-control" id="meeting_message_textarea" placeholder="Enter Meeting message." name="meeting_message"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer text-sm">
                        <div class="col">
                            <p>
                                <span class="text-danger">*</span>
                                Fields are required.
                            </p>
                        </div>
                        <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm text-light" style="background-color: var(--wb-dark-red);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="manageMeetingStatusModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Update Meeting Status</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
                </div>
                <form id="meeting_status_update_form" method="post">
                    <div class="modal-body text-sm">
                        @csrf
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <div class="form-group">
                                    <label for="event_date_inp_for_meeting_done">Event Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="event_date_inp_for_meeting_done" name="event_date" required>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="form-group">
                                    <label for="price_quoted_inp">Price Quoted <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="price_quoted_inp" name="price_quoted" required>
                                </div>
                            </div>
                            <div class="col-sm-12 mb-3">
                                <div class="form-group">
                                    <label for="done_message_textarea">Done Message</label>
                                    <textarea type="text" class="form-control" id="done_message_textarea" placeholder="Enter done message" name="done_message"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer text-sm">
                        <div class="col">
                            <p>
                                <span class="text-danger">*</span>
                                Fields are required.
                            </p>
                        </div>
                        <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm text-light" style="background-color: var(--wb-dark-red);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('footer-script')
<script src="{{asset('plugins/select2/js/select2.min.js')}}"></script>
<script>
    $(document).ready(function() {
        if("{{$lead_forward->lead_status}}" == "Done"){
            const container = document.getElementById('view_lead_card_container');
            const btns = container.querySelectorAll('button');
            for(let item of btns){
                item.disabled = true;
                item.removeAttribute('data-bs-toggle');
            }
        }
    })

    function handle_event_information(url_for_submit, url_for_fetch = null) {
        const manageEventModal = document.getElementById('manageEventModal');
        const modalHeading = manageEventModal.querySelector('.modal-title')

        manage_event_form.action = url_for_submit;
        const modal = new bootstrap.Modal(manageEventModal);
        if (url_for_fetch === null) {
            modalHeading.innerText = "Create Event";
            const inps = manageEventModal.querySelectorAll("input:not([type='hidden'])");
            for (let inp of inps) {
                inp.value = null;
            }
            modal.show();
        } else {
            fetch(url_for_fetch).then(response => response.json()).then(data => {
                if (data.success == true) {
                    modalHeading.innerText = "Edit Event";
                    manageEventModal.querySelector('#event_name_inp').value = data.event.event_name;
                    manageEventModal.querySelector('#event_date_inp').value = data.event.event_date;
                    manageEventModal.querySelector('#number_of_guest_inp').value = data.event.pax;
                    manageEventModal.querySelector('#budget_inp').value = data.event.budget;

                    manageEventModal.querySelector(`option[value="${data.event.event_slot}"]`).selected = true;
                    manageEventModal.querySelector(`option[value="${data.event.food_preference}"]`).selected = true;
                    modal.show();
                } else {
                    toastr[data.alert_type](data.message)
                }
            })
        }
    }

    function handle_note_information(url_for_submit, url_for_fetch = null) {
        const manageNoteModal = document.getElementById('manageNoteModal');
        const modalHeading = manageNoteModal.querySelector('.modal-title')

        manage_note_form.action = url_for_submit;
        const modal = new bootstrap.Modal(manageNoteModal);
        if (url_for_fetch === null) {
            modalHeading.innerText = "Add Note";
            const inps = manageNoteModal.querySelectorAll("input:not([type='hidden'])");
            for (let inp of inps) {
                inp.value = null;
            }
            modal.show();
        } else {
            fetch(url_for_fetch).then(response => response.json()).then(data => {
                if (data.success == true) {
                    modalHeading.innerText = "Edit Note";
                    manageNoteModal.querySelector('#note_message_textarea').value = data.note.message;
                    modal.show();
                } else {
                    toastr[data.alert_type](data.message);
                }
            })
        }
    }

    function handle_task_status_update(task_id) {
        const url = `{{route('vendor.task.status.update')}}/${task_id}`;
        const modal = new bootstrap.Modal('#manageTaskStatusModal');
        task_status_update_form.action = url;
        modal.show();
    }

    function handle_meeting_status_update(meeting_id) {
        const url = `{{route('vendor.meeting.status.update')}}/${meeting_id}`;
        const modal = new bootstrap.Modal('#manageMeetingStatusModal');
        meeting_status_update_form.action = url;
        modal.show();
    }
</script>
@endsection
