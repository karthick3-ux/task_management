<!doctype html>
<html class="no-js" lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>My-Task:: @yield('title', 'Dashboard')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <!-- project css file -->
    <link rel="stylesheet" href="{{ asset('assets/css/my-task.style.min.css') }}">

    @livewireStyles

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    
    <!-- Custom Select2 Bootstrap 5 Theme -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-5-theme/1.3.0/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        .select2-container .select2-selection--multiple {
            min-height: 48px !important;
        }
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #ced4da !important;
            border-radius: 0.375rem !important;
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.js" integrity="sha512-+k1pnlgt4F1H8L7t3z95o3/KO+o78INEcXTbnoJQ/F2VqDVhWoaiVml/OEHv9HsVgxUaVW+IbiZPUJQfF/YxZw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>




</head>
<body data-mytask="theme-indigo">
    <div id="mytask-layout">
        
        <!-- sidebar -->
        <div class="sidebar px-4 py-4 py-md-5 me-0" x-show="open">
            <div class="d-flex flex-column h-100">
                <a href="{{ route('dashboard') }}" class="mb-0 brand-icon">
                    <span class="logo-icon">
                        <svg width="35" height="35" fill="currentColor" class="bi bi-clipboard-check" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                            <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
                            <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
                        </svg>
                    </span>
                    <span class="logo-text">Task Management</span>
                </a>
                
                <!-- Menu: main ul -->
                <ul class="menu-list flex-grow-1 mt-3">
                    <li class="collapsed">
                        <a class="m-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="icofont-home fs-5"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    @if(auth()->user()->isSuperAdmin())
   <li class="collapsed">
                        <a class="m-link {{ request()->routeIs('admin_dashboard') ? 'active' : '' }}" href="{{ route('admin_dashboard') }}">
                            <i class="icofont-home fs-5"></i> <span>Admin Dashboard</span>
                        </a>
                    </li>
                    @endif
                    
                    @can('view_users')
                    <li class="collapsed">
                        <a class="m-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="icofont-users-alt-5 fs-5"></i> <span>Employees</span>
                        </a>
                    </li>
                    @endcan

                       @can('manage_roles')
                    <li class="collapsed">
                        <a class="m-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                            <i class="icofont-users-alt-5 fs-5"></i> <span>Roles and permissions</span>
                        </a>
                    </li>
                    @endcan
                    
                     @can('view_departments')
                    <li class="collapsed">
                        <a class="m-link {{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}">
                            <i class="icofont-building fs-5"></i> <span>Departments</span>
                        </a>
                    </li>
                   @endcan

                       @can('view_projects')
                    <li class="collapsed">
                        <a class="m-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}">
                            <i class="icofont-building fs-5"></i> <span>Projects</span>
                        </a>
                    </li>
                   @endcan
                @canany(['view_project_report','view_employee_reports'])
                     <li class="collapsed">
                    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#report-Components" href="#"><i
                            class="icofont-user-male"></i> <span>Reports</span> <span class="arrow icofont-dotted-down ms-auto text-end fs-5"></span></a>
                    <!-- Menu: Sub menu ul -->
                    <ul class="sub-menu collapse {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="report-Components">
                        @can('view_project_report')
                        <li><a class="ms-link {{ request()->routeIs('reports.projects') ? 'active' : '' }}" href="{{route('reports.projects')}}"> <span>Project Reports</span></a></li>
                        @endcan
                        @can('view_employee_reports')
                        <li><a class="ms-link {{ request()->routeIs('reports.employees') ? 'active' : '' }}" href="{{route('reports.employees')}}"> <span>Employee Reports</span></a></li>
                        @endcan
                    </ul>
                </li>
                  @endcanany
                </ul>

                <!-- Menu: menu collapse btn -->
                <button type="button" class="btn btn-link sidebar-mini-btn text-light">
                    <span class="ms-2"><i class="icofont-bubble-right"></i></span>
                </button>
            </div>
        </div>

        <!-- main body area -->
        <div class="main px-lg-4 px-md-4">

            <!-- Body: Header -->
            <div class="header">
                <nav class="navbar py-4">
                    <div class="container-xxl">
                        
                        <!-- header rightbar icon -->
                        <div class="h-right d-flex align-items-center mr-5 mr-lg-0 order-1">
                            <div class="d-flex" style="visibility:hidden">
                                <a class="nav-link text-primary collapsed" href="#" title="Get Help">
                                    <i class="icofont-info-square fs-5"></i>
                                </a>
                            </div>

                               @php 
                               if(auth()->user()->isSuperAdmin())
                                {
                               $notifications=\App\Models\TaskUpdateHistory::with(['user', 'task'])
            ->orderBy('created_at', 'desc')

            ->limit(15)
            ->get()
            ->map(function ($history) {
                $history->formatted_date = $history->created_at->format('M d, Y \a\t g:i A');
                $history->time_ago = $history->created_at->diffForHumans();
                return $history;
            });
        }
        else{
             $notifications=\App\Models\TaskUpdateHistory::with(['user', 'task'])
             ->where('type','assignment_started')
             ->where('user_id',auth()->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($history) {
                $history->formatted_date = $history->created_at->format('M d, Y \a\t g:i A');
                $history->time_ago = $history->created_at->diffForHumans();
                return $history;
            });
        }
            @endphp
           
                              <div class="dropdown notifications">
                            <a class="nav-link dropdown-toggle pulse" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="icofont-alarm fs-5"></i>
                                <span class="pulse-ring"></span>
                            </a>

                 
                            <div id="NotificationsDiv" class="dropdown-menu rounded-lg shadow border-0 dropdown-animation dropdown-menu-sm-end p-0 m-0">
                                <div class="card border-0 w380">
                                    <div class="card-header border-0 p-3">
                                        <h5 class="mb-0 font-weight-light d-flex justify-content-between">
                                            <span>Notifications</span>
                                            <span class="badge text-white">{{count($notifications)}}</span>
                                        </h5>
                                    </div>
                                    <div class="tab-content card-body">
                                        <div class="tab-pane fade show active">
                                            <ul class="list-unstyled list mb-0">
                                                @foreach($notifications as $notification)
                                                <li class="py-2 mb-1 border-bottom">
                                                 
                                                        <div class="flex-fill ms-2">
                                                            <p class="d-flex justify-content-between mb-0 "><span class="font-weight-bold">{{ $notification->task->project->project_name }} - {{ $notification->task->task_name }}</span> <small>{{ $notification->time_ago }}</small></p>
                                                            <span class="">{{$notification->message}} by {{ $notification->user->name }}</span>
                                                        </div>
                                                    
                                                </li>
                                                @endforeach
                                             
                                            </ul>
                                        </div>
                                    </div>
                                   
                                </div>
                            </div>
                        </div>
                            <div class="dropdown user-profile ml-2 ml-sm-3 d-flex align-items-center">
                                <div class="u-info me-2">
                                    <p class="mb-0 text-end line-height-sm">
                                        <span class="font-weight-bold">{{ auth()->user()->name }}</span>
                                    </p>
                                    <small>{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</small>
                                </div>
                                <a class="nav-link dropdown-toggle pulse p-0" href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static">
                                    <img class="avatar lg rounded-circle img-thumbnail" src="{{ auth()->user()->avatar_url }}" alt="profile">
                                </a>
                                <div class="dropdown-menu rounded-lg shadow border-0 dropdown-animation dropdown-menu-end p-0 m-0">
                                    <div class="card border-0 w280">
                                        <div class="card-body pb-0">
                                            <div class="d-flex py-1">
                                                <img class="avatar rounded-circle" src="{{ auth()->user()->avatar_url }}" alt="profile">
                                                <div class="flex-fill ms-3">
                                                    <p class="mb-0"><span class="font-weight-bold">{{ auth()->user()->name }}</span></p>
                                                    <small>{{ auth()->user()->email }}</small>
                                                </div>
                                            </div>
                                            <div><hr class="dropdown-divider border-dark"></div>
                                        </div>
                                        <div class="list-group m-2">
                                            <!-- <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action border-0">
                                                <i class="icofont-ui-user fs-6 me-3"></i>Profile & account
                                            </a> -->
                                            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="list-group-item list-group-item-action border-0">
                                                <i class="icofont-logout fs-6 me-3"></i>Sign out
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- menu toggler -->
                        <button class="navbar-toggler p-0 border-0 menu-toggle order-3"  type="button" data-bs-toggle="collapse" data-bs-target="#mainHeader">
                            <span class="fa fa-bars"></span>
                        </button>

                        <!-- main menu Search-->
                        <div class="order-0 col-lg-4 col-md-4 col-sm-12 col-12 mb-3 mb-md-0">
                            <!-- <div class="input-group flex-nowrap input-group-lg">
                                <button type="button" class="input-group-text" id="addon-wrapping"><i class="fa fa-search"></i></button>
                                <input type="search" class="form-control" placeholder="Search" aria-label="search" aria-describedby="addon-wrapping">
                                <button type="button" class="input-group-text add-member-top" id="addon-wrappingone" data-bs-toggle="modal" data-bs-target="#addUser"><i class="fa fa-plus"></i></button>
                            </div> -->
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Body: Body -->
            <div class="body d-flex py-lg-3 py-md-2">
                <div class="container-xxl">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Jquery Core Js -->
    <script src="{{ asset('assets/bundles/libscripts.bundle.js') }}"></script>
    <script src="{{ asset('js/template.js') }}"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

    <!-- <script src="{{ asset('js/page/chart-apex.js')}}"></script> -->

    @livewireScripts

    
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <script>
            // Integrate with your notification system
            console.log('Success: {{ session('success') }}');
        </script>
    @endif

    @if (session()->has('error'))
        <script>
            console.log('Error: {{ session('error') }}');
        </script>
    @endif
    @stack('scripts')


   
</body>
</html>