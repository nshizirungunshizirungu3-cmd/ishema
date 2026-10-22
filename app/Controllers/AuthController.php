<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Core\Translator;
use App\Models\PasswordReset;
use App\Models\User;

class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::role() === 'SUPER_ADMIN' ? '/admin' : '/dashboard');
            return;
        }
        $error = Session::flash('error');
        echo $this->view('auth/login', ['error' => $error]);
    }

    public function doLogin(): void
    {
        $email = (string)Security::input('email', '');
        $password = (string)Security::post('password', '');

        if ($email === '' || $password === '') {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect('/login');
            return;
        }

        $result = Auth::attempt($email, $password);
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            $this->redirect('/login');
            return;
        }

        $role = $result['user']['role'];
        if ($role === 'SUPER_ADMIN') {
            $this->redirect('/admin');
            return;
        }

        // If the user has multiple businesses, go to a selector
        $links = \App\Models\BusinessUser::queryAs('bu')
            ->select('bu.business_id, b.name, b.type')
            ->join('businesses b', 'b.id = bu.business_id')
            ->where('bu.user_id', (int)$result['user']['id'])
            ->where('bu.is_active', 1)
            ->whereNull('b.deleted_at')
            ->get();
        if (count($links) > 1 && !Session::has('active_business_id')) {
            Session::set('business_choices', $links);
            $this->redirect('/select-business');
            return;
        }

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('message', Translator::t('auth.logout_success'));
        $this->redirect('/');
    }

    public function forgot(): void
    {
        $sent = Session::flash('sent');
        $error = Session::flash('error');
        echo $this->view('auth/forgot', ['sent' => $sent, 'error' => $error]);
    }

    public function doForgot(): void
    {
        $email = (string)Security::input('email', '');
        $user = User::findByEmail($email);
        if ($user) {
            $token = Security::generateToken(48);
            PasswordReset::create($user['email'], $token);
            // In production, send the link by email. For local dev the token is displayed.
            Session::flash('sent', true);
            Session::flash('dev_token', $token); // remove when email sending is configured
        } else {
            Session::flash('sent', true); // do not reveal whether the email exists
        }
        $this->redirect('/forgot-password');
    }

    public function reset(string $token): void
    {
        $row = PasswordReset::valid($token);
        if (!$row) {
            Session::flash('error', Translator::t('auth.reset_link_invalid'));
            $this->redirect('/forgot-password');
            return;
        }
        $error = Session::flash('error');
        echo $this->view('auth/reset', ['token' => $token, 'error' => $error]);
    }

    public function doReset(string $token): void
    {
        $row = PasswordReset::valid($token);
        if (!$row) {
            Session::flash('error', Translator::t('auth.reset_link_invalid'));
            $this->redirect('/forgot-password');
            return;
        }
        $password = (string)Security::post('password', '');
        $confirm = (string)Security::post('confirm_password', '');
        if (strlen($password) < 8) {
            Session::flash('error', Translator::t('auth.password_min'));
            $this->redirect('/reset-password/' . $token);
            return;
        }
        if ($password !== $confirm) {
            Session::flash('error', Translator::t('auth.passwords_match'));
            $this->redirect('/reset-password/' . $token);
            return;
        }
        $user = User::findByEmail($row['email']);
        if ($user) {
            User::update((int)$user['id'], ['password' => password_hash($password, PASSWORD_DEFAULT)]);
        }
        PasswordReset::consume((int)$row['id']);
        Session::flash('message', Translator::t('auth.password_reset_success'));
        $this->redirect('/login');
    }

    public function switchLanguage(string $lang): void
    {
        $lang = strtolower(substr($lang, 0, 2));
        if (array_key_exists($lang, Translator::LANGS)) {
            Translator::setLocale($lang);
            Session::set('locale', $lang);
            setcookie('ishema_lang', $lang, time() + 31536000, '/', '', false, true);
            $user = Auth::user();
            if ($user) {
                User::update((int)$user['id'], ['language' => $lang]);
            }
        }
        $ref = $_SERVER['HTTP_REFERER'] ?? Router::url('/');
        header('Location: ' . $ref);
        exit;
    }

    public function selectBusiness(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }
        $choices = Session::get('business_choices', []);
        if (empty($choices)) {
            $this->redirect('/dashboard');
            return;
        }
        echo $this->view('auth/select-business', ['choices' => $choices]);
    }

    public function doSelectBusiness(): void
    {
        $businessId = (int)Security::post('business_id', 0);
        if (!\App\Core\Tenant::switchTo($businessId)) {
            Session::flash('error', Translator::t('common.invalid_request'));
            $this->redirect('/select-business');
            return;
        }
        Session::delete('business_choices');
        $this->redirect('/dashboard');
    }
}
