<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="{{route('admin.dashboard')}}" class="logo">
                    <span class="logo-sm">
                        <img src="{{checkFileExist(config('general_settings.SMALL_SITE_LOGO'))}}" height="20">
                    </span>
                    <span class="logo-lg">
                        <img src="{{checkFileExist(config('general_settings.SITE_LOGO'))}}" height="50">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
        </div>

        <div class="d-flex">
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user" src="{{Auth::user()->profile_image}}"
                         alt="{{ucfirst(@Auth::user()->username)}}">
                    <span class="d-none d-xl-inline-block ms-1" key="t-henry">{{ucfirst(@Auth::user()->username)}}
                         @if(Auth::user()->is_verified == 1)
                            <i class="mdi mdi-check-decagram " 
                            style="font-size: 18px; vertical-align: middle; color: #2f41ff;"></i>
                        @endif
                    </span>
                    @if (Auth::user()->type == 'admin')<i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>@endif
                </button>
                @if (Auth::user()->type == 'admin')
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <a class="dropdown-item" href="{{route('admin.profile')}}"><i class="bx bx-user font-size-16 align-middle me-1"></i> <span key="t-profile">Profile</span></a>
                        <a class="dropdown-item" href="{{route('admin.change-password')}}"><i class="bx bx-hide font-size-16 align-middle me-1"></i> <span key="t-settings">Change Password</span></a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="{{route('admin.logout')}}"><i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> <span key="t-logout">Logout</span></a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</header>
