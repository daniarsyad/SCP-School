<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Room;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MasterDataController extends Controller
{
    // ==================== KELAS ====================
    public function classes(Request $request)
    {
        $query = Classes::query();

        if ($request->filled('search')) {
            $query->where('class_name', 'like', '%' . $request->search . '%');
        }

        $classes = $query->orderBy('class_name')->get();

        return view('waka.master.classes', compact('classes'));
    }

    public function storeClass(Request $request)
    {
        $request->validate([
            'class_name' => 'required|string|max:50|unique:classes,class_name',
        ]);

        Classes::create(['class_name' => $request->class_name]);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function updateClass(Request $request, $id)
    {
        $request->validate([
            'class_name' => 'required|string|max:50|unique:classes,class_name,' . $id,
        ]);

        $class = Classes::findOrFail($id);
        $class->update(['class_name' => $request->class_name]);

        return back()->with('success', 'Kelas berhasil diupdate.');
    }

    public function deleteClass($id)
    {
        Classes::findOrFail($id)->delete();
        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    // ==================== RUANGAN ====================
    public function rooms(Request $request)
    {
        $query = Room::query();

        if ($request->filled('search')) {
            $query->where('room_name', 'like', '%' . $request->search . '%');
        }

        $rooms = $query->orderBy('room_name')->get();

        return view('waka.master.rooms', compact('rooms'));
    }

    public function storeRoom(Request $request)
    {
        $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name',
        ]);

        Room::create(['room_name' => $request->room_name]);

        return back()->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function updateRoom(Request $request, $id)
    {
        $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name,' . $id,
        ]);

        $room = Room::findOrFail($id);
        $room->update(['room_name' => $request->room_name]);

        return back()->with('success', 'Ruangan berhasil diupdate.');
    }

    public function deleteRoom($id)
    {
        Room::findOrFail($id)->delete();
        return back()->with('success', 'Ruangan berhasil dihapus.');
    }

    // ==================== USER ====================
    public function users(Request $request)
    {
        $query = User::with(['role', 'class']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', '%' . $search . '%')
                  ->orWhere('username', 'like', '%' . $search . '%');
            });
        }

        $users = $query->orderBy('fullname')->get();
        $roles = Role::all();
        $classes = Classes::orderBy('class_name')->get();

        return view('waka.master.users', compact('users', 'roles', 'classes'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            'class_id' => 'nullable|exists:classes,id',
        ]);

        User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
            'class_id' => $request->class_id,
        ]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $id,
            'password' => 'nullable|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            'class_id' => 'nullable|exists:classes,id',
        ]);

        $data = [
            'fullname' => $request->fullname,
            'username' => $request->username,
            'role_id'  => $request->role_id,
            'class_id' => $request->class_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'User berhasil diupdate.');
    }

    public function deleteUser($id)
    {
        if ($id == auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        User::findOrFail($id)->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}