<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class SidebarService
{
    /**
     * Get the menu items for the admin sidebar.
     *
     * @return array
     */
    public function getAdminSidebarMenu()
    {
        // Get the current user (you may also consider using policies or permissions)
        $user = Auth::user();

        // Sample logic to dynamically generate menu items based on user role or permissions
        if ($user && ($user->type === 'admin' || $user->type === 'sub-admin')) {
            $menu = [
                [
                    'route' => route('admin.dashboard'),
                    'name' => __('Dashboard'),
                    'icon' => 'bx bx-home-circle',
                    'child' => [],
                    'all_routes' => ['admin.dashboard'],
                ],
                [
                    'route' => route('admin.users.index'),
                    'name' => __('User Management'),
                    'icon' => 'fa fa-user',
                    'child' => [],
                    'all_routes' => ['admin.user.index', 'admin.user.show', 'admin.user.add'],
                    'permission' => 'user-list',
                ],
                [
                    'route' => route('admin.business-users.index'),
                    'name' => __('Business Users'),
                    'icon' => 'bx bx-user-circle',
                    'child' => [],
                    'all_routes' => ['admin.user.index', 'admin.user.show', 'admin.user.add'],
                    'permission' => 'business-user-list',
                ],
                [
                    'route' => route('admin.sub-admins.index'),
                    'name' => __('Sub Admins'),
                    'icon' => 'fa fa-user-shield',
                    'child' => [],
                    'all_routes' => ['admin.sub-admins.index', 'admin.sub-admins.create', 'admin.sub-admins.edit'],
                    'permission' => 'sub-admin-list',
                ],
                [
                    'route' => route('admin.business-category.index'),
                    'name' => __('Business Categories'),
                    'icon' => 'fa fa-envelope-square',
                    'child' => [],
                    'all_routes' => ['admin.business-category.index', 'admin.business-category.show', 'admin.business-category.create'],
                    'permission' => 'business-category-list',
                ],
                [
                    'route' => 'javascript:;',
                    'name' => __('Business Management'),
                    'icon' => 'fa fa-briefcase',
                    'permission' => 'business-list',
                    'child' => [
                        [
                            'route' => route('admin.pending.business'),
                            'name' => __('Pending'),
                            'icon' => '',
                            'all_routes' => ['admin.pending.business'],
                        ],
                        [
                            'route' => route('admin.approved.business'),
                            'name' => __('Approved'),
                            'icon' => '',
                            'all_routes' => ['admin.approved.business'],
                        ],
                    ],
                    'all_routes' => ['admin.approved.business', 'admin.business.show'],
                ],
                [
                    'route' => route('admin.event-category.index'),
                    'name' => __('Event Categories'),
                    'icon' => 'fa fa-tasks',
                    'child' => [],
                    'all_routes' => ['admin.event-category.index', 'admin.event-category.show', 'admin.event-category.create'],
                    'permission' => 'event-category-list',
                ],
                [
                    'route' => 'javascript:;',
                    'name' => __('Event Management'),
                    'icon' => 'fa fa-calendar',
                    'permission' => 'event-list',
                    'child' => [
                        [
                            'route' => route('admin.pending.event'),
                            'name' => __('Pending'),
                            'icon' => '',
                            'all_routes' => ['admin.pending.event'],
                        ],
                        [
                            'route' => route('admin.approved.event'),
                            'name' => __('Approved'),
                            'icon' => '',
                            'all_routes' => ['admin.approved.event'],
                        ],
                    ],
                    'all_routes' => ['admin.approved.event', 'admin.event.show'],
                ],
                [
                    'route' => route('admin.home-banner.index'),
                    'name' => __('Banner'),
                    'icon' => 'fa fa-image',
                    'child' => [],
                    'all_routes' => ['admin.home-banner.index', 'admin.home-banner.show'],
                    'permission' => 'banner-list',
                ],
                [
                    'route' => route('admin.city.index'),
                    'name' => __('Cities'),
                    'icon' => 'fa fa-map-marker',
                    'child' => [],
                    'all_routes' => ['admin.city.index', 'admin.city.show'],
                    'permission' => 'city-list',
                ],
                [
                    'route' => route('admin.service.index'),
                    'name' => __('Services'),
                    'icon' => 'fa fa-adjust',
                    'child' => [],
                    'all_routes' => ['admin.service.index', 'admin.service.show'],
                    'permission' => 'service-list',
                ],
                [
                    'route' => route('admin.business.qr.change.index'),
                    'name' => __('Change QR Code'),
                    'icon' => 'fa fa-qrcode',
                    'child' => [],
                    'all_routes' => [],
                    'permission' => 'qr-change-list',
                ],
                [
                    'route' => route('admin.boost.index'),
                    'name' => __('Boosts'),
                    'icon' => 'fa fa-exclamation-triangle',
                    'child' => [],
                    'all_routes' => [],
                    'permission' => 'boost-list',
                ],
                [
                    'route' => route('admin.my-event.index'),
                    'name' => __('My Events'),
                    'icon' => 'fa fa-calendar',
                    'child' => [],
                    'all_routes' => ['admin.my-event.index', 'admin.my-event.show'],
                    'permission' => 'my-event-list',
                ],
                [
                    'route' => route('admin.post.index'),
                    'name' => __('My Posts'),
                    'icon' => 'fa fa-tasks',
                    'child' => [],
                    'all_routes' => ['admin.post.index', 'admin.post.show'],
                    'permission' => 'my-post-list',
                ],
                [
                    'route' => route('admin.notification.create'),
                    'name' => __('Notifications'),
                    'icon' => 'fa fa-bell',
                    'child' => [],
                    'all_routes' => ['admin.notification.create'],
                    'permission' => 'notification-list',
                ],
                [
                    'route' => route('admin.report-type.index'),
                    'name' => __('Report Types'),
                    'icon' => 'fa fa-flag',
                    'child' => [],
                    'all_routes' => ['admin.report-type.index', 'admin.report-type.create', 'admin.report-type.edit'],
                    'permission' => 'report-type-list',
                ],
                [
                    'route' => route('admin.reports.index'),
                    'name' => __('Report'),
                    'icon' => 'fa fa-bell',
                    'child' => [],
                    'all_routes' => [],
                    'permission' => 'report-list',
                ],
                [
                    'route' => route('admin.moderation.posts'),
                    'name' => __('Moderation'),
                    'icon' => 'bx bx-detail',
                    'child' => [],
                    'all_routes' => ['admin.moderation.index'],
                    'permission' => 'moderation-list',
                ],
                [
                    'route' => route('admin.contents.index'),
                    'name' => __('Contents'),
                    'icon' => 'bx bx-detail',
                    'child' => [],
                    'all_routes' => ['admin.contents.index', 'admin.contents.show'],
                    'permission' => 'content-list',
                ],
                [
                    'route' => route('admin.business-ratings.index'),
                    'name' => __('Business Ratings'),
                    'icon' => 'fa fa-star',
                    'child' => [],
                    'all_routes' => ['admin.business-ratings.index'],
                    'permission' => 'business-rating-list',
                ],
                [
                    'route' => 'javascript:;',
                    'name' => __('Reports'),
                    'icon' => 'fa fa-calendar',
                    'permission' => 'reported-content-list',
                    'child' => [
                        [
                            'route' => route('admin.report-post.index'),
                            'name' => __('Reported Posts'),
                            'icon' => '',
                            'all_routes' => ['admin.report-post.index'],
                        ],
                        [
                            'route' => route('admin.report-comment.index'),
                            'name' => __('Reported Comments'),
                            'icon' => '',
                            'all_routes' => ['admin.report-comment.index'],
                        ],
                    ],
                    'all_routes' => ['admin.report-post.index'],
                ],
                [
                    'route' => route('admin.business-post-permissions.index'),
                    'name' => __('Business Post Permissions'),
                    'icon' => 'fa fa-lock',
                    'child' => [],
                    'all_routes' => ['admin.business-post-permissions.index', 'admin.business-post-permissions.create'],
                    'permission' => 'business-post-permission-list',
                ],
                [
                    'route' => 'javascript:;',
                    'name' => __('Roles & Permissions'),
                    'icon' => 'fa fa-shield-alt',
                    'permission' => 'role-list',
                    'child' => [
                        [
                            'route' => route('admin.roles.index'),
                            'name' => __('Roles'),
                            'icon' => '',
                            'all_routes' => ['admin.roles.index', 'admin.roles.create', 'admin.roles.edit'],
                        ],
                        [
                            'route' => route('admin.permissions.index'),
                            'name' => __('Permissions'),
                            'icon' => '',
                            'all_routes' => ['admin.permissions.index', 'admin.permissions.create', 'admin.permissions.edit'],
                        ],
                    ],
                    'all_routes' => ['admin.roles.index', 'admin.permissions.index'],
                ],
            ];

            // Example: Add a "Settings" item only for users with admin privileges
            if ($user && ($user->type == 'admin' || $user->type == 'sub-admin')) {
                $menu[] = [
                    'route' => 'javascript:;',
                    'name' => __('Personal Settings'),
                    'icon' => 'bx bxs-badge-check',
                    'child' => [
                        [
                            'route' => route('admin.change-password'),
                            'name' => __('Change Password'),
                            'icon' => '',
                            'all_routes' => ['admin.change-password'],
                        ],
                        [
                            'route' => route('admin.profile'),
                            'name' => __('Profile'),
                            'icon' => '',
                            'all_routes' => ['admin.profile'],
                        ],
                    ],
                    'all_routes' => ['admin.change-password', 'admin.profile'],
                ];
                
                // General Settings often restricted to Super Admins only, but adding check anyway
                $menu[] = [
                    'route' => 'javascript:;',
                    'name' => __('General Settings'),
                    'icon' => 'bx bx-cog',
                    'permission' => 'setting-list',
                    'child' => [
                        [
                            'route' => route('admin.site-settings'),
                            'name' => __('Site Settings'),
                            'icon' => '',
                            'all_routes' => ['admin.site-settings'],
                        ],
                        [
                            'route' => route('admin.version-settings'),
                            'name' => __('Version Settings'),
                            'icon' => '',
                            'all_routes' => ['admin.version-settings'],
                        ],
                    ],
                    'all_routes' => ['admin.version-settings', 'admin.site-settings'],
                ];
            }

            // Add a logout option at the end of the menu
            $menu[] = [
                'route' => route('admin.logout'),
                'name' => __('Logout'),
                'icon' => 'bx bx-power-off',
                'child' => [],
                'all_routes' => [],
            ];

            // Filter menu items for Sub-Admins based on permissions
            if ($user->type === 'sub-admin') {
                $filteredMenu = [];
                foreach ($menu as $item) {
                    if (!isset($item['permission']) || $user->hasPermissionTo($item['permission'])) {
                        $filteredMenu[] = $item;
                    }
                }
                return $filteredMenu;
            }

            return $menu;

            return $menu;
        } else {
            $menu = [
                [
                    'route' => route('user.dashboard'),
                    'name' => __('Dashboard'),
                    'icon' => 'bx bx-home-circle',
                    'child' => [],
                    'all_routes' => ['user.dashboard'],
                ],
                [
                    'route' => 'javascript:;',
                    'name' => __('Business Manegement'),
                    'icon' => 'fa fa-image',
                    'child' => [
                        [
                            'route' => route('user.business.index'),
                            'name' => __('Businesses'),
                            'icon' => '',
                            'all_routes' => ['user.business.index', 'user.business.show'],
                            'permission' => 'business-list',
                        ],
                        [
                            'route' => route('user.service.index'),
                            'name' => __('Services'),
                            'icon' => '',
                            'all_routes' => ['user.service.index'],
                            'permission' => 'service-list',
                        ],
                        [
                            'route' => route('user.nowis-point.index'),
                            'name' => __('Nowis Point'),
                            'icon' => '',
                            'all_routes' => ['user.nowis-point.index'],
                            'permission' => 'nowis-point-list',
                        ],
                        // [
                        //     'route' => route('user.stamp-card.index'),
                        //     'name' => __('Stamp Card'),
                        //     'icon' => '',
                        //     'all_routes' => ['user.stamp-card.index'],
                        //     'permission' => 'stamp-card-list',
                        // ],
                    ],
                    'all_routes' => ['user.change-password', 'user.profile'],
                ],
                [
                    'route' => route('user.sub-business-users.index'),
                    'name' => __('Sub Business Users'),
                    'icon' => 'fa fa-users-cog',
                    'child' => [],
                    'all_routes' => ['user.sub-business-users.index', 'user.sub-business-users.create', 'user.sub-business-users.edit'],
                    'permission' => 'sub-user-list',
                ],
                [
                    'route' => route('user.roles.index'),
                    'name' => __('Roles & Permissions'),
                    'icon' => 'fa fa-user-shield',
                    'child' => [],
                    'all_routes' => ['user.roles.index', 'user.roles.create', 'user.roles.edit'],
                    'permission' => 'role-list',
                ],
                [
                    'route' => route('user.event.index'),
                    'name' => __('Events'),
                    'icon' => 'fa fa-calendar',
                    'child' => [],
                    'all_routes' => ['user.event.index', 'user.event.show'],
                    'permission' => 'my-event-list',
                ],
                [
                    'route' => route('user.post.index'),
                    'name' => __('Posts'),
                    'icon' => 'fa fa-tasks',
                    'child' => [],
                    'all_routes' => ['user.post.index', 'user.post.show'],
                    'permission' => 'my-post-list',
                ],
                [
                    'route' => route('user.notification.create'),
                    'name' => __('Customer Interaction'),
                    'icon' => 'fa fa-bell',
                    'child' => [],
                    'all_routes' => ['notification.create'],
                    'permission' => 'notification-list',
                ],
                [
                    'route' => route('user.subscription.index'),
                    'name' => __('Subscriptions'),
                    'icon' => 'fa fa-asterisk',
                    'child' => [],
                    'all_routes' => ['user.subscription.index'],
                ],
                [
                    'route' => route('user.2fa.setup'),
                    'name' => __('Two-Factor Authentication'),
                    'icon' => 'fa fa-check-square',
                    'child' => [],
                    'all_routes' => ['user.2fa.setup'],
                    'permission' => 'two-factor-auth',
                ],
                [
                    'route' => 'javascript:;',
                    'name' => __('Personal Settings'),
                    'icon' => 'bx bxs-badge-check',
                    'child' => [
                        [
                            'route' => route('user.change-password'),
                            'name' => __('Change Password'),
                            'icon' => '',
                            'all_routes' => ['user.change-password'],
                        ],
                        [
                            'route' => route('user.profile'),
                            'name' => __('Profile'),
                            'icon' => '',
                            'all_routes' => ['user.profile'],
                        ],
                    ],
                    'all_routes' => ['user.change-password', 'user.profile'],
                ],
                [
                'route' => route('user.logout'),
                'name' => __('Logout'),
                'icon' => 'bx bx-power-off',
                'child' => [],
                'all_routes' => [],
                ]
                
            ];
            if ($user && $user->type === 'sub-businessuser') {
                $filteredMenu = [];
                $restrictedRoutes = [
                    route('user.sub-business-users.index'),
                    route('user.roles.index'),
                    route('user.subscription.index')
                ];

                foreach ($menu as $item) {
                    // Check top level items
                    if (isset($item['route']) && in_array($item['route'], $restrictedRoutes)) {
                        continue;
                    }

                    // Check children
                    if (!empty($item['child'])) {
                        $item['child'] = array_filter($item['child'], function($child) use ($restrictedRoutes) {
                            return !in_array($child['route'], $restrictedRoutes);
                        });
                        
                        // If no children left and it was a parent with no route, skip it
                        if (empty($item['child']) && $item['route'] === 'javascript:;') {
                            continue;
                        }
                    }

                    $filteredMenu[] = $item;
                }

                // Further filter based on actual permissions assigned to the role
                $permissionFilteredMenu = [];
                foreach ($filteredMenu as $item) {
                    if (isset($item['permission'])) {
                        if (!$user->hasPermissionTo($item['permission'])) {
                            continue;
                        }
                    }

                    if (!empty($item['child'])) {
                        $item['child'] = array_filter($item['child'], function($child) use ($user) {
                            if (isset($child['permission'])) {
                                return $user->hasPermissionTo($child['permission']);
                            }
                            return true;
                        });

                        if (empty($item['child']) && $item['route'] === 'javascript:;') {
                            continue;
                        }
                    }
                    $permissionFilteredMenu[] = $item;
                }

                return $permissionFilteredMenu;
            }

            return $menu;
        }
    }
}
