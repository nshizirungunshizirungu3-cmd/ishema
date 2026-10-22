<?php
declare(strict_types=1);

use App\Core\Router;

// -------------------- PUBLIC WEBSITE --------------------
Router::get('/', [App\Controllers\PublicController::class, 'home']);
Router::get('/home', [App\Controllers\PublicController::class, 'home']);
Router::get('/services', [App\Controllers\PublicController::class, 'services']);
Router::get('/about', [App\Controllers\PublicController::class, 'about']);
Router::get('/packages', [App\Controllers\PublicController::class, 'packages']);
Router::get('/tutorials', [App\Controllers\PublicController::class, 'tutorials']);
Router::get('/contact', [App\Controllers\PublicController::class, 'contact']);
Router::post('/contact/send', [App\Controllers\PublicController::class, 'sendContact']);
Router::post('/demo/request', [App\Controllers\PublicController::class, 'requestDemo']);

// -------------------- AUTHENTICATION --------------------
Router::get('/login', [App\Controllers\AuthController::class, 'login'], ['guest']);
Router::post('/login', [App\Controllers\AuthController::class, 'doLogin'], ['guest']);
Router::get('/logout', [App\Controllers\AuthController::class, 'logout'], ['auth']);
Router::get('/forgot-password', [App\Controllers\AuthController::class, 'forgot'], ['guest']);
Router::post('/forgot-password', [App\Controllers\AuthController::class, 'doForgot'], ['guest']);
Router::get('/reset-password/{token}', [App\Controllers\AuthController::class, 'reset'], ['guest']);
Router::post('/reset-password/{token}', [App\Controllers\AuthController::class, 'doReset'], ['guest']);
Router::get('/language/{lang}', [App\Controllers\AuthController::class, 'switchLanguage']);
Router::get('/select-business', [App\Controllers\AuthController::class, 'selectBusiness'], ['auth']);
Router::post('/select-business', [App\Controllers\AuthController::class, 'doSelectBusiness'], ['auth']);

// -------------------- CUSTOMER QR PORTAL (public, token based) --------------------
Router::get('/qr/{token}', [App\Controllers\CustomerController::class, 'qr']);
Router::post('/qr/{token}/add-to-cart', [App\Controllers\CustomerController::class, 'addToCart'], ['no_csrf']);
Router::post('/qr/{token}/update-cart', [App\Controllers\CustomerController::class, 'updateCart'], ['no_csrf']);
Router::post('/qr/{token}/clear-cart', [App\Controllers\CustomerController::class, 'clearCart'], ['no_csrf']);
Router::get('/qr/{token}/checkout', [App\Controllers\CustomerController::class, 'checkout']);
Router::post('/qr/{token}/place-order', [App\Controllers\CustomerController::class, 'placeOrder'], ['no_csrf']);
Router::get('/order/{orderNumber}', [App\Controllers\CustomerController::class, 'track']);
Router::get('/order/{orderNumber}/poll', [App\Controllers\CustomerController::class, 'pollStatus']);
Router::get('/order/{orderNumber}/receipt', [App\Controllers\CustomerController::class, 'receipt']);
Router::post('/order/confirm-receipt', [App\Controllers\CustomerController::class, 'confirmReceipt'], ['no_csrf']);
Router::post('/order/request-clearing', [App\Controllers\CustomerController::class, 'requestClearing'], ['no_csrf']);
Router::post('/order/rate', [App\Controllers\CustomerController::class, 'rate'], ['no_csrf']);

// -------------------- GUEST PORTAL (public booking) --------------------
Router::get('/hotel/{businessId}/rooms', [App\Controllers\GuestPortalController::class, 'rooms']);
Router::get('/book/{businessId}', [App\Controllers\GuestPortalController::class, 'book']);
Router::post('/book/{businessId}', [App\Controllers\GuestPortalController::class, 'doBook']);
Router::get('/booking/lookup', [App\Controllers\GuestPortalController::class, 'lookup']);
Router::get('/guest/bookings', [App\Controllers\GuestPortalController::class, 'myBookings'], ['auth']);
Router::get('/guest/requests', [App\Controllers\GuestPortalController::class, 'myRequests'], ['auth']);
Router::post('/guest/service', [App\Controllers\GuestPortalController::class, 'requestService'], ['auth']);
Router::post('/guest/checkout-request/{bookingId}', [App\Controllers\GuestPortalController::class, 'requestCheckout'], ['auth']);
Router::post('/guest/review/{bookingId}', [App\Controllers\GuestPortalController::class, 'review'], ['auth']);

// -------------------- PAYMENT PROVIDER CALLBACKS (no CSRF, external) --------------------
Router::any('/payment/callback/{provider}', [App\Controllers\PaymentController::class, 'callback'], ['no_csrf']);

// -------------------- AUTHENTICATED DASHBOARD --------------------
Router::get('/dashboard', [App\Controllers\DashboardController::class, 'index'], ['auth', 'tenant', 'subscription']);
Router::get('/dashboard/subscription', [App\Controllers\DashboardController::class, 'subscription'], ['auth', 'tenant']);
Router::get('/profile', [App\Controllers\DashboardController::class, 'profile'], ['auth']);
Router::post('/profile/update', [App\Controllers\DashboardController::class, 'updateProfile'], ['auth']);
Router::post('/profile/password', [App\Controllers\DashboardController::class, 'changePassword'], ['auth']);

// -------------------- SUPER ADMIN --------------------
Router::get('/admin', [App\Controllers\SuperAdminController::class, 'index'], ['auth', 'super_admin']);
Router::get('/admin/businesses', [App\Controllers\SuperAdminController::class, 'businesses'], ['auth', 'super_admin']);
Router::get('/admin/businesses/create', [App\Controllers\SuperAdminController::class, 'createBusiness'], ['auth', 'super_admin']);
Router::post('/admin/businesses/create', [App\Controllers\SuperAdminController::class, 'storeBusiness'], ['auth', 'super_admin']);
Router::get('/admin/businesses/{id}/edit', [App\Controllers\SuperAdminController::class, 'editBusiness'], ['auth', 'super_admin']);
Router::post('/admin/businesses/{id}/update', [App\Controllers\SuperAdminController::class, 'updateBusiness'], ['auth', 'super_admin']);
Router::post('/admin/businesses/{id}/toggle-status', [App\Controllers\SuperAdminController::class, 'toggleBusinessStatus'], ['auth', 'super_admin']);
Router::post('/admin/businesses/{id}/archive', [App\Controllers\SuperAdminController::class, 'archiveBusiness'], ['auth', 'super_admin']);
Router::get('/admin/businesses/{id}/view', [App\Controllers\SuperAdminController::class, 'viewBusiness'], ['auth', 'super_admin']);
Router::get('/admin/packages', [App\Controllers\SuperAdminController::class, 'packages'], ['auth', 'super_admin']);
Router::get('/admin/packages/create', [App\Controllers\SuperAdminController::class, 'createPackage'], ['auth', 'super_admin']);
Router::post('/admin/packages/create', [App\Controllers\SuperAdminController::class, 'storePackage'], ['auth', 'super_admin']);
Router::get('/admin/packages/{id}/edit', [App\Controllers\SuperAdminController::class, 'editPackage'], ['auth', 'super_admin']);
Router::post('/admin/packages/{id}/update', [App\Controllers\SuperAdminController::class, 'updatePackage'], ['auth', 'super_admin']);
Router::post('/admin/packages/{id}/delete', [App\Controllers\SuperAdminController::class, 'deletePackage'], ['auth', 'super_admin']);
Router::get('/admin/users', [App\Controllers\SuperAdminController::class, 'users'], ['auth', 'super_admin']);
Router::get('/admin/users/create', [App\Controllers\SuperAdminController::class, 'createUser'], ['auth', 'super_admin']);
Router::post('/admin/users/create', [App\Controllers\SuperAdminController::class, 'storeUser'], ['auth', 'super_admin']);
Router::get('/admin/users/{id}/edit', [App\Controllers\SuperAdminController::class, 'editUser'], ['auth', 'super_admin']);
Router::post('/admin/users/{id}/update', [App\Controllers\SuperAdminController::class, 'updateUser'], ['auth', 'super_admin']);
Router::post('/admin/users/{id}/toggle-status', [App\Controllers\SuperAdminController::class, 'toggleUserStatus'], ['auth', 'super_admin']);
Router::get('/admin/tutorials', [App\Controllers\SuperAdminController::class, 'tutorials'], ['auth', 'super_admin']);
Router::get('/admin/tutorials/create', [App\Controllers\SuperAdminController::class, 'createTutorial'], ['auth', 'super_admin']);
Router::post('/admin/tutorials/create', [App\Controllers\SuperAdminController::class, 'storeTutorial'], ['auth', 'super_admin']);
Router::get('/admin/tutorials/{id}/edit', [App\Controllers\SuperAdminController::class, 'editTutorial'], ['auth', 'super_admin']);
Router::post('/admin/tutorials/{id}/update', [App\Controllers\SuperAdminController::class, 'updateTutorial'], ['auth', 'super_admin']);
Router::post('/admin/tutorials/{id}/delete', [App\Controllers\SuperAdminController::class, 'deleteTutorial'], ['auth', 'super_admin']);
Router::get('/admin/contact-messages', [App\Controllers\SuperAdminController::class, 'contactMessages'], ['auth', 'super_admin']);
Router::post('/admin/contact-messages/{id}/read', [App\Controllers\SuperAdminController::class, 'readContactMessage'], ['auth', 'super_admin']);
Router::post('/admin/contact-messages/{id}/delete', [App\Controllers\SuperAdminController::class, 'deleteContactMessage'], ['auth', 'super_admin']);
Router::get('/admin/audit-logs', [App\Controllers\SuperAdminController::class, 'auditLogs'], ['auth', 'super_admin']);
Router::get('/admin/settings', [App\Controllers\SuperAdminController::class, 'settings'], ['auth', 'super_admin']);
Router::post('/admin/settings/save', [App\Controllers\SuperAdminController::class, 'saveSettings'], ['auth', 'super_admin']);
Router::get('/admin/subscriptions', [App\Controllers\SuperAdminController::class, 'subscriptions'], ['auth', 'super_admin']);

// -------------------- RESTAURANT MODULE --------------------
Router::get('/restaurant', [App\Controllers\RestaurantController::class, 'overview'], ['auth', 'tenant', 'subscription']);
Router::get('/restaurant/products', [App\Controllers\RestaurantController::class, 'products'], ['auth', 'tenant', 'subscription']);
Router::get('/restaurant/products/create', [App\Controllers\RestaurantController::class, 'createProduct'], ['auth', 'tenant']);
Router::post('/restaurant/products/create', [App\Controllers\RestaurantController::class, 'storeProduct'], ['auth', 'tenant']);
Router::get('/restaurant/products/{id}/edit', [App\Controllers\RestaurantController::class, 'editProduct'], ['auth', 'tenant']);
Router::post('/restaurant/products/{id}/update', [App\Controllers\RestaurantController::class, 'updateProduct'], ['auth', 'tenant']);
Router::post('/restaurant/products/{id}/delete', [App\Controllers\RestaurantController::class, 'deleteProduct'], ['auth', 'tenant']);
Router::get('/restaurant/categories', [App\Controllers\RestaurantController::class, 'categories'], ['auth', 'tenant', 'subscription']);
Router::post('/restaurant/categories/create', [App\Controllers\RestaurantController::class, 'storeCategory'], ['auth', 'tenant']);
Router::post('/restaurant/categories/{id}/update', [App\Controllers\RestaurantController::class, 'updateCategory'], ['auth', 'tenant']);
Router::post('/restaurant/categories/{id}/delete', [App\Controllers\RestaurantController::class, 'deleteCategory'], ['auth', 'tenant']);
Router::get('/restaurant/tables', [App\Controllers\RestaurantController::class, 'tables'], ['auth', 'tenant', 'subscription']);
Router::post('/restaurant/tables/create', [App\Controllers\RestaurantController::class, 'storeTable'], ['auth', 'tenant']);
Router::post('/restaurant/tables/{id}/update', [App\Controllers\RestaurantController::class, 'updateTable'], ['auth', 'tenant']);
Router::post('/restaurant/tables/{id}/delete', [App\Controllers\RestaurantController::class, 'deleteTable'], ['auth', 'tenant']);
Router::get('/restaurant/tables/{tableId}/qr/download', [App\Controllers\RestaurantController::class, 'downloadQr'], ['auth', 'tenant']);
Router::get('/restaurant/staff', [App\Controllers\RestaurantController::class, 'staff'], ['auth', 'tenant', 'subscription']);
Router::get('/restaurant/staff/create', [App\Controllers\RestaurantController::class, 'createEmployee'], ['auth', 'tenant']);
Router::post('/restaurant/staff/create', [App\Controllers\RestaurantController::class, 'storeEmployee'], ['auth', 'tenant']);
Router::get('/restaurant/staff/{id}/edit', [App\Controllers\RestaurantController::class, 'editEmployee'], ['auth', 'tenant']);
Router::post('/restaurant/staff/{id}/update', [App\Controllers\RestaurantController::class, 'updateEmployee'], ['auth', 'tenant']);
Router::post('/restaurant/staff/{id}/toggle', [App\Controllers\RestaurantController::class, 'toggleEmployee'], ['auth', 'tenant']);
Router::post('/restaurant/shifts/create', [App\Controllers\RestaurantController::class, 'storeShift'], ['auth', 'tenant']);
Router::post('/restaurant/shifts/assign', [App\Controllers\RestaurantController::class, 'assignShift'], ['auth', 'tenant']);
Router::get('/restaurant/inventory', [App\Controllers\RestaurantController::class, 'inventory'], ['auth', 'tenant', 'subscription']);
Router::post('/restaurant/inventory/movement', [App\Controllers\RestaurantController::class, 'stockMovement'], ['auth', 'tenant']);
Router::post('/restaurant/inventory/suppliers/create', [App\Controllers\RestaurantController::class, 'storeSupplier'], ['auth', 'tenant']);
Router::post('/restaurant/inventory/suppliers/{id}/update', [App\Controllers\RestaurantController::class, 'updateSupplier'], ['auth', 'tenant']);
Router::post('/restaurant/inventory/suppliers/{id}/delete', [App\Controllers\RestaurantController::class, 'deleteSupplier'], ['auth', 'tenant']);
Router::get('/restaurant/reports', [App\Controllers\RestaurantController::class, 'reports'], ['auth', 'tenant']);

// -------------------- ORDER MANAGEMENT --------------------
Router::get('/orders', [App\Controllers\OrderController::class, 'list'], ['auth', 'tenant', 'subscription']);
Router::get('/orders/create', [App\Controllers\OrderController::class, 'create'], ['auth', 'tenant']);
Router::post('/orders/create', [App\Controllers\OrderController::class, 'store'], ['auth', 'tenant']);
Router::get('/orders/kitchen', [App\Controllers\OrderController::class, 'kitchen'], ['auth', 'tenant']);
Router::get('/orders/complaints', [App\Controllers\OrderController::class, 'complaints'], ['auth', 'tenant']);
Router::get('/orders/{id}', [App\Controllers\OrderController::class, 'show'], ['auth', 'tenant']);
Router::post('/orders/{id}/update-status', [App\Controllers\OrderController::class, 'updateStatus'], ['auth', 'tenant']);
Router::post('/orders/items/{id}/deliver', [App\Controllers\OrderController::class, 'recordDelivery'], ['auth', 'tenant']);
Router::post('/orders/{id}/confirm-cleared', [App\Controllers\OrderController::class, 'confirmCleared'], ['auth', 'tenant']);
Router::get('/orders/{id}/receipt', [App\Controllers\OrderController::class, 'receipt'], ['auth', 'tenant']);

// -------------------- HOTEL MODULE --------------------
Router::get('/hotel', [App\Controllers\HotelController::class, 'overview'], ['auth', 'tenant', 'subscription']);
Router::get('/hotel/room-types', [App\Controllers\HotelController::class, 'roomTypes'], ['auth', 'tenant', 'subscription']);
Router::post('/hotel/room-types/create', [App\Controllers\HotelController::class, 'storeRoomType'], ['auth', 'tenant']);
Router::post('/hotel/room-types/{id}/update', [App\Controllers\HotelController::class, 'updateRoomType'], ['auth', 'tenant']);
Router::post('/hotel/room-types/{id}/delete', [App\Controllers\HotelController::class, 'deleteRoomType'], ['auth', 'tenant']);
Router::get('/hotel/rooms', [App\Controllers\HotelController::class, 'rooms'], ['auth', 'tenant', 'subscription']);
Router::get('/hotel/rooms/create', [App\Controllers\HotelController::class, 'createRoom'], ['auth', 'tenant']);
Router::post('/hotel/rooms/create', [App\Controllers\HotelController::class, 'storeRoom'], ['auth', 'tenant']);
Router::get('/hotel/rooms/{id}/edit', [App\Controllers\HotelController::class, 'editRoom'], ['auth', 'tenant']);
Router::post('/hotel/rooms/{id}/update', [App\Controllers\HotelController::class, 'updateRoom'], ['auth', 'tenant']);
Router::post('/hotel/rooms/{id}/delete', [App\Controllers\HotelController::class, 'deleteRoom'], ['auth', 'tenant']);
Router::post('/hotel/rooms/{id}/remove-image', [App\Controllers\HotelController::class, 'removeRoomImage'], ['auth', 'tenant']);
Router::get('/hotel/bookings', [App\Controllers\HotelController::class, 'bookings'], ['auth', 'tenant', 'subscription']);
Router::get('/hotel/bookings/create', [App\Controllers\HotelController::class, 'createBooking'], ['auth', 'tenant']);
Router::post('/hotel/bookings/check-availability', [App\Controllers\HotelController::class, 'checkAvailability'], ['auth', 'tenant']);
Router::post('/hotel/bookings/create', [App\Controllers\HotelController::class, 'storeBooking'], ['auth', 'tenant']);
Router::get('/hotel/bookings/{id}/edit', [App\Controllers\HotelController::class, 'editBooking'], ['auth', 'tenant']);
Router::post('/hotel/bookings/{id}/update', [App\Controllers\HotelController::class, 'updateBooking'], ['auth', 'tenant']);
Router::get('/hotel/bookings/{id}', [App\Controllers\HotelController::class, 'viewBooking'], ['auth', 'tenant']);
Router::post('/hotel/bookings/{id}/{action}', [App\Controllers\HotelController::class, 'bookingAction'], ['auth', 'tenant']);
Router::get('/hotel/guests', [App\Controllers\HotelController::class, 'guests'], ['auth', 'tenant']);
Router::post('/hotel/guests/create', [App\Controllers\HotelController::class, 'storeGuest'], ['auth', 'tenant']);
Router::post('/hotel/guests/{id}/update', [App\Controllers\HotelController::class, 'updateGuest'], ['auth', 'tenant']);
Router::get('/hotel/guests/{id}', [App\Controllers\HotelController::class, 'viewGuest'], ['auth', 'tenant']);
Router::get('/hotel/housekeeping', [App\Controllers\HotelController::class, 'housekeeping'], ['auth', 'tenant']);
Router::post('/hotel/housekeeping/create', [App\Controllers\HotelController::class, 'storeHousekeeping'], ['auth', 'tenant']);
Router::post('/hotel/housekeeping/{id}/{action}', [App\Controllers\HotelController::class, 'housekeepingAction'], ['auth', 'tenant']);
Router::get('/hotel/services', [App\Controllers\HotelController::class, 'services'], ['auth', 'tenant']);
Router::post('/hotel/services/create', [App\Controllers\HotelController::class, 'storeService'], ['auth', 'tenant']);
Router::post('/hotel/services/{id}/{action}', [App\Controllers\HotelController::class, 'serviceAction'], ['auth', 'tenant']);
Router::get('/hotel/reports', [App\Controllers\HotelController::class, 'reports'], ['auth', 'tenant']);

// -------------------- PAYMENTS --------------------
Router::get('/payments', [App\Controllers\PaymentController::class, 'list'], ['auth', 'tenant']);
Router::get('/payments/pay', [App\Controllers\PaymentController::class, 'pay'], ['auth', 'tenant']);
Router::post('/payments/initiate', [App\Controllers\PaymentController::class, 'initiate'], ['auth', 'tenant']);
Router::post('/payments/verify', [App\Controllers\PaymentController::class, 'verify'], ['auth', 'tenant']);
Router::post('/payments/cash', [App\Controllers\PaymentController::class, 'recordCash'], ['auth', 'tenant']);
Router::post('/payments/{id}/refund', [App\Controllers\PaymentController::class, 'refund'], ['auth', 'tenant']);
Router::get('/payments/{id}/receipt', [App\Controllers\PaymentController::class, 'receipt'], ['auth', 'tenant']);

// -------------------- NOTIFICATIONS --------------------
Router::get('/notifications', [App\Controllers\NotificationController::class, 'list'], ['auth']);
Router::get('/notifications/poll', [App\Controllers\NotificationController::class, 'poll'], ['auth']);
Router::post('/notifications/read', [App\Controllers\NotificationController::class, 'markRead'], ['auth']);
