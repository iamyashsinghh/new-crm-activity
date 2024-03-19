@extends('nonvenue.layouts.app')
@section('header-css')
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
@endsection
@section('title', $page_heading . ' | Non Venue CRM')
@if (!isset($filter_params['dashboard_filters']))
    @section('navbar-right-links')
        <li class="nav-item">
            <a class="nav-link" title="Filters" data-widget="control-sidebar" data-controlsidebar-slide="true" href="#"
                role="button">
                <i class="fas fa-filter"></i>
            </a>
        </li>
    @endsection
@endif
@section('main')
    @php
        $auth_user = Auth::guard('nonvenue')->user();
    @endphp
    <div class="content-wrapper pb-5">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12 d-flex justify-content-between">
                        <h1 class="m-0">{{ $page_heading }}</h1>
                        <a href="{{ route('nonvenue.lead.list') }}" class="btn btn-secondary btn-sm">Refresh</a>
                        <button class="btn btn-sm text-light" onclick="getSelectedCheckboxValues()"
                            style="background-color: var(--wb-renosand);">Whatsapp</button>
                    </div>
                </div>
                @if (!isset($filter_params['dashboard_filters']))
                    <div class="filter-container text-center" style="display: none;">
                        <form action="{{ route('nonvenue.lead.list') }}" method="post">
                            @csrf
                            <label for="">Filter by lead date</label>
                            <input type="date" name="lead_from_date"
                                value="{{ isset($filter_params['lead_from_date']) ? $filter_params['lead_from_date'] : '' }}"
                                class="form-control form-control-sm d-inline-block" style="width: unset;" required>
                            <span class="">To:</span>
                            <input type="date" name="lead_to_date"
                                value="{{ isset($filter_params['lead_to_date']) ? $filter_params['lead_to_date'] : '' }}"
                                class="form-control form-control-sm d-inline-block" style="width: unset;">
                            <button type="submit" class="btn text-light btn-sm"
                                style="background-color: var(--wb-dark-red)">Submit</button>
                            <a href="{{ route('nonvenue.lead.list') }}" class="btn btn-secondary btn-sm">Reset</a>
                        </form>
                    </div>
                @endif
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <div class="table-responsive" style="overflow-x: visible;">
                    <table id="serverTable" class="table text-sm">
                        <thead class="sticky_head bg-light" style="position: sticky; top: 0;">
                            <tr>
                                <th class="text-nowrap"><input type="checkbox" id="select-all-checkbox"> Lead ID</th>
                                <th class="text-nowrap">Lead Date</th>
                                <th class="">Name</th>
                                <th class="text-nowrap">Mobile</th>
                                <th class="">Lead Status</th>
                                <th class="text-nowrap">Event Date</th>
                                <th class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </section>
        <div class="modal fade" id="leadForwardedMemberInfo" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Forward Information</h4>
                        <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa fa-times"></i></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="table-responsive">
                            <table id="clientTable" class="table text-sm">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap">S.No.</th>
                                        <th class="text-nowrap">Vendor Name</th>
                                        <th class="text-nowrap">Business Name</th>
                                        <th class="text-nowrap">Category</th>
                                        <th class="text-nowrap">Forwarded At</th>
                                    </tr>
                                </thead>
                                <tbody id="forward_info_table_body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <aside class="control-sidebar control-sidebar-dark" style="display: none;">
            <div class="p-3 control-sidebar-content">
                <h5>Lead Filters</h5>
                <hr class="mb-2">
                <form action="{{ route('nonvenue.lead.list') }}" method="post" id="filters-form">
                    @csrf
                    <div class="accordion text-sm" id="accordionExample">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapse1"
                                    aria-expanded="true" aria-controls="collapse1">Lead Status</button>
                            </h2>
                            <div id="collapse1"
                                class="accordion-collapse collapse {{ isset($filter_params['lead_status']) ? 'show' : '' }}"
                                data-bs-parent="#accordionExample">
                                <div class="accordion-body pl-2 pb-4">
                                    <div class="custom-control custom-radio my-1">
                                        <input class="custom-control-input" type="radio" id="lead_status_active_radio"
                                            name="lead_status" value="Active"
                                            {{ isset($filter_params['lead_status']) && $filter_params['lead_status'] == 'Active' ? 'checked' : '' }}>
                                        <label for="lead_status_active_radio" class="custom-control-label">Active</label>
                                    </div>
                                    <div class="custom-control custom-radio my-1">
                                        <input class="custom-control-input" type="radio" id="lead_status_done_radio"
                                            name="lead_status" value="Done"
                                            {{ isset($filter_params['lead_status']) && $filter_params['lead_status'] == 'Done' ? 'checked' : '' }}>
                                        <label for="lead_status_done_radio" class="custom-control-label">Done</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapse2"
                                    aria-expanded="true" aria-controls="collapse2">Lead Read Status</button>
                            </h2>
                            <div id="collapse2"
                                class="accordion-collapse collapse {{ isset($filter_params['lead_read_status']) ? 'show' : '' }}"
                                data-bs-parent="#accordionExample">
                                <div class="accordion-body pl-2 pb-4">
                                    <div class="custom-control custom-radio my-1">
                                        <input class="custom-control-input" type="radio" id="read_status_readed_radio"
                                            name="lead_read_status" value="1"
                                            {{ isset($filter_params['lead_read_status']) && $filter_params['lead_read_status'] == '1' ? 'checked' : '' }}>
                                        <label for="read_status_readed_radio" class="custom-control-label">Readed</label>
                                    </div>
                                    <div class="custom-control custom-radio my-1">
                                        <input class="custom-control-input" type="radio"
                                            id="read_status_unreaded_radio" name="lead_read_status" value="0"
                                            {{ isset($filter_params['lead_read_status']) && $filter_params['lead_read_status'] == '0' ? 'checked' : '' }}>
                                        <label for="read_status_unreaded_radio"
                                            class="custom-control-label">Unreaded</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapse4"
                                    aria-expanded="true" aria-controls="collapse4">Has RM Message?</button>
                            </h2>
                            <div id="collapse4"
                                class="accordion-collapse collapse {{ isset($filter_params['has_rm_message']) ? 'show' : '' }}"
                                data-bs-parent="#accordionExample">
                                <div class="accordion-body pl-2 pb-4">
                                    <div class="custom-control custom-radio my-1">
                                        <input class="custom-control-input" type="radio" id="has_rm_message_no_radio"
                                            name="has_rm_message" value="no"
                                            {{ isset($filter_params['has_rm_message']) && $filter_params['has_rm_message'] == 'no' ? 'checked' : '' }}>
                                        <label for="has_rm_message_no_radio" class="custom-control-label">No</label>
                                    </div>
                                    <div class="custom-control custom-radio my-1">
                                        <input class="custom-control-input" type="radio" id="has_rm_message_yes_radio"
                                            name="has_rm_message" value="yes"
                                            {{ isset($filter_params['has_rm_message']) && $filter_params['has_rm_message'] == 'yes' ? 'checked' : '' }}>
                                        <label for="has_rm_message_yes_radio" class="custom-control-label">Yes</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapse5"
                                    aria-expanded="true" aria-controls="collapse5">Event Date</button>
                            </h2>
                            <div id="collapse5"
                                class="accordion-collapse collapse {{ isset($filter_params['event_from_date']) ? 'show' : '' }}"
                                data-bs-parent="#accordionExample">
                                <div class="accordion-body pl-2 pb-4">
                                    <div class="form-group">
                                        <label for="event_from_date_inp">From</label>
                                        <input type="date" class="form-control" id="event_from_date_inp"
                                            name="event_from_date"
                                            value="{{ isset($filter_params['event_from_date']) ? $filter_params['event_from_date'] : '' }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="event_to_date_inp">To</label>
                                        <input type="date" class="form-control" id="event_to_date_inp"
                                            name="event_to_date"
                                            value="{{ isset($filter_params['event_to_date']) ? $filter_params['event_to_date'] : '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapse6"
                                    aria-expanded="true" aria-controls="collapse6">Lead Date</button>
                            </h2>
                            <div id="collapse6"
                                class="accordion-collapse collapse {{ isset($filter_params['lead_from_date']) ? 'show' : '' }}"
                                data-bs-parent="#accordionExample">
                                <div class="accordion-body pl-2 pb-4">
                                    <div class="form-group">
                                        <label for="lead_from_date_inp">From</label>
                                        <input type="date" class="form-control" id="lead_from_date_inp"
                                            name="lead_from_date"
                                            value="{{ isset($filter_params['lead_from_date']) ? $filter_params['lead_from_date'] : '' }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="lead_to_date_inp">To</label>
                                        <input type="date" class="form-control" id="lead_to_date_inp"
                                            name="lead_to_date"
                                            value="{{ isset($filter_params['lead_to_date']) ? $filter_params['lead_to_date'] : '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapse7"
                                    aria-expanded="true" aria-controls="collapse7">Lead Done Date</button>
                            </h2>
                            <div id="collapse7"
                                class="accordion-collapse collapse {{ isset($filter_params['lead_done_from_date']) ? 'show' : '' }}"
                                data-bs-parent="#accordionExample">
                                <div class="accordion-body pl-2 pb-4">
                                    <div class="form-group">
                                        <label for="lead_done_from_date">From</label>
                                        <input type="date" class="form-control" id="event_date_inp"
                                            name="lead_done_from_date"
                                            value="{{ isset($filter_params['lead_done_from_date']) ? $filter_params['lead_done_from_date'] : '' }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="lead_done_to_date">To</label>
                                        <input type="date" class="form-control" id="event_date_inp"
                                            name="lead_done_to_date"
                                            value="{{ isset($filter_params['lead_done_to_date']) ? $filter_params['lead_done_to_date'] : '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="my-5">
                        <button type="submit" class="btn btn-sm text-light btn-block"
                            style="background-color: var(--wb-renosand);">Apply</button>
                        <a href="{{ route('nonvenue.lead.list') }}" type="submit"
                            class="btn btn-sm btn-secondary btn-block">Reset</a>
                    </div>
                </form>
            </div>
        </aside>
    </div>
@endsection
@section('footer-script')
    @include('whatsapp.multiplemsg');
    @include('whatsapp.chat');
    @php
        $filter = '';
        if (isset($filter_params['dashboard_filters'])) {
            $filter = 'dashboard_filters=' . $filter_params['dashboard_filters'];
        }
        $dashfilters = isset($filter_params['dashboard_filters']) ? $filter_params['dashboard_filters'] : null;
    @endphp
    <script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script>
        function handle_whatsapp_msg(id) {
            const elementToUpdate = document.querySelector(`#what_id-${id}`);

            if (elementToUpdate) {
                elementToUpdate.outerHTML =
                    `<i class="fab fa-whatsapp" onclick="handle_whatsapp_msg(${id})" style="font-size: 25px; color: green;"></i>`;
            }
            const form_title = document.querySelector(`#form_title_modal`);
            form_title.innerHTML = `Whatsapp Messages of ${id}`;
            const manageWhatsappChatModal = new bootstrap.Modal(document.getElementById('wa_msg'));
            wamsg(id);
            manageWhatsappChatModal.show();
            const wa_status_url = `{{ route('whatsapp_chat.status_nv_team') }}`;
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

        const data_url = `{{ route('nonvenue.lead.list.ajax') }}?{!! $filter !!}`;

        var dataTable;
        $(document).ready(function() {
            var dashfilters = @json($dashfilters);
            if(dashfilters){
                console.log(dashfilters)
                $('#serverTable').DataTable({

                pageLength: 10,
                language: {
                    "search": "_INPUT_", // Removes the 'Search' field label
                    "searchPlaceholder": "Type here to search..", // Placeholder for the search box
                    processing: `<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>`, // loader
                },
                serverSide: true,
                loading: true,
                ajax: {
                    url: `{{ route('nonvenue.lead.list.ajax') }}?{!! $filter !!}`,
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    },
                    method: "get",
                    dataSrc: "data",
                },

                columns: [{
                        targets: 0,
                        name: "lead_id",
                        data: "lead_id",
                    },
                    {
                        targets: 1,
                        name: "lead_date",
                        data: "lead_date",
                    },
                    {
                        targets: 2,
                        name: "name",
                        data: "name",
                    },
                    {
                        targets: 3,
                        name: "mobile",
                        data: "mobile",
                    },
                    {
                        targets: 4,
                        name: "lead_status",
                        data: "lead_status",
                    },
                    {
                        targets: 5,
                        name: "event_date",
                        data: "event_date",
                    },
                    {
                        targets: 6,
                        name: "forward_id",
                        data: "forward_id",
                        searchable: false,
                        orderable: false,
                    },

                ],
                order: [
                    [1, 'desc']
                ],
                rowCallback: function(row, data, index) {

                    if (data.read_status == 1) {
                        row.style.background = "#3636361f";
                    }
                    const td_elements = row.querySelectorAll('td');
                    for (let i = 1; i < 4; i++) {
                        if (i != 3) {
                            td_elements[i].style.cursor = "pointer";
                            td_elements[i].setAttribute('onclick', `handle_view_lead(${data.lead_id})`);
                        }
                    }
                    td_elements[0].innerHTML =
                        `${data.lead_id} <input type="checkbox" class="forward_lead_checkbox" value="${data.mobile}">`;
                    td_elements[1].innerText = moment(data.lead_date).format("DD-MMM-YYYY hh:mm a");
                    if (data.is_whatsapp_msg === 1) {
                        td_elements[3].innerHTML =
                            `<div class="d-flex"><div>${data.mobile} </div> &nbsp;&nbsp;&nbsp;<i class="fa-brands fa-square-whatsapp" onclick="handle_whatsapp_msg(${data.mobile})" id="what_id-${data.mobile}" style="font-size: 25px; color: green;"></i></div>`;
                    } else {
                        td_elements[3].innerHTML =
                            `<div class="d-flex"><div>${data.mobile} </div>&nbsp;&nbsp;&nbsp;<i class="fab fa-whatsapp" onclick="handle_whatsapp_msg(${data.mobile})" style="font-size: 25px; color: green;"></i></div>`;
                    }
                    if (data.lead_status == "Done") {
                        td_elements[4].innerHTML = `<span class="badge badge-secondary">Done</span>`;
                    } else {
                        td_elements[4].innerHTML =
                            `<span class="badge badge-success">${data.lead_status}</span>`;
                    }
                    td_elements[5].innerText = data.event_date ? moment(data.event_date).format(
                        "DD-MMM-YYYY") : 'N/A';

                    td_elements[6].innerHTML =
                        `<button onclick="handle_get_forward_info(${data.lead_id})" class="btn p-0 px-2 btn-info d-flex align-items-center" title="Forward info" style="column-gap: 5px;"><i class="fa fa-share-alt" style="font-size: 15px;"></i>${data.forwarded_count}</button>`
                }
            });
            }else{
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
                    url: "{{ route('nonvenue.lead.list.ajax') }}",
                    data: function(d) {
                        var formData = $('#filters-form').serializeArray();
                        formData.forEach(function(item) {
                            d[item.name] = item.value;
                        });
                    },
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    },
                    method: "get",
                    dataSrc: "data",
                },

                columns: [{
                        targets: 0,
                        name: "lead_id",
                        data: "lead_id",
                        searchable: false,
                        orderable: false,
                    },
                    {
                        targets: 1,
                        name: "lead_date",
                        data: "lead_date",
                    },
                    {
                        targets: 2,
                        name: "name",
                        data: "name",
                    },
                    {
                        targets: 3,
                        name: "mobile",
                        data: "mobile",
                    },
                    {
                        targets: 4,
                        name: "lead_status",
                        data: "lead_status",
                    },
                    {
                        targets: 5,
                        name: "event_date",
                        data: "event_date",
                    },
                    {
                        targets: 6,
                        name: "forward_id",
                        data: "forward_id",
                        searchable: false,
                        orderable: false,
                    },

                ],
                order: [
                    [1, 'desc']
                ],
                rowCallback: function(row, data, index) {
                    if (data.read_status == 1) {
                        row.style.background = "#3636361f";
                    }
                    const td_elements = row.querySelectorAll('td');
                    for (let i = 1; i < 4; i++) {
                        if (i != 3) {
                            td_elements[i].style.cursor = "pointer";
                            td_elements[i].setAttribute('onclick', `handle_view_lead(${data.lead_id})`);
                        }
                    }
                    td_elements[0].innerHTML =
                        `${data.lead_id} <input type="checkbox" class="forward_lead_checkbox" value="${data.mobile}">`;
                    td_elements[1].innerText = moment(data.lead_date).format("DD-MMM-YYYY hh:mm a");
                    if (data.is_whatsapp_msg === 1) {
                        td_elements[3].innerHTML =
                            `<div class="d-flex"><div>${data.mobile} </div> &nbsp;&nbsp;&nbsp;<i class="fa-brands fa-square-whatsapp" onclick="handle_whatsapp_msg(${data.mobile})" id="what_id-${data.mobile}" style="font-size: 25px; color: green;"></i></div>`;
                    } else {
                        td_elements[3].innerHTML =
                            `<div class="d-flex"><div>${data.mobile} </div>&nbsp;&nbsp;&nbsp;<i class="fab fa-whatsapp" onclick="handle_whatsapp_msg(${data.mobile})" style="font-size: 25px; color: green;"></i></div>`;
                    }
                    if (data.lead_status == "Done") {
                        td_elements[4].innerHTML = `<span class="badge badge-secondary">Done</span>`;
                    } else {
                        td_elements[4].innerHTML =
                            `<span class="badge badge-success">${data.lead_status}</span>`;
                    }
                    td_elements[5].innerText = data.event_date ? moment(data.event_date).format(
                        "DD-MMM-YYYY") : 'N/A';

                    td_elements[6].innerHTML =
                        `<button onclick="handle_get_forward_info(${data.lead_id})" class="btn p-0 px-2 btn-info d-flex align-items-center" title="Forward info" style="column-gap: 5px;"><i class="fa fa-share-alt" style="font-size: 15px;"></i>${data.forwarded_count}</button>`
                }
            });
            $('#filters-form').on('submit', function(e) {
                e.preventDefault();
                dataTable.ajax.reload()
                document.querySelector('[data-widget="control-sidebar"]').click();
            });
            }
        });


        $('#select-all-checkbox').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('.forward_lead_checkbox').prop('checked', isChecked);
        });
        $('.forward_lead_checkbox').on('change', function() {
            $('#select-all-checkbox').prop('checked', $('.forward_lead_checkbox:checked').length === $(
                '.forward_lead_checkbox').length);
        });

        function getSelectedCheckboxValues() {
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

        function handle_view_lead(lead_id) {
            window.open(`{{ route('nonvenue.lead.view') }}/${lead_id}`);
        }

        function handle_get_forward_info(lead_id) {
            fetch(`{{ route('nonvenue.lead.getForwardInfo') }}/${lead_id}`).then(response => response.json()).then(
                data => {
                    const forward_info_table_body = document.getElementById('forward_info_table_body');
                    const modal = new bootstrap.Modal("#leadForwardedMemberInfo")
                    forward_info_table_body.innerHTML = "";
                    if (data.success == true) {
                        if (data.lead_forwards.length > 0) {
                            let i = 1;
                            for (let item of data.lead_forwards) {
                                let tr = document.createElement('tr');
                                let tds = `<td>${i}</td>
                        <td>${item.name}</td>
                        <td>${item.business_name}</td>
                        <td>${item.category_name}</td>
                        <td>${moment(item.lead_forwarded_at).format("DD-MMM-YYYY hh:mm a")}</td>`;

                                tr.innerHTML = tds;
                                forward_info_table_body.appendChild(tr);
                                i++;
                            }
                        } else {
                            forward_info_table_body.innerHTML = `<tr>
                        <td colspan="5">No data available in table</td>
                    </tr>`
                        }
                        modal.show();
                    } else {
                        toastr[data.alert_type](data.message);
                    }
                })
        }
    </script>
@endsection
