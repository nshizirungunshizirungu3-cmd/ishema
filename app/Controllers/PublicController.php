<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Core\Translator;
use App\Models\ContactMessage;
use App\Models\SettingsModel;
use App\Models\Tutorial;

class PublicController extends Controller
{
    public function home(): void
    {
        $stats = [
            'businesses' => (int)Database::run('SELECT COUNT(*) AS c FROM businesses WHERE deleted_at IS NULL AND status = "active"')->fetch()['c'],
            'orders' => (int)Database::run('SELECT COUNT(*) AS c FROM orders')->fetch()['c'],
            'uptime' => '99.9%',
        ];
        echo $this->view('public/home', ['stats' => $stats]);
    }

    public function services(): void
    {
        echo $this->view('public/services');
    }

    public function about(): void
    {
        echo $this->view('public/about');
    }

    public function packages(): void
    {
        $packages = \App\Models\Package::active();
        $cycle = in_array(Security::input('billing', 'monthly'), ['monthly', 'yearly'], true)
            ? Security::input('billing', 'monthly')
            : 'monthly';
        echo $this->view('public/packages', ['packages' => $packages, 'billing' => $cycle]);
    }

    public function tutorials(): void
    {
        $category = Security::input('category', 'all');
        $search = (string)Security::input('search', '');
        $lang = Translator::locale();
        $items = Tutorial::active($category, $lang, $search);
        echo $this->view('public/tutorials', [
            'tutorials' => $items,
            'category' => $category,
            'search' => $search,
        ]);
    }

    public function contact(): void
    {
        $message = Session::flash('message');
        $error = Session::flash('error');
        echo $this->view('public/contact', ['message' => $message, 'error' => $error]);
    }

    public function sendContact(): void
    {
        $name = (string)Security::input('name', '');
        $email = (string)Security::input('email', '');
        $phone = (string)Security::input('phone', '');
        $subject = (string)Security::input('subject', '');
        $businessType = (string)Security::input('business_type', '');
        $message = (string)Security::input('message', '');

        if ($name === '' || !Security::isValidEmail($email) || $message === '') {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect('/contact');
            return;
        }

        ContactMessage::insert([
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'subject' => $subject !== '' ? $subject : null,
            'business_type' => $businessType !== '' ? $businessType : null,
            'message' => $message,
        ]);

        Session::flash('message', Translator::t('public.contact_success'));
        $this->redirect('/contact');
    }

    /** Request demo - same as contact but pre-fills subject. */
    public function requestDemo(): void
    {
        $_POST['subject'] = 'Demo Request';
        $this->sendContact();
    }
}
