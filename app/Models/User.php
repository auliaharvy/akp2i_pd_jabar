<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'gender',
        'birthplace',
        'date_of_birth',

        'phone_number',
        'email',
        'email_verified_at',
        'password',
        'address',
        'photo',

        'division_id',
        'position_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /** Role yang boleh ditetapkan user ini ke orang lain (B4: hanya SA yang memegang SA & admin PD). */
    public function assignableRoles(): array
    {
        $roles = array_keys(config('akp2i.roles'));

        return $this->hasRole('super_administrator')
            ? $roles
            : array_values(array_diff($roles, ['super_administrator', 'admin_pd']));
    }

    /** B4: admin non-SA tidak boleh menyentuh akun SA atau admin PD. */
    public function canManage(User $target): bool
    {
        return $this->hasRole('super_administrator')
            || ! $target->hasAnyRole(['super_administrator', 'admin_pd']);
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public function threads()
    {
        return $this->hasMany(Thread::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
