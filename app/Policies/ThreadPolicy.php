<?php

namespace App\Policies;

use App\Models\User;

class ThreadPolicy extends OwnedPolicy
{
    protected string $module = 'thread';

    protected string $moderatorPermission = 'thread-moderate';

    public function view(User $user, $thread): bool
    {
        return parent::view($user, $thread) && ($thread->publication_status || $this->owns($user, $thread) || $this->moderates($user));
    }

    /** B5: hanya moderator yang menyembunyikan/menerbitkan thread. */
    public function publish(User $user): bool
    {
        return $this->moderates($user);
    }
}
