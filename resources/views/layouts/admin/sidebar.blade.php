<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-menu">Menu</li>

                @foreach ($sidebarMenu as $menuItem)
                    <li class="{{is_active_module($menuItem['all_routes'])}}">
                        <a href="{{ $menuItem['route'] }}" class="waves-effect {{ !empty($menuItem['child']) ? 'has-arrow' : '' }}">
                            <i class="{{ $menuItem['icon'] }}"></i>
                            <span>{{ $menuItem['name'] }}</span>
                        </a>
                        @if (!empty($menuItem['child']))
                            <ul class="sub-menu" aria-expanded="false">
                                @foreach ($menuItem['child'] as $subMenuItem)
                                    <li class="{{is_active_module($subMenuItem['all_routes'])}}">
                                        <a href="{{ $subMenuItem['route'] }}">
                                            {{ $subMenuItem['name'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
