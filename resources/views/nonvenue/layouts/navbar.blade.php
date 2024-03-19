@php
$auth_user = Auth::guard('nonvenue')->user();
$auth_user_role = $auth_user->get_role;
@endphp
<nav class="main-header navbar navbar-expand navbar-dark navbar-light" style="background: var(--wb-renosand)">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link" data-widget="pushmenu" id="sidebar_collapsible_elem" data-collapse="1" onclick="handle_sidebar_collapse(this)"><i class="fas fa-bars"></i></a>
        </li>
        @if (array_search("add", $auth_user_role->permissions->lead) !== false)
        <li class="nav-item d-none d-sm-inline-block">
            <a href="javascript:void(0);" class="nav-link" data-bs-toggle="modal" data-bs-target="#manageNvLeadModal">Create Lead</a>
        </li>
        @endif
    </ul>
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" title="Logout" onclick="return confirm('Are you sure want to logout?')" href="{{route('logout')}}">
                <i class="fas fa-power-off"></i>
            </a>
        </li>
        @yield('navbar-right-links')
    </ul>
</nav>
@include('nonvenue.lead.manage_lead_modal')