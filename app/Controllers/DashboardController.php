<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\NotificationService;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Core\Tenant;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        if ($user['role'] === 'SUPER_ADMIN') {
            $this->redirect('/admin');
            return;
        }

        $business = Tenant::current();
        if (!$business) {
            echo $this->view('dashboard/no-business');
            return;
        }

        $overview = ReportService::businessOverview((int)$business['id'], (string)$business['type']);
        $notifications = NotificationService::forUser((int)$user['id'], 10);
        $unread = NotificationService::unreadCount((int)$user['id']);

        echo $this->view('dashboard/index', [
            'business' => $business,
            'overview' => $overview,
            'notifications' => $notifications,
            'unread' => $unread,
        ]);
    }

    public function subscription(): void
    {
        $business = Tenant::current();
        if (!$business) {
            $this->redirect('/dashboard');
            return;
        }
        $sub = \App\Models\Subscription::currentForBusiness((int)$business['id']);
        $packages = \App\Models\Package::active();
        $history = \App\Models\Subscription::forBusiness((int)$business['id']);
        echo $this->view('dashboard/subscription', [
            'business' => $business,
            'subscription' => $sub,
            'packages' => $packages,
            'history' => $history,
        ]);
    }

    public function profile(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
            return;
        }
        $message = Session::flash('message');
        $error = Session::flash('error');
        echo $this->view('dashboard/profile', ['user' => $user, 'message' => $message, 'error' => $error]);
    }

    public function updateProfile(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
            return;
        }
        $name = (string)Security::input('name', '');
        $phone = (string)Security::input('phone', '');
        $language = (string)Security::input('language', 'en');
        if ($name === '') {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect('/profile');
            return;
        }
        if (!array_key_exists($language, \App\Core\Translator::LANGS)) {
            $language = 'en';
        }
        $data = ['name' => $name, 'phone' => $phone !== '' ? $phone : null, 'language' => $language];
        \App\Models\User::update((int)$user['id'], $data);
        Session::set('locale', $language);
        \App\Core\Translator::setLocale($language);
        Session::flash('message', Translator::t('common.record_updated'));
        $this->redirect('/profile');
    }

    public function changePassword(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->json(['ok' => false, 'error' => 'Not authenticated'], 401);
            return;
        }
        $current = (string)Security::post('current_password', '');
        $new = (string)Security::post('new_password', '');
        $confirm = (string)Security::post('confirm_password', '');

        if (!password_verify($current, (string)$user['password'])) {
            $this->json(['ok' => false, 'error' => 'Current password is incorrect.'], 422);
            return;
        }
        if (strlen($new) < 8) {
            $this->json(['ok' => false, 'error' => \App\Core\Translator::t('auth.password_min')], 422);
            return;
        }
        if ($new !== $confirm) {
            $this->json(['ok' => false, 'error' => \App\Core\Translator::t('auth.passwords_match')], 422);
            return;
        }
        \App\Models\User::update((int)$user['id'], ['password' => password_hash($new, PASSWORD_DEFAULT)]);
        $this->json(['ok' => true, 'message' => 'Password updated successfully.']);
    }
}
