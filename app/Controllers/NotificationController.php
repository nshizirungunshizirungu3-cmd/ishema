<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\NotificationService;
use App\Core\Security;

class NotificationController extends Controller
{
    public function list(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->json(['ok' => false], 401);
            return;
        }
        $notifications = NotificationService::forUser((int)$user['id'], 50);
        $this->json(['ok' => true, 'notifications' => $notifications]);
    }

    public function poll(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->json(['ok' => false], 401);
            return;
        }
        $this->json([
            'ok' => true,
            'unread' => NotificationService::unreadCount((int)$user['id']),
            'notifications' => NotificationService::forUser((int)$user['id'], 10),
        ]);
    }

    public function markRead(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->json(['ok' => false], 401);
            return;
        }
        $id = (int)Security::input('id', 0);
        if ($id > 0) {
            NotificationService::markRead($id, (int)$user['id']);
        } else {
            NotificationService::markAllRead((int)$user['id']);
        }
        $this->json(['ok' => true, 'unread' => NotificationService::unreadCount((int)$user['id'])]);
    }
}
