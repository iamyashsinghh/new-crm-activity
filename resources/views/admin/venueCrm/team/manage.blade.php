@extends('admin.layouts.app')
@section('title', $page_heading . ' | Venue CRM')
@section('main')
    <div class="content-wrapper pb-5">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">{{ $page_heading }}</h1>
                    </div>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <div class="card text-sm">
                    <div class="card-header text-light" style="background-color: var(--wb-renosand)">
                        <h3 class="card-title">Member Details</h3>
                    </div>
                    <form action="{{ route('admin.team.manage.process', $member->id) }}" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="name_inp">Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name_inp" placeholder="Enter name"
                                            name="name" required value="{{ $member->name }}">
                                        @error('name')
                                            <span class="position-absolute ml-1 text-sm text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label>Profile Image</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="customFile"
                                                name="profile_image">
                                            <label class="custom-file-label" for="customFile">Choose file</label>
                                        </div>
                                        <span class="position-absolute ml-1 text-xs" style="left: 8px; bottom: -8px;">File
                                            must be an image and less than 200KB.</span>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="email_inp">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email_inp" placeholder="Enter email"
                                            name="email" required value="{{ $member->email }}">
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="mobile_inp">Mobile No. <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="mobile_inp"
                                            placeholder="Enter mobile no." name="mobile_number" required
                                            value="{{ $member->mobile }}">
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="vanue_inp">Venue Name</label>
                                        <input type="text" class="form-control" id="vanue_inp"
                                            placeholder="Enter venue name." name="venue_name"
                                            value="{{ $member->venue_name }}">
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="status_select">Role <span class="text-danger">*</span></label>
                                        <select class="form-control" name="role" id="status_select" required>
                                            <option value="" selected disabled>Select role</option>
                                            @foreach ($roles as $list)
                                                <option value="{{ $list->id }}"
                                                    {{ $member->role_id == $list->id ? 'selected' : '' }}>
                                                    {{ $list->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="status_select">Parent Member <span class="text-xs">(Manager)</label>
                                        <select class="form-control" name="manager" id="status_select">
                                            <option value="" selected>Select parent member</option>
                                            @if (isset($member->parent_id))
                                                @foreach ($managers as $list)
                                                    <option value="{{ $list->id }}"
                                                        {{ $member->parent_id == $list->id ? 'selected' : '' }}>
                                                        {{ $list->name }}</option>
                                                @endforeach
                                            @else
                                                @foreach ($managers as $list)
                                                    <option value="{{ $list->id }}">{{ $list->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="status_select">Status <span class="text-danger">*</span></label>
                                        <select class="form-control" name="status" id="status_select" required>
                                            <option value="1" selected>Active</option>
                                            <option value="0" {{ $member->status == 0 ? 'selected' : '' }}>Deactive
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        <label for="nv_nvrm_inp">Select NVRM (<i style="font-size:13px">Select Nvrm if this is rm account</i>)</label>
                                        <div class="d-flex">
                                            @foreach ($getRm as $rm)
                                                <div class="custom-control custom-radio my-1 mx-2">
                                                    <input class="custom-control-input" type="radio" name="nvrm_id"
                                                        id="team_member_{{ $rm->id }}" value="{{ $rm->id }}"
                                                        {{ isset($member) && $member->nvrm_id == $rm->id ? 'checked' : '' }}>
                                                    <label for="team_member_{{ $rm->id }}"
                                                        class="custom-control-label">{{ $rm->name }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col">
                                    <p>
                                        <span class="text-danger">*</span>
                                        Fields are required.
                                    </p>
                                </div>
                                <div class="col text-right">
                                    <a href="{{ route('admin.team.list') }}" class="btn btn-sm bg-secondary m-1">Back</a>
                                    <button type="submit" class="btn btn-sm m-1 text-light"
                                        style="background-color: var(--wb-dark-red);">Submit</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
