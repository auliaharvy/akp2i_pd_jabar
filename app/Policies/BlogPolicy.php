<?php

namespace App\Policies;

use App\Models\User;

class BlogPolicy extends OwnedPolicy
{
    protected string $module = 'blog';

    protected string $moderatorPermission = 'blog-publish';

    public function view(User $user, $blog): bool
    {
        return parent::view($user, $blog) && ($blog->publication_status || $this->owns($user, $blog) || $this->moderates($user));
    }

    /** B5: hanya penerbit yang menentukan status terbit. */
    public function publish(User $user): bool
    {
        return $this->moderates($user);
    }
}
