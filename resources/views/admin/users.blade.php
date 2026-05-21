@extends('layouts.master')

@section('title', 'Manage Users')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold"><i class="fas fa-users"></i> Manage Users</h1>
    <button onclick="showCreateUserModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
        <i class="fas fa-plus"></i> Add New User
    </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-3 md:grid-cols-6 gap-3 mb-6">
    <div class="bg-white rounded-xl p-3 text-center">
        <p class="text-xl font-bold">{{ $stats['total'] }}</p>
        <p class="text-xs text-gray-500">Total</p>
    </div>
    <div class="bg-purple-50 rounded-xl p-3 text-center">
        <p class="text-xl font-bold text-purple-600">{{ $stats['sellers'] }}</p>
        <p class="text-xs text-gray-500">Sellers</p>
    </div>
    <div class="bg-blue-50 rounded-xl p-3 text-center">
        <p class="text-xl font-bold text-blue-600">{{ $stats['customers'] }}</p>
        <p class="text-xs text-gray-500">Customers</p>
    </div>
    <div class="bg-red-50 rounded-xl p-3 text-center">
        <p class="text-xl font-bold text-red-600">{{ $stats['admins'] }}</p>
        <p class="text-xs text-gray-500">Admins</p>
    </div>
    <div class="bg-green-50 rounded-xl p-3 text-center">
        <p class="text-xl font-bold text-green-600">{{ $stats['active'] }}</p>
        <p class="text-xs text-gray-500">Active</p>
    </div>
    <div class="bg-gray-50 rounded-xl p-3 text-center">
        <p class="text-xl font-bold text-gray-600">{{ $stats['blocked'] }}</p>
        <p class="text-xs text-gray-500">Blocked</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl p-4 shadow-sm mb-4 flex flex-wrap gap-3">
    <a href="{{ route('admin.users') }}" class="px-3 py-1 rounded-full text-sm {{ !request()->get('role') ? 'bg-indigo-600 text-white' : 'bg-gray-200' }}">All</a>
    <a href="{{ route('admin.users', ['role' => 'seller']) }}" class="px-3 py-1 rounded-full text-sm {{ request()->get('role') == 'seller' ? 'bg-purple-600 text-white' : 'bg-gray-200' }}">Sellers</a>
    <a href="{{ route('admin.users', ['role' => 'user']) }}" class="px-3 py-1 rounded-full text-sm {{ request()->get('role') == 'user' ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">Customers</a>
    <a href="{{ route('admin.users', ['status' => 'active']) }}" class="px-3 py-1 rounded-full text-sm {{ request()->get('status') == 'active' ? 'bg-green-600 text-white' : 'bg-gray-200' }}">Active</a>
    <a href="{{ route('admin.users', ['status' => 'blocked']) }}" class="px-3 py-1 rounded-full text-sm {{ request()->get('status') == 'blocked' ? 'bg-red-600 text-white' : 'bg-gray-200' }}">Blocked</a>
</div>

<!-- Users Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr><th class="p-3 text-left">Name</th><th class="p-3 text-left">Email</th><th class="p-3 text-left">Role</th><th class="p-3 text-left">Status</th><th class="p-3 text-left">Joined</th><th class="p-3 text-center">Actions</th></tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-3 font-medium">{{ $user->name }}</td>
                <td class="p-3">{{ $user->email }}</td>
                <td class="p-3">
                    <span class="px-2 py-1 rounded-full text-xs {{ $user->role == 'admin' ? 'bg-red-100 text-red-800' : ($user->role == 'seller' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800') }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </td>
                <td class="p-3">
                    @if($user->is_active)
                        <span class="text-green-600 text-sm">✅ Active</span>
                    @else
                        <span class="text-red-600 text-sm">❌ Blocked</span>
                    @endif
                </td>
                <td class="p-3 text-sm">{{ $user->created_at->format('d M Y') }}</td>
                <td class="p-3 text-center">
                    <a href="{{ route('admin.users.show', $user) }}" class="text-indigo-600 hover:underline mr-2">View</a>
                    @if($user->id != Auth::id())
                        @if($user->is_active)
                            <form action="{{ route('admin.users.block', $user) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="reason" value="Blocked by admin">
                                <button type="submit" class="text-orange-600 hover:underline" onclick="return confirm('Block this user?')">Block</button>
                            </form>
                        @else
                            <form action="{{ route('admin.users.unblock', $user) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:underline" onclick="return confirm('Unblock this user?')">Unblock</button>
                            </form>
                        @endif
                        <form action="{{ route('admin.users.delete', $user) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline ml-2" onclick="return confirm('Delete this user?')">Delete</button>
                        </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="p-4">{{ $users->links() }}</div>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <h3 class="text-lg font-bold mb-4">Add New User</h3>
        <form action="{{ route('admin.users.create') }}" method="POST">
            @csrf
            <input type="text" name="name" placeholder="Full Name" class="w-full p-2 border rounded-lg mb-3" required>
            <input type="email" name="email" placeholder="Email" class="w-full p-2 border rounded-lg mb-3" required>
            <input type="password" name="password" placeholder="Password" class="w-full p-2 border rounded-lg mb-3" required>
            <select name="role" class="w-full p-2 border rounded-lg mb-3" required>
                <option value="user">Customer</option>
                <option value="seller">Seller</option>
            </select>
            <div class="flex gap-3">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg">Create User</button>
                <button type="button" onclick="hideCreateUserModal()" class="bg-gray-300 px-4 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateUserModal() { document.getElementById('createUserModal').classList.remove('hidden'); }
function hideCreateUserModal() { document.getElementById('createUserModal').classList.add('hidden'); }
</script>
@endsection