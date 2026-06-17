<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()->orderBy('id')->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', [
            'user' => new User,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('users.index')->with('status', 'Пользователь добавлен.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', [
            'user' => $user,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('status', 'Пользователь обновлён.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return back()->withErrors(['delete' => 'Нельзя удалить свою учётную запись.']);
        }

        if ($user->isAdmin() && User::query()->where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->withErrors(['delete' => 'Нельзя удалить единственного администратора.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Пользователь удалён.');
    }

    /** @return array<string, mixed> */
    private function validateUser(Request $request, ?User $user = null): array
    {
        $isEdit = $user !== null;

        $passwordRules = $isEdit
            ? ['nullable', 'string', 'confirmed', Password::min(8)]
            : ['required', 'string', 'confirmed', Password::min(8)];

        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => [
                'required',
                'string',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in([User::ROLE_MANAGER, User::ROLE_ADMIN])],
            'password' => $passwordRules,
        ], [
            'password.confirmed' => 'Пароли не совпадают.',
        ]);
    }
}
