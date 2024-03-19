@extends('admin.layouts.app')
@section('header-css')
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
@endsection
@section('title', $page_heading." | Non Venue CRM")
@section('navbar-right-links')
<li class="nav-item">
    <a class="nav-link" title="Filters" data-widget="control-sidebar" data-controlsidebar-slide="true" href="javascript:void(0);" role="button">
        <i class="fas fa-filter"></i>
    </a>
</li>
@endsection
@section('main')
@php
    $filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
@endphp
<div class="content-wrapper pb-5">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12 d-flex justify-content-between">
                    <h1 class="m-0">{{$page_heading}}</h1>
                    <button class="btn btn-sm text-light" onclick="send_what_msg_multiple()" style="background-color: var(--wb-renosand);">Whatsapp</button>
                </div>
            </div>
            <div class="button-group my-4">
                <a href="javascript:void(0);" class="btn text-light btn-sm buttons-print mx-1" data-bs-toggle="modal" data-bs-target="#manageNvLeadModal" style="background-color: var(--wb-renosand)"><i class="fa fa-plus"></i> New</a>
                <a href="javascript:void(0);" class="btn text-light btn-sm buttons-print mx-1" onclick="handle_forward_leads_to_rm(this)" style="background-color: var(--wb-dark-red)"><i class="fa fa-paper-plane"></i> Forward to NVRM's</a>
                <a href="{{route('admin.nvlead.list')}}" class="btn btn-secondary btn-sm">Refresh</a>
            </div>
            <div class="filter-container text-center" style="display:none;">
                <form action="{{route('admin.nvlead.list')}}" method="post">
                    @csrf
                    <label for="">Filter by lead date</label>
                    <input type="date" name="lead_from_date" value="{{isset($filter_params['lead_from_date']) ? $filter_params['lead_from_date'] : ''}}" class="form-control form-control-sm d-inline-block" style="width: unset;" required>
                    <span class="">To:</span>
                    <input type="date" name="lead_to_date" value="{{isset($filter_params['lead_to_date']) ? $filter_params['lead_to_date'] : ''}}" class="form-control form-control-sm d-inline-block" style="width: unset;">
                    <button type="submit" class="btn text-light btn-sm" style="background-color: var(--wb-dark-red)">Submit</button>
                    <a href="{{route('admin.nvlead.list')}}" class="btn btn-secondary btn-sm">Reset</a>
                </form>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="table-responsive" style="overflow-x: visible;">
                <table id="serverTable" class="table text-sm">
                    <thead class="sticky_head bg-light" style="position: sticky; top: 0;">
                        <tr>
                            <th class=""><input type="checkbox" onchange="handle_select_all_leads(this)" class=""></th>
                            <th class="text-nowrap">Lead ID</th>
                            <th class="text-nowrap">Lead Date</th>
                            <th class="">Name</th>
                            <th class="text-nowrap">Mobile</th>
                            <th class="text-nowrap">Event Date</th>
                            <th class="">Created By</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                </table>

            </div>
        </div>
    </section>

    @include('admin.nonVenueCrm.nvlead.nvlead_forwarded_info_modal')
    @include('admin.nonVenueCrm.nvlead.forward_leads_modal')
    @include('admin.nonVenueCrm.nvlead.manage_lead_modal')

    <aside class="control-sidebar control-sidebar-dark" style="display: none;">
        {{-- filter sidebar for leads page --}}
        <div class="p-3 control-sidebar-content">
            <h5>Lead Filters</h5>
            <hr class="mb-2">
            <form action="{{route('admin.nvlead.list')}}" method="post" id="filters-form">
                @csrf
                <div class="accordion text-sm" id="accordionExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="true" aria-controls="collapse4">Has NVRM Message?</button>
                        </h2>
                        <div id="collapse4" class="accordion-collapse collapse {{isset($filter_params['has_rm_message']) ? 'show' : ''}}" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                <div class="custom-control custom-radio my-1">
                                    <input class="custom-control-input" type="radio" id="has_rm_message_no_radio" name="has_rm_message" value="no" {{isset($filter_params['has_rm_message']) && $filter_params['has_rm_message'] == 'no'  ? 'checked' : ''}}>
                                    <label for="has_rm_message_no_radio" class="custom-control-label">No</label>
                                </div>
                                <div class="custom-control custom-radio my-1">
                                    <input class="custom-control-input" type="radio" id="has_rm_message_yes_radio" name="has_rm_message" value="yes" {{isset($filter_params['has_rm_message']) && $filter_params['has_rm_message'] == 'yes'  ? 'checked' : ''}}>
                                    <label for="has_rm_message_yes_radio" class="custom-control-label">Yes</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" aria-expanded="true" aria-controls="collapse5">Event Date</button>
                        </h2>
                        <div id="collapse5" class="accordion-collapse collapse {{isset($filter_params['event_from_date']) ? 'show' : ''}}" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                <div class="form-group">
                                    <label for="event_from_date_inp">From</label>
                                    <input type="date" class="form-control" id="event_from_date_inp" name="event_from_date" value="{{isset($filter_params['event_from_date']) ? $filter_params['event_from_date'] : ''}}">
                                </div>
                                <div class="form-group">
                                    <label for="event_to_date_inp">To</label>
                                    <input type="date" class="form-control" id="event_to_date_inp" name="event_to_date" value="{{isset($filter_params['event_to_date']) ? $filter_params['event_to_date'] : ''}}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6" aria-expanded="true" aria-controls="collapse6">Lead Date</button>
                        </h2>
                        <div id="collapse6" class="accordion-collapse collapse {{isset($filter_params['lead_from_date']) ? 'show' : ''}}" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                <div class="form-group">
                                    <label for="lead_from_date_inp">From</label>
                                    <input type="date" class="form-control" id="lead_from_date_inp" name="lead_from_date" value="{{isset($filter_params['lead_from_date']) ? $filter_params['lead_from_date'] : ''}}">
                                </div>
                                <div class="form-group">
                                    <label for="lead_to_date_inp">To</label>
                                    <input type="date" class="form-control" id="lead_to_date_inp" name="lead_to_date" value="{{isset($filter_params['lead_to_date']) ? $filter_params['lead_to_date'] : ''}}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="my-5">
                    <button type="submit" class="btn btn-sm text-light btn-block" style="background-color: var(--wb-renosand);">Apply</button>
                    <a href="{{route('admin.nvlead.list')}}" type="submit" class="btn btn-sm btn-secondary btn-block">Reset</a>
                </div>
            </form>
        </div>
    </aside>
</div>
@endsection
@section('footer-script')
@include("whatsapp.admin_multiplemsg_nv");
@include('whatsapp.chat');
@php
$filter = "";
if (isset($filter_params['lead_status'])) {
    $filter = "lead_status=" . $filter_params['lead_status'];
} elseif (isset($filter_params['lead_read_status'])) {
    $filter = "lead_read_status=" . $filter_params['lead_read_status'];
} elseif (isset($filter_params['service_status'])) {
    $filter = "service_status=" . $filter_params['service_status'];
} elseif (isset($filter_params['has_rm_message'])) {
    $filter = "has_rm_message=" . $filter_params['has_rm_message'];
} elseif (isset($filter_params['event_from_date'])) {
    $filter = "event_from_date=" . $filter_params['event_from_date'] . "&event_to_date=" . $filter_params['event_to_date'];
} elseif (isset($filter_params['lead_from_date'])) {
    $filter = "lead_from_date=" . $filter_params['lead_from_date'] . "&lead_to_date=" . $filter_params['lead_to_date'];
} elseif (isset($filter_params['lead_done_from_date'])) {
    $filter = "lead_done_from_date=" . $filter_params['lead_done_from_date'] . "&lead_done_to_date=" . $filter_params['lead_done_to_date'];
}
@endphp
<script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script src="{{asset('plugins/moment/moment.min.js')}}"></script>
<script>
    function send_what_msg_multiple(){
        const manageWhatsappChatModal = new bootstrap.Modal(document.getElementById('wa_msg_multiple'));
            var selectedValues = [];
            $('.forward_lead_checkbox:checked').each(function() {
                selectedValues.push($(this).val());
            });
            console.log(selectedValues);
            let phonenum = document.getElementById('phone_inp_id_m');
            phonenum.value = selectedValues;
            if(selectedValues.length > 0){
                manageWhatsappChatModal.show();
            }else{
                toastr.info("Select the lead's which you want to send messages.");
            }
    }

    function handle_whatsapp_msg(id) {
            const elementToUpdate = document.querySelector(`#what_id-${id}`);
            if (elementToUpdate) {
        elementToUpdate.outerHTML = `<i class="fab fa-whatsapp" onclick="handle_whatsapp_msg(${id})" style="font-size: 25px; color: green;"></i>`;
    }
    const form_title = document.querySelector(`#form_title_modal`);
    form_title.innerHTML = `Whatsapp Messages of ${id}`;
            const manageWhatsappChatModal = new bootstrap.Modal(document.getElementById('wa_msg'));
            wamsg(id);
            manageWhatsappChatModal.show();
            const wa_status_url = `{{ route('whatsapp_chat.status_nv') }}`;
            const wa_status_data = {
                mobile: id
            };
            fetch(wa_status_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(wa_status_data),
                })
                .then(response => response.json())
                .then(data => {})
                .catch((error) => {});
        }
    const data_url = `{{route('admin.nvlead.list.ajax')}}?{!!$filter!!}`;
    var dataTable;
    $(document).ready(function() {

        dataTable = $('#serverTable').DataTable({
            pageLength: 10,
            language: {
                "search": "_INPUT_", // Removes the 'Search' field label
                "searchPlaceholder": "Type here to search..", // Placeholder for the search box
                processing: `<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>`, // loader
            },
            serverSide: true,
            loading: true,
            ajax: {
                url: "{{ route('admin.nvlead.list.ajax') }}",
                    data: function(d) {
                        var formData = $('#filters-form').serializeArray();
                        formData.forEach(function(item) {
                            d[item.name] = item.value;
                        });
                    },
                headers: {
                    'X-CSRF-TOKEN': "{{csrf_token()}}",
                },
                method: "get",
                dataSrc: "data",
            },

            columns: [{
                    targets: 0,
                    name: "id",
                    data: "id",
                    orderable: false,
                    searchable: false,
                },
                {
                    targets: 1,
                    name: "id",
                    data: "id",
                },
                {
                    targets: 2,
                    name: "lead_datetime",
                    data: "lead_datetime",
                },
                {
                    targets: 3,
                    name: "name",
                    data: "name",
                },
                {
                    targets: 4,
                    name: "mobile",
                    data: "mobile",
                },
                {
                    targets: 5,
                    name: "event_datetime",
                    data: "event_datetime",
                },
                {
                    targets: 5,
                    name: "id",
                    data: "id",
                },
                {
                    targets: 6,
                    name: "id",
                    data: "id",
                    orderable: false,
                    searchable: false,
                },
            ],
            rowCallback: function(row, data, index) {
                // if(data.forwarded_info_count == 0){
                //     row.style.backgroundColor = "#fffff"; // white
                // }else if(data.forwarded_count > 0){
                //     row.style.backgroundColor = "#ff00001f"; //light red
                // }else if(data.team_name != null){
                //     row.style.backgroundColor = "#4bff0033"; //light green
                // }else{
                //     row.style.backgroundColor = "#0066ff33"; //light blue
                // }
                const td_elements = row.querySelectorAll('td');
                td_elements[0].innerHTML = `<input type="checkbox" onchange="handle_select_single_lead(this)" class="forward_lead_checkbox" value="${data.id}">`;
                td_elements[2].innerText = moment(data.lead_datetime).format("DD-MMM-YYYY hh:mm a");
                td_elements[3].innerText = data.name ? data.name : 'N/A';
                if (data.is_whatsapp_msg === 1) {
                        td_elements[4].innerHTML =
                            `<div class="d-flex"><div>${data.mobile} </div> &nbsp;&nbsp;&nbsp;<i class="fa-brands fa-square-whatsapp" onclick="handle_whatsapp_msg(${data.mobile})" id="what_id-${data.mobile}" style="font-size: 25px; color: green;"></i></div>`;
                    } else {
                        td_elements[4].innerHTML =
                            `<div class="d-flex"><div>${data.mobile} </div>&nbsp;&nbsp;&nbsp;<i class="fab fa-whatsapp" onclick="handle_whatsapp_msg(${data.mobile})" style="font-size: 25px; color: green;"></i></div>`;
                    }
                td_elements[5].innerText = data.event_datetime ? moment(data.event_datetime).format("DD-MMM-YYYY") : 'N/A';
                td_elements[6].innerText = data.team_name ? data.team_name+" - "+data.team_role : 'N/A';

                let forwarded_count = data.nvrm_forwarded_count+data.nv_forwarded_count;

                const action_btns = `<a href="{{route('admin.nvlead.view')}}/${data.id}" target="_blank" class="text-dark mx-2" title="View"><i class="fa fa-eye" style="font-size: 15px;"></i></a>
                <a href="{{route('admin.nvlead.delete')}}/${data.id}" onclick="return confirm('Are you sure want to delete?')" class="text-danger mx-2" title="Delete"><i class="fa fa-trash-alt" style="font-size: 15px;"></i></a>
                <button onclick="handle_get_nvlead_forwarded_info(${data.id})" class="btn mx-2 p-0 px-2 btn-info" title="Forward info"><i class="fa fa-share-alt" style="font-size: 15px;"></i> ${forwarded_count}</button>`

                td_elements[7].classList.add('text-nowrap');
                td_elements[7].innerHTML = action_btns;
            }
        });
    $('#filters-form').on('submit', function(e) {
                e.preventDefault();
                dataTable.ajax.reload();
                document.querySelector('[data-widget="control-sidebar"]').click();
            });
    });


    let for_forward_leads_id = [];
    function handle_select_all_leads(elem){
        const forward_lead_checkbox = document.querySelectorAll('.forward_lead_checkbox');
        if(elem.checked){
            for(let item of forward_lead_checkbox){
                for_forward_leads_id.push(item.value);
                item.checked = true;
            }
        }else{
            for(let item of forward_lead_checkbox){
                item.checked = false;
            }
            for_forward_leads_id = [];
        }
    }

    function handle_select_single_lead(elem){
        if(elem.checked){
            for_forward_leads_id.push(elem.value);
        }else{
            for_forward_leads_id.splice(for_forward_leads_id.indexOf(elem.value), 1);
        }
    }

    function handle_forward_leads_to_rm(elem){
        if(for_forward_leads_id.length > 0){
            document.querySelector('input[name="forward_leads_id"]').value = for_forward_leads_id;
            const forward_rms_chekbox = document.querySelectorAll(`input[name="forward_rms_id[]"]`);
            const modal = new bootstrap.Modal("#forwardLeadModal");

            document.getElementById('select_all_rms').checked = false;
            for(let item of forward_rms_chekbox){
                item.checked = false;
            }
            modal.show();
        }else{
            toastr.info("Select the lead's which you want to forward.");
        }
    }
</script>
@endsection

{{-- Hello worldfsdffd fsdfgfg --}}

{{-- rm message background color: #fdfd7b5c --}}
